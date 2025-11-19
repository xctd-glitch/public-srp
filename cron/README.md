# SRP Cron Jobs - Quick Reference

## 🚀 Quick Setup (Recommended)

```bash
cd /home/gassstea/trackng.us/cron
chmod +x setup-cron.sh
./setup-cron.sh
```

Script akan otomatis:
- ✓ Detect PHP path
- ✓ Make scripts executable
- ✓ Create directories (backups, logs)
- ✓ Generate crontab entries
- ✓ Install cronjobs (optional)
- ✓ Test all scripts
- ✓ Create security .htaccess

---

## 📋 Available Cron Jobs

### 1. cleanup.php - Log Cleanup
```bash
php cleanup.php [days]
```
**Default:** 7 days
**Schedule:** Daily 2 AM
**Purpose:** Delete old traffic logs

### 2. health-check.php - System Monitor
```bash
php health-check.php
```
**Schedule:** Every 15 minutes
**Purpose:** Monitor system health

### 3. backup.php - Database Backup
```bash
php backup.php [retention_days]
```
**Default:** 30 days
**Schedule:** Daily 3 AM
**Purpose:** Backup database

---

## ⚡ Manual Setup (cPanel)

### Via cPanel Cron Jobs:

1. Login cPanel → Cron Jobs
2. Add these jobs:

**Cleanup (Daily 2 AM):**
```
0 2 * * * /usr/bin/php /home/gassstea/trackng.us/cron/cleanup.php 7
```

**Health Check (Every 15 min):**
```
*/15 * * * * /usr/bin/php /home/gassstea/trackng.us/cron/health-check.php
```

**Backup (Daily 3 AM):**
```
0 3 * * * /usr/bin/php /home/gassstea/trackng.us/cron/backup.php 30
```

---

## 🖥️ Manual Setup (SSH)

```bash
# Edit crontab
crontab -e

# Add these lines:
0 2 * * * /usr/bin/php /home/gassstea/trackng.us/cron/cleanup.php 7 >> /home/gassstea/trackng.us/logs/cron-cleanup.log 2>&1
*/15 * * * * /usr/bin/php /home/gassstea/trackng.us/cron/health-check.php >> /home/gassstea/trackng.us/logs/cron-health.log 2>&1
0 3 * * * /usr/bin/php /home/gassstea/trackng.us/cron/backup.php 30 >> /home/gassstea/trackng.us/logs/cron-backup.log 2>&1

# Save and exit (:wq in vim)
```

---

## 🧪 Testing

```bash
cd /home/gassstea/trackng.us

# Test cleanup
php cron/cleanup.php 7

# Test health check
php cron/health-check.php

# Test backup
php cron/backup.php 30
```

---

## 📊 Monitoring

### View Logs:
```bash
# Cleanup log
tail -f /home/gassstea/trackng.us/logs/cron-cleanup.log

# Health check log
tail -f /home/gassstea/trackng.us/logs/cron-health.log

# Backup log
tail -f /home/gassstea/trackng.us/logs/cron-backup.log
```

### Check Crontab:
```bash
crontab -l
```

### Check Backups:
```bash
ls -lh /home/gassstea/trackng.us/backups/
```

---

## 🔧 Troubleshooting

### Cron not running?

1. **Check PHP path:**
```bash
which php
# Update crontab dengan path yang benar
```

2. **Check permissions:**
```bash
chmod +x cron/*.php
```

3. **Check logs:**
```bash
tail -f logs/cron-*.log
```

### Permission denied?

```bash
chmod +x cron/cleanup.php
chmod +x cron/health-check.php
chmod +x cron/backup.php
chmod 755 cron/
```

### Backup fails?

```bash
# Check mysqldump
which mysqldump

# Test manually
mysqldump --host=localhost --user=root --password=yourpass database_name > test.sql
```

---

## 📚 Full Documentation

Read the complete guide:
```bash
less /home/gassstea/trackng.us/CRONJOB_GUIDE.md
```

Or view online at your project root.

---

## ⚙️ Customization

### Change Retention Periods:

**Cleanup (3 days instead of 7):**
```cron
0 2 * * * php cleanup.php 3
```

**Backup (90 days instead of 30):**
```cron
0 3 * * * php backup.php 90
```

### Change Schedule:

**Health check every 5 minutes:**
```cron
*/5 * * * *
```

**Cleanup every Sunday:**
```cron
0 2 * * 0
```

**Backup every 6 hours:**
```cron
0 */6 * * *
```

---

## 🎯 Recommended Schedules

### Low Traffic:
```cron
0 2 * * * cleanup.php 7
0 3 * * * backup.php 30
```

### Medium Traffic:
```cron
0 2 * * * cleanup.php 7
*/15 * * * * health-check.php
0 3 * * * backup.php 30
```

### High Traffic:
```cron
0 2 * * * cleanup.php 3
*/5 * * * * health-check.php
0 3 * * * backup.php 14
```

---

## 📧 Email Notifications

Add to crontab for email alerts:
```cron
MAILTO=admin@example.com

0 2 * * * php cleanup.php 7
```

---

## 🔒 Security

Files protected by .htaccess:
- `/cron/*.php` - Deny web access
- `/backups/*` - Deny web access
- `/logs/*` - Deny web access

---

## 💡 Tips

1. ✓ Test manually sebelum schedule
2. ✓ Monitor logs untuk verify execution
3. ✓ Backup sebelum cleanup (3 AM > 2 AM)
4. ✓ Check disk space untuk backups
5. ✓ Adjust retention based on needs

---

## 📞 Quick Commands

```bash
# List all crons
crontab -l

# Edit crons
crontab -e

# Remove all crons (careful!)
crontab -r

# View cleanup log
tail -f logs/cron-cleanup.log

# View health log
tail -f logs/cron-health.log

# View backup log
tail -f logs/cron-backup.log

# Check last backup
ls -lt backups/ | head

# Test all scripts
php cron/cleanup.php 7 && php cron/health-check.php && php cron/backup.php 30
```

---

**Need help?** Read `CRONJOB_GUIDE.md` for detailed documentation.
