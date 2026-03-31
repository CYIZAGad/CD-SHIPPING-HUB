# Quick Start: Deploy to Render in 15 Minutes

## Prerequisites
- GitHub account with your code pushed
- Render account (free tier available)
- MySQL database (Planetscale, Railway, or Render MySQL)

---

## Step 1: Push Code to GitHub (5 minutes)

```bash
cd c:\xampp\htdocs\cdshipping

# Initialize git if not already done
git init

# Add all files
git add .

# Commit
git commit -m "Add Docker support - ready for Render deployment"

# Add remote and push
git remote add origin https://github.com/YOUR_USERNAME/cdshipping.git
git branch -M main
git push -u origin main
```

---

## Step 2: Create MySQL Database (3 minutes)

Choose one (we recommend **Planetscale** for free tier):

### Option A: Planetscale (Recommended)
1. Go to https://planetscale.com
2. Sign up with GitHub
3. Create database `cdshipping`
4. Create user: `cdshipping_user` with password
5. Copy connection string
6. Note the host, username, password

### Option B: Railway
1. Go to https://railway.app
2. New Project → Add MySQL
3. Get connection details from Variables tab
4. Copy host, username, password

### Option C: Render (Limited)
1. Dashboard → Databases → Create MySQL
2. Copy connection string
3. **Note:** Render MySQL requires upgrade from free tier

---

## Step 3: Deploy on Render (5 minutes)

### 3.1 Create New Web Service
1. Go to https://render.com
2. Click **New +** → **Web Service**
3. Select your GitHub repository
4. Fill in:
   - **Name:** `cd-shipping` (lowercase, no spaces)
   - **Instance Type:** Free (or paid for production)
   - Click **Create Web Service**

### 3.2 Set Environment Variables
While deployment builds, go to **Environment** tab and add:

```
DB_HOST=your-db-host.psdb.cloud
DB_PORT=3306
DB_NAME=cdshipping
DB_USER=cdshipping_user
DB_PASS=your_secure_password
APP_ENV=production
SITE_PROTOCOL=https
SITE_DOMAIN=cd-shipping.onrender.com
SITE_PATH=/
INIT_DB=1
SESSION_SECURE_COOKIE=true
SESSION_COOKIE_SAMESITE=Strict
```

⚠️ **Replace** `your-db-host.psdb.cloud` and credentials with your actual values!

### 3.3 Wait for Deployment
- Render builds Docker image (2-3 min)
- Deployment status shows at top
- Check **Logs** tab for any errors

---

## Step 4: Access Your Application (1 minute)

Once deployment shows "Live":

1. Visit: `https://cd-shipping.onrender.com`
2. Login as admin:
   - Email: `admin@cdshipping.com`
   - Password: `admin123` (CHANGE THIS!)

3. Change admin password immediately:
   - Profile menu → Update password

---

## Verification Checklist

After deployment, verify:

- [ ] Application loads at https://your-domain.onrender.com
- [ ] Admin login works with admin@cdshipping.com
- [ ] Can view products
- [ ] Can add product to cart
- [ ] Database tables created (check via MySQL tool)
- [ ] File uploads work (upload product image)

---

## Troubleshooting

### "Application failed to start"
Check **Logs** tab:
- See database connection error? → Check DB_HOST, credentials
- See "Port already in use"? → Render issue, contact support
- See PHP error? → Check error logs for details

### "Failed to connect to database"
1. Verify environment variables are set correctly
2. Test connection to database locally
3. Ensure database host is accessible from Render IP
4. Check username/password are correct

### "Can't upload files"
1. Check filesystem is writable
2. Verify uploads directory exists
3. Check file size limit (default 50MB)

### "Site URL is wrong"
1. Check `SITE_DOMAIN` environment variable
2. Verify `SITE_PROTOCOL=https` for production
3. Clear browser cache
4. Restart application (in Render dashboard)

---

## Next Steps After Deployment

### Day 1: Immediate
- [ ] Change admin password
- [ ] Test all main features
- [ ] Check error logs for issues
- [ ] Verify database backups working

### Week 1: Optimization
- [ ] Monitor application performance
- [ ] Set up error alerting
- [ ] Create backup strategy
- [ ] Document admin procedures

### Month 1: Production Hardening
- [ ] Set up monitoring
- [ ] Enable rate limiting on login
- [ ] Consider database optimization
- [ ] Plan scaling strategy

---

## Cost Estimates (March 2026)

| Component | Free | Paid |
|-----------|------|------|
| Render App | Free (0.5 CPU) | $7+/month |
| Planetscale DB | Free (5GB) | $29+/month |
| Traffic | Unlimited | Included |
| **Total** | **$0** | **$36+** |

---

## Quick Reference: Your Values to Collect

```
GitHub Username:           _____________________
GitHub Repo URL:           _____________________
MySQL Host:                _____________________
MySQL Username:            _____________________
MySQL Password:            _____________________
Render App Name:           cd-shipping
Render Domain:             cd-shipping.onrender.com
Admin Email:               admin@cdshipping.com
Admin Password:            admin123 → CHANGE ME!
```

---

## Support

- **Render Help:** https://render.com/docs
- **GitHub:** https://github.com
- **Planetscale:** https://planetscale.com/docs
- **Docker Issues:** https://docker.com/support

---

**You're ready to deploy! Start with Step 1 above.**
