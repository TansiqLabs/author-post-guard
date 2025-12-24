<?php
/**
 * GitHub Updater for Author Post Guard
 *
 * Provides automatic update functionality by checking GitHub releases.
 * Integrates seamlessly with WordPress's native plugin update system.
 *
 * @package AuthorPostGuard
 * @subpackage Updater
 * @since 1.0.0
 * @author Tansiq Labs <support@tansiqlabs.com>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class APG_Updater
 *
 * Handles GitHub-based plugin updates
 */
class APG_Updater {

    /**
     * Plugin options
     *
     * @var array
     */
    private $options = array();

    /**
     * GitHub API base URL
     *
     * @var string
     */
    private $api_url = 'https://api.github.com';

    /**
     * Cached release data
     *
     * @var object|null
     */
    private $release_data = null;

    /**
     * Cache duration in seconds (6 hours)
     *
     * @var int
     */
    private $cache_duration = 21600;

    /**
     * Constructor - Set up update hooks
     */
    public function __construct() {
        $this->options = get_option( 'apg_settings', array() );

        // Only initialize if auto-updates are enabled
        if ( empty( $this->options['auto_update_enabled'] ) ) {
            return;
        }

        // Filter plugin update transient
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_updates' ) );

        // Filter plugin information for details popup
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );

        // After plugin update, clear cache
        add_action( 'upgrader_process_complete', array( $this, 'clear_cache' ), 10, 2 );

        // Add authentication for GitHub API requests
        add_filter( 'http_request_args', array( $this, 'add_github_auth' ), 10, 2 );

        // AJAX handler for manual update check
        add_action( 'wp_ajax_apg_check_updates', array( $this, 'ajax_check_updates' ) );
    }

    /**
     * Check GitHub for plugin updates
     *
     * @param object $transient Update transient object
     * @return object Modified transient
     */
    public function check_for_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        // Get release info from GitHub
        $release = $this->get_latest_release();

        if ( ! $release ) {
            return $transient;
        }

        // Compare versions
        $remote_version = $this->normalize_version( $release->tag_name );
        $local_version  = APG_VERSION;

        if ( version_compare( $remote_version, $local_version, '>' ) ) {
            // Find the zip asset
            $download_url = $this->get_download_url( $release );

            if ( $download_url ) {
                $transient->response[ APG_PLUGIN_BASENAME ] = (object) array(
                    'id'            => 'author-post-guard',
                    'slug'          => 'author-post-guard',
                    'plugin'        => APG_PLUGIN_BASENAME,
                    'new_version'   => $remote_version,
                    'url'           => $this->get_repo_url(),
                    'package'       => $download_url,
                    'icons'         => array(
                        'default' => APG_PLUGIN_URL . 'assets/logo.svg',
                    ),
                    'banners'       => array(),
                    'requires'      => '5.8',
                    'requires_php'  => '7.4',
                    'tested'        => get_bloginfo( 'version' ),
                    'compatibility' => new stdClass(),
                );
            }
        } else {
            // No update available - add to no_update list
            $transient->no_update[ APG_PLUGIN_BASENAME ] = (object) array(
                'id'            => 'author-post-guard',
                'slug'          => 'author-post-guard',
                'plugin'        => APG_PLUGIN_BASENAME,
                'new_version'   => $local_version,
                'url'           => $this->get_repo_url(),
                'package'       => '',
            );
        }

        // Update last check timestamp
        update_option( 'apg_last_update_check', time() );

        return $transient;
    }

    /**
     * Provide plugin information for the update details popup
     *
     * @param false|object|array $result Result object or array
     * @param string             $action API action
     * @param object             $args   Request arguments
     * @return false|object Plugin info or false
     */
    public function plugin_info( $result, $action, $args ) {
        // Only handle plugin_information requests for our plugin
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        if ( ! isset( $args->slug ) || 'author-post-guard' !== $args->slug ) {
            return $result;
        }

        $release = $this->get_latest_release();

        if ( ! $release ) {
            return $result;
        }

        $plugin_info = new stdClass();

        $plugin_info->name           = 'Author Post Guard';
        $plugin_info->slug           = 'author-post-guard';
        $plugin_info->version        = $this->normalize_version( $release->tag_name );
        $plugin_info->author         = '<a href="https://tansiqlabs.com">Tansiq Labs</a>';
        $plugin_info->author_profile = 'https://tansiqlabs.com';
        $plugin_info->homepage       = 'https://github.com/TansiqLabs/author-post-guard';
        $plugin_info->requires       = '5.8';
        $plugin_info->tested         = get_bloginfo( 'version' );
        $plugin_info->requires_php   = '7.4';
        $plugin_info->downloaded     = 0;
        $plugin_info->last_updated   = $release->published_at;
        $plugin_info->download_link  = $this->get_download_url( $release );

        // Parse release body as changelog
        $plugin_info->sections = array(
            'description' => $this->get_plugin_description(),
            'changelog'   => $this->parse_changelog( $release->body ),
            'support'     => $this->get_support_section(),
        );

        $plugin_info->banners = array(
            'low'  => APG_PLUGIN_URL . 'assets/banner-772x250.png',
            'high' => APG_PLUGIN_URL . 'assets/banner-1544x500.png',
        );

        return $plugin_info;
    }

    /**
     * Get latest release from GitHub API
     *
     * @param bool $force_refresh Force refresh from API
     * @return object|false Release data or false on failure
     */
    public function get_latest_release( $force_refresh = false ) {
        // Check cache first
        if ( ! $force_refresh && null !== $this->release_data ) {
            return $this->release_data;
        }

        // Check transient cache
        $cached = get_transient( 'apg_github_release' );
        if ( ! $force_refresh && false !== $cached ) {
            $this->release_data = $cached;
            return $this->release_data;
        }

        // Fetch from GitHub API
        $repo = $this->get_repo_slug();
        if ( empty( $repo ) ) {
            return false;
        }

        $api_url  = sprintf( '%s/repos/%s/releases/latest', $this->api_url, $repo );
        $response = $this->make_api_request( $api_url );

        if ( ! $response ) {
            return false;
        }

        // Cache the result
        $this->release_data = $response;
        set_transient( 'apg_github_release', $response, $this->cache_duration );

        return $this->release_data;
    }

    /**
     * Make request to GitHub API
     *
     * @param string $url API endpoint URL
     * @return object|false Response object or false on failure
     */
    private function make_api_request( $url ) {
        $args = array(
            'timeout'     => 15,
            'sslverify'   => true,
            'headers'     => array(
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'Author-Post-Guard/' . APG_VERSION,
            ),
        );

        // Add authorization header if token is set
        $token = $this->get_access_token();
        if ( ! empty( $token ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }

        $response = wp_remote_get( $url, $args );

        if ( is_wp_error( $response ) ) {
            $this->log_error( 'GitHub API request failed: ' . $response->get_error_message() );
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = wp_remote_retrieve_body( $response );

        if ( 200 !== $response_code ) {
            $this->log_error( sprintf( 'GitHub API returned status %d', $response_code ) );
            return false;
        }

        $data = json_decode( $body );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            $this->log_error( 'Failed to parse GitHub API response' );
            return false;
        }

        return $data;
    }

    /**
     * Get download URL from release
     *
     * @param object $release Release data
     * @return string|false Download URL or false
     */
    private function get_download_url( $release ) {
        // First, try to find a zip asset attached to the release
        if ( ! empty( $release->assets ) ) {
            foreach ( $release->assets as $asset ) {
                if ( substr( $asset->name, -4 ) === '.zip' ) {
                    return $asset->browser_download_url;
                }
            }
        }

        // Fallback to source code zipball
        if ( ! empty( $release->zipball_url ) ) {
            return $release->zipball_url;
        }

        return false;
    }

    /**
     * Add GitHub authentication to download requests
     *
     * @param array  $args HTTP request arguments
     * @param string $url  Request URL
     * @return array Modified arguments
     */
    public function add_github_auth( $args, $url ) {
        // Only add auth for GitHub API and release downloads
        if ( strpos( $url, 'github.com' ) === false && strpos( $url, 'api.github.com' ) === false ) {
            return $args;
        }

        $token = $this->get_access_token();
        if ( ! empty( $token ) ) {
            $args['headers']['Authorization'] = 'Bearer ' . $token;
        }

        return $args;
    }

    /**
     * Clear update cache
     *
     * @param WP_Upgrader $upgrader Upgrader instance
     * @param array       $options  Upgrade options
     * @return void
     */
    public function clear_cache( $upgrader, $options ) {
        if ( 'update' === $options['action'] && 'plugin' === $options['type'] ) {
            delete_transient( 'apg_github_release' );
            $this->release_data = null;
        }
    }

    /**
     * AJAX handler for manual update check
     *
     * @return void
     */
    public function ajax_check_updates() {
        if ( ! check_ajax_referer( 'apg_admin_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'author-post-guard' ) ) );
        }

        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'author-post-guard' ) ) );
        }

        // Force refresh from API
        $release = $this->get_latest_release( true );

        if ( ! $release ) {
            wp_send_json_error( array( 'message' => __( 'Could not connect to GitHub. Please try again later.', 'author-post-guard' ) ) );
        }

        $remote_version = $this->normalize_version( $release->tag_name );
        $local_version  = APG_VERSION;

        $has_update = version_compare( $remote_version, $local_version, '>' );

        $response = array(
            'current_version' => $local_version,
            'latest_version'  => $remote_version,
            'has_update'      => $has_update,
            'release_url'     => $release->html_url,
            'last_check'      => current_time( 'F j, Y g:i A' ),
        );

        if ( $has_update ) {
            $response['message'] = sprintf(
                __( 'Update available! Version %s is ready to install.', 'author-post-guard' ),
                $remote_version
            );
        } else {
            $response['message'] = __( 'You have the latest version installed.', 'author-post-guard' );
        }

        wp_send_json_success( $response );
    }

    /**
     * Normalize version string (remove 'v' prefix)
     *
     * @param string $version Version string
     * @return string Normalized version
     */
    private function normalize_version( $version ) {
        return ltrim( $version, 'vV' );
    }

    /**
     * Get repository slug from settings
     *
     * @return string Repository slug (owner/repo)
     */
    private function get_repo_slug() {
        return isset( $this->options['github_repo'] ) 
            ? $this->options['github_repo'] 
            : 'TansiqLabs/author-post-guard';
    }

    /**
     * Get repository URL
     *
     * @return string Full repository URL
     */
    private function get_repo_url() {
        return 'https://github.com/' . $this->get_repo_slug();
    }

    /**
     * Get GitHub access token
     *
     * @return string Access token or empty string
     */
    private function get_access_token() {
        return isset( $this->options['github_access_token'] ) 
            ? $this->options['github_access_token'] 
            : '';
    }

    /**
     * Get plugin description for info popup
     *
     * @return string HTML description
     */
    private function get_plugin_description() {
        return '
            <p><strong>Author Post Guard</strong> is a premium white-label solution for WordPress, developed by Tansiq Labs.</p>
            <h4>Features:</h4>
            <ul>
                <li>🎨 Complete white-labeling and custom branding</li>
                <li>📋 Role-based menu visibility control</li>
                <li>🔔 Webhook notifications (Discord, Telegram, Generic)</li>
                <li>🔄 Automatic updates from GitHub releases</li>
                <li>✨ Modern, SaaS-style admin interface</li>
            </ul>
            <p>For documentation and support, visit <a href="https://tansiqlabs.com">tansiqlabs.com</a></p>
        ';
    }

    /**
     * Get support section for info popup
     *
     * @return string HTML support info
     */
    private function get_support_section() {
        return '
            <h4>Need Help?</h4>
            <p>For support inquiries, please contact us:</p>
            <ul>
                <li>📧 Email: <a href="mailto:support@tansiqlabs.com">support@tansiqlabs.com</a></li>
                <li>🌐 Website: <a href="https://tansiqlabs.com">tansiqlabs.com</a></li>
                <li>🐙 GitHub: <a href="https://github.com/TansiqLabs/author-post-guard/issues">Report an Issue</a></li>
            </ul>
        ';
    }

    /**
     * Parse changelog from release body
     *
     * @param string $body Release body markdown
     * @return string HTML changelog
     */
    private function parse_changelog( $body ) {
        if ( empty( $body ) ) {
            return '<p>No changelog available for this release.</p>';
        }

        // Simple markdown conversion
        $html = esc_html( $body );
        
        // Convert headers
        $html = preg_replace( '/^### (.+)$/m', '<h4>$1</h4>', $html );
        $html = preg_replace( '/^## (.+)$/m', '<h3>$1</h3>', $html );
        
        // Convert bold
        $html = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html );
        
        // Convert lists
        $html = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $html );
        $html = preg_replace( '/(<li>.+<\/li>\n?)+/', '<ul>$0</ul>', $html );
        
        // Convert line breaks
        $html = nl2br( $html );

        return $html;
    }

    /**
     * Log error messages
     *
     * @param string $message Error message
     * @return void
     */
    private function log_error( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Author Post Guard Updater] ' . $message );
        }
    }

    /**
     * Get update status information
     *
     * @return array Status data
     */
    public function get_update_status() {
        $release = $this->get_latest_release();

        return array(
            'current_version' => APG_VERSION,
            'latest_version'  => $release ? $this->normalize_version( $release->tag_name ) : APG_VERSION,
            'has_update'      => $release ? version_compare( $this->normalize_version( $release->tag_name ), APG_VERSION, '>' ) : false,
            'last_check'      => get_option( 'apg_last_update_check', 0 ),
            'repo_url'        => $this->get_repo_url(),
        );
    }
}
