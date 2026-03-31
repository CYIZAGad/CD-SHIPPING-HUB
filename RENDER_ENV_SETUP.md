# 🚀 Render Deployment: Environment Variables Setup Guide

**Date:** March 31, 2026  
**Repository:** https://github.com/CYIZAGad/CD-SHIPPING-HUB

---

## 📋 Step-by-Step: Complete Render Setup

### STEP 1: Prepare Database (Do This First!)

You need a **MySQL database** for Render. Choose ONE provider:

#### Option A: Planetscale (RECOMMENDED - Free Tier)
1. Go to https://planetscale.com
2. Sign up with GitHub account
3. Create new database: `cdshipping`
4. Go to **Passwords** tab
5. Create a password/user
6. Copy these details:
   ```
   Host: [your-db-host].psdb.cloud
   Username: [your-username]
   Password: [your-password]
   Database: cdshipping
   Port: 3306
   ```

#### Option B: Railway (Alternative)
1. Go to https://railway.app
2. Create new project
3. Add MySQL service
4. Click on MySQL service → Variables tab
5. Copy connection string details

#### Option C: Render MySQL (Limited - Requires Paid Plan)
Not recommended for free tier - upgrade needed

**✅ ACTION:** Choose one and gather your database credentials before continuing.

---

### STEP 2: Create Render Web Service

1. Go to https://render.com and sign up (free account)
2. Click **New +** → **Web Service**
3. Select your GitHub repository: `CD-SHIPPING-HUB`
4. Fill in:
   - **Name:** `cd-shipping` (lowercase, no spaces)
   - **Region:** Choose closest to you
   - **Branch:** `main`
   - **Runtime:** Should auto-detect as `Docker`
   - **Build Command:** Leave blank
   - **Start Command:** Leave blank

**DO NOT CLICK DEPLOY YET** - We need to add environment variables first!

---

### STEP 3: Add Environment Variables in Render

In the Render dashboard **BEFORE clicking Deploy**:

1. Scroll down to **Environment** section
2. Click **Add Environment Variable**
3. Add each variable below (use YOUR actual database credentials)

#### Required Variables (MUST SET):

| Variable | Value | Example |
|----------|-------|---------|
| `DB_HOST` | Your database host | `abc123.psdb.cloud` |
| `DB_USER` | Database username | `your_username` |
| `DB_PASS` | Database password | `pscale_pw_xxxxx` |

#### Important Variables (Recommended):

| Variable | Value | 
|----------|-------|
| `DB_PORT` | `3306` |
| `DB_NAME` | `cdshipping` |
| `APP_ENV` | `production` |
| `SITE_PROTOCOL` | `https` |
| `SITE_DOMAIN` | `cd-shipping.onrender.com` |
| `SITE_PATH` | `/` |
| `INIT_DB` | `1` |
| `SESSION_SECURE_COOKIE` | `true` |
| `SESSION_COOKIE_SAMESITE` | `Strict` |

#### How to Add Each Variable:

**Step 1:** Click "Add Environment Variable"  
**Step 2:** Enter key (e.g., `DB_HOST`)  
**Step 3:** Enter value  
**Step 4:** Click "Save"  
**Step 5:** Repeat for all variables  

---

### STEP 4: Set Instance Type & Deploy

1. **Instance Type:** Select `Free` (or upgrade to paid if needed)
2. **Auto-Deploy:** Leave enabled (auto-redeploy on git push)
3. Click **Create Web Service**

Render will now:
- Build Docker image (2-3 minutes)
- Initialize database
- Start application
- Assign you a domain: `cd-shipping.onrender.com`

---

## 🔑 Complete Environment Variables Reference

Copy all these variables to Render. Replace values with YOUR actual data:

```
DB_HOST=your-db-host.psdb.cloud
DB_PORT=3306
DB_NAME=cdshipping
DB_USER=your_db_username
DB_PASS=your_db_password
APP_ENV=production
SITE_PROTOCOL=https
SITE_DOMAIN=cd-shipping.onrender.com
SITE_PATH=/
INIT_DB=1
SESSION_SECURE_COOKIE=true
SESSION_COOKIE_SAMESITE=Strict
MAX_UPLOAD_SIZE=5242880
```

---

## 📝 Database Credentials Checklist

Before deploying, gather these from your database provider:

### From Planetscale:
- [ ] Host (looks like: `abc123def456.psdb.cloud`)
- [ ] Username (looks like: `g4h8k2m9`)
- [ ] Password (looks like: `pscale_pw_xxxxxxxxxxxxx`)
- [ ] Port: `3306`
- [ ] Database name: `cdshipping`

### From Railway:
- [ ] Host from connection string
- [ ] Username from connection string
- [ ] Password from connection string
- [ ] Port: `3306`
- [ ] Database name: `cdshipping`

---

## 🎯 Render Domain Configuration

Your app will be accessible at:
```
https://cd-shipping.onrender.com
```

Make sure your `SITE_DOMAIN` environment variable matches this!

If you want a custom domain:
1. In Render → Settings → Custom Domain
2. Add your domain
3. Point DNS to Render (Render will show instructions)

---

## ✅ Verification Checklist

After deployment, verify:

- [ ] Application loads: https://cd-shipping.onrender.com
- [ ] Admin login works: admin@cdshipping.com / admin123
- [ ] Can access Products page
- [ ] Database is connected (check logs if unsure)
- [ ] No errors in deployment logs

Check logs in Render: Logs tab (top right)

---

## 🚨 Common Issues & Fixes

### "Database connection failed"
**Fix:** Check `DB_HOST`, `DB_USER`, `DB_PASS` are correct in environment variables

### "Application failed to start"
**Fix:** 
1. Check logs in Render dashboard
2. Verify all required environment variables are set
3. Ensure database host is accessible from Render

### "SITE_DOMAIN shows localhost"
**Fix:** Make sure you set `SITE_DOMAIN=cd-shipping.onrender.com`

### "Can't log in as admin"
**Fix:** 
1. Set `INIT_DB=1` in environment variables
2. Restart deployment
3. Wait for database initialization to complete

---

## 📊 Your Deployment Summary

| Item | Value |
|------|-------|
| **Repository** | https://github.com/CYIZAGad/CD-SHIPPING-HUB |
| **Render App Name** | cd-shipping |
| **Web Service URL** | https://cd-shipping.onrender.com |
| **Database Provider** | Planetscale/Railway/Other |
| **Admin Email** | admin@cdshipping.com |
| **Admin Password** | admin123 (⚠️ CHANGE AFTER FIRST LOGIN) |
| **Deployment Type** | Docker |

---

## 🔒 IMPORTANT SECURITY NOTES

⚠️ **BEFORE PRODUCTION:**

1. **Change Admin Password**
   - Login as admin@cdshipping.com with password admin123
   - Go to Profile
   - Change to strong password immediately

2. **Database Credentials**
   - Use strong database passwords
   - Never share credentials
   - Keep `.env` file local (never commit to git)

3. **HTTPS**
   - Render provides free SSL
   - All traffic is encrypted automatically
   - No action needed from you

---

## 📞 Need Help?

### Render Support
- https://render.com/docs
- Dashboard Chat Support

### Database Provider Support
- Planetscale: https://planetscale.com/docs
- Railway: https://docs.railway.app

### Application Issues
- Check deployment logs in Render
- Review DEPLOYMENT.md in your repo
- Check docker-compose.log for local testing

---

## 🎬 Next: After Deployment

### Immediate (Day 1):
- [ ] Test login
- [ ] Change admin password
- [ ] Test file uploads
- [ ] Verify database

### Week 1:
- [ ] Set up backups
- [ ] Monitor error logs
- [ ] Test all features

### Month 1:
- [ ] Optimize performance
- [ ] Add monitoring
- [ ] Plan scaling

---

**Ready to deploy? Follow Step 1-4 above, then check your app at https://cd-shipping.onrender.com**

*For detailed deployment info, see DEPLOYMENT.md and QUICK_RENDER_DEPLOY.md in your repository.*
