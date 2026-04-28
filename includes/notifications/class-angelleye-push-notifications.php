<?php
/**
 * AngellEYE Push Notifications.
 *
 * Self-contained reusable class for fetching and displaying admin notifications
 * pushed from angelleye.com. Designed to be copy-pasted into any AngellEYE plugin.
 *
 * Usage in a plugin's bootstrap:
 *
 *     require_once __DIR__ . '/includes/notifications/class-angelleye-push-notifications.php';
 *     add_action( 'plugins_loaded', function () {
 *         ( new AngellEYE_Push_Notifications( array(
 *             'plugin_slug' => 'my-plugin-slug',
 *         ) ) )->register();
 *     }, 25 );
 *
 * Optional config:
 *   - api_url       (string)   Override the remote endpoint base.
 *   - timeout       (int)      HTTP timeout in seconds. Default 5.
 *   - success_ttl   (int)      Cache TTL on success. Default 12 hours.
 *   - failure_ttl   (int)      Cache TTL on failure. Default 4 hours.
 *   - applies_to    (callable) fn( $notification, $plugin_slug ): bool — filter which
 *                              notifications are shown to the current admin.
 *
 * @package angelleye-shared
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'AngellEYE_Push_Notifications' ) ) {

    class AngellEYE_Push_Notifications {

        const VERSION    = '1.0.0';
        const API_BASE   = 'https://www.angelleye.com/';
        const USER_AGENT = 'AngellEYE';

        private $plugin_slug;
        private $api_url;
        private $timeout;
        private $success_ttl;
        private $failure_ttl;
        private $applies_to;
        private $dismiss_action;

        private static $script_printed = false;

        public function __construct( array $args ) {
            if ( empty( $args['plugin_slug'] ) ) {
                _doing_it_wrong( __METHOD__, 'plugin_slug is required.', '1.0.0' );
                $args['plugin_slug'] = 'unknown';
            }
            $this->plugin_slug    = sanitize_key( $args['plugin_slug'] );
            $this->api_url        = isset( $args['api_url'] ) ? $args['api_url'] : self::API_BASE;
            $this->timeout        = isset( $args['timeout'] ) ? (int) $args['timeout'] : 5;
            $this->success_ttl    = isset( $args['success_ttl'] ) ? (int) $args['success_ttl'] : 12 * HOUR_IN_SECONDS;
            $this->failure_ttl    = isset( $args['failure_ttl'] ) ? (int) $args['failure_ttl'] : 4 * HOUR_IN_SECONDS;
            $this->applies_to     = isset( $args['applies_to'] ) && is_callable( $args['applies_to'] ) ? $args['applies_to'] : null;
            $this->dismiss_action = 'angelleye_dismiss_notice_' . $this->plugin_slug;
        }

        public function register() {
            add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
            add_action( 'wp_ajax_' . $this->dismiss_action, array( $this, 'handle_dismiss' ) );
            add_action( 'admin_print_footer_scripts', array( $this, 'print_dismiss_script' ) );
        }

        /**
         * Fetch push notifications, with success + failure transient caching.
         *
         * @return object|false
         */
        public function get_push_notifications() {
            $success_key = $this->transient_key( 'success' );
            $failure_key = $this->transient_key( 'failure' );

            $cached = get_transient( $success_key );
            if ( false !== $cached ) {
                return $cached;
            }
            if ( false !== get_transient( $failure_key ) ) {
                return false;
            }

            $endpoint = trailingslashit( $this->api_url ) . '?Wordpress_Plugin_Notification_Sender&action=angelleye_get_plugin_notification';

            $response = wp_remote_post( $endpoint, array(
                'method'      => 'POST',
                'timeout'     => $this->timeout,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => true,
                'headers'     => array( 'user-agent' => self::USER_AGENT ),
                'body'        => array( 'plugin_name' => $this->plugin_slug ),
                'cookies'     => array(),
                'sslverify'   => false,
            ) );

            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
                set_transient( $failure_key, time(), $this->failure_ttl );
                return false;
            }

            $body   = wp_remote_retrieve_body( $response );
            $parsed = $body ? json_decode( $body ) : false;

            if ( $parsed ) {
                set_transient( $success_key, $parsed, $this->success_ttl );
                delete_transient( $failure_key );
                return $parsed;
            }

            set_transient( $failure_key, time(), $this->failure_ttl );
            return false;
        }

        public function display_admin_notices() {
            if ( ! is_user_logged_in() ) {
                return;
            }

            $response = $this->get_push_notifications();
            if ( ! is_object( $response ) || empty( $response->data ) || ! is_array( $response->data ) ) {
                return;
            }

            $user_id = get_current_user_id();

            foreach ( $response->data as $notification ) {
                if ( empty( $notification->id ) ) {
                    continue;
                }
                if ( get_user_meta( $user_id, $notification->id, true ) ) {
                    continue;
                }
                if ( $this->applies_to && ! call_user_func( $this->applies_to, $notification, $this->plugin_slug ) ) {
                    continue;
                }
                $this->render_notification( $notification );
            }
        }

        public function render_notification( $n ) {
            $id          = isset( $n->id ) ? $n->id : '';
            $logo        = isset( $n->ans_company_logo ) ? $n->ans_company_logo : '';
            $title       = isset( $n->ans_message_title ) ? $n->ans_message_title : '';
            $description = isset( $n->ans_message_description ) ? $n->ans_message_description : '';
            $btn_url     = isset( $n->ans_button_url ) ? $n->ans_button_url : '';
            $btn_label   = isset( $n->ans_button_label ) ? $n->ans_button_label : '';
            ?>
            <div class="notice notice-success angelleye-notice" style="display:none;" id="<?php echo esc_attr( $id ); ?>" data-angelleye-action="<?php echo esc_attr( $this->dismiss_action ); ?>">
                <?php if ( $logo ) : ?>
                <div class="angelleye-notice-logo-push"><span><img alt="" src="<?php echo esc_url( $logo ); ?>" /></span></div>
                <?php endif; ?>
                <div class="angelleye-notice-message">
                    <h3><?php echo esc_html( $title ); ?></h3>
                    <div class="angelleye-notice-message-inner">
                        <p><?php echo esc_html( $description ); ?></p>
                        <?php if ( $btn_url && $btn_label ) : ?>
                        <div class="angelleye-notice-action">
                            <a target="_blank" href="<?php echo esc_url( $btn_url ); ?>" class="button button-primary"><?php echo esc_html( $btn_label ); ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="angelleye-notice-cta">
                    <button type="button" class="angelleye-notice-dismiss angelleye-dismiss-welcome" data-msg="<?php echo esc_attr( $id ); ?>" data-action="<?php echo esc_attr( $this->dismiss_action ); ?>">Dismiss</button>
                </div>
            </div>
            <?php
        }

        public function handle_dismiss() {
            if ( ! is_user_logged_in() ) {
                wp_send_json_error();
            }
            $message_id = isset( $_POST['data'] ) ? wc_clean( wp_unslash( $_POST['data'] ) ) : '';
            if ( '' === $message_id ) {
                wp_send_json_error();
            }
            add_user_meta( get_current_user_id(), $message_id, 'true', true );
            wp_send_json_success();
        }

        /**
         * Print one inline dismiss handler that works for every AngellEYE_Push_Notifications
         * instance on the page (each notice carries its own data-action).
         */
        public function print_dismiss_script() {
            if ( self::$script_printed ) {
                return;
            }
            self::$script_printed = true;
            ?>
            <script type="text/javascript">
            (function ($) {
                $(document).on('click', '.angelleye-notice-dismiss', function (e) {
                    e.preventDefault();
                    var $btn   = $(this);
                    var msg    = $btn.data('msg');
                    var action = $btn.data('action');
                    if (!action) { return; }
                    $btn.closest('.angelleye-notice').fadeOut(600, function () { $(this).remove(); });
                    $.post(ajaxurl, { action: action, data: msg });
                });
            })(jQuery);
            </script>
            <?php
        }

        private function transient_key( $type ) {
            return 'angelleye_push_notification_' . $type . '_' . md5( $this->plugin_slug );
        }
    }
}
