<?php
/**
 * Review Order
 */

global $woocommerce;
$checked = get_option('woocommerce_enable_guest_checkout');
$checkout_form_data = maybe_unserialize(WC()->session->checkout_form);
$hide_button = false;
### After PayPal payment method confirmation, user is redirected back to this page with token and Payer ID ###

$frm_act = add_query_arg(array( 'pp_action' => 'payaction'));
$is_paypal_express = true;
$result = unserialize(WC()->session->RESULT);
$email = ( isset($_POST['email']) && !empty($_POST['email'])) ? $_POST['email'] : (isset($result['EMAIL']) && !empty($result['EMAIL'])) ? $result['EMAIL'] : '';
if (!isset(WC()->session->TOKEN)) {
    $ms = sprintf(__('Sorry, your session has expired. <a href=%s>Return to homepage &rarr;</a>', 'paypal-for-woocommerce'), '"' . home_url() . '"');
    $ec_confirm_message = apply_filters('angelleye_ec_confirm_message', $ms);
    wc_add_notice($ec_confirm_message, "error");
    $hide_button = true;
}
//Add hook to show login form or not
$show_login = apply_filters('paypal-for-woocommerce-show-login', $is_paypal_express && !is_user_logged_in() && $checked==="no" );

//Add hook to show create account form
$show_act = apply_filters('paypal-for-woocommerce-show-login', $is_paypal_express && !is_user_logged_in() && $checked==="yes" && empty($checkout_form_data['billing_address_1']));

?>

<?php do_action( 'angelleye_review_order_before_checkout_form' );?>

<form class="angelleye_checkout" method="POST" action="<?php echo $frm_act;?>">
    <div class="wp_notice_own"></div>
    <?php wc_print_notices();?>

    <?php do_action( 'angelleye_review_order_before_cart_contents' );?>

    <div id="paypalexpress_order_review">
            <?php woocommerce_order_review();?>
    </div>

    <?php do_action( 'angelleye_review_order_after_cart_contents' );?>

<?php if ( WC()->cart->needs_shipping()  ) : ?>

    <?php do_action( 'angelleye_review_order_before_customer_details' );?>

    <div class="title">
        <h2><?php _e( 'Customer details', 'paypal-for-woocommerce' ); ?></h2>
    </div>

    <div class="col2-set addresses">

        <div class="col-1">

            <div class="title">
                <h3><?php _e( 'Shipping Address', 'paypal-for-woocommerce' ); ?></h3>
            </div>
            <div class="address">
                <p>
                    <?php
                    // Formatted Addresses
                    $address = array(
                    'first_name' 	=> WC()->customer->shiptoname,
                    'company'		=> WC()->customer->company,
                    'address_1'		=> WC()->customer->shipping_address_1,
                    'address_2'		=> WC()->customer->shipping_address_2,
                    'city'			=> WC()->customer->shipping_city,
                    'state'			=> WC()->customer->shipping_state,
                    'postcode'		=> WC()->customer->shipping_postcode,
                    'country'		=> WC()->customer->shipping_country
                    ) ;

                    echo WC()->countries->get_formatted_address( $address );
                    ?>
                </p>
            </div>

        </div><!-- /.col-1 -->
        <div class="col-2">
        	<?php 
        	$woocommerce_paypal_express_settings = maybe_unserialize(get_option('woocommerce_paypal_express_settings'));
        	// Formatted Addresses
        	$user_submit_form = maybe_unserialize(WC()->session->checkout_form);

        	if( (isset($user_submit_form) && !empty($user_submit_form)) && is_array($user_submit_form) ) {
        		if( isset($user_submit_form['ship_to_different_address']) && $user_submit_form['ship_to_different_address'] == true ) {
        			$billing_address = array(
        			'first_name' 	=> $user_submit_form['billing_first_name'],
        			'last_name'		=> $user_submit_form['billing_last_name'],
        			'company'		=> $user_submit_form['billing_company'],
        			'address_1'		=> $user_submit_form['billing_address_1'],
        			'address_2'		=> $user_submit_form['billing_address_2'],
        			'city'			=> $user_submit_form['billing_city'],
        			'state'			=> $user_submit_form['billing_state'],
        			'postcode'		=> $user_submit_form['billing_postcode'],
        			'country'		=> $user_submit_form['billing_country']
        			) ;
        		}
        	} else {

        		$billing_address = array(
        		'first_name' 	=> WC()->customer->firstname. ' '. WC()->customer->lastname,
        		'company'		=> WC()->customer->company,
        		'address_1'		=> WC()->customer->get_address(),
        		'address_2'		=> WC()->customer->get_address_2(),
        		'city'			=> WC()->customer->get_city(),
        		'state'			=> WC()->customer->get_state(),
        		'postcode'		=> WC()->customer->get_postcode(),
        		'country'		=> WC()->customer->get_country()
        		) ;
        	}

        	if( isset($billing_address) && !empty($billing_address) ) :
        		?>
        	

	            <div class="title">
	                <h3><?php _e( 'Billing Address', 'paypal-for-woocommerce' ); ?></h3>
	            </div>
	            <div class="address">
	                <p>
	                    
	                    <?php 
	                    echo WC()->countries->get_formatted_address( $billing_address );

	                    ?>
	                </p>
	            </div>

       
        	<?php endif; ?>
        </div><!-- /.col-2 -->
    </div><!-- /.col2-set -->

    <?php do_action( 'angelleye_review_order_after_customer_details' );?>
<?php endif; ?>
    <?php do_action( 'woocommerce_after_order_notes', WC()->checkout() ); ?>
<?php if ( $show_act ): ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            jQuery(".chkcreate_act").click(function(){
                var ischecked_act = jQuery('.chkcreate_act').is(':checked') ;

                if (ischecked_act == false) {
                    jQuery('.create_account_child').toggle();
                }else if(ischecked_act == true) {
                    jQuery('.create_account_child').toggle();
                }

            });
        });
    </script>

    <?php do_action( 'angelleye_review_order_before_create_account' );?>

    <div class="create-account" class="div_create_act" >
        <p class="form-row form-row-wide create-account div_create_act_para" style="cursor:pointer;">
            <input class="input-checkbox chkcreate_act" id="createaccount" type="checkbox" name="createaccount" value="1">
            <label for="createaccount" style="cursor:pointer;" class="checkbox lbl_chkcreate_act"><?php echo __('Create an account?', 'paypal-for-woocommerce');?></label>
        </p>
        <div class="create_account_child" style="display:none;">
            <p><?php echo __('Create an account by entering the information below. If you are a returning customer please login at the top of the page.', 'paypal-for-woocommerce');?></p>
            <p class="form-row validate-required">
                <label for="paypalexpress_order_review_email">Email:<span class="required">*</span></label>
                <input style="width: 100%;" type="email" name="email" id="paypalexpress_order_review_email" value="<?php echo $email; ?>" />
            </p>
            <p class="form-row validate-required woocommerce-validated form-row-last" id="account_password_field">
                <label for="account_password" class=""><?php echo __('Account password', 'paypal-for-woocommerce');?><abbr class="required" title="required">*</abbr>
                </label>
                <input type="password" class="input-text" placeholder="Password" value="" name="create_act"/>
            </p>

            <div class="clear"></div>
        </div>
    </div>

    <?php do_action( 'angelleye_review_order_after_create_account' );?>

<?php endif;?>
<?php if ( $show_login ):  ?>
</form>
    <style type="text/css">

        .woocommerce #content p.form-row input.button,
        .woocommerce #respond p.form-row input#submit,
        .woocommerce p.form-row a.button,
        .woocommerce p.form-row button.button,
        .woocommerce p.form-row input.button,
        .woocommerce-page p.form-row #content input.button,
        .woocommerce-page p.form-row #respond input#submit,
        .woocommerce-page p.form-row a.button,
        .woocommerce-page p.form-row button.button,
        .woocommerce-page p.form-row input.button{
            display: block !important;
        }
    </style>

    <?php do_action( 'angelleye_review_order_before_login_create_account' );?>

    <div class="title">
        <h2><?php _e( 'Login', 'paypal-for-woocommerce' ); ?></h2>
    </div>
    <form name="" action="" method="post">
        <?php

        woocommerce_login_form(
            array(
            'message'  => 'Please login or create an account to complete your order.',
            'redirect' => AngellEYE_Gateway_Paypal::curPageURL(),
            'hidden'   => true
            )
        );
        ?>
    </form>
    <div class="title">
        <h2><?php _e( 'Create A New Account', 'paypal-for-woocommerce' ); ?></h2>
    </div>
    <form action="<?php echo add_query_arg(array( 'pp_action' => 'revieworder'));?>" method="post">
        <p class="form-row form-row-first">
            <label for="paypalexpress_order_review_username">Username:<span class="required">*</span></label>
            <input style="width: 100%;" type="text" name="username" id="paypalexpress_order_review_username" value="<?php echo @$_POST['username']; ?>" />
        </p>
        <p class="form-row form-row-last">
            <label for="paypalexpress_order_review_email">Email:<span class="required">*</span></label>
            <input style="width: 100%;" type="email" name="email" id="paypalexpress_order_review_email" value="<?php echo $email; ?>" />
        </p>
        <div class="clear"></div>
        <p class="form-row form-row-first">
            <label for="paypalexpress_order_review_password">Password:<span class="required">*</span></label>
            <input type="password" name="password" id="paypalexpress_order_review_password" class="input-text" />
        </p>
        <p class="form-row form-row-last">
            <label for="paypalexpress_order_review_repassword">Re Password:<span class="required">*</span></label>
            <input type="password" name="repassword" id="paypalexpress_order_review_repassword" class="input-text"/>
        </p>
        <div class="clear"></div>
        <p>
            <input class="button" type="submit" name="createaccount" value="Create Account" />
            <input type="hidden" name="address" value="<?php echo WC()->customer->get_address(); ?>">
        </p>
    </form>

    <?php do_action( 'angelleye_review_order_after_login_create_account' );?>

<?php elseif (!$hide_button):
    global $pp_settings;
    $cancel_url = isset( $pp_settings['cancel_page'] ) ? get_permalink( $pp_settings['cancel_page'] ) : $woocommerce->cart->get_cart_url();
    $cancel_url = apply_filters( 'angelleye_review_order_cance_url', $cancel_url );
    echo '<div class="clear"></div>';
    $cancel_button = '<p><a class="button angelleye_cancel" href="' . $cancel_url . '">'.__('Cancel order', 'paypal-for-woocommerce').'</a> ';

    if ($is_paypal_express && wc_get_page_id( 'terms' ) > 0 && apply_filters( 'woocommerce_checkout_show_terms', true ) && empty( $checkout_form_data['terms'] ) ){
?>
        <?php do_action( 'angelleye_review_order_before_place_order' );
        
        $gateways = WC()->payment_gateways()->payment_gateways();
        if($gateways[ 'paypal_express' ]->supports( 'tokenization' )) :
           echo sprintf(
			'<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
				<input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
				<label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
			</p>',
			esc_attr( 'paypal_express' ),
			esc_html__( 'Save PayPal Billing Agreement to Account', 'woocommerce' )
		);     
        endif;
        ?>

        <script type="text/javascript">
            jQuery(document).ready(function (){
                jQuery(".cls_place_order_own").click(function(){

                    var ischecked = jQuery('.terms_own').is(':checked') ;

                    if (ischecked == false) {
                        jQuery('.wp_notice_own').html('<div class="woocommerce-error"><?php echo __( 'You must accept our Terms &amp; Conditions.', 'paypal-for-woocommerce' );?></div>');
			    
			// Scroll to .wp_notice_own to better highlight form *error*!
			jQuery( "html, body" ).animate({
				scrollTop: ( jQuery( ".wp_notice_own" ).offset().top - 60 )
			}, 1000 );				    
			    
                        return false;
                    }else if (ischecked == true) {
                        jQuery('.wp_notice_own').html('');
                        jQuery(this).attr('disabled','disabled').val('Processing');

                        jQuery(this).parents('form').submit();
                        return true;
                    }



                });

            });
        </script>
        <style type="text/css">
            #payment{
                display:none;
            }
            .lbl_terms{
                float: left;
                display: inline-block !important;
                margin-right: 5px !important;
            }
            .terms_own
            {
                float: none;
                margin-top: 8px !important;
                display: inline-block !important;
            }
        </style>

        <p class="terms">
            	<label for="terms" class="checkbox lbl_terms">
		    <input type="checkbox" class="input-checkbox terms_own" name="terms" <?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); ?> id="terms" />
		    <?php printf( __( 'I&rsquo;ve read and accept the <a href="%s" class="terms_chkbox" target="_blank">terms &amp; conditions</a>', 'paypal-for-woocommerce' ), esc_url( wc_get_page_permalink( 'terms' ) ) ); ?>
		</label>   
		<?php do_action( 'angelleye_review_order_after_terms' );?>
        </p>
        <?php  echo $cancel_button;?>
        <input type="button" class="button cls_place_order_own" value="<?php echo  __( 'Place Order','paypal-for-woocommerce');?>" /></p>

        <?php do_action( 'angelleye_review_order_after_place_order' );?>

<?php
    } else {
        $gateways = WC()->payment_gateways()->payment_gateways();
        if($gateways[ 'paypal_express' ]->supports( 'tokenization' )) :
           echo sprintf(
			'<p class="form-row woocommerce-SavedPaymentMethods-saveNew">
				<input id="wc-%1$s-new-payment-method" name="wc-%1$s-new-payment-method" type="checkbox" value="true" style="width:auto;" />
				<label for="wc-%1$s-new-payment-method" style="display:inline;">%2$s</label>
			</p>',
			esc_attr( 'paypal_express' ),
			esc_html__( 'Save PayPal Billing Agreement to Account', 'woocommerce' )
		);     
        endif;
        do_action( 'angelleye_review_order_before_place_order' );

        echo $cancel_button;
        echo sprintf( '<input type="submit" class="button" onclick=" jQuery(this).attr(\'disabled\', \'disabled\').val(\'%1$s\'); jQuery(this).parents(\'form\').submit(); return false;" value="' . esc_attr__( 'Place Order', 'paypal-for-woocommerce' ) . '"/ ></p>' , esc_attr__( 'Processing', 'paypal-for-woocommerce' ) );
        do_action( 'angelleye_review_order_after_place_order' );
    }
    ?>
    </form><!--close the checkout form-->
<?php endif; ?>
<div class="clear"></div>

<?php do_action( 'angelleye_review_order_after_checkout_form' );?>
