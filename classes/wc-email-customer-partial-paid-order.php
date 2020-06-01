<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

if (!class_exists('WC_Email_Partially_Paid_Order', false)) :

    class WC_Email_Partially_Paid_Order extends WC_Email {

        public function __construct() {
            $this->id = 'customer_partially_paid_order';
            $this->customer_email = true;

            $this->title = __('Partially Paid order', 'paypal-for-woocommerce');
            $this->description = __('This is an order notification sent to customers containing order details after partially paid.', 'paypal-for-woocommerce');
            $this->template_html = PAYPAL_FOR_WOOCOMMERCE_DIR_PATH . '/template/emails/angelleye-customer-partial-paid-order.php';
            $this->template_plain = PAYPAL_FOR_WOOCOMMERCE_DIR_PATH . '/template/emails/plain/angelleye-customer-partial-paid-order.php';
            $this->placeholders = array(
                '{order_date}' => '',
                '{order_number}' => '',
            );

            // Triggers for this email.
            add_action('woocommerce_order_status_cancelled_to_processing_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_failed_to_processing_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_on-hold_to_processing_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_pending_to_processing_notification', array($this, 'trigger'), 10, 2);

            // Call parent constructor.
            parent::__construct();
        }

        public function get_default_subject() {
            return __('Your {site_title} order has been received!', 'paypal-for-woocommerce');
        }

        public function get_default_heading() {
            return __('Thank you for your order', 'paypal-for-woocommerce');
        }

        public function trigger($order_id, $order = false) {
            $this->setup_locale();

            if ($order_id && !is_a($order, 'WC_Order')) {
                $order = wc_get_order($order_id);
            }

            if (is_a($order, 'WC_Order')) {
                $this->object = $order;
                $this->recipient = $this->object->get_billing_email();
                $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
                $this->placeholders['{order_number}'] = $this->object->get_order_number();
            }

            if ($this->is_enabled() && $this->get_recipient()) {
                $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
            }

            $this->restore_locale();
        }

        public function get_content_html() {
            return wc_get_template_html(
                    $this->template_html, array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this,
                    )
            );
        }

        public function get_content_plain() {
            return wc_get_template_html(
                    $this->template_plain, array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'additional_content' => $this->get_additional_content(),
                'sent_to_admin' => false,
                'plain_text' => true,
                'email' => $this,
                    )
            );
        }

        public function get_default_additional_content() {
            return __('Thanks for using {site_address}!', 'paypal-for-woocommerce');
        }

    }

    endif;

return new WC_Email_Partially_Paid_Order();
