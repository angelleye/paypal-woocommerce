<?php
/**
 * @wordpress-plugin
 * Plugin Name:       PayPal for WooCommerce
 * Plugin URI:        http://www.angelleye.com/product/paypal-for-woocommerce-plugin/
 * Description:       Easily enable PayPal Express Checkout, Website Payments Pro 3.0, and Payments Pro 2.0 (PayFlow).  Each option is available separately so you can enable them individually.
 * Version:           1.1.5
 * Author:            Angell EYE
 * Author URI:        http://www.angelleye.com/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
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

/**
 * Set global parameters
 */
global $woocommerce, $pp_settings, $pp_pro, $pp_payflow, $wp_version;

/**
 * Get Settings
 */
$pp_settings = get_option( 'woocommerce_paypal_express_settings' );
$pp_pro     = get_option('woocommerce_paypal_pro_settings');
$pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');
if(!class_exists('AngellEYE_Gateway_Paypal')){
    class AngellEYE_Gateway_Paypal
    {
        /**
         * General class constructor where we'll setup our actions, hooks, and shortcodes.
         *
         */
        public function __construct()
        {

            /**
             * Check current WooCommerce version to ensure compatibility.
             */
            $woo_version = $this->wpbo_get_woo_version_number();
            if(version_compare($woo_version,'2.1','<'))
            {
                exit( __('PayPal for WooCommerce requires WooCommerce version 2.1 or higher.  Please backup your site files and database, update WooCommerce, and try again.','paypal-for-woocommerce'));
            }

            add_filter( 'woocommerce_paypal_args', array($this,'ae_paypal_standard_additional_parameters'));
            add_action( 'plugins_loaded', array($this, 'init'));
            register_activation_hook( __FILE__, array($this, 'activate_paypal_for_woocommerce' ));
            register_deactivation_hook( __FILE__,array($this,'deactivate_paypal_for_woocommerce' ));
            add_action( 'wp_enqueue_scripts', array($this, 'woocommerce_paypal_express_init_styles'), 12 );
            add_action( 'admin_notices', array($this, 'admin_notices') );
            add_action( 'admin_init', array($this, 'set_ignore_tag'));
            add_filter( 'woocommerce_product_title' , array($this, 'woocommerce_product_title') );
            add_action( 'woocommerce_sections_checkout', array( $this, 'donate_message' ), 11 );

            // http://stackoverflow.com/questions/22577727/problems-adding-action-links-to-wordpress-plugin
            $basename = plugin_basename(__FILE__);
            $prefix = is_network_admin() ? 'network_admin_' : '';
            add_filter("{$prefix}plugin_action_links_$basename",array($this,'plugin_action_links'),10,4);
            add_action( 'woocommerce_after_add_to_cart_button', array($this, 'buy_now_button'));
            add_action( 'add_to_cart_redirect', array($this, 'add_to_cart_redirect'));
            add_action( 'woocommerce_after_single_variation', array($this, 'buy_now_button_js'));
            add_action('admin_enqueue_scripts', array( $this , 'onetarek_wpmut_admin_scripts' ) );
            add_action('admin_print_styles', array( $this , 'onetarek_wpmut_admin_styles' ) );
            add_action( 'woocommerce_cart_calculate_fees', array($this, 'woocommerce_custom_surcharge') );
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

        function onetarek_wpmut_admin_scripts()
        {
            $dir = plugin_dir_path( __FILE__ );
            wp_enqueue_media();
            wp_enqueue_script( 'jquery');
        }

        function onetarek_wpmut_admin_styles()
        {
            wp_enqueue_style('thickbox');

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

            if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                if(!in_array(@$_GET['action'],array('activate-plugin', 'upgrade-plugin','activate','do-plugin-upgrade')) && is_plugin_active($plugin) ) {
                    deactivate_plugins( $plugin );
                    wp_die( "<strong>".$plugin_data['Name']."</strong> requires <strong>WooCommerce</strong> plugin to work normally. Please activate it or install it from <a href=\"http://wordpress.org/plugins/woocommerce/\" target=\"_blank\">here</a>.<br /><br />Back to the WordPress <a href='".get_admin_url(null, 'plugins.php')."'>Plugins page</a>." );
                }
            }
            $user_id = $current_user->ID;
            /* If user clicks to ignore the notice, add that to their user meta */
            $notices = array('ignore_pp_ssl', 'ignore_pp_sandbox', 'ignore_pp_woo', 'ignore_pp_check', 'ignore_pp_donate');
            foreach ($notices as $notice)
                if ( isset($_GET[$notice]) && '0' == $_GET[$notice] ) {
                    add_user_meta($user_id, $notice, 'true', true);
                }
        }

        function admin_notices() {
            global $current_user, $pp_settings ;
            $user_id = $current_user->ID;

            $pp_pro = get_option('woocommerce_paypal_pro_settings');
            $pp_payflow = get_option('woocommerce_paypal_pro_payflow_settings');
            $pp_standard = get_option('woocommerce_paypal_settings');

            if (@$pp_pro['enabled']=='yes' || @$pp_payflow['enabled']=='yes') {
                // Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
                if ( get_option('woocommerce_force_ssl_checkout')=='no' && ! class_exists( 'WordPressHTTPS' ) && !get_user_meta($user_id, 'ignore_pp_ssl') )
                    echo '<div class="error"><p>' . sprintf(__('WooCommerce PayPal Payments Pro requires that the %sForce secure checkout%s option is enabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - PayPal Pro will only work in test mode. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), '<a href="'.admin_url('admin.php?page=woocommerce').'">', "</a>", '"'.add_query_arg("ignore_pp_ssl",0).'"', __("Hide this notice", 'paypal-for-woocommerce'))  . '</p></div>';
                if ((@$pp_pro['testmode']=='yes' || @$pp_payflow['testmode']=='yes' || @$pp_settings['testmode']=='yes') && !get_user_meta($user_id, 'ignore_pp_sandbox')) {
                    $testmodes = array();
                    if (@$pp_pro['enabled']=='yes' && @$pp_pro['testmode']=='yes') $testmodes[] = 'PayPal Pro';
                    if (@$pp_payflow['enabled']=='yes' && @$pp_payflow['testmode']=='yes') $testmodes[] = 'PayPal Pro PayFlow';
                    if (@$pp_settings['enabled']=='yes' && @$pp_settings['testmode']=='yes') $testmodes[] = 'PayPal Express';
                    $testmodes_str = implode(", ", $testmodes);
                    echo '<div class="error"><p>' . sprintf(__('WooCommerce PayPal Payments ( %s ) is currently running in Sandbox mode and will NOT process any actual payments. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), $testmodes_str, '"'.add_query_arg("ignore_pp_sandbox",0).'"',  __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
                }
            } elseif (@$pp_settings['enabled']=='yes'){
                if (@$pp_settings['testmode']=='yes' && !get_user_meta($user_id, 'ignore_pp_sandbox')) {
                    echo '<div class="error"><p>' . sprintf(__('WooCommerce PayPal Express is currently running in Sandbox mode and will NOT process any actual payments. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), '"'.add_query_arg("ignore_pp_sandbox",0).'"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
                }
            }
            if(@$pp_settings['enabled']=='yes' && @$pp_standard['enabled']=='yes' && !get_user_meta($user_id, 'ignore_pp_check')){
                echo '<div class="error"><p>' . sprintf(__('You currently have both PayPal (standard) and Express Checkout enabled.  It is recommended that you disable the standard PayPal from <a href="'.get_admin_url().'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_paypal">the settings page</a> when using Express Checkout. | <a href=%s>%s</a>', 'paypal-for-woocommerce'), '"'.add_query_arg("ignore_pp_check",0).'"', __("Hide this notice", 'paypal-for-woocommerce')) . '</p></div>';
            }

            if ( !in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) && !get_user_meta($user_id, 'ignore_pp_woo')) {
                echo '<div class="error"><p>' . sprintf( __("WooCommerce PayPal Payments requires WooCommerce plugin to work normally. Please activate it or install it from <a href='http://wordpress.org/plugins/woocommerce/' target='_blank'>here</a>. | <a href=%s>%s</a>", 'paypal-for-woocommerce'), '"'.add_query_arg("ignore_pp_woo",0).'"', __("Hide this notice", 'paypal-for-woocommerce') ) . '</p></div>';
            }
        }

        //init function
        function init(){
            if (!class_exists("WC_Payment_Gateway")) return;
            load_plugin_textdomain('paypal-for-woocommerce', false, dirname(plugin_basename(__FILE__)). '/i18n/languages/');
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
            if ( ! is_admin() && is_cart())
                wp_enqueue_style( 'ppe_cart', plugins_url( 'assets/css/cart.css' , __FILE__ ) );

            if ( ! is_admin() && is_checkout() && @$pp_settings['enabled']=='yes' && @$pp_settings['show_on_checkout']=='yes' )
                wp_enqueue_style( 'ppe_checkout', plugins_url( 'assets/css/checkout.css' , __FILE__ ) );
            if ( ! is_admin() && is_single() && @$pp_settings['enabled']=='yes' && @$pp_settings['show_on_product_page']=='yes'){
                wp_enqueue_style( 'ppe_single', plugins_url( 'assets/css/single.css' , __FILE__ ) );
            }

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

        /**
         * Run when plugin is activated
         */
        function activate_paypal_for_woocommerce()
        {
            // If WooCommerce is not enabled, deactivate plugin.
            if(!in_array( 'woocommerce/woocommerce.php',apply_filters('active_plugins',get_option('active_plugins'))))
            {
                deactivate_plugins(plugin_basename(__FILE__));
            }
            else
            {
                global $woocommerce;
                //include_once $woocommerce->plugin_path() . '/admin/woocommerce-admin-install.php';

                // Create review page for Express Checkout
                wc_create_page(esc_sql(_x('review-order','page_slug','woocommerce')),'woocommerce_review_order_page_id',__('Checkout &rarr; Review Order','paypal-for-woocommerce'),'[woocommerce_review_order]',wc_get_page_id('checkout'));

                // Log activation in Angell EYE database via web service.
                $log_url = $_SERVER['HTTP_HOST'];
                $log_plugin_id = 1;
                $log_activation_status = 1;
                wp_remote_request('http://www.angelleye.com/web-services/wordpress/update-plugin-status.php?url='.$log_url.'&plugin_id='.$log_plugin_id.'&activation_status='.$log_activation_status);
            }
        }

        /**
         * Run when plugin is deactivated.
         */
        function deactivate_paypal_for_woocommerce()
        {
            // Log activation in Angell EYE database via web service.
            $log_url = $_SERVER['HTTP_HOST'];
            $log_plugin_id = 1;
            $log_activation_status = 0;
            wp_remote_request('http://www.angelleye.com/web-services/wordpress/update-plugin-status.php?url='.$log_url.'&plugin_id='.$log_plugin_id.'&activation_status='.$log_activation_status);
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
            if ( ! empty( $_GET['pp_action'] ) && $_GET['pp_action'] == 'revieworder' ) {
                $woocommerce_ppe = new WC_Gateway_PayPal_Express_AngellEYE();
                $woocommerce_ppe->paypal_express_checkout();
            }
        }

        /**
         * Javascript code to move it in to button add to cart wrap
         */
        function buy_now_button_js() {
            global $pp_settings;
            if (@$pp_settings['enabled']=='yes' && @$pp_settings['show_on_product_page']=='yes')
            {
                ?>
                <script type="text/javascript">
                    jQuery(document).ready(function(){
                        jQuery('input.single_variation_wrap').appendTo(".variations_button");
                    });
                </script>
            <?php
            }
        }

        /**
         * Display Paypal Express Checkout on product page
         */
        function buy_now_button() {
            global $pp_settings, $post;
            if (@$pp_settings['enabled']=='yes' && @$pp_settings['show_on_product_page']=='yes')
            {
                $_product = get_product($post->ID);
                $hide = '';
                if($_product->product_type == 'variation' ||
                    $_product->is_type('external'))
                {
                    $hide = 'display:none;';
                }

                if (empty($pp_settings['checkout_with_pp_button_type'])) $pp_settings['checkout_with_pp_button_type']='paypalimage';
                switch($pp_settings['checkout_with_pp_button_type'])
                {
                    case "textbutton":
                        $add_to_cart_action = add_query_arg( 'express_checkout', '1');
                        echo '<div id="paypal_ec_button_product">';
                        echo '<input type="submit" style="float:left;margin-left:10px;',$hide,'" class="single_variation_wrap paypal_checkout_button button alt" name="express_checkout"  onclick="',"jQuery('form.cart').attr('action','",$add_to_cart_action,"');jQuery('form.cart').submit();",'" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') .'"/>';
                        echo '</div>';
                        echo '<div class="clear"></div>';
                        break;
                    case "paypalimage":
                        $button_locale_code = defined(WPLANG) && WPLANG != '' ? WPLANG : 'en_US';
                        $button_img =  "https://www.paypal.com/".$button_locale_code."/i/btn/btn_xpressCheckout.gif";
                        echo '<div id="paypal_ec_button_product">';
                        echo '<input type="image" src="',$button_img,'" style="float:left;margin-left:10px;',$hide,'" class="single_variation_wrap" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') .'"/>';
                        echo '</div>';
                        echo '<div class="clear"></div>';
                        break;
                    case "customimage":
                        if(!empty($pp_settings['checkout_with_pp_button_type']))
                        {
                            $button_img = $pp_settings['checkout_with_pp_button_type_my_custom'];
                        }
                        else
                        {
                            $button_locale_code = defined(WPLANG) && WPLANG != '' ? WPLANG : 'en_US';
                            $button_img =  "https://www.paypal.com/".$button_locale_code."/i/btn/btn_xpressCheckout.gif";
                        }
                        echo '<div id="paypal_ec_button_product">';
                        echo '<input type="image" src="',$button_img,'" style="float:left;margin-left:10px;',$hide,'" class="single_variation_wrap" name="express_checkout" value="' . __('Pay with PayPal', 'paypal-for-woocommerce') .'"/>';
                        echo '</div>';
                        echo '<div class="clear"></div>';
                        break;
                }
            }

        }

        /**
         * Redirect to PayPal from the product page EC button
         * @param $url
         * @return string
         */
        function add_to_cart_redirect($url) {
            if (isset($_REQUEST['express_checkout'])||isset($_REQUEST['express_checkout_x'])){
                $url = add_query_arg( 'pp_action', 'expresscheckout', add_query_arg( 'wc-api', 'WC_Gateway_PayPal_Express_AngellEYE', home_url( '/' ) ) ) ;
            }
            return $url;
        }

        /**
         * Donate function
         */
        function donate_message() {
            if (@$_GET['page']=='wc-settings' && @$_GET['tab']=='checkout' && in_array( @$_GET['section'], array('wc_gateway_paypal_express_angelleye', 'wc_gateway_paypal_pro_angelleye', 'wc_gateway_paypal_pro_payflow_angelleye')) && !get_user_meta(get_current_user_id(), 'ignore_pp_donate') ) {
                ?>
                <div class="updated donation">
                    <a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SG9SQU2GBXJNA"><img style="float:left;margin-right:10px;" src="https://www.angelleye.com/images/paypal-for-woocommerce/donate-button.png" border="0" alt="PayPal - The safer, easier way to pay online!"></a>
                    <p>We are learning why it is difficult to provide, support, and maintain free software. Every little bit helps and is greatly appreciated. </p>
                    <p>Developers, join us on <a href="https://github.com/angelleye/paypal-woocommerce" target="_blank">GitHub</a>. Pull Requests are welcomed!</p>
                    <a style="float:right;" href="<?php echo add_query_arg("ignore_pp_donate",0);?>">x <?php echo __("Hide", 'paypal-for-woocommerce');?></a>
                    <div style="clear:both"></div>
                </div>
            <?php
            }
        }
    }
}
new AngellEYE_Gateway_Paypal();