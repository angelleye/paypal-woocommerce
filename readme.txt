=== PayPal for WooCommerce ===
Contributors: angelleye
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SG9SQU2GBXJNA
Tags: woocommerce, paypal, express checkout, payments pro, angelleye, payflow, dodirectpayment, braintree, payments advanced, rest
Requires at least: 3.8
Tested up to: 4.7
Stable tag: 1.3.3
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Developed by an Ace Certified PayPal Developer, Official PayPal Partner, PayPal Ambassador, and 3-time PayPal Star Developer Award Winner.

== Description ==

= Introduction =

Easily add PayPal payment options to your WordPress / WooCommerce website.

 * PayPal Express Checkout / PayPal Credit
 * PayPal Website Payments Pro 3.0 (DoDirectPayment)
 * PayPal Payments Pro 2.0 (PayPal Manager / PayFlow Gateway)
 * PayPal Plus (Germany, Brazil, Mexico)
 * PayPal Payments Advanced
 * PayPal REST Credit Card Payments
 * PayPal Braintree Credit Card Payments
 * Fully Supports WooCommerce Payment Tokens!
 
[youtube https://www.youtube.com/watch?v=svq9ovWGp7I]

[youtube https://www.youtube.com/watch?v=VhQT8rX7uwE]

= WooCommerce Payment Tokens Compatibility =
Fully supports WooCommerce payment tokens, so buyers can choose to save their payment method to their account with your site for quicker checkout in the future.

= FREE PayPal Payments Pro Account =
By using our plugin we can get you hooked up with PayPal Payments Pro with no monthly fee! (U.S. merchants only) [Contact us](https://www.angelleye.com/contact-us) for details.

= PayPal Plus Information =
The BETA version of PayPal Plus that we had included with this plugin has been stripped out as of version 1.2.4.  We have moved PayPal Plus to its own separate plugin so that we may focus on all the different features and functionality it needs to work with the various countries it supports.  [Get the PayPal Plus Plugin!](https://www.angelleye.com/product/woocommerce-paypal-plus-plugin/)

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

* WooCommerce 2.6 or higher

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

1. Display Pay with Credit Card and Pay with PayPal / PayPal Credit options on the shopping cart page.
2. PayPal Express Checkout button on product detail page.
3. Your logo and cart items accurately displayed on PayPal Express Checkout review pages.
4. Direct credit card processing option available with PayPal Payments Pro.
5. WooCommerce Payment Tokens - Save to Account option displayed to buyer during checkout.

== Frequently Asked Questions ==

= How do I create sandbox accounts for testing? =

* Login at http://developer.paypal.com.  
* Click the Applications tab in the top menu.
* Click Sandbox Accounts in the left sidebar menu.
* Click the Create Account button to create a new sandbox account.
* TIP: Create at least one "seller" account and one "buyer" account if you want to fully test Express Checkout or other PayPal wallet payments. 
* TUTORIAL: See our [step-by-step instructions with video guide](https://www.angelleye.com/create-paypal-sandbox-account/).

= Where do I get my API credentials? =

* Live credentials can be obtained by signing in to your live PayPal account here:  https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run
* Sandbox credentials can be obtained by viewing the sandbox account profile within your PayPal developer account, or by signing in with a sandbox account here:  https://www.sandbox.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run

= How do I know which version of Payments Pro I have? = 
* If you have a PayPal Manager account at http://manager.paypal.com as well as your regular PayPal account at http://www.paypal.com, then you are on Payments Pro 2.0.
* If you are unsure, you may need to [contact PayPal](https://www.paypal.com/us/webapps/helpcenter/helphub/home/) and request the information.  Just let them know you need to enable a Payments Pro plugin on your website, but you're unsure whether you should use Website Payments Pro 3.0(DoDirectPayment) or Payments Pro 2.0 (PayFlow).  They can confirm which one you need to use.

== Changelog ==

= 1.3.3 - 02.03.2017 =
* Fix - Resolves PHP failures happening when out-dated versions of PHP and/or WooCommerce are installed. ([#635](https://github.com/angelleye/paypal-woocommerce/issues/635))

= 1.3.2 - 01.27.2017 =
* Tweak - Adds confirmation when capturing orders to ensure the expected amount is being captured.  ([#631](https://github.com/angelleye/paypal-woocommerce/pull/631))
* Fix - Resolves an issue with dynamic Express Checkout buttons not loading properly for some countries. ([#623](https://github.com/angelleye/paypal-woocommerce/issues/623))
* Fix - Resolves an issue with Braintree calls failing on some sites. ([#625](https://github.com/angelleye/paypal-woocommerce/issues/625))
* Fix - Resolves an issue with Order captures being sent to the sandbox instead of the live server. ([#621](https://github.com/angelleye/paypal-woocommerce/issues/621))
* Fix - Resolves PHP notices. ([#608](https://github.com/angelleye/paypal-woocommerce/issues/608))

= 1.3.1 - 12.28.2016 =
* Fix - Resolves an issue causing the incorrect expiration date to display on credit card token payments. ([#620](https://github.com/angelleye/paypal-woocommerce/issues/620))

= 1.3.0 - 12.26.2016 =
* Feature - Adds WooCommerce payment tokens compatibility in all payment gateways. ([#585](https://github.com/angelleye/paypal-woocommerce/issues/585))
* Feature - Adds compatibility with WC AutoShip plugin. ([#597](https://github.com/angelleye/paypal-woocommerce/issues/597))
* Feature - Adds the option to disable Terms and Conditions when using the Skip Final Review option with Express Checkout. ([#471](https://github.com/angelleye/paypal-woocommerce/issues/471))
* Feature - Adds filters for currency codes to all gateways and improves Aelia Currency Switcher compatibility. ([#587](https://github.com/angelleye/paypal-woocommerce/issues/587))
* Feature - Adds filters for credit card icon graphics on checkout page. ([#563](https://github.com/angelleye/paypal-woocommerce/issues/563))
* Feature - Adds a button to quickly clear all logs saved by the plugin. ([#562](https://github.com/angelleye/paypal-woocommerce/issues/562))
* Feature - Adds a hook to send your own value in the CUSTOM parameter for PayFlow. ([#610](https://github.com/angelleye/paypal-woocommerce/issues/610))
* Tweak - Adjustments to terms and conditions check box on review page. ([#614](https://github.com/angelleye/paypal-woocommerce/pull/614))
* Tweak - Improved logs for REST credit card processing. ([#559](https://github.com/angelleye/paypal-woocommerce/issues/559))
* Tweak - Adjustments to the way the address is displayed on the Express Checkout review page. ([#595](https://github.com/angelleye/paypal-woocommerce/issues/595))
* Tweak - Updates Express Checkout image used on product page, cart page, and checkout page. ([#573](https://github.com/angelleye/paypal-woocommerce/issues/573))
* Tweak - Adjusts the log files saved for REST transactions. ([#566](https://github.com/angelleye/paypal-woocommerce/issues/566))
* Tweak - Masks PayFlow logs for security purposes. ([#582](https://github.com/angelleye/paypal-woocommerce/issues/582))
* Tweak - Adjusts the way order status is handled with Authorized / Captured orders. ([#557](https://github.com/angelleye/paypal-woocommerce/issues/557))
* Fix - Resolves references to Braintree within the REST credit card processing. ([#591](https://github.com/angelleye/paypal-woocommerce/issues/591))
* Fix - Resolves an issue in PayPal Advanced on orders where the subtotal is $0 but shipping still needs to be paid. ([#543](https://github.com/angelleye/paypal-woocommerce/issues/543))
* Fix - Replaces deprecated function. ([#552](https://github.com/angelleye/paypal-woocommerce/issues/552))
* Fix - Resolves an issue with deprecated address functions in WooCommerce 2.6 or higher. ([#602](https://github.com/angelleye/paypal-woocommerce/issues/602))
* Fix - Resolves a problem processing MasterCard transactions with REST. ([#558](https://github.com/angelleye/paypal-woocommerce/issues/558))
* Fix - Resolves problems capturing authorized orders. ([#553](https://github.com/angelleye/paypal-woocommerce/issues/553))

= 1.2.4 - 09.13.2016 =
* Feature - PayPal Advanced logo setup. ([#491](https://github.com/angelleye/paypal-woocommerce/issues/491))
* Feature - Adds the ability to set a Page Style Option in the Express Checkout settings. [(#535](https://github.com/angelleye/paypal-woocommerce/issues/535))
* Feature - Adds the option to include separate fields for "billing name" and "credit card name" during checkout. ([#133](https://github.com/angelleye/paypal-woocommerce/issues/133))
* Feature - Adds filter hooks to set PayFlow COMMENT fields to your own values. ([#498](https://github.com/angelleye/paypal-woocommerce/issues/498))
* Feature - Turns the PayPal transaction ID in the WooCommerce order screen into a link to view the transaction details at PayPal.com. ([#542](https://github.com/angelleye/paypal-woocommerce/issues/542))
* Tweak - Adjusts the credit card / PayPal logo used for Express Checkout. ([#235](https://github.com/angelleye/paypal-woocommerce/issues/235))
* Tweak - Cross-check existing accounts when Express Checkout is used to ensure local customer data matches PayPal's customer data. ([#236](https://github.com/angelleye/paypal-woocommerce/issues/236))
* Tweak - Adjustments to pre-population of PayPal's credit card form on the Express Checkout screen. ([#237](https://github.com/angelleye/paypal-woocommerce/issues/237))
* Tweak - Hides PayPal buttons when currency is unsupported. ([#285](https://github.com/angelleye/paypal-woocommerce/issues/285))
* Tweak - Adds utm_nooverride to Express Checkout return URL to avoid Google Analytics setting PayPal as the referral on orders. ([#492](https://github.com/angelleye/paypal-woocommerce/issues/492))
* Tweak - Adjusts the way inventory management is handled when dealing with Authorization orders. ([#496](https://github.com/angelleye/paypal-woocommerce/issues/496))
* Tweak - Adds missing parameters to WooCommerce hook. ([#530](https://github.com/angelleye/paypal-woocommerce/issues/530))
* Tweak - Shipping calculation adjustments related to PayPal payment request setup.  ([#522](https://github.com/angelleye/paypal-woocommerce/issues/522))
* Tweak - Adds number_format to item pricing in payment request to PayPal. ([#504](https://github.com/angelleye/paypal-woocommerce/issues/504))
* Tweak - Adjust IPN URL setting to ensure nothing gets sent in the API request if the setting is blank. ([#514](https://github.com/angelleye/paypal-woocommerce/issues/514))
* Tweak - Adjustment to jQuery involved with variable products. ([#515](https://github.com/angelleye/paypal-woocommerce/pull/515))
* Tweak - Adds Billing Agreement ID to Express Checkout orders when billing agreements are enabled. ([#493](https://github.com/angelleye/paypal-woocommerce/issues/493))
* Tweak - Adjustments to how the Skip Final Review option is handled.  ([#525](https://github.com/angelleye/paypal-woocommerce/issues/525))
* Tweak - Adds Braintree MID functionality. ([#521](https://github.com/angelleye/paypal-woocommerce/issues/521))
* Fix - Resolves PHP warnings when orders are processed. ([#502](https://github.com/angelleye/paypal-woocommerce/issues/502))
* Fix - Resolves a conflict with 3rd party plugin(s) related to currency codes. ([#508](https://github.com/angelleye/paypal-woocommerce/issues/508))
* Fix - Resolves tax calculation issue. ([#516](https://github.com/angelleye/paypal-woocommerce/issues/516))
* Fix - Resolves a conflict in our Checkout Button Type option with regards to all the different places the custom image would be displayed. ([#524](https://github.com/angelleye/paypal-woocommerce/issues/524))
* Fix - Resolves a PHP failure happening when a cURL failure occurs. ([#528](https://github.com/angelleye/paypal-woocommerce/issues/528))
* Fix - Resolves a PHP fatal error that sometimes occurs with Braintree. ([#533](https://github.com/angelleye/paypal-woocommerce/issues/533)) ([#537](https://github.com/angelleye/paypal-woocommerce/issues/537))
* Fix - Resolves an issue with Braintree UI where the payment form is output multiple times. ([#532](https://github.com/angelleye/paypal-woocommerce/issues/532))
* Fix - Improves error handling for Braintree.  ([#536](https://github.com/angelleye/paypal-woocommerce/issues/536)) ([#538](https://github.com/angelleye/paypal-woocommerce/issues/538)) ([#540](https://github.com/angelleye/paypal-woocommerce/issues/540))
* Removal - Removes BETA version of PayPal Plus originally included with this plugin.  ([#481](https://github.com/angelleye/paypal-woocommerce/issues/481))  [Get the New PayPal Plus Plugin!](https://www.angelleye.com/product/woocommerce-paypal-plus-plugin/)

= 1.2.3 - 06.22.2016 =
* Fix - Fixes an incorrect parameter name in Express Checkout response logic. ([#488](https://github.com/angelleye/paypal-woocommerce/pull/488))
* Fix - Adds the PPREF value to PayFlow orders when PayPal is used as the processor. ([#482](https://github.com/angelleye/paypal-woocommerce/issues/482))

= 1.2.2 - 06.21.2016 =
* Fix - Resolves incompatibility with Express Checkout and new shipping features in WooCommerce 2.6. ([#483](https://github.com/angelleye/paypal-woocommerce/issues/483))
* Fix - Resolves an issue with American Express orders getting denied with USD currency code. ([#485](https://github.com/angelleye/paypal-woocommerce/issues/485))
* Fix - Resolves a problem with the auto cancel / refund feature based on seller protection. ([#486](https://github.com/angelleye/paypal-woocommerce/issues/486))

= 1.2.1 - 06.19.2016 =
* Fix - Backwards compatibility with WooCommerce 2.5 / 2.6. ([#480](https://github.com/angelleye/paypal-woocommerce/issues/480))
* Fix - Adjustments for compatibility with the new 2.6 shipping features. ([#479](https://github.com/angelleye/paypal-woocommerce/issues/479))
* Fix - Resolves PHP failures in the WooCommerce settings panel happening with some PHP versions. ([#478](https://github.com/angelleye/paypal-woocommerce/issues/478))

= 1.2.0 - 06.17.2016 =
* Feature - Adds PayPal Payments Advanced. ([#11](https://github.com/angelleye/paypal-woocommerce/issues/11))
* Feature - Adds the ability to capture Orders / Authorizations from within the WooCommerce order screen. ([#36](https://github.com/angelleye/paypal-woocommerce/issues/36))
* Feature - Adds an option to specify an Instant Payment Notification (IPN) URL for transactions ([#47](https://github.com/angelleye/paypal-woocommerce/issues/47))
* Feature - Adds Express Checkout wallet coupons and rewards. ([#87](https://github.com/angelleye/paypal-woocommerce/issues/87))
* Feature - Adds an option to provide separate fields for "billing name" and "credit card name" for Payments Pro credit card transactions. ([#180](https://github.com/angelleye/paypal-woocommerce/issues/180))
* Feature - Adds Braintree credit card payments. ([#370](https://github.com/angelleye/paypal-woocommerce/issues/370))
* Feature - Adds PayPal REST credit card payments. ([#414](https://github.com/angelleye/paypal-woocommerce/issues/414))
* Feature - Adds refund functionality for PayPal Plus.  ([#398](https://github.com/angelleye/paypal-woocommerce/issues/398))
* Feature - Adds order number to PayPal Plus. ([#400](https://github.com/angelleye/paypal-woocommerce/issues/400))
* Feature - Adds an option to automatically cancel / refund orders that are not covered by PayPal Seller Protection. ([#429](https://github.com/angelleye/paypal-woocommerce/pull/429))
* Feature - Adds a hook to include custom data in the CUSTOM parameter of PayPal payments. ([#431](https://github.com/angelleye/paypal-woocommerce/issues/431))
* Feature - Adds the ability to force TLS 1.2 for HTTP requests if your server is not doing this by default. ([#463](https://github.com/angelleye/paypal-woocommerce/issues/463))
* Feature - Adds functionality for PayPal Orders, Authorization, and Capture. ([#462](https://github.com/angelleye/paypal-woocommerce/issues/462))
* Tweak - Adjusts the way pending payments are handled in relation to digital goods orders. ([#440](https://github.com/angelleye/paypal-woocommerce/issues/440))
* Tweak - Enables Fraud Management Filters information to be included in API response logs. ([#432](https://github.com/angelleye/paypal-woocommerce/issues/432))
* Tweak - Woo Checkout Add-Ons compatibility. ([#430](https://github.com/angelleye/paypal-woocommerce/issues/430))
* Tweak - Woo EU Vat Number compatibility. ([#434](https://github.com/angelleye/paypal-woocommerce/issues/434))
* Tweak - Woo Local Pickup Plus compatibility. ([#438](https://github.com/angelleye/paypal-woocommerce/issues/438))
* Tweak - Adds jQuery triggers / event listeners. ([#427](https://github.com/angelleye/paypal-woocommerce/pull/427))
* Tweak - Adjustments to custom checkout image options. ([#435](https://github.com/angelleye/paypal-woocommerce/issues/435))
* Tweak - Adds filter for add_body_classes for easier CSS styling. ([#428](https://github.com/angelleye/paypal-woocommerce/pull/428))
* Tweak - Adds experience ID to PayPal Plus transactions. ([#402](https://github.com/angelleye/paypal-woocommerce/issues/402))
* Tweak - Adjusts the CSS / jQuery used to place the Express Checkout button on product details pages. ([#209](https://github.com/angelleye/paypal-woocommerce/issues/209) [#312](https://github.com/angelleye/paypal-woocommerce/issues/312))
* Tweak - Removes American Express from the credit card type options when incompatible currencies are set in WooCommerce. ([#420](https://github.com/angelleye/paypal-woocommerce/issues/420))
* Tweak - Upgrades the PayPal PHP SDK. ([#422](https://github.com/angelleye/paypal-woocommerce/pull/422))
* Tweak - Adds invoice prefix to the Order ID parameter in PayFlow requests. ([#443](https://github.com/angelleye/paypal-woocommerce/issues/443))
* Tweak - WordPress 4.5 compatibility. ([#444](https://github.com/angelleye/paypal-woocommerce/issues/444))
* Tweak - Adjusts the path displayed for log files. ([#458](https://github.com/angelleye/paypal-woocommerce/issues/458))
* Tweak - Adjusts text domain and domain path. ([#459](https://github.com/angelleye/paypal-woocommerce/issues/459))
* Tweak - Adjustments to avoid errors when using PSR 4 loading standards. ([#468](https://github.com/angelleye/paypal-woocommerce/issues/468))
* Fix - WooCommerce 2.6 Compatibility ([#476](https://github.com/angelleye/paypal-woocommerce/issues/476))
* Fix - Resolves issues with 3DSecure in Website Payments Pro 3.0 ([#149](https://github.com/angelleye/paypal-woocommerce/issues/149))
* Fix - Resolves a conflict with the Express Checkout button and the "Quick Buy" plugin. ([#415](https://github.com/angelleye/paypal-woocommerce/issues/415))
* Fix - Resolves a problem with ajax loader. ([#417](https://github.com/angelleye/paypal-woocommerce/issues/417))
* Fix - Resolves an issue with $0 orders in PayPal Plus. ([#442](https://github.com/angelleye/paypal-woocommerce/issues/442))
* Fix - Resolves an issue with PayFlow Pro resulting in duplicate API requests. ([#454](https://github.com/angelleye/paypal-woocommerce/issues/454))
* Fix - Resolves an issue with a PHP undefined method. ([#472](https://github.com/angelleye/paypal-woocommerce/issues/472))
* Fix - Resolves a problem with some invoice numbers being passed to PayPal incorrectly. ([#467](https://github.com/angelleye/paypal-woocommerce/issues/467))
* Fix - Resolves a problem with PHP class declaration. ([#461](https://github.com/angelleye/paypal-woocommerce/issues/461))

= 1.1.9.2 - 02.07.2016 =
* Fix - Resolves a problem with backorder handling when users are checking out near the same time. ([#403](https://github.com/angelleye/paypal-woocommerce/issues/403))
* Fix - Resolves 3rd party plugin conflict. ([#406](https://github.com/angelleye/paypal-woocommerce/issues/406))
* Fix - Resolves an issue with apostrophes not getting handled correctly in PayPal buyer data. ([#409](https://github.com/angelleye/paypal-woocommerce/issues/409))
* Fix - Resolves an issue with PayPal Plus where the payment chosen was not always used. ([#411](https://github.com/angelleye/paypal-woocommerce/issues/411))
* Fix - Resolves issue with PayPal Plus where submit button was not working in some themes.

= 1.1.9.1 - 01.22.2016 =
* Fix - Removes the sandbox / test mode message that was displaying even when in live mode.

= 1.1.9 - 01.22.2016 =
* Feature - Hear About Us plugin compatibility. ([#392](https://github.com/angelleye/paypal-woocommerce/issues/392))
* Feature - Moves bulk update for enable/disable shipping requirements to a separate tool specific to the plugin. ([#381](https://github.com/angelleye/paypal-woocommerce/issues/381))
* Tweak - Description ([#146](https://github.com/angelleye/paypal-woocommerce/issues/146))
* Tweak - Moves the Billing Agreement option to the product level. ([#382](https://github.com/angelleye/paypal-woocommerce/issues/382))
* Tweak - Better error handling for session token problems. ([#386](https://github.com/angelleye/paypal-woocommerce/issues/386))
* Tweak - Adds more logic to the bulk product options editor. ([#391](https://github.com/angelleye/paypal-woocommerce/issues/391))
* Tweak - Updates credit card form for PayPal Payments Pro to use built in WooCommerce forms. ([#395](https://github.com/angelleye/paypal-woocommerce/issues/395))
* Fix - Resolves a bug when processing payments for non-decimal currencies. ([#384](https://github.com/angelleye/paypal-woocommerce/issues/384))
* Fix - Resolves CSS conflict with Storefront theme. ([#388](https://github.com/angelleye/paypal-woocommerce/issues/388))

= 1.1.8 - 01.11.2016 =
* Feature - Adds an option to include a billing agreement with Express Checkout, which enables the use of future reference transactions. ([#168](https://github.com/angelleye/paypal-woocommerce/issues/168))
* Feature - Adds a product-level option for digital/virtual products to enable/disable shipping requirements in Express Checkout ([#174](https://github.com/angelleye/paypal-woocommerce/issues/174))
* Feature - Adds a bulk edit tool to enable/disable shipping at the product level for multiple products at once. ([#175](https://github.com/angelleye/paypal-woocommerce/issues/175))
* Feature - Adds hooks to insert custom fields for data collection to the Express Checkout order review page. ([#338](https://github.com/angelleye/paypal-woocommerce/issues/338))
* Tweak - Applies the "shipping override" feature in Express Checkout when the WooCommerce checkout page is used to ensure that address is held all the way through checkout. ([#211](https://github.com/angelleye/paypal-woocommerce/issues/211), [#215](https://github.com/angelleye/paypal-woocommerce/issues/215))
* Tweak - Adds a settings panel specific to the plugin. ([#214](https://github.com/angelleye/paypal-woocommerce/issues/214))
* Tweak - Adds additional validation to PayFlow credit card transactions. ([#220](https://github.com/angelleye/paypal-woocommerce/issues/220))
* Tweak - Improved cURL error handling. ([#146](https://github.com/angelleye/paypal-woocommerce/issues/146))
* Tweak - Adds validation to the "create account" option on the Express Checkout review page. ([#346](https://github.com/angelleye/paypal-woocommerce/issues/346))
* Tweak - Adds hooks to ensure data is saved correctly when custom fields are in use on the WooCommerce checkout page. ([#17](https://github.com/angelleye/paypal-woocommerce/issues/347))
* Tweak - Ensure that the email address entered on the WooCommerce checkout page is carried all the way through Express Checkout and not replaced by a PayPal login email. ([#350](https://github.com/angelleye/paypal-woocommerce/issues/350))
* Tweak - Handle scenarios where a discount code zeroes out the subtotal of an order, but shipping still needs to be paid. ([#352](https://github.com/angelleye/paypal-woocommerce/issues/352))
* Tweak - Updates deprecated function. ([#354](https://github.com/angelleye/paypal-woocommerce/issues/354))
* Tweak - Adjustment to ensure the PayPal Express Checkout button on product pages redirects to PayPal instead of the cart on all themes. ([#357](https://github.com/angelleye/paypal-woocommerce/issues/357))
* Tweak - Adds address line 2 to the Express Checkout review page when applicable. ([#371](https://github.com/angelleye/paypal-woocommerce/issues/371))
* Tweak - Adjusts Express Checkout button on product page to handle items "sold individually" correctly. ([#208](https://github.com/angelleye/paypal-woocommerce/issues/208))
* Tweak - Better error handling for scenarios where the PayPal response is blank for some reason. ([#274](https://github.com/angelleye/paypal-woocommerce/issues/274))
* Tweak - Updates PayPal API version to 124.0. ([#375](https://github.com/angelleye/paypal-woocommerce/issues/375))
* Tweak - PayPal Plus bug fixes and code improvements. ([#377](https://github.com/angelleye/paypal-woocommerce/issues/377))
* Tweak - Adds user IP address to PayPal API error admin email notifications. ([#378](https://github.com/angelleye/paypal-woocommerce/issues/378))
* Tweak - Clears items from cart after PayPal Plus order is completed. ([#374](https://github.com/angelleye/paypal-woocommerce/issues/374))
* Fix - Resolves potential function name conflict with themes. ([#349](https://github.com/angelleye/paypal-woocommerce/issues/349))
* Fix - Adjusts PayFlow request to ensure line items are passed correctly when enabled. ([#351](https://github.com/angelleye/paypal-woocommerce/issues/351))
* Fix - Updates successful order hook to include order ID param. ([#358](https://github.com/angelleye/paypal-woocommerce/issues/358))
* Fix - Adjustment to ensure order notes entered on WooCommerce checkout page are saved with Express Checkout orders. ([#363](https://github.com/angelleye/paypal-woocommerce/issues/363))
* Fix - Resolves potential configuration bugs with PayPal Plus integration. ([#368](https://github.com/angelleye/paypal-woocommerce/issues/368))
* Fix - Adjusts incorrect parameter name for the Express Checkout logo. ([#373](https://github.com/angelleye/paypal-woocommerce/issues/373))
* Fix - Resolves issues with gift wrap options. ([#341](https://github.com/angelleye/paypal-woocommerce/issues/341))

= 1.1.7.5 - 10.26.2015 =
* Fix - Resolves a broken setting for the cancel URL.
* Fix - Resolves some PHP warnings that were displayed with PayPal Plus.
* Fix - Resolves a problem where billing and shipping names are sometimes mixed up on orders.
* Tweak - Adjusts order notes in the PayPal payment request to avoid "too many character" warnings and correctly handles special characters.
* Tweak - Adjusts PayPal Plus to use country / language based on WooCommerce store settings.
* Tweak - Masks sensitive data in API logs.
* Tweak - Adjusts the PayPal Express and PayPal Credit buttons so they are independent from each other.

= 1.1.7.4 - 10.11.2015 =
* Fix - Resolves an issue with custom fees included on a cart/order.

= 1.1.7.3 - 10.08.2015 =
* Tweak - Disables PayPal Plus if your server is not running PHP 5.3+ (which is required for the PayPal REST SDK).

= 1.1.7.2 - 10.08.2015 =
* Fix - Resolves PayPal Plus payment failures when no shipping address is included on the order.

= 1.1.7.1 - 10.07.2015 =
* Fix - Hides PayPal Plus API credentials when Plus is not active.

= 1.1.7 - 10.07.2015 =
* Feature - Adds PayPal Plus (Germany)
* Feature - WP-Affiliate Compatibility
* Fix - Resolves a number of general bugs.
* Fix - Resolves issues that stem from the "Default Customer Address" setting when set to "Geolocate (with page caching support)".
* Fix - Resolves conflict with currency switcher plugins.
* Fix - Resolves a bug where shipping info was sometimes not saved with order meta data.
* Tweak - Moves order notes from general notes section to the meta data field for customer notes.
* Tweak - Enforces Terms and Conditions on the Express Checkout review page.
* Tweak - Adds the option to create an account from the Express Checkout review page (even if guest checkout is enabled).
* Tweak - Pre-populate email address on Express Checkout login screen if entered in the WooCommerce checkout page.
* Tweak - Adds logic to avoid invalid token erros with Express Checkout.
* Tweak - Disables PayPal Credit when the base country in WooCommerce is not the U.S.

= 1.1.6.3.7 - 08.27.2015 =
* Rollback - Removes adjustments that were made in an attempt to resolve rare cart total errors with PayPal.
* Rollback - Removes adjustments to code in an attempt to resolve issues with Currency Switcher plugins.
* Rollback - Removes adjustments made related to shipping data returned from PayPal and order meta data.
* Rollback - Removes WooCommerce terms and conditions acceptance from Express Checkout review page.
* Rollback - Removes "create account" option from Express Checkout review page (unless the require account option is enabled.)

= 1.1.6.3.6 - 08.22.2015 =
* Fix - Removes PHP short tag causing PHP failures on servers that do not have short tags enabled.
* Fix - Resolves conflict with the password validation when creating a new account during Express Checkout review.
* Tweak - Populates all available data to new customer record when account is created during Express Checkout review.
* Tweak - CSS adjustments to the terms and conditions acceptance during Express Checkout review.

= 1.1.6.3.5 - 08.20.2015 =
* Fix - WooCommerce 2.4 Compatibility.
* Fix - Resolves more cart total / calculation errors based on unique order totals.
* Fix - Resolves a problem where an & character in product names could cause checkout to fail.
* Fix - "WooCommerce Currency Switcher" plugin compatibility.
* Fix - Resolves a bug when setting Website Payments Pro 3.0 to Authorization.
* Fix - Resolves SSL warnings caused by graphics loading from http:// sources.
* Fix - Resolves a bug in the way discounts were passed in Payments Pro 2.0 orders.
* Tweak - Moves customer notes into WooCommerce order meta fields.
* Tweak - Adds a filter for PayPal API credentials for the ability to override the plugin setting values.
* Tweak - Adjusts logic around "Proceed to Checkout" button for better compatibility across themes.
* Tweak - Adjusts the way shipping details are saved with PayPal Express Checkout orders.
* Tweak - Masks API credentials in raw logs.
* Tweak - If Terms and Conditions page is set, Express Checkout will now require it (even if skipping the WooCommerce checkout page.)
* Tweak - If guest checkout is enabled in WooCommerce, Express Checkout will still provide the option to create an account (even if skipping the WooCommerce checkout page.)
* Tweak - Cleans deprecated functions.

= 1.1.6.3.4 - 06.29.2015 =
* Fix - Resolves an issue causing some 3rd party plugins to conflict and keep plugin options from loading correctly.
* Fix - Replaces the use of WPLANG throughout the plugin with get_local() and eliminates PHP notices.

= 1.1.6.3.3 - 06.26.2015 =
* Fix - Resolves a problem where Express Checkout orders were not getting saved to a logged in  users account.

= 1.1.6.3.2 - 06.26.2015 =
* Fix - Resolves a bug in the PayFlow gateway where ITEMAMT was not correct if "Send Item Details" is disabled.

= 1.1.6.3.1 - 06.24.2015 =
* Tweak - Sets default values in database for new features that were added in 1.1.6.3.

= 1.1.6.3 - 06.24.2015 =
* Fix - Resolves PayPal error 10431, item amount invalid, which would happen on rare occasions.
* Fix - Resolves a conflict with the Bulk Item Discount plugin that resulted in a PayPal order total error.
* Fix - Resolves other various PayPal order total errors by adjusting shipping/tax price when WooCommerce orders do not calculate correctly.
* Fix - Adds better error handling if the PayPal API response is empty.
* Fix - Resolves "Proceed to Checkout" button display problems since the WooCommerce 2.3 update.
* Fix - Resolves a conflict with the WooCommerce Wishlist plugin.
* Fix - Resolves an SSL conflict with the credit card images provided for Payments Pro (PayFlow).
* Fix - Resolves an issue where customer accounts were not getting created successfully with some Express Checkout transactions.
* Fix - Resolves an issue causing the Express Checkout default button to be displayed on the product page even if a custom button graphic has been set.
* Tweak - Adjusts the way the Locale Code is sent to PayPal based on WordPress language settings.
* Tweak - Adjusts functions that have been deprecated in WooCommerce 2.3.
* Tweak - Adjusts the width value for the PayPal Express Checkout graphics.
* Tweak - Adds order details (if any) to the PayPal error email notification that is sent to the site admin (if enabled).
* Tweak - jQuery adjustments to Express Checkout review page.
* Feature - Adds option to enable / disable sending line item details to PayPal.
* Feature - Adds developer hooks for customizing PayPal error notifications.
* Feature - Adds an option to display the PayPal Express Checkout button(s) below the cart, above the cart, or both.
* Feature - Adds an option to set the billing address to the same address as shipping when Express Checkout is used.
* Feature - Adds the ability to choose which page the user gets sent to if they cancel checkout from the PayPal Express Checkout pages.
* Feature - Adds an option to set orders to be processed as Sale or Authorization.

= 1.1.6.2 - 01/22/2015 =
* Fix - Resolves a PHP syntax issue that caused failures on PHP 5.2 or earlier.

= 1.1.6.1 - 01/22/2015 =
* Fix - Adjusts page element CSS problems with PayPal Express Checkout button on product details page.

= 1.1.6 - 01/21/2015 =
* Fix - Adds WooCommerce country limitation compatibility to PayPal Express Checkout.
* Fix - Resolves minor PHP notices/warnings displayed in certain scenarios.
* Fix - Removes a PHP short-tag that was used and causing failures on servers where short tags are not enabled.
* Fix - Adds adjustments for multi-site compatibility.
* Fix - Resolves issue with custom image used for PayPal Express Checkout button on product detail pages.
* Tweak - Resolves an issue where the PayPal Express Checkout button was showing up on product pages even for free items.
* Tweak - Adjusts logic in Payments Pro (PayFlow) to handle duplicate transactions correctly.
* Tweak - Adds the NZD currency code to Payments Pro (PayFlow)
* Tweak - Minor code adjustments to keep up with changes to the WooCommerce code.
* Tweak - Adds a progress "spinner" when the PayPal Express Checkout button is pushed so users can see that it was indeed triggered and can't click it again.
* Tweak - Adjusts the PayPal Express Checkout review page to include a username field when creating an account due to the WooCommerce "Guest Checkout" option being disabled.
* Tweak - Adds adjustments to the logic surrounding the display of checkout and/or PayPal buttons on the shopping cart page to reduce theme conflicts.
* Tweak - Adds WooThemes Points and Rewards extension compatibility.
* Tweak - Adds PayPal Express Checkout to the WooCommerce cart widget.
* Tweak - Adjusts order data so that the name of the customer is displayed instead of "Guest" for guest checkouts.
* Tweak - Adjusts the logic that calculates the MAXAMT in Express Checkout to avoid conflicts with features like gift wrapping where additional cost may be applied.
* Feature - Adds the option to display PayPal Express Checkout in the general gateway list on the checkout page.
* Feature - Adds the option to adjust the message displayed next the Express Checkout button at the top of the checkout page.
* Feature - Adds WooCommerce refund compatibility for PayPal Express Checkout and Payments Pro.
* Feature - Adds the option to enable/disable the LOCALECODE in PayPal Express Checkout, which can effect the checkout experience.
* Feature - Adds the option to skip the final review page for PayPal Express Checkout.  This can be used on sites where shipping and tax do not need calculated.
* Feature - Adds WPML compatibility.
* Feature - Adds JCB credit cards to the PayPal Payments Pro (PayFlow) gateway.
* Refactor - Adjusts PayPal class names to ensure no conflicts will occur with 3rd party plugins/themes.

= 1.1.5.3 - 11/12/2014 =
* Tweak - More adjustments to cURL options in the plugin in response to POODLE.  This update will eliminate the need to update cURL to any specific version.

= 1.1.5.2 - 11/05/2014 =
* Tweak - Updates cURL so it uses TLS instead of SSLv3 and resolves vulnerability per PayPal's requirement.  It is very important that you ensure your server is running cURL version 7.36.0 or higher before installing this update!

= 1.1.5 - 08/26/2014 =
* Fix - Re-creates checkout review when unavailable to eliminate Invalid ReturnURL error from PayPal.
* Fix - Resolves an issue with long field names on some servers causing the Express Checkout settings page to fail when saving.
* Fix - Resolves an issue where two checkout buttons were sometimes displayed on the cart depending on which payment gateways were currently enabled.
* Fix - Resolves an issue where Express Checkout buttons were displayed in certain places on the site even when Express Checkout was disabled.
* Fix - Removes included javascript on pages where it wasn't being used to eliminate 404 warnings.
* Fix - Adjusts CSS on Express Checkout buttons to eliminate potential conflicts with some themes.
* Fix - Adds namespace to class names on checkout forms to eliminate potential conflicts with some themes.
* Tweak - Disables "Place Order" button on review order page to eliminate duplicate orders and/or errors during checkout.
* Tweak - Splits the ship to name returned from PayPal Express Checkout so that it's correctly entered into WooCommerce first and last name fields.
* Tweak - Updates PayPal Bill Me Later to PayPal Credit
* Tweak - Masks API credentials in API log files.
* Tweak - Adds length validation to Customer Service Phone number option in Express Checkout to eliminate warning codes (11835) from being returned.
* Tweak - Adds handling of PayPal error 10486 and returns the user to PayPal so they can choose another payment method per PayPal's documentation.
* Tweak - Adds the ship to phone number returned from Express Checkout to WooCommerce order details.
* Feature - Adds the ability to show/hide the Express Checkout button on the cart page.
* Feature - Adds hooks so that developers can override the template used for the Express Checkout review order page.
* Feature - Adds AVS and CVV2 response codes to WooCommerce order notes.
* Feature - Adds Payer Status and Address Status to WooCommerce order notes.
* Feature - Adds an option to enable/disable an admin email notification when PayPal errors occur.
* Feature - Adds the ability to include custom banner/logo for PayPal hosted checkout pages.
* Refactor - Updates function used to obtain currency code so that "currency switcher" plugins will work correctly with PayPal.

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

== Upgrade Notice ==

= 1.3.0 =
After updating, make sure to clear any caching / CDN plugins you may be using.  Also, go into the plugin's gateway settings, review everything, and click Save even if you do not make any changes.