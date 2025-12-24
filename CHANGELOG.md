# Changelog

All notable changes to Author Post Guard will be documented in this file.

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
