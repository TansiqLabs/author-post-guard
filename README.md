# Author Post Guard

<p align="center">
  <img src="assets/logo.svg" alt="Author Post Guard Logo" width="120">
</p>

<p align="center">
  <strong>A Premium White-Label WordPress Plugin by Tansiq Labs</strong>
</p>

<p align="center">
  <a href="https://tansiqlabs.com">Website</a> •
  <a href="mailto:support@tansiqlabs.com">Support</a> •
  <a href="https://github.com/TansiqLabs/author-post-guard/issues">Issues</a>
</p>

---

## 🎯 Overview

**Author Post Guard** is a premium WordPress plugin designed for agencies, developers, and businesses who need complete control over their WordPress admin experience. It provides white-labeling capabilities, advanced menu management, webhook notifications, and automatic updates from GitHub.

**Version 1.1.0** introduces comprehensive security features including admin-only access controls, direct URL blocking for hidden menus, and protected plugin pages for backup and security tools.

## ✨ Features

### 🎨 White Labeling & Branding
- Replace WordPress footer text with your custom branding
- Custom logo upload with media library integration
- Custom logo on the login page
- Replace WordPress logo in the admin bar
- Media library restriction (users see only their uploads)
- Complete brand consistency across the admin

### 🔒 Security & Access Control
- **Admin-Only Plugin Access**: Only administrators can access plugin settings
- **Direct URL Blocking**: Hidden menus blocked even with direct URL access
- **Protected Plugin Pages**: Backup plugins (UpdraftPlus, BackWPup, Duplicator) always admin-only
- **Cache Plugin Protection**: LiteSpeed, WP Rocket, W3 Total Cache restricted to admins
- **Security Plugin Protection**: Wordfence, Sucuri, iThemes Security admin-only
- **File Manager Protection**: WP File Manager, Adminer, phpMyAdmin restricted
- Comprehensive capability checks throughout

### 📋 Menu Management (40+ Plugins Supported)
- Hide specific menu items for different user roles
- **Form Plugins**: Contact Form 7, Ninja Forms, WPForms, Gravity Forms, Formidable Forms
- **Page Builders**: Elementor, Divi, Beaver Builder, WPBakery, Oxygen, Bricks
- **SEO Tools**: Yoast SEO, Rank Math, All in One SEO
- **E-Commerce**: WooCommerce, Easy Digital Downloads, WP eCommerce
- **Backup Tools**: UpdraftPlus, BackWPup, Duplicator, All-in-One WP Migration
- **Cache Plugins**: LiteSpeed Cache, WP Rocket, W3 Total Cache, WP Super Cache
- **Security**: Wordfence, Sucuri, iThemes Security, Solid Security
- **Analytics**: MonsterInsights, Google Analytics, Matomo
- **File Managers**: WP File Manager, File Manager, Simple File Manager
- **Custom Fields**: Advanced Custom Fields, Pods, Toolset, Meta Box
- **And many more...**
- Administrator menus remain unaffected
- Toggle-based intuitive interface

### 💻 Code Snippets
- Custom CSS for admin area styling
- Custom JavaScript for admin functionality
- Custom PHP code execution (admin only)
- Safe code editor with syntax highlighting support
- Real-time preview and testing

### 🔔 Advanced Notification System
- **Discord Integration**: Send notifications via Discord webhooks
- **Telegram Integration**: Notify through Telegram bot
- **Generic Webhooks**: Support for Slack, Zapier, and custom endpoints

**Trigger Events:**
- Post Published
- Post Pending Review
- New User Registration

### 🔄 GitHub Auto-Updates
- Automatic update detection from GitHub releases
- Seamless WordPress plugin update integration
- Support for private repositories with access tokens
- Manual update check functionality

### 💎 Modern Admin UI
- SaaS-style dashboard interface
- Clean tabbed navigation
- Responsive design
- Subtle animations and transitions
- Professional color palette

## 📁 Project Structure

```
author-post-guard/
├── 📄 author-post-guard.php           # Main plugin file (v1.1.0)
├── 📋 CHANGELOG.md                    # Version history
├── 🚀 DEPLOYMENT.md                   # Production deployment guide
├── ⭐ FEATURES.md                     # Complete feature documentation
├── 📜 LICENSE                         # MIT License
├── 📊 PROJECT-SUMMARY.md              # Project overview & metrics
├── ⚡ QUICKSTART.md                   # 5-minute setup guide
├── 📖 README.md                       # Main documentation (this file)
├── 🔒 SECURITY.md                     # Security architecture guide
├── ✅ TESTING.md                      # Testing procedures & checklist
├── 🔍 verify.sh                       # Automated verification script
├── 📁 inc/
│   ├── class-settings.php            # Settings page & admin UI (5 tabs)
│   ├── class-notifications.php       # Discord, Telegram, Generic webhooks
│   └── class-updater.php             # GitHub auto-update integration
└── 📁 assets/
    ├── admin-script.js               # Admin interactions, AJAX, media uploader
    ├── admin-style.css               # Modern SaaS-style admin design (1000+ lines)
    └── logo.svg                      # Plugin logo (shield with gradient)
└── assets/
    ├── admin-style.css       # Modern admin styles
    ├── admin-script.js       # Frontend JavaScript
    └── logo.svg              # Plugin logo
```

## 🔒 Security Features

- **Admin-Only Access**: Plugin settings only accessible by administrators
- **Direct URL Protection**: Hidden menus cannot be accessed via direct URL
- **Role-Based Restrictions**: Granular control over menu visibility
- **Media Library Isolation**: Users see only their own uploads
- **Capability Checks**: All actions verify proper user permissions
- **Protected Plugin Pages**: Backup, cache, and security plugins restricted to admins

## 🚀 Installation

### Manual Installation
1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New
3. Click "Upload Plugin" and select the zip file
4. Activate the plugin

### From GitHub
1. Clone or download from the repository
2. Upload the `author-post-guard` folder to `/wp-content/plugins/`
3. Activate through the WordPress Plugins screen

## ⚙️ Configuration

After activation, navigate to **Author Post Guard** in your WordPress admin sidebar.

### General Branding Tab
- Enable/disable white labeling
- Set custom admin footer text
- Toggle login page logo
- Toggle admin bar logo

### Menu Control Tab
- Select user roles (Editor, Author, Contributor, Subscriber)
- Check menus to hide for each role
- Changes apply immediately after saving

### Notifications Tab
Configure webhook endpoints:

**Discord:**
1. Create a webhook in your Discord server (Server Settings → Integrations)
2. Copy the webhook URL
3. Paste in the Discord Webhook URL field

**Telegram:**
1. Create a bot via @BotFather
2. Copy the bot token
3. Get your chat ID from @getidsbot
4. Enter both in the settings

**Generic Webhook:**
- Enter any HTTP endpoint that accepts JSON POST requests
- Compatible with Slack, Zapier, Make, n8n, etc.

### Update Settings Tab
- Enable/disable automatic updates
- Configure GitHub repository path
- Add access token for private repos
- Manually check for updates

## 🔌 Webhook Payload Format

### Generic Webhook JSON Structure
```json
{
  "source": "author-post-guard",
  "version": "1.0.0",
  "site_url": "https://example.com",
  "site_name": "Your Site Name",
  "event": "post_published",
  "data": {
    "post_id": 123,
    "title": "Post Title",
    "author": "Author Name",
    "post_type": "post",
    "permalink": "https://example.com/post-slug",
    "timestamp": "2025-12-24 10:30:00",
    "site": "Your Site Name"
  }
}
```

## 🛠️ Development

### Requirements
- WordPress 5.8+
- PHP 7.4+
- Modern browser for admin interface

### Testing
Run automated verification:
```bash
./verify.sh
```

This checks:
- File structure integrity
- PHP syntax validation
- Security features
- Code quality metrics
- Version consistency

### Customizing the Logo
Replace `assets/logo.svg` with your own SVG logo. Recommended dimensions: 200x200px.

### Hooks & Filters
The plugin follows WordPress coding standards and can be extended through standard WordPress hooks.

---

## 📚 Complete Documentation

Comprehensive guides are available:

| Document | Description | Lines |
|----------|-------------|-------|
| [📖 README.md](README.md) | Main documentation & overview | 200+ |
| [⚡ QUICKSTART.md](QUICKSTART.md) | 5-minute setup guide | 200+ |
| [⭐ FEATURES.md](FEATURES.md) | Complete feature documentation | 650+ |
| [🔒 SECURITY.md](SECURITY.md) | Security architecture & best practices | 550+ |
| [✅ TESTING.md](TESTING.md) | Testing procedures & checklist | 350+ |
| [🚀 DEPLOYMENT.md](DEPLOYMENT.md) | Production deployment guide | 400+ |
| [📋 CHANGELOG.md](CHANGELOG.md) | Version history & changes | 50+ |
| [📊 PROJECT-SUMMARY.md](PROJECT-SUMMARY.md) | Project overview & metrics | 650+ |

**Total Documentation:** 3,050+ lines covering every aspect of the plugin.

### Quick Links
- **New to the plugin?** → Start with [QUICKSTART.md](QUICKSTART.md)
- **Need all features?** → Read [FEATURES.md](FEATURES.md)
- **Security questions?** → Check [SECURITY.md](SECURITY.md)
- **Deploying to production?** → Follow [DEPLOYMENT.md](DEPLOYMENT.md)
- **Testing the plugin?** → Use [TESTING.md](TESTING.md)

---

## 🏆 Project Highlights

### Code Quality
- ✅ **0 Syntax Errors** - Clean, production-ready code
- ✅ **35 Automated Checks** - All passing
- ✅ **114 Sanitization Calls** - Comprehensive security
- ✅ **6 Capability Checks** - Proper access control
- ✅ **6,700+ Lines** - Well-documented codebase

### Security
- 🔒 Admin-only plugin access
- 🔒 Direct URL blocking for hidden menus
- 🔒 Protected plugin pages (backup, cache, security)
- 🔒 Multi-layer security architecture
- 🔒 OWASP Top 10 coverage

### Features
- 🎨 40+ plugins supported in menu control
- 🎨 Custom logo upload via Media Library
- 🎨 Modern SaaS-style admin interface
- 🎨 Code snippets (CSS, JS, PHP)
- 🎨 Webhook notifications (Discord, Telegram, Generic)
- 🎨 GitHub auto-updates

### Documentation
- 📚 8 comprehensive guides
- 📚 3,050+ lines of documentation
- 📚 Complete testing procedures
- 📚 Security best practices
- 📚 Deployment checklists

---

## 📄 License

MIT License - see [LICENSE](LICENSE) for details.

This plugin is free to use, modify, and distribute commercially or personally.

## 🤝 Support

- **Email:** support@tansiqlabs.com
- **Website:** https://tansiqlabs.com
- **Issues:** https://github.com/TansiqLabs/author-post-guard/issues

---

<p align="center">
   Developed by <a href="https://tansiqlabs.com">Tansiq Labs</a>
</p>
