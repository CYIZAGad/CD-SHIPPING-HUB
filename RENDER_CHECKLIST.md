# 📋 Render Deployment Checklist

**Your Repository:** https://github.com/CYIZAGad/CD-SHIPPING-HUB

---

## ✅ PRE-RENDER CHECKLIST

### Database Setup (Choose ONE)

#### Planetscale ✓
- [ ] Sign up at https://planetscale.com
- [ ] Create database: `cdshipping`
- [ ] Create password
- [ ] Copy these values:
  ```
  DB_HOST = _____________________
  DB_USER = _____________________
  DB_PASS = _____________________
  ```

#### Railway ✓
- [ ] Sign up at https://railway.app
- [ ] Create MySQL service
- [ ] Copy connection details:
  ```
  DB_HOST = _____________________
  DB_USER = _____________________
  DB_PASS = _____________________
  ```

---

## ✅ RENDER SETUP CHECKLIST

### 1. Create Web Service
- [ ] Go to https://render.com
- [ ] Sign up if needed
- [ ] Click **New +** → **Web Service**
- [ ] Select repository: **CD-SHIPPING-HUB**
- [ ] Verify settings:
  - [ ] Runtime: Docker
  - [ ] Name: `cd-shipping`
  - [ ] Branch: `main`

### 2. Add Environment Variables (CRITICAL!)

**⚠️ DO THIS BEFORE CLICKING DEPLOY**

#### Required Variables:
- [ ] `DB_HOST` = (your database host)
- [ ] `DB_USER` = (your database user)
- [ ] `DB_PASS` = (your database password)

#### Standard Configuration:
- [ ] `DB_PORT` = `3306`
- [ ] `DB_NAME` = `cdshipping`
- [ ] `APP_ENV` = `production`
- [ ] `SITE_PROTOCOL` = `https`
- [ ] `SITE_DOMAIN` = `cd-shipping.onrender.com`
- [ ] `SITE_PATH` = `/`
- [ ] `INIT_DB` = `1`
- [ ] `SESSION_SECURE_COOKIE` = `true`
- [ ] `SESSION_COOKIE_SAMESITE` = `Strict`

### 3. Deploy
- [ ] Instance Type: **Free** (or paid if preferred)
- [ ] Auto-Deploy: **Enabled**
- [ ] Click **Create Web Service**
- [ ] Wait for build (3-5 minutes)
- [ ] Check status: Should show "Live" when done

---

## ✅ POST-DEPLOYMENT CHECKLIST

### Immediate (5 minutes after deployment):
- [ ] Wait until status shows "Live"
- [ ] Visit: https://cd-shipping.onrender.com
- [ ] Check for any errors
- [ ] Look at deployment logs if issues

### First Login (10 minutes):
- [ ] Access your app URL
- [ ] Login with:
  - Email: `admin@cdshipping.com`
  - Password: `admin123`
- [ ] Go to Profile
- [ ] **CHANGE PASSWORD** to something strong
- [ ] Save changes

### Functionality Testing (20 minutes):
- [ ] View Products page
- [ ] Add product to cart
- [ ] View Orders (should be admin menu)
- [ ] Test file upload (if product edit available)
- [ ] Logout and login again
- [ ] Check Notifications

### Database Verification (5 minutes):
- [ ] Open database client (MySQL Workbench, DBeaver, etc.)
- [ ] Connect with credentials:
  - [ ] Host: (your DB_HOST)
  - [ ] User: (your DB_USER)
  - [ ] Password: (your DB_PASS)
- [ ] Check database `cdshipping` exists
- [ ] Check tables created (users, products, orders, etc.)

---

## 📝 Your Database Credentials (Keep Safe!)

**Provider:** ___________________________

**DB_HOST:** ___________________________

**DB_USER:** ___________________________

**DB_PASS:** ___________________________

**DB_NAME:** cdshipping

**DB_PORT:** 3306

---

## 🔗 Your Application URL

```
https://cd-shipping.onrender.com
```

**Admin Login:**
- Email: `admin@cdshipping.com`
- Password: `admin123` (change immediately!)

---

## 📞 Support Resources

### When You See...

**"Application failed to start"**
- Check Render Logs (top right)
- Verify all environment variables set
- Check DB credentials are correct

**"Cannot connect to database"**
- Verify DB_HOST value is correct
- Ensure database credentials match
- Check database is running

**"Application shows 'localhost' URLs"**
- Update SITE_DOMAIN to correct value
- Restart application
- Clear browser cache

**"Admin login fails"**
- Ensure INIT_DB=1 on first deployment
- Wait 2 minutes for database setup
- Check logs for errors

---

## ✨ Advanced: Custom Domain (Optional)

1. Purchase domain from registrar (GoDaddy, Namecheap, etc.)
2. In Render Dashboard → Settings → Custom Domain
3. Add your domain
4. Follow DNS setup instructions
5. Update `SITE_DOMAIN` environment variable

---

## 📊 Monitoring URLs

**Your App:** https://cd-shipping.onrender.com

**Render Dashboard:** https://render.com/dashboard

**GitHub Repository:** https://github.com/CYIZAGad/CD-SHIPPING-HUB

---

## 🎯 Success Indicators

✅ All checkboxes above completed  
✅ Application loads without errors  
✅ Admin login works  
✅ Can view products and orders  
✅ Database is connected  
✅ Admin password changed  

---

## 📌 If Something Goes Wrong

1. **Check Render Logs**
   - Dashboard → Logs tab (top right)
   - Read error messages carefully

2. **Verify Environment Variables**
   - Dashboard → Environment tab
   - Ensure all values are correct
   - No typos in DB credentials

3. **Restart Application**
   - Dashboard → top right menu
   - Click "Restart Service"

4. **Check Database Connection**
   - Connect to database with your credentials
   - Verify tables exist
   - Ensure database is running

5. **Review Documentation**
   - RENDER_ENV_SETUP.md (this file)
   - DEPLOYMENT.md
   - QUICK_RENDER_DEPLOY.md

---

**Status:** Ready for Render Deployment ✅

**Next Step:** Follow checklist above to deploy

*Save this file for reference during deployment*
