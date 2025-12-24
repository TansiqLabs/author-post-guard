# Quick Start Guide - Author Post Guard

Get your plugin up and running in 5 minutes! ⚡

## 📥 Installation (2 minutes)

### Option 1: WordPress Admin Upload
```bash
# Create ZIP file
cd /home/nazim/Software
zip -r author-post-guard.zip author-post-guard/ -x "*.git*"
```

1. Go to WordPress Admin → **Plugins → Add New → Upload Plugin**
2. Choose `author-post-guard.zip`
3. Click **Install Now** → **Activate**
4. Done! ✅

### Option 2: FTP/SFTP Upload
1. Upload `author-post-guard` folder to `/wp-content/plugins/`
2. Go to WordPress Admin → **Plugins**
3. Find "Author Post Guard" → Click **Activate**
4. Done! ✅

---

## ⚙️ Basic Setup (3 minutes)

### Step 1: White-Label Your Admin (1 minute)

Go to **Author Guard → Branding**

```
Plugin Name: [Your Company Guard]
Login Footer: [Powered by Your Company]
Admin Bar Text: [Your Company]
```

Click **Upload Logo** → Choose your logo → **Save Changes** ✅

---

### Step 2: Hide Sensitive Plugins (1 minute)

Go to **Menu Control** tab

**Recommended for all sites:**
- ✅ UpdraftPlus (backups)
- ✅ BackWPup (backups)
- ✅ Duplicator (backups)
- ✅ Wordfence (security)
- ✅ LiteSpeed Cache (performance)
- ✅ WP File Manager (file access)

Click **Save Changes** ✅

**Result:** Non-admin users won't see or access these plugins!

---

### Step 3: Enable Notifications (Optional - 1 minute)

#### For Discord:
Go to **Notifications** tab
```
Discord Webhook URL: [Your webhook URL]
☑️ Enable Discord Notifications
```
Click **Test Discord** → Check your channel → **Save Changes** ✅

#### For Telegram:
```
Bot Token: [Your bot token from @BotFather]
Chat ID: [Your chat ID]
☑️ Enable Telegram Notifications
```
Click **Test Telegram** → Check your chat → **Save Changes** ✅

---

## 🎯 Common Use Cases

### Use Case 1: Agency Client Site
**Goal:** Clean, branded admin for client
**Time:** 2 minutes

1. **Branding Tab:**
   - Plugin Name: "Client Company Guard"
   - Upload client's logo
   - Admin Bar: "Client Company"
   - Save ✅

2. **Menu Control:**
   - Hide ALL backup plugins
   - Hide ALL security plugins
   - Hide ALL cache plugins
   - Save ✅

3. **Result:** Client sees clean, professional admin with their branding!

---

### Use Case 2: Multi-Author Blog
**Goal:** Restrict author access to critical plugins
**Time:** 2 minutes

1. **Branding Tab:**
   - Enable Media Restrictions ✅
   - Save ✅

2. **Menu Control:**
   - Hide UpdraftPlus
   - Hide Wordfence
   - Hide WooCommerce (if applicable)
   - Save ✅

3. **Notifications Tab:**
   - Set up Discord webhook
   - Test → Save ✅

4. **Result:** Authors can't access backups, see only their media, team gets notified on new posts!

---

### Use Case 3: Add Custom Functionality
**Goal:** Customize without editing theme files
**Time:** 2 minutes

1. **Code Snippets Tab:**

**Custom CSS:**
```css
/* Hide WordPress logo from admin bar */
#wpadminbar #wp-admin-bar-wp-logo {
    display: none !important;
}
```

**Custom JavaScript:**
```javascript
// Example: Add custom behavior
jQuery(document).ready(function($) {
    console.log('Custom admin loaded');
});
```

**Custom PHP:**
```php
// Example: Change excerpt length
add_filter('excerpt_length', function($length) {
    return 30;
});
```

2. Click **Save Changes** ✅

3. **Result:** Custom functionality without theme edits!

---

## 🔍 Quick Testing

### Test 1: Verify Admin-Only Access (30 seconds)
1. Create test user: Editor role
2. Login as Editor
3. Try accessing: `/wp-admin/admin.php?page=author-post-guard`
4. **Expected:** 403 Access Denied ✅

### Test 2: Verify Menu Hiding (30 seconds)
1. Login as Editor
2. Check sidebar - hidden plugins should be gone ✅
3. Try direct URL to hidden plugin
4. **Expected:** 403 Access Denied ✅

### Test 3: Verify Notifications (30 seconds)
1. Create new post
2. Publish post
3. Check Discord/Telegram
4. **Expected:** Notification received ✅

---

## 🚨 Troubleshooting

### Problem: Settings won't save
**Solution:**
- Clear browser cache
- Check browser console for errors
- Verify you're logged in as Administrator

### Problem: Hidden menus still visible
**Solution:**
- Refresh page (hard refresh: Ctrl+F5)
- Clear WordPress cache
- Verify you're logged in as non-admin

### Problem: Webhooks not working
**Solution:**
- Test webhook URL manually (curl command)
- Check webhook URL format
- Verify server can make HTTPS requests

### Problem: Logo not showing
**Solution:**
- Clear browser cache
- Verify image uploaded successfully
- Check console for 404 errors

---

## 📚 Next Steps

After basic setup:

1. **Read Full Documentation**
   - [README.md](README.md) - Overview
   - [FEATURES.md](FEATURES.md) - Complete features
   - [SECURITY.md](SECURITY.md) - Security guide

2. **Test Thoroughly**
   - Follow [TESTING.md](TESTING.md) checklist
   - Create test users for all roles
   - Verify all features work

3. **Deploy to Production**
   - Follow [DEPLOYMENT.md](DEPLOYMENT.md) guide
   - Backup before deployment
   - Test in staging first

4. **Monitor & Maintain**
   - Check for GitHub updates weekly
   - Monitor error logs
   - Review user access quarterly

---

## 🎓 Tips & Best Practices

### Security Tips
- ✅ Only give Administrator role to 1-2 trusted users
- ✅ Hide all backup plugins from non-admins
- ✅ Enable media restrictions for multi-author sites
- ✅ Test custom PHP in staging first
- ✅ Keep backups before adding code snippets

### Performance Tips
- ✅ Use specific code snippets (avoid generic selectors)
- ✅ Limit number of hidden menus (only what's needed)
- ✅ Test custom JavaScript for conflicts
- ✅ Monitor webhook response times

### Workflow Tips
- ✅ Document all custom code snippets
- ✅ Keep changelog of branding changes
- ✅ Test with each user role after changes
- ✅ Use version control for snippets
- ✅ Schedule regular reviews

---

## 🔗 Resources

### Official Documentation
- [README.md](README.md) - Main documentation
- [FEATURES.md](FEATURES.md) - Complete feature list
- [SECURITY.md](SECURITY.md) - Security documentation
- [TESTING.md](TESTING.md) - Testing guide
- [DEPLOYMENT.md](DEPLOYMENT.md) - Deployment guide
- [CHANGELOG.md](CHANGELOG.md) - Version history

### Support
- **Website:** https://tansiqlabs.com
- **Email:** support@tansiqlabs.com
- **GitHub:** Report issues on repository

### External Resources
- [WordPress Codex](https://codex.wordpress.org/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WordPress Security](https://wordpress.org/about/security/)

---

## ✅ Quick Setup Checklist

- [ ] Plugin installed and activated
- [ ] White-labeling configured
- [ ] Custom logo uploaded (optional)
- [ ] Sensitive plugins hidden
- [ ] Media restrictions enabled (if needed)
- [ ] Notifications set up (optional)
- [ ] Code snippets added (if needed)
- [ ] Tested with non-admin user
- [ ] Documentation reviewed
- [ ] Backup created

---

## 🎉 You're All Set!

Your Author Post Guard plugin is now configured and ready to use!

**What happens now?**
- Non-admin users see clean, restricted admin
- Your branding appears throughout admin
- Hidden plugins are inaccessible
- Notifications sent on post publish
- Custom code executes as configured
- Automatic updates from GitHub

**Need help?** Check documentation or contact support!

---

**Version:** 1.1.0  
**Last Updated:** 2024  
**Estimated Setup Time:** 5 minutes  
**Difficulty Level:** Beginner-Friendly ⭐
