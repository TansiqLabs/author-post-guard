<?php
/**
 * Notifications Handler for Author Post Guard
 *
 * Manages webhook integrations for Discord, Telegram, and generic endpoints.
 * Triggers notifications based on WordPress events.
 *
 * @package AuthorPostGuard
 * @subpackage Notifications
 * @since 1.0.0
 * @author Tansiq Labs <support@tansiqlabs.com>
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class APG_Notifications
 *
 * Handles all webhook notification logic
 */
class APG_Notifications {

    /**
     * Plugin options
     *
     * @var array
     */
    private $options = array();

    /**
     * Site name for notifications
     *
     * @var string
     */
    private $site_name = '';

    /**
     * Constructor - Set up hooks
     */
    public function __construct() {
        $this->options   = get_option( 'apg_settings', array() );
        $this->site_name = get_bloginfo( 'name' );

        // Post status transition hooks
        add_action( 'transition_post_status', array( $this, 'handle_post_status_change' ), 10, 3 );

        // User registration hook
        add_action( 'user_register', array( $this, 'handle_user_registration' ), 10, 1 );
    }

    /**
     * Handle post status transitions
     *
     * @param string  $new_status New post status
     * @param string  $old_status Previous post status
     * @param WP_Post $post       Post object
     * @return void
     */
    public function handle_post_status_change( $new_status, $old_status, $post ) {
        // Avoid duplicate notifications and revision triggers
        if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
            return;
        }

        // Only handle post and page post types (can be extended)
        if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
            return;
        }

        // Post Published
        if ( 'publish' === $new_status && 'publish' !== $old_status ) {
            if ( ! empty( $this->options['notify_post_published'] ) ) {
                $this->notify_post_published( $post );
            }
        }

        // Post Pending Review
        if ( 'pending' === $new_status && 'pending' !== $old_status ) {
            if ( ! empty( $this->options['notify_post_pending'] ) ) {
                $this->notify_post_pending( $post );
            }
        }
    }

    /**
     * Handle new user registration
     *
     * @param int $user_id New user ID
     * @return void
     */
    public function handle_user_registration( $user_id ) {
        if ( empty( $this->options['notify_user_registered'] ) ) {
            return;
        }

        $user = get_userdata( $user_id );
        if ( ! $user ) {
            return;
        }

        $this->notify_user_registered( $user );
    }

    /**
     * Send notification when post is published
     *
     * @param WP_Post $post Post object
     * @return void
     */
    private function notify_post_published( $post ) {
        $author    = get_userdata( $post->post_author );
        $permalink = get_permalink( $post->ID );

        $message = sprintf(
            "📢 **New Post Published**\n\n**Title:** %s\n**Author:** %s\n**Type:** %s\n**Link:** %s",
            $post->post_title,
            $author ? $author->display_name : __( 'Unknown', 'author-post-guard' ),
            ucfirst( $post->post_type ),
            $permalink
        );

        $data = array(
            'event'     => 'post_published',
            'post_id'   => $post->ID,
            'title'     => $post->post_title,
            'author'    => $author ? $author->display_name : '',
            'post_type' => $post->post_type,
            'permalink' => $permalink,
            'timestamp' => current_time( 'mysql' ),
            'site'      => $this->site_name,
        );

        $this->dispatch_notifications( $message, $data, 'post_published' );
    }

    /**
     * Send notification when post is pending review
     *
     * @param WP_Post $post Post object
     * @return void
     */
    private function notify_post_pending( $post ) {
        $author   = get_userdata( $post->post_author );
        $edit_url = admin_url( 'post.php?post=' . $post->ID . '&action=edit' );

        $message = sprintf(
            "⏳ **Post Pending Review**\n\n**Title:** %s\n**Author:** %s\n**Type:** %s\n**Review:** %s",
            $post->post_title,
            $author ? $author->display_name : __( 'Unknown', 'author-post-guard' ),
            ucfirst( $post->post_type ),
            $edit_url
        );

        $data = array(
            'event'     => 'post_pending',
            'post_id'   => $post->ID,
            'title'     => $post->post_title,
            'author'    => $author ? $author->display_name : '',
            'post_type' => $post->post_type,
            'edit_url'  => $edit_url,
            'timestamp' => current_time( 'mysql' ),
            'site'      => $this->site_name,
        );

        $this->dispatch_notifications( $message, $data, 'post_pending' );
    }

    /**
     * Send notification when new user registers
     *
     * @param WP_User $user User object
     * @return void
     */
    private function notify_user_registered( $user ) {
        $profile_url = admin_url( 'user-edit.php?user_id=' . $user->ID );
        $roles       = implode( ', ', $user->roles );

        $message = sprintf(
            "👤 **New User Registered**\n\n**Username:** %s\n**Email:** %s\n**Role:** %s\n**Profile:** %s",
            $user->user_login,
            $user->user_email,
            ucfirst( $roles ),
            $profile_url
        );

        $data = array(
            'event'       => 'user_registered',
            'user_id'     => $user->ID,
            'username'    => $user->user_login,
            'email'       => $user->user_email,
            'display_name'=> $user->display_name,
            'roles'       => $user->roles,
            'profile_url' => $profile_url,
            'timestamp'   => current_time( 'mysql' ),
            'site'        => $this->site_name,
        );

        $this->dispatch_notifications( $message, $data, 'user_registered' );
    }

    /**
     * Dispatch notifications to all configured channels
     *
     * @param string $message Formatted message text
     * @param array  $data    Structured event data
     * @param string $event   Event type identifier
     * @return void
     */
    private function dispatch_notifications( $message, $data, $event ) {
        // Discord
        if ( ! empty( $this->options['discord_webhook'] ) ) {
            $this->send_to_discord( $message, $event );
        }

        // Telegram
        if ( ! empty( $this->options['telegram_bot_token'] ) && ! empty( $this->options['telegram_chat_id'] ) ) {
            $this->send_to_telegram( $message, $event );
        }

        // Generic Webhook
        if ( ! empty( $this->options['generic_webhook_url'] ) ) {
            $this->send_to_generic_webhook( $data, $event );
        }
    }

    /**
     * Send notification to Discord webhook
     *
     * @param string $message Message text
     * @param string $event   Event type
     * @return bool Success status
     */
    private function send_to_discord( $message, $event ) {
        $webhook_url = $this->options['discord_webhook'];

        // Determine embed color based on event
        $colors = array(
            'post_published'  => 5763719,  // Green
            'post_pending'    => 16776960, // Yellow
            'user_registered' => 3447003,  // Blue
            'test'            => 9807270,  // Gray
        );

        $color = isset( $colors[ $event ] ) ? $colors[ $event ] : 5793266;

        // Build Discord embed payload
        $payload = array(
            'username'   => 'Author Post Guard',
            'avatar_url' => APG_PLUGIN_URL . 'assets/logo.svg',
            'embeds'     => array(
                array(
                    'title'       => $this->get_event_title( $event ),
                    'description' => $this->convert_markdown_for_discord( $message ),
                    'color'       => $color,
                    'footer'      => array(
                        'text' => $this->site_name . ' • ' . current_time( 'F j, Y g:i A' ),
                    ),
                ),
            ),
        );

        return $this->make_webhook_request( $webhook_url, $payload );
    }

    /**
     * Send notification to Telegram
     *
     * @param string $message Message text
     * @param string $event   Event type
     * @return bool Success status
     */
    private function send_to_telegram( $message, $event ) {
        $bot_token = $this->options['telegram_bot_token'];
        $chat_id   = $this->options['telegram_chat_id'];
        
        $api_url = sprintf( 'https://api.telegram.org/bot%s/sendMessage', $bot_token );

        // Convert to Telegram markdown format
        $telegram_message = $this->convert_markdown_for_telegram( $message );
        $telegram_message .= "\n\n🌐 *" . esc_html( $this->site_name ) . "*";

        $payload = array(
            'chat_id'                  => $chat_id,
            'text'                     => $telegram_message,
            'parse_mode'               => 'Markdown',
            'disable_web_page_preview' => false,
        );

        return $this->make_webhook_request( $api_url, $payload );
    }

    /**
     * Send notification to generic webhook endpoint
     *
     * @param array  $data  Structured event data
     * @param string $event Event type
     * @return bool Success status
     */
    private function send_to_generic_webhook( $data, $event ) {
        $webhook_url = $this->options['generic_webhook_url'];

        // Add metadata to payload
        $payload = array(
            'source'    => 'author-post-guard',
            'version'   => APG_VERSION,
            'site_url'  => home_url(),
            'site_name' => $this->site_name,
            'event'     => $event,
            'data'      => $data,
        );

        return $this->make_webhook_request( $webhook_url, $payload );
    }

    /**
     * Make HTTP POST request to webhook URL
     *
     * @param string $url     Webhook URL
     * @param array  $payload Data to send
     * @return bool Success status
     */
    private function make_webhook_request( $url, $payload ) {
        $response = wp_remote_post( $url, array(
            'body'        => wp_json_encode( $payload ),
            'headers'     => array(
                'Content-Type' => 'application/json',
            ),
            'timeout'     => 15,
            'sslverify'   => true,
            'data_format' => 'body',
        ) );

        if ( is_wp_error( $response ) ) {
            $this->log_error( 'Webhook request failed: ' . $response->get_error_message() );
            return false;
        }

        $response_code = wp_remote_retrieve_response_code( $response );

        // Success codes: 200, 201, 204
        if ( $response_code >= 200 && $response_code < 300 ) {
            return true;
        }

        $this->log_error( sprintf( 'Webhook returned status %d: %s', $response_code, wp_remote_retrieve_body( $response ) ) );
        return false;
    }

    /**
     * Send test notification
     *
     * @param string $type Notification channel type (discord, telegram, generic)
     * @return bool Success status
     */
    public function send_test_notification( $type ) {
        // Refresh options
        $this->options = get_option( 'apg_settings', array() );

        $message = sprintf(
            "🧪 **Test Notification**\n\nThis is a test message from Author Post Guard.\n\n**Site:** %s\n**Time:** %s",
            $this->site_name,
            current_time( 'F j, Y g:i A' )
        );

        $data = array(
            'event'     => 'test',
            'message'   => 'Test notification from Author Post Guard',
            'timestamp' => current_time( 'mysql' ),
            'site'      => $this->site_name,
            'site_url'  => home_url(),
        );

        switch ( $type ) {
            case 'discord':
                return $this->send_to_discord( $message, 'test' );

            case 'telegram':
                return $this->send_to_telegram( $message, 'test' );

            case 'generic':
                return $this->send_to_generic_webhook( $data, 'test' );

            default:
                return false;
        }
    }

    /**
     * Get human-readable event title
     *
     * @param string $event Event type
     * @return string Event title
     */
    private function get_event_title( $event ) {
        $titles = array(
            'post_published'  => '📢 New Post Published',
            'post_pending'    => '⏳ Post Pending Review',
            'user_registered' => '👤 New User Registered',
            'test'            => '🧪 Test Notification',
        );

        return isset( $titles[ $event ] ) ? $titles[ $event ] : ucfirst( str_replace( '_', ' ', $event ) );
    }

    /**
     * Convert markdown for Discord format
     *
     * @param string $message Original message
     * @return string Converted message
     */
    private function convert_markdown_for_discord( $message ) {
        // Discord uses similar markdown, minor adjustments
        $message = str_replace( '**', '**', $message );
        return $message;
    }

    /**
     * Convert markdown for Telegram format
     *
     * @param string $message Original message
     * @return string Converted message
     */
    private function convert_markdown_for_telegram( $message ) {
        // Convert **bold** to *bold* for Telegram
        $message = preg_replace( '/\*\*(.+?)\*\*/', '*$1*', $message );
        return $message;
    }

    /**
     * Log error messages for debugging
     *
     * @param string $message Error message
     * @return void
     */
    private function log_error( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[Author Post Guard] ' . $message );
        }
    }

    /**
     * Get notification statistics
     *
     * @return array Stats data
     */
    public function get_stats() {
        $stats = get_option( 'apg_notification_stats', array(
            'total_sent'         => 0,
            'discord_sent'       => 0,
            'telegram_sent'      => 0,
            'generic_sent'       => 0,
            'last_notification'  => null,
        ) );

        return $stats;
    }

    /**
     * Increment notification counter
     *
     * @param string $channel Channel type
     * @return void
     */
    private function increment_stats( $channel ) {
        $stats = $this->get_stats();
        
        $stats['total_sent']++;
        $stats[ $channel . '_sent' ] = isset( $stats[ $channel . '_sent' ] ) 
            ? $stats[ $channel . '_sent' ] + 1 
            : 1;
        $stats['last_notification'] = current_time( 'mysql' );

        update_option( 'apg_notification_stats', $stats );
    }

    /**
     * Check if any notification channel is configured
     *
     * @return bool True if at least one channel is configured
     */
    public function has_configured_channels() {
        return ! empty( $this->options['discord_webhook'] )
            || ( ! empty( $this->options['telegram_bot_token'] ) && ! empty( $this->options['telegram_chat_id'] ) )
            || ! empty( $this->options['generic_webhook_url'] );
    }
}
