<?php

/**
 * @since      1.0.0
 * @package    AngellEYE_PayPal_PPCP_Migration
 * @subpackage AngellEYE_PayPal_PPCP_Migration/includes
 * @author     AngellEYE <andrew@angelleye.com>
 */
defined('ABSPATH') || exit;

class AngellEYE_PayPal_PPCP_Migration {

    protected static $_instance = null;

    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function angelleye_ppcp_paypal_express_to_ppcp($seller_onboarding_status) {
        try {
            $this->angelleye_express_checkout_setting_field_map();
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_pro_to_ppcp($seller_onboarding_status) {
        try {
            
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_pro_payflow_to_ppcp($seller_onboarding_status) {
        try {
            
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_advanced_to_ppcp($seller_onboarding_status) {
        try {
            
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_ppcp_paypal_credit_card_rest_to_ppcp($seller_onboarding_status) {
        try {
            
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_express_checkout_setting_field_map() {
        try {
            $woocommerce_paypal_express_settings = get_option('woocommerce_paypal_express_settings');
            $woocommerce_angelleye_ppcp_settings = get_option('woocommerce_angelleye_ppcp_settings');
            $map_fields_list = array('enable_cart_button' => 'show_on_cart',
                'cart_button_position' => 'button_position',
                'enable_paypal_checkout_page' => 'show_on_checkout',
                'checkout_page_display_option' => 'show_on_checkout',
                'enable_product_button' => 'show_on_product_page',
                'landing_page' => 'landing_page',
                'brand_name' => 'brand_name',
                'soft_descriptor' => 'softdescriptor',
                'error_email_notification' => 'error_email_notify',
                'invoice_prefix' => 'invoice_id_prefix',
                'skip_final_review' => 'skip_final_review',
                'disable_term' => 'disable_term',
                'paymentaction' => 'payment_action',
                'set_billing_address' => 'billing_address',
                'send_items' => 'send_items',
                'debug' => 'debug',
                'product_style_color' => 'button_color',
                'cart_style_color' => 'button_color',
                'checkout_style_color' => 'button_color',
                'product_style_shape' => 'button_shape',
                'cart_style_shape' => 'button_shape',
                'checkout_style_shape' => 'button_shape',
                'product_button_tagline' => 'button_tagline',
                'cart_button_tagline' => 'button_tagline',
                'checkout_button_tagline' => 'button_tagline',
                'single_product_configure_settings' => '',
                'product_button_layout' => 'single_product_button_layout',
                'product_button_size' => 'single_product_button_size',
                'product_button_height' => 'single_product_button_height',
                'product_button_label' => 'single_product_button_label',
                'product_disallowed_funding_methods' => 'single_product_disallowed_funding_methods',
                'cart_configure_settings' => '',
                'cart_button_layout' => 'cart_button_layout',
                'cart_button_size' => 'cart_button_size',
                'cart_button_height' => 'cart_button_height',
                'cart_button_label' => 'cart_button_label',
                'cart_disallowed_funding_methods' => 'cart_disallowed_funding_methods',
                'checkout_page_configure_settings' => '',
                'checkout_disable_smart_button' => 'checkout_page_disable_smart_button',
                'checkout_button_layout' => 'checkout_page_button_layout',
                'checkout_button_size' => 'checkout_page_button_size',
                'checkout_button_height' => 'checkout_page_button_height',
                'checkout_button_label' => 'checkout_page_button_label',
                'checkout_disallowed_funding_methods' => 'checkout_page_disallowed_funding_methods',
                'enabled_pay_later_messaging' => 'enabled_credit_messaging',
                'pay_later_messaging_page_type' => 'credit_messaging_page_type',
                'pay_later_messaging_home_layout_type' => 'credit_messaging_home_layout_type',
                'pay_later_messaging_home_text_layout_logo_type' => 'credit_messaging_home_text_layout_logo_type',
                'pay_later_messaging_home_text_layout_logo_position' => 'credit_messaging_home_text_layout_logo_position',
                'pay_later_messaging_home_text_layout_text_size' => 'credit_messaging_home_text_layout_text_size',
                'pay_later_messaging_home_text_layout_text_color' => 'credit_messaging_home_text_layout_text_color',
                'pay_later_messaging_home_flex_layout_color' => 'credit_messaging_home_flex_layout_color',
                'pay_later_messaging_home_flex_layout_ratio' => 'credit_messaging_home_flex_layout_ratio',
                'pay_later_messaging_home_shortcode' => 'credit_messaging_home_shortcode',
                'pay_later_messaging_category_layout_type' => 'credit_messaging_category_layout_type',
                'pay_later_messaging_category_text_layout_logo_type' => 'credit_messaging_category_text_layout_logo_type',
                'pay_later_messaging_category_text_layout_logo_position' => 'credit_messaging_category_text_layout_logo_position',
                'pay_later_messaging_category_text_layout_text_size' => 'credit_messaging_category_text_layout_text_size',
                'pay_later_messaging_category_text_layout_text_color' => 'credit_messaging_category_text_layout_text_color',
                'pay_later_messaging_category_flex_layout_color' => 'credit_messaging_category_flex_layout_color',
                'pay_later_messaging_category_flex_layout_ratio' => 'credit_messaging_category_flex_layout_ratio',
                'pay_later_messaging_category_shortcode' => 'credit_messaging_category_shortcode',
                'pay_later_messaging_product_layout_type' => 'credit_messaging_product_layout_type',
                'pay_later_messaging_product_text_layout_logo_type' => 'credit_messaging_product_text_layout_logo_type',
                'pay_later_messaging_product_text_layout_logo_position' => 'credit_messaging_product_text_layout_logo_position',
                'pay_later_messaging_product_text_layout_text_size' => 'credit_messaging_product_text_layout_text_size',
                'pay_later_messaging_product_text_layout_text_color' => 'credit_messaging_product_text_layout_text_color',
                'pay_later_messaging_product_flex_layout_color' => 'credit_messaging_product_flex_layout_color',
                'pay_later_messaging_product_flex_layout_ratio' => 'credit_messaging_product_flex_layout_ratio',
                'pay_later_messaging_product_shortcode' => 'credit_messaging_product_shortcode',
                'pay_later_messaging_cart_layout_type' => 'credit_messaging_cart_layout_type',
                'pay_later_messaging_cart_text_layout_logo_type' => 'credit_messaging_cart_text_layout_logo_type',
                'pay_later_messaging_cart_text_layout_logo_position' => 'credit_messaging_cart_text_layout_logo_position',
                'pay_later_messaging_cart_text_layout_text_size' => 'credit_messaging_cart_text_layout_text_size',
                'pay_later_messaging_cart_text_layout_text_color' => 'credit_messaging_cart_text_layout_text_color',
                'pay_later_messaging_cart_flex_layout_color' => 'credit_messaging_cart_flex_layout_color',
                'pay_later_messaging_cart_flex_layout_ratio' => 'credit_messaging_cart_flex_layout_ratio',
                'pay_later_messaging_cart_shortcode' => 'credit_messaging_cart_shortcode',
                'pay_later_messaging_payment_layout_type' => 'credit_messaging_payment_layout_type',
                'pay_later_messaging_payment_text_layout_logo_type' => 'credit_messaging_payment_text_layout_logo_type',
                'pay_later_messaging_payment_text_layout_logo_position' => 'credit_messaging_payment_text_layout_logo_position',
                'pay_later_messaging_payment_text_layout_text_size' => 'credit_messaging_payment_text_layout_text_size',
                'pay_later_messaging_payment_text_layout_text_color' => 'credit_messaging_payment_text_layout_text_color',
                'pay_later_messaging_payment_flex_layout_color' => 'credit_messaging_payment_flex_layout_color',
                'pay_later_messaging_payment_flex_layout_ratio' => 'credit_messaging_payment_flex_layout_ratio',
                'pay_later_messaging_payment_shortcode' => 'credit_messaging_payment_shortcode'
            );
            if (!empty($woocommerce_paypal_express_settings)) {
                if (isset($woocommerce_paypal_express_settings['single_product_configure_settings']) && $woocommerce_paypal_express_settings['single_product_configure_settings'] === '') {
                    $map_fields_list['product_button_layout'] = 'button_layout';
                    $map_fields_list['product_button_size'] = 'button_size';
                    $map_fields_list['product_button_height'] = 'button_height';
                    $map_fields_list['product_button_label'] = 'button_label';
                    $map_fields_list['product_disallowed_funding_methods'] = 'disallowed_funding_methods';
                }
                if (isset($woocommerce_paypal_express_settings['cart_configure_settings']) && $woocommerce_paypal_express_settings['cart_configure_settings'] === '') {
                    $map_fields_list['cart_button_layout'] = 'button_layout';
                    $map_fields_list['cart_button_size'] = 'button_size';
                    $map_fields_list['cart_button_height'] = 'button_height';
                    $map_fields_list['cart_button_label'] = 'button_label';
                    $map_fields_list['cart_disallowed_funding_methods'] = 'disallowed_funding_methods';
                }
                if (isset($woocommerce_paypal_express_settings['checkout_page_configure_settings']) && $woocommerce_paypal_express_settings['checkout_page_configure_settings'] === '') {
                    $map_fields_list['checkout_button_layout'] = 'button_layout';
                    $map_fields_list['checkout_button_size'] = 'button_size';
                    $map_fields_list['checkout_button_height'] = 'button_height';
                    $map_fields_list['checkout_button_label'] = 'button_label';
                    $map_fields_list['checkout_disallowed_funding_methods'] = 'disallowed_funding_methods';
                }
                foreach ($map_fields_list as $key => $value) {
                    if (isset($woocommerce_paypal_express_settings[$value]) && !empty($woocommerce_paypal_express_settings[$value])) {
                        $woocommerce_angelleye_ppcp_settings[$key] = $woocommerce_paypal_express_settings[$value];
                    }
                }
                if (isset($woocommerce_paypal_express_settings['show_on_checkout']) && $woocommerce_paypal_express_settings['show_on_checkout'] !== 'no') {
                    $woocommerce_angelleye_ppcp_settings['enable_paypal_checkout_page'] = 'yes';
                } else {
                    $woocommerce_angelleye_ppcp_settings['enable_paypal_checkout_page'] = '';
                }
                $woocommerce_paypal_express_settings['enabled'] = 'no';
                update_option('woocommerce_paypal_express_settings', $woocommerce_paypal_express_settings);
                update_option('woocommerce_angelleye_ppcp_settings', $woocommerce_angelleye_ppcp_settings);
            }
        } catch (Exception $ex) {
            
        }
    }

    public function angelleye_express_checkout_setting_list() {
        
    }

}
