=== PayPal for WooCommerce ===
Contributors: angelleye
Donate link: http://www.angelleye.com/product/buy-beer/
Tags: woocommerce, paypal, express checkout, payments pro, angelleye, payflow, dodirectpayment
Requires at least: 3.8
Tested up to: 3.9
Stable tag: 1.1.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Developed by an Ace Certified PayPal Developer, official PayPal Partner, PayPal Ambassador, and 3-time PayPal Star Developer Award Winner. 

== Description ==

= Introduction =

Easily add PayPal payment options to your WordPress / WooCommerce website.

 * PayPal Express Checkout / Bill Me Later
 * PayPal Website Payments Pro 3.0 (DoDirectPayment)
 * PayPal Payments Pro 2.0 (PayPal Manager / PayFlow Gateway)
 
[youtube https://www.youtube.com/watch?v=svq9ovWGp7I]

= Quality Control =
Payment processing can't go wrong.  It's as simple as that.  Our certified PayPal engineers have developed and thoroughly tested this plugin on the PayPal sandbox (test) servers to ensure your customers don't have problems paying you.  

= Seamless PayPal Integration =
Stop bouncing back and forth between WooCommerce and PayPal to manage and reconcile orders.  We've made sure to include all WooCommerce order data in PayPal transactions so that everything matches in both places.  If you're looking at a PayPal transaction details page it will have all of the same data as a WooCommerce order page, and vice-versa.  

= Error Handling =
PayPal's system can be tricky when it comes to handling errors.  Most PayPal plugins do not correctly process the PayPal response which can result in big problems.  For example:

* Fraud Filters could throw a "warning" instead of a full "success" response even when the payment was completed successfully.  
* Many plugins treat these as failures and customers end up with duplicate payments if they continue to retry.

Our plugins always handle these warnings/errors correctly so that you do not have to worry about dealing with those types of situations.

= Localization = 
The PayPal Express Checkout buttons and checkout pages will translate based off your WordPress language setting by default.  The rest of the plugin was also developed with localization in mind and is ready for translation.

If you're interested in helping translate please [let us know](http://www.angelleye.com/contact-us/)!

= Get Involved =
Developers can contribute to the source code on the [PayPal for WooCommerce GitHub repository](https://github.com/angelleye/paypal-woocommerce).

== Installation ==

= Minimum Requirements =

* WooCommerce 2.1 or higher

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of PayPal for WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type PayPal for WooCommerce and click Search Plugins. Once you've found our plugin you can view details about it such as the the rating and description. Most importantly, of course, you can install it by simply clicking Install Now.

= Manual Installation =

1. Unzip the files and upload the folder into your plugins folder (/wp-content/plugins/) overwriting older versions if they exist
2. Activate the plugin in your WordPress admin area.
 
= Usage = 

1. Open the settings page for WooCommerce and click the "Checkout" tab
2. Click on the sub-item for PayPal Express Checkout or Payments Pro.
3. Enter your API credentials and adjust any other settings to suit your needs. 

= Updating = 

Automatic updates should work great for you.  As always, though, we recommend backing up your site prior to making any updates just to be sure nothing goes wrong.
 
== Screenshots ==

1. Display Pay with Credit Card and Pay with PayPal / Bill Me Later options on the shopping cart page.
2. PayPal Express Checkout button on product detail page.
3. Your logo and cart items accurately displayed on PayPal Express Checkout review pages.
4. Direct credit card processing option available with PayPal Payments Pro.

== Frequently Asked Questions ==

= How do I create sandbox accounts for testing? =

* Login at http://developer.paypal.com.  
* Click the Applications tab in the top menu.
* Click Sandbox Accounts in the left sidebar menu.
* Click the Create Account button to create a new sandbox account.
* TIP: Create at least one "seller" account and one "buyer" account if you want to fully test Express Checkout or other PayPal wallet payments. 

= Where do I get my API credentials? =

* Live credentials can be obtained by signing in to your live PayPal account here:  https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run
* Sandbox credentials can be obtained by viewing the sandbox account profile within your PayPal developer account, or by signing in with a sandbox account here:  https://www.sandbox.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run

= How do I know which version of Payments Pro I have? = 
* If you have a PayPal Manager account at http://manager.paypal.com as well as your regular PayPal account at http://www.paypal.com, then you are on Payments Pro 2.0.
* If you are unsure, you may need to [contact PayPal](https://www.paypal.com/us/webapps/helpcenter/helphub/home/) and request the information.  Just let them know you need to enable a Payments Pro plugin on your website, but you're unsure whether you should use Website Payments Pro 3.0(DoDirectPayment) or Payments Pro 2.0 (PayFlow).  They can confirm which one you need to use.

== Changelog ==

= 1.1.4 - 05/02/2014 =
* Fix - Corrects an issue happening with some browsers on the Express Checkout review page.

= 1.1.3 - 04/23/2014 =
* Feature - Adds a notice if you try to activate on an incompatible version of WooCommerce.

= 1.1.2 - 04/23/2014 =
* Fix - Removes PHP warnings/notices from PayPal Express Checkout review page.
* Fix - Custom fees applied to the Woo cart are now handled correctly in each gateway.
* Fix - Old logic for which buttons to display (based on active gateways) has been removed and replaced with new logic utilizing the Checkout Button Type option in Express Checkout.
* Feature - Express Checkout now has the option to set a Brand Name and a Customer Service Number that will be used on the PayPal review pages.
* Feature - Express Checkout now has the option to enable a Gift Wrap option for your buyers on the PayPal review pages.
* Feature - Customer notes left on the PayPal review pages during an Express Checkout order are now saved in the Woo order notes.

= 1.1.1 - 04/05/2014 = 
* Fix - PayPal Express Checkout button no longer shows up on the product page for an external product.

= 1.1 - 04/03/2014 =
* Fix - If WooCommerce Guest Checkout is disabled, Express Checkout now requires login or account creation.
* Localization - Ready for translation.
* Feature - Adds the option to include a Bill Me Later button on cart and checkout pages.
* Feature - Adds option to display detailed or generic errors to users when payments fail.
* Feature - Adds ability to set a custom image in place of the default PayPal Express Checkout button.
* Feature - Adds option to include Express Checkout button on product pages.
* Tweak - Adds admin notice when both PayPal Standard and Express Checkout are enabled.
* Tweak - Adds the option to enable/disable logging in Payments Pro (PayFlow)
* Tweak - Adds links to obtain API credentials from settings page for easy access.
* Tweak - Improves CSS styles on Express Checkout and Bill Me Later buttons.
* Tweak - Improves CSS styles on Payments Pro checkout forms.
* Tweak - Updates PayPal API version in Angell EYE PayPal PHP Library
* Tweak - Updates guest checkout options in Express Checkout to work with new API parameters.
* Refactor - Strips unnecessary code from original WooThemes extension.
* Refactor - Strips unnecessary additional calls to GetExpressCheckoutDetails to reduce server loads.

= 1.0.5 - 03/17/2014 =
* Refactor - Minor code adjustments and cleanup.

= 1.0.4 - 03/12/2014 = 
* Fix - Resolves issue with invalid order number getting sent to PayPal for merchants in some countries.

= 1.0.3 - 03/11/2014 =
* Tweak - Update the checkout button verbiage based on enabled payment gateways.
* Fix - Eliminate PHP warnings that would surface if error reporting was enabled on the server.
* Fix - Eliminate conflict with WooCommerce if plugin is enabled while updating WooCommerce. 

= 1.0.2 - 03/05/2014 =
* Refactor - Stripped out all the original Woo PayPal integration code and replaced it with the Angelleye PHP Class Library for PayPal.

= 1.0.1 =
* Tweak - Adds better error handling when PayPal API credentials are incorrect.

= 1.0 =
* Feature - PayPal Express Checkout
* Feature - PayPal Website Payments Pro 3.0 (DoDirectPayment)
* Feature - PayPal Payments Pro 2.0 (PayPal Manager / PayFlow)