# CD SHIPPING HUB - Image Disappearing & Duplicate Content Debug Guide

## Issues Found & Fixed

### **Issue #1: Database Configuration Mismatch** ✅ FIXED
**Problem**: The database configuration was set to PostgreSQL but the SQL schema is MySQL.
```php
// BEFORE (WRONG):
$dsn = "pgsql:host=" . DB_HOST;  // PostgreSQL

// AFTER (CORRECT):
$dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
```
**File**: `config/database.php`
**Impact**: Database connection failures, queries not working, products displaying incorrect data.

---

### **Issue #2: Boolean Handling in Featured Products Query** ✅ FIXED
**Problem**: Using PostgreSQL boolean syntax `WHERE p.featured = true` in MySQL.
```sql
-- BEFORE (WRONG - PostgreSQL syntax):
WHERE p.featured = true

-- AFTER (CORRECT - MySQL uses 0/1):
WHERE p.featured = 1
```
**Files Affected**:
- `index.php` - Featured products query
- `admin/product-form.php` - Boolean conversion (changed from `'t'/'f'` to `1/0`)

**Impact**: Featured products may not display correctly, causing duplicate or missing data rendering.

---

### **Issue #3: Image Caching Problems** ✅ FIXED
**Problem**: Browser and server cache caused stale image URLs to display.
```php
// BEFORE:
$imgSrc = UPLOAD_URL . $product['image'];

// AFTER (Cache Busting):
$imgSrc = UPLOAD_URL . $product['image'] . '?t=' . time();
```
**Files Fixed**:
- `includes/product-card.php` - Added cache busting to product cards
- `product.php` - Added cache busting to product detail images
- `includes/product-card.php` - Added lazy loading for better performance

**Impact**: Prevents stale cached images from displaying after re-uploads.

---

### **Issue #4: Variable Scope in Included Files** ✅ ARCHITECTURE NOTED
**Consideration**: `includes/product-card.php` is included multiple times per page:
- Featured section in `index.php`
- Latest section in `index.php`  
- Products grid in `products.php`

The include mechanism properly maintains variable scope, but the cache busting fixes ensure fresh data is always rendered.

---

### **Issue #5: Duplicate Product Rendering** ✅ EXPECTED BEHAVIOR
**Cause**: Products can appear in both "Featured Products" and "Latest Arrivals" if:
- They are marked as featured (`p.featured = 1`)
- AND they are among the 8 newest products

**Current Behavior** (by design):
- Featured section shows up to 8 featured products
- Latest section shows up to 8 newest products (including featured ones)

**To prevent duplicates**, modify `index.php`:
```php
// Get latest products (excluding featured ones if you prefer)
$latest = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' AND p.featured = 0 ORDER BY p.created_at DESC LIMIT 8")->fetchAll();
```

---

## Step-by-Step Debugging Checklist

### **1. Check Database Connection**
```bash
# Test MySQL connection from terminal:
mysql -h localhost -u root -p cdshipping_hub -e "SELECT COUNT(*) FROM products;"
```
✅ Should show a number (e.g., 30)
❌ If fails: Check MySQL is running, credentials are correct

---

### **2. Verify Product Images**
```bash
# Check if image files exist
ls -la uploads/products/

# Check database image field values
mysql -h localhost -u root -p cdshipping_hub -e "SELECT id, name, image, image2, image3 FROM products LIMIT 3;"
```
✅ Image filenames should match files in `uploads/products/`
❌ If mismatch: Images were deleted or renamed

---

### **3. Test Product Display in Console**
1. Open browser DevTools (F12)
2. Go to Products page
3. Open Network tab and check image URLs:
   ```
   http://localhost/uploads/products/product-name-image-1234567890.webp?t=1712000000
   ```
✅ Images show with cache-busting `?t=timestamp`
❌ If not showing: Clear browser cache (Ctrl+Shift+Del)

---

### **4. Check Featured Products SQL**
Run this directly in your database client:
```sql
SELECT id, name, featured, image FROM products WHERE featured = 1 ORDER BY created_at DESC LIMIT 8;
```
✅ Should show featured products with image filenames
❌ If empty: No featured products, or boolean syntax wrong

---

### **5. Verify Image Upload Process**
1. Go to Admin → Products → Edit/Create
2. Upload an image
3. Check browser console for JavaScript errors
4. Check if file appears in `uploads/products/`
5. Verify database has the filename

---

## Browser Cache Clearing Commands

### **For Users (Browser)**
- **Chrome**: Ctrl + Shift + Delete
- **Firefox**: Ctrl + Shift + Delete  
- **Safari**: Cmd + Option + E
- **Edge**: Ctrl + Shift + Delete

### **For Web Server (PHP)**
Add to your `.htaccess` (if using Apache):
```apache
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
  Header set Cache-Control "public, max-age=3600"
  Header set ETag ""
  Header set Last-Modified ""
  Header unset Pragma
  Header unset Expires
</FilesMatch>
```

---

## Server-Side Image Handling Log

Add this debug code temporarily to `includes/product-card.php`:

```php
<?php
// TEMPORARY DEBUG - Remove after testing
if (defined('DEBUG_IMAGES') && DEBUG_IMAGES) {
    error_log("Product: " . $product['name'] . " | Image: " . $product['image'] . " | Stock: " . $product['stock']);
}

// Product card component...
```

Then in `config/database.php`, add at the top:
```php
define('DEBUG_IMAGES', getenv('DEBUG_IMAGES') ?: false);
```

Check PHP error log:
```bash
tail -f /path/to/php/error_log
```

---

## Common Causes & Solutions

| Issue | Cause | Solution |
|-------|-------|----------|
| Images show then disappear | Browser cache | Clear cache or use `?t=timestamp` (FIXED) |
| Wrong products display | PostgreSQL/MySQL mismatch | Use MySQL protocol (FIXED) |
| Featured products missing | Boolean syntax error | Use `= 1` not `= true` (FIXED) |
| Duplicate products | Same product in 2 sections | Add category filter or exclude featured from latest |
| "No image" placeholder | File deleted from disk | Re-upload or check database filename |
| Images don't save | File permissions | Check `uploads/products/` folder permissions (755) |
| Slow image load | Large file size | Ensure images <5MB, use WebP format |

---

## Performance Recommendations

### **1. Enable WebP Format**
Add to `admin/product-form.php`:
```php
'image/webp' => 'webp',  // Already supported
```
Users should upload `.webp` files (80% smaller than JPG)

### **2. Add Image Lazy Loading**
```html
<img src="..." loading="lazy">  <!-- Already added in product-card.php -->
```

### **3. Optimize Database Queries**
Add indexes (already in schema):
```sql
CREATE INDEX idx_products_featured ON products(featured, status);
CREATE INDEX idx_products_category_status ON products(category_id, status);
```

### **4. Implement Server-Side Caching**
Add to top of `index.php`:
```php
// Cache for 1 hour
$cacheKey = 'homepage_products_' . date('YmdH');
$featured = apcu_fetch($cacheKey . '_featured') ?: $pdo->query("...")->fetchAll();
```

---

## Next Steps

1. ✅ **Clear all browser caches** - Use DevTools or Ctrl+Shift+Delete
2. ✅ **Test homepage** - Refresh multiple times, images should persist
3. ✅ **Edit a product** - Upload new image, verify it displays
4. ✅ **Check admin form** - Verify featured products show correctly
5. ✅ **Monitor logs** - Check for database connection errors
6. ✅ **Test different browsers** - Ensure consistent display

---

## Support Debugging

If issues persist, provide:
1. PHP error log output
2. MySQL version: `SELECT VERSION();`
3. Screenshot of Network tab (DevTools)
4. Product details that are showing incorrectly
5. Browser and OS info

---

**Last Updated**: March 31, 2026
**Database**: MySQL/MariaDB
**PHP Version**: 8.2+
