<?php
/**
 * @wordpress-plugin
 * Plugin Name:       PayPal for WooCommerce
 * Plugin URI:        http://www.angelleye.com/product/paypal-for-woocommerce-plugin/
 * Description:       Easily enable PayPal Express Checkout, Website Payments Pro 3.0, and Payments Pro 2.0 (PayFlow).  Each option is available separately so you can enable them individually.
 * Version:           1.0.0
 * Author:            Angell EYE
 * Author URI:        http://www.angelleye.com/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/angelleye/paypal-woocommerce
 */
 
if (!defined('ABSPATH'))
{
    exit(); // Exit if accessed directly
}
global $woocommerce, $pp_settings;

/**
 * Get Settings
 */
$pp_settings = get_option( 'woocommerce_paypal_express_settings' );
if(!class_exists('AngellEYE_Gateway_Paypal')){
    class AngellEYE_Gateway_Paypal
    {
        /**
         * General class constructor where we'll setup our actions, hooks, and shortcodes.
         *
         */
        public function __construct()
        {
            add_filter('woocommerce_paypal_args', array($this,'ae_paypal_standard_additional_parameters'));
            add_action( 'plugins_loaded', array($this, 'init'));
            register_activation_hook( __FILE__, array($this, 'activate_woocommerce_paypal_express' ));
            add_action( 'wp_enqueue_scripts', array($this, 'woocommerce_paypal_express_init_styles'), 12 );
            add_action( 'admin_notices', array($this, 'wc_gateway_paypal_pro_ssl_check') );
            add_action( 'admin_init', array($this, 'set_ignore_tag'));
            add_filter( 'woocommerce_product_title' , array($this, 'woocommerce_product_title') );
        }

        function woocommerce_product_title($title){
            $title = str_replace(array("&#8211;", "&#8211"), array("-"), $title);
            return $title;
        }

        function set_ignore_tag(){
            global $current_user;
            $plugin = plugin_basename( __FILE__ );
            $plugin_data = get_plugin_data( __FILE__, false );

            if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                if( is_plugin_active($plugin) ) {
                    deactivate_plugins( $plugin );
                    wp_die( "<strong>".$plugin_data['Name']."</strong> requires <strong>WooCommerce</strong> plugin to work normally. Please active it or install it from <a href=\"http://wordpress.org/plugins/woocommerce/\" target=\"_blank\">here</a>.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
                }
            }
            $user_id = $current_user->ID;
            /* If user clicks to ignore the notice, add that to their user meta */
            $notices = array('ignore_pp_ssl', 'ignore_pp_sandbox', 'ignore_pp_woo');
            foreach ($notices as $notice)
                if ( isset($_GET[$notice]) && '0' == $_GET[$notice] ) {
                    add_user_meta($user_id, $notice, 'true', true);
                }
        }

        function wc_gateway_paypal_pro_ssl_check() {
            global $current_user, $pp_settings ;
            $user_id = $current_user->ID;

            $pp_pro = get_option('woocommerce_paypal_pro_settings');
            $pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');

            if (@$pp_pro['enabled']=='yes' || @$pp_payflow['enabled']=='yes') {
                // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
                if ( get_option('woocommerce_force_ssl_checkout')=='no' && ! class_exists( 'WordPressHTTPS' ) && !get_user_meta($user_id, 'ignore_pp_ssl') )
                    echo '<div class="error"><p>' . sprintf( __('WooCommerce PayPal Payments Pro requires that the <a href="%s">Force secure checkout</a> option is enabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - PayPal Pro will only work in test mode. | <a href="'.add_query_arg("ignore_pp_ssl",0).'">Hide Notice</a>', 'wc_paypal_pro'), admin_url('admin.php?page=woocommerce' ) ) . '</p></div>';
                if ((@$pp_pro['testmode']=='yes' || @$pp_payflow['testmode']=='yes' || @$pp_settings['testmode']=='yes') && !get_user_meta($user_id, 'ignore_pp_sandbox')) {
                    $testmodes = array();
                    if (@$pp_pro['enabled']=='yes' && @$pp_pro['testmode']=='yes') $testmodes[] = 'PayPal Pro';
                    if (@$pp_payflow['enabled']=='yes' && @$pp_payflow['testmode']=='yes') $testmodes[] = 'PayPal Pro PayFlow';
                    if (@$pp_settings['enabled']=='yes' && @$pp_settings['testmode']=='yes') $testmodes[] = 'PayPal Express';
                    $testmodes_str = implode(", ", $testmodes);
                    echo '<div class="error"><p>' . sprintf( __('WooCommerce PayPal Payments ( %s ) is currently running in Sandbox mode and will NOT process any actual payments. | <a href="'.add_query_arg("ignore_pp_sandbox",0).'">Hide Notice</a>', 'wc_paypal_pro'), $testmodes_str ) . '</p></div>';
                }
            } elseif (@$pp_settings['enabled']=='yes'){
                if (@$pp_settings['testmode']=='yes' && !get_user_meta($user_id, 'ignore_pp_sandbox')) {
                    echo '<div class="error"><p>' . __('WooCommerce PayPal Express is currently running in Sandbox mode and will NOT process any actual payments. | <a href="'.add_query_arg("ignore_pp_sandbox",0).'">Hide Notice</a>', 'wc_paypal_pro') . '</p></div>';
                }
            }

            if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && !get_user_meta($user_id, 'ignore_pp_woo')) {
                echo '<div class="error"><p>' . sprintf( __('WooCommerce PayPal Payments requires WooCommerce plugin to work normally. Please active it or install it from <a href="http://wordpress.org/plugins/woocommerce/" target="_blank">here</a>. | <a href="'.add_query_arg("ignore_pp_woo",0).'">Hide Notice</a>', 'wc_paypal_pro'), admin_url('admin.php?page=woocommerce' ) ) . '</p></div>';
            }
        }

        //init function
        function init(){
            if (!class_exists("WC_Payment_Gateway")) return;
            add_filter( 'woocommerce_payment_gateways', array($this, 'angelleye_add_paypal_pro_gateway'),1000 );
            remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_paypal_express_checkout_button', 12 );
            add_action( 'woocommerce_proceed_to_checkout', array( 'WC_Gateway_PayPal_Express_AngellEYE', 'woocommerce_paypal_express_checkout_button_angelleye'), 12 );
            add_action( 'woocommerce_before_cart', array( 'WC_Gateway_PayPal_Express_AngellEYE', 'woocommerce_before_cart'), 12 );
            remove_action( 'init', 'woocommerce_paypal_express_review_order_page') ;
            add_action( 'init', array($this, 'woocommerce_paypal_express_review_order_page_angelleye') );
            remove_shortcode( 'woocommerce_review_order');
            add_shortcode( 'woocommerce_review_order', array($this, 'get_woocommerce_review_order_angelleye' ));

            require_once('classes/wc-gateway-paypal-pro-payflow-angelleye.php');
            require_once('classes/wc-gateway-paypal-pro-angelleye.php');
            require_once('classes/wc-gateway-paypal-express-angelleye.php');
        }

        /**
         * woocommerce_paypal_express_init_styles function.
         *
         * @access public
         * @return void
         */
        function woocommerce_paypal_express_init_styles() {
            global $pp_settings;

            if ( ! is_admin() && is_cart() && isset( $pp_settings['hide_checkout_button'] ) && $pp_settings['hide_checkout_button'] == 'yes' )
                wp_enqueue_style( 'ppe_cart', plugins_url( 'assets/css/cart.css' , __FILE__ ) );

            if ( ! is_admin() && is_checkout() )
                wp_enqueue_style( 'ppe_checkout', plugins_url( 'assets/css/checkout.css' , __FILE__ ) );

            if (is_page( wc_get_page_id( 'review_order' ) )) {
                $assets_path          = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
                $frontend_script_path = $assets_path . 'js/frontend/';
                $suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_enqueue_script( 'wc-checkout', plugins_url( '/assets/js/checkout.js' , __FILE__ ), array( 'jquery' ), WC_VERSION, true );

                wp_localize_script( 'wc-checkout', 'wc_checkout_params', apply_filters( 'wc_checkout_params', array(
                    'ajax_url'                  => WC()->ajax_url(),
                    'ajax_loader_url'           => apply_filters( 'woocommerce_ajax_loader_url', $assets_path . 'images/ajax-loader@2x.gif' ),
                    'update_order_review_nonce' => wp_create_nonce( "update-order-review" ),
                    'apply_coupon_nonce'        => wp_create_nonce( "apply-coupon" ),
                    'option_guest_checkout'     => get_option( 'woocommerce_enable_guest_checkout' ),
                    'checkout_url'              => add_query_arg( 'action', 'woocommerce_checkout', WC()->ajax_url() ),
                    'is_checkout'               => 1
                ) ) );
            }
        }

        function activate_woocommerce_paypal_express() {
            if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) )) {
                deactivate_plugins( plugin_basename( __FILE__ ) );
            } else {
                global $woocommerce;
                //include_once $woocommerce->plugin_path() . '/admin/woocommerce-admin-install.php';

                // Pay page
                wc_create_page( esc_sql( _x( 'review-order', 'page_slug', 'woocommerce' ) ), 'woocommerce_review_order_page_id', __( 'Checkout &rarr; Review Order', 'woocommerce' ), '[woocommerce_review_order]', woocommerce_get_page_id( 'checkout' ) );
            }
        }

		/**
		 * Adds PayPal gateway options for Payments Pro and Express Checkout into the WooCommerce checkout settings.
		 *
		 */
        function angelleye_add_paypal_pro_gateway( $methods ) {
            foreach ($methods as $key=>$method){
                if (in_array($method, array('WC_Gateway_PayPal_Pro', 'WC_Gateway_PayPal_Pro_Payflow', 'WC_Gateway_PayPal_Express'))) {
                    unset($methods[$key]);
                    break;
                }
            }
            $methods[] = 'WC_Gateway_PayPal_Pro_AngellEYE';
            $methods[] = 'WC_Gateway_PayPal_Pro_Payflow_AngellEYE';
            $methods[] = 'WC_Gateway_PayPal_Express_AngellEYE';

            return $methods;
        }

        /**
         * Add additional parameters to the PayPal Standard checkout built into WooCommerce.
         *
         */
        public function ae_paypal_standard_additional_parameters($paypal_args)
        {
            $paypal_args['bn'] = 'AngellEYE_PHPClass';
            return $paypal_args;
        }

        /**
         * Add the gateway to woocommerce
         */
        function get_woocommerce_review_order_angelleye( $atts ) {
            global $woocommerce;
            return WC_Shortcodes::shortcode_wrapper(array($this,'woocommerce_review_order_angelleye'), $atts);
        }
        /**
         * Outputs the pay page - payment gateways can hook in here to show payment forms etc
         **/
        function woocommerce_review_order_angelleye() {
            global $woocommerce;
            //$woocommerce->nocache();
            wc_print_notices() ;
            echo "
			<script>
			jQuery(document).ready(function($) {
				// Inputs/selects which update totals instantly
                $('form.checkout').unbind( 'submit' );
			});
			</script>
			";
            echo '<form class="checkout" method="POST" action="' . add_query_arg( 'pp_action', 'payaction', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) . '">';
            $template = plugin_dir_path( __FILE__ ) . 'template/review-order.php';
            load_template( $template, false );
            do_action( 'woocommerce_ppe_checkout_order_review' );
            echo '<p><a class="button cancel" href="' . $woocommerce->cart->get_cart_url() . '">'.__('Cancel order', 'woothemes').'</a> ';
            echo '<input type="submit" class="button" value="' . __( 'Place Order','woothemes') . '" /></p>';
            echo '</form>';
        }
        function woocommerce_paypal_express_review_order_page_angelleye() {
            if ( ! empty( $_GET['pp_action'] ) && $_GET['pp_action'] == 'revieworder' ) {
                $woocommerce_ppe = new WC_Gateway_PayPal_Express_AngellEYE();
                $woocommerce_ppe->paypal_express_checkout();
            }
        }
    }
}
new AngellEYE_Gateway_Paypal();