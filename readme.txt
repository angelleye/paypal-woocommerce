=== PayPal for WooCommerce ===
Contributors: angelleye
Donate link: http://www.angelleye.com/product/buy-beer/
Tags: woocommerce, paypal, express checkout, payments pro, angelleye, payflow, dodirectpayment
Requires at least: 3.8
Tested up to: 3.8.1
Stable tag: 1.0.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Easily integrate PayPal Express Checkout and Payments Pro into the WooCommerce shopping cart.

== Description ==

Easily add PayPal payment options to your WordPress / WooCommerce website.

 * PayPal Express Checkout
 * PayPal Website Payments Pro 3.0 (DoDirectPayment)
 * PayPal Payments Pro 2.0 (PayFlow)
 
We are PayPal specialists who have been integrating PayPal into a wide variety of shopping cart systems over the years.  We've run into all the common problems associated with PayPal integration, and we know how to eliminate them.  We've also learned some tricks of the trade to make working with shopping carts and PayPal as seamless as possible.

This plugin brings that experience to WooCommerce so that you can rest assured your payments will be processed correctly…and, it's free!

= Quality Control =
Payment processing can't go wrong.  It's as simple as that.  Our certified PayPal engineers have developed and thoroughly tested this plugin on the PayPal sandbox (test) servers.  

= Seamless PayPal Integration =
Stop bouncing back and forth between WooCommerce and PayPal to manage and reconcile orders.  We've made sure to include all WooCommerce order data in PayPal transactions so that everything matches in both places.  If you're looking at a PayPal transaction details page it will have all of the same data as a WooCommerce order page, and vice-versa.  

= Error Handling (Correctly) =
PayPal's system can be tricky when it comes to handling errors.  Most PayPal plugins do not correctly process the PayPal response which can result in big problems.  For example:

* Fraud Filters could throw a "warning" instead of a full "success" response even when the payment was completed successfully.  
* Many plugins treat these as failures and customers end up with duplicate payments if they continue to retry.

Our plugins always handle these warnings/errors correctly so that you do not have to worry about dealing with those types of situations.

= Get Involved =
Developers can contribute to the source code on the PayPal for [WooCommerce GitHub repository](https://github.com/angelleye/paypal-woocommerce).

== Installation ==

= Minimum Requirements =

* WordPress 3.8 or greater
* PHP version 5.2.4 or greater
* MySQL version 5.0 or greater

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of PayPal for WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type PayPal for WooCommerce and click Search Plugins. Once you've found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now.

= Manual Installation =

 1. Unzip the files and upload the folder into your plugins folder (wp-content/plugins/) overwriting old versions if they exist
 2. Activate the plugin in your WordPress admin area.
 3. Open the settings page for WooCommerce and click the "Checkout" tab
 4. Click on the sub-item for PayPal Express Checkout or Payments Pro.
 5. Configure your settings accordingly.
 
= Updating = 

Automatic updates should work great for you.  As always, though, we recommend backing up your site prior to making any updates just to be sure nothing goes wrong.
 
== Screenshots ==

1. Display Pay with Credit Card and Pay with PayPal options on the shopping cart page.
2. Your logo and cart items accurately displayed on PayPal Express Checkout review pages.
3. Direct credit card processing option available with PayPal Payments Pro.

== Frequently Asked Questions ==

= Where do I get my API credentials? =

 * Sandbox credentials can be obtained by signing in with a sandbox account here:  https://www.sandbox.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run
 * Live credentials can be obtained by signing in to your live PayPal account here:  https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run

== Changelog ==

= 1.0.1 =
Adds better error handling when PayPal API credentials are incorrect.

= 1.0 =
Initial, stable release.