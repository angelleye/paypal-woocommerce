<?php
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
/**
 * Functions used by plugins
 */

/**
 * Queue updates for the Angell EYE Updater
 */
if (!function_exists('angelleye_queue_update')) {

    function angelleye_queue_update($file, $file_id, $product_id) {
        global $angelleye_queued_updates;

        if (!isset($angelleye_queued_updates))
            $angelleye_queued_updates = array();

        $plugin = new stdClass();
        $plugin->file = $file;
        $plugin->file_id = $file_id;
        $plugin->product_id = $product_id;

        $angelleye_queued_updates[] = $plugin;
    }

}

/**
 * Get log path - Resolved wc_get_log_file_path() deprecation warnings.
 */
if (!function_exists('angelleye_get_log_path')) {
    function angelleye_get_log_path($handle)
    {
        return WC_Log_Handler_File::get_log_file_path($handle);
    }
}

/**
 * Load installer for the AngellEYE Updater.
 * @return $api Object
 */
if (!class_exists('AngellEYE_Updater') && !function_exists('angell_updater_install')) {

    function angell_updater_install($api, $action, $args) {
        $download_url = AEU_ZIP_URL;

        if ('plugin_information' != $action ||
                false !== $api ||
                !isset($args->slug) ||
                'angelleye-updater' != $args->slug
        )
            return $api;

        $api = new stdClass();
        $api->name = 'AngellEYE Updater';
        $api->version = '';
        $api->download_link = esc_url($download_url);
        return $api;
    }

    add_filter('plugins_api', 'angell_updater_install', 10, 3);
}

/**
 * AngellEYE Installation Prompts
 */
if (!class_exists('AngellEYE_Updater') && !function_exists('angell_updater_notice')) {

    /**
     * Display a notice if the "AngellEYE Updater" plugin hasn't been installed.
     * @return void
     */
    function angell_updater_notice() {
        $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));
        if (in_array('angelleye-updater/angelleye-updater.php', $active_plugins))
            return;

        $slug = 'angelleye-updater';
        $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $slug), 'install-plugin_' . $slug);
        $activate_url = 'plugins.php?action=activate&plugin=' . urlencode('angelleye-updater/angelleye-updater.php') . '&plugin_status=all&paged=1&s&_wpnonce=' . urlencode(wp_create_nonce('activate-plugin_angelleye-updater/angelleye-updater.php'));

        $message = '<a href="' . esc_url($install_url) . '">Install the Angell EYE Updater plugin</a> to get updates for your Angell EYE plugins.';
        $is_downloaded = false;
        $plugins = array_keys(get_plugins());
        foreach ($plugins as $plugin) {
            if (strpos($plugin, 'angelleye-updater.php') !== false) {
                $is_downloaded = true;
                $message = '<a href="' . esc_url(admin_url($activate_url)) . '"> Activate the Angell EYE Updater plugin</a> to get updates for your Angell EYE plugins.';
            }
        }
        echo '<div id="angelleye-updater-notice" class="updated notice updater-dismissible"><p>' . $message . '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>' . "\n";
    }

    function angelleye_updater_dismissible_admin_notice() {
        set_transient( 'angelleye_updater_notice_hide', 'yes', MONTH_IN_SECONDS );
    }
    if ( false === ( $angelleye_updater_notice_hide = get_transient( 'angelleye_updater_notice_hide' ) ) ) {
        add_action('admin_notices', 'angell_updater_notice');
    }
    add_action( 'wp_ajax_angelleye_updater_dismissible_admin_notice', 'angelleye_updater_dismissible_admin_notice' );
}

if (!function_exists('ae_get_shop_order_screen_id')) {
    function ae_get_shop_order_screen_id()
    {
        return wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
            ? wc_get_page_screen_id('shop-order') : 'shop_order';
    }
}

if (!function_exists('ae_is_active_screen')) {
    /**
     * Returns True if the current active screen matches to one of the array elements
     * @param string $screen
     * @return bool
     */
    function ae_is_active_screen(string $screen): bool
    {
        $current_screen = get_current_screen();
        $screen_id = $current_screen ? $current_screen->id : '';
        return $screen_id == $screen;
    }
}
