<?php
/**
 * @var array $jsonResponse
 */
if ($jsonResponse['status']) {
    $domain_validation_file = $this->apple_pay_domain_validation->getDomainAssociationFilePath(true);
    ?>
    <h4 class="center"><?php echo __('Add Apple Pay Domain', 'paypal-for-woocommerce') ?></h4>
    <p class="no-padding no-margin">
        <?php echo __('Please ensure that the following link is accessible in order to verify the domain for Apple Pay.', 'paypal-for-woocommerce') ?><br />
        <a target="_blank" href="<?php echo $domain_validation_file ?>"><?php echo $domain_validation_file ?></a>
    </p>
    <div class="apple-pay-domain-add-form">
        <form method="post" action="<?php echo add_query_arg(['action' => 'angelleye_register_apple_pay_domain'], admin_url('admin-ajax.php')) ?>" class="angelleye_apple_pay_ajax_form_submit">
            <label>Domain Name: </label><input type="text" name="apple_pay_domain" value="<?php echo parse_url(get_site_url(), PHP_URL_HOST) ?>">
            <input type="submit" value="Add Domain" class="wplk-button button-primary submit_btn">
        </form>
    </div>
    <div class="separator"></div>
    <h4 class="center"><?php echo __('Apple Pay Domains in your PayPal Account', 'paypal-for-woocommerce') ?></h4>
    <table class="wp-list-table widefat fixed striped table-view-list apple-pay-domain-listing-table">
        <tr><th><?php echo __('Domain Name', 'paypal-for-woocommerce') ?></th><th><?php echo __('Action', 'paypal-for-woocommerce') ?></th></tr>
        <?php
        if (count($jsonResponse['domains'])) {
            foreach ($jsonResponse['domains'] as $domain) { ?>
                <tr>
                    <td><?php echo $domain['domain'] ?></td>
                    <td><a class="angelleye_apple_pay_remove_api_call"
                           href="<?php echo add_query_arg(['domain' => $domain['domain'], 'action' => 'angelleye_remove_apple_pay_domain'], admin_url('admin-ajax.php')) ?>"><?php echo __('Delete', 'paypal-for-woocommerce'); ?></a>
                    </td>
                </tr>
            <?php }
        } else {
            echo '<tr><td colspan="2">'.__('No domains registered yet.', 'paypal-for-woocommerce').'</td></tr>';
        }?>
    </table>

    <?php
} else {
    echo '<div class="error">'.$jsonResponse['message'].'</div>';
}
