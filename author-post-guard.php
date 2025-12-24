<?php
/**
 * Plugin Name: Author Post Guard
 * Plugin URI: https://github.com/TansiqLabs/author-post-guard
 * Description: A premium white-label solution for WordPress branding, menu control, and advanced notifications by Tansiq Labs.
 * Version: 1.0.0
 * Author: Tansiq Labs
 * Author URI: https://tansiqlabs.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: author-post-guard
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package AuthorPostGuard
 * @author Tansiq Labs <support@tansiqlabs.com>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Constants
 */
define( 'APG_VERSION', '1.0.0' );
define( 'APG_PLUGIN_FILE', __FILE__ );
define( 'APG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'APG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'APG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 * 
 * Orchestrates all plugin functionality using singleton pattern
 * to ensure single instance throughout WordPress lifecycle.
 *
 * @since 1.0.0
 */
final class Author_Post_Guard {

    /**
     * Singleton instance
     *
     * @var Author_Post_Guard|null
     */
    private static $instance = null;

    /**
     * Settings handler instance
     *
     * @var APG_Settings|null
     */
    public $settings = null;

    /**
     * Notifications handler instance
     *
     * @var APG_Notifications|null
     */
    public $notifications = null;

    /**
     * Updater handler instance
     *
     * @var APG_Updater|null
     */
    public $updater = null;

    /**
     * Get singleton instance
     *
     * @return Author_Post_Guard
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize the plugin
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required class files
     *
     * @return void
     */
    private function load_dependencies() {
        require_once APG_PLUGIN_DIR . 'inc/class-settings.php';
        require_once APG_PLUGIN_DIR . 'inc/class-notifications.php';
        require_once APG_PLUGIN_DIR . 'inc/class-updater.php';
    }

    /**
     * Initialize WordPress hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Core initialization
        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );
        
        // Activation/Deactivation hooks
        register_activation_hook( APG_PLUGIN_FILE, array( $this, 'activate' ) );
        register_deactivation_hook( APG_PLUGIN_FILE, array( $this, 'deactivate' ) );

        // Admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Execute custom snippets
        add_action( 'admin_head', array( $this, 'output_custom_css' ) );
        add_action( 'admin_footer', array( $this, 'output_custom_js' ) );
        add_action( 'init', array( $this, 'execute_custom_php' ) );

        // White-label branding hooks
        add_action( 'login_enqueue_scripts', array( $this, 'login_page_branding' ) );
        add_filter( 'admin_footer_text', array( $this, 'custom_admin_footer' ) );
        add_filter( 'update_footer', array( $this, 'custom_update_footer' ), 99 );
        add_action( 'admin_bar_menu', array( $this, 'customize_admin_bar_logo' ), 11 );

        // Menu control hooks
        add_action( 'admin_menu', array( $this, 'control_admin_menu' ), 999 );
        add_action( 'admin_init', array( $this, 'control_admin_submenus' ), 999 );

        // Media library restrictions
        add_filter( 'ajax_query_attachments_args', array( $this, 'restrict_media_library' ) );
        add_filter( 'pre_get_posts', array( $this, 'restrict_media_library_list' ) );
    }

    /**
     * Initialize plugin components
     *
     * @return void
     */
    public function init_plugin() {
        // Load text domain for translations
        load_plugin_textdomain( 'author-post-guard', false, dirname( APG_PLUGIN_BASENAME ) . '/languages' );

        // Initialize core components
        $this->settings      = new APG_Settings();
        $this->notifications = new APG_Notifications();
        $this->updater       = new APG_Updater();
    }

    /**
     * Plugin activation routine
     *
     * @return void
     */
    public function activate() {
        // Set default options on first activation
        $defaults = array(
            'branding_enabled'      => true,
            'custom_footer_text'    => 'Powered by Tansiq Labs',
            'login_logo_enabled'    => true,
            'adminbar_logo_enabled' => true,
            'hidden_menus'          => array(),
            'discord_webhook'       => '',
            'telegram_bot_token'    => '',
            'telegram_chat_id'      => '',
            'generic_webhook_url'   => '',
            'notify_post_published' => true,
            'notify_post_pending'   => true,
            'notify_user_registered'=> true,
            'github_repo'           => 'TansiqLabs/author-post-guard',
            'auto_update_enabled'   => true,
        );

        $existing = get_option( 'apg_settings', array() );
        
        if ( empty( $existing ) ) {
            update_option( 'apg_settings', $defaults );
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation routine
     *
     * @return void
     */
    public function deactivate() {
        // Clean up scheduled events if any
        wp_clear_scheduled_hook( 'apg_daily_cleanup' );
        flush_rewrite_rules();
    }

    /**
     * Enqueue admin styles and scripts
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        // Load on all admin pages for branding
        wp_enqueue_style(
            'apg-admin-global',
            APG_PLUGIN_URL . 'assets/admin-style.css',
            array(),
            APG_VERSION
        );

        // Load full assets only on our settings page
        if ( 'toplevel_page_author-post-guard' === $hook ) {
            // Enqueue WordPress media uploader
            wp_enqueue_media();
            
            wp_enqueue_style(
                'apg-admin-style',
                APG_PLUGIN_URL . 'assets/admin-style.css',
                array(),
                APG_VERSION
            );

            wp_enqueue_script(
                'apg-admin-script',
                APG_PLUGIN_URL . 'assets/admin-script.js',
                array( 'jquery', 'media-upload', 'media-views' ),
                APG_VERSION,
                true
            );

            wp_localize_script( 'apg-admin-script', 'apgAdmin', array(
                'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
                'nonce'     => wp_create_nonce( 'apg_admin_nonce' ),
                'strings'   => array(
                    'saved'     => __( 'Settings saved successfully!', 'author-post-guard' ),
                    'error'     => __( 'An error occurred. Please try again.', 'author-post-guard' ),
                    'testing'   => __( 'Sending test notification...', 'author-post-guard' ),
                    'testSent'  => __( 'Test notification sent!', 'author-post-guard' ),
                ),
            ) );
        }
    }

    /**
     * Custom login page branding
     *
     * @return void
     */
    public function login_page_branding() {
        $options = get_option( 'apg_settings', array() );
        
        if ( empty( $options['login_logo_enabled'] ) ) {
            return;
        }

        $logo_url = ! empty( $options['custom_logo_url'] ) 
            ? $options['custom_logo_url'] 
            : APG_PLUGIN_URL . 'assets/logo.svg';
        ?>
        <style type="text/css">
            #login h1 a, .login h1 a {
                background-image: url('<?php echo esc_url( $logo_url ); ?>');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                width: 100%;
                height: 80px;
            }
            .login form {
                border-radius: 8px;
                box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            }
            .login #backtoblog a, .login #nav a {
                color: #6366f1;
            }
            .wp-core-ui .button-primary {
                background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
                border: none;
                border-radius: 6px;
                text-shadow: none;
                box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
            }
            .wp-core-ui .button-primary:hover {
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            }
        </style>
        <?php
    }

    /**
     * Custom admin footer text
     *
     * @param string $text Original footer text
     * @return string Modified footer text
     */
    public function custom_admin_footer( $text ) {
        $options = get_option( 'apg_settings', array() );
        
        if ( empty( $options['branding_enabled'] ) ) {
            return $text;
        }

        $custom_text = isset( $options['custom_footer_text'] ) 
            ? $options['custom_footer_text'] 
            : 'Powered by Tansiq Labs';

        return '<span id="apg-footer-text">' . esc_html( $custom_text ) . '</span>';
    }

    /**
     * Custom update footer text (removes WP version)
     *
     * @param string $text Original text
     * @return string Modified text
     */
    public function custom_update_footer( $text ) {
        $options = get_option( 'apg_settings', array() );
        
        if ( empty( $options['branding_enabled'] ) ) {
            return $text;
        }

        return '';
    }

    /**
     * Customize admin bar with Tansiq Labs logo
     *
     * @param WP_Admin_Bar $wp_admin_bar Admin bar instance
     * @return void
     */
    public function customize_admin_bar_logo( $wp_admin_bar ) {
        $options = get_option( 'apg_settings', array() );
        
        if ( empty( $options['adminbar_logo_enabled'] ) ) {
            return;
        }

        // Remove default WordPress logo
        $wp_admin_bar->remove_node( 'wp-logo' );

        // Add custom Tansiq Labs logo
        $wp_admin_bar->add_node( array(
            'id'    => 'tansiq-logo',
            'title' => '<span class="apg-adminbar-logo" aria-hidden="true"></span>',
            'href'  => admin_url(),
            'meta'  => array(
                'title' => 'Tansiq Labs',
            ),
        ) );
    }

    /**
     * Control admin menu visibility based on user roles
     *
     * @return void
     */
    public function control_admin_menu() {
        $options = get_option( 'apg_settings', array() );
        $hidden  = isset( $options['hidden_menus'] ) ? $options['hidden_menus'] : array();
        $user    = wp_get_current_user();
        
        // Don't hide menus from administrators unless specifically set
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return;
        }

        foreach ( $user->roles as $role ) {
            if ( isset( $hidden[ $role ] ) && is_array( $hidden[ $role ] ) ) {
                foreach ( $hidden[ $role ] as $menu_slug ) {
                    remove_menu_page( $menu_slug );
                }
            }
        }
    }

    /**
     * Restrict media library to user's own uploads
     *
     * @param array $query Query args
     * @return array Modified query
     */
    public function restrict_media_library( $query ) {
        $options = get_option( 'apg_settings', array() );
        
        if ( empty( $options['restrict_media_library'] ) ) {
            return $query;
        }

        $user = wp_get_current_user();
        
        // Don't restrict administrators
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return $query;
        }

        // Restrict to user's own uploads
        $query['author'] = get_current_user_id();
        
        return $query;
    }

    /**
     * Restrict media library list view
     *
     * @param WP_Query $query Query object
     * @return void
     */
    public function restrict_media_library_list( $query ) {
        global $pagenow;
        
        if ( 'upload.php' !== $pagenow || ! $query->is_main_query() ) {
            return;
        }

        $options = get_option( 'apg_settings', array() );
        
        if ( empty( $options['restrict_media_library'] ) ) {
            return;
        }

        $user = wp_get_current_user();
        
        // Don't restrict administrators
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return;
        }

        // Restrict to user's own uploads
        $query->set( 'author', get_current_user_id() );
    }

    /**
     * Output custom CSS in admin head
     *
     * @return void
     */
    public function output_custom_css() {
        $options = get_option( 'apg_settings', array() );
        $custom_css = isset( $options['custom_css'] ) ? trim( $options['custom_css'] ) : '';
        
        if ( ! empty( $custom_css ) ) {
            echo "\n<!-- Author Post Guard Custom CSS -->\n<style type=\"text/css\">\n";
            echo wp_strip_all_tags( $custom_css );
            echo "\n</style>\n";
        }
    }

    /**
     * Output custom JavaScript in admin footer
     *
     * @return void
     */
    public function output_custom_js() {
        $options = get_option( 'apg_settings', array() );
        $custom_js = isset( $options['custom_js'] ) ? trim( $options['custom_js'] ) : '';
        
        if ( ! empty( $custom_js ) ) {
            echo "\n<!-- Author Post Guard Custom JavaScript -->\n<script type=\"text/javascript\">\n";
            echo wp_strip_all_tags( $custom_js );
            echo "\n</script>\n";
        }
    }

    /**
     * Execute custom PHP code
     *
     * @return void
     */
    public function execute_custom_php() {
        // Only administrators can execute custom PHP
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $options = get_option( 'apg_settings', array() );
        $custom_php = isset( $options['custom_php'] ) ? trim( $options['custom_php'] ) : '';
        
        if ( ! empty( $custom_php ) ) {
            try {
                // Execute the PHP code
                eval( $custom_php );
            } catch ( Exception $e ) {
                // Log error if PHP code fails
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    error_log( '[Author Post Guard] Custom PHP Error: ' . $e->getMessage() );
                }
            }
        }
    }

    /**
     * Control submenu visibility
     *
     * @return void
     */
    public function control_admin_submenus() {
        $options = get_option( 'apg_settings', array() );
        $hidden  = isset( $options['hidden_submenus'] ) ? $options['hidden_submenus'] : array();
        $user    = wp_get_current_user();

        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return;
        }

        foreach ( $user->roles as $role ) {
            if ( isset( $hidden[ $role ] ) && is_array( $hidden[ $role ] ) ) {
                foreach ( $hidden[ $role ] as $submenu ) {
                    if ( isset( $submenu['parent'], $submenu['slug'] ) ) {
                        remove_submenu_page( $submenu['parent'], $submenu['slug'] );
                    }
                }
            }
        }
    }

    /**
     * Get plugin option value
     *
     * @param string $key Option key
     * @param mixed  $default Default value
     * @return mixed Option value
     */
    public static function get_option( $key, $default = '' ) {
        $options = get_option( 'apg_settings', array() );
        return isset( $options[ $key ] ) ? $options[ $key ] : $default;
    }

    /**
     * Update plugin option
     *
     * @param string $key Option key
     * @param mixed  $value Option value
     * @return bool Success status
     */
    public static function update_option( $key, $value ) {
        $options = get_option( 'apg_settings', array() );
        $options[ $key ] = $value;
        return update_option( 'apg_settings', $options );
    }
}

/**
 * Initialize the plugin
 *
 * @return Author_Post_Guard
 */
function author_post_guard() {
    return Author_Post_Guard::get_instance();
}

// Fire it up!
author_post_guard();
