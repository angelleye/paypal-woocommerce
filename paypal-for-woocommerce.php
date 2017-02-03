<?php
/**
 * @wordpress-plugin
 * Plugin Name:       PayPal for WooCommerce
 * Plugin URI:        http://www.angelleye.com/product/paypal-for-woocommerce-plugin/
 * Description:       Easily enable PayPal Express Checkout, PayPal Pro, PayPal Advanced, PayPal REST, and PayPal Braintree.  Each option is available separately so you can enable them individually.
 * Version:           1.3.3
 * Author:            Angell EYE
 * Author URI:        http://www.angelleye.com/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       paypal-for-woocommerce
 * Domain Path:       /i18n/languages/
 * GitHub Plugin URI: https://github.com/angelleye/paypal-woocommerce
 *
 *************
 * Attribution
 *************
 * PayPal for WooCommerce is a derivative work of the code from WooThemes / SkyVerge,
 * which is licensed with GPLv3.  This code is also licensed under the terms
 * of the GNU Public License, version 3.
 */

/**
 * Exit if accessed directly.
 */
if (!defined('ABSPATH'))
{
    exit();
}
if (!defined('PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR')) {
    define('PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR', dirname(__FILE__));
}
/**
 * Set global parameters
 */
global $woocommerce, $pp_settings, $pp_pro, $pp_payflow, $wp_version;

/**
 * Get Settings
 */
$pp_settings = get_option( 'woocommerce_paypal_express_settings' );
if (substr(get_option("woocommerce_default_country"),0,2) != 'US') {
    $pp_settings['show_paypal_credit'] = 'no';
}
$pp_pro     = get_option('woocommerce_paypal_pro_settings');
$pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');


if(!class_exists('AngellEYE_Gateway_Paypal')){
    class AngellEYE_Gateway_Paypal
    {
    	
    	protected $plugin_screen_hook_suffix = null;
    	protected $plugin_slug = 'paypal-for-woocommerce';
    	
        /**
         * General class constructor where we'll setup our actions, hooks, and shortcodes.
         *
         */
        
        const VERSION_PFW = '1.3.3';
        public $customer_id = '';
        public function __construct()
        {
            require_once plugin_dir_path(__FILE__) . 'angelleye-includes/angelleye-utility.php';
            $plugin_admin = new AngellEYE_Utility($this->plugin_slug, self::VERSION_PFW);
            $woo_version = $this->wpbo_get_woo_version_number();
            add_filter( 'woocommerce_paypal_args', array($this,'ae_paypal_standard_additional_parameters'));
            if(version_compare($woo_version,'2.6','>=')) {
                add_action( 'plugins_loaded', array($this, 'init'));
            }
            register_activation_hook( __FILE__, array($this, 'activate_paypal_for_woocommerce' ));
            register_deactivation_hook( __FILE__,array($this,'deactivate_paypal_for_woocommerce' ));
            add_action( 'wp_enqueue_scripts', array($this, 'frontend_scripts'), 100 );
            add_action( 'admin_notices', array($this, 'admin_notices') );
            add_action( 'admin_init', array($this, 'set_ignore_tag'));
            add_filter( 'woocommerce_product_title' , array($this, 'woocommerce_product_title') );
            add_action( 'woocommerce_sections_checkout', array( $this, 'donate_message' ), 11 );
            if(version_compare($woo_version,'2.6','>=')) {
                add_action( 'parse_request', array($this, 'woocommerce_paypal_express_review_order_page_angelleye') , 11);
            }
            add_action( 'parse_request', array($this, 'wc_gateway_payment_token_api_parser') , 99);

            // http://stackoverflow.com/questions/22577727/problems-adding-action-links-to-wordpress-plugin
            $basename = plugin_basename(__FILE__);
            $prefix = is_network_admin() ? 'network_admin_' : '';
            add_filter("{$prefix}plugin_action_links_$basename",array($this,'plugin_action_links'),10,4);
            if(version_compare($woo_version,'2.6','>=')) {
                add_action( 'woocommerce_after_add_to_cart_button', array($this, 'buy_now_button'));
            }
            if(version_compare($woo_version,'2.6','>=')) {
                add_action( 'woocommerce_after_mini_cart', array($this, 'mini_cart_button'));            
            }
            add_action( 'woocommerce_add_to_cart_redirect', array($this, 'add_to_cart_redirect'));
            add_action( 'admin_enqueue_scripts', array( $this , 'admin_scripts' ) );
            add_action( 'admin_print_styles', array( $this , 'admin_styles' ) );
            add_action( 'woocommerce_cart_calculate_fees', array($this, 'woocommerce_custom_surcharge') );
            add_action( 'admin_init', array( $this, 'angelleye_check_version' ), 5 );
            add_filter( 'woocommerce_add_to_cart_redirect', array($this, 'angelleye_woocommerce_add_to_cart_redirect'), 1000, 1);
            add_action( 'admin_menu', array( $this, 'angelleye_admin_menu_own' ) );
            add_action( 'product_type_options', array( $this, 'angelleye_product_type_options_own' ), 10, 1);
            add_action( 'woocommerce_process_product_meta', array( $this, 'angelleye_woocommerce_process_product_meta_own' ), 10, 1 );
            add_filter( 'woocommerce_add_to_cart_sold_individually_quantity', array( $this, 'angelleye_woocommerce_add_to_cart_sold_individually_quantity' ), 10, 5 );
            add_action('admin_enqueue_scripts', array( $this, 'angelleye_woocommerce_admin_enqueue_scripts' ) );
            add_action( 'wp_ajax_pfw_ed_shipping_bulk_tool', array( $this, 'angelleye_woocommerce_pfw_ed_shipping_bulk_tool' ) );
            add_action( 'woocommerce_checkout_process', array( $this, 'angelleye_paypal_express_checkout_process_checkout_fields' ) );
            add_filter('body_class', array($this, 'add_body_classes'));
            add_action('http_api_curl', array($this, 'http_api_curl_ex_add_curl_parameter'), 10, 3);
            add_filter( "pre_option_woocommerce_paypal_express_settings", array($this, 'angelleye_express_checkout_decrypt_gateway_api'), 10, 1);
            add_filter( "pre_option_woocommerce_paypal_advanced_settings", array($this, 'angelleye_paypal_advanced_decrypt_gateway_api'), 10, 1);
            add_filter( "pre_option_woocommerce_paypal_credit_card_rest_settings", array($this, 'angelleye_paypal_credit_card_rest_decrypt_gateway_api'), 10, 1);
            add_filter( "pre_option_woocommerce_paypal_pro_settings", array($this, 'angelleye_paypal_pro_decrypt_gateway_api'), 10, 1);
            add_filter( "pre_option_woocommerce_paypal_pro_payflow_settings", array($this, 'angelleye_paypal_pro_payflow_decrypt_gateway_api'), 10, 1);
            add_filter( "pre_option_woocommerce_braintree_settings", array($this, 'angelleye_braintree_decrypt_gateway_api'), 10, 1);
            $this->customer_id;
        }

        /*
         * Adds class name to HTML body to enable easy conditional CSS styling
         * @access public
         * @param array $classes
         * @return array
         */
        public function add_body_classes($classes) {
          global $pp_settings;
          if(@$pp_settings['enabled'] == 'yes')
            $classes[] = 'has_paypal_express_checkout';
          return $classes;
        }

        /**
         * Get WooCommerce Version Number
         * http://wpbackoffice.com/get-current-woocommerce-version-number/
         */
        function wpbo_get_woo_version_number()
        {
            // If get_plugins() isn't available, require it
            if ( ! function_exists( 'get_plugins' ) )
            {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }

            // Create the plugins folder and file variables
            $plugin_folder = get_plugins( '/' . 'woocommerce' );
            $plugin_file = 'woocommerce.php';

            // If the plugin version number is set, return it
            if ( isset( $plugin_folder[$plugin_file]['Version'] ) )
            {
                return $plugin_folder[$plugin_file]['Version'];
            }
            else
            {
                // Otherwise return null
                return NULL;
            }
        }

        /**
         * Add gift amount to cart
         * @param $cart
         */
        function woocommerce_custom_surcharge($cart){
            if (isset($_REQUEST['pp_action']) && ($_REQUEST['pp_action']=='revieworder' || $_REQUEST['pp_action']=='payaction') && WC()->session->giftwrapamount){
                $cart->add_fee( __('Gift Wrap', 'paypal-for-woocommerce'), WC()->session->giftwrapamount );
            }
        }

        /**
         * Return the plugin action links.  This will only be called if the plugin
         * is active.
         *
         * @since 1.0.6
         * @param array $actions associative array of action names to anchor tags
         * @return array associative array of plugin action links
         */
        public function plugin_action_links($actions, $plugin_file, $plugin_data, $context)
        {
            $custom_actions = array(
                //'configure' => sprintf( '<a href="%s">%s</a>', admin_url( 'admin.php?page=wc-settings&tab=checkout' ), __( 'Configure', 'paypal-for-woocommerce' ) ),
                'docs'      => sprintf( '<a href="%s" target="_blank">%s</a>', 'http://www.angelleye.com/category/docs/paypal-for-woocommerce/?utm_source=paypal_for_woocommerce&utm_medium=docs_link&utm_campaign=paypal_for_woocommerce', __( 'Docs', 'paypal-for-woocommerce' ) ),
                'support'   => sprintf( '<a href="%s" target="_blank">%s</a>', 'http://wordpress.org/support/plugin/paypal-for-woocommerce/', __( 'Support', 'paypal-for-woocommerce' ) ),
                'review'    => sprintf( '<a href="%s" target="_blank">%s</a>', 'http://wordpress.org/support/view/plugin-reviews/paypal-for-woocommerce', __( 'Write a Review', 'paypal-for-woocommerce' ) ),
            );

            // add the links to the front of the actions list
            return array_merge( $custom_actions, $actions );
        }

        function woocommerce_product_title($title){
            $title = str_replace(array("&#8211;", "&#8211"), array("-"), $title);
            return $title;
        }

        function set_ignore_tag(){
            global $current_user;
            $plugin = plugin_basename( __FILE__ );
            $plugin_data = get_plugin_data( __FILE__, false );

            if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && !is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
                if(!in_array(@$_GET['action'],array('activate-plugin', 'upgrade-plugin','activate','do-plugin-upgrade')) && is_plugin_active($plugin) ) {
                    deactivate_plugins( $plugin );
                    wp_die( "<strong>".$plugin_data['Name']."</strong> requires <strong>WooCommerce</strong> plugin to work normally. Please activate it or install it from <a href=\"http://wordpress.org/plugins/woocommerce/\" target=\"_blank\">here</a>.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
                }
            }
            
            $user_id = $current_user->ID;
            
            /* If user clicks to ignore the notice, add that to their user meta */
            $notices = array('ignore_pp_ssl', 'ignore_pp_sandbox', 'ignore_pp_woo', 'ignore_pp_check', 'ignore_pp_donate', 'ignore_paypal_plus_move_notice');
            
            foreach ($notices as $notice) {
                if ( isset($_GET[$notice]) && '0' == $_GET[$notice] ) {
                    add_user_meta($user_id, $notice, 'true', true);
                    $set_ignore_tag_url =  remove_query_arg( $notice );
                    wp_redirect($set_ignore_tag_url);
                }
            }
        }

        function admin_notices() {
            global $current_user, $pp_settings ;
            $user_id = $current_user->ID;

            $pp_pro = get_option('woocommerce_paypal_pro_settings');
            $pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');
            $pp_standard = get_option('woocommerce_paypal_settings');

            do_action( 'angelleye_admin_notices', $pp_pro, $pp_payflow, $pp_standard );

            if (@$pp_pro['enabled']=='yes' || @$pp_payflow['enabled']=='yes') {
                // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
                if ( get_option('woocommerce_force_ssl_checkout')=='no' && ! class_exists( 'WordPressHTTPS' ) && !get_user_meta($user_id, 'ignore_pp_ssl') )
                    echo '<div class="error"><p>' . sprintf(__('WooCommerce PayPal Payments Pro requires that the %sForce secure checkout%s option is enabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - PayPal Pro will only work in test mode. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), '<a href="'.admin_url('admin.php?page=woocommerce').'">', "</a>", '"'.esc_url(add_query_arg("ignore_pp_ssl",0)).'"', __("Hide this notice", 'paypal-for-woocommerce'))  . '</p></div>';
                if ((@$pp_pro['testmode']=='yes' || @$pp_payflow['testmode']=='yes' || @$pp_settings['testmode']=='yes') && !get_user_meta($user_id, 'ignore_pp_sandbox')) {
                    $testmodes = array();
                    if (@$pp_pro['enabled']=='yes' && @$pp_pro['testmode']=='yes') $testmodes[] = 'PayPal Pro';
                    if (@$pp_payflow['enabled']=='yes' && @$pp_payflow['testmode']=='yes') $testmodes[] = 'PayPal Pro PayFlow';
                    if (@$pp_settings['enabled']=='yes' && @$pp_settings['testmode']=='yes') $testmodes[] = 'PayPal Express';
                    $testmodes_str = implode(", ", $testmodes);
                    echo '<div class="error"><p>' . sprintf(__('WooCommerce PayPal Payments ( %s ) is currently running in Sandbox mode and will NOT process any actual payments. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), $testmodes_str, '"'.esc_url(add_query_arg("ignore_pp_sandbox",0)).'"',  __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
                }
            } elseif (@$pp_settings['enabled']=='yes'){
                if (@$pp_settings['testmode']=='yes' && !get_user_meta($user_id, 'ignore_pp_sandbox')) {
                    echo '<div class="error"><p>' . sprintf(__('WooCommerce PayPal Express is currently running in Sandbox mode and will NOT process any actual payments. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), '"'.esc_url(add_query_arg("ignore_pp_sandbox",0)).'"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
                }
            }
            if(@$pp_settings['enabled']=='yes' && @$pp_standard['enabled']=='yes' && !get_user_meta($user_id, 'ignore_pp_check')){
                echo '<div class="error"><p>' . sprintf(__('You currently have both PayPal (standard) and Express Checkout enabled.  It is recommended that you disable the standard PayPal from <a href="'.get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal">the settings page</a> when using Express Checkout. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), '"'.esc_url(add_query_arg("ignore_pp_check",0)).'"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
            }

            if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && !get_user_meta($user_id, 'ignore_pp_woo') && !is_plugin_active_for_network( 'woocommerce/woocommerce.php' )) {
                echo '<div class="error"><p>' . sprintf( __("WooCommerce PayPal Payments requires WooCommerce plugin to work normally. Please activate it or install it from <a href='http://wordpress.org/plugins/woocommerce/' target='_blank'>here</a>. | <a href=%s>%s</a>", 'paypal-for-woocommerce'), '"'.esc_url(add_query_arg("ignore_pp_woo",0)).'"', __("Hide this notice", 'paypal-for-woocommerce') ) . '</p></div>';
            }
            
            $screen = get_current_screen();
            
            if( $screen->id == "settings_page_paypal-for-woocommerce" ) {
                $processed = (isset($_GET['processed']) ) ? $_GET['processed'] : FALSE;
                if($processed) {
                    echo '<div class="updated">';
                    echo '<p>'. sprintf( __('Action completed; %s records processed. ', 'paypal-for-woocommerce'), ($processed == 'zero') ? 0 : $processed);
                    echo '</div>';
                }
            }
            
            $this->angelleye_paypal_plus_notice($user_id);
        }

        //init function
        function init(){
            global $pp_settings;
            if (!class_exists("WC_Payment_Gateway")) return;
            load_plugin_textdomain( 'paypal-for-woocommerce', FALSE, basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
            
            /**
             * Check current WooCommerce version to ensure compatibility.
             */
            
            $woo_version = $this->wpbo_get_woo_version_number();
            if(version_compare($woo_version,'2.6','<')) {
                add_action( 'admin_notices', array($this, 'woo_compatibility_notice') );
            } else {
                add_filter( 'woocommerce_payment_gateways', array($this, 'angelleye_add_paypal_pro_gateway'),1000 );
            }
            
            //remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_paypal_express_checkout_button', 12 );
            
            if(version_compare($woo_version,'2.6','>=')) {
                if(AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
                    if( isset($pp_settings['button_position']) && ($pp_settings['button_position'] == 'bottom' || $pp_settings['button_position'] == 'both')){
                        add_action( 'woocommerce_proceed_to_checkout', array( 'WC_Gateway_PayPal_Express_AngellEYE', 'woocommerce_paypal_express_checkout_button_angelleye'), 22 );
                    }
                }
            }
            
            if(version_compare($woo_version,'2.6','>=')) {
                add_action( 'woocommerce_before_cart', array( 'WC_Gateway_PayPal_Express_AngellEYE', 'woocommerce_before_cart'), 12 );
            }
            remove_action( 'init', 'woocommerce_paypal_express_review_order_page') ;
            remove_shortcode( 'woocommerce_review_order');
            add_shortcode( 'woocommerce_review_order', array($this, 'get_woocommerce_review_order_angelleye' ));

            require_once('classes/wc-gateway-paypal-pro-payflow-angelleye.php');
            require_once('classes/wc-gateway-paypal-pro-angelleye.php');
            require_once('classes/wc-gateway-braintree-angelleye.php');
            require_once('classes/wc-gateway-paypal-express-angelleye.php');
            require_once('classes/wc-gateway-paypal-advanced-angelleye.php');
            

            if (version_compare(phpversion(), '5.3.0', '>=')) {
                require_once('classes/wc-gateway-paypal-credit-cards-rest-angelleye.php');
            }
        }

        /**
         * Admin Script
         */
        function admin_scripts()
        {
            $dir = plugin_dir_path( __FILE__ );
            wp_enqueue_media();
            wp_enqueue_script( 'jquery');
            // Localize the script with new data
            wp_register_script( 'angelleye_admin', plugins_url( '/assets/js/angelleye-admin.js' , __FILE__ ), array( 'jquery' ));
            $translation_array = array(
                'is_ssl' => AngellEYE_Gateway_Paypal::is_ssl()? "yes":"no",
                'choose_image' => __('Choose Image', 'paypal-for-woocommerce'),
                'shop_based_us' => (substr(get_option("woocommerce_default_country"),0,2) == 'US')? "yes":"no"

            );
            wp_localize_script( 'angelleye_admin', 'angelleye_admin', $translation_array );
            wp_enqueue_script( 'angelleye_admin');
        }

        /**
         * Admin Style
         */
        function admin_styles()
        {
            wp_enqueue_style('thickbox');

        }

        /**
         * frontend_scripts function.
         *
         * @access public
         * @return void
         */
        function frontend_scripts() {
            global $pp_settings;
            wp_register_script( 'angelleye_frontend', plugins_url( '/assets/js/angelleye-frontend.js' , __FILE__ ), array( 'jquery' ), WC_VERSION, true );
            $translation_array = array(
                'is_product' => is_product()? "yes" : "no",
                'is_cart' => is_cart()? "yes":"no",
                'is_checkout' => is_checkout()? "yes":"no",
                'three_digits'  => __('3 digits usually found on the signature strip.', 'paypal-for-woocommerce'),
                'four_digits'  => __('4 digits usually found on the front of the card.', 'paypal-for-woocommerce')
            );
            wp_localize_script( 'angelleye_frontend', 'angelleye_frontend', $translation_array );
            wp_enqueue_script('angelleye_frontend');

            if ( ! is_admin() && is_cart()){
                wp_enqueue_style( 'ppe_cart', plugins_url( 'assets/css/cart.css' , __FILE__ ) );
            }

            if ( ! is_admin() && is_checkout() ) {
                wp_enqueue_style( 'ppe_checkout', plugins_url( 'assets/css/checkout.css' , __FILE__ ) );
            }
            if ( ! is_admin() && is_single() && @$pp_settings['enabled']=='yes' && @$pp_settings['show_on_product_page']=='yes'){
                wp_enqueue_style( 'ppe_single', plugins_url( 'assets/css/single.css' , __FILE__ ) );
                wp_enqueue_script('angelleye_button');
            }

            if (is_page( wc_get_page_id( 'review_order' ) )) {
                $assets_path          = str_replace( array( 'http:', 'https:' ), '', WC()->plugin_url() ) . '/assets/';
                $frontend_script_path = $assets_path . 'js/frontend/';
                $suffix               = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
                wp_enqueue_script( 'wc-checkout', plugins_url( '/assets/js/checkout.js' , __FILE__ ), array( 'jquery' ), WC_VERSION, true );

                wp_localize_script( 'wc-checkout', 'wc_checkout_params', apply_filters( 'wc_checkout_params', array(
                    'ajax_url'                  => WC()->ajax_url(),
                    'update_order_review_nonce' => wp_create_nonce( "update-order-review" ),
                    'apply_coupon_nonce'        => wp_create_nonce( "apply-coupon" ),
                    'option_guest_checkout'     => get_option( 'woocommerce_enable_guest_checkout' ),
                    'checkout_url'              => esc_url(add_query_arg( 'action', 'woocommerce_checkout', WC()->ajax_url() )),
                    'is_checkout'               => 1
                ) ) );
            }
          
        }

        /**
         * Run when plugin is activated
         */
        function activate_paypal_for_woocommerce()
        {
            // If WooCommerce is not enabled, deactivate plugin.
            if(!in_array( 'woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins'))) && !is_plugin_active_for_network( 'woocommerce/woocommerce.php' ))
            {
                deactivate_plugins(plugin_basename(__FILE__));
            }
            else
            {
                global $woocommerce;
                
                // Create review page for Express Checkout
                wc_create_page(esc_sql(_x('review-order','page_slug','paypal-for-woocommerce')),'woocommerce_review_order_page_id',__('Checkout &rarr; Review Order','paypal-for-woocommerce'),'[woocommerce_review_order]',wc_get_page_id('checkout'));

                // Log activation in Angell EYE database via web service.
                // @todo Need to turn this into an option people can enable by request.
                //$log_url = $_SERVER['HTTP_HOST'];
                //$log_plugin_id = 1;
                //$log_activation_status = 1;
                //wp_remote_request('http://www.angelleye.com/web-services/wordpress/update-plugin-status.php?url='.$log_url.'&plugin_id='.$log_plugin_id.'&activation_status='.$log_activation_status);
            }
        }

        /**
         * Run when plugin is deactivated.
         */
        function deactivate_paypal_for_woocommerce()
        {
            // Log activation in Angell EYE database via web service.
            // @todo Need to turn this into an option people can enable.
            //$log_url = $_SERVER['HTTP_HOST'];
            //$log_plugin_id = 1;
            //$log_activation_status = 0;
            //wp_remote_request('http://www.angelleye.com/web-services/wordpress/update-plugin-status.php?url='.$log_url.'&plugin_id='.$log_plugin_id.'&activation_status='.$log_activation_status);
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
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                $methods[] = 'WC_Gateway_Braintree_AngellEYE';
            }
            $methods[] = 'WC_Gateway_PayPal_Advanced_AngellEYE';
            if (version_compare(phpversion(), '5.3.0', '>=')) {
                $methods[] = 'WC_Gateway_PayPal_Credit_Card_Rest_AngellEYE';
            }
            return $methods;
        }

        /**
         * Add additional parameters to the PayPal Standard checkout built into WooCommerce.
         *
         */
        public function ae_paypal_standard_additional_parameters($paypal_args)
        {
            $paypal_args['bn'] = 'AngellEYE_SP_WooCommerce';
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

            echo "
			<script>
			jQuery(document).ready(function($) {
				// Inputs/selects which update totals instantly
                $('form.checkout').unbind( 'submit' );
			});
			</script>
			";
            //echo '<form class="checkout" method="POST" action="' . add_query_arg( 'pp_action', 'payaction', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) . '">';
            $template = plugin_dir_path( __FILE__ ) . 'template/';

            //Allow override in theme: <theme_name>/woocommerce/paypal-paypal-review-order.php
            wc_get_template('paypal-review-order.php', array(), '', $template);

            do_action( 'woocommerce_ppe_checkout_order_review' );
            //echo '<p><a class="button cancel" href="' . $woocommerce->cart->get_cart_url() . '">'.__('Cancel order', 'paypal-for-woocommerce').'</a> ';
            //echo '<input type="submit" class="button" value="' . __( 'Place Order','paypal-for-woocommerce') . '" /></p>';
            //echo '</form>';
        }

        /**
         * Review page for PayPal Express Checkout
         */
        function woocommerce_paypal_express_review_order_page_angelleye() {
            if ( ! empty( $_GET['pp_action'] ) && ($_GET['pp_action'] == 'revieworder' ||  $_GET['pp_action'] == 'payaction') ) {
                $woocommerce_ppe = new WC_Gateway_PayPal_Express_AngellEYE();
                $woocommerce_ppe->paypal_express_checkout();
            }
        }

        /**
         * Display Paypal Express Checkout on product page
         */
        function buy_now_button() {
            global $pp_settings, $post, $product;
            
            if(!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
                return false;
            }
            if(!AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
                return false;
            }
            if (@$pp_settings['enabled']=='yes' && @$pp_settings['show_on_product_page']=='yes')
            {
                ?>
                <div class="angelleye_button_single">
                <?php
                $_product = wc_get_product($post->ID);
                $button_dynamic_class = 'single_variation_wrap_angelleye_' . $product->id;
                $hide = '';
                if($_product->product_type == 'variation' ||
                    $_product->is_type('external') ||
                    $_product->get_price() == 0 ||
                    $_product->get_price() == '')
                {
                    $hide = 'display:none;';
                }
                $add_to_cart_action = esc_url(add_query_arg( 'express_checkout', '1'));
                if (empty($pp_settings['checkout_with_pp_button_type'])) $pp_settings['checkout_with_pp_button_type']='paypalimage';
                switch($pp_settings['checkout_with_pp_button_type'])
                {
                    case "textbutton":
                        if(!empty($pp_settings['pp_button_type_text_button'])){
                            $button_text = $pp_settings['pp_button_type_text_button'];
                        } else {
                            $button_text = __( 'Proceed to Checkout', 'paypal-for-woocommerce' );
                        }
                        echo '<input data-action="'.$add_to_cart_action.'" type="button" style="float: left; clear: both; margin: 3px 0 0 0; border: none;',$hide,'" class="single_add_to_cart_button single_variation_wrap_angelleye paypal_checkout_button button alt '.$button_dynamic_class.'" name="express_checkout"  value="' .$button_text .'"/>';
                        break;
                    case "paypalimage":
                        $button_img =  WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage();
                        echo '<input data-action="'.$add_to_cart_action.'" type="image" src="',$button_img,'" style="width: auto; height: auto;float: left; clear: both; margin: 3px 0 3px 0; border: none; padding: 0;',$hide,'" class="single_add_to_cart_button single_variation_wrap_angelleye '.$button_dynamic_class.'" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') .'"/>';
                        break;
                    case "customimage":
                        $add_to_cart_action = esc_url(add_query_arg( 'express_checkout', '1'));
                        $button_img = $pp_settings['pp_button_type_my_custom'];
                        echo '<input data-action="'.$add_to_cart_action.'" type="image" src="',$button_img,'" style="float: left; clear: both; margin: 3px 0 3px 0; border: none; padding: 0;',$hide,'" class="single_add_to_cart_button single_variation_wrap_angelleye '.$button_dynamic_class.'" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') .'"/>';
                        break;
                }
                ?>
                </div>
                <?php
            }
        }

        /**
         * Redirect to PayPal from the product page EC button
         * @param $url
         * @return string
         */
        function add_to_cart_redirect($url) {
            if (isset($_REQUEST['express_checkout'])||isset($_REQUEST['express_checkout_x'])){
                wc_clear_notices();
                $url = esc_url_raw(add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) )) ;
            }
            return $url;
        }

        /**
         * Donate function
         */
        function donate_message() {
            if (@$_GET['page']=='wc-settings' && @$_GET['tab']=='checkout' && in_array( @$_GET['section'], array('wc_gateway_paypal_express_angelleye', 'wc_gateway_paypal_pro_angelleye', 'wc_gateway_paypal_pro_payflow_angelleye')) && !get_user_meta(get_current_user_id(), 'ignore_pp_donate') ) {
                ?>
                <div class="updated welcome-panel notice" id="paypal-for-woocommerce-donation">
                    <div style="float:left; margin: 19px 16px 19px 0;" id="plugin-icon-paypal-for-woocommerce" ></div>
                    <h3>PayPal for WooCommerce</h3>
                    <p class="donation_text">We are learning why it is difficult to provide, support, and maintain free software. Every little bit helps and is greatly appreciated.</p>
                    <p>Developers, join us on <a href="https://github.com/angelleye/paypal-woocommerce" target="_blank">GitHub</a>. Pull Requests are welcomed!</p>
                    <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SG9SQU2GBXJNA"><img style="float:left;margin-right:10px;" src="https://www.angelleye.com/images/paypal-for-woocommerce/donate-button.png" border="0" alt="PayPal - The safer, easier way to pay online!"></a>
                    <a class="welcome-panel-close" href="<?php echo esc_url( add_query_arg( array( 'ignore_pp_donate' => '0' ) ) ); ?>"><?php _e( 'Dismiss' ); ?></a>
                    <div style="clear:both"></div>
                </div>
            <?php
            }
        }
        function mini_cart_button(){
            global $pp_settings, $pp_pro, $pp_payflow;
            if(!AngellEYE_Utility::is_valid_for_use_paypal_express()) {
                return false;
            }
            if(!AngellEYE_Utility::is_express_checkout_credentials_is_set()) {
                return false;
            }
            if( @$pp_settings['enabled']=='yes' && (empty($pp_settings['show_on_cart']) || $pp_settings['show_on_cart']=='yes') && WC()->cart->cart_contents_count > 0) {
                echo '<div class="paypal_box_button">';
                if (empty($pp_settings['checkout_with_pp_button_type'])) $pp_settings['checkout_with_pp_button_type'] = 'paypalimage';

                $_angelleyeOverlay = '<div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url('. WC()->plugin_url() .'/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>';

                switch ($pp_settings['checkout_with_pp_button_type']) {
                    case "textbutton":
                        if (!empty($pp_settings['pp_button_type_text_button'])) {
                            $button_text = $pp_settings['pp_button_type_text_button'];
                        } else {
                            $button_text = __('Proceed to Checkout', 'paypal-for-woocommerce');
                        }
                        echo '<div class="paypal_ec_textbutton">';
                        echo '<a class="paypal_checkout_button button alt" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">' . $button_text . '</a>';
                        echo $_angelleyeOverlay;
                        echo '</div>';
                        break;
                    case "paypalimage":
                        echo '<div id="paypal_ec_button">';
                        echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                        echo "<img src='".WC_Gateway_PayPal_Express_AngellEYE::angelleye_get_paypalimage()."' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                        echo "</a>";
                        echo $_angelleyeOverlay;
                        echo '</div>';
                        break;
                    case "customimage":
                        $button_img = $pp_settings['pp_button_type_my_custom'];
                        echo '<div id="paypal_ec_button">';
                        echo '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')))) . '">';
                        echo "<img src='{$button_img}' width='150' border='0' alt='" . __('Pay with PayPal', 'paypal-for-woocommerce') . "'/>";
                        echo "</a>";
                        echo $_angelleyeOverlay;
                        echo '</div>';
                        break;
                }

                /**
                 * Displays the PayPal Credit checkout button if enabled in EC settings.
                 */
                if (isset($pp_settings['show_paypal_credit']) && $pp_settings['show_paypal_credit'] == 'yes') {
                    // PayPal Credit button
                    $paypal_credit_button_markup = '<div id="paypal_ec_paypal_credit_button">';
                    $paypal_credit_button_markup .= '<a class="paypal_checkout_button" href="' . esc_url(add_query_arg('use_paypal_credit', 'true', add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))))) . '" >';
                    $paypal_credit_button_markup .= "<img src='https://www.paypalobjects.com/webstatic/en_US/i/buttons/ppcredit-logo-small.png' alt='Check out with PayPal Credit'/>";
                    $paypal_credit_button_markup .= '</a>';
                    $paypal_credit_button_markup .= $_angelleyeOverlay;
                    $paypal_credit_button_markup .= '</div>';

                    echo $paypal_credit_button_markup;
                }
                ?>
                <!--<div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php /*echo WC()->plugin_url(); */?>/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>-->
                <script type="text/javascript">
                    jQuery(document).ready(function($){
                        $(".paypal_checkout_button").click(function(){
                            $(this).parent().find(".angelleyeOverlay").show();
                            return true;
                        });
                    });
                </script>
                <?php
                echo "<div class='clear'></div></div>";
            }
        }
        function add_div_before_add_to_cart_button(){
            ?>
            <div class="angelleye_buton_box_relative" style="position: relative;">
            <?php
        }
        function add_div_after_add_to_cart_button(){
            ?>
            <div class="blockUI blockOverlay angelleyeOverlay" style="display:none;z-index: 1000; border: none; margin: 0px; padding: 0px; width: 100%; height: 100%; top: 0px; left: 0px; opacity: 0.6; cursor: default; position: absolute; background: url(<?php echo WC()->plugin_url(); ?>/assets/images/select2-spinner.gif) 50% 50% / 16px 16px no-repeat rgb(255, 255, 255);"></div>
            </div>
            <?php
        }
        
        public function angelleye_check_version() {
        	
        	$paypal_for_woocommerce_version = get_option('paypal_for_woocommerce_version');
        	if( empty($paypal_for_woocommerce_version) ) {
        		
        		// PayFlow
                $woocommerce_paypal_pro_payflow_settings = get_option('woocommerce_paypal_pro_payflow_settings');
                if( isset($woocommerce_paypal_pro_payflow_settings) && !empty($woocommerce_paypal_pro_payflow_settings)) {
                	
                	if( !isset($woocommerce_paypal_pro_payflow_settings['payment_action']) && empty($woocommerce_paypal_pro_payflow_settings['payment_action'])) {
                		$woocommerce_paypal_pro_payflow_settings['payment_action'] = 'Sale';
                	}
                	
                	if( !isset($woocommerce_paypal_pro_payflow_settings['send_items']) && empty($woocommerce_paypal_pro_payflow_settings['send_items']) ) {
                		$woocommerce_paypal_pro_payflow_settings['send_items'] = 'yes';
                	}
                	
                	update_option('woocommerce_paypal_pro_payflow_settings', $woocommerce_paypal_pro_payflow_settings);
                }
                
                // DoDirectPayment
                $woocommerce_paypal_pro_settings = get_option('woocommerce_paypal_pro_settings');
                if( isset($woocommerce_paypal_pro_settings) && !empty($woocommerce_paypal_pro_settings)) {
                	
                	if( !isset($woocommerce_paypal_pro_settings['payment_action']) && empty($woocommerce_paypal_pro_settings['payment_action']) ) {
                		$woocommerce_paypal_pro_settings['payment_action'] = 'Sale';
                	}
                	
                	if( !isset($woocommerce_paypal_pro_settings['send_items']) && empty($woocommerce_paypal_pro_settings['send_items']) ) {
                		$woocommerce_paypal_pro_settings['send_items'] = 'yes';
                	}
                	
                	update_option('woocommerce_paypal_pro_settings', $woocommerce_paypal_pro_settings);
                }
                
                // PayPal Express Checkout
                $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
                if( isset($woocommerce_paypal_express_settings) && !empty($woocommerce_paypal_express_settings)) {
                	
                	if( !isset($woocommerce_paypal_express_settings['payment_action']) && empty($woocommerce_paypal_express_settings['payment_action'])) {
                		$woocommerce_paypal_express_settings['payment_action'] = 'Sale';
                	}
                	
                	if( !isset($woocommerce_paypal_express_settings['cancel_page']) && empty($woocommerce_paypal_express_settings['cancel_page'])) {
                		$woocommerce_paypal_express_settings['cancel_page'] = get_option('woocommerce_cart_page_id');
                	}
                	
                	if( !isset($woocommerce_paypal_express_settings['send_items']) && empty($woocommerce_paypal_express_settings['send_items'])) {
                		$woocommerce_paypal_express_settings['send_items'] = 'yes';
                	}
                	
                	if( !isset($woocommerce_paypal_express_settings['billing_address']) && empty($woocommerce_paypal_express_settings['billing_address'])) {
                		$woocommerce_paypal_express_settings['billing_address'] = 'no';
                	}
                	
                	if( !isset($woocommerce_paypal_express_settings['button_position']) && empty($woocommerce_paypal_express_settings['button_position'])) {
                		$woocommerce_paypal_express_settings['button_position'] = 'bottom';
                	}
                	
                	update_option('woocommerce_paypal_express_settings', $woocommerce_paypal_express_settings);
                }
                    update_option('paypal_for_woocommerce_version', self::VERSION_PFW);
        	}
        }


        public static function calculate($order, $send_items = false){

            $PaymentOrderItems = array();
            $ctr = $giftwrapamount = $total_items = $total_discount = $total_tax = $shipping = 0;
            $ITEMAMT = 0;
            if ($order) {
                $order_total = $order->get_total();
                $items = $order->get_items();
                /*
                * Set shipping and tax values.
                */
                if (get_option('woocommerce_prices_include_tax') == 'yes') {
                    $shipping = $order->get_total_shipping() + $order->get_shipping_tax();
                    $tax = 0;
                } else {
                    $shipping = $order->get_total_shipping();
                    $tax = $order->get_total_tax();
                }

                if('yes' === get_option( 'woocommerce_calc_taxes' ) && 'yes' === get_option( 'woocommerce_prices_include_tax' )) {
                    $tax = $order->get_total_tax();
                }
            }
            else {
                //if empty order we get data from cart
                $order_total = WC()->cart->total;
                $items = WC()->cart->get_cart();
                /**
                 * Get shipping and tax.
                 */
                if(get_option('woocommerce_prices_include_tax' ) == 'yes')
                {
                    $shipping 		= WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
                    $tax			= 0;
                }
                else
                {
                    $shipping 		= WC()->cart->shipping_total;
                    $tax 			= WC()->cart->get_taxes_total();
                }

                if('yes' === get_option( 'woocommerce_calc_taxes' ) && 'yes' === get_option( 'woocommerce_prices_include_tax' )) {
                    $tax = WC()->cart->get_taxes_total();
                }
            }

            if ($send_items) {
                foreach ($items as $item) {
                    /*
                     * Get product data from WooCommerce
                     */
                    if ($order) {
                        $_product = $order->get_product_from_item($item);
                        $qty = absint($item['qty']);
                        $item_meta = new WC_Order_Item_Meta($item,$_product);
                        $meta = $item_meta->display(true, true);
                    } else {
                        $_product = $item['data'];
                        $qty = absint($item['quantity']);
                        $meta = WC()->cart->get_item_data($item, true);
                    }

                    $sku = $_product->get_sku();
                    $item['name'] = html_entity_decode($_product->get_title(), ENT_NOQUOTES, 'UTF-8');
                    if ($_product->product_type == 'variation') {
                        if (empty($sku)) {
                            $sku = $_product->parent->get_sku();
                        }

                        if (!empty($meta)) {
                            $item['name'] .= " - " . str_replace(", \n", " - ", $meta);
                        }
                    }

                    $Item = array(
                        'name' => $item['name'], // Item name. 127 char max.
                        'desc' => '', // Item description. 127 char max.
                        'amt' => self::number_format(self::round( $item['line_subtotal'] / $qty)), // Cost of item.
                        'number' => $sku, // Item number.  127 char max.
                        'qty' => $qty, // Item qty on order.  Any positive integer.
                    );
                    array_push($PaymentOrderItems, $Item);
                    $ITEMAMT += self::round( $item['line_subtotal'] / $qty ) * $qty;
                }

                /**
                 * Add custom Woo cart fees as line items
                 */
                foreach (WC()->cart->get_fees() as $fee) {
                    $Item = array(
                        'name' => $fee->name, // Item name. 127 char max.
                        'desc' => '', // Item description. 127 char max.
                        'amt' => self::number_format($fee->amount, 2, '.', ''), // Cost of item.
                        'number' => $fee->id, // Item number. 127 char max.
                        'qty' => 1, // Item qty on order. Any positive integer.
                    );

                    /**
                     * The gift wrap amount actually has its own parameter in
                     * DECP, so we don't want to include it as one of the line
                     * items.
                     */
                    if ($Item['number'] != 'gift-wrap') {
                        array_push($PaymentOrderItems, $Item);
                        $ITEMAMT += self::round($fee->amount);
                    } else {
                        $giftwrapamount = self::round($fee->amount);
                    }

                    $ctr++;
                }

                //caculate discount
                if ($order){
                    if (!AngellEYE_Gateway_Paypal::is_wc_version_greater_2_3()) {
                        if ($order->get_cart_discount() > 0) {
                            foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
                                $Item = array(
                                    'name' => 'Cart Discount',
                                    'number' => $code,
                                    'qty' => '1',
                                    'amt' => '-' . self::number_format(WC()->cart->coupon_discount_amounts[$code])
                                );
                                array_push($PaymentOrderItems, $Item);
                            }
                            $total_discount -= $order->get_cart_discount();
                        }

                        if ($order->get_order_discount() > 0) {
                            foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
                                $Item = array(
                                    'name' => 'Order Discount',
                                    'number' => $code,
                                    'qty' => '1',
                                    'amt' => '-' . self::number_format(WC()->cart->coupon_discount_amounts[$code])
                                );
                                array_push($PaymentOrderItems, $Item);
                            }
                            $total_discount -= $order->get_order_discount();
                        }
                    } else {
                        if ($order->get_total_discount() > 0) {
                            $Item = array(
                                'name'      => 'Total Discount',
                                'qty'       => 1,
                                'amt'       => - self::number_format($order->get_total_discount()),
                                'number'    => implode(", ", $order->get_used_coupons())
                            );
                            array_push($PaymentOrderItems, $Item);
                            $total_discount -= $order->get_total_discount();
                        }
                    }
                } else {
                    if ( !empty( WC()->cart->applied_coupons ) ) {
                        foreach (WC()->cart->get_coupons('cart') as $code => $coupon) {
                            $Item = array(
                                'name' => 'Cart Discount',
                                'qty' => '1',
                                'number'=> $code,
                                'amt' => '-' . self::number_format(WC()->cart->coupon_discount_amounts[$code])
                            );
                            array_push($PaymentOrderItems, $Item);
                            $total_discount -= self::number_format(WC()->cart->coupon_discount_amounts[$code]);
                        }

                    }

                    if (!AngellEYE_Gateway_Paypal::is_wc_version_greater_2_3()) {
                        if ( !empty( WC()->cart->applied_coupons ) ) {
                            foreach (WC()->cart->get_coupons('order') as $code => $coupon) {
                                $Item = array(
                                    'name' => 'Order Discount',
                                    'qty' => '1',
                                    'number'=> $code,
                                    'amt' => '-' . self::number_format(WC()->cart->coupon_discount_amounts[$code])
                                );
                                array_push($PaymentOrderItems, $Item);
                                $total_discount -= self::number_format(WC()->cart->coupon_discount_amounts[$code]);
                            }

                        }
                    }
                }
            }



            if( $tax > 0) {
                $tax = self::number_format($tax);
            }

            if( $shipping > 0) {
                $shipping = self::number_format($shipping);
            }

            if( $total_discount ) {
                $total_discount = self::round($total_discount);
            }

            if (empty($ITEMAMT)) {
                $cart_fees = WC()->cart->get_fees();
                if( isset($cart_fees[0]->id) && $cart_fees[0]->id == 'gift-wrap' ) {
                    $giftwrapamount = isset($cart_fees[0]->amount)  ? $cart_fees[0]->amount : 0;
                } else {
                    $giftwrapamount = 0;
                }
                $Payment['itemamt'] = $order_total - $tax - $shipping - $giftwrapamount;
            } else {
                $Payment['itemamt'] = self::number_format($ITEMAMT + $total_discount);
            }


            /*
             * Set tax
             */
            if ($tax > 0) {
                $Payment['taxamt'] = self::number_format($tax);       // Required if you specify itemized L_TAXAMT fields.  Sum of all tax items in this order.
            } else {
                $Payment['taxamt'] = 0;
            }

            /*
             * Set shipping
             */
            if ($shipping > 0) {
                $Payment['shippingamt'] = self::number_format($shipping);      // Total shipping costs for this order.  If you specify SHIPPINGAMT you mut also specify a value for ITEMAMT.
            } else {
                $Payment['shippingamt'] = 0;
            }

            $Payment['order_items'] = $PaymentOrderItems;

            // Rounding amendment
            if (trim(self::number_format($order_total)) !== trim(self::number_format($Payment['itemamt'] + $giftwrapamount + $tax + $shipping))) {
                $diffrence_amount = AngellEYE_Gateway_Paypal::get_diffrent($order_total, $Payment['itemamt'] + $tax + $shipping);
                if($shipping > 0) {
                    $Payment['shippingamt'] = abs(self::number_format($shipping + $diffrence_amount));
                } elseif ($tax > 0) {
                    $Payment['taxamt'] = abs(self::number_format($tax + $diffrence_amount));
                } else {
                    //make change to itemamt
                    $Payment['itemamt'] = abs(self::number_format($Payment['itemamt'] + $diffrence_amount));
                    //also make change to the first item
                    if ($send_items) {
                        $Payment['order_items'][0]['amt'] = abs(self::number_format($Payment['order_items'][0]['amt'] + $diffrence_amount / $Payment['order_items'][0]['qty']));
                    }

                }
            }

            return $Payment;
        }

        public static function get_diffrent($amout_1, $amount_2) {
            $diff_amount = $amout_1 - $amount_2;
            return $diff_amount;
        }
        public static function cut_off($number) {
            $parts = explode(".", $number);
            $newnumber = $parts[0] . "." . $parts[1][0] . $parts[1][1];
            return $newnumber;
        }

        public static function is_wc_version_greater_2_3() {
            return AngellEYE_Gateway_Paypal::get_wc_version() && version_compare(AngellEYE_Gateway_Paypal::get_wc_version(), '2.3', '>=');
        }

        public static function get_wc_version() {
            return defined('WC_VERSION') && WC_VERSION ? WC_VERSION : null;
        }

        /**
         * Check if site is SSL ready
         *
         */
        static public function is_ssl()
        {
            if (is_ssl() || get_option('woocommerce_force_ssl_checkout') == 'yes' || class_exists('WordPressHTTPS'))
                return true;
            return false;
        }

        public function angelleye_admin_menu_own(){
        	$this->plugin_screen_hook_suffix = add_submenu_page(
			'options-general.php', 
			__( 'PayPal for WooCommerce - Settings', 'paypal-for-woocommerce' ),
			__( 'PayPal for WooCommerce', 'paypal-for-woocommerce' ),
			'manage_options',
			'paypal-for-woocommerce',
			array( $this, 'display_plugin_admin_page'));	
        }
        
        public function display_plugin_admin_page(){
            // WooCommerce product categories
            $taxonomy     = 'product_cat';
            $orderby      = 'name';
            $show_count   = 0;      // 1 for yes, 0 for no
            $pad_counts   = 0;      // 1 for yes, 0 for no
            $hierarchical = 1;      // 1 for yes, 0 for no
            $title        = '';
            $empty        = 0;

            $args = array(
            'taxonomy'     => $taxonomy,
            'orderby'      => $orderby,
            'show_count'   => $show_count,
            'pad_counts'   => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li'     => $title,
            'hide_empty'   => $empty
            );

            $product_cats = get_categories( $args );
            include_once( 'template/admin.php' );
        }
        
        static public function curPageURL() {
            $pageURL = 'http';
            if (@$_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
            $pageURL .= "://";
            if ($_SERVER["SERVER_PORT"] != "80") {
                $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
            } else {
                $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
            }
            return $pageURL;
        }
        
        public function angelleye_woocommerce_add_to_cart_redirect($url) {
            if (isset($_REQUEST['express_checkout']) && $_REQUEST['express_checkout'] == '1') {
                return add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/')));
            } else {
                return $url;
            }
        }

        /*
         *   Billing Agreement Adjustments #382 
         */
        public static function angelleye_paypal_for_woocommerce_paypal_billing_agreement($PayPalRequestData, $tokenization) {
            if (sizeof(WC()->cart->get_cart()) != 0) {
                foreach (WC()->cart->get_cart() as $key => $value) {
                    $_product = $value['data'];
                    if (isset($_product->id) && !empty($_product->id) ) {
                        $_paypal_billing_agreement = get_post_meta($_product->id, '_paypal_billing_agreement', true);
                        if( $_paypal_billing_agreement == 'yes' || $tokenization == true) {
                            $BillingAgreements = array();
                            $Item = array(
                                'l_billingtype' => '', // Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
                                'l_billingtype' => 'MerchantInitiatedBilling', // Required.  Type of billing agreement.  For recurring payments it must be RecurringPayments.  You can specify up to ten billing agreements.  For reference transactions, this field must be either:  MerchantInitiatedBilling, or MerchantInitiatedBillingSingleSource
                                'l_billingagreementdescription' => '', // Required for recurring payments.  Description of goods or services associated with the billing agreement.
                                'l_paymenttype' => '', // Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
                                'l_paymenttype' => 'Any', // Specifies the type of PayPal payment you require for the billing agreement.  Any or IntantOnly
                                'l_billingagreementcustom' => ''     // Custom annotation field for your own use.  256 char max.
                            );
                            array_push($BillingAgreements, $Item);
                            $PayPalRequestData['BillingAgreements'] = $BillingAgreements;
                            return $PayPalRequestData;
                        } 
                    }
                }
            } 
            return $PayPalRequestData;
        }
        
        
        /*
         *  Express Checkout - Digital / Virtual Goods - NOSHIPPING #174 
         */
        public static function angelleye_paypal_for_woocommerce_needs_shipping($SECFields) {
            if (sizeof(WC()->cart->get_cart()) != 0) {
                foreach (WC()->cart->get_cart() as $key => $value) {
                    $_product = $value['data'];
                    if (isset($_product->id) && !empty($_product->id) ) {
                        $_no_shipping_required = get_post_meta($_product->id, '_no_shipping_required', true);
                        if( $_no_shipping_required == 'yes' ) {
                            $SECFields['noshipping'] = 1;
                        } else {
                            $SECFields['noshipping'] = 0;
                            return $SECFields;
                        }
                    }
                }
            } else {
                $SECFields['noshipping'] = 0;
            }
            return $SECFields;
        }
        
        
        function angelleye_product_type_options_own($product_type){
            if( isset($product_type) && !empty($product_type) ) {
                $product_type['no_shipping_required'] = array(
                        'id'            => '_no_shipping_required',
                        'wrapper_class' => '',
                        'label'         => __( 'No Shipping Required', 'paypal-for-woocommerce' ),
                        'description'   => __( 'Disables shipping requirements in the PayPal checkout flow.', 'paypal-for-woocommerce' ),
                        'default'       => 'no'
                );
                $product_type['paypal_billing_agreement'] = array(
                        'id'            => '_paypal_billing_agreement',
                        'wrapper_class' => '',
                        'label'         => __( 'Enable PayPal Billing Agreement', 'paypal-for-woocommerce' ),
                        'description'   => __( 'Adds a billing agreement to the product.  The user must agree to the billing agreement on the PayPal checkout pages, and then you can process future payments for the buyer using reference transactions..', 'paypal-for-woocommerce' ),
                        'default'       => 'no'
                );
                return $product_type;
            } else {
                    return $product_type;
            }
        }
        
        function angelleye_woocommerce_process_product_meta_own( $post_id ){
            $no_shipping_required = isset( $_POST['_no_shipping_required'] ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_no_shipping_required', $no_shipping_required );
            $_paypal_billing_agreement = isset( $_POST['_paypal_billing_agreement'] ) ? 'yes' : 'no';
            update_post_meta( $post_id, '_paypal_billing_agreement', $_paypal_billing_agreement );
        }
        
        public static function angelleye_paypal_for_woocommerce_curl_error_handler($PayPalResult, $methos_name = null, $gateway = null, $error_email_notify = true) {
            if( isset( $PayPalResult['CURL_ERROR'] ) ){
                try {
                        if($error_email_notify == true) {
                            $admin_email = get_option("admin_email");
                            $message = __( $methos_name . " call failed." , "paypal-for-woocommerce" )."\n\n";
                            $message .= __( 'Error Code: 0' ,'paypal-for-woocommerce' ) . "\n";
                            $message .= __( 'Detailed Error Message: ' , 'paypal-for-woocommerce') . $PayPalResult['CURL_ERROR'];
                            wp_mail($admin_email, $gateway . " Error Notification",$message);
                        }
                        $display_error = 'There was a problem connecting to the payment gateway.';
                        wc_add_notice($display_error, 'error');
                        if (!is_ajax()) {
                            wp_redirect(get_permalink(wc_get_page_id('cart')));
                            exit;
                        } else {
                            wp_send_json_error( array( 'error' => $display_error ) );
                        }
                        
                } catch ( Exception $e ) {
                    if ( ! empty( $e ) ) {
                        throw new Exception( __( $e->getMessage(), 'paypal-for-woocommerce' ) );
                    }
                }
            }
        }
        
        /*
         * Check payment gateway settings to cancel order based on transaction's seller protection response
         * @param WC_Payment_Gateway $Payment_Gateway
         * @param array $PayPalResult
         * @return bool
         */
        public static function angelleye_woocommerce_sellerprotection_should_cancel_order(&$Payment_Gateway,&$PayPalResult) {
          // Following check should not be needed, but in case something goes wrong, we know what happened.
          if(in_array('WC_Payment_Gateway',class_parents($Payment_Gateway)) === false) {
            error_log('FATAL ERROR! Payment gateway provided to angelleye_woocommerce_sellerprotection_should_cancel_order() is not of WC_Payment_Gateway.');
            return false;
          }
          // TODO: Add $order_cancellations setting to all applicable Angell EYE payment gateways
          // If there is no setting available, this will become a NULL, which will default in the following case switch.
          // NOTE: All gateways that use this function need to correctly add a note to the order which will explain WHY
          // it wias cancelled (i.e. seller protection protection requirements failed)
          $order_cancellation_setting = @$Payment_Gateway->order_cancellations;
          // TODO: (?) Add some function that will take the returned (and verified) PayPal transaction details and return the applicable
          // seller protection value based on the payment gateway/API call. **The following line is only for PayPal Express!**
          $txn_protection_eligibility_response = isset($PayPalResult['PAYMENTINFO_0_PROTECTIONELIGIBILITY'])?$PayPalResult['PAYMENTINFO_0_PROTECTIONELIGIBILITY']:'ERROR!';
          // TODO: (?) Same goes for the transaction ID. **The following line is only for PayPal Express!**
          $txn_id = isset($PayPalResult['PAYMENTINFO_0_TRANSACTIONID'])?$PayPalResult['PAYMENTINFO_0_TRANSACTIONID']:'ERROR!';
          switch($order_cancellation_setting) {
            // If transaction does not have ANY seller protection
            case 'no_seller_protection':
              if($txn_protection_eligibility_response != 'Eligible' && $txn_protection_eligibility_response != 'PartiallyEligible') {
                $Payment_Gateway->add_log('Transaction '.$txn_id.' is BAD. Setting: no_seller_protection, Response: '.$txn_protection_eligibility_response);
                return true;
              }
              $Payment_Gateway->add_log('Transaction '.$txn_id.' is OK. Setting: no_seller_protection, Response: '.$txn_protection_eligibility_response);
              return false;
            // If transaction is not protected for unauthorized payments
            case 'no_unauthorized_payment_protection':
              if($txn_protection_eligibility_response != 'Eligible') {
                $Payment_Gateway->add_log('Transaction '.$txn_id.' is BAD. Setting: no_unauthorized_payment_protection, Response: '.$txn_protection_eligibility_response);
                return true;
              }
              $Payment_Gateway->add_log('Transaction '.$txn_id.' is OK. Setting: no_unauthorized_payment_protection, Response: '.$txn_protection_eligibility_response);
              return false;
            // If we have disabled this check/feature
            case 'disabled':
              $Payment_Gateway->add_log('Transaction '.$txn_id.' is OK. Setting: disabled, Response: '.$txn_protection_eligibility_response);
              return false;
            // Catch all other invalid values
            default:
              $Payment_Gateway->add_log('ERROR! order_cancellations setting for '.$Payment_Gateway->method_title.' is not valid!');
              return true;
          }
        }
        
        /**
         * Express Checkout - Adjust button on product details page. #208 
         * @param type $qtyone
         * @param type $quantity
         * @param type $product_id
         * @param type $variation_id
         * @param type $cart_item_data
         * @return type
         * @since    1.1.8
         */
        public function angelleye_woocommerce_add_to_cart_sold_individually_quantity($qtyone, $quantity, $product_id, $variation_id, $cart_item_data) {
            if( (isset($_REQUEST['express_checkout']) && $_REQUEST['express_checkout'] == 1) && (isset($_REQUEST['add-to-cart']) && !empty($_REQUEST['add-to-cart'])) ) {
                if (sizeof(WC()->cart->get_cart()) != 0) {
                    foreach( WC()->cart->get_cart() as $cart_item_key => $values ) {
                        $_product = $values['data'];
                        if( $product_id == $_product->id || $variation_id == $_product->id) {
                            wp_redirect(add_query_arg('pp_action', 'expresscheckout', add_query_arg('wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url('/'))));
                            exit();
                        }
                    }
                } else {
                   return $qtyone; 
                }
            } else {
                return $qtyone;
            }
        }
        
        public function angelleye_woocommerce_admin_enqueue_scripts() {
            wp_enqueue_style( 'ppe_cart', plugins_url( 'assets/css/admin.css' , __FILE__ ) );
        }
        
        public function angelleye_woocommerce_pfw_ed_shipping_bulk_tool() {
    
            if (is_admin() && (defined('DOING_AJAX') || DOING_AJAX)) {

                global $wpdb;

                $processed_product_id = array();
                $errors = FALSE;
                $products = FALSE;
                $product_ids = FALSE;
                $update_count = 0;
                $where_args = array(
                    'post_type' => array('product', 'product_variation'),
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'fields' => 'id=>parent',
                );
                $where_args['meta_query'] = array();
                $pfw_bulk_action_type = ( isset($_POST["actionType"]) ) ? $_POST['actionType'] : FALSE;
                $pfw_bulk_action_target_type = ( isset($_POST["actionTargetType"]) ) ? $_POST['actionTargetType'] : FALSE;
                $pfw_bulk_action_target_where_type = ( isset($_POST["actionTargetWhereType"]) ) ? $_POST['actionTargetWhereType'] : FALSE;
                $pfw_bulk_action_target_where_category = ( isset($_POST["actionTargetWhereCategory"]) ) ? $_POST['actionTargetWhereCategory'] : FALSE;
                $pfw_bulk_action_target_where_product_type = ( isset($_POST["actionTargetWhereProductType"]) ) ? $_POST['actionTargetWhereProductType'] : FALSE;
                $pfw_bulk_action_target_where_price_value = ( isset($_POST["actionTargetWherePriceValue"]) ) ? $_POST['actionTargetWherePriceValue'] : FALSE;
                $pfw_bulk_action_target_where_stock_value = ( isset($_POST["actionTargetWhereStockValue"]) ) ? $_POST['actionTargetWhereStockValue'] : FALSE;

                if (!$pfw_bulk_action_type || !$pfw_bulk_action_target_type) {
                    $errors = TRUE;
                }

                $is_enable_value = explode("_", $pfw_bulk_action_type);
                $is_enable = (isset($is_enable_value[0]) && $is_enable_value[0] == 'enable') ? 'yes' : 'no';
                
                if( $pfw_bulk_action_type == 'enable_no_shipping' || $pfw_bulk_action_type == 'disable_no_shipping' ) {
                    $action_key = "_no_shipping_required";
                    
                } elseif ($pfw_bulk_action_type == 'enable_paypal_billing_agreement' || $pfw_bulk_action_type == 'disable_paypal_billing_agreement') {
                    $action_key = "_paypal_billing_agreement";
                }

                // All Products
                if ($pfw_bulk_action_target_type == 'all') {

                    $products = new WP_Query($where_args);

                } elseif ($pfw_bulk_action_target_type == 'featured') {
                    // Featured products
                    array_push($where_args['meta_query'], array(
                        'key' => '_featured',
                        'value' => 'yes'
                            )
                    );
                    $products = new WP_Query($where_args);
                } elseif($pfw_bulk_action_target_type == 'all_downloadable') {
                    // downloadable products.
                    array_push($where_args['meta_query'], array(
                            'key'           => '_downloadable',
                            'value'         => 'yes'
                        ));
                    
                    $products = new WP_Query($where_args);

                } elseif($pfw_bulk_action_target_type == 'all_virtual') {
                    // virtual products.
                    array_push($where_args['meta_query'], array(
                            'key'           => '_virtual',
                            'value'         => 'yes'
                        ));
                    
                    $products = new WP_Query($where_args);

                } elseif ($pfw_bulk_action_target_type == 'where' && $pfw_bulk_action_target_where_type) {

                    // Where - By Category
                    if ($pfw_bulk_action_target_where_type == 'category' && $pfw_bulk_action_target_where_category) {
                        $where_args['product_cat'] = $pfw_bulk_action_target_where_category;
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'product_type' && $pfw_bulk_action_target_where_product_type) {
                        // Where - By Product type
                        $where_args['product_type'] = $pfw_bulk_action_target_where_product_type;
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'price_greater') {
                        array_push($where_args['meta_query'], array(
                            'key' => '_price',
                            'value' => str_replace(",", "", number_format($pfw_bulk_action_target_where_price_value, 2)),
                            'compare' => '>',
                            'type' => 'DECIMAL(10,2)'
                                )
                        );
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'price_less') {
                        // Where - By Price - less than
                        array_push($where_args['meta_query'], array(
                            'key' => '_price',
                            'value' => str_replace(",", "", number_format($pfw_bulk_action_target_where_price_value, 2)),
                            'compare' => '<',
                            'type' => 'DECIMAL(10,2)'
                                )
                        );
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'stock_greater') {
                        // Where - By Stock - greater than
                        array_push($where_args['meta_query'], array(
                            'key' => '_manage_stock',
                            'value' => 'yes'
                                )
                        );
                        array_push($where_args['meta_query'], array(
                            'key' => '_stock',
                            'value' => str_replace(",", "", number_format($pfw_bulk_action_target_where_stock_value, 0)),
                            'compare' => '>',
                            'type' => 'NUMERIC'
                                )
                        );
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'stock_less') {
                        // Where - By Stock - less than
                        array_push($where_args['meta_query'], array(
                            'key' => '_manage_stock',
                            'value' => 'yes'
                                )
                        );
                        array_push($where_args['meta_query'], array(
                            'key' => '_stock',
                            'value' => str_replace(",", "", number_format($pfw_bulk_action_target_where_stock_value, 0)),
                            'compare' => '<',
                            'type' => 'NUMERIC'
                                )
                        );
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'instock') {
                        // Where - Stock status 'instock'
                        array_push($where_args['meta_query'], array(
                            'key' => '_stock_status',
                            'value' => 'instock'
                                )
                        );
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'outofstock') {
                        // Where - Stock status 'outofstock'
                        array_push($where_args['meta_query'], array(
                            'key' => '_stock_status',
                            'value' => 'outofstock'
                                )
                        );
                        $products = new WP_Query($where_args);
                    } elseif ($pfw_bulk_action_target_where_type == 'sold_individually') {
                        // Where - Sold Individually
                        array_push($where_args['meta_query'], array(
                            'key' => '_sold_individually',
                            'value' => 'yes'
                                )
                        );
                        $products = new WP_Query($where_args);
                    }
                } else {
                    $errors = TRUE;
                }

                // Update posts
                if (!$errors && $products) {
                    if (count($products->posts) < 1) {
                        $errors = TRUE;
                        $update_count = 'zero';
                        $redirect_url = admin_url('options-general.php?page=' . $this->plugin_slug . '&tab=tools&processed=' . $update_count);
                        echo $redirect_url;
                    } else {
                        foreach ($products->posts as $target) {
                            $target_product_id = ( $target->post_parent != '0' ) ? $target->post_parent : $target->ID;
                            if (get_post_type($target_product_id) == 'product' && !in_array($target_product_id, $processed_product_id)) {
                                if (!update_post_meta($target_product_id, $action_key, $is_enable)) {

                                } else {
                                    $processed_product_id[$target_product_id] = $target_product_id;
                                }
                            }
                        }
                        $update_count = count($processed_product_id);
                    }
                }

                // return
                if (!$errors) {
                    if ($update_count == 0) {
                        $update_count = 'zero';
                    }
                    $redirect_url = admin_url('options-general.php?page=paypal-for-woocommerce&tab=tabs&processed=' . $update_count);
                    echo $redirect_url;
                } else {
                    //echo 'failed';
                }
                die(); // this is required to return a proper result
            }
        }
        
        /**
         * @since    1.1.8.1
         * Non-decimal currency bug..?? #384 
         * Check if currency has decimals
         * @param type $currency
         * @return boolean
         */
        public static function currency_has_decimals( $currency ) {
		if ( in_array( $currency, array( 'HUF', 'JPY', 'TWD' ) ) ) {
			return false;
		}

		return true;
	}

        /**
         * @since    1.1.8.1
         * Non-decimal currency bug..?? #384 
         * Round prices
         * @param type $price
         * @return type
         */
	public static function round( $price ) {
		$precision = 2;

		if ( !self::currency_has_decimals( get_woocommerce_currency() ) ) {
			$precision = 0;
		}

		return round( $price, $precision );
	}

        /**
         * @since    1.1.8.1
         * Non-decimal currency bug..?? #384 
         * Round prices
         * @param type $price
         * @return type
         */
	public static function number_format( $price ) {
		$decimals = 2;

		if ( !self::currency_has_decimals( get_woocommerce_currency() ) ) {
			$decimals = 0;
		}

		return number_format( $price, $decimals, '.', '' );
	}
        
        public function angelleye_paypal_express_checkout_process_checkout_fields() {
            $this->set_session('checkout_form_post_data', serialize($_POST));
        }
        
        private function set_session($key, $value) {
            WC()->session->$key = $value;
        }
        public function http_api_curl_ex_add_curl_parameter($handle, $r, $url ) {
            $Force_tls_one_point_two = get_option('Force_tls_one_point_two', 'no');
            if ( (strstr( $url, 'https://' ) && strstr( $url, '.paypal.com' )) && isset($Force_tls_one_point_two) && $Force_tls_one_point_two == 'yes' ) {
                curl_setopt($handle, CURLOPT_VERBOSE, 1);
                curl_setopt($handle, CURLOPT_SSLVERSION, 6);
            }
        }
        
        public function angelleye_paypal_plus_notice($user_id) {
            $paypal_plus = get_option('woocommerce_paypal_plus_settings');
            $ignore_paypal_plus_move_notice = get_option('ignore_paypal_plus_move_notice');
            $ignore_paypal_plus_move_notice = get_user_meta($user_id, 'ignore_paypal_plus_move_notice');
            if($ignore_paypal_plus_move_notice == 'true') {
                return false;
            }
            if ( !empty($paypal_plus['enabled']) && $paypal_plus['enabled'] == 'yes' && version_compare(self::VERSION_PFW,'1.2.4','<=') && $this->is_paypal_plus_plugin_active() == false && $ignore_paypal_plus_move_notice == false) {
                echo '<div class="notice welcome-panel error"><p style="margin: 10px;">' . sprintf( __("In order to better support the different countries and international features that PayPal Plus provides we have created a new, separate plugin. <a href='https://www.angelleye.com/product/woocommerce-paypal-plus-plugin' target='_blank'>Get the New PayPal Plus Plugin!</a>"));
                ?></p><a class="welcome-panel-close" href="<?php echo esc_url( add_query_arg( array( 'ignore_paypal_plus_move_notice' => '0' ) ) ); ?>"><?php _e( 'Dismiss' ); ?></a></div><?php 
            }
        }
        
        public function is_paypal_plus_plugin_active() {
            if ( !in_array( 'woo-paypal-plus/woo-paypal-plus.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && !is_plugin_active_for_network( 'woo-paypal-plus/woo-paypal-plus.php' )) {
                return false;
            } else {
                return true;
            }
        }
        
        public function wc_gateway_payment_token_api_parser() {
            if( !empty($_GET['do_action']) && $_GET['do_action'] = 'update_payment_method') {
                if( !empty($_GET['method_name']) && $_GET['method_name'] == 'paypal_express') {
                    switch ($_GET['action_name']) {
                       case 'SetExpressCheckout':
                            $woocommerce_token_api = new WC_Gateway_PayPal_Express_AngellEYE();
                            $woocommerce_token_api->paypal_express_checkout_token_request_handler();
                           break;
                       default:
                           break;
                   }
                }
            }
        }
        
        public function woo_compatibility_notice() {
            echo '<div class="error"><p>' . __('PayPal for WooCommerce requires WooCommerce version 2.6 or higher.  Please backup your site files and database, update WooCommerce, and try again.','paypal-for-woocommerce') . '</p></div>';
        }
        
        public function angelleye_express_checkout_decrypt_gateway_api($bool) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'woocommerce_paypal_express_settings' ) );
            $gateway_settings = isset($row->option_value) ? maybe_unserialize($row->option_value) : array();
            if( !empty($row->option_value) && !empty($gateway_settings['is_encrypt'])) {
                $gateway_settings_key_array = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
                foreach ($gateway_settings_key_array as $gateway_setting_key => $gateway_settings_value) {
                    if( !empty( $gateway_settings[$gateway_settings_value]) ) {
                        $gateway_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($gateway_settings[$gateway_settings_value], $action = 'd');
                    }
                }
                return $gateway_settings;
            } else {
                return $bool;
            }
        }
        public function angelleye_paypal_advanced_decrypt_gateway_api($bool) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'woocommerce_paypal_advanced_settings' ) );
            $gateway_settings = isset($row->option_value) ? maybe_unserialize($row->option_value) : array();
            if( !empty($row->option_value) && !empty($gateway_settings['is_encrypt'])) {
                $gateway_settings_key_array = array('loginid', 'resellerid', 'user', 'password');
                foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                    if( !empty( $gateway_settings[$gateway_settings_value]) ) {
                        $gateway_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($gateway_settings[$gateway_settings_value], $action = 'd');
                    }
                }
                return $gateway_settings;
            } else {
                return $bool;
            }
        }
        public function angelleye_paypal_credit_card_rest_decrypt_gateway_api($bool) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'woocommerce_paypal_credit_card_rest_settings' ) );
            $gateway_settings = isset($row->option_value) ? maybe_unserialize($row->option_value) : array();
            if( !empty($row->option_value) && !empty($gateway_settings['is_encrypt'])) {
                $gateway_settings_key_array = array('rest_client_id_sandbox', 'rest_secret_id_sandbox', 'rest_client_id', 'rest_secret_id');
                foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                    if( !empty( $gateway_settings[$gateway_settings_value]) ) {
                        $gateway_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($gateway_settings[$gateway_settings_value], $action = 'd');
                    }
                }
                return $gateway_settings;
            } else {
                return $bool;
            }
        }
        public function angelleye_paypal_pro_decrypt_gateway_api($bool) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'woocommerce_paypal_pro_settings' ) );
            $gateway_settings = isset($row->option_value) ? maybe_unserialize($row->option_value) : array();
            if( !empty($row->option_value) && !empty($gateway_settings['is_encrypt'])) {
                $gateway_settings_key_array = array('sandbox_api_username', 'sandbox_api_password', 'sandbox_api_signature', 'api_username', 'api_password', 'api_signature');
                foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                    if( !empty( $gateway_settings[$gateway_settings_value]) ) {
                        $gateway_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($gateway_settings[$gateway_settings_value], $action = 'd');
                    }
                }
                return $gateway_settings;
            } else {
                return $bool;
            }
        }
        public function angelleye_paypal_pro_payflow_decrypt_gateway_api($bool) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'woocommerce_paypal_pro_payflow_settings' ) );
            $gateway_settings = isset($row->option_value) ? maybe_unserialize($row->option_value) : array();
            if( !empty($row->option_value) && !empty($gateway_settings['is_encrypt'])) {
                $gateway_settings_key_array = array('sandbox_paypal_vendor', 'sandbox_paypal_password', 'sandbox_paypal_user', 'sandbox_paypal_partner', 'paypal_vendor', 'paypal_password', 'paypal_user', 'paypal_partner');
                foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                    if( !empty( $gateway_settings[$gateway_settings_value]) ) {
                        $gateway_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($gateway_settings[$gateway_settings_value], $action = 'd');
                    }
                }
                return $gateway_settings;
            } else {
                return $bool;
            }
        }
        public function angelleye_braintree_decrypt_gateway_api($bool) {
            global $wpdb;
            $row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", 'woocommerce_braintree_settings' ) );
            $gateway_settings = isset($row->option_value) ? maybe_unserialize($row->option_value) : array();
            if( !empty($row->option_value) && !empty($gateway_settings['is_encrypt'])) {
                $gateway_settings_key_array = array('sandbox_public_key', 'sandbox_private_key', 'sandbox_merchant_id', 'sandbox_merchant_account_id', 'public_key', 'private_key', 'merchant_id', 'merchant_account_id');
                foreach ($gateway_settings_key_array as $gateway_settings_key => $gateway_settings_value) {
                    if( !empty( $gateway_settings[$gateway_settings_value]) ) {
                        $gateway_settings[$gateway_settings_value] = AngellEYE_Utility::crypting($gateway_settings[$gateway_settings_value], $action = 'd');
                    }
                }
                return $gateway_settings;
            } else {
                return $bool;
            }
        }
    }
}
new AngellEYE_Gateway_Paypal();