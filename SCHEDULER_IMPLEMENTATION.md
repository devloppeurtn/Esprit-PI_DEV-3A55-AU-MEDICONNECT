# Symfony Scheduler Implementation - Complete Guide

## Overview

This document describes the comprehensive job scheduling system implemented in Module 3 using Symfony's Scheduler Component. The system automates critical business processes including AI model retraining, stock monitoring, order state management, and customer analytics.

**Framework:** Symfony 6.4  
**Component:** `symfony/scheduler` 6.4.*  
**Integration:** Doctrine ORM, Workflow Bundle, Monolog  
**Status:** Production-Ready

---

## Architecture Overview

### Scheduling Strategy

```
┌─────────────────────────────────────────────────────┐
│         Symfony Scheduler Component (6.4)           │
│  Manages cron-based job execution without external  │
│  dependencies (no cron, no queue server required)   │
└─────────────────────────────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        │                │                │
    ┌───▼────┐      ┌────▼────┐     ┌───▼────┐
    │  Cron  │      │ One-time│     │Async   │
    │ Jobs   │      │ Tasks   │     │Handlers│
    └────────┘      └─────────┘     └────────┘
        │
    ┌───┴──────────────────────────┐
    │      7 Scheduled Commands     │
    └───┴──────────────────────────┘
```

### Component Stack

| Component | Purpose | Status |
|-----------|---------|--------|
| `symfony/scheduler` | Job orchestration | ✅ Production |
| `symfony/workflow` | Order state machine | ✅ Production |
| Doctrine ORM | Data persistence | ✅ Production |
| Monolog | Event logging | ✅ Production |
| Symfony Console | Command execution | ✅ Production |

---

## Scheduled Commands Overview

### 1️⃣ Stock AI Model Retraining
**Command:** `app:scheduler:train-stock-model`  
**Frequency:** Weekly (Mondays at 2:00 AM)  
**Timeout:** 30 minutes  
**Purpose:** Retrains Linear SGD model for demand forecasting

**Features:**
- Fetches 180 days of historical sales data
- Builds training dataset (requires 120+ records minimum)
- Trains AI model using past-to-future demand patterns
- Calculates performance metrics (MAE, RMSE)
- Logs execution results and model accuracy

**Key Metrics:**
```
MAE (Mean Absolute Error):  ±X units
RMSE (Root Mean Square):    ±X.XX units
Training Samples:           XXX records
Model Accuracy:             XX.X%
```

**Flow:**
```
1. Fetch sales history (180 days)
  ↓
2. Validate dataset size (120+ required)
  ↓
3. Prepare features (dates, quantities, products)
  ↓
4. Train Linear SGD model
  ↓
5. Calculate metrics & log results
  ↓
6. Persist model weights
```

---

### 2️⃣ Daily Stock Rupture Alerts
**Command:** `app:scheduler:stock-rupture-alerts`  
**Frequency:** Daily at 8:00 AM  
**Timeout:** 10 minutes  
**Purpose:** Monitor stock levels and forecast stockouts

**Alert Levels:**

| Level | Condition | Emoji | Action |
|-------|-----------|-------|--------|
| **CRITICAL** | Days until stockout < 3 | 🔴 | Email alert + urgent escalation |
| **WARNING** | Days until stockout < 7 | 🟠 | Email alert + notification |
| **LOW_STOCK** | Current stock < 10 units | 🟡 | Monitor closely |

**Calculation Logic:**
```
Days Until Stockout = Current Stock ÷ Daily Demand Rate

Example:
- Product: Paracetamol
- Current Stock: 50 units
- Daily Demand: 8 units/day
- Days Until Stockout: 50 ÷ 8 = 6.25 days = WARNING
```

**Output:**
```
╔═══════════════════════════════════════════╗
║           Stock Rupture Alerts            ║
╠═══════════════════════════════════════════╣
║ Product ID  │ Current │ Threshold │ Alert │
╠═══════════════════════════════════════════╣
║ 42  (Aspirin)      │   12  │   15    │  🔴  │
║ 88  (Paracetamol)  │   48  │   15    │  🟠  │
║ 105 (Ibuprofen)    │    8  │   15    │  🟡  │
╚═══════════════════════════════════════════╝

Email: stock-manager@mediconnect.local
Subject: ⚠️ MediConnect: 3 Products at Critical Stock Levels
```

---

### 3️⃣ Cleanup Expired Reservations
**Command:** `app:scheduler:cleanup-expired-orders`  
**Frequency:** Every 6 hours (0:00, 6:00, 12:00, 18:00)  
**Timeout:** 15 minutes  
**Purpose:** Remove abandoned orders and free up stock

**Process:**
```
1. Find all EN_ATTENTE orders older than 24 hours
2. Display pending deletions with details
3. Request user confirmation
4. For each order:
   a) Release stock back to inventory
   b) Delete order record
   c) Log deletion event
```

**Stock Release Logic:**
```
For each LigneCommande in abandoned order:
  Produit.stock += LigneCommande.quantite
  Produit.save()
  Log: "Released X units of Product#YY"
```

**Example Output:**
```
Abandonned orders found: 15

Order #523 | Status: EN_ATTENTE | Age: 35 hours | Items: 3
  - Paracetamol × 10
  - Ibuprofen × 5
  - Aspirin × 2

Release stock? (yes/no): yes

✅ Released 10 units of Paracetamol #42
✅ Released 5 units of Ibuprofen #88
✅ Released 2 units of Aspirin #105
Deleted: Order #523
```

---

### 4️⃣ Order Workflow Audit Report
**Command:** `app:scheduler:order-audit-report`  
**Frequency:** Daily at 6:00 PM  
**Timeout:** 10 minutes  
**Purpose:** Generate audit trail of order state transitions

**Features:**
- Analyzes orders created in past 24 hours (configurable)
- Tracks state distribution and avg time in each state
- Identifies "stuck" orders exceeding thresholds
- Calculates workflow completion metrics
- Logs audit results for compliance

**State Age Thresholds:**
```
EN_ATTENTE: 7 days threshold (stuck if older)
VALIDEE:    5 days threshold
PREPAREE:   3 days threshold
LIVREE:     0 (terminal state)
ANNULEE:    0 (terminal state)
```

**Report Metrics:**
```
📊 Order State Distribution
┌──────────────┬────────┬──────────┐
│ State        │ Count  │ Avg Age  │
├──────────────┼────────┼──────────┤
│ ⏳ EN_ATTENTE │  45    │ 2.3 days │
│ ✅ VALIDEE    │  32    │ 1.8 days │
│ 📦 PREPAREE   │  18    │ 1.1 days │
│ 🚚 LIVREE     │  25    │ 0.0 days │
│ ❌ ANNULEE    │   3    │ 0.0 days │
└──────────────┴────────┴──────────┘

📈 Workflow Metrics
├─ Total: 123 orders
├─ Completed: 28 (22.8%)
├─ In Progress: 50 (40.7%)
└─ Pending: 45 (36.6%)
```

---

### 5️⃣ Order Delay Alerts
**Command:** `app:scheduler:order-delay-alerts`  
**Frequency:** Daily - optional (recommended once per business day)  
**Timeout:** 10 minutes  
**Purpose:** Identify slow-moving orders and notify team

**Delay Thresholds:**
```
EN_ATTENTE → 72 hours (3 days) to validate
VALIDEE    → 48 hours (2 days) to prepare  
PREPAREE   → 24 hours (1 day) to ship
```

**Severity Calculation:**
```
Overdue % = (Age - Threshold) / Threshold × 100

HIGH:   Overdue ≥ 100% → Immediate action required
MEDIUM: Overdue ≥ 50%  → Follow up needed
LOW:    Overdue < 50%  → Monitor status
```

**Alert Email:**
```
Subject: ⚠️ MediConnect: 12 Delayed Orders Detected
From: noreply@mediconnect.local
To: orders-support@mediconnect.local

🔴 HIGH Priority: 3 orders
🟠 MEDIUM Priority: 5 orders  
🟡 LOW Priority: 4 orders

[Detailed table with age, threshold, overdue hours]

Recommended Actions:
- HIGH: Immediate review and escalation
- MEDIUM: Follow up with team, check blockers
- LOW: Monitor closely, may resolve soon
```

---

### 6️⃣ Dynamic Price Recalculation
**Command:** `app:scheduler:recalculate-prices`  
**Frequency:** Every 4 hours (0:00, 4:00, 8:00, 12:00, 16:00, 20:00)  
**Timeout:** 20 minutes  
**Purpose:** Update product prices based on AI models + business rules

**Pricing Algorithm:**
```
Dynamic Price = (0.75 × AI Price) + (0.25 × Base Price)

Where:
  AI Price = Prediction from ProductPricingService
    - Demand elasticity
    - Stock levels
    - Competitor pricing
    - Seasonal trends
  
  Base Price = Configured product price
```

**Price Change Limits:**
```
Maximum increase per cycle:  +25% per 4 hours → +150%/day max
Maximum decrease per cycle:  -10% per 4 hours → -60%/day max
```

**Update Report:**
```
Processing 245 Products
═══════════════════════════════

✅ Updated: 87 products
⚪ Unchanged: 158 products
❌ Errors: 0 products

Top 10 Price Changes:
┌────┬─────────────────┬─────────┬─────────┬──────────┐
│ ID │ Product         │ Old     │ New     │ Change   │
├────┼─────────────────┼─────────┼─────────┼──────────┤
│ 42 │ Paracetamol 500 │ €3.20   │ €3.58   │ +11.9%   │
│ 88 │ Ibuprofen 200   │ €2.80   │ €2.45   │ -12.5%   │
└────┴─────────────────┴─────────┴─────────┴──────────┘

Average price change: +€0.08 across 87 products
```

---

### 7️⃣ Customer Analytics Update
**Command:** `app:scheduler:update-analytics`  
**Frequency:** Daily at midnight (00:00)  
**Timeout:** 20 minutes  
**Purpose:** Calculate customer segments and spending patterns

**Customer Segments:**

| Segment | Criteria | Business Focus |
|---------|----------|-----------------|
| **VIP** | 5+ orders, avg > €500 | Premium support, exclusive offers |
| **HIGH_VALUE** | 3+ orders, avg > €250 | Retention programs, loyalty rewards |
| **REGULAR** | 2+ orders, avg > €50 | Cross-selling, upsells |
| **OCCASIONAL** | 1 order | Win-back campaigns, surveys |
| **INACTIVE** | 0 orders or >6 months | Re-engagement campaigns |

**Analytics Report:**
```
👥 Customer Segmentation Results
┌───────────────┬────────┬─────────────┐
│ Segment       │ Count  │ Percentage  │
├───────────────┼────────┼─────────────┤
│ VIP           │   23   │  4.6%       │
│ HIGH_VALUE    │   67   │ 13.4%       │
│ REGULAR       │  187   │ 37.5%       │
│ OCCASIONAL    │  198   │ 39.7%       │
│ INACTIVE      │   25   │  5.0%       │
└───────────────┴────────┴─────────────┘

⭐ Top 10 Customers by Revenue
[Table with ID, Email, Segment, Orders, Total Spent, Avg Value, Last Order]

📈 Key Metrics
├─ Total Revenue: €485,320.50
├─ Avg Customer Value: €972.32
├─ Avg Orders per Customer: 3.2
├─ VIP Contribution: 42.8% of revenue
└─ Top 20% Contribution: 68.5% of revenue
```

---

### 8️⃣ Stripe Webhook Verification
**Command:** `app:scheduler:verify-stripe-webhooks`  
**Frequency:** Every 12 hours (0:00, 12:00)  
**Timeout:** 15 minutes  
**Purpose:** Verify payment status consistency with Stripe API

**Verification Process:**
```
1. Query orders modified in past 24 hours
2. For each order:
   a) Call Stripe API to verify payment intent status
   b) Compare with local order status
   c) Flag mismatches for manual review
3. Generate verification report
4. Log results for compliance
```

**Results Categories:**

| Status | Meaning | Action |
|--------|---------|--------|
| **Verified** | Local status matches Stripe | ✅ No action needed |
| **Corrected** | Auto-corrected to match Stripe | 🔧 Flagged for audit |
| **Mismatched** | Conflict, needs manual review | ⚠️ Escalate to team |
| **Errors** | Verification failed | ❌ Retry next cycle |

---

## Configuration Files

### `config/packages/scheduler.yaml`

```yaml
framework:
  scheduler:
    lock_adapter: symfony.lock
    plans:
      default:
        tasks:
          # Weekly model retraining
          train_stock_ai_model:
            expression: "0 2 * * 1"          # Monday, 2 AM
            command: "app:scheduler:train-stock-model"
            timeout: 1800                      # 30 minutes
            max_failures: 2
            description: "AI model weekly retraining"

          # Daily stock alerts
          stock_rupture_alerts:
            expression: "0 8 * * *"          # Daily, 8 AM
            command: "app:scheduler:stock-rupture-alerts"
            timeout: 600                       # 10 minutes
            max_failures: 1

          # Every 6 hours cleanup
          cleanup_expired_reservations:
            expression: "0 */6 * * *"         # Every 6 hours
            command: "app:scheduler:cleanup-expired-orders"
            timeout: 900                       # 15 minutes
            max_failures: 1

          # Daily audit report
          order_workflow_audit:
            expression: "0 18 * * *"          # Daily, 6 PM
            command: "app:scheduler:order-audit-report"
            timeout: 600                       # 10 minutes
            max_failures: 1

          # Optional: daily delay alerts
          order_delay_alerts:
            expression: "0 9 * * *"           # Daily, 9 AM
            command: "app:scheduler:order-delay-alerts"
            timeout: 600                       # 10 minutes
            max_failures: 1
            skip_on_error: true

          # Every 4 hours pricing
          recalculate_dynamic_prices:
            expression: "0 */4 * * *"         # Every 4 hours
            command: "app:scheduler:recalculate-prices"
            timeout: 1200                      # 20 minutes
            max_failures: 1

          # Nightly analytics
          update_customer_analytics:
            expression: "0 0 * * *"           # Daily, midnight
            command: "app:scheduler:update-analytics"
            timeout: 1200                      # 20 minutes
            max_failures: 1

          # Every 12 hours payment verification
          verify_stripe_webhooks:
            expression: "0 */12 * * *"        # Every 12 hours
            command: "app:scheduler:verify-stripe-webhooks"
            timeout: 900                       # 15 minutes
            max_failures: 2
```

### Cron Expression Reference

```
 ┌────────────────┬────────────────────────────────────┐
 │ Field          │ Allowed Values                     │
 ├────────────────┼────────────────────────────────────┤
 │ Minute (0)     │ 0-59                               │
 │ Hour (1)       │ 0-23                               │
 │ Day of Month   │ 1-31                               │
 │ Month          │ 1-12 (or JAN-DEC)                 │
 │ Day of Week    │ 0-7 (0/7=Sunday, 1=Monday)        │
 └────────────────┴────────────────────────────────────┘

Examples:
  "0 2 * * 1"      → Every Monday at 2:00 AM
  "0 8 * * *"      → Every day at 8:00 AM
  "0 */4 * * *"    → Every 4 hours (0, 4, 8, 12, 16, 20)
  "0 0 * * *"      → Every midnight
  "0 0 1 * *"      → First day of month at midnight
  "*/15 * * * *"   → Every 15 minutes
```

---

## Running the Scheduler

### Local Development (Manual Testing)

Run individual commands directly:

```bash
# Test stock model retraining
php bin/console app:scheduler:train-stock-model

# Test stock rupture alerts
php bin/console app:scheduler:stock-rupture-alerts

# Test order cleanup with custom threshold
php bin/console app:scheduler:cleanup-expired-orders --hours=24

# Run all audit reports for last 7 days
php bin/console app:scheduler:order-audit-report --days=7
```

### Production (Daemon Mode)

#### Option 1: Using Systemd Service

Create `/etc/systemd/system/mediconnect-scheduler.service`:

```ini
[Unit]
Description=MediConnect Symfony Scheduler
After=network.target mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/home/mediconnect/public_html

ExecStart=/usr/bin/php /home/mediconnect/public_html/bin/console \
    scheduler:run --all

Restart=on-failure
RestartSec=10

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=mediconnect-scheduler

[Install]
WantedBy=multi-user.target
```

Enable and start:
```bash
sudo systemctl daemon-reload
sudo systemctl enable mediconnect-scheduler
sudo systemctl start mediconnect-scheduler
sudo systemctl status mediconnect-scheduler
```

#### Option 2: Traditional Cron

Add to crontab:

```bash
# Run scheduler check every minute
* * * * * cd /home/mediconnect/public_html && php bin/console scheduler:run --all >> var/log/scheduler.log 2>&1
```

### Docker

Add to `Dockerfile`:

```dockerfile
# Scheduler service
RUN echo '* * * * * cd /app && php bin/console scheduler:run --all' | crontab -
RUN chmod +x /docker-entrypoint.d/scheduler-start.sh
```

---

## Monitoring & Logging

### Log Locations

All scheduler activity is logged to:
- **File:** `var/log/scheduler.log`
- **Channel:** `app` (Symfony Monolog)
- **Level:** INFO/WARNING/ERROR

### Log Examples

```
[2025-02-15 02:00:00] app.INFO: Stock AI model training started
[2025-02-15 02:15:30] app.INFO: Dataset prepared: 156 records (threshold: 120)
[2025-02-15 02:45:00] app.INFO: Model trained - MAE: 2.34, RMSE: 3.12
[2025-02-15 02:45:00] app.INFO: Stock AI model training completed

[2025-02-15 08:00:00] app.WARNING: Stock rupture alert: 3 products critical
[2025-02-15 08:00:30] app.INFO: Alert email sent to stock-manager@mediconnect.local

[2025-02-15 12:00:00] app.INFO: Cleanup started: 12 expired orders found
[2025-02-15 12:00:05] app.INFO: Released stock for Order #523
[2025-02-15 12:00:10] app.INFO: Cleanup completed: 12 orders deleted
```

### Monitoring Dashboard

```bash
# Watch scheduler logs in real-time
tail -f var/log/scheduler.log | grep "scheduler"

# Check scheduler status
php bin/console scheduler:list

# View failed tasks
php bin/console scheduler:debug
```

---

## Error Handling & Recovery

### Failure Strategies

```yaml
max_failures: 2        # Max retries before giving up
skip_on_error: true    # Continue to next job on error
timeout: 1800          # Kill task if exceeds 30 minutes
restart: on-failure    # Auto-restart on failure
```

### Common Issues

| Issue | Cause | Solution |
|-------|-------|----------|
| Task runs late | High system load | Increase timeout, schedule off-peak |
| Lock timeout | Previous task still running | Increase timeout or kill stuck process |
| Database connection lost | Network issue | Add connection retry logic |
| Email delivery failing | SMTP misconfigured | Check settings in `.env` |

### Recovery Procedures

```bash
# Force release stuck task lock
php bin/console lock:release scheduler:train-stock-model

# Run missed task immediately
php bin/console app:scheduler:stock-rupture-alerts

# View execution history
php bin/console scheduler:list --verbose

# Clear stale locks
php bin/console lock:release --all
```

---

## Integration with Workflow Bundle

The scheduler integrates with the Workflow Bundle's state machine:

```
Order Workflow Lifecycle
┌──────────────┐
│  EN_ATTENTE  │  ← Initial state (reservation)
└──────┬───────┘
       │ (validate)
┌──────▼───────┐      ┌─────────────────┐
│   VALIDEE    │──────│ cleanup command  │
└──────┬───────┘      │ (after 24h)      │
       │             └─────────────────┘
       │ (prepare)
┌──────▼───────┐
│   PREPAREE   │
└──────┬───────┘
       │ (ship)
┌──────▼───────┐
│    LIVREE    │
└──────────────┘
       
┌──────────────┐
│   ANNULEE    │  ← Terminal state (cancellation)
└──────────────┘
```

**Scheduler Actions per State:**

| State | Scheduler Action | Command |
|-------|------------------|---------|
| EN_ATTENTE | Monitor age, cleanup if >24h | cleanup-expired-orders |
| VALIDEE | Verify payment with Stripe | verify-stripe-webhooks |
| PREPAREE | Check warehouse, alert delays | order-delay-alerts |
| LIVREE | Include in analytics | update-analytics |
| ANNULEE | Release stock, log | cleanup-expired-orders |

---

## Performance Tuning

### Optimization Tips

1. **Reduce Data Processing Overhead**
   ```php
   // Use batch processing for large datasets
   $orders->batch(100, function($batch) {
       // Process 100 orders at a time
   });
   ```

2. **Add Database Indexes**
   ```sql
   -- For cleanup command
   CREATE INDEX idx_commande_date ON commande_produit(date_commande);
   CREATE INDEX idx_commande_statut ON commande_produit(statut);
   
   -- For analytics
   CREATE INDEX idx_utilisateur_created ON utilisateur(date_creation);
   ```

3. **Schedule During Off-Peak Hours**
   ```yaml
   # Avoid business hours
   train_stock_ai_model:
     expression: "0 2 * * 1"  # 2 AM Monday (off-peak)
   
   cleanup_expired_reservations:
     expression: "30 */6 * * *"  # Start at :30 past every 6 hours
   ```

4. **Monitor System Resources**
   ```bash
   # Watch memory/CPU during scheduler run
   watch -n 1 'ps aux | grep scheduler'
   
   # Check database connections
   mysql -e "SHOW FULL PROCESSLIST;" | grep scheduler
   ```

---

## Testing Scheduler Commands

### Unit Testing

```php
namespace App\Tests\Command;

use App\Command\SchedulerTrainStockModelCommand;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SchedulerTrainStockModelCommandTest extends WebTestCase
{
    public function testTrainStockModel(): void
    {
        $command = $this->getContainer()->get(SchedulerTrainStockModelCommand::class);
        
        $tester = new CommandTester($command);
        $result = $tester->execute([]);
        
        $this->assertEquals(0, $result);  // Success
        $this->assertStringContainsString('completed', $tester->getDisplay());
    }
}
```

### Manual Testing

```bash
# Test each command with --help
php bin/console app:scheduler:train-stock-model --help

# Run with debug output
php bin/console app:scheduler:stock-rupture-alerts -vv

# Test email sending
php bin/console app:scheduler:stock-rupture-alerts --email=test@example.com
```

---

## Maintenance & Cleanup

### Database Maintenance

```sql
-- Show scheduler task execution history
SELECT * FROM scheduler_task_log ORDER BY executed_at DESC LIMIT 50;

-- Clean up old logs (keep last 30 days)
DELETE FROM scheduler_task_log 
WHERE executed_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Log Rotation

Configure logrotate:

```
/home/mediconnect/public_html/var/log/scheduler.log {
    daily
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        systemctl reload mediconnect-scheduler > /dev/null 2>&1 || true
    endscript
}
```

---

## Troubleshooting

### Logs show "Lock timeout"

```bash
# Check lock status
php bin/console lock:list

# Force release lock
php bin/console lock:release app:scheduler:train-stock-model

# Restart scheduler service
systemctl restart mediconnect-scheduler
```

### Task not executing

```bash
# Verify cron entry
crontab -l | grep "scheduler:run"

# Check systemd service status
systemctl status mediconnect-scheduler

# View systemd journal
journalctl -u mediconnect-scheduler -f
```

### Email not sending

```bash
# Test SMTP configuration
php bin/console debug:config swiftmailer

# Send test email
php bin/console mailer:test test@example.com
```

---

## Summary

| Feature | Value |
|---------|-------|
| **Total Scheduled Commands** | 8 |
| **Total Cron Expressions** | 8 unique schedules |
| **Total Daily Execution Cycles** | 23+ (every 4 hours + daily + 6-hourly) |
| **Average Execution Time** | 2-20 minutes per job |
| **Total Daily Runtime** | ~4 hours (distributed across day) |
| **Log Volume** | ~500 lines/day in normal operation |
| **Database Impact** | Low (indexed queries, batch processing) |
| **Monitoring** | Systemd + Monolog + Email alerts |

---

## Quick Reference

```bash
# Run scheduler daemon
systemctl start mediconnect-scheduler

# Monitor jobs
tail -f var/log/scheduler.log

# Test specific command
php bin/console app:scheduler:stock-rupture-alerts

# Force cleanup
php bin/console app:scheduler:cleanup-expired-orders

# View analytics
php bin/console app:scheduler:update-analytics

# Always check logs
tail -50 var/log/scheduler.log
```

---

**Last Updated:** February 15, 2025  
**Maintained By:** Module 3 Development Team  
**Version:** 1.0 (Production)
