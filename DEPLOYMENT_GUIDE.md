# Workflow & Scheduler Deployment Guide

## Quick Start - Production Deployment

### Phase 1: Install Dependencies (5 minutes)

```bash
cd /path/to/MediConnect-isramodule3

# Install Symfony components
composer require symfony/workflow:6.4.* symfony/scheduler:6.4.*

# Update autoloader
composer dump-autoload --optimize
```

### Phase 2: Configure Bundles (2 minutes)

✅ Already done - verify in `config/bundles.php`:

```php
return [
    // ...
    Symfony\Bundle\WorkflowBundle\WorkflowBundle::class => ['all' => true],
    // ...
];
```

### Phase 3: Create Database Tables (5 minutes)

```bash
# Create migrations for OrderAuditLog entity
php bin/console make:migration --name="AddOrderAuditLog"

# Create OrderAuditLog entity with:
# - order_id (FK to CommandeProduit)
# - transition (string: validate, prepare, ship, etc.)
# - from_state, to_state (enum: EN_ATTENTE, VALIDEE, etc.)
# - executed_at (DateTime)
# - userId (string, nullable - user or "scheduler")
# - metadata (JSON - IP, reason, evidence)

php bin/console doctrine:migrations:migrate
```

#### Order Audit Log Entity Template

Create `src/Entity/OrderAuditLog.php`:

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'order_audit_log')]
class OrderAuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: CommandeProduit::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CommandeProduit $commande;

    #[ORM\Column(type: 'string')]
    private string $transition;

    #[ORM\Column(type: 'string')]
    private string $fromState;

    #[ORM\Column(type: 'string')]
    private string $toState;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $executedAt;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $userId = null;

    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    public function __construct()
    {
        $this->id = (string) Uuid::v4();
        $this->executedAt = new \DateTime();
    }

    // ... getters/setters ...
}
```

### Phase 4: Verify Configuration Files (2 minutes)

Check these three files exist and are correct:

```bash
# Workflow config
ls config/packages/workflow.yaml
# Should define: order_workflow with 5 places and 8 transitions

# Scheduler config  
ls config/packages/scheduler.yaml
# Should define: 7 scheduled commands with cron expressions

# Event listener
ls src/EventListener/OrderWorkflowListener.php
# Should have guard logic and audit logging
```

---

## Production Setup

### Option A: Systemd Service (Recommended)

#### Step 1: Create Service File

```bash
sudo nano /etc/systemd/system/mediconnect-scheduler.service
```

Paste:

```ini
[Unit]
Description=MediConnect Symfony Scheduler Daemon
Documentation=https://mediconnect.example.com/docs
After=network.target mysql.service php-fpm.service
Wants=mysql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/mediconnect

# Start scheduler daemon
ExecStart=/usr/bin/php /var/www/mediconnect/bin/console scheduler:run --all

# Restart on failure
Restart=on-failure
RestartSec=10
StartLimitInterval=60s
StartLimitBurst=3

# Process management
KillMode=process
KillSignal=SIGTERM
TimeoutStartSec=0
TimeoutStopSec=30

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=mediconnect-scheduler

# Security
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=/var/www/mediconnect/var /var/log

[Install]
WantedBy=multi-user.target
```

#### Step 2: Enable and Start Service

```bash
# Reload systemd daemon
sudo systemctl daemon-reload

# Enable service to start on boot
sudo systemctl enable mediconnect-scheduler.service

# Start the service
sudo systemctl start mediconnect-scheduler.service

# Check status
sudo systemctl status mediconnect-scheduler.service

# View logs
journalctl -u mediconnect-scheduler.service -f
```

#### Step 3: Monitor Service

```bash
# Check if running
sudo systemctl is-active mediconnect-scheduler.service

# Restart if needed
sudo systemctl restart mediconnect-scheduler.service

# View recent logs (last 50 lines)
journalctl -u mediconnect-scheduler.service -n 50

# View logs since last boot
journalctl -u mediconnect-scheduler.service -b
```

---

### Option B: Traditional Cron (Alternative)

#### Step 1: Create Cron Entry

```bash
# Open crontab
crontab -e

# Add this line to run scheduler every minute
# This allows Symfony Scheduler to check and execute tasks
* * * * * cd /var/www/mediconnect && /usr/bin/php bin/console scheduler:run --all >> /var/log/mediconnect/scheduler.log 2>&1
```

#### Step 2: Verify Cron Entry

```bash
# List all cron jobs
crontab -l

# Should show something like:
# Scheduler
# * * * * * cd /var/www/mediconnect && /usr/bin/php bin/console scheduler:run --all >> /var/log/mediconnect/scheduler.log 2>&1
```

---

### Option C: Docker Deployment

#### Step 1: Update Dockerfile

```dockerfile
FROM php:8.1-fpm

# ... existing PHP setup ...

# Install scheduler daemon
COPY --chown=www-data:www-data . /app

# Create scheduler startup script
RUN echo '#!/bin/sh\ncd /app\nexec php bin/console scheduler:run --all' > /docker-scheduler.sh && \
    chmod +x /docker-scheduler.sh

# Expose port if needed
EXPOSE 9000

# Start both FPM and scheduler
CMD ["/bin/sh", "-c", "php-fpm & /docker-scheduler.sh"]
```

#### Step 2: Docker Compose Update

```yaml
version: '3.8'
services:
  app:
    build: .
    container_name: mediconnect_app
    environment:
      APP_ENV: prod
      APP_DEBUG: 'false'
    volumes:
      - ./var:/app/var
    depends_on:
      - db

  scheduler:
    build: .
    container_name: mediconnect_scheduler
    command: php bin/console scheduler:run --all
    environment:
      APP_ENV: prod
      APP_DEBUG: 'false'
    volumes:
      - ./var:/app/var
    depends_on:
      - app
      - db

  db:
    image: mysql:8.0
    # ... configuration ...
```

---

## Environment Configuration

### .env Settings

Add or update in `.env.local`:

```bash
# Scheduler
SCHEDULER_ENABLED=true
SCHEDULER_TIMEZONE=Europe/Paris

# Stock Alerts Email
STOCK_ALERT_EMAIL=stock-manager@mediconnect.local
STOCK_ALERT_RECIPIENTS=supply-chain@mediconnect.local,ceo@mediconnect.local

# Orders Support Email
ORDERS_SUPPORT_EMAIL=orders-support@mediconnect.local

# AI Model Settings
AI_MODEL_RETRAINING_MIN_SAMPLES=120
AI_MODEL_RETRAINING_LOOKBACK_DAYS=180

# Price Recalculation
PRICE_MAX_INCREASE_PERCENT=25
PRICE_MAX_DECREASE_PERCENT=10

# Order Cleanup
ORDER_CLEANUP_THRESHOLD_HOURS=24
ORDER_CLEANUP_BATCH_SIZE=100
```

---

## Running Commands Manually (Development/Testing)

### Test Individual Scheduler Commands

```bash
# Test stock alerts (displays table, sends email)
php bin/console app:scheduler:stock-rupture-alerts

# Test order cleanup (interactive, asks for confirmation)
php bin/console app:scheduler:cleanup-expired-orders --hours=24

# Test audit report (shows last 1 day)
php bin/console app:scheduler:order-audit-report --days=1

# Test with 7 days of data
php bin/console app:scheduler:order-audit-report --days=7

# Test delay alerts
php bin/console app:scheduler:order-delay-alerts --email=test@example.com

# Test price recalculation  
php bin/console app:scheduler:recalculate-prices

# Test analytics update
php bin/console app:scheduler:update-analytics

# Test AI model training
php bin/console app:scheduler:train-stock-model

# Test Stripe webhook verification
php bin/console app:scheduler:verify-stripe-webhooks
```

### Debugging Scheduler Issues

```bash
# Check which tasks are registered
php bin/console scheduler:list

# Run scheduler with verbose output
php bin/console scheduler:run --all -vvv

# Check scheduled expression (cron-like)
php bin/console debug:config framework.scheduler

# View Monolog logs in real-time
tail -f var/log/app.log | grep scheduler

# Check system lock adapter status
php bin/console lock:list
```

---

## Post-Deployment Verification

### Checklist

```
✓ Composer dependencies installed
  └─ composer.lock includes symfony/workflow and symfony/scheduler

✓ Configuration files created
  └─ config/packages/workflow.yaml
  └─ config/packages/scheduler.yaml

✓ Event listener registered
  └─ src/EventListener/OrderWorkflowListener.php

✓ Scheduler commands created
  └─ 7+ command files in src/Command/Scheduler*Command.php

✓ Database migrations
  └─ OrderAuditLog table created
  └─ Proper indexes on commande_produit (statut, date_commande)

✓ Service running
  └─ systemctl status mediconnect-scheduler (or cron job active)

✓ Logs being written
  └─ var/log/scheduler.log exists and has recent entries

✓ Email configured
  └─ MAILER_DSN in .env.local
  └─ Test email sent successfully

✓ Database accessible
  └─ Scheduler can read from all referenced tables
  └─ Scheduler can write audit logs
```

### Test Verification

```bash
# 1. Check configuration loads without errors
php bin/console config:dump-reference framework.scheduler

# 2. Verify workflow loads correctly
php bin/console debug:config framework.workflows

# 3. Test a command executes without errors
php bin/console app:scheduler:stock-rupture-alerts --no-interaction

# 4. Check logs for command execution
tail -20 var/log/app.log

# 5. Verify service is running
ps aux | grep "scheduler:run"

# 6. Test database write capability
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM order_audit_log LIMIT 1"
```

---

## Maintenance Tasks

### Daily

```bash
# Monitor scheduler service
systemctl status mediconnect-scheduler.service

# Check for errors in logs
grep ERROR var/log/app.log | tail -5

# Verify recent scheduler executions
grep "scheduler" var/log/app.log | tail -10
```

### Weekly

```bash
# Archive old logs
find var/log -name "*.log" -mtime +30 -delete

# Verify all commands executed at least once
grep -o "app:scheduler:[^ ]*" var/log/app.log | sort -u

# Check database growth
du -h var/

# Review audit log table size
php bin/console doctrine:query:sql "SELECT COUNT(*) as count FROM order_audit_log"
```

### Monthly

```bash
# Rebuild database indexes
php bin/console cache:clear --env=prod

# Check disk space
df -h /var/www/mediconnect/

# Export audit reports
php bin/console app:scheduler:order-audit-report --days=30 > reports/audit-$(date +%Y%m%d).txt

# Database optimization
php bin/console doctrine:query:sql "OPTIMIZE TABLE commande_produit, order_audit_log"
```

---

## Troubleshooting

### Problem: "Command not found"

```bash
# Solution: Rebuild autoloader
composer dump-autoload --optimize
php bin/console cache:clear --env=prod
```

### Problem: "Scheduler not executing tasks"

```bash
# Check if service is running
systemctl status mediconnect-scheduler.service

# Manually run one iteration
php bin/console scheduler:run --all -vvv

# Check cron job (if using cron method)
crontab -l

# Verify write permissions to var/log
ls -la var/log/
```

### Problem: "Lock timeout error"

```bash
# List active locks
php bin/console lock:list

# Force release stuck lock
php bin/console lock:release app:scheduler:train-stock-model

# Or release all locks
php bin/console lock:release --all
```

### Problem: "Email not sending"

```bash
# Test SMTP configuration
php bin/console mailer:test admin@example.com

# Check MAILER_DSN in .env
grep MAILER_DSN .env.local

# Verify email settings in services.yaml
php bin/console debug:config swiftmailer
```

### Problem: "Guard blocks valid transition"

```bash
# Check order has sufficient stock
php bin/console doctrine:query:sql "
  SELECT p.id, p.nom, p.stock 
  FROM produit p 
  WHERE p.id IN (
    SELECT DISTINCT up.produit_id FROM ligne_commande up
  )
"

# Check if order validation guard is too strict
# Edit: src/EventListener/OrderWorkflowListener.php
# Look for: hasRequiredStock() method
```

---

## Performance Optimization

### Database Optimization

```sql
-- Create indexes for faster queries
CREATE INDEX idx_commande_statut ON commande_produit(statut);
CREATE INDEX idx_commande_date ON commande_produit(date_commande);
CREATE INDEX idx_audit_order ON order_audit_log(commande_id);
CREATE INDEX idx_audit_date ON order_audit_log(executed_at);

-- Analyze tables
ANALYZE TABLE commande_produit, order_audit_log;

-- Check execution plans
EXPLAIN SELECT * FROM commande_produit WHERE statut = 'EN_ATTENTE' AND date_commande < NOW() - INTERVAL 1 DAY;
```

### Memory Optimization

```php
// In command execute() method, process in batches
$orders->batch(500, function(array $batch) {
    foreach ($batch as $order) {
        // Process order
        $this->processOrder($order);
    }
    $this->entityManager->clear();  // Free memory
});
```

### Cron/Systemd Optimization

```bash
# Reduce scheduler check frequency (if using cron)
# Instead of every minute, run every 5 minutes:
*/5 * * * * cd /var/www && php bin/console scheduler:run --all

# But ensure commands still run at correct times
# (Scheduler adjusts for missed runs)
```

---

## Monitoring & Alerting

### Prometheus Metrics (Optional)

Add to your monitoring:

```bash
# Total scheduled commands executed
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM order_audit_log WHERE executed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"

# Average command execution time
php bin/console doctrine:query:sql "SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) FROM scheduler_execution_log"

# Failure rate
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM scheduler_execution_log WHERE status = 'FAILED' AND executed_at > DATE_SUB(NOW(), INTERVAL 7 DAY)"
```

### Nagios/Zabbix Checks

```bash
#!/bin/bash
# Check if mediconnect-scheduler service is running
service=mediconnect-scheduler
if systemctl is-active --quiet $service; then
    echo "OK - Scheduler running"
    exit 0
else
    echo "CRITICAL - Scheduler not running"
    exit 2
fi
```

---

## Rollback Procedure

If something goes wrong:

```bash
# 1. Stop scheduler
sudo systemctl stop mediconnect-scheduler.service

# 2. Rollback code  
git revert <commit-hash>
composer install

# 3. Rollback database
php bin/console doctrine:migrations:migrate prev

# 4. Clear caches
php bin/console cache:clear --env=prod

# 5. Restart scheduler
sudo systemctl start mediconnect-scheduler.service

# 6. Monitor logs
journalctl -u mediconnect-scheduler.service -f
```

---

## Support & Documentation

### Key Files

| File | Purpose |
|------|---------|
| `config/packages/workflow.yaml` | Workflow state machine definition |
| `config/packages/scheduler.yaml` | Cron schedule configuration |
| `src/EventListener/OrderWorkflowListener.php` | Guard logic + audit logging |
| `src/Command/Scheduler*.php` | 7 scheduler commands |
| `SCHEDULER_IMPLEMENTATION.md` | Detailed scheduler guide |
| `WORKFLOW_SCHEDULER_INTEGRATION.md` | Architecture & design |

### Quick Reference

```bash
# Start scheduler
sudo systemctl start mediconnect-scheduler.service

# Stop scheduler
sudo systemctl stop mediconnect-scheduler.service

# View logs
journalctl -u mediconnect-scheduler.service -f

# Run test
php bin/console app:scheduler:stock-rupture-alerts

# Check status
sudo systemctl status mediconnect-scheduler.service
```

---

**Last Updated:** February 15, 2025  
**Version:** 1.0 (Production Ready)
