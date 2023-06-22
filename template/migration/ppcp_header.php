<?php

$angelleye_ppcp_migration_wizard_notice_data = get_option('angelleye_ppcp_migration_wizard_notice_key');

if (!empty($angelleye_ppcp_migration_wizard_notice_data)) {
    foreach ($angelleye_ppcp_migration_wizard_notice_data as $type => $message_data) {
        foreach ($message_data as $key => $value) {
            display_ppcp_notice($type, $value);
        }
    }

    delete_option('angelleye_ppcp_migration_wizard_notice_key');
}

function display_ppcp_notice($type, $message) {
    try {
        echo '<div class="notice notice-' . $type . ' angelleye-notice" style="display:none;"><div class="angelleye-notice-logo"><span></span></div><div class="angelleye-notice-message"><p style="padding-top: 5px;padding-bottom: 5px;line-height: 24px;">' . $message . '</p></div><div class="angelleye-notice-cta"><button class="angelleye-notice-dismiss">Dismiss</button></div></div>';
    } catch (Exception $ex) {
        
    }
}
