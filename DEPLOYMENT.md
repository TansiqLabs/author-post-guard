# Author Post Guard - Deployment Guide

## 🚀 Production Deployment

### Prerequisites

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Administrator access to WordPress
- FTP/SFTP access or File Manager

### Installation Methods

#### Method 1: Upload via WordPress Admin (Recommended)

1. Create a ZIP file of the plugin:
   ```bash
   cd /home/nazim/Software
   zip -r author-post-guard.zip author-post-guard/ -x "*.git*" "*.DS_Store"
   ```

2. Upload in WordPress:
   - Go to **Plugins → Add New → Upload Plugin**
   - Choose `author-post-guard.zip`
   - Click **Install Now**
   - Click **Activate**

#### Method 2: FTP/SFTP Upload

1. Connect to your server via FTP/SFTP
2. Navigate to: `/wp-content/plugins/`
3. Upload the entire `author-post-guard` folder
4. Go to WordPress Admin → Plugins
5. Find "Author Post Guard" and click **Activate**

#### Method 3: WP-CLI (For Advanced Users)

```bash
cd /path/to/wordpress/wp-content/plugins
git clone <your-github-repo-url> author-post-guard
wp plugin activate author-post-guard
```

### Post-Installation Setup

#### Step 1: Configure White-Labeling

1. Go to **Author Guard → Branding**
2. Set your custom branding:
   - **Plugin Name:** "Your Company Guard"
   - **Login Footer Text:** "Powered by Your Company"
   - **Admin Bar Branding:** "Your Company"
   - **Upload Custom Logo** (optional)
3. Click **Save Changes**

#### Step 2: Configure Menu Control

1. Go to **Menu Control** tab
2. Select plugins you want to hide from non-admins
3. Common selections:
   - ✅ All backup plugins (UpdraftPlus, BackWPup, etc.)
   - ✅ Security plugins (Wordfence, Sucuri, etc.)
   - ✅ Cache plugins (LiteSpeed, WP Rocket, W3TC)
   - ✅ File managers
   - ✅ Database tools
4. Click **Save Changes**

> **Note:** These plugins are ALWAYS admin-only regardless of menu settings:
> - UpdraftPlus
> - BackWPup
> - Duplicator
> - LiteSpeed Cache
> - WP Rocket
> - W3 Total Cache
> - Wordfence
> - Sucuri Security
> - WP File Manager

#### Step 3: Add Custom Code (Optional)

Go to **Code Snippets** tab:

**Custom CSS:**
```css
/* Hide WordPress logo from admin bar */
#wpadminbar #wp-admin-bar-wp-logo {
    display: none !important;
}
```

**Custom JavaScript:**
```javascript
// Add custom analytics or behavior
console.log('Author Post Guard Active');
```

**Custom PHP:**
```php
// Add custom functionality
// Example: Change excerpt length
add_filter('excerpt_length', function($length) {
    return 30;
});
```

#### Step 4: Configure Notifications (Optional)

**For Discord:**
1. Create a webhook in your Discord server
2. Go to **Notifications** tab
3. Paste webhook URL
4. Click **Test Discord** to verify
5. Enable notifications
6. Save Changes

**For Telegram:**
1. Create a bot via @BotFather
2. Get your bot token and chat ID
3. Go to **Notifications** tab
4. Enter credentials
5. Click **Test Telegram** to verify
6. Enable notifications
7. Save Changes

#### Step 5: Enable GitHub Auto-Updates

1. Go to **Updates** tab
2. GitHub repository field shows: `tansiq-labs/author-post-guard` (readonly)
3. Updates will check automatically
4. When new version available, update via WordPress admin

### Security Checklist

After deployment, verify these security features:

- [ ] Non-admin users cannot access plugin settings
- [ ] Hidden menus are not accessible via direct URL
- [ ] Backup plugin pages are admin-only
- [ ] Media library restrictions work for Authors
- [ ] All AJAX requests validate nonces
- [ ] Custom PHP code executes safely

### Testing in Production

Create test users:

```bash
# Via WP-CLI
wp user create editor editor@example.com --role=editor
wp user create author author@example.com --role=author
```

Test scenarios:
1. Login as Editor → Try accessing `/wp-admin/admin.php?page=author-post-guard` → Should fail
2. Login as Author → Try accessing hidden plugin pages → Should fail
3. Login as Author → Upload media → Should see only own files
4. Publish a post → Check webhook notifications arrive

### Performance Optimization

#### Enable Object Caching (Recommended)

```php
// In wp-config.php (if using Redis/Memcached)
define('WP_CACHE', true);
```

#### Database Optimization

The plugin stores all settings in a single option:
- `apg_settings` - Main settings array

No additional database tables are created, minimizing overhead.

#### Disable Features You Don't Use

If you're not using certain features:
- Don't enable webhooks if not needed
- Remove code snippets to avoid execution overhead
- Disable media restrictions if not needed

### Backup Recommendations

Before deployment:

1. **Full Site Backup:**
   ```bash
   # If using UpdraftPlus
   wp updraft backup --all
   
   # Or manual backup
   tar -czf backup-$(date +%Y%m%d).tar.gz /path/to/wordpress
   ```

2. **Database Backup:**
   ```bash
   wp db export backup-$(date +%Y%m%d).sql
   ```

3. **Test in Staging First:**
   - Deploy to staging environment
   - Run all tests from TESTING.md
   - Verify no conflicts
   - Then deploy to production

### Common Deployment Scenarios

#### Scenario 1: New Client Site

1. Install WordPress
2. Install required plugins (forms, page builders, etc.)
3. Install Author Post Guard
4. Configure white-labeling with client branding
5. Hide all admin-only plugins from menu
6. Set up Discord notifications to your channel
7. Create Author/Editor accounts for client team

#### Scenario 2: Existing Site with Multiple Users

1. Backup database
2. Install plugin
3. Test with existing user roles
4. Gradually enable menu hiding
5. Monitor for any access issues
6. Adjust settings as needed

#### Scenario 3: Multisite Network

```php
// Enable network-wide (in wp-config.php)
define('WP_ALLOW_MULTISITE', true);

// Network activate
wp plugin activate author-post-guard --network
```

> **Note:** Settings are per-site, not network-wide

### Environment-Specific Configuration

#### Development

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

#### Staging

- Use test webhook URLs
- Enable all logging
- Test with production-like data

#### Production

```php
// In wp-config.php
define('WP_DEBUG', false);
define('DISALLOW_FILE_EDIT', true); // Disable theme/plugin editor
```

### Monitoring & Maintenance

#### Check Plugin Functionality

```bash
# Check if plugin is active
wp plugin list --status=active | grep author-post-guard

# Check for errors in logs
tail -f /path/to/wordpress/wp-content/debug.log | grep "Author Post Guard"
```

#### Update Process

1. Plugin checks GitHub for updates daily
2. When update available, notification appears in WordPress
3. Click "Update Now" in Plugins page
4. Plugin updates automatically
5. Verify functionality after update

#### Rollback Plan

If something goes wrong:

```bash
# Via WP-CLI
wp plugin deactivate author-post-guard
wp plugin delete author-post-guard

# Restore from backup
cd /path/to/wordpress/wp-content/plugins
tar -xzf author-post-guard-backup.tar.gz
wp plugin activate author-post-guard
```

### Troubleshooting

#### White Screen After Activation

```bash
# Disable via WP-CLI
wp plugin deactivate author-post-guard

# Or rename folder via FTP
mv author-post-guard author-post-guard-disabled
```

#### Settings Not Saving

1. Check file permissions: `chmod 755 /path/to/wordpress/wp-content/plugins/author-post-guard`
2. Check PHP error logs
3. Verify AJAX endpoint is accessible
4. Clear browser cache

#### Webhooks Not Working

1. Test webhook URL manually:
   ```bash
   curl -X POST "YOUR_DISCORD_WEBHOOK_URL" \
     -H "Content-Type: application/json" \
     -d '{"content":"Test message"}'
   ```
2. Check server can make outbound HTTPS requests
3. Verify no firewall blocking

#### Updates Not Showing

1. Verify GitHub repository is public
2. Check `releases` exist in GitHub
3. Force update check:
   ```bash
   wp transient delete update_plugins
   wp plugin update --all --dry-run
   ```

### Support & Resources

- **Documentation:** [README.md](README.md)
- **Testing Guide:** [TESTING.md](TESTING.md)
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)
- **License:** [LICENSE](LICENSE)
- **GitHub Issues:** Report bugs on GitHub repository

### Migration from Other White-Label Plugins

If you're migrating from another plugin:

1. **Document current settings** from old plugin
2. **Don't deactivate old plugin yet**
3. Install Author Post Guard
4. Configure settings to match old plugin
5. Test with non-admin user accounts
6. When satisfied, deactivate old plugin
7. Keep old plugin for 1 week as backup
8. Delete old plugin

### Compliance & Legal

#### GDPR Compliance

- Plugin doesn't collect user data
- Webhooks send post metadata only (configurable)
- No external tracking or analytics
- Admin logs not stored persistently

#### Licensing

- MIT License (see LICENSE file)
- Free to use commercially
- Attribution appreciated but not required

### Production Checklist

Before going live:

- [ ] Backup completed
- [ ] Staging tested successfully
- [ ] Security verified with test users
- [ ] White-labeling configured
- [ ] Menu control set up
- [ ] Notifications tested (if enabled)
- [ ] Code snippets reviewed
- [ ] Media restrictions working
- [ ] GitHub updates configured
- [ ] Performance tested
- [ ] Mobile responsive verified
- [ ] Browser compatibility checked
- [ ] Documentation reviewed
- [ ] Support plan in place

### Post-Deployment

- [ ] Monitor error logs for 24 hours
- [ ] Check webhook notifications arrive
- [ ] Verify non-admin users can work normally
- [ ] Test plugin updates within first week
- [ ] Gather feedback from team
- [ ] Document any custom configurations
- [ ] Schedule regular plugin updates

---

**Deployed By:** ___________  
**Deployment Date:** ___________  
**WordPress Version:** ___________  
**PHP Version:** ___________  
**Site URL:** ___________

**Notes:**
_________________________________
_________________________________
