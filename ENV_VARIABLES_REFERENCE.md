# 🔐 Copy-Paste: Environment Variables for Render

**Use this to quickly add all variables to Render dashboard**

---

## STEP 1: Gather Your Database Info

Before copying variables below, get these from your database provider:

```
Your Database Host:     ___________________________
Your Database User:     ___________________________
Your Database Password: ___________________________
```

---

## STEP 2: Copy Each Variable to Render

Go to: **Render Dashboard → Environment** → Add each below

### Variable 1
**Key:** `DB_HOST`  
**Value:** _(Your database host from above)_

### Variable 2
**Key:** `DB_PORT`  
**Value:** `3306`

### Variable 3
**Key:** `DB_NAME`  
**Value:** `cdshipping`

### Variable 4
**Key:** `DB_USER`  
**Value:** _(Your database user from above)_

### Variable 5
**Key:** `DB_PASS`  
**Value:** _(Your database password from above)_

### Variable 6
**Key:** `APP_ENV`  
**Value:** `production`

### Variable 7
**Key:** `SITE_PROTOCOL`  
**Value:** `https`

### Variable 8
**Key:** `SITE_DOMAIN`  
**Value:** `cd-shipping.onrender.com`

### Variable 9
**Key:** `SITE_PATH`  
**Value:** `/`

### Variable 10
**Key:** `INIT_DB`  
**Value:** `1`

### Variable 11
**Key:** `SESSION_SECURE_COOKIE`  
**Value:** `true`

### Variable 12
**Key:** `SESSION_COOKIE_SAMESITE`  
**Value:** `Strict`

---

## QUICK REFERENCE TABLE

Copy-paste this table to remember all variables:

| # | Key | Value |
|---|-----|-------|
| 1 | DB_HOST | (your db host) |
| 2 | DB_PORT | 3306 |
| 3 | DB_NAME | cdshipping |
| 4 | DB_USER | (your db user) |
| 5 | DB_PASS | (your db password) |
| 6 | APP_ENV | production |
| 7 | SITE_PROTOCOL | https |
| 8 | SITE_DOMAIN | cd-shipping.onrender.com |
| 9 | SITE_PATH | / |
| 10 | INIT_DB | 1 |
| 11 | SESSION_SECURE_COOKIE | true |
| 12 | SESSION_COOKIE_SAMESITE | Strict |

---

## 🔑 How to Get Database Credentials

### From Planetscale:
1. Go to https://planetscale.com
2. Click your database
3. Click **Passwords** tab
4. Create password or use existing
5. Connection string shows: `mysql://user:password@host/database`
   - `DB_HOST` = the host part
   - `DB_USER` = the user part
   - `DB_PASS` = the password part

### From Railway:
1. Go to https://railway.app
2. Click MySQL service
3. Click **Variables** tab
4. You'll see:
   - `MYSQL_HOST` → use as `DB_HOST`
   - `MYSQL_USER` → use as `DB_USER`
   - `MYSQL_PASSWORD` → use as `DB_PASS`

---

## ✅ Validation Checklist

Before clicking **Deploy** in Render:

- [ ] All 12 environment variables added
- [ ] No typos in variable names
- [ ] Database credentials are correct
- [ ] `SITE_DOMAIN` is `cd-shipping.onrender.com`
- [ ] Ready to click Deploy

---

## 🚀 After Adding Variables

1. Click **Create Web Service** button
2. Render will build and deploy (3-5 minutes)
3. Wait for status to show **Live**
4. Visit: https://cd-shipping.onrender.com
5. Login with:
   - Email: `admin@cdshipping.com`
   - Password: `admin123`

---

## 🆘 If Database Credentials Wrong

If you get "Database connection failed" after deployment:

1. Go to Render Dashboard
2. Click **Environment** tab
3. Fix the values:
   - DB_HOST (check for typos)
   - DB_USER (check for typos)
   - DB_PASS (check for typos)
4. Click top menu → "Restart Service"
5. Wait 1 minute
6. Refresh your browser

---

## 📌 Remember

✅ All variables must be added BEFORE clicking Deploy  
✅ Typos in variables = deployment failure  
✅ Your database credentials are sensitive - keep safe  
✅ Change admin password after first login  

---

**Use this guide to add all environment variables correctly to Render**
