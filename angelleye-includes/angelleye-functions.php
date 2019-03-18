<?php

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
        echo '<div class="updated fade"><p>' . $message . '</p></div>' . "\n";
    }

    add_action('admin_notices', 'angell_updater_notice');
}