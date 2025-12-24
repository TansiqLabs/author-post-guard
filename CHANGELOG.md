# Changelog

All notable changes to Author Post Guard will be documented in this file.
## [1.2.0] - 2024-12-24

### Added
- **Reporter Role**: New custom WordPress role with limited permissions
  - Can login and create own posts
  - Can publish and edit own posts
  - Can upload and manage own media files
  - Cannot access other users' content
  - Cannot manage categories, tags, or site settings
- Reporter Role management tab in settings
- Visual capability indicators showing what Reporters can and cannot do
- Enable/disable toggle for Reporter role
- Automatic media library restrictions for Reporter users

### Removed
- Menu Control tab (deprecated - not useful for the workflow)
- All menu visibility control functions
- Direct URL blocking functions (no longer needed)
- Hidden menus configuration options

### Changed
- Simplified plugin focus to branding and role management
- Updated tab navigation (now 5 tabs: Branding, Reporter Role, Snippets, Notifications, Updates)
- Improved settings page organization
## [1.1.0] - 2025-12-24

### Added
- **Direct URL Protection**: Hidden menus now blocked from direct URL access
- **Admin-Only Plugin Pages**: UpdraftPlus, BackWPup, and other critical plugins restricted to administrators
- **Custom Logo Upload**: Media library integration for logo selection
- **Code Snippets**: Add custom CSS, JavaScript, and PHP code
- **Media Library Restriction**: Non-admin users see only their own uploads
- **Comprehensive Menu List**: Added support for 40+ popular plugins
- **Enhanced Security**: Multiple layers of access control and capability checks

### Changed
- Updated sidebar icon to standard WordPress dashicon (shield-alt)
- Improved admin bar logo system with custom logo support
- Enhanced button visibility with white text on dark backgrounds
- GitHub repository field now read-only to prevent accidental changes
- License changed to MIT

### Fixed
- Plugin settings now properly restricted to administrators only
- Direct URL access to hidden pages now blocked
- Form save issues resolved for all tabs
- Logo preview now updates dynamically when custom logo is uploaded

### Security
- Added `manage_options` capability checks throughout
- Implemented direct URL access blocking for hidden menus
- Protected sensitive plugin pages from non-admin access
- Enhanced media library isolation for user uploads

## [1.0.0] - 2025-12-24

### Initial Release
- White labeling and branding features
- Menu visibility control by user role
- Discord, Telegram, and generic webhook notifications
- GitHub auto-update integration
- Modern SaaS-style admin interface
