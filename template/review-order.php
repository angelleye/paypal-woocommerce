<?php
/**
 * Review Order
 */

global $woocommerce;
?>
<style type="text/css">
    #payment{
        display:none;
    }
</style>
<div id="paypalexpress_order_review">
    <?php woocommerce_order_review();?>

</div>


<?php if ( WC()->cart->needs_shipping()  ) : ?>


<header>
    <h2><?php _e( 'Customer details', 'woocommerce' ); ?></h2>
</header>

<div class="col2-set addresses">

    <div class="col-1">

        <header class="title">
            <h3><?php _e( 'Shipping Address', 'woocommerce' ); ?></h3>
        </header>
        <address><p>
                <?php
                // Formatted Addresses
                $address = array(
                    'first_name' 	=> WC()->customer->shiptoname,
                    'last_name'		=> "",
                    'company'		=> "",
                    'address_1'		=> WC()->customer->get_address(),
                    'address_2'		=> "",
                    'city'			=> WC()->customer->get_city(),
                    'state'			=> WC()->customer->get_state(),
                    'postcode'		=> WC()->customer->get_postcode(),
                    'country'		=> WC()->customer->get_country()
                ) ;

                echo WC()->countries->get_formatted_address( $address );
                ?>
            </p></address>

    </div><!-- /.col-1 -->

    <div class="col-2">
    </div><!-- /.col-2 -->

</div><!-- /.col2-set -->

<?php endif; ?>

<div class="clear"></div>