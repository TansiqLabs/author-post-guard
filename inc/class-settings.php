<?php
/**
 * Settings Handler for Author Post Guard
 *
 * Manages the admin settings page with modern tabbed interface,
 * branding controls, and menu management functionality.
 *
 * @package AuthorPostGuard
 * @subpackage Settings
 * @since 1.0.0
 * @author Tansiq Labs <support@tansiqlabs.com>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class APG_Settings
 *
 * Handles all settings page rendering and form processing
 */
class APG_Settings {

    /**
     * Available tabs configuration
     *
     * @var array
     */
    private $tabs = array();

    /**
     * Current active tab
     *
     * @var string
     */
    private $current_tab = 'branding';

    /**
     * Constructor - Set up hooks
     */
    public function __construct() {
        $this->define_tabs();
        
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'wp_ajax_apg_save_settings', array( $this, 'ajax_save_settings' ) );
        add_action( 'wp_ajax_apg_test_webhook', array( $this, 'ajax_test_webhook' ) );
    }

    /**
     * Define available tabs
     *
     * @return void
     */
    private function define_tabs() {
        $this->tabs = array(
            'branding'      => array(
                'label' => __( 'General Branding', 'author-post-guard' ),
                'icon'  => 'dashicons-art',
            ),
            'menu'          => array(
                'label' => __( 'Menu Control', 'author-post-guard' ),
                'icon'  => 'dashicons-menu',
            ),
            'snippets'      => array(
                'label' => __( 'Code Snippets', 'author-post-guard' ),
                'icon'  => 'dashicons-editor-code',
            ),
            'notifications' => array(
                'label' => __( 'Notifications', 'author-post-guard' ),
                'icon'  => 'dashicons-bell',
            ),
            'updates'       => array(
                'label' => __( 'Update Settings', 'author-post-guard' ),
                'icon'  => 'dashicons-update',
            ),
        );
    }

    /**
     * Register admin menu page
     *
     * @return void
     */
    public function register_admin_menu() {
        add_menu_page(
            __( 'Author Post Guard', 'author-post-guard' ),
            __( 'Author Post Guard', 'author-post-guard' ),
            'manage_options',
            'author-post-guard',
            array( $this, 'render_settings_page' ),
            $this->get_menu_icon_svg(),
            30
        );
    }

    /**
     * Get menu icon for admin sidebar
     *
     * @return string Icon identifier
     */
    private function get_menu_icon_svg() {
        // Using dashicon for better WordPress integration
        return 'dashicons-shield-alt';
    }

    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings() {
        register_setting(
            'apg_settings_group',
            'apg_settings',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array(),
            )
        );
    }

    /**
     * Sanitize all settings before saving
     *
     * @param array $input Raw input data
     * @return array Sanitized data
     */
    public function sanitize_settings( $input ) {
        $sanitized = array();

        // Branding settings
        $sanitized['branding_enabled']      = ! empty( $input['branding_enabled'] );
        $sanitized['custom_footer_text']    = sanitize_text_field( $input['custom_footer_text'] ?? '' );
        $sanitized['login_logo_enabled']    = ! empty( $input['login_logo_enabled'] );
        $sanitized['adminbar_logo_enabled'] = ! empty( $input['adminbar_logo_enabled'] );
        $sanitized['custom_logo_url']       = esc_url_raw( $input['custom_logo_url'] ?? '' );
        $sanitized['restrict_media_library']= ! empty( $input['restrict_media_library'] );

        // Menu control
        if ( isset( $input['hidden_menus'] ) && is_array( $input['hidden_menus'] ) ) {
            $sanitized['hidden_menus'] = $this->sanitize_hidden_menus( $input['hidden_menus'] );
        } else {
            $sanitized['hidden_menus'] = array();
        }

        // Code Snippets
        $sanitized['custom_css']            = isset( $input['custom_css'] ) ? wp_strip_all_tags( $input['custom_css'] ) : '';
        $sanitized['custom_js']             = isset( $input['custom_js'] ) ? wp_strip_all_tags( $input['custom_js'] ) : '';
        $sanitized['custom_php']            = isset( $input['custom_php'] ) ? $input['custom_php'] : ''; // Don't strip PHP

        // Notification settings
        $sanitized['discord_webhook']       = esc_url_raw( $input['discord_webhook'] ?? '' );
        $sanitized['telegram_bot_token']    = sanitize_text_field( $input['telegram_bot_token'] ?? '' );
        $sanitized['telegram_chat_id']      = sanitize_text_field( $input['telegram_chat_id'] ?? '' );
        $sanitized['generic_webhook_url']   = esc_url_raw( $input['generic_webhook_url'] ?? '' );
        $sanitized['notify_post_published'] = ! empty( $input['notify_post_published'] );
        $sanitized['notify_post_pending']   = ! empty( $input['notify_post_pending'] );
        $sanitized['notify_user_registered']= ! empty( $input['notify_user_registered'] );

        // Update settings
        $sanitized['github_repo']           = sanitize_text_field( $input['github_repo'] ?? 'TansiqLabs/author-post-guard' );
        $sanitized['auto_update_enabled']   = ! empty( $input['auto_update_enabled'] );
        $sanitized['github_access_token']   = sanitize_text_field( $input['github_access_token'] ?? '' );

        return $sanitized;
    }

    /**
     * Sanitize hidden menus array
     *
     * @param array $menus Raw menus data
     * @return array Sanitized menus
     */
    private function sanitize_hidden_menus( $menus ) {
        $sanitized = array();
        
        foreach ( $menus as $role => $menu_slugs ) {
            $role = sanitize_key( $role );
            $sanitized[ $role ] = array();
            
            if ( is_array( $menu_slugs ) ) {
                foreach ( $menu_slugs as $slug ) {
                    $sanitized[ $role ][] = sanitize_text_field( $slug );
                }
            }
        }
        
        return $sanitized;
    }

    /**
     * AJAX handler for saving settings
     *
     * @return void
     */
    public function ajax_save_settings() {
        // Verify nonce
        if ( ! check_ajax_referer( 'apg_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'author-post-guard' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'author-post-guard' ) ) );
        }

        // Get and sanitize settings
        $settings = isset( $_POST['settings'] ) ? $_POST['settings'] : array();
        
        // Parse serialized data if needed
        if ( is_string( $settings ) ) {
            parse_str( $settings, $parsed );
            // Extract the apg_settings array if it exists
            $settings = isset( $parsed['apg_settings'] ) ? $parsed['apg_settings'] : $parsed;
        }

        // If settings is still not an array, bail
        if ( ! is_array( $settings ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid settings format.', 'author-post-guard' ) ) );
        }

        // Sanitize and save
        $sanitized = $this->sanitize_settings( $settings );
        
        // Always update, even if values are the same (to avoid the "no changes" issue)
        update_option( 'apg_settings', $sanitized, false );
        
        wp_send_json_success( array( 'message' => __( 'Settings saved successfully!', 'author-post-guard' ) ) );
    }

    /**
     * AJAX handler for testing webhooks
     *
     * @return void
     */
    public function ajax_test_webhook() {
        // Verify nonce
        if ( ! check_ajax_referer( 'apg_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'author-post-guard' ) ) );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'author-post-guard' ) ) );
        }

        $type = isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '';
        
        // Get notifications instance and send test
        $notifications = author_post_guard()->notifications;
        $result = $notifications->send_test_notification( $type );

        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Test notification sent successfully!', 'author-post-guard' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to send test notification. Check your settings.', 'author-post-guard' ) ) );
        }
    }

    /**
     * Render the main settings page
     *
     * @return void
     */
    public function render_settings_page() {
        // Security check - only administrators can access
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'author-post-guard' ) );
        }
        
        // Get current tab
        $this->current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'branding';
        
        if ( ! array_key_exists( $this->current_tab, $this->tabs ) ) {
            $this->current_tab = 'branding';
        }

        // Get current settings
        $options = get_option( 'apg_settings', array() );
        ?>
        <div class="apg-wrap">
            <!-- Header -->
            <header class="apg-header">
                <div class="apg-header-brand">
                    <img src="<?php echo esc_url( APG_PLUGIN_URL . 'assets/logo.svg' ); ?>" alt="Tansiq Labs" class="apg-logo">
                    <div class="apg-header-text">
                        <h1><?php esc_html_e( 'Author Post Guard', 'author-post-guard' ); ?></h1>
                        <span class="apg-version">v<?php echo esc_html( APG_VERSION ); ?></span>
                    </div>
                </div>
                <div class="apg-header-actions">
                    <a href="https://tansiqlabs.com" target="_blank" class="apg-btn apg-btn-outline">
                        <span class="dashicons dashicons-external"></span>
                        <?php esc_html_e( 'Visit Tansiq Labs', 'author-post-guard' ); ?>
                    </a>
                </div>
            </header>

            <!-- Tab Navigation -->
            <nav class="apg-tabs">
                <?php foreach ( $this->tabs as $tab_key => $tab ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=author-post-guard&tab=' . $tab_key ) ); ?>" 
                       class="apg-tab <?php echo $this->current_tab === $tab_key ? 'apg-tab-active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $tab['icon'] ); ?>"></span>
                        <?php echo esc_html( $tab['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <!-- Main Content -->
            <main class="apg-content">
                <form id="apg-settings-form" method="post" action="options.php">
                    <?php settings_fields( 'apg_settings_group' ); ?>
                    
                    <div class="apg-tab-content">
                        <?php
                        switch ( $this->current_tab ) {
                            case 'branding':
                                $this->render_branding_tab( $options );
                                break;
                            case 'menu':
                                $this->render_menu_tab( $options );
                                break;
                            case 'snippets':
                                $this->render_snippets_tab( $options );
                                break;
                            case 'notifications':
                                $this->render_notifications_tab( $options );
                                break;
                            case 'updates':
                                $this->render_updates_tab( $options );
                                break;
                        }
                        ?>
                    </div>

                    <!-- Save Button -->
                    <div class="apg-actions">
                        <button type="submit" class="apg-btn apg-btn-primary apg-btn-save">
                            <span class="dashicons dashicons-saved"></span>
                            <?php esc_html_e( 'Save Changes', 'author-post-guard' ); ?>
                        </button>
                        <span class="apg-save-indicator"></span>
                    </div>
                </form>
            </main>

            <!-- Footer -->
            <footer class="apg-footer">
                <p>
                    <?php 
                    printf(
                        esc_html__( 'Made with %s by %s', 'author-post-guard' ),
                        '<span class="apg-heart">♥</span>',
                        '<a href="https://tansiqlabs.com" target="_blank">Tansiq Labs</a>'
                    ); 
                    ?>
                </p>
                <p class="apg-support">
                    <?php esc_html_e( 'Need help?', 'author-post-guard' ); ?>
                    <a href="mailto:support@tansiqlabs.com">support@tansiqlabs.com</a>
                </p>
            </footer>
        </div>
        <?php
    }

    /**
     * Render General Branding tab content
     *
     * @param array $options Current settings
     * @return void
     */
    private function render_branding_tab( $options ) {
        ?>
        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'White Label Settings', 'author-post-guard' ); ?></h2>
                <p><?php esc_html_e( 'Customize the WordPress admin branding to match your brand identity.', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <!-- Enable Branding -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="branding_enabled"><?php esc_html_e( 'Enable White Labeling', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Toggle all branding customizations on or off.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <label class="apg-toggle">
                                <input type="checkbox" name="apg_settings[branding_enabled]" id="branding_enabled" value="1" 
                                    <?php checked( ! empty( $options['branding_enabled'] ) ); ?>>
                                <span class="apg-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Custom Footer Text -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="custom_footer_text"><?php esc_html_e( 'Admin Footer Text', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Replace the default WordPress footer text.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <input type="text" name="apg_settings[custom_footer_text]" id="custom_footer_text" 
                                   value="<?php echo esc_attr( $options['custom_footer_text'] ?? 'Powered by Tansiq Labs' ); ?>" 
                                   class="apg-input" placeholder="Powered by Tansiq Labs">
                        </div>
                    </div>
                </div>

                <!-- Login Logo -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="login_logo_enabled"><?php esc_html_e( 'Custom Login Logo', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Replace the WordPress logo on the login page with your custom logo.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <label class="apg-toggle">
                                <input type="checkbox" name="apg_settings[login_logo_enabled]" id="login_logo_enabled" value="1" 
                                    <?php checked( ! empty( $options['login_logo_enabled'] ) ); ?>>
                                <span class="apg-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Admin Bar Logo -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="adminbar_logo_enabled"><?php esc_html_e( 'Admin Bar Logo', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Replace the WordPress logo in the admin bar with your custom logo.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <label class="apg-toggle">
                                <input type="checkbox" name="apg_settings[adminbar_logo_enabled]" id="adminbar_logo_enabled" value="1" 
                                    <?php checked( ! empty( $options['adminbar_logo_enabled'] ) ); ?>>
                                <span class="apg-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Custom Logo Upload -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="custom_logo_url"><?php esc_html_e( 'Custom Logo URL', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Upload or enter a custom logo URL. Leave empty to use the default plugin logo.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input" style="flex: 1; max-width: 400px;">
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <input type="url" name="apg_settings[custom_logo_url]" id="custom_logo_url" 
                                       value="<?php echo esc_attr( $options['custom_logo_url'] ?? '' ); ?>" 
                                       class="apg-input" style="flex: 1;" placeholder="https://example.com/logo.svg">
                                <button type="button" class="apg-btn apg-btn-outline apg-btn-sm apg-upload-logo">
                                    <?php esc_html_e( 'Upload', 'author-post-guard' ); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restrict Media Library -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="restrict_media_library"><?php esc_html_e( 'Restrict Media Library', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Non-admin users will only see their own uploaded files in the media library.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <label class="apg-toggle">
                                <input type="checkbox" name="apg_settings[restrict_media_library]" id="restrict_media_library" value="1" 
                                    <?php checked( ! empty( $options['restrict_media_library'] ) ); ?>>
                                <span class="apg-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo Preview -->
        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'Logo Preview', 'author-post-guard' ); ?></h2>
                <p><?php esc_html_e( 'Current logo being used for branding', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <div class="apg-logo-preview">
                    <?php 
                    $logo_url = ! empty( $options['custom_logo_url'] ) 
                        ? $options['custom_logo_url'] 
                        : APG_PLUGIN_URL . 'assets/logo.svg';
                    ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo Preview" id="apg-logo-preview-img">
                </div>
                <p class="apg-field-desc" style="text-align: center; margin-top: 15px;">
                    <?php if ( ! empty( $options['custom_logo_url'] ) ) : ?>
                        <?php esc_html_e( 'Using custom uploaded logo', 'author-post-guard' ); ?>
                    <?php else : ?>
                        <?php esc_html_e( 'Using default plugin logo from:', 'author-post-guard' ); ?><br>
                        <code><?php echo esc_html( APG_PLUGIN_DIR . 'assets/logo.svg' ); ?></code>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render Menu Control tab content
     *
     * @param array $options Current settings
     * @return void
     */
    private function render_menu_tab( $options ) {
        global $menu;
        
        // Get all editable roles except administrator
        $roles = wp_roles()->get_names();
        unset( $roles['administrator'] );
        
        // Get current hidden menus
        $hidden_menus = isset( $options['hidden_menus'] ) ? $options['hidden_menus'] : array();

        // Get available menu items
        $menu_items = $this->get_available_menu_items();
        ?>
        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'Menu Visibility Control', 'author-post-guard' ); ?></h2>
                <p><?php esc_html_e( 'Control which menu items are visible to different user roles. Administrators always see all menus.', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <div class="apg-menu-control-grid">
                    <?php foreach ( $roles as $role_key => $role_name ) : ?>
                        <div class="apg-role-card">
                            <div class="apg-role-header">
                                <span class="apg-role-icon dashicons dashicons-admin-users"></span>
                                <h3><?php echo esc_html( $role_name ); ?></h3>
                            </div>
                            <div class="apg-role-menus">
                                <p class="apg-field-desc"><?php esc_html_e( 'Hide these menus:', 'author-post-guard' ); ?></p>
                                <?php foreach ( $menu_items as $slug => $label ) : 
                                    $is_hidden = isset( $hidden_menus[ $role_key ] ) && in_array( $slug, $hidden_menus[ $role_key ], true );
                                ?>
                                    <label class="apg-checkbox-item">
                                        <input type="checkbox" 
                                               name="apg_settings[hidden_menus][<?php echo esc_attr( $role_key ); ?>][]" 
                                               value="<?php echo esc_attr( $slug ); ?>"
                                               <?php checked( $is_hidden ); ?>>
                                        <span class="apg-checkbox-mark"></span>
                                        <span class="apg-checkbox-label"><?php echo esc_html( $label ); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="apg-info-card">
            <span class="dashicons dashicons-info-outline"></span>
            <div>
                <strong><?php esc_html_e( 'Note:', 'author-post-guard' ); ?></strong>
                <?php esc_html_e( 'Menu visibility changes take effect immediately after saving. Users will need to refresh their browser to see the changes.', 'author-post-guard' ); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Get available WordPress admin menu items
     *
     * @return array Menu items with slug => label
     */
    private function get_available_menu_items() {
        return array(
            // Core WordPress Menus
            'index.php'                 => __( 'Dashboard', 'author-post-guard' ),
            'edit.php'                  => __( 'Posts', 'author-post-guard' ),
            'upload.php'                => __( 'Media', 'author-post-guard' ),
            'edit.php?post_type=page'   => __( 'Pages', 'author-post-guard' ),
            'edit-comments.php'         => __( 'Comments', 'author-post-guard' ),
            'themes.php'                => __( 'Appearance', 'author-post-guard' ),
            'plugins.php'               => __( 'Plugins', 'author-post-guard' ),
            'users.php'                 => __( 'Users', 'author-post-guard' ),
            'tools.php'                 => __( 'Tools', 'author-post-guard' ),
            'options-general.php'       => __( 'Settings', 'author-post-guard' ),
            'profile.php'               => __( 'Profile', 'author-post-guard' ),
            
            // Form Builders
            'admin.php?page=wpcf7'      => __( 'Contact Form 7', 'author-post-guard' ),
            'admin.php?page=wpforms-overview' => __( 'WPForms', 'author-post-guard' ),
            'admin.php?page=gf_edit_forms' => __( 'Gravity Forms', 'author-post-guard' ),
            'admin.php?page=ninja-forms' => __( 'Ninja Forms', 'author-post-guard' ),
            'admin.php?page=formidable' => __( 'Formidable Forms', 'author-post-guard' ),
            
            // Page Builders
            'admin.php?page=elementor'  => __( 'Elementor', 'author-post-guard' ),
            'admin.php?page=et_divi_options' => __( 'Divi', 'author-post-guard' ),
            'admin.php?page=vc-general-settings' => __( 'WPBakery', 'author-post-guard' ),
            
            // SEO Plugins
            'admin.php?page=wpseo_dashboard' => __( 'Yoast SEO', 'author-post-guard' ),
            'admin.php?page=rank-math' => __( 'Rank Math', 'author-post-guard' ),
            'admin.php?page=aioseo' => __( 'All in One SEO', 'author-post-guard' ),
            
            // Backup Plugins
            'admin.php?page=updraftplus' => __( 'UpdraftPlus', 'author-post-guard' ),
            'admin.php?page=backwpup' => __( 'BackWPup', 'author-post-guard' ),
            'admin.php?page=duplicator' => __( 'Duplicator', 'author-post-guard' ),
            
            // Cache & Performance
            'admin.php?page=litespeed' => __( 'LiteSpeed Cache', 'author-post-guard' ),
            'admin.php?page=wprocket'  => __( 'WP Rocket', 'author-post-guard' ),
            'admin.php?page=w3tc_dashboard' => __( 'W3 Total Cache', 'author-post-guard' ),
            'admin.php?page=wpsupercache' => __( 'WP Super Cache', 'author-post-guard' ),
            'admin.php?page=autoptimize' => __( 'Autoptimize', 'author-post-guard' ),
            
            // Security Plugins
            'admin.php?page=wordfence' => __( 'Wordfence Security', 'author-post-guard' ),
            'admin.php?page=sucuri' => __( 'Sucuri Security', 'author-post-guard' ),
            'admin.php?page=itsec' => __( 'iThemes Security', 'author-post-guard' ),
            
            // Code & Snippets
            'edit.php?post_type=snippets' => __( 'Code Snippets', 'author-post-guard' ),
            
            // WooCommerce
            'admin.php?page=wc-admin'  => __( 'WooCommerce', 'author-post-guard' ),
            'edit.php?post_type=shop_order' => __( 'Orders (WooCommerce)', 'author-post-guard' ),
            'edit.php?post_type=product' => __( 'Products (WooCommerce)', 'author-post-guard' ),
            'admin.php?page=wc-settings' => __( 'WooCommerce Settings', 'author-post-guard' ),
            
            // File Manager
            'admin.php?page=wp-file-manager-settings' => __( 'File Manager', 'author-post-guard' ),
        );
    }

    /**
     * Render Code Snippets tab content
     *
     * @param array $options Current settings
     * @return void
     */
    private function render_snippets_tab( $options ) {
        ?>
        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'Custom CSS', 'author-post-guard' ); ?></h2>
                <p><?php esc_html_e( 'Add custom CSS that will be loaded in the WordPress admin area.', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <textarea name="apg_settings[custom_css]" id="custom_css" rows="12" class="apg-code-editor" placeholder="/* Add your custom CSS here */&#10;.my-custom-class {&#10;    color: #6366f1;&#10;}"><?php echo esc_textarea( $options['custom_css'] ?? '' ); ?></textarea>
                <p class="apg-field-desc" style="margin-top: 10px;">
                    <span class="dashicons dashicons-info-outline" style="color: var(--apg-info);"></span>
                    <?php esc_html_e( 'This CSS will be applied to the admin area for all users.', 'author-post-guard' ); ?>
                </p>
            </div>
        </div>

        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'Custom JavaScript', 'author-post-guard' ); ?></h2>
                <p><?php esc_html_e( 'Add custom JavaScript that will be loaded in the WordPress admin area.', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <textarea name="apg_settings[custom_js]" id="custom_js" rows="12" class="apg-code-editor" placeholder="// Add your custom JavaScript here&#10;jQuery(document).ready(function($) {&#10;    console.log('Custom JS loaded');&#10;});"><?php echo esc_textarea( $options['custom_js'] ?? '' ); ?></textarea>
                <p class="apg-field-desc" style="margin-top: 10px;">
                    <span class="dashicons dashicons-info-outline" style="color: var(--apg-info);"></span>
                    <?php esc_html_e( 'jQuery is available as $ inside jQuery(document).ready()', 'author-post-guard' ); ?>
                </p>
            </div>
        </div>

        <div class="apg-card">
            <div class="apg-card-header">
                <h2>
                    <?php esc_html_e( 'Custom PHP Code', 'author-post-guard' ); ?>
                    <span class="apg-badge apg-badge-warning" style="margin-left: 10px;"><?php esc_html_e( 'Advanced', 'author-post-guard' ); ?></span>
                </h2>
                <p><?php esc_html_e( 'Add custom PHP code that will be executed during WordPress initialization.', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <textarea name="apg_settings[custom_php]" id="custom_php" rows="12" class="apg-code-editor" placeholder="// Add your custom PHP code here (without &lt;?php tags)&#10;// Example:&#10;add_filter('the_content', function($content) {&#10;    return $content;&#10;});"><?php echo esc_textarea( $options['custom_php'] ?? '' ); ?></textarea>
                <p class="apg-field-desc" style="margin-top: 10px;">
                    <span class="dashicons dashicons-warning" style="color: var(--apg-error);"></span>
                    <strong><?php esc_html_e( 'Warning:', 'author-post-guard' ); ?></strong>
                    <?php esc_html_e( 'Invalid PHP code can break your site. Test thoroughly before saving.', 'author-post-guard' ); ?>
                </p>
            </div>
        </div>

        <div class="apg-info-card">
            <span class="dashicons dashicons-info-outline"></span>
            <div>
                <strong><?php esc_html_e( 'Best Practices:', 'author-post-guard' ); ?></strong>
                <ul style="margin: 8px 0 0 20px; line-height: 1.8;">
                    <li><?php esc_html_e( 'Test your code in a staging environment first', 'author-post-guard' ); ?></li>
                    <li><?php esc_html_e( 'Keep snippets organized and well-commented', 'author-post-guard' ); ?></li>
                    <li><?php esc_html_e( 'Use WordPress hooks and filters properly', 'author-post-guard' ); ?></li>
                    <li><?php esc_html_e( 'Backup your site before adding complex PHP code', 'author-post-guard' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Render Notifications tab content
     *
     * @param array $options Current settings
     * @return void
     */
    private function render_notifications_tab( $options ) {
        ?>
        <!-- Notification Triggers -->
        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'Notification Triggers', 'author-post-guard' ); ?></h2>
                <p><?php esc_html_e( 'Choose when to send notifications to your configured channels.', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <div class="apg-triggers-grid">
                    <div class="apg-trigger-card">
                        <div class="apg-trigger-icon">
                            <span class="dashicons dashicons-welcome-write-blog"></span>
                        </div>
                        <div class="apg-trigger-info">
                            <label for="notify_post_published"><?php esc_html_e( 'Post Published', 'author-post-guard' ); ?></label>
                            <span><?php esc_html_e( 'Notify when a new post is published', 'author-post-guard' ); ?></span>
                        </div>
                        <label class="apg-toggle">
                            <input type="checkbox" name="apg_settings[notify_post_published]" id="notify_post_published" value="1" 
                                <?php checked( ! empty( $options['notify_post_published'] ) ); ?>>
                            <span class="apg-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="apg-trigger-card">
                        <div class="apg-trigger-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="apg-trigger-info">
                            <label for="notify_post_pending"><?php esc_html_e( 'Post Pending Review', 'author-post-guard' ); ?></label>
                            <span><?php esc_html_e( 'Notify when a post is submitted for review', 'author-post-guard' ); ?></span>
                        </div>
                        <label class="apg-toggle">
                            <input type="checkbox" name="apg_settings[notify_post_pending]" id="notify_post_pending" value="1" 
                                <?php checked( ! empty( $options['notify_post_pending'] ) ); ?>>
                            <span class="apg-toggle-slider"></span>
                        </label>
                    </div>

                    <div class="apg-trigger-card">
                        <div class="apg-trigger-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <div class="apg-trigger-info">
                            <label for="notify_user_registered"><?php esc_html_e( 'New User Registration', 'author-post-guard' ); ?></label>
                            <span><?php esc_html_e( 'Notify when a new user registers', 'author-post-guard' ); ?></span>
                        </div>
                        <label class="apg-toggle">
                            <input type="checkbox" name="apg_settings[notify_user_registered]" id="notify_user_registered" value="1" 
                                <?php checked( ! empty( $options['notify_user_registered'] ) ); ?>>
                            <span class="apg-toggle-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Discord Webhook -->
        <div class="apg-card">
            <div class="apg-card-header apg-card-header-inline">
                <div>
                    <h2>
                        <span class="apg-service-icon apg-service-discord"></span>
                        <?php esc_html_e( 'Discord Integration', 'author-post-guard' ); ?>
                    </h2>
                    <p><?php esc_html_e( 'Send notifications to a Discord channel via webhook.', 'author-post-guard' ); ?></p>
                </div>
                <button type="button" class="apg-btn apg-btn-sm apg-test-webhook" data-type="discord">
                    <?php esc_html_e( 'Test', 'author-post-guard' ); ?>
                </button>
            </div>
            <div class="apg-card-body">
                <div class="apg-field">
                    <label for="discord_webhook"><?php esc_html_e( 'Discord Webhook URL', 'author-post-guard' ); ?></label>
                    <input type="url" name="apg_settings[discord_webhook]" id="discord_webhook" 
                           value="<?php echo esc_attr( $options['discord_webhook'] ?? '' ); ?>" 
                           class="apg-input apg-input-full" 
                           placeholder="https://discord.com/api/webhooks/...">
                    <span class="apg-field-desc">
                        <?php esc_html_e( 'Get this from Discord: Server Settings → Integrations → Webhooks', 'author-post-guard' ); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Telegram Bot -->
        <div class="apg-card">
            <div class="apg-card-header apg-card-header-inline">
                <div>
                    <h2>
                        <span class="apg-service-icon apg-service-telegram"></span>
                        <?php esc_html_e( 'Telegram Integration', 'author-post-guard' ); ?>
                    </h2>
                    <p><?php esc_html_e( 'Send notifications to a Telegram chat or channel.', 'author-post-guard' ); ?></p>
                </div>
                <button type="button" class="apg-btn apg-btn-sm apg-test-webhook" data-type="telegram">
                    <?php esc_html_e( 'Test', 'author-post-guard' ); ?>
                </button>
            </div>
            <div class="apg-card-body">
                <div class="apg-field-grid">
                    <div class="apg-field">
                        <label for="telegram_bot_token"><?php esc_html_e( 'Bot Token', 'author-post-guard' ); ?></label>
                        <input type="text" name="apg_settings[telegram_bot_token]" id="telegram_bot_token" 
                               value="<?php echo esc_attr( $options['telegram_bot_token'] ?? '' ); ?>" 
                               class="apg-input apg-input-full" 
                               placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz">
                        <span class="apg-field-desc">
                            <?php esc_html_e( 'Get this from @BotFather on Telegram', 'author-post-guard' ); ?>
                        </span>
                    </div>
                    <div class="apg-field">
                        <label for="telegram_chat_id"><?php esc_html_e( 'Chat ID', 'author-post-guard' ); ?></label>
                        <input type="text" name="apg_settings[telegram_chat_id]" id="telegram_chat_id" 
                               value="<?php echo esc_attr( $options['telegram_chat_id'] ?? '' ); ?>" 
                               class="apg-input apg-input-full" 
                               placeholder="-1001234567890">
                        <span class="apg-field-desc">
                            <?php esc_html_e( 'Use @getidsbot to find your chat ID', 'author-post-guard' ); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Generic Webhook -->
        <div class="apg-card">
            <div class="apg-card-header apg-card-header-inline">
                <div>
                    <h2>
                        <span class="apg-service-icon apg-service-webhook"></span>
                        <?php esc_html_e( 'Generic Webhook', 'author-post-guard' ); ?>
                    </h2>
                    <p><?php esc_html_e( 'Send JSON payloads to any custom endpoint (Slack, Zapier, etc).', 'author-post-guard' ); ?></p>
                </div>
                <button type="button" class="apg-btn apg-btn-sm apg-test-webhook" data-type="generic">
                    <?php esc_html_e( 'Test', 'author-post-guard' ); ?>
                </button>
            </div>
            <div class="apg-card-body">
                <div class="apg-field">
                    <label for="generic_webhook_url"><?php esc_html_e( 'Webhook URL', 'author-post-guard' ); ?></label>
                    <input type="url" name="apg_settings[generic_webhook_url]" id="generic_webhook_url" 
                           value="<?php echo esc_attr( $options['generic_webhook_url'] ?? '' ); ?>" 
                           class="apg-input apg-input-full" 
                           placeholder="https://your-webhook-endpoint.com/notify">
                    <span class="apg-field-desc">
                        <?php esc_html_e( 'Receives JSON POST requests with event data.', 'author-post-guard' ); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render Update Settings tab content
     *
     * @param array $options Current settings
     * @return void
     */
    private function render_updates_tab( $options ) {
        ?>
        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'GitHub Auto-Update', 'author-post-guard' ); ?></h2>
                <p><?php esc_html_e( 'Automatically check for updates from your GitHub repository.', 'author-post-guard' ); ?></p>
            </div>
            <div class="apg-card-body">
                <!-- Enable Auto-Updates -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="auto_update_enabled"><?php esc_html_e( 'Enable Auto-Updates', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Receive update notifications when new releases are published on GitHub.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <label class="apg-toggle">
                                <input type="checkbox" name="apg_settings[auto_update_enabled]" id="auto_update_enabled" value="1" 
                                    <?php checked( ! empty( $options['auto_update_enabled'] ) ); ?>>
                                <span class="apg-toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- GitHub Repository -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="github_repo"><?php esc_html_e( 'GitHub Repository', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Format: owner/repository (default repository, cannot be changed)', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <input type="text" name="apg_settings[github_repo]" id="github_repo" 
                                   value="<?php echo esc_attr( $options['github_repo'] ?? 'TansiqLabs/author-post-guard' ); ?>" 
                                   class="apg-input" placeholder="TansiqLabs/author-post-guard" readonly style="background: var(--apg-gray-100); cursor: not-allowed;">
                        </div>
                    </div>
                </div>

                <!-- Access Token (for private repos) -->
                <div class="apg-field">
                    <div class="apg-field-row">
                        <div class="apg-field-info">
                            <label for="github_access_token"><?php esc_html_e( 'Access Token (Optional)', 'author-post-guard' ); ?></label>
                            <span class="apg-field-desc"><?php esc_html_e( 'Required only for private repositories. Generate a token with "repo" scope.', 'author-post-guard' ); ?></span>
                        </div>
                        <div class="apg-field-input">
                            <input type="password" name="apg_settings[github_access_token]" id="github_access_token" 
                                   value="<?php echo esc_attr( $options['github_access_token'] ?? '' ); ?>" 
                                   class="apg-input" placeholder="ghp_xxxxxxxxxxxx" autocomplete="off">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Status Card -->
        <div class="apg-card">
            <div class="apg-card-header">
                <h2><?php esc_html_e( 'Update Status', 'author-post-guard' ); ?></h2>
            </div>
            <div class="apg-card-body">
                <div class="apg-status-grid">
                    <div class="apg-status-item">
                        <span class="apg-status-label"><?php esc_html_e( 'Current Version:', 'author-post-guard' ); ?></span>
                        <span class="apg-status-value apg-badge apg-badge-info"><?php echo esc_html( APG_VERSION ); ?></span>
                    </div>
                    <div class="apg-status-item">
                        <span class="apg-status-label"><?php esc_html_e( 'Repository:', 'author-post-guard' ); ?></span>
                        <a href="https://github.com/<?php echo esc_attr( $options['github_repo'] ?? 'TansiqLabs/author-post-guard' ); ?>" 
                           target="_blank" class="apg-status-value apg-link">
                            <?php echo esc_html( $options['github_repo'] ?? 'TansiqLabs/author-post-guard' ); ?>
                            <span class="dashicons dashicons-external"></span>
                        </a>
                    </div>
                    <div class="apg-status-item">
                        <span class="apg-status-label"><?php esc_html_e( 'Last Check:', 'author-post-guard' ); ?></span>
                        <span class="apg-status-value">
                            <?php 
                            $last_check = get_option( 'apg_last_update_check', 0 );
                            if ( $last_check ) {
                                echo esc_html( human_time_diff( $last_check ) . ' ' . __( 'ago', 'author-post-guard' ) );
                            } else {
                                esc_html_e( 'Never', 'author-post-guard' );
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="apg-actions-inline">
                    <button type="button" class="apg-btn apg-btn-outline" id="apg-check-updates">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e( 'Check for Updates', 'author-post-guard' ); ?>
                    </button>
                </div>
            </div>
        </div>

        <div class="apg-info-card">
            <span class="dashicons dashicons-info-outline"></span>
            <div>
                <strong><?php esc_html_e( 'How it works:', 'author-post-guard' ); ?></strong>
                <?php esc_html_e( 'When you push a new release to your GitHub repository with a version tag (e.g., v1.1.0), WordPress will detect and offer the update in the Plugins page.', 'author-post-guard' ); ?>
            </div>
        </div>
        <?php
    }
}
