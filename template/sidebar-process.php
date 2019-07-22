<?php

add_action('wp_ajax_angelleye_marketing_sendy_subscription', 'own_angelleye_marketing_sendy_subscription');

function own_angelleye_marketing_sendy_subscription() {
    $url = 'https://sendy.angelleye.com/subscribe';
    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'timeout' => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array( 'list' => 'pjFolYKqSdLe57i4uuUz0g', 'boolean' => 'true', 'email' => $_POST['email'], 'gdpr' =>  'true', 'silent' => 'true'),
        'cookies' => array()
            )
    );
    if (is_wp_error($response)) {
        wp_send_json(wp_remote_retrieve_body($response));
    } else {
        $body = wp_remote_retrieve_body($response);
        $apiResponse = strval($body);
        switch ( $apiResponse ) {
            case 'true':
            case '1':
                prepareResponse( "true", 'Thank you for subscribing!' );
            case 'Already subscribed.':
                prepareResponse( "true", 'Already subscribed!' );
            default:
                prepareResponse( "false", $apiResponse );
        }
    }
    
    
}

function prepareResponse( $status = false, $msg = 'Something went wrong!' ) {
        $return = array(
                'result'  => $status,
                'message'       => $msg
            );
        wp_send_json($return);
    }