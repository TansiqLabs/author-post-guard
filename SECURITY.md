# Security Documentation - Author Post Guard

## 🔒 Security Features Overview

Author Post Guard implements multiple layers of security to protect your WordPress installation and ensure that only authorized users can access sensitive areas.

## Security Architecture

### 1. Admin-Only Plugin Access

**Implementation:**
```php
// In class-settings.php - render_settings_page()
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 
        esc_html__( 'You don\'t have permission to access this page.', 'author-post-guard' ),
        esc_html__( 'Access Denied', 'author-post-guard' ),
        array( 'response' => 403 )
    );
}
```

**Protection Level:** HIGH

**What it does:**
- Only users with `manage_options` capability (Administrators) can access plugin settings
- Attempting to access `/wp-admin/admin.php?page=author-post-guard` as non-admin results in 403 error
- All AJAX endpoints validate capability before processing

**Bypasses:** None - capability check is at function entry point

---

### 2. Direct URL Blocking

**Implementation:**
```php
// In author-post-guard.php - block_direct_access()
public function block_direct_access() {
    // Allow administrators
    if ( current_user_can( 'manage_options' ) ) {
        return;
    }
    
    $options = get_option( 'apg_settings', array() );
    $hidden  = isset( $options['hidden_menus'] ) ? $options['hidden_menus'] : array();
    
    // Build list of admin-only pages
    $admin_only_pages = array(
        'admin.php?page=updraftplus',
        'admin.php?page=backwpup',
        'admin.php?page=duplicator',
        // ... more protected pages
    );
    
    // Check current page
    global $pagenow;
    $current_page = $pagenow;
    if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) ) {
        $current_page = 'admin.php?page=' . sanitize_text_field( $_GET['page'] );
    }
    
    // Block if page is hidden or admin-only
    if ( in_array( $current_page, $hidden, true ) || in_array( $current_page, $admin_only_pages, true ) ) {
        wp_die( /* ... */ );
    }
}
```

**Hook Priority:** 1 (earliest possible in admin_init)

**Protection Level:** CRITICAL

**What it does:**
- Intercepts all admin page requests before they load
- Checks if page is in hidden menus list
- Checks if page is in admin-only pages list
- Blocks non-administrators with 403 error
- Prevents direct URL access to: `/wp-admin/admin.php?page=hidden-plugin`

**Protected Page Examples:**
- `admin.php?page=updraftplus` - UpdraftPlus backup settings
- `admin.php?page=litespeed` - LiteSpeed Cache settings
- `admin.php?page=Wordfence` - Wordfence security settings
- `admin.php?page=wp-file-manager-settings` - File manager access

---

### 3. Always Admin-Only Plugin Pages

**Protected Plugins:**

#### Backup & Migration
- **UpdraftPlus** (`admin.php?page=updraftplus`)
  - Can restore entire site
  - Access to backups
  - Database download
  
- **BackWPup** (`admin.php?page=backwpup`)
  - Backup management
  - Schedule configuration
  
- **Duplicator** (`admin.php?page=duplicator`)
  - Site cloning
  - Package creation
  
- **All-in-One WP Migration** (`admin.php?page=ai1wm_export`)
  - Full site export/import

#### Cache & Performance
- **LiteSpeed Cache** (`admin.php?page=litespeed`)
  - Cache purge
  - Performance settings
  - CDN configuration
  
- **WP Rocket** (`admin.php?page=wprocket`)
  - Cache control
  - Optimization settings
  
- **W3 Total Cache** (`admin.php?page=w3tc_dashboard`)
  - Cache management
  - CDN settings

#### Security
- **Wordfence** (`admin.php?page=Wordfence`)
  - Firewall rules
  - Malware scanning
  - Login security
  
- **Sucuri Security** (`admin.php?page=sucuriscan`)
  - Security auditing
  - Malware detection
  
- **iThemes Security** (`admin.php?page=itsec`)
  - Security hardening
  - User management

#### File & Database Management
- **WP File Manager** (`admin.php?page=wp-file-manager-settings`)
  - File system access
  - File upload/download
  
- **phpMyAdmin** (if installed)
  - Direct database access
  
- **Adminer** (if installed)
  - Database management

**Why These Are Critical:**

1. **Data Exfiltration Risk:** 
   - Backup plugins allow downloading entire site
   - Database access reveals passwords, API keys, user data

2. **Site Destruction Risk:**
   - Cache purge on high-traffic site = server overload
   - Incorrect settings = site downtime
   - File deletion = site broken

3. **Security Bypass:**
   - Disabling security plugins
   - Modifying firewall rules
   - Accessing security logs reveals attack vectors

4. **Privilege Escalation:**
   - Some plugins allow user role modification
   - Database access can change user capabilities

---

### 4. AJAX Security

**Nonce Verification:**
```php
// In class-settings.php
public function save_settings() {
    check_ajax_referer( 'apg_settings_nonce', 'security' );
    
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Insufficient permissions' );
        return;
    }
    // ... process data
}
```

**Protection Level:** HIGH

**What it does:**
- All AJAX requests require valid nonce
- Nonces expire after 24 hours
- Capability check before any data processing
- Prevents CSRF attacks

---

### 5. Data Sanitization & Escaping

**Input Sanitization:**
```php
// Sanitize text fields
$plugin_name = sanitize_text_field( $data['plugin_name'] );

// Sanitize URLs
$webhook_url = esc_url_raw( $data['discord_webhook_url'] );

// Sanitize code snippets
$custom_css = wp_strip_all_tags( $data['custom_css'] );
$custom_js = wp_strip_all_tags( $data['custom_js'] );

// Sanitize PHP (no sanitization - controlled execution)
$custom_php = $data['custom_php']; // Only admins can save
```

**Output Escaping:**
```php
// Escape HTML
echo esc_html( $plugin_name );

// Escape URLs
echo esc_url( $logo_url );

// Escape attributes
echo 'data-id="' . esc_attr( $option_id ) . '"';
```

**Protection Level:** MEDIUM

**What it does:**
- Prevents XSS attacks
- Prevents SQL injection (when using wpdb)
- Ensures data integrity

---

### 6. Custom PHP Execution Safety

**Implementation:**
```php
public function execute_custom_php() {
    // Only administrators
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    $options = get_option( 'apg_settings', array() );
    $custom_php = isset( $options['custom_php'] ) ? trim( $options['custom_php'] ) : '';
    
    if ( ! empty( $custom_php ) ) {
        try {
            eval( $custom_php );
        } catch ( Exception $e ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[Author Post Guard] Custom PHP Error: ' . $e->getMessage() );
            }
        }
    }
}
```

**Protection Level:** CONTROLLED RISK

**Security Considerations:**
- ⚠️ Uses `eval()` - inherently dangerous
- ✅ Only administrators can execute
- ✅ Wrapped in try-catch for error handling
- ✅ Errors logged when WP_DEBUG enabled
- ⚠️ No syntax validation before execution

**Recommendations:**
1. Only use if absolutely necessary
2. Test code in staging first
3. Keep backups before adding PHP code
4. Consider alternatives (must-use plugins, theme functions.php)

**Safer Alternative:**
Instead of custom PHP, create a must-use plugin:
```bash
# Create mu-plugins folder
mkdir -p wp-content/mu-plugins

# Create your custom code file
nano wp-content/mu-plugins/custom-functions.php
```

---

### 7. Media Library Restrictions

**Implementation:**
```php
public function restrict_media_library( $query ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        global $current_user;
        $query['author'] = $current_user->ID;
    }
    return $query;
}
```

**Protection Level:** MEDIUM

**What it does:**
- Authors/Editors see only their uploaded media
- Administrators see all media
- Prevents accessing other users' uploads

**Use Cases:**
- Multi-author blogs
- Client sites with multiple contributors
- Agencies managing content teams

---

## Security Best Practices

### For Plugin Administrators

1. **User Management**
   - Only give Administrator role to trusted users
   - Use Editor role for content managers
   - Use Author role for writers
   - Regularly audit user list

2. **Custom PHP Code**
   - Test in staging environment first
   - Keep backups before adding code
   - Use version control for snippets
   - Document what each snippet does
   - Consider alternatives to eval()

3. **Menu Hiding**
   - Hide backup plugins from all non-admins
   - Hide security plugins from editors
   - Hide cache plugins from authors
   - Keep Settings → General visible for Editors if needed

4. **Webhook Security**
   - Use HTTPS webhooks only
   - Rotate webhook URLs periodically
   - Don't send sensitive data in webhooks
   - Monitor webhook logs

5. **Regular Maintenance**
   - Keep WordPress updated
   - Keep all plugins updated
   - Monitor WordPress error logs
   - Review user access quarterly

### For Site Owners

1. **Plugin Updates**
   - Enable automatic updates from GitHub
   - Test updates in staging first
   - Read changelog before updating
   - Keep rollback plan ready

2. **Access Control**
   - Limit Administrator accounts to 1-2 users
   - Use strong passwords (20+ characters)
   - Enable two-factor authentication
   - Monitor login attempts

3. **Monitoring**
   - Check error logs weekly
   - Monitor webhook notifications
   - Review hidden menu list monthly
   - Audit user roles quarterly

4. **Incident Response**
   - Have backup restoration plan
   - Know how to disable plugin via FTP
   - Keep emergency contact list
   - Document all customizations

---

## Security Testing Checklist

### Access Control Tests

- [ ] Non-admin cannot access `/wp-admin/admin.php?page=author-post-guard`
- [ ] Non-admin cannot access hidden plugin pages via URL
- [ ] Non-admin cannot access UpdraftPlus directly
- [ ] Editor cannot see Author's media files
- [ ] AJAX requests fail without valid nonce
- [ ] AJAX requests fail for non-administrators

### Penetration Testing Scenarios

**Test 1: Direct URL Access**
```
User: Editor
Action: Navigate to /wp-admin/admin.php?page=updraftplus
Expected: 403 Access Denied
```

**Test 2: AJAX Hijacking**
```
User: Author
Action: Submit AJAX request with forged nonce
Expected: AJAX error "Invalid nonce"
```

**Test 3: SQL Injection**
```
Input: Plugin name = "Test'; DROP TABLE wp_posts;--"
Expected: Sanitized to "Test DROP TABLE wp_posts"
```

**Test 4: XSS Attack**
```
Input: Footer text = "<script>alert('XSS')</script>"
Expected: Displayed as plain text, not executed
```

**Test 5: PHP Injection**
```
User: Editor
Action: Try to save custom PHP snippet
Expected: Cannot access settings page
```

---

## Known Limitations

### What This Plugin Does NOT Protect Against

1. **WordPress Core Vulnerabilities**
   - Keep WordPress updated
   - This plugin doesn't patch core issues

2. **Server-Level Attacks**
   - Not a firewall replacement
   - Use Wordfence/Sucuri for firewall

3. **Brute Force Attacks**
   - Doesn't limit login attempts
   - Use login security plugin

4. **File System Access**
   - Doesn't prevent FTP/SSH access
   - Secure your hosting credentials

5. **Database Direct Access**
   - Doesn't prevent phpMyAdmin access via URL
   - Secure phpMyAdmin installation separately

### Admin Bypass Warning

⚠️ **Administrators can still:**
- Access all hidden pages
- See all media files
- Execute custom PHP code
- Modify plugin settings
- Disable the plugin

**Mitigation:**
- Only give Administrator role to 1-2 trusted users
- Use security plugins to monitor admin activity
- Enable WordPress audit logs
- Require 2FA for administrators

---

## Compliance & Standards

### WordPress Coding Standards
- Follows WordPress PHP Coding Standards
- Uses WordPress escape functions
- Implements nonce verification
- Uses WordPress database class (wpdb)

### OWASP Top 10 Coverage

| Risk | Mitigated? | How |
|------|------------|-----|
| Injection | ✅ Yes | Sanitization, prepared statements |
| Broken Authentication | ✅ Yes | WordPress capability checks |
| Sensitive Data Exposure | ✅ Yes | Admin-only access controls |
| XML External Entities | ❌ N/A | No XML processing |
| Broken Access Control | ✅ Yes | Capability checks, URL blocking |
| Security Misconfiguration | ⚠️ Partial | Depends on admin configuration |
| Cross-Site Scripting | ✅ Yes | Output escaping |
| Insecure Deserialization | ✅ Yes | No user-controlled deserialization |
| Using Components with Known Vulnerabilities | ✅ Yes | Auto-updates from GitHub |
| Insufficient Logging & Monitoring | ⚠️ Partial | WP_DEBUG logging available |

---

## Reporting Security Issues

If you discover a security vulnerability:

1. **DO NOT** create a public GitHub issue
2. Email security concerns to: security@tansiqlabs.com
3. Include:
   - Description of vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)
4. Allow 48 hours for initial response
5. Coordinated disclosure after patch released

### Security Response Timeline

- **0-48 hours:** Acknowledge receipt
- **48-72 hours:** Verify vulnerability
- **3-7 days:** Develop patch
- **7-14 days:** Release patched version
- **14+ days:** Public disclosure (if appropriate)

---

## Security Changelog

### Version 1.1.0
- ✅ Added admin-only plugin access
- ✅ Implemented direct URL blocking
- ✅ Protected backup plugin pages
- ✅ Protected cache plugin pages
- ✅ Protected security plugin pages
- ✅ Protected file manager pages
- ✅ Enhanced AJAX capability checks

### Version 1.0.0
- ✅ Initial release
- ✅ Nonce verification
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Capability checks
- ✅ Media library restrictions

---

## Additional Resources

- [WordPress Security Whitepaper](https://wordpress.org/about/security/)
- [OWASP WordPress Security Guide](https://owasp.org/www-project-wordpress-security/)
- [WordPress Capability Check Guide](https://developer.wordpress.org/plugins/security/checking-user-capabilities/)
- [WordPress Nonce Guide](https://developer.wordpress.org/apis/security/nonces/)

---

**Last Updated:** Version 1.1.0  
**Security Review Date:** 2024  
**Next Review:** Upon major version release
