<?php

add_action('wp_ajax_angelleye_marketing_mailchimp_subscription', 'own_angelleye_marketing_mailchimp_subscription');

function own_angelleye_marketing_mailchimp_subscription() {
    $url = 'https://angelleye.us7.list-manage.com/subscribe/post-json?u=4c6d56be138a966cd573c928f&id=84c27e6cd1';
    $url = add_query_arg(array('EMAIL' => $_POST['email']), $url);
    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array(),
        'cookies' => array()
            )
    );
    if (is_wp_error($response)) {
        wp_send_json(wp_remote_retrieve_body($response));
    } else {
        update_user_meta(get_current_user_id(), 'enable_mailchimp_subscription', 'yes');
        wp_send_json(wp_remote_retrieve_body($response));
    }
}