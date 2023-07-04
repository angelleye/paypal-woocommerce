<?php

class AngellEye_PayPal_PPCP_Apple_Domain_Validation {
    use AngellEye_PPCP_Core;

    private AngellEye_WordPress_Custom_Route_Handler $route_handler;

    public function __construct()
    {
        $this->angelleye_ppcp_load_class();
        $this->route_handler = AngellEye_WordPress_Custom_Route_Handler::instance();
        add_action('plugins_loaded', [$this, 'addDomainValidationRoute'], 100);
    }

    public function addDomainValidationRoute()
    {
        $file_name = $this->getDomainAssociationFilePath();
        if (!empty($file_name)) {
            $this->route_handler->addRoute($file_name, [$this, 'domainAssociationFile']);
        }
    }

    public function domainAssociationFile(): void
    {
        if ($this->isSandbox()) {
            $file_path = '/ppcp-gateway/lib/apple-validation/domain-association-file-sandbox.txt';
        } else {
            $file_path = '/ppcp-gateway/lib/apple-validation/apple-developer-merchantid-domain-association.txt';
        }
        header('Content-type: text/plain');
        ob_start();
        include PAYPAL_FOR_WOOCOMMERCE_PLUGIN_DIR . $file_path;
        $contents = ob_get_clean();
        echo $contents;
        exit();
    }

    public function getDomainAssociationFilePath($include_site_url = false): string
    {
        $file_name = $this->isSandbox() ? 'domain-association-file-sandbox' : 'apple-developer-merchantid-domain-association';
        if ('yes' === $this->setting_obj->get('enable_apple_pay', 'yes')) {
            return (!empty($include_site_url) ? get_bloginfo('url') . '/' : '')  . '.well-known/' . $file_name;
        }
        return '';
    }

}
