# Author Post Guard - Testing Guide

## Version 1.1.0 Testing Checklist

### Security Tests ✅

#### Admin-Only Access
- [x] Non-admin users cannot see "Author Guard" menu in sidebar
- [x] Direct URL access blocked for non-admins (`/wp-admin/admin.php?page=author-post-guard`)
- [x] Returns 403 error with proper message
- [x] AJAX endpoints protected with `manage_options` capability

#### Hidden Menu Protection
- [x] Menus hidden from sidebar for selected plugins
- [x] Direct URL access blocked even when menu is hidden
- [x] Admin users can still access hidden pages
- [x] Non-admins get 403 error on direct URL attempt

#### Protected Plugin Pages (Admin-Only)
The following plugins are ALWAYS admin-only, regardless of menu settings:
- UpdraftPlus (`admin.php?page=updraftplus`)
- BackWPup (`admin.php?page=backwpup`)
- Duplicator (`admin.php?page=duplicator`)
- LiteSpeed Cache (`admin.php?page=litespeed`)
- WP Rocket (`admin.php?page=wprocket`)
- W3 Total Cache (`admin.php?page=w3tc_dashboard`)
- Wordfence (`admin.php?page=Wordfence`)
- Sucuri Security (`admin.php?page=sucuriscan`)
- WP File Manager (`admin.php?page=wp-file-manager-settings`)
- phpMyAdmin (if installed)
- Adminer (if installed)

### Feature Tests

#### 1. White-Labeling
- [ ] Custom footer text appears on login page
- [ ] Custom admin bar branding shows
- [ ] Custom logo appears in admin bar (when uploaded)
- [ ] Dashboard welcome panel customized

#### 2. Menu Control
Test with these popular plugins (40+ supported):
- Contact Form 7, Ninja Forms, WPForms, Gravity Forms, Formidable Forms
- Elementor, Divi, Beaver Builder, WPBakery, Oxygen
- Yoast SEO, Rank Math, All in One SEO
- WooCommerce, Easy Digital Downloads
- LiteSpeed Cache, WP Rocket, W3 Total Cache
- UpdraftPlus, BackWPup, Duplicator
- Wordfence, Sucuri, iThemes Security
- MonsterInsights, Google Analytics
- WP File Manager, File Manager
- Advanced Custom Fields, Pods, Toolset
- And more...

Test Steps:
1. Go to Menu Control tab
2. Select plugins to hide
3. Save settings
4. Verify menus hidden from sidebar
5. Try accessing via direct URL (should be blocked)
6. Login as admin (should still work)

#### 3. Code Snippets
- [ ] CSS snippets appear on frontend and admin
- [ ] JS snippets execute properly
- [ ] PHP snippets run without errors
- [ ] Syntax highlighting visible in editors
- [ ] Changes save correctly

#### 4. Media Library Restrictions
- [ ] Authors see only their own uploads
- [ ] Administrators see all media files
- [ ] Restriction works in both grid and list views

#### 5. Notifications
**Discord:**
- [ ] Test webhook sends successfully
- [ ] Post published notification received
- [ ] Embed formatting correct

**Telegram:**
- [ ] Test message sends
- [ ] Post notification received
- [ ] Bot token valid

**Generic Webhook:**
- [ ] Test payload sends
- [ ] Custom webhook receives data
- [ ] JSON format correct

#### 6. GitHub Auto-Updates
- [ ] Plugin checks for updates
- [ ] Update notification appears in WordPress
- [ ] Update installs successfully
- [ ] Version number updates correctly

#### 7. Custom Logo Upload
- [ ] Media library opens when clicking button
- [ ] Selected image URL saves
- [ ] Preview shows correctly
- [ ] Logo appears in admin bar
- [ ] Removal works properly

### UI/UX Tests

- [ ] All tabs load without errors
- [ ] Form submissions show success toast
- [ ] Error messages display properly
- [ ] Buttons are visible (white text on dark background)
- [ ] Responsive design works on smaller screens
- [ ] Icons display correctly (dashicons-shield-alt)

### Performance Tests

- [ ] Plugin loads without slowing admin
- [ ] No JavaScript errors in console
- [ ] No PHP warnings or notices
- [ ] Database queries optimized
- [ ] AJAX responses fast

### Compatibility Tests

**WordPress Versions:**
- [ ] 5.8+
- [ ] 6.0+
- [ ] 6.4+ (latest)

**PHP Versions:**
- [ ] 7.4
- [ ] 8.0
- [ ] 8.1
- [ ] 8.2

**Common Plugins:**
- [ ] Works with WooCommerce
- [ ] Works with Elementor
- [ ] Works with Yoast SEO
- [ ] Works with security plugins

### Edge Cases

- [ ] What happens if GitHub API is down?
- [ ] How does it handle invalid webhook URLs?
- [ ] Can admins still access hidden pages?
- [ ] Does it conflict with other white-label plugins?
- [ ] What if custom PHP code has errors?

## Critical Security Scenarios

### Scenario 1: Author Trying to Access Settings
**Steps:**
1. Login as Author role
2. Try to access: `/wp-admin/admin.php?page=author-post-guard`

**Expected Result:**
- Access denied with 403 error
- Message: "You don't have permission to access this page."

### Scenario 2: Editor Trying to Access Hidden Plugin
**Steps:**
1. Login as Editor role
2. Admin hides "Contact Form 7" from menu
3. Try to access: `/wp-admin/admin.php?page=wpcf7`

**Expected Result:**
- Access denied with 403 error
- Plugin page not accessible

### Scenario 3: Author Trying to Access UpdraftPlus
**Steps:**
1. Login as Author role
2. Try to access: `/wp-admin/admin.php?page=updraftplus`

**Expected Result:**
- Access denied with 403 error
- Even if menu is visible, page is blocked

### Scenario 4: Admin Accessing Everything
**Steps:**
1. Login as Administrator
2. Access hidden pages
3. Access protected backup plugins
4. Access settings page

**Expected Result:**
- All pages accessible
- No restrictions for admins

## Troubleshooting

### Issue: Settings Won't Save
**Check:**
- Browser console for JavaScript errors
- PHP error logs
- Nonce verification

### Issue: Direct URL Still Accessible
**Check:**
- `block_direct_access()` function hooked properly
- User role is non-admin
- `$hidden` and `$admin_only_pages` arrays populated

### Issue: Custom Logo Not Showing
**Check:**
- Image URL saved in database
- CSS variable injected in `<head>`
- Admin bar CSS not overridden by theme

### Issue: Webhooks Not Sending
**Check:**
- Valid URL format
- Network connectivity
- Webhook service accepting requests
- Test button functionality

## Recommended Test Users

Create these test accounts:

1. **Administrator** (admin)
   - Can access everything
   - Test all features

2. **Editor** (editor)
   - Cannot access settings
   - Cannot access protected plugins
   - Can publish posts (triggers notifications)

3. **Author** (author)
   - Cannot access settings
   - Cannot access any hidden plugins
   - Can see only own media
   - Can publish posts

4. **Contributor** (contributor)
   - Most restricted
   - Test media restrictions
   - Test menu hiding

## Final Verification

Before deploying to production:

- ✅ All security checks pass
- ✅ No PHP errors or warnings
- ✅ No JavaScript console errors
- ✅ All features working as expected
- ✅ GitHub repository field is readonly
- ✅ Button text is visible on dark backgrounds
- ✅ Admin bar shows custom logo
- ✅ Protected plugins blocked for non-admins
- ✅ Direct URL access properly blocked
- ✅ CHANGELOG.md updated
- ✅ Version number is 1.1.0

## Testing Commands

```bash
# Check for PHP syntax errors
php -l author-post-guard.php
php -l inc/class-settings.php
php -l inc/class-notifications.php
php -l inc/class-updater.php

# Check WordPress coding standards (if PHP_CodeSniffer installed)
phpcs --standard=WordPress author-post-guard.php

# Check for security issues (if installed)
psalm --init
psalm
```

## Browser Testing

Test in these browsers:
- Chrome/Chromium
- Firefox
- Safari
- Edge

## Mobile Testing

- Test admin interface on tablet
- Test settings page responsiveness
- Verify touch interactions work

---

**Testing Date:** ___________  
**Tester Name:** ___________  
**WordPress Version:** ___________  
**PHP Version:** ___________  
**Result:** PASS / FAIL

**Notes:**
_________________________________
_________________________________
_________________________________
