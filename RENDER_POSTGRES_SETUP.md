# Render PostgreSQL Deployment Setup

## Your PostgreSQL Credentials

**Provider:** Render Managed PostgreSQL 15  
**Region:** Oregon

```
DB_HOST: dpg-d7635ehr0fns73brim00-a.oregon-postgres.render.com
DB_PORT: 5432
DB_NAME: cd_shipping_hubdb
DB_USER: cd_shipping_hubdb_user
DB_PASS: 4nICAvxlyLWL45phgiumFsW16GYsbcKl
```

---

## Step-by-Step Environment Variable Setup in Render

### 1. Go to Your Render Web Service Dashboard
- Navigate to https://dashboard.render.com
- Select your web service: **cd-shipping-hub**

### 2. Click on "Environment" Tab
- Located in the left sidebar under your service

### 3. Add Each Environment Variable

**Copy and paste the values exactly as shown:**

| Variable | Value |
|----------|-------|
| `DB_HOST` | `dpg-d7635ehr0fns73brim00-a.oregon-postgres.render.com` |
| `DB_PORT` | `5432` |
| `DB_NAME` | `cd_shipping_hubdb` |
| `DB_USER` | `cd_shipping_hubdb_user` |
| `DB_PASS` | `4nICAvxlyLWL45phgiumFsW16GYsbcKl` |
| `APP_ENV` | `production` |
| `SITE_PROTOCOL` | `https` |
| `SITE_DOMAIN` | `cd-shipping-hub.onrender.com` |
| `SITE_PATH` | `/` |
| `INIT_DB` | `1` |

### 4. Add Variables Individually

For each variable:
1. Click **"Add Environment Variable"**
2. Enter the **Key** (e.g., `DB_HOST`)
3. Enter the **Value** (e.g., `dpg-d7635ehr0fns73brim00-a.oregon-postgres.render.com`)
4. Click **Add**

**Screenshot Guide:**
```
┌─────────────────────────────────────────────────────────┐
│ Environment Variables                                   │
├─────────────────────────────────────────────────────────┤
│ Key              │ Value                                 │
├──────────────────┼───────────────────────────────────────┤
│ DB_HOST          │ dpg-d7635ehr0fns73brim00-a.oregon... │ 
│ DB_PORT          │ 5432                                  │
│ DB_NAME          │ cd_shipping_hubdb                     │
│ DB_USER          │ cd_shipping_hubdb_user                │
│ DB_PASS          │ 4nICAvxlyLWL45phgiumFsW16GYsbcKl │
│ APP_ENV          │ production                            │
│ SITE_PROTOCOL    │ https                                 │
│ SITE_DOMAIN      │ cd-shipping-hub.onrender.com          │
│ SITE_PATH        │ /                                     │
│ INIT_DB          │ 1                                     │
└──────────────────┴───────────────────────────────────────┘
```

---

## Critical: INIT_DB=1 (First Deployment Only)

**⚠️ IMPORTANT:** 
- `INIT_DB=1` tells `docker-entrypoint.sh` to run `setup.php` on container startup
- This will **initialize your PostgreSQL database** with all tables, triggers, indexes, and production data
- **Remove this variable after first successful deployment** to prevent re-initialization
- First deployment will take 30-60 seconds longer as it creates the database

---

## Step 5: Save and Redeploy

1. **Scroll to bottom** and click **"Save Configuration"**
2. **Go back to your service Dashboard**
3. **Click "Redeploy"** (or wait for auto-deploy if you set it to trigger on push)
4. **Monitor the deploy logs** - you should see:
   ```
   Connected to PostgreSQL database successfully
   Created tables...
   Inserting production data...
   ✓ Inserted 19 products
   ✓ Inserted 2 users
   ✓ Inserted 2 orders
   ✓ Inserted 3 order items
   ✓ Inserted 4 notifications
   ✓ Inserted 1 newsletter subscriber
   ✅ Database setup completed successfully with all production data!
   ```

---

## Step 6: Verify Deployment

Once deployment shows **"Live"**:

1. **Test your website:**
   - Visit: https://cd-shipping-hub.onrender.com
   - Should display homepage with products

2. **Test admin login:**
   - Go to: https://cd-shipping-hub.onrender.com/login.php
   - Email: `admin@cdshipping.com`
   - Password: `admin123`
   - Should log in successfully to admin dashboard

3. **Check database:**
   - Admin Dashboard → Products should show 19 products
   - Admin Dashboard → Orders should show 2 existing orders
   - Admin Dashboard → Customers should show 2 users

---

## Step 7: Post-Deployment Actions

### ⚠️ Remove INIT_DB from Environment Variables

After first successful deployment:
1. Go to Environment tab
2. Delete the `INIT_DB` variable
3. Click "Save Configuration"
4. This prevents accidental database re-initialization

### 🔐 Change Admin Password

**URGENT - Change the default admin password:**

1. Log in as admin (admin@cdshipping.com / admin123)
2. Go to: **Profile Settings**
3. Change password to something secure
4. Click Save

### 📧 Update Newsletter Email (Optional)

If you want to test order notifications:
1. Go to Settings/Configuration
2. Update notification email from current test address

---

## Troubleshooting Deployment

### Service shows "Live" but 502/503 errors

**Check logs:**
1. Click "Logs" tab in Render dashboard
2. Look for database connection errors

**Common issues:**
- Database credentials wrong - verify copy/paste carefully
- PostgreSQL not responding - wait 1-2 minutes for Render PostgreSQL to boot
- Firewall issue - Render PostgreSQL allows internal connections automatically

### Database init fails

**Check:**
1. Verify `INIT_DB=1` is set
2. Check PostgreSQL credentials one more time
3. Look for SQL syntax errors in logs

### Products page shows empty

**If database isn't initializing:**
1. SSH into container: `docker exec -it <container-id> /bin/bash`
2. Run manually: `php /var/www/html/setup.php`
3. Check output for errors

---

## Connection Test Command

**To verify PostgreSQL connection works from Render container:**

```bash
# This runs when container starts:
nc -z dpg-d7635ehr0fns73brim00-a.oregon-postgres.render.com 5432
echo "PostgreSQL port 5432 is reachable"
```

The `docker-entrypoint.sh` script automatically does this check before starting Apache.

---

## What Gets Initialized

When `INIT_DB=1` runs, `setup.php` creates:

**Tables:**
- `users` (2 rows - admin + test user)
- `categories` (6 rows - Cars, Laptops, Desktops, Phones, Stoves, Other)
- `products` (19 rows - from your exported database)
- `orders` (2 rows - with real transaction data)
- `order_items` (3 rows - line items from orders)
- `notifications` (4 rows - order updates)
- `newsletter_subscribers` (1 row)

**Indexes:** For fast lookups on category slugs, product searches, order status filtering

**Triggers:** For automatic timestamp updates on `updated_at` columns

**Data Integrity:**
- Foreign key constraints for referential integrity
- Unique checks on emails, order numbers, product slugs
- Default values for timestamps

---

## Timeline

- **Before redeploy:** ~5 minutes (set variables)
- **First deployment:** ~2-3 minutes (builds image + init DB)
- **Subsequent deploys:** ~30 seconds (uses cached image)

---

## Next: Auto-Healing

Your service is configured to:
- Auto-restart if it crashes
- Stay live during deployments (rolling updates)
- Automatically scale if traffic exceeds limits (Render Pro plan)

**You're ready to deploy!** 🚀
