# Workflow & Scheduler Integration Guide

## Overview

This document explains how Symfony's Workflow Bundle (state machine) integrates with the Scheduler Component to create a complete order lifecycle management system with automated monitoring, validation, and compliance tracking.

---

## Architecture Overview

### System Components Interaction

```
┌──────────────────────────────────────────────────────────┐
│      Order Workflow State Machine (Strict)               │
│  Manages: EN_ATTENTE → VALIDEE → PREPAREE → LIVREE      │
│  Guards: Stock validation, payment verification, address │
│  Events: Fired for each transition, tracked for audit    │
└──────────────────────────────────┬───────────────────────┘
                                   │
                    ┌──────────────┼──────────────┐
                    │              │              │
         ┌──────────▼────┐  ┌──────▼────────┐  ┌─▼─────────┐
         │  EventListener │  │  Scheduler    │  │ Audit Log │
         │  (Guards)      │  │  Commands     │  │ (Monolog) │
         └────────────────┘  └───────────────┘  └───────────┘
```

### Data Flow

```
Customer Places Order
       ↓
Workflow: EN_ATTENTE (Initial state + audit log: "Order created")
       ↓
Scheduler: daily stock alerts checking EN_ATTENTE orders
       ↓
Guard: Verify sufficient stock (blocks if insufficient)
       ↓
Workflow: VALIDEE (if guard passes + audit log: "Order validated")
       ↓
Scheduler: daily delay alerts checking VALIDEE orders
       ↓
Guard: Verify payment mode defined
       ↓
Workflow: PREPAREE (warehouse prep + audit log: "Order prepared")
       ↓
Guard: Verify delivery address exists
       ↓
Workflow: LIVREE (shipped + audit log: "Order shipped")
       ↓
Scheduler: nightly analytics update (includes completed orders)
```

---

## Workflow State Machine Details

### State Definition

```yaml
# config/packages/workflow.yaml
order_workflow:
  type: 'state_machine'
  audit_trail:
    enabled: true
  places:
    - EN_ATTENTE      # Awaiting validation (reservation)
    - VALIDEE         # Validated and payment confirmed
    - PREPAREE        # Being prepared in warehouse
    - LIVREE          # Delivered to customer
    - ANNULEE         # Cancelled (terminal state)
  
  marking_store:
    type: 'method'
    property: 'statut'  # Property on CommandeProduit entity
```

### Transitions & Guards

```yaml
transitions:
  validate:
    from: EN_ATTENTE
    to: VALIDEE
    metadata:
      label: "Valider la commande"
      description: "Passer au statut validé après vérification du stock"
    # Guard in EventListener: Stock check
  
  prepare:
    from: VALIDEE
    to: PREPAREE
    metadata:
      label: "Préparer la commande"
      # Guard: Payment mode must be defined
  
  ship:
    from: PREPAREE
    to: LIVREE
    metadata:
      label: "Expédier la commande"
      # Guard: Delivery address required
  
  cancel_pending:
    from: EN_ATTENTE
    to: ANNULEE
    metadata:
      label: "Annuler la commande"
  
  cancel_validated:
    from: VALIDEE
    to: ANNULEE
    metadata:
      label: "Annuler la commande"
  
  cancel_prepared:
    from: PREPAREE
    to: ANNULEE
    metadata:
      label: "Annuler la commande"
  
  reopen:
    from: ANNULEE
    to: EN_ATTENTE
    metadata:
      label: "Réouvrir la commande"
```

---

## EventListener Integration

### Order Workflow Event Listener

Located: `src/EventListener/OrderWorkflowListener.php`

**Purpose:** Enforce business rules via guards and track state changes

**Event Handlers:**

#### 1. Guard Transition Event

```php
#[AsEventListener(
    event: 'workflow.order_workflow.guard.validate',
    dispatcher: WorkflowEvents::GUARD,
)]
public function onGuardValidate(GuardEvent $event): void
{
    $order = $event->getSubject();
    
    // Check: Sufficient stock available
    if (!$this->hasRequiredStock($order)) {
        $event->setBlocked(true, 'Stock insuffisant pour cette commande');
        $this->logger->warning('Transition bloquée: Stock insuffisant', [
            'order_id' => $order->getId(),
            'required_items' => count($order->getLigneCommandes()),
        ]);
    }
}
```

**Guard Rules:**

| Transition | Guard | Error Message |
|-----------|-------|---------------|
| validate | hasRequiredStock() | "Stock insuffisant pour cette commande" |
| prepare | isPaymentModeSet() | "Mode de paiement non défini" |
| ship | hasDeliveryAddress() | "Adresse de livraison manquante" |
| cancel_* | logAuditTrail() | (Always logs) |
| reopen | isEligibleForReopen() | "Commande ne peut pas être réouverte" |

#### 2. Enter Place Event (Called when entering a state)

```php
#[AsEventListener(
    event: 'workflow.order_workflow.enter.VALIDEE',
    dispatcher: WorkflowEvents::ENTERED,
)]
public function onEnterValidated(EnterPlaceEvent $event): void
{
    $order = $event->getSubject();
    
    // Lock stock: Mark items as reserved
    foreach ($order->getLigneCommandes() as $ligne) {
        $product = $ligne->getProduit();
        $product->lockStock($ligne->getQuantite());
    }
    
    // Notify warehouse
    $this->warehouseService->notifyIncomingOrder($order);
    
    // Log audit trail
    $this->logAuditEntry($order, 'VALIDEE', 'Order validated by system');
}
```

#### 3. Leave Place Event (Called when leaving a state)

```php
#[AsEventListener(
    event: 'workflow.order_workflow.leave.PREPAREE',
    dispatcher: WorkflowEvents::LEAVING,
)]
public function onLeavePrepared(LeaveEvent $event): void
{
    $order = $event->getSubject();
    
    // Log departure from PREPAREE
    $this->logger->info('Leaving PREPAREE state', [
        'order_id' => $order->getId(),
        'next_state' => $event->getTransition()->getName(),
    ]);
}
```

#### 4. Transition Completed (Called after successful transition)

```php
#[AsEventListener(
    event: 'workflow.order_workflow.completed',
    dispatcher: WorkflowEvents::COMPLETED,
)]
public function onTransitionCompleted(TransitionEvent $event): void
{
    $order = $event->getSubject();
    $transition = $event->getTransition();
    
    // Create audit entry
    $entry = new OrderAuditLog();
    $entry->setOrder($order)
        ->setTransition($transition->getName())
        ->setFromState($transition->getFrom()[0])
        ->setToState($transition->getTo()[0])
        ->setExecutedAt(new \DateTime())
        ->setUser($this->security->getUser())
        ->setDetails([
            'user_ip' => $this->request->getClientIp(),
            'user_agent' => $this->request->headers->get('User-Agent'),
        ]);
    
    $this->entityManager->persist($entry);
    $this->entityManager->flush();
}
```

---

## Scheduler Commands Integration

### 1. EN_ATTENTE Cleanup (6-hourly)

**Command:** `SchedulerCleanupExpiredOrdersCommand`

**Workflow Integration:**
```
Find: All orders in EN_ATTENTE state where age > 24h
Action: 
  - Transition via cancel_pending → ANNULEE
  - Release stock back to Produit.stock
  - Log: "Reservation expired - cancelled by scheduler"
Reason: Free up inventory from abandoned reservations
```

### 2. Stock Alerts (Daily 8 AM)

**Command:** `SchedulerStockRuptureAlertsCommand`

**Workflow Integration:**
```
Monitor: All EN_ATTENTE orders awaiting validation
Check: Can transitions to VALIDEE proceed?
  - If stock becomes insufficient: Flag for user review
  - If delay detected: Send alert email
Alert Levels:
  🔴 CRITICAL: Stock for 3+ pending orders unavailable
  🟠 WARNING: Stock depleting faster than expected
  🟡 LOW: less than 10 units remaining
```

### 3. Order Delay Alerts (Daily 9 AM)

**Command:** `SchedulerOrderDelayAlertsCommand`

**Workflow Integration:**
```
Monitor state aging:
  - EN_ATTENTE: stuck for >3 days → Validate immediately or cancel
  - VALIDEE: stuck for >2 days → Prepare and ship
  - PREPAREE: stuck for >1 day → Ship immediately
  
Trigger: Order > threshold age in state
Alert: Team notification with order details
Action: Recommend state transition or escalation
```

### 4. Audit Report (Daily 6 PM)

**Command:** `SchedulerOrderAuditReportCommand`

**Workflow Integration:**
```
Collect: All orders transitioned in past 24h
Analyze:
  - State distribution (how many in each state)
  - State duration (avg time in each state)
  - Stuck orders (exceeding thresholds)
  - Completion rate (orders reaching LIVREE)
  - Cancellation rate (orders reaching ANNULEE)
  
Audit Trail: Query OrderAuditLog for all transitions
Report: Email with compliance metrics
```

### 5. Dynamic Pricing (Every 4 hours)

**Command:** `SchedulerRecalculatePricesCommand`

**Workflow Integration:**
```
Impact: Orders in EN_ATTENTE state
Logic:
  - Recalculate product prices based on AI demand forecasting
  - If price increased >20%, notify customer via email
  - If price decreased, apply to pending order
  - Log price change in order audit trail
  
Timing: Recalculate before VALIDEE transition
Reason: Ensure pricing reflects current market conditions
```

### 6. Customer Analytics (Daily midnight)

**Command:** `SchedulerUpdateAnalyticsCommand`

**Workflow Integration:**
```
Segment Update:
  - VIP: 5+ orders → LIVREE state
  - REGULAR: 2+ orders → LIVREE state
  - Analyze: Customer LTV by segment
  - Track: Cancellation rate per segment (orders in ANNULEE)
  
Insights:
  - Which segments have highest failure rate (ANNULEE)?
  - Which segments complete faster (quick LIVREE)?
  - Identify retention risks early
  
Report: Nightly metrics email
```

### 7. Stripe Verification (Every 12 hours)

**Command:** `SchedulerVerifyStripeWebhooksCommand`

**Workflow Integration:**
```
Verify: Orders in VALIDEE/PREPAREE have confirmed payments
Check:
  - Stripe payment intent status matches local order status
  - No mismatches between local DB and Stripe
  - Flag payment failures that might prevent PREPAREE
  
Sync: If Stripe shows payment failed
  - Revert order from VALIDEE → EN_ATTENTE
  - Log: "Payment verification failed"
  - Notify customer: "Payment declined, retry required"
  
Reason: Ensure legitimate orders proceed only with confirmed payment
```

---

## State Diagram with Scheduler Actions

```
                         ┌─────────────────────┐
                         │   EN_ATTENTE        │
                         │  (Reservation)      │
                         └──────────┬──────────┘
                                    │
                   ┌────────────────┼────────────────┐
                   │                │                │
            ┌──────▼──────┐  ┌──────▼──────┐  ┌─────▼──────┐
            │  Scheduler   │  │  Guard:     │  │ EventListener
            │  (6h check)  │  │ hasStock()  │  │ lockStock()
            │  > 24h old   │  │ BLOCK if NO │  │ notify WH
            └──────┬──────┘  └──────┬──────┘  
                   │                │         
            ┌──────▼──────┐  ┌──────▼──────┐
            │ ANNULEE     │  │   VALIDEE   │
            │(Cancelled)  │◄─┤ (Validated) │
            └─────────────┘  └──────┬──────┘
                                    │
                  ┌─────────────────┼─────────────────┐
                  │                 │                 │
           ┌──────▼──────┐   ┌──────▼──────┐   ┌─────▼──────┐
           │ Scheduler    │   │ Guard:      │   │ Scheduler  │
           │ (12h check)  │   │ isPayment() │   │ (daily)    │
           │ verify Stripe│   │ BLOCK if NO │   │ delay alert
           └─────────────┘   └──────┬──────┘   └────────────┘
                                    │
                              ┌─────▼──────┐
                              │  PREPAREE  │
                              │(Preparing) │
                              └────┬───────┘
                                   │
                  ┌────────────────┬┼┬────────────┐
                  │                ││ │            │
           ┌──────▼──────┐   ┌─────▼┼▼────┐  ┌────▼─────┐
           │ ANNULEE     │   │ Guard: addr │  │ Scheduler │
           │(Cancelled)  │◄──┤ BLOCK if NO │  │ always    │
           └─────────────┘   └─────┬──────┘  │ monitors  │
                                   │         └───────────┘
                            ┌──────▼──────┐
                            │   LIVREE    │
                            │ (Delivered) │
                            └─────────────┘
                                   │
                            ┌──────▼──────┐
                            │ Scheduler   │
                            │ (nightly)   │
                            │ analytics   │
                            └─────────────┘

≈ Scheduler Action Points
✓ Guard Enforcement Points
```

---

## Audit Trail Architecture

### Storage

```php
// Entity: OrderAuditLog
#[ORM\Entity]
#[ORM\Table(name: 'order_audit_log')]
class OrderAuditLog
{
    #[ORM\Column(type: 'string')]
    private string $transition;      // validate, prepare, ship, cancel_*
    
    #[ORM\Column(type: 'string')]
    private string $fromState;       // EN_ATTENTE, VALIDEE, etc.
    
    #[ORM\Column(type: 'string')]
    private string $toState;         // VALIDEE, PREPAREE, etc.
    
    #[ORM\Column(type: 'datetime')]
    private \DateTime $executedAt;   // When transition occurred
    
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $userId;         // Who triggered (user or "scheduler")
    
    #[ORM\Column(type: 'json')]
    private array $metadata = [];     // Extra context (IP, reason, etc.)
    
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $reason;         // Guard block reason
}
```

### Audit Log Examples

```
Order #523 - Paracetamol × 10
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
2025-02-15 14:32:10 | EN_ATTENTE → VALIDEE
  By: user:42 (admin@mediconnect.local)
  IP: 192.168.1.100
  Reason: Manual validation via dashboard
  Guard Checks: ✓ Stock verified ✓ Payment set

2025-02-15 14:35:45 | VALIDEE → PREPAREE
  By: scheduler (automatic)
  Timestamp: 14:35:45
  Details: Auto-transition after payment confirmation

2025-02-15 18:20:30 | PREPAREE → LIVREE
  By: user:87 (warehouse@mediconnect.local)
  IP: 192.168.1.105
  Tracking: DHL-2025-0123456
  Evidence: GPS signature confirmed

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Order Status: LIVREE (Delivered)
Lifecycle Time: 3 hours 48 minutes
```

### Querying Audit Trail

```php
// Find all transitions for an order
$logs = $auditRepository->findByOrder($order);

// Find orders stuck in state
$stuckOrders = $auditRepository->findStuckInState(
    'EN_ATTENTE',
    new \DateInterval('P3D')  // 3 days
);

// Compliance report
$report = $auditRepository->getComplianceReport(
    start: new \DateTime('2025-02-01'),
    end: new \DateTime('2025-02-15')
);
```

---

## Example: Complete Order Lifecycle

### Manual Process (Before Workflow)

```
Customer orders → Manual validation (Days 1-2)
                → Add to warehouse queue (Days 3-4)
                → Wait for shipment slot (Days 5-7)
                → Manual shipment (Day 8)
                → No audit trail
                Total: 8 days, high error rate
```

### Automated Process (With Workflow + Scheduler)

```
2025-02-15 14:30
├─ Customer places order
├─ System: EN_ATTENTE state + audit log
└─ Stock locked (5 units reserved)

2025-02-15 14:32
├─ Guard: Stock check ✓
├─ Transition: EN_ATTENTE → VALIDEE
├─ System: Lock stock for real
├─ Warehouse notified: "New order ready for pickup"
└─ Audit: "Order validated - stock verified"

2025-02-15 18:00 (Scheduler: Delay Alert Check)
├─ Order age: 3.5 hours in VALIDEE
├─ Threshold: 48 hours
├─ Status: ON SCHEDULE ✓

2025-02-16 08:00 (Scheduler: Daily Stock Alerts)
├─ Check: Can orders proceed?
├─ Status: Stock available ✓
├─ No alerts needed

2025-02-16 10:15
├─ Payment confirmed via Stripe webhook
├─ Guard: Payment mode verified ✓
├─ Transition: VALIDEE → PREPAREE
├─ System: Assign to warehouse
├─ Audit: "Order prepared - payment confirmed"

2025-02-16 18:00 (Scheduler: Audit Report)
├─ Order age in PREPAREE: 8 hours
├─ Threshold: 24 hours
├─ Status: ON TIME ⚡

2025-02-17 09:30
├─ Guard: Delivery address verified ✓
├─ Transition: PREPAREE → LIVREE
├─ System: Create shipping label
├─ Email: Tracking number sent to customer
├─ Audit: "Order shipped - DHL-2025-0123456"

2025-02-17 00:00 (Scheduler: Analytics Update)
├─ Order data collected
├─ Customer segment updated (1 order completed)
├─ Metrics: Order completion speed = 1.5 days

Total: 1.5 days (instead of 8), fully audited, automated
```

---

## Testing Workflow + Scheduler Integration

### Unit Test Example

```php
namespace App\Tests\Integration;

use App\Entity\CommandeProduit;
use App\Enum\StatutCommande;
use App\Repository\CommandeProduitRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class OrderWorkflowTest extends KernelTestCase
{
    private WorkflowInterface $workflow;
    private CommandeProduitRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = self::getContainer()->get('workflow.order_workflow');
        $this->repo = self::getContainer()->get(CommandeProduitRepository::class);
    }

    public function testStockGuardBlocksInvalidTransition(): void
    {
        // Create order with insufficient stock
        $order = new CommandeProduit();
        $order->setStatut(StatutCommande::EN_ATTENTE);
        // ... set insufficient stock ...

        // Attempt transition (should be blocked)
        $this->assertFalse(
            $this->workflow->can($order, 'validate'),
            'Transition should be blocked due to insufficient stock'
        );
    }

    public function testValidTransitionSucceeds(): void
    {
        // Create order with sufficient stock
        $order = new CommandeProduit();
        $order->setStatut(StatutCommande::EN_ATTENTE);
        // ... set sufficient stock ...

        // Attempt transition (should succeed)
        $this->assertTrue(
            $this->workflow->can($order, 'validate'),
            'Transition should succeed with sufficient stock'
        );

        $this->workflow->apply($order, 'validate');
        $this->assertEquals(StatutCommande::VALIDEE, $order->getStatut());
    }
}
```

### Integration Test with Scheduler

```php
public function testSchedulerCleansupExpiredOrders(): void
{
    // Create 24+ hour old order in EN_ATTENTE
    $expiredOrder = $this->createOrder(
        statut: StatutCommande::EN_ATTENTE,
        age: new \DateInterval('P2D')  // 2 days old
    );

    // Run cleanup command
    $command = $this->getContainer()->get(SchedulerCleanupExpiredOrdersCommand::class);
    $tester = new CommandTester($command);
    $result = $tester->execute(['--hours' => '24']);

    // Verify order was cancelled
    $this->assertTrue($this->workflow->can($expiredOrder, 'cancel_pending'));
    $this->assertEquals(
        StatutCommande::ANNULEE,
        $this->repo->find($expiredOrder->getId())->getStatut()
    );
}
```

---

## Monitoring & Alerting

### Dashboard Metrics

```
Order Workflow Dashboard
┌─────────────────────────────────────────┐
│ Current State Distribution              │
│ EN_ATTENTE:  45 (36.6%) ▓▓▓▓▓░░░░░░    │
│ VALIDEE:     32 (26.0%) ▓▓▓░░░░░░░░    │
│ PREPAREE:    18 (14.6%) ▓▓░░░░░░░░░    │
│ LIVREE:      25 (20.3%) ▓▓░░░░░░░░░    │
│ ANNULEE:      3 (2.4%)  ░░░░░░░░░░░    │
└─────────────────────────────────────────┘

State Duration Metrics
┌─────────────────────────────────────────┐
│ EN_ATTENTE:  avg 2.3 days (target <3d) │
│ VALIDEE:     avg 1.8 days (target <2d) │
│ PREPAREE:    avg 1.1 days (target <1d) │
│ LIVREE:      avg 0.0 days              │
└─────────────────────────────────────────┘

Completion Metrics
┌─────────────────────────────────────────┐
│ Orders completed (LIVREE): 58%          │
│ Orders cancelled (ANNULEE): 2%          │
│ Orders stuck: 40% (NEED ATTENTION)      │
│ Average order time: 5.2 days            │
│ Target order time: 3 days               │
│ ⚠️ Running 73% over target!             │
└─────────────────────────────────────────┘
```

### Alert Triggers

```
🔴 CRITICAL ALERTS (Immediate Action)
├─ Orders stuck >7 days in EN_ATTENTE
├─ Stock unavailable for EN_ATTENTE orders
├─ Payment failures in VALIDEE orders
└─ Scheduler command failed >2x in 24h

🟠 WARNING ALERTS (Within 4 hours)
├─ Orders stuck >4 days in VALIDEE
├─ Orders stuck >2 days in PREPAREE
├─ 10+ orders awaiting validation
└─ Scheduler response time >timeout

🟡 INFO ALERTS (Daily Report)
├─ Completion rate summary
├─ Average order time
├─ Customer segment updates
└─ Audit trail integrity check
```

---

## Best Practices

1. **Always use workflows for state machines** - Don't store raw status strings
2. **Implement guards for business rules** - Prevent invalid transitions at DB level
3. **Audit everything** - Create audit log entries for compliance
4. **Schedule monitoring tasks** - Let scheduler handle repetitive checks
5. **Set realistic thresholds** - Base on actual business metrics
6. **Monitor scheduler execution** - Watch logs, not just results
7. **Test state transitions** - Unit test all guard conditions
8. **Document state meanings** - Each state explains why it exists

---

**Last Updated:** February 15, 2025  
**Version:** 1.0 (Complete Integration)
