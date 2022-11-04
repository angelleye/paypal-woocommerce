=== PayPal for WooCommerce ===
Contributors: angelleye, angelleyesupport, Umangvaghela
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=SG9SQU2GBXJNA
Tags: woocommerce, paypal, express checkout, payments pro, angelleye, payflow, dodirectpayment, apple pay, google play, braintree, payments advanced, rest, credit cards, credit card payments, payments, payment
Requires at least: 5.5
Tested up to: 6.1
Stable tag: 3.0.40
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

One plugin for all things PayPal!  Express Checkout with Smart Payment Buttons, PayPal Pro, Braintree with Apple and Google Pay, PayPal Advanced, and more!

== Description ==

= Introduction =

Easily add PayPal payment options to your WordPress / WooCommerce website.
 * PayPal Complete Payments
 * PayPal Express Checkout / PayPal Smart Payment Buttons
 * PayPal Website Payments Pro 3.0 (DoDirectPayment)
 * PayPal Payments Pro 2.0 (PayPal Manager / PayFlow Gateway)
 * PayPal Plus (Germany, Brazil, Mexico)
 * PayPal Payments Advanced
 * PayPal REST Credit Card Payments
 * PayPal Braintree Credit Card Payments
 * Fully Supports WooCommerce Payment Tokens!
 * Compatible with WooCommerce Subscriptions!
 
[youtube https://www.youtube.com/watch?v=svq9ovWGp7I]

[youtube https://www.youtube.com/watch?v=VhQT8rX7uwE]

= WooCommerce Payment Tokens Compatibility =
Fully supports WooCommerce payment tokens, so buyers can choose to save their payment method to their account with your site for quicker checkout in the future.

= WooCommerce Subscriptions Compatibility =
If you are using WooCommerce Subscriptions to manage subscription profiles you will be able to accept any of our PayPal payment gateways for subscription sign-up and payments.

= FREE PayPal Payments Pro Account =
By using our plugin we can get you hooked up with PayPal Payments Pro with no monthly fee! (U.S. and Canada merchants only.)

This availability is limited based on your monthly volume, so you will need to be doing at least $1,000/mo in sales in order to get this done.  If you are not at this level yet, enabling Express Checkout with our plugin will increase conversion rates on your site and help you get to that level.

[Submit a request](https://www.angelleye.com/free-paypal-pro-account-request/?utm_source=paypal-for-woocommerce&utm_medium=readme&utm_campaign=free_paypal_pro) and we'll get you hooked up!

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

* WooCommerce 3.0 or higher

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of PayPal for WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type PayPal for WooCommerce and click Search Plugins. Once you've found our plugin you can view details about it such as the the rating and description. Most importantly, of course, you can install it by simply clicking Install Now�?.

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
6. PayPal Credit banner ad displayed on site via Marketing Solutions option (contracted).
7. PayPal Credit banner ad displayed on site via Marketing Solutions option (expanded).

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

= 3.0.40 - 11.03.2022 =
* Feature - Adds 3DS2 for Braintree. ([PFW-1192](https://github.com/angelleye/paypal-woocommerce/pull/1748))
* Feature - Adds German Translation. ([PFW-1199](https://github.com/angelleye/paypal-woocommerce/pull/1747))
* Feature - Adds  push notification specific PFW gateway users. ([PFW-1204](https://github.com/angelleye/paypal-woocommerce/pull/1749))
* Fix -  Resolved issue when smart button disable on checkout page. ([PFW-1200](https://github.com/angelleye/paypal-woocommerce/pull/1746))

= 3.0.39 - 10.10.2022 =
* Fix - Use Product name as Item description value. ([PFW-1170](https://github.com/angelleye/paypal-woocommerce/pull/1737))
* Fix - Use WC inbuilt function. ([PFW-1190](https://github.com/angelleye/paypal-woocommerce/pull/1742))
* Fix - Resolved issue with disable specific cards. ([PFW-1196](https://github.com/angelleye/paypal-woocommerce/pull/1743))

= 3.0.38 - 09.22.2022 =
* Fix - Resolves issue with Payment action. ([PFW-1187](https://github.com/angelleye/paypal-woocommerce/pull/1741))

= 3.0.37 - 09.19.2022 =
* Fix - Resolves issue with Authorization and Capture History. ([PFW-1184](https://github.com/angelleye/paypal-woocommerce/pull/1739))

= 3.0.36 - 09.19.2022 =
* Feature - Adds  PPCP - Authorize and Partial Capture Functionality. ([PFW-1058](https://github.com/angelleye/paypal-woocommerce/pull/1728))
* Feature - Adds  PPCP - Product Level Option for Payment Action. ([PFW-1078](https://github.com/angelleye/paypal-woocommerce/pull/1729))
* Feature - Adds  PPCP - PayPal buttons displayed via shortcode. ([PFW-1160](https://github.com/angelleye/paypal-woocommerce/pull/1727))
* Tweak - Remove PPCP - 1st Party Credentials Compatibility. ([PFW-1062](https://github.com/angelleye/paypal-woocommerce/pull/1736))

= 3.0.35 - 08.15.2022 =
* Fix - Resolves PPCP - Refund Failure. ([PFW-1158](https://github.com/angelleye/paypal-woocommerce/pull/1726))

= 3.0.34 - 08.10.2022 =
* Fix - Resolves PPCP - Buttons showing up on product page for $0 items. ([PFW-1147](https://github.com/angelleye/paypal-woocommerce/pull/1721))
* Fix - Resolves PPCP - Province Validation on Final Review Page. ([PFW-1128](https://github.com/angelleye/paypal-woocommerce/pull/1722))
* Fix - Resolves PPCP - Error Handling. ([PFW-743](https://github.com/angelleye/paypal-woocommerce/pull/1723))
* Fix - Resolves PPCP - Checkout from My Account Page. ([PFW-1151](https://github.com/angelleye/paypal-woocommerce/pull/1724))
* Fix - Resolves PPCP - Conflict Variation Swatches for WooCommerce. ([PFW-1150](https://github.com/angelleye/paypal-woocommerce/pull/1725))

= 3.0.33 - 07.26.2022 =
* Feature - Adds  PPCP - Disable Line Items. ([PFW-1067](https://github.com/angelleye/paypal-woocommerce/pull/1720))

= 3.0.32 - 07.20.2022 =
* Fix - Resolves PPCP - Payment Method Title Disappeared from Front End Checkout. ([PFW-1117](https://github.com/angelleye/paypal-woocommerce/pull/1716))
* Fix - Resolves Conflict with Checkout WC. ([PFW-1118](https://github.com/angelleye/paypal-woocommerce/pull/1718))
* Fix - Resolves WC order issue. ([PFW-1116](https://github.com/angelleye/paypal-woocommerce/pull/1719))

= 3.0.31 - 07.11.2022 =
* Fix - Resolves PPCP - Phone Number Format. ([PFW-1081](https://github.com/angelleye/paypal-woocommerce/pull/1708))
* Fix - Resolves Conflict with WooCommerce Address Book. ([PFW-1079](https://github.com/angelleye/paypal-woocommerce/pull/1713))
* Fix - Resolves Conflict with Checkout WC. ([PFW-1113](https://github.com/angelleye/paypal-woocommerce/pull/1714))
* Feature - Adds PPCP - Checkout Page Options Enhancement. ([PFW-1072](https://github.com/angelleye/paypal-woocommerce/pull/1709))
* Feature - PPCP - Proper display of payment gateway in order details. ([PFW-1083](https://github.com/angelleye/paypal-woocommerce/pull/1710))
* Feature - Adds hook for CUSTOM parameter. ([PFW-1080](https://github.com/angelleye/paypal-woocommerce/pull/1711))
* Feature - Adds PPCP - PayPal button placement options for Cart and Checkout. ([PFW-1055](https://github.com/angelleye/paypal-woocommerce/pull/1715))

= 3.0.30 - 07.11.2022 =
* Feature - Adds new filter. ([PFW-1026](https://github.com/angelleye/paypal-woocommerce/pull/1704))
* Feature - PPCP Advanced Credit Cards for Germany. ([PFW-1042](https://github.com/angelleye/paypal-woocommerce/pull/1706))
* Fix - PPCP - Checkout Page - Translation Conflict. ([PFW-1069](https://github.com/angelleye/paypal-woocommerce/pull/1705))
* Fix - PPCP - Pay Later Messaging - Banner is floating on top of other page elements. ([PFW-1065](https://github.com/angelleye/paypal-woocommerce/pull/1703))
* Fix - PPCP - Final Review Page - Phone Number Handling. ([PFW-1068](https://github.com/angelleye/paypal-woocommerce/pull/1707))

= 3.0.29 - 06.29.2022 =
* Tweak - WooCommerce compatibility 6.6.0. ([PFW-1053](https://github.com/angelleye/paypal-woocommerce/pull/1700))
* Fix - PPCP - Woo order invoice not showing payment options properly. ([PFW-1048](https://github.com/angelleye/paypal-woocommerce/pull/1698))
* Fix - PPCP - Pay Later Messaging - Too many placements on checkout page. ([PFW-1046](https://github.com/angelleye/paypal-woocommerce/pull/1701))
* Fix - PPCP - Display header array in log file. ([PFW-1061](https://github.com/angelleye/paypal-woocommerce/pull/1699))
* Fix Resolves Cancel button not working on order review page. ([PFW-810](https://github.com/angelleye/paypal-woocommerce/pull/1702))

= 3.0.28 - 06.17.2022 =
* Fix - Resolves PPCP - Order Item Total Mismatch. ([PFW-1054](https://github.com/angelleye/paypal-woocommerce/pull/1697))

= 3.0.27 - 06.09.2022 =
* Feature -  WooCommerce TM Extra Product Options with PayPal Express. ([PFW-1025](https://github.com/angelleye/paypal-woocommerce/pull/1694))

= 3.0.26 - 06.03.2022 =
* Feature - Adds Payment Method on PPCP Review/Thank You page. ([PFW-1039](https://github.com/angelleye/paypal-woocommerce/pull/1691))
* Tweak - Use inbuilt function for payment description. ([PFW-1043](https://github.com/angelleye/paypal-woocommerce/pull/1692))

= 3.0.25 - 05.17.2022 =
* Tweak - Updates hook/filter to pass product name with product variation attribute. ([PFW-1026](https://github.com/angelleye/paypal-woocommerce/pull/1687))
* Tweak - Updates Forcefully show save button. ([PFW-1029](https://github.com/angelleye/paypal-woocommerce/pull/1688))
* Fix - Resolves PPCP - Buttons displaying on product page when they should not be. ([PFW-1032](https://github.com/angelleye/paypal-woocommerce/pull/1689))
* Fix - Resolves PHP error. ([PFW-1020](https://github.com/angelleye/paypal-woocommerce/pull/1690))
* Fix - Resolves PPCP - Pay Later Messaging - Use including tax price. ([PFW-1027](https://github.com/angelleye/paypal-woocommerce/pull/1686))

= 3.0.24 - 04.29.2022 =
* Tweak - Update .pot file. ([PFW-1019](https://github.com/angelleye/paypal-woocommerce/pull/1682))
* Tweak - Updates Partially Paid Email template Override. ([PFW-1021](https://github.com/angelleye/paypal-woocommerce/pull/1685))
* Fix - Resolves PPCP - Hide PayPal Button for Free product. ([PFW-1018](https://github.com/angelleye/paypal-woocommerce/pull/1683))
* Fix - Resolves PPCP - Hide/Show Setting fields. ([PFW-1017](https://github.com/angelleye/paypal-woocommerce/pull/1684))

= 3.0.23 - 04.18.2022 =
* Feature - PPCP By Default enable Pay Later button. ([PFW-1005](https://github.com/angelleye/paypal-woocommerce/pull/1680))
* Fix - Resolves PPCP - Billing Address / Phone Number Return. ([PFW-1016](https://github.com/angelleye/paypal-woocommerce/pull/1681))
* Fix - Resolves PPCP - PayPal Smart button not displaying when cart is not empty. ([PFW-1010](https://github.com/angelleye/paypal-woocommerce/pull/1677))
* Fix - WooCommerce Germanized Compatibility. ([PFW-1013](https://github.com/angelleye/paypal-woocommerce/pull/1678))
* Fix - Resolves Auto enabled Create an Account on order Review page. ([PFW-1012](https://github.com/angelleye/paypal-woocommerce/pull/1679))

= 3.0.22 - 04.05.2022 =
* Tweak - Remove merchant-id from PayPal JS SDK URL. ([PFW-1005](https://github.com/angelleye/paypal-woocommerce/pull/1676))

= 3.0.21 - 03.24.2022 =
* Feature - Adds PPCP - First Party Integration. ([PFW-986](https://github.com/angelleye/paypal-woocommerce/pull/1671))
* Fix - Resolves PPCP - Order Errors. ([PFW-992](https://github.com/angelleye/paypal-woocommerce/pull/1672))
* Fix - Resolves a issue with extra comma. ([PFW-1007](https://github.com/angelleye/paypal-woocommerce/pull/1673))
* Fix - Resolves PPCP - Method not supported or body is not present issue. ([PFW-1009](https://github.com/angelleye/paypal-woocommerce/pull/1675))

= 3.0.20 - 03.14.2022 =
* Fix - Resolves a issue with Refund order status when Multi-currency plugin active. ([PFW-996](https://github.com/angelleye/paypal-woocommerce/pull/1670))
* Feature - Add descriptor in ACH - Braintree. ([PFW-998](https://github.com/angelleye/paypal-woocommerce/pull/1669))

= 3.0.19 - 03.08.2022 =
* Fix - Resolves a issue with Product description limit. ([PFW-999](https://github.com/angelleye/paypal-woocommerce/pull/1668))

= 3.0.18 - 03.07.2022 =
* Feature - PPCP - Add filter for product name. ([PFW-975](https://github.com/angelleye/paypal-woocommerce/pull/1667))
* Fix - Resolves a issue with CartFlows Pro with PPCP. ([PFW-993](https://github.com/angelleye/paypal-woocommerce/pull/1666))

= 3.0.17 - 02.28.2022 =
* Feature -  CartFlows Pro Compatibility with PPCP. ([PFW-981](https://github.com/angelleye/paypal-woocommerce/pull/1663))
* Feature -  PPCP - ACC - Enable/Disable specific credit card types. ([PFW-980](https://github.com/angelleye/paypal-woocommerce/pull/1661))
* Tweak - Hide PayPal Billing Agreement field when PFWMA enable. ([PFW-984](https://github.com/angelleye/paypal-woocommerce/pull/1664))
* Tweak - PPCP - ACC - Improve CSS Styling. ([PFW-982](https://github.com/angelleye/paypal-woocommerce/pull/1662))
* Fix - Resolves a issue with PayPal request product name value. ([PFW-988](https://github.com/angelleye/paypal-woocommerce/pull/1660))

= 3.0.16 - 02.07.2022 =
* Fix - Replaces deprecated function. ([PFW-972](https://github.com/angelleye/paypal-woocommerce/pull/1655))
* Fix - Resolves a issue with PayPal payment status. ([PFW-970](https://github.com/angelleye/paypal-woocommerce/pull/1656))

= 3.0.15 - 01.26.2022 =
* Feature - Add filter for intent. ([PFW-829](https://github.com/angelleye/paypal-woocommerce/pull/1649))
* Tweak - Hide Billing Address option when Force shipping to the customer billing address is enable. ([PFW-963](https://github.com/angelleye/paypal-woocommerce/pull/1648))
* Tweak - Updates PPCP - ACC - Field Labels. ([PFW-966](https://github.com/angelleye/paypal-woocommerce/pull/1651))
* Fix - Resolves a issue with PayPal payment status. ([PFW-970](https://github.com/angelleye/paypal-woocommerce/pull/1652))
* Fix - Resolves a issue with PPCP - Different names in PayPal and Woo Billing/Shipping. ([PFW-968](https://github.com/angelleye/paypal-woocommerce/pull/1653))
* Fix - Resolves address not appearing issue at checkout. ([PFW-914](https://github.com/angelleye/paypal-woocommerce/pull/1654))
* Fix - Resolves a issue when getmypid() has been disabled for security reasons. ([PFW-965](https://github.com/angelleye/paypal-woocommerce/pull/1650))

= 3.0.14 - 01.11.2022 =
* Feature - Adds CartFlows Pro Pro 1.8.0 compatibility. 

= 3.0.13 - 01.10.2022 =
* Feature - Adds new funding methods list. ([PFW-950](https://github.com/angelleye/paypal-woocommerce/pull/1641))
* Feature - Adds PPCP - Smart Button Size and Height. ([PFW-912](https://github.com/angelleye/paypal-woocommerce/pull/1643))
* Fix - Resolves order status not updating after Capturing funds. ([PFW-955](https://github.com/angelleye/paypal-woocommerce/pull/1644))
* Fix - Resolves PayFlow conflict with WooCommerce Zapier plugin. ([PFW-867](https://github.com/angelleye/paypal-woocommerce/pull/1585))
* Fix - Resolves Compatibility issue with WPML plugin. ([PFW-958](https://github.com/angelleye/paypal-woocommerce/pull/1645))

= 3.0.12 - 12.27.2021 =
* Feature - Adds Gateway title and shorting functionality for Advanced Credit Cards. ([PFW-949](https://github.com/angelleye/paypal-woocommerce/pull/1640))
* Tweak - Resolves Advanced Credit Cards fields loading issue. ([PFW-935](https://github.com/angelleye/paypal-woocommerce/pull/1639))
* Fix - Resolves a issue with WooCommerce Bookings plugin. ([PFW-948](https://github.com/angelleye/paypal-woocommerce/pull/1638))

= 3.0.11 - 12.22.2021 =
* Feature - Adds Myworks.software compatibility. ([PFW-945](https://github.com/angelleye/paypal-woocommerce/pull/1637))
* Tweak - Resolves a issue with PayPal Fee showing up on non-PayPal orders. ([PFW-946](https://github.com/angelleye/paypal-woocommerce/pull/1636))

= 3.0.10 - 12.20.2021 =
* Fix - Resolves a hide/show issue with the Place order button. ([PFW-944](https://github.com/angelleye/paypal-woocommerce/pull/1635))

= 3.0.9 - 12.16.2021 =
* Fix - Resolves a problem processing refunds through WooCommerce with PayPal Express Checkout. ([PFW-940](https://github.com/angelleye/paypal-woocommerce/pull/1633))
* Fix - Resolves a issue with Force shipping to the customer billing address" option enable. ([PFW-942](https://github.com/angelleye/paypal-woocommerce/pull/1632))
* Fix - Resolves a issue with shipping address whrn local pickup shipping method used in PPCP payment method. ([PFW-941](https://github.com/angelleye/paypal-woocommerce/pull/1634))

= 3.0.8 - 12.13.2021 =
* Fix - Resolves decimal precision issue. ([PFW-938](https://github.com/angelleye/paypal-woocommerce/pull/1630))
* Fix - Resolves fatal error with WP REST API V3. ([PFW-939](https://github.com/angelleye/paypal-woocommerce/pull/1631))

= 3.0.7 - 12.03.2021 =
* Feature - Adds option to display separate Credit Card payment option in checkout page. ([PFW-930](https://github.com/angelleye/paypal-woocommerce/pull/1629))

= 3.0.6 - 11.29.2021 =
* Tweak - Adjustments to Prevent duplicate PayPal order payment. ([PFW-900](https://github.com/angelleye/paypal-woocommerce/pull/1627))
* Tweak - Adjustments to code to display PayPal Fee in order details page in admin side. ([PFW-907](https://github.com/angelleye/paypal-woocommerce/pull/1628))

= 3.0.5 - 11.27.2021 =
* Fix - Resolves an issue with Variable Products Not Opening PayPal Window. ([PFW-928](https://github.com/angelleye/paypal-woocommerce/pull/1626))
* Fix - Resolves Settings Page Empty issue when Woo Subscriptions plugin active. ([PFW-923](https://github.com/angelleye/paypal-woocommerce/pull/1625))

= 3.0.4 - 11.23.2021 =
* Feature - Adds Payment Method on PPCP Review/Thank You page. ([PFW-922](https://github.com/angelleye/paypal-woocommerce/pull/1624))
* Tweak - Adjustments to PPCP - On Hold / Failed Orders. ([PFW-927](https://github.com/angelleye/paypal-woocommerce/pull/1623))
* Fix - Resolves Create account option becomes unchecked in Final Review page issue. ([PFW-898](https://github.com/angelleye/paypal-woocommerce/pull/1621))
* Fix - Resolves an issue with PPCP - Credit Cards 3DS. ([PFW-921](https://github.com/angelleye/paypal-woocommerce/pull/1622))

= 3.0.3 - 11.17.2021 =
* Feature - Adds an option to enable/disable an admin email notification when PayPal errors occur. ([PFW-911](https://github.com/angelleye/paypal-woocommerce/issues/1618))
* Tweak - Adjustments to PayPal sdk function to avoid time error. ([PFW-917](https://github.com/angelleye/paypal-woocommerce/pull/1616))
* Tweak - Updates logic for localhost detection function. ([PFW-920](https://github.com/angelleye/paypal-woocommerce/pull/1619))
* Fix - Resolves an issue with PPCP custom_id value. ([PFW-913](https://github.com/angelleye/paypal-woocommerce/pull/1617))

= 3.0.2 - 11.15.2021 =
* Feature - Adds Soft Descriptor setting to credit card gateways so you can set what shows on customer credit card statements. ([PFW-910](https://github.com/angelleye/paypal-woocommerce/issues/1612))
* Feature - Adds Venmo Checkout Integration. ([PFW-885](https://github.com/angelleye/paypal-woocommerce/pull/1609))
* Tweak - Adjustments to avoid errors when item calculations from Woo are not accurate. ([PFW-904](https://github.com/angelleye/paypal-woocommerce/pull/1612))
* Tweak - Updates logic for Enable/Disable Advanced Credit Cards based on API response. ([PFW-894](https://github.com/angelleye/paypal-woocommerce/pull/1610))
* Tweak - Updates logic for Display Payment gateways when smart buttons are disabled. ([PFW-897](https://github.com/angelleye/paypal-woocommerce/pull/1613))
* Fix - Resolves an issue with Seller created Woo orders are not working properly. ([PFW-895](https://github.com/angelleye/paypal-woocommerce/pull/1611))
* Fix - Resolves an issue with PPCP to Unable hide debit/credit cards on the cart page. ([PFW-905](https://github.com/angelleye/paypal-woocommerce/pull/1614))
* Fix - Resolved shorting issue with payment gateways. ([PFW-896](https://github.com/angelleye/paypal-woocommerce/pull/1608))

= 3.0.1 - 11.02.2021 =
* Tweak - Adds Configure URL and Update plugin details. ([PFW-892](https://github.com/angelleye/paypal-woocommerce/pull/1605))
* Fix - Resolves Gateway setting page issue. ([PFW-893](https://github.com/angelleye/paypal-woocommerce/pull/1604))

= 3.0.0 - 11.02.2021 =
* Feature - Adds PayPal Complete Payments. ([PFW-27](https://github.com/angelleye/paypal-woocommerce/pull/1603))

= 2.5.19 - 18.10.2021 =
* Feature - Re-Added required hooks for our PayPal for WooCommerce Multi-Account extension. ([PFW-813](https://github.com/angelleye/paypal-woocommerce/pull/1557))
* Tweak - Adjustments for PHP 8.0 compatibility. ([PFW-863](https://github.com/angelleye/paypal-woocommerce/pull/1583))
* Fix - Resolves Conflict with All Import Pro plugin. ([PFW-866](https://github.com/angelleye/paypal-woocommerce/pull/1586))
* Fix - Resolves an issue with Express Checkout when Sending invoice from admin. ([PFW-853](https://github.com/angelleye/paypal-woocommerce/issues/1580))


= 2.5.18- 10.07.2021 =
* Tweak - Updates Partially Paid Email template Override. ([PFW-858](https://github.com/angelleye/paypal-woocommerce/pull/1579))
* Fix - Resolves CSS Conflict. ([PFW-864](https://github.com/angelleye/paypal-woocommerce/pull/1581))

= 2.5.17 - 09.30.2021 =
* Tweak - Updates Braintree PayPal checkout type. ([PFW-494](https://github.com/angelleye/paypal-woocommerce/pull/1575))
* Feature - Adds custom class at order page. ([PFW-152](https://github.com/angelleye/paypal-woocommerce/pull/1578))

= 2.5.16 - 09.16.2021 =
* Fix - Resolves PHP notice related to checkout page. ([PFW-856](https://github.com/angelleye/paypal-woocommerce/pull/1577))

= 2.5.15 - 09.13.2021 =
* Tweak - Upgrades Braintree Drop-in JS ([PFW-847](https://github.com/angelleye/paypal-woocommerce/pull/1573))
* Fix - Resolves a PHP error, when PHP 8.0 version. ([PFW-839](https://github.com/angelleye/paypal-woocommerce/issues/1572))

= 2.5.14 - 08.20.2021 =
* Tweak - Adjusts CSS for Cancel Order button on Express Checkout review page. ([PFW-841](https://github.com/angelleye/paypal-woocommerce/pull/1567))
* Fix - Resolves Payment Gateway Sorting issue. ([PFW-840](https://github.com/angelleye/paypal-woocommerce/pull/1566))

= 2.5.13 - 08.20.2021 =
* Fix - Resolves Braintree Compatibility with WooCommerce 2.6.2 issue. ([PFW-842](https://github.com/angelleye/paypal-woocommerce/pull/1568))

= 2.5.12 - 07.19.2021 =
* Fix - Resolves an issue with CartFlow with subscription. ([PFW-820](https://github.com/angelleye/paypal-woocommerce/pull/1562))

= 2.5.11 - 07.12.2021 =
* Tweak - Updates Update Braintree SDK. ([PFW-824](https://github.com/angelleye/paypal-woocommerce/pull/1560))

= 2.5.10 - 07.01.2021 =
* Fix - Resolves SG Optimizer issue. ([PFW-815](https://github.com/angelleye/paypal-woocommerce/pull/1558))

= 2.5.9 - 05.25.2021 =
* Fix - Resolves Braintree ACH issue. ([PFW-806](https://github.com/angelleye/paypal-woocommerce/pull/1552))

= 2.5.8 - 05.18.2021 =
* Feature - Adds Webtoffee Subscriptions compatibility. ([PFW-792](https://github.com/angelleye/paypal-woocommerce/pull/1549))

= 2.5.7 - 04.13.2021 =
* Feature - Add WCVendor Compatibility with Enable Express Checkout Option. ([PFW-791](https://github.com/angelleye/paypal-woocommerce/pull/1545))

= 2.5.6 - 04.05.2021 =
* Fix - Adjust Line Item for Discounted Orders. ([PFW-713](https://github.com/angelleye/paypal-woocommerce/pull/1543))
* Fix - Adjust subscription order status based on gateway setting. ([PR-1542](https://github.com/angelleye/paypal-woocommerce/pull/1542))

= 2.5.5 - 03.30.2021 =
* Feature - Adds option for Enable Express Checkout by default on all new products. ([PFW-748](https://github.com/angelleye/paypal-woocommerce/pull/1541))
* Fix - Adjust Hide/Show PayPal Credit gateway icon. ([PFW-787](https://github.com/angelleye/paypal-woocommerce/pull/1540))
* Fix - Resolves Woo Checkout and PayPal Address Override issue. ([PFW-785](https://github.com/angelleye/paypal-woocommerce/pull/1539))

= 2.5.4 - 03.22.2021 =
* Fix - Resolves Products with no shipping cost issue. ([PFW-770](https://github.com/angelleye/paypal-woocommerce/pull/1534))
* Fix - Resolves a PHP error, call to a member function get(). ([PFW-771](https://github.com/angelleye/paypal-woocommerce/pull/1538))

= 2.5.3 - 03.19.2021 =
* Fix - Resolves PayPal Smart Buttons - Guest Checkout spinner issue. ([PFW-783](https://github.com/angelleye/paypal-woocommerce/pull/1537))

= 2.5.2 - 03.10.2021 =
* Fix - Adjust Buy Now Pay Later Messaging. ([PFW-767](https://github.com/angelleye/paypal-woocommerce/pull/1533))

= 2.5.1 - 02.22.2021 =
* Feature - Adds SiteGround SG Optimize compatibility. ([PFW-709](https://github.com/angelleye/paypal-woocommerce/pull/1529))
* Fix - Adjust Buy Now Pay Later Messaging. ([PFW-760](https://github.com/angelleye/paypal-woocommerce/pull/1530))

= 2.5.0 - 02.14.2021 =
* Feature - Adds Braintree ACH Payment. ([PFW-725](https://github.com/angelleye/paypal-woocommerce/pull/1528))
* Feature - Adds FraudNet Integration. ([PFW-712](https://github.com/angelleye/paypal-woocommerce/pull/1522))
* Fix - Resolves Woo Smart Coupons Compatibility issue. ([PFW-140](https://github.com/angelleye/paypal-woocommerce/issues/1526))

= 2.4.9 - 01.18.2020 =
* Fix - Resolves Express Checkout - Smart Buttons issue. ([PFW-753](https://github.com/angelleye/paypal-woocommerce/pull/1524))

= 2.4.8 - 01.14.2020 =
* Fix - Adjust string for use with WPML translation. ([PFW-724](https://github.com/angelleye/paypal-woocommerce/pull/1519))
* Fix - Improve smart button loading time. ([PFW-718](https://github.com/angelleye/paypal-woocommerce/pull/1520))
* Fix - Resolves Cartflow with subscription issue. ([PFW-723](https://github.com/angelleye/paypal-woocommerce/pull/1521))
* Fix - Resolves Express Checkout - Smart Buttons sometimes load on checkout and sometimes do not issue. ([PFW-720](https://github.com/angelleye/paypal-woocommerce/pull/1523))

= 2.4.7 - 12.14.2020 =
* Feature - Adds CartFlows Pro compatibility. ([PFW-717](https://github.com/angelleye/paypal-woocommerce/pull/1513))
* Tweak - Resolves Transaction ID is not getting saved correctly. ([PFW-722](https://github.com/angelleye/paypal-woocommerce/issues/1515))
* Fix - Resolves Smart Buttons JS issue. ([PFW-721](https://github.com/angelleye/paypal-woocommerce/issues/1514))

= 2.4.6 - 11.13.2020 =
* Tweak - Update .pot file. ([PFW-696](https://github.com/angelleye/paypal-woocommerce/issues/1509))
* Tweak - Resolves Smart Buttons Sometimes Not Showing Up issue. ([PFW-711](https://github.com/angelleye/paypal-woocommerce/issues/1510))
* Tweak - Display BNPL only for US country. ([PFW-704](https://github.com/angelleye/paypal-woocommerce/issues/1511))
* Fix - Resolves Smart Buttons Alignment issue. ([PFW-708](https://github.com/angelleye/paypal-woocommerce/issues/1506))
* Fix - Resolves API Credentials issue. ([PFW-638](https://github.com/angelleye/paypal-woocommerce/issues/1508))

= 2.4.5 - 10.29.2020 =
* Fix - Resolves Woo Smart Coupons Compatibility issue. ([PFW-140](https://github.com/angelleye/paypal-woocommerce/issues/1505))

= 2.4.4 - 10.20.2020 =
* Fix - Resolves Conflict with WooCommerce Stripe Payment Gateway. ([PFW-698](https://github.com/angelleye/paypal-woocommerce/pull/1504))

= 2.4.3 - 10.15.2020 =
* Verification - WooCommerce 4.6.0 compatibility.

= 2.4.2 - 10.12.2020 =
* Feature - Adds Trigger event for PayPal Smart button action. ([PFW-678](https://github.com/angelleye/paypal-woocommerce/pull/1503))
* Tweak - Enable PayPal Credit Messaging (BNPL) for all countries. ([PFW-699](https://github.com/angelleye/paypal-woocommerce/pull/1501))
* Tweak - Remove WooCoomerce Email filter. ([PFW-695](https://github.com/angelleye/paypal-woocommerce/pull/1497))
* Fix - Resolves Google reCAPTCHA v3 related issue. ([PFW-702](https://github.com/angelleye/paypal-woocommerce/pull/1502))

= 2.4.1 - 10.04.2020 =
* Fix - Resolves PayPal Credit Messaging related issue. ([PFW-697](https://github.com/angelleye/paypal-woocommerce/pull/1500))

= 2.4.0 - 09.28.2020 =
* Feature - Upgrades PayPal Smart Button JS. ([PFW-682](https://github.com/angelleye/paypal-woocommerce/pull/1490)) ([PFW-692](https://github.com/angelleye/paypal-woocommerce/pull/1495))
* Feature - Adds PayPal Credit Messaging - Buy Now Pay Later. ([PFW-679](https://github.com/angelleye/paypal-woocommerce/pull/1493)) ([PFW-692](https://github.com/angelleye/paypal-woocommerce/pull/1495))
* Tweak - Adds Deprecation notice for the PayPal Credit Card Rest API. ([PFW-685](https://github.com/angelleye/paypal-woocommerce/pull/1489))
* Tweak - Removes meta tag for PayPal Smart button. ([PFW-676](https://github.com/angelleye/paypal-woocommerce/pull/1492))
* Tweak - Removes the Version string from HTML header. ([PFW-694](https://github.com/angelleye/paypal-woocommerce/pull/1494)) ([PFW-694-A](https://github.com/angelleye/paypal-woocommerce/pull/1496))

= 2.3.8 - 09.09.2020 =
* Verification - WooCommerce 4.5.0 compatibility.

= 2.3.7 - 09.04.2020 =
* Verification - WooCommerce 4.4.1 and WordPress 5.5.1 compatibility.

= 2.3.6 - 08.26.2020 =
* Fix - "WooCommerce Multilingual Multi-Currency compability" plugin compatibility. ([PFW-672](https://github.com/angelleye/paypal-woocommerce/pull/1487))

= 2.3.5 - 08.20.2020 =
* Fix - Resolves a PHP error, Reference Transaction from admin side. ([PFW-674](https://github.com/angelleye/paypal-woocommerce/pull/1485))
* Tweak - Adds WooCommerce 4.4.0 Compatibility. ([PFW-675](https://github.com/angelleye/paypal-woocommerce/pull/1486))

= 2.3.4 - 08.17.2020 =
* Fix - Adds WooCommerce 3.6.3 Compatibility. ([PFW-673](https://github.com/angelleye/paypal-woocommerce/pull/1484))

= 2.3.3 - 08.08.2020 =
* Feature - Google reCAPTCHA v3 on Checkout page with PayFlow and DDP Gateway ([PFW-668](https://github.com/angelleye/paypal-woocommerce/pull/1483))
* Tweak - Removed "Token Payment Not Available with PFWMA" admin notice ([PFW-669](https://github.com/angelleye/paypal-woocommerce/pull/1482))

= 2.3.2 - 07.30.2020 =
* Fix - Smart Button CSS Conflict ([PFW-662](https://github.com/angelleye/paypal-woocommerce/pull/1481))
* Fix - Credit Card Expiration Date Fields CSS Conflict ([PFW-661](https://github.com/angelleye/paypal-woocommerce/pull/1480))
* Fix - DDP and Pay Flow Credit Card Holder Name Field Issue ([PFW-667](https://github.com/angelleye/paypal-woocommerce/pull/1479))
* Fix - Invalid Email Results in A 404 Page Instead of The Order Review Page ([PFW-666](https://github.com/angelleye/paypal-woocommerce/pull/1478))

= 2.3.1 - 07.22.2020 =
* Fix - Fatal error resolved with the Session Function ([PFW-664](https://github.com/angelleye/paypal-woocommerce/pull/1477))

= 2.3.0 - 07.22.2020 =
* Fix - General improvement with WooCommerce Session Object Initialization and Validation ([PFW-664](https://github.com/angelleye/paypal-woocommerce/pull/1477))
* Fix - Express checkout compatibility with Woo Subscription for the New Users ([PFW-663](https://github.com/angelleye/paypal-woocommerce/pull/1476))
* Fix - PayPal Pro Cardholder first name and last name issue ([PFW-656](https://github.com/angelleye/paypal-woocommerce/pull/1475))
* Fix - General improvement with order object validation to avoid fatal errors ([PFW-658](https://github.com/angelleye/paypal-woocommerce/pull/1474))

= 2.2.10 - 07.13.2020 =
* Fix - Smart button responsive issue resolved ([PFW-625](https://github.com/angelleye/paypal-woocommerce/pull/1473))
* Tweak - Translation file update ([PFW-647](https://github.com/angelleye/paypal-woocommerce/pull/1472))
* Fix - Billing address override issue resolved with Express Checkout ([PFW-652](https://github.com/angelleye/paypal-woocommerce/pull/1470))
* Fix - Allow users to Re-enable Smart Payment Buttons once it's disabled ([PFW-654](https://github.com/angelleye/paypal-woocommerce/pull/1471))

= 2.2.9 - 07.02.2020 =
* Fix - Resolves Special character issue with API password ([PFW-650](https://github.com/angelleye/paypal-woocommerce/pull/1469))

= 2.2.8 - 07.01.2020 =
* Feature - Compatibility added for Split Payment across Credit Cards ([PFW-642](https://github.com/angelleye/paypal-woocommerce/pull/1466))

= 2.2.7 - 07.01.2020 =
* Fix - Special character support added for API Username ([PFW-645](https://github.com/angelleye/paypal-woocommerce/pull/1468))
* Tweak - UI fixes on Settings page ([PFW-646](https://github.com/angelleye/paypal-woocommerce/pull/1467))

= 2.2.6 - 06.24.2020 =
* Fix - Update PayPal Payflow Iframe ([PFW-644](https://github.com/angelleye/paypal-woocommerce/pull/1465))
* Tweak - Improved Partial Success Email Template ([PFW-643](https://github.com/angelleye/paypal-woocommerce/pull/1464))

= 2.2.5 - 06.10.2020 =
* Feature - Product Level Authorization and Capture with Express Checkout and Braintree Payment Gateway ([PFW-560](https://github.com/angelleye/paypal-woocommerce/pull/1441))
* Tweak - Improved data accessors to use WooCommerce functions ([PFW-619](https://github.com/angelleye/paypal-woocommerce/pull/1462))
* Fix - Call get_cart function after wp_loaded action ([PFW-641](https://github.com/angelleye/paypal-woocommerce/pull/1460))
* Fix - Cartflow discount issue resolved ([PFW-640](https://github.com/angelleye/paypal-woocommerce/pull/1459))
* Tweak - Partial Success Order Handling ([PFW-636](https://github.com/angelleye/paypal-woocommerce/pull/1458))

= 2.2.4 - 04.06.2020 =
* Compatibility with WooCommerce 4.2 & WordPress 5.4.1

= 2.2.3 - 05.07.2020 =
* Tweak - Added the PPREF Meta Key for the Payflow Payment Processor Gateway ([PFW-611](https://github.com/angelleye/paypal-woocommerce/pull/1452))
* Tweak - Woo Checkout Add-Ons Compatibility ([PFW-614](https://github.com/angelleye/paypal-woocommerce/pull/1455))
* Fix - Fixes Braintree requirement validation notice ([PFW-603](https://github.com/angelleye/paypal-woocommerce/pull/1456))

= 2.2.2 - 04.30.2020 =
* Fix - Resolves fatal error on WooCommerce Payment methods page & Cartflows checkout ([PFW-602](https://github.com/angelleye/paypal-woocommerce/pull/1450))

= 2.2.1 - 04.30.2020 =
* Tweak - PayFlow - Memo Field Adjustment ([PFW-596](https://github.com/angelleye/paypal-woocommerce/pull/1449))

= 2.2.0 - 04.29.2020 =
* Feature - Upgrades Braintree SDK from 3.34.0. to 5.0.0 ([PFW-595](https://github.com/angelleye/paypal-woocommerce/pull/1448))
* Tweak - Added filter to override allowed product type with express checkout payment method. ([PFW-587](https://github.com/angelleye/paypal-woocommerce/pull/1447))

= 2.1.16 - 04.07.2020 =
* Verification - WooCommerce 4.0.1 and WordPress 5.4 compatibility.

= 2.1.15 - 03.27.2020 =
* Tweak - Adds required hooks for our PayPal Shipment tracking. ([PFW-563](https://github.com/angelleye/paypal-woocommerce/pull/1423))
* Tweak – Adjustments “Thank you�? page message. ([PFW-566](https://github.com/angelleye/paypal-woocommerce/pull/1425))
* Fix - Resolves jQuery conflict. ([PFW-565](https://github.com/angelleye/paypal-woocommerce/pull/1424))
* Fix - Correct year format as per cardinal commerce docs. ([#1430](https://github.com/angelleye/paypal-woocommerce/pull/1430))
* Fix - "WooCommerce Booster Currency Switcher" plugin compatibility. ([PFW-571](https://github.com/angelleye/paypal-woocommerce/pull/1429))
* Fix - Resolves a conflict with multi-currency sites with Braintree. ([PFW-574](https://github.com/angelleye/paypal-woocommerce/pull/1434))
* Fix - Resolves a conflict with the Express Checkout PHP code. ([PFW-578](https://github.com/angelleye/paypal-woocommerce/pull/1436))

= 2.1.14 - 02.03.2020 =
* Fix - Resolves an issue with W3 Total Cache. ([PFW-561](https://github.com/angelleye/paypal-woocommerce/pull/1422))

= 2.1.13 - 01.29.2020 =
* Feature - Adds WooCommerce 3.8.0 Compatibility. ([PFW-554](https://github.com/angelleye/paypal-woocommerce/pull/1416)) ([PFW-553](https://github.com/angelleye/paypal-woocommerce/pull/1418))
* Tweak – Adjustments “Thank you�? page message to comply with PayPal Guidelines. ([PFW-552](https://github.com/angelleye/paypal-woocommerce/pull/1414))
* Tweak - Adjustments to admin notice display. ([PFW-531](https://github.com/angelleye/paypal-woocommerce/pull/1417))
* Fix - Resolves some PHP notices. ([PFW-66](https://github.com/angelleye/paypal-woocommerce/pull/1409)) ([PFW-163](https://github.com/angelleye/paypal-woocommerce/pull/1410))
* Fix - Resolves an issue with Woo Subscriptions - Refund. ([PFW-550](https://github.com/angelleye/paypal-woocommerce/pull/1411))
* Fix - Resolves an issue with Braintree - Soft Descriptor. ([PFW-69](https://github.com/angelleye/paypal-woocommerce/pull/1412))
* Fix - Resolves an issue with PayPal Express Checkout - Authorization. ([PFW-514](https://github.com/angelleye/paypal-woocommerce/pull/1413))
* Fix - Resolves an issue were Smart button not displaying with Shortcode. ([PFW-94](https://github.com/angelleye/paypal-woocommerce/pull/1415))

= 2.1.12 - 12.30.2019 =
* Tweak - Adjustment to Express Checkout PayPal error 10486 and returns the user to PayPal so they can choose another payment method per PayPal's documentation. ([PFW-466](https://github.com/angelleye/paypal-woocommerce/pull/1406))
* Tweak - Adjustments to PayPal SuccessWithWarning Handling. ([PFW-529](https://github.com/angelleye/paypal-woocommerce/pull/1407))

= 2.1.11 - 12.23.2019 =
* Tweak - Adjustments to PayPal SuccessWithWarning Handling. ([PFW-529](https://github.com/angelleye/paypal-woocommerce/pull/1402))
* Tweak - Adjustments to Sandbox details Notice don't show when description is empty. ([PFW-541](https://github.com/angelleye/paypal-woocommerce/pull/1403))
* Tweak - Adjustment to Updater plugin notice dismissible. ([PFW-536](https://github.com/angelleye/paypal-woocommerce/pull/1404))

= 2.1.10 - 12.10.2019 =
* Tweak - CSS tweaks to resolve viewed on a small/mobile device payment gateway setting issues. ([PFW-518](https://github.com/angelleye/paypal-woocommerce/pull/1392))
* Tweak - Adjustment to Express Checkout - Product Page Cancel. ([PFW-519](https://github.com/angelleye/paypal-woocommerce/pull/1393))
* Tweak - Adjustment to Express Checkout - Checkout Page Display. ([PFW-532](https://github.com/angelleye/paypal-woocommerce/pull/1395))
* Tweak - Adjustment to Updater plugin notice dismissible. ([PFW-536](https://github.com/angelleye/paypal-woocommerce/pull/1396))
* Tweak - Improves CSS styles on Credit Card forms. ([PFW-530](https://github.com/angelleye/paypal-woocommerce/pull/1398))
* Tweak - Adjustment to Express Checkout - Smart Button Options. ([PFW-523](https://github.com/angelleye/paypal-woocommerce/pull/1401))
* Fix - Resolves an issue with CartFlow Reference Transactions. ([PFW-537](https://github.com/angelleye/paypal-woocommerce/pull/1399))

= 2.1.9 - 11.20.2019 =
* Fix - Resolves some PHP notices. ([PFW-512](https://github.com/angelleye/paypal-woocommerce/pull/1390))
* Fix - Resolves an issue where Express Checkout - Smart Buttons Product Page option conflict with Minicart. ([PFW-520](https://github.com/angelleye/paypal-woocommerce/pull/1391))

= 2.1.8 - 11.05.2019 =
* Feature - Adds WooCommerce 3.8.0 Compatibility. ([PFW-522](https://github.com/angelleye/paypal-woocommerce/pull/1389))

= 2.1.7 - 11.05.2019 =
* Tweak - Adjustment to Express Checkout - Smart Button Options. ([PFW-477](https://github.com/angelleye/paypal-woocommerce/pull/1388))
* Tweak - Adjustment to Express Checkout Minicart by default disable. ([PFW-516](https://github.com/angelleye/paypal-woocommerce/pull/1386))
* Tweak - Adjustment to Express Checkout Hide all smart button option when Enable Smart Buttons is disable. ([PFW-517](https://github.com/angelleye/paypal-woocommerce/pull/1387))

= 2.1.6 - 10.25.2019 =
* Tweak - Disable Token Payments when Multi-Account plugin enable. ([PFW-509](https://github.com/angelleye/paypal-woocommerce/pull/1385))
* Fix - Resolves an issue where the wrong payment method displaying on order detail page. ([PFW-505](https://github.com/angelleye/paypal-woocommerce/pull/1380))
* Fix - Resolves an issue with Braintree where place order not working. ([PFW-506](https://github.com/angelleye/paypal-woocommerce/pull/1382))
* Fix - Resolves an issue with Braintree where saved cards not displaying on Payment Methods Page. ([PFW-507](https://github.com/angelleye/paypal-woocommerce/pull/1383))
* Fix - Resolves an issue with customer IP address in API requests. ([PFW-484](https://github.com/angelleye/paypal-woocommerce/pull/1381))
* Fix - Resolves conflict with CartFlows Settings plugin. ([PFW-510](https://github.com/angelleye/paypal-woocommerce/pull/1384))

= 2.1.5 - 10.15.2019 =
* Feature - Adds 3DSecure to PayPal Payments Pro 2.0 (PayFlow). ([PFW-408](https://github.com/angelleye/paypal-woocommerce/pull/1373))
* Tweak - Adjustment default sandbox credentials for PayFlow so you don't have to setup your own test account for sandbox testing. ([PFW-495](https://github.com/angelleye/paypal-woocommerce/pull/1370))
* Fix - Resolves an issue where the shipping address was not displaying on thank you page. ([PFW-498](https://github.com/angelleye/paypal-woocommerce/pull/1368))
* Fix - Resolves an issue where No spinner when clicking Continue / Pay Now from the PayPal pop. ([PFW-497](https://github.com/angelleye/paypal-woocommerce/pull/1369))
* Fix - Resolves an issue where the Use a new payment method displaying on checkout page. ([PFW-503](https://github.com/angelleye/paypal-woocommerce/pull/1372))
* Fix - Resolves an issue where the checkout error was not displaying on cartflow page. ([PFW-502](https://github.com/angelleye/paypal-woocommerce/pull/1371))

= 2.1.4 - 10.08.2019 =
* Fix - Resolves an issue with checkout page validation focus. ([PFW-487](https://github.com/angelleye/paypal-woocommerce/pull/1366))
* Fix - Re-Added required hooks for our PayPal for WooCommerce Multi-Account extension. ([PFW-496](https://github.com/angelleye/paypal-woocommerce/pull/1367))

= 2.1.3 - 10.03.2019 =
* Tweak - Adds required hooks for our PayPal for WooCommerce Multi-Account extension. ([PFW-464](https://github.com/angelleye/paypal-woocommerce/pull/1364))
* Feature - Adds CartFlows compatibility. ([PFW-493](https://github.com/angelleye/paypal-woocommerce/pull/1365))

= 2.1.2 - 09.17.2019 =
* Tweak - Adjustment to ensure the PayPal Express Checkout button on checkout pages validation display on checkout page. ([PFW-479](https://github.com/angelleye/paypal-woocommerce/pull/1358))
* Tweak - Adjustments to CartFlows compatibility with bump order. ([PFW-482](https://github.com/angelleye/paypal-woocommerce/pull/1359))
* Tweak - Adjustments to Braintree gateway always send merchant_account_id when it available. ([#1361](https://github.com/angelleye/paypal-woocommerce/pull/1361))
* Tweak - Adjustments to CartFlows compatibility with pre checkout offer. ([PFW-488](https://github.com/angelleye/paypal-woocommerce/pull/1363))
* Fix - Resolves an issue with Cartflow plugin were Discount Values getting Ignored. ([PFW-481](https://github.com/angelleye/paypal-woocommerce/pull/1357))

= 2.1.1 - 09.02.2019 =
* Feature - Adds CartFlows compatibility. ([PFW-461](https://github.com/angelleye/paypal-woocommerce/pull/1355))
* Feature - Adds hook for Differentiate between Woo checkout and PayPal review page. ([PFW-452](https://github.com/angelleye/paypal-woocommerce/pull/1350))
* Feature - Adds WooCommerce 3.7 Compatibility. ([PFW-463](https://github.com/angelleye/paypal-woocommerce/pull/1356))
* Tweak - Adjustments to Marketing Sidebar - Newsletter Signup Adjustment use sendy. ([PFW-460](https://github.com/angelleye/paypal-woocommerce/pull/1344))
* Tweak - Adjustments to PayPal SuccessWithWarning Handling. ([PFW-454](https://github.com/angelleye/paypal-woocommerce/pull/1349))
* Tweak - Adjustments to Line Item Details - Subtotal Mismatch Behavior Option. ([PFW-458](https://github.com/angelleye/paypal-woocommerce/pull/1354))
* Fix - Resolves an issue with Express Checkout PayPal buttons hide when used upsell product. ([PFW-427](https://github.com/angelleye/paypal-woocommerce/pull/1345))
* Fix - Resolves an issue with Express Checkout email and phone number not pass in request. ([PFW-459](https://github.com/angelleye/paypal-woocommerce/pull/1347))
* Fix - Resolves an issue with WP Widget Title. ([PFW-462](https://github.com/angelleye/paypal-woocommerce/pull/1348))
* Fix - Resolves PHP notice related to Braintree option. ([PFW-467](https://github.com/angelleye/paypal-woocommerce/pull/1351))
* Fix - Resolves a Tokenization problem with Braintree. ([PFW-469](https://github.com/angelleye/paypal-woocommerce/pull/1352))
* Fix - Resolves an issue with Aelia Multi-Currency plugin were Tax Decimal Values getting Ignored. ([PFW-412](https://github.com/angelleye/paypal-woocommerce/pull/1353))

= 2.1.0 - 07.14.2019 =
* Feature - Multi-Currency Suite compatibility. ([PFW-148](https://github.com/angelleye/paypal-woocommerce/pull/1322))
* Fix - Resolves an issue with Express Checkout as a Missing Parameter on subscription purchases. ([PFW-418](https://github.com/angelleye/paypal-woocommerce/pull/1316))
* Fix - Resolves an issue with Express Checkout as a Missing PayerID. ([PFW-437](https://github.com/angelleye/paypal-woocommerce/pull/1332))
* Fix - Resolves an issue with Express Checkout Billing Agreement Bug. ([PFW-429](https://github.com/angelleye/paypal-woocommerce/pull/1321)) ([PFW-457](https://github.com/angelleye/paypal-woocommerce/pull/1343))
* Fix - Resolves an issue where disabling PayPal buttons in mini-cart would sometimes disable them from Woo checkout page as well. ([PFW-430](https://github.com/angelleye/paypal-woocommerce/pull/1324))
* Fix - Resolves typos in Express Checkout Page Display setting. ([PFW-421](https://github.com/angelleye/paypal-woocommerce/pull/1325))
* Fix - Resolves an issue with PayPal Payflow where AVS & Card Security Code display Multiple time on Mail. ([PFW-431](https://github.com/angelleye/paypal-woocommerce/pull/1326))
* Fix - Resolves a Compatibility issue with Woo Subscriptions. ([PFW-433](https://github.com/angelleye/paypal-woocommerce/pull/1327))
* Fix - Updates push notification system sync time interval. ([PFW-435](https://github.com/angelleye/paypal-woocommerce/pull/1327))
* Fix - Resolves an issue with PayPal sandbox / test mode at the product level. ([PFW-409](https://github.com/angelleye/paypal-woocommerce/pull/1331))
* Fix - Resolves a problem with PayPal Pro when the subtotal is zero and all you are paying for is shipping. ([PFW-425](https://github.com/angelleye/paypal-woocommerce/pull/1333)) ([PFW-447](https://github.com/angelleye/paypal-woocommerce/pull/1335))
* Fix - Resolves a problem with Tokenization and Multi-Account plugin ([PFW-449](https://github.com/angelleye/paypal-woocommerce/pull/1336))
* Fix - Resolves a problem with duplicate opt-in activation logging notice. ([PFW-451](https://github.com/angelleye/paypal-woocommerce/pull/1338))
* Fix - Resolves conflict with Braintree for GravityForms plugin. ([PFW-453](https://github.com/angelleye/paypal-woocommerce/pull/1340))
* Tweak - Stops stripping special characters from Invoice ID value passed to PayPal. ([PFW-434](https://github.com/angelleye/paypal-woocommerce/pull/1341))
* Tweak - Performance improvements. ([PFW-406](https://github.com/angelleye/paypal-woocommerce/pull/1320))
* Tweak - Adjustments to prevent duplicate order with Express Checkout. ([#1329](https://github.com/angelleye/paypal-woocommerce/pull/1329))
* Tweak - Adjustments to Express Checkout Tokenization checkbox html. ([PFW-442](https://github.com/angelleye/paypal-woocommerce/pull/1334))
* Tweak - Adjustments to Express Checkout PayPal Credit to Enable for all the Country. ([PFW-456](https://github.com/angelleye/paypal-woocommerce/pull/1342))

= 2.0.1 - 05.31.2019 =
* Feature - Adds autocomplete=new-password to all API credential fields in settings so that password managers will not interfere.  ([PFW-403](https://github.com/angelleye/paypal-woocommerce/pull/1314))
* Tweak - Adjustments to admin notice display. ([PFW-410](https://github.com/angelleye/paypal-woocommerce/pull/1312))
* Tweak - Adjustments to handle orders with decimal value quantities. ([PFW-414](https://github.com/angelleye/paypal-woocommerce/pull/1315))
* Tweak - Check for token match on return to site, and resolve fatal error. ([PFW-424](https://github.com/angelleye/paypal-woocommerce/pull/1317))
* Fix - Resolves an issue with saving Express Checkout as a token payment method. ([PFW-426](https://github.com/angelleye/paypal-woocommerce/pull/1318))
* Fix - Resolves an unexpected token error that happens on rare occasions. ([PFW-428](https://github.com/angelleye/paypal-woocommerce/pull/1319))

= 2.0.0 - 04.30.2019 =
* Feature - Adds compatibility for our Updater plugin to get future notices and automated updates. ([PFWA-31](https://github.com/angelleye/paypal-woocommerce/pull/1292)) ([PFW-396](https://github.com/angelleye/paypal-woocommerce/pull/1305))
* Feature - Adds push notification system and settings sidebar. ([PFW-343](https://github.com/angelleye/paypal-woocommerce/pull/1279)) ([PFWA-3](https://github.com/angelleye/paypal-woocommerce/pull/1285)) ([PFW-399](https://github.com/angelleye/paypal-woocommerce/pull/1307))
* Feature - Adds ability to disable PayPal Smart Buttons in the Woo Checkout page gateway list. ([PFW-359](https://github.com/angelleye/paypal-woocommerce/pull/1277))
* Feature - Woo Side Cart compatibility. ([PFWA-160](https://github.com/angelleye/paypal-woocommerce/pull/1293))
* Feature - Adds 3DSecure to Website Payments Pro 3.0 (DoDirectPayment). ([PFW-16](https://github.com/angelleye/paypal-woocommerce/pull/1308))
* Tweak - Adjusts the way order properties are accessed. ([PFWA-164](https://github.com/angelleye/paypal-woocommerce/pull/1300))
* Tweak - Adds feedback request when deactivating the plugin. ([PFWA-2](https://github.com/angelleye/paypal-woocommerce/pull/1291))
* Tweak - Updates plugin action links. ([PFWA-161](https://github.com/angelleye/paypal-woocommerce/pull/1295)) ([PFWA-18](https://github.com/angelleye/paypal-woocommerce/pull/1296))
* Tweak - Updates links in settings sidebar and plugin action links. ([PFWA-34](https://github.com/angelleye/paypal-woocommerce/pull/1302))
* Tweak - Adds more specific error messaging to Braintree Payments. ([PFW-395](https://github.com/angelleye/paypal-woocommerce/pull/1309))
* Fix - Resolves an issue with Braintree token payments. ([PFW-357](https://github.com/angelleye/paypal-woocommerce/pull/1280)) ([PFW-372](https://github.com/angelleye/paypal-woocommerce/pull/1284)) ([PFWA-24](https://github.com/angelleye/paypal-woocommerce/pull/1298))
* Fix - Adjusts credit card number validation logic to resolve rare occurrences of false-negatives. ([PFW-369](https://github.com/angelleye/paypal-woocommerce/pull/1283))
* Fix - Resolves a compatibility issue with Woo Subscriptions where payments were triggered when payment method was updated by user. ([PFWA-15](https://github.com/angelleye/paypal-woocommerce/pull/1287))
* Fix - Resolves an issue where the PayPal Smart Buttons would show up on the Woo checkout page during $0.00 free orders. ([PFWA-165](https://github.com/angelleye/paypal-woocommerce/pull/1301))
* Fix - Resolves a conflict with multi-currency sites and Woo Subscriptions payments. ([PFW-397](https://github.com/angelleye/paypal-woocommerce/pull/1310))

= 1.5.7 - 01.20.2019 =
* Fix - Adjustments to coincide with recent Woo changes and resolve PayPal button performance issues. ([PFW-356](https://github.com/angelleye/paypal-woocommerce/pull/1274))

= 1.5.6 - 01.19.2019 =
* Fix - Resolves a problem with Express Checkout button display on some themes. ([PFW-354](https://github.com/angelleye/paypal-woocommerce/pull/1273))

= 1.5.5 - 01.17.2019 =
* Feature - "Checkout WC" Compatibility with PayPal Express Checkout. ([PFW-305](https://github.com/angelleye/paypal-woocommerce/pull/1254))
* Feature - Adds Express Checkout Smart Button functionality to Woo checkout page. ([PFW-303](https://github.com/angelleye/paypal-woocommerce/pull/1258))
* Feature - Adds an opt-in for basic logging to help us improve the plugin. ([PFW-341](https://angelleye.atlassian.net/browse/PFW-341))
* Feature - Adds the ability to set Express Checkout Smart Button options specific to product pages, cart page, and checkout page. ([PFW-334](https://github.com/angelleye/paypal-woocommerce/pull/1261))
* Tweak - Adjusts the way Maestro cards are handled with regard to regular vs. token payments. ([PFW-329](https://github.com/angelleye/paypal-woocommerce/pull/1255))
* Tweak - Improved method for handling WooCommerce subtotal calculations mis-matches to avoid PayPal checkout errors. ([PFW-318](https://github.com/angelleye/paypal-woocommerce/pull/1257))
* Fix - Resolves an issue with Braintree Google Pay. ([PFW-330](https://github.com/angelleye/paypal-woocommerce/pull/1256))
* Fix - Resolves a bug with the PayFlow option to display expiration month using name instead of numbers. ([PFW-333](https://github.com/angelleye/paypal-woocommerce/pull/1260))
* Fix - Resolves an issue where a PayFlow display notice was appearing two times. ([PFW-332](https://github.com/angelleye/paypal-woocommerce/pull/1259))
* Fix - Resolves a missing PPREF value in PayFlow when PayPal is used as the processor. ([PFW-310](https://github.com/angelleye/paypal-woocommerce/pull/1265))
* Fix - Resolves an issue in Braintree where the card holder name was not always sent correctly in the request. ([PFW-342](https://github.com/angelleye/paypal-woocommerce/pull/1266))
* Fix - Resolves an issue in validation of credit card types in some gateways. ([PFW-341](https://github.com/angelleye/paypal-woocommerce/pull/1267))

= 1.5.4 - 12.08.2018 =
* Feature - Adds PayPal Fee to WooCommerce order meta data for Express Checkout orders. ([PFW-299](https://github.com/angelleye/paypal-woocommerce/pull/1243))
* Tweak - Adjusts logs with better details for Card Verification and Capture/Sale transactions. ([PFW-317](https://github.com/angelleye/paypal-woocommerce/pull/1245))
* Fix - Resolves an issue where some item attributes were not getting included in Express Checkout line items. ([PFW-215](https://github.com/angelleye/paypal-woocommerce/pull/1244))
* Fix - Resolves a PHP Notice related to data availability. ([PFW-304](https://github.com/angelleye/paypal-woocommerce/pull/1242))
* Fix - Resolves PHP notice related to Express Checkout option for ignoring terms. ([PFW-312](https://github.com/angelleye/paypal-woocommerce/pull/1247))
* Fix - Resolves a problem that sometimes keeps token payments from saving properly. ([PFW-316](https://github.com/angelleye/paypal-woocommerce/pull/1246))
* Fix - Resolves jQuery bug with processing spinner in Braintree. ([PFW-320](https://github.com/angelleye/paypal-woocommerce/pull/1248))
* Fix - Resolves an empty info box popping up sometimes with DoDirectPayment token payments. ([PFW-321](https://github.com/angelleye/paypal-woocommerce/pull/1250))
* Fix - Resolves an issue in PayPal Advanced where redirect would not work when using token payments in some cases. ([PFW-322](https://github.com/angelleye/paypal-woocommerce/pull/1249))
* Fix - Resolves an issue in DoDirectPayment where the "Send Line Item Details to PayPal" option was not being properly followed, and would send itemized details even if it was disabled. ([PFW-322](https://github.com/angelleye/paypal-woocommerce/pull/1251))
* Fix - Resolves an issue with REST DCC token payments with Maestro cards. ([PFW-324](https://github.com/angelleye/paypal-woocommerce/pull/1252))
* Fix - Resolves a problem in Braintree gateway where some error messages were not displayed properly during checkout. ([PFW-325](https://github.com/angelleye/paypal-woocommerce/pull/1253))

= 1.5.3 - 11.07.2018 =
* Fix - Resolves a problem with expiration date validation in credit card gateways. ([PFW-301](https://github.com/angelleye/paypal-woocommerce/pull/1241))

= 1.5.2 - 11.06.2018 =
* Feature - Special Request - Adds `ae_add_custom_order_note` hook to PayPal Pro, Braintree, and REST DCC gateways. ([PFW-300](https://github.com/angelleye/paypal-woocommerce/pull/1240))

= 1.5.1 - 11.05.2018 =
* Fix - PayFlow - Resolves an issue with the way Fraud Filter warnings were being handled. ([PFW-298](https://github.com/angelleye/paypal-woocommerce/pull/1238))

= 1.5.0 - 11.02.2018 =
* Feature - PayFlow - Adds an option to run Card Verification for Authorization instead of a full order authorization. ([PFW-272](https://github.com/angelleye/paypal-woocommerce/pull/1222))([PFW-292](https://github.com/angelleye/paypal-woocommerce/pull/1236))
* Feature - PayFlow - Adds product-level option for Authorization or Sale. ([PFW-273](https://github.com/angelleye/paypal-woocommerce/pull/1223))
* Feature - Braintree - Adds Apple and Google Pay to the Drop In UI experience. ([PFW-106](https://github.com/angelleye/paypal-woocommerce/pull/1233))
* Feature - PayFlow - Adds an option to run Card Verification for Authorization instead of a full order authorization. ([PFW-272](https://github.com/angelleye/paypal-woocommerce/pull/1222))
* Feature - PayFlow - Adds product-level option for Authorization or Sale. ([PFW-273](https://github.com/angelleye/paypal-woocommerce/pull/1223))([PFW-290](https://github.com/angelleye/paypal-woocommerce/pull/1231))
* Feature - PayFlow - Adds additional data for Address Verification to Woo order notes. ([PFW-276](https://github.com/angelleye/paypal-woocommerce/pull/1221))
* Tweak - REST CC - Adjustments to error display on failed payments. ([PFW-284](https://github.com/angelleye/paypal-woocommerce/pull/1225))
* Tweak - PayFlow - Formatting adjustment for auth/capture AVS notes. ([PFW-293](https://github.com/angelleye/paypal-woocommerce/pull/1230))
* Tweak - CSS tweaks to resolve mobile checkout form field issues. ([PWF-268](https://github.com/angelleye/paypal-woocommerce/pull/1227))
* Tweak - Adjusts tool-tip related to Authorization and Capture functionality. ([PFW-291](https://github.com/angelleye/paypal-woocommerce/pull/1232))
* Fix - Express Checkout - Resolves a conflict with some themes where PayPal Smart Buttons would get cut off. ([PFW-266](https://github.com/angelleye/paypal-woocommerce/pull/1228))
* Fix - Advanced - Resolves a failure sometimes happening related to wc_add_notice(). ([PFW-278](https://github.com/angelleye/paypal-woocommerce/pull/1235))
* Fix - Braintree - Resolves a problem where failed data validation would break the Drop In UI in some cases.  ([PFW-280](https://github.com/angelleye/paypal-woocommerce/pull/1219)[PFW-283](https://github.com/angelleye/paypal-woocommerce/pull/1224))
* Fix - PayFlow - Resolves an issue with handling of duplicate order ID errors.  ([PFW-281](https://github.com/angelleye/paypal-woocommerce/pull/1220))
* Fix - DoDirectPayment - Resolves an issue related to token payments. ([PFW-287](https://github.com/angelleye/paypal-woocommerce/pull/1229))

= 1.4.19 - 10.27.2018 =
* Tweak - Adds verification of compatibility with WooCommerce 3.5.0.

= 1.4.18 - 10.08.2018 =
* [PFW-249](https://github.com/angelleye/paypal-woocommerce/pull/1214) - Feature - Adds ability to set values for submit buttons on Checkout and Order Review pages.
* [PFW-262](https://github.com/angelleye/paypal-woocommerce/pull/1215) - Tweak - Adds PayPal Credit option to buyers/sellers in the UK (Previously only available in US).
* [PFW-267](https://github.com/angelleye/paypal-woocommerce/pull/1216) - Tweak - Updates languages files.

= 1.4.17 - 10.04.2018 =
* [PFW-256](https://github.com/angelleye/paypal-woocommerce/pull/1212) - Fix - Resolves a PHP error related to order emails.
* [PFW-264](https://github.com/angelleye/paypal-woocommerce/pull/1211) - Fix - Better error handling for scenarios where Woo Subscriptions is being used with Express Checkout, but the PayPal account does not have Billing Agreements enabled.

= 1.4.16 - 09.26.2018 =
* [PFW-252](https://github.com/angelleye/paypal-woocommerce/pull/1204) - Feature - Adds SOFTDESCRIPTOR to direct credit card processing requests.
* [PFW-31](https://github.com/angelleye/paypal-woocommerce/pull/1199)  - Tweak - Updates credit card icons for direct CC processing gateways to a more modern style.
* [PFW-234](https://github.com/angelleye/paypal-woocommerce/pull/1198) - Tweak - Adds required hooks for our PayPal for WooCommerce Multi-Account extension.
* [PFW-186](https://github.com/angelleye/paypal-woocommerce/pull/1206) - Fix - Resolves a problem where the detailed vs. generic error display option was not functioning as expected.

= 1.4.15 - 09.05.2018 =
* [PFW-104](https://github.com/angelleye/paypal-woocommerce/pull/1182) - Feature - Adds Authorization and Capture functionality to Braintree.
* [PFW-213](https://github.com/angelleye/paypal-woocommerce/pull/1188)[PFW-229](https://github.com/angelleye/paypal-woocommerce/pull/1201) - Feature - Upgrades Braintree Direct Credit Card form to use secure hosted payments fields.
* [PFW-155](https://github.com/angelleye/paypal-woocommerce/pull/1179) - Tweak - Adjustment so the email address used on WC checkout page gets carried through when Express Checkout is used.
* [PFW-170](https://github.com/angelleye/paypal-woocommerce/pull/1186) - Tweak - Adjustment to PayPal Advanced response handling.
* [PFW-191](https://github.com/angelleye/paypal-woocommerce/pull/1187) - Tweak - Adjusts CSS related to admin notice.
* [PFW-197](https://github.com/angelleye/paypal-woocommerce/pull/1178) - Tweak - Adjustment to Braintree token payments experience.
* [PFW-203](https://github.com/angelleye/paypal-woocommerce/pull/1184) - Tweak - Adjustment to refunds so it pulls current open amount instead of original order amount by default.
* [PFW-218](https://github.com/angelleye/paypal-woocommerce/pull/1189) - Tweak - Adjusts Amex logic in PayPal Pro so it will accept CAD currency when used in CA country.
* [PFW-188](https://github.com/angelleye/paypal-woocommerce/pull/1194) - Fix - Resolves a problem with Sale transactions failing when using token payments.
* [PFW-206](https://github.com/angelleye/paypal-woocommerce/pull/1183) - Fix - Resolves a problem with the Proceed to Checkout button localization/translation.
* [PFW-216](https://github.com/angelleye/paypal-woocommerce/pull/1181) - Fix - Adds localization to confirmation message that was missing it.
* [PFW-221](https://github.com/angelleye/paypal-woocommerce/pull/1191) - Fix - Resolves some PHP notices that get displayed when the plugin is activated with debug mode enabled.
* [PFW-222](https://github.com/angelleye/paypal-woocommerce/pull/1185) - Fix - Adds Invoice ID to DoCapture requests per new PayPal requirements.
* [PFW-223](https://github.com/angelleye/paypal-woocommerce/pull/1190)[PFW-231](https://github.com/angelleye/paypal-woocommerce/pull/1192) - Fix - Resolves a duplicate error message sometimes displayed on Braintree payment failures.
* [PFW-232](https://github.com/angelleye/paypal-woocommerce/pull/1193) - Fix - Resolves a problem where Braintree would not always work from the Woo checkout order page.
* [PFW-240](https://github.com/angelleye/paypal-woocommerce/pull/1197) - Fix - Resolves a conflict with our PayPal WP Button Manager plugin.
* [PFW-242](https://github.com/angelleye/paypal-woocommerce/pull/1203) - Fix - Resolves PHP failures when adding payment methods to Braintree token payment experience.

= 1.4.14 - 07.17.2018 =
* [PFW-167](https://github.com/angelleye/paypal-woocommerce/pull/1171) - Tweak - Adjustments to Braintree data validation.

= 1.4.13 - 07.17.2018 =
* [PFW-189](https://github.com/angelleye/paypal-woocommerce/pull/1169) - Feature - WooCommerce One Click Upsell Compatibility.
* [PFW-180](https://github.com/angelleye/paypal-woocommerce/pull/1172) - Tweak - Adjusts optional/required fields on Express Checkout review page.
* [PFW-190](https://github.com/angelleye/paypal-woocommerce/pull/1173) - Tweak - Resolves a CSS conflict in Braintree settings panel.
* [PFW-200](https://github.com/angelleye/paypal-woocommerce/pull/1174) - Tweak - Adjustments to JS related to Braintree data validation.

= 1.4.12 - 07.16.2018 =
* Feature - Adds ability to hide individual credit card types from Express Checkout Smart Payment Buttons. ([PFW-179](https://github.com/angelleye/paypal-woocommerce/pull/1159))
* Feature - Adds option to override the Proceed to Checkout button text on the Woo cart page. ([PFW-183](https://github.com/angelleye/paypal-woocommerce/pull/1168)) ([PFW-193](https://github.com/angelleye/paypal-woocommerce/pull/1167))
* Tweak - Adjustments to JS around Express Checkout Smart Buttons to improve performance. ([PFW-135](https://github.com/angelleye/paypal-woocommerce/pull/1157)) ([PFW-178](https://github.com/angelleye/paypal-woocommerce/pull/1162))
* Tweak - Removes unnecessary admin notices to avoid clutter. ([PFW-164](https://github.com/angelleye/paypal-woocommerce/pull/1156)) ([PFW-174](https://github.com/angelleye/paypal-woocommerce/pull/1160)) ([PFW-175](https://github.com/angelleye/paypal-woocommerce/pull/1161))
* Fix - Resolves some PHP notices. ([PFW-136](https://github.com/angelleye/paypal-woocommerce/pull/1152))
* Fix - Resolves an issue where Smart Buttons would not display on Checkout page when Page Setup options were changed from default. ([PFW-147](https://github.com/angelleye/paypal-woocommerce/pull/1151))
* Fix - Resolves some PHP notices. ([PFW-135](https://github.com/angelleye/paypal-woocommerce/pull/1152))
* Fix - Adjusts the check for SSL to coincide with changes made in recent updates to WooCommerce. ([PFW-168](https://github.com/angelleye/paypal-woocommerce/pull/1155))
* Fix - Resolves an issue with error handling related to Woo Subscription payment failures. ([PFW-171](https://github.com/angelleye/paypal-woocommerce/pull/1158))
* Fix - Resolves a problem with order amount not displaying properly in Express Checkout screens. ([PFW-176](https://github.com/angelleye/paypal-woocommerce/pull/1165))
* Fix - Resolves incorrect handling of "skip final review" when Smart Buttons are used in Express Checkout. ([PFW-178](https://github.com/angelleye/paypal-woocommerce/pull/1162))

= 1.4.11 - 06.21.2018 =
* Tweak - Further data sanitization and validation for privacy and security. ([PFW-156](https://github.com/angelleye/paypal-woocommerce/pull/1149))

= 1.4.10 - 06.20.2018 =
* Feature - Adds PayPal Payment Type and Transaction Fee to order meta data for Express Checkout orders. ([PFW-74](https://github.com/angelleye/paypal-woocommerce/pull/1146))
* Feature - Adds ability to specify one or more email addresses to use for PayPal API error notifications. ([PFW-19](https://github.com/angelleye/paypal-woocommerce/pull/1138))
* Tweak - Hides admin notices from non-admin user roles. ([PFW-7](https://github.com/angelleye/paypal-woocommerce/pull/1136))
* Tweak - Adds billing phone number to PayFlow requests. ([PFW-2](https://github.com/angelleye/paypal-woocommerce/pull/1145/))
* Tweak - Adds an admin notice to inform users when the PHP version on the server does not support plugin functionality. ([PFW-40](https://github.com/angelleye/paypal-woocommerce/pull/1135))
* Tweak - Adds environment label (sandbox or production) to API logs. ([PFW-48](https://github.com/angelleye/paypal-woocommerce/pull/1140))
* Tweak - Adds email address to Express Checkout requests when available. ([PFW-55](https://github.com/angelleye/paypal-woocommerce/pull/1133))
* Tweak - Organizes Express Checkout settings panel. ([PFW-33](https://github.com/angelleye/paypal-woocommerce/pull/1144))
* Tweak - Adds data sanitize functions where necessary. ([PFW-151](https://github.com/angelleye/paypal-woocommerce/pull/1148))
* Tweak - Adjustments to avoid errors when item calculations from Woo are not accurate. ([PFW-46](https://github.com/angelleye/paypal-woocommerce/pull/1147))
* Fix - Resolves issues with some account creation options during checkout with Express Checkout. ([PFW-21](https://github.com/angelleye/paypal-woocommerce/pull/1137))
* Fix - Resolves an issue where the shipping address was sometimes not updated in the WooCommerce order. ([PFW-57](https://github.com/angelleye/paypal-woocommerce/pull/1139))
* Fix - Resolves a problem in some mobile browsers where the last name field was getting hidden during checkout. ([PFW-126](https://github.com/angelleye/paypal-woocommerce/pull/1141))

= 1.4.9 - 05.23.2018 =
* Compatibility - Check for compatibility with WooCommerce 3.4. ([#1126](https://github.com/angelleye/paypal-woocommerce/issues/1126))
* Feature - Upgrades Braintree SDK from v2 to v3. ([#1101](https://github.com/angelleye/paypal-woocommerce/issues/1101)) ([#1122](https://github.com/angelleye/paypal-woocommerce/issues/1122))
* Feature - Adds setting to include a custom message below the page header on the Express Checkout review page. ([#1119](https://github.com/angelleye/paypal-woocommerce/issues/1119))
* Feature - Adds default sandbox credentials for PayFlow so you don't have to setup your own test account for sandbox testing. ([#962](https://github.com/angelleye/paypal-woocommerce/issues/962))
* Tweak - Makes use of the option to send locale code from WordPress to PayPal with Smart Buttons to update button language accordingly. ([#1105](https://github.com/angelleye/paypal-woocommerce/issues/1105))
* Tweak - Adds WooCommerce and PayPal for WooCommerce version tags to PayFlow log files. ([#979](https://github.com/angelleye/paypal-woocommerce/issues/979))
* Tweak - Adds PayPal for WooCommerce version to HTML comments so we can view source to see the current plugin version installed. ([#980](https://github.com/angelleye/paypal-woocommerce/issues/980))
* Tweak - Adjusts REST CC log files. ([#1125](https://github.com/angelleye/paypal-woocommerce/issues/1125))
* Cleanup - Adjusts the formatting of admin error email notification. ([#862](https://github.com/angelleye/paypal-woocommerce/issues/862))
* Cleanup - Adjusts IPN options in Express Checkout settings so they are grouped together. ([#978](https://github.com/angelleye/paypal-woocommerce/issues/978))
* Cleanup - Adjusts incorrect text-domain in some code. ([#1107](https://github.com/angelleye/paypal-woocommerce/issues/1107))
* Cleanup - Adjusts element IDs in some code to ensure they are accurate and unique. ([#1113](https://github.com/angelleye/paypal-woocommerce/issues/1113))
* Cleanup - Adjusts the handling of the state code returned by PayPal within WooCommerce. ([#1066](https://github.com/angelleye/paypal-woocommerce/pull/1066))

= 1.4.8.9 - 04.26.2018 =
* Fix - Resolves an issue with Smart Button jQuery conflict. ([#1106](https://github.com/angelleye/paypal-woocommerce/issues/1106)) ([#1110](https://github.com/angelleye/paypal-woocommerce/issues/1110)) ([#1112](https://github.com/angelleye/paypal-woocommerce/issues/1112))

= 1.4.8.8 - 04.23.2018 =
* Feature - Ensures WooCommerce 3.4 compatibility. ([#1098](https://github.com/angelleye/paypal-woocommerce/issues/1098))
* Tweak - Adds priority to Express Checkout hooks to ensure it always redirects as expected. ([#1086](https://github.com/angelleye/paypal-woocommerce/issues/1086))
* Tweak - Disables autocomplete on API credential fields to keep browser auto-fillers from populating fields with incorrect data. ([#1088](https://github.com/angelleye/paypal-woocommerce/issues/1088))
* Tweak - Adds validation to ensure the AMT is always a numeric value. ([#1090](https://github.com/angelleye/paypal-woocommerce/issues/1090))
* Tweak - Adjustments to CSS for PayPal buttons on the cart page to avoid conflicts with some themes. ([#1096](https://github.com/angelleye/paypal-woocommerce/issues/1096))
* Tweak - Sets PayPal Credit for US only. ([#1099](https://github.com/angelleye/paypal-woocommerce/issues/1099))
* Fix - Resolves jQuery conflict in admin panel. [#1092](https://github.com/angelleye/paypal-woocommerce/issues/1092))
* Fix - Resolves a bug in the Braintree Kount Custom fraud tools. [(#1020](https://github.com/angelleye/paypal-woocommerce/issues/1020))
* Fix - Resolves a bug in Express Checkout when specific countries are set in WooCommerce. [(#1100](https://github.com/angelleye/paypal-woocommerce/issues/1100))

= 1.4.8.7 - 04.03.2018 =
* Tweak - Performance adjustments for PayPal Smart Buttons. ([#1080](https://github.com/angelleye/paypal-woocommerce/issues/1080))
* Tweak - Adjusts the CSS for PayPal Smart Buttons in the WooCommerce mini-cart. ([#1072](https://github.com/angelleye/paypal-woocommerce/issues/1072))
* Fix - Resolves a PHP error happening when some types of variable products are in the cart. ([#1069](https://github.com/angelleye/paypal-woocommerce/issues/1069))([#1071](https://github.com/angelleye/paypal-woocommerce/issues/1071))([#1078](https://github.com/angelleye/paypal-woocommerce/issues/1078))
* Fix - Resolves a conflict with UPS and USPS plugins on the Express Checkout review page. ([#1079](https://github.com/angelleye/paypal-woocommerce/issues/1079))

= 1.4.8.6 - 03.29.2018 =
* Tweak - Adjustments to Smart Buttons in mini-cart. ([#1042](https://github.com/angelleye/paypal-woocommerce/issues/1042))
* Fix - Resolves a PHP syntax error on old versions of PHP. ([#1064](https://github.com/angelleye/paypal-woocommerce/issues/1064))

= 1.4.8.5 - 03.28.2018 =
* Feature - Adds PayPal Seller Protection Status to WooCommerce order notes. ([#1053](https://github.com/angelleye/paypal-woocommerce/issues/1053))
* Feature - Adds -OR- between the Proceed to Checkout button and PayPal buttons on cart page, with filter to adjust. ([#1061](https://github.com/angelleye/paypal-woocommerce/issues/1061))
* Tweak - Adjusts error handling with Express Checkout token is not available. ([#1047](https://github.com/angelleye/paypal-woocommerce/issues/1047))
* Tweak - Hides PayPal Smart Payment Buttons on external products. ([#1054](https://github.com/angelleye/paypal-woocommerce/issues/1054))
* Tweak - Update PayPal Smart Payment Button JS to use minified version. ([#1056](https://github.com/angelleye/paypal-woocommerce/issues/1056))
* Tweak - Adjusts PayPal Smart Payment Button preview in the settings panel. ([#1063](https://github.com/angelleye/paypal-woocommerce/issues/1063))
* Fix - Resolves a duplicate note added to WooCommerce order when an order is refunded. ([#1057](https://github.com/angelleye/paypal-woocommerce/issues/1057))
* Fix - Resolves an issue where PayPal buttons fail from the product page if the item is already in the cart. ([#1036](https://github.com/angelleye/paypal-woocommerce/issues/1036))
* Fix - Resolves an issue where Marketing Solutions was automatically becoming enabled. ([#1041](https://github.com/angelleye/paypal-woocommerce/issues/1041))
* Fix - Resolves duplicate form IDs. ([#1037](https://github.com/angelleye/paypal-woocommerce/issues/1037))

= 1.4.8.4 - 03.27.2018 =
* Tweak - Adjusts some Smart Button options under settings to avoid confusion. ([#1032](https://github.com/angelleye/paypal-woocommerce/issues/1032))
* Tweak - Adjusts progress spinner provided by WooCommerce when used with Smart Buttons / In Context checkout flow. ([#1048](https://github.com/angelleye/paypal-woocommerce/issues/1048))
* Tweak - Version tracking on CSS / jQuery to help avoid caching conflicts. ([#1051](https://github.com/angelleye/paypal-woocommerce/issues/1051))
* Fix - More adjustments to jQuery with Smart Buttons. ([#1044](https://github.com/angelleye/paypal-woocommerce/issues/1044)) ([#1049](https://github.com/angelleye/paypal-woocommerce/issues/1049))
* Fix - Resolves an issue with Smart Buttons on the Woo mini-cart. ([#1042](https://github.com/angelleye/paypal-woocommerce/issues/1042))

= 1.4.8.3 - 03.25.2018 =
* Fix - Resolves a jQuery conflict with Smart Buttons when shipping methods are switched on the cart page. ([#1040](https://github.com/angelleye/paypal-woocommerce/issues/1040))

= 1.4.8.2 - 03.24.2018 =
* Fix - Resolves a redirect loop on the cart page caused by a jQuery conflict with Smart Buttons and some 3rd party plugins. ([#1038](https://github.com/angelleye/paypal-woocommerce/issues/1038))

= 1.4.8.1 - 03.23.2018 =
* Feature - Adds Google Analytics click tracking option and performance adjustments to PayPal Smart Payment Buttons. ([#1033](https://github.com/angelleye/paypal-woocommerce/issues/1033))
* Tweak - Updates paypal_transaction post type arguments for better performance. ([#1031](https://github.com/angelleye/paypal-woocommerce/pull/1031))
* Tweak - Adjustments to Woo Subscriptions token payments with Express Checkout to avoid sign-ups when reference transactions are not enabled. ([#1034](https://github.com/angelleye/paypal-woocommerce/issues/1034))

= 1.4.8 - 03.22.2018 =
* Feature - Adds the new Smart Payment Buttons to Express Checkout. ([#997](https://github.com/angelleye/paypal-woocommerce/issues/997)) ([#1026](https://github.com/angelleye/paypal-woocommerce/issues/1026)) ([#1010](https://github.com/angelleye/paypal-woocommerce/issues/1010))
* Tweak - Adjusts CSS on checkout page to avoid conflicts. ([#1013](https://github.com/angelleye/paypal-woocommerce/issues/1013))
* Tweak - Adjusts the way the addresses are sent to PayPal when the WooCommerce checkout page is used. ([#1014](https://github.com/angelleye/paypal-woocommerce/issues/1014))
* Tweak - Performance adjustments in Express Checkout settings panel.  ([#1027](https://github.com/angelleye/paypal-woocommerce/issues/1027))
* Fix - Resolves an issue with token payments in Paypal REST DCC. ([#1011](https://github.com/angelleye/paypal-woocommerce/issues/1011))
* Fix - Resolves an issue with order notes being saved when API calls fails. ([#1022](https://github.com/angelleye/paypal-woocommerce/pull/1022))

= 1.4.7.4 - 02.10.2018 =
* Fix - Resolves a problem with PayFlow credit card button not displaying based on checkout settings. ([#996](https://github.com/angelleye/paypal-woocommerce/issues/996))

= 1.4.7.3 - 02.09.2018 =
* Tweak - Adjustments to Braintree Kount Custom functionality. ([#989](https://github.com/angelleye/paypal-woocommerce/issues/989)) ([#990](https://github.com/angelleye/paypal-woocommerce/issues/990))
* Tweak - Adjustments to API calls with PayPal and WooCommerce to improve overall performance and load times. ([#991](https://github.com/angelleye/paypal-woocommerce/issues/991))
* Tweak - Adjusts order notes for PayFlow transactions to specify "PayFlow" instead of just "PayPal Pro". ([#994](https://github.com/angelleye/paypal-woocommerce/issues/994))
* Fix - Resolves a problem with commas being used as decimal in some payment requests. ([#985](https://github.com/angelleye/paypal-woocommerce/issues/985))
* Fix - Resolves rounding issues that can occur in PayFlow gateway. ([#987](https://github.com/angelleye/paypal-woocommerce/issues/987))
* Fix - Resolves an issue with "missing token" in some PayPal Pro requests. ([#993](https://github.com/angelleye/paypal-woocommerce/issues/993))

= 1.4.7.2 - 01.16.2018 =
* Tweak - Removes unused / commented code that was causing a false flag with the Sucuri service. ([#981](https://github.com/angelleye/paypal-woocommerce/issues/981))

= 1.4.7.1 - 01.02.2018 =
* Tweak - Adjusts hook name. ([#975](https://github.com/angelleye/paypal-woocommerce/issues/975))
* Fix - Resolves an issue where WP user accounts were not created properly in some scenarios. ([#974](https://github.com/angelleye/paypal-woocommerce/issues/974))
* Fix - Resolves an issue with PayPal Advanced where timeouts could occur with greater than 10 items in the shopping cart. ([#976](https://github.com/angelleye/paypal-woocommerce/issues/976))

= 1.4.7 - 12.21.2017 =
* Feature - WooCommerce 3.3 compatibility based on beta release. ([#961](https://github.com/angelleye/paypal-woocommerce/issues/961))
* Feature - Adds an option for what order status should be set when an order is Authorized but has not yet been captured. ([#883](https://github.com/angelleye/paypal-woocommerce/issues/883))
* Feature - Adds Authorization functionality to PayPal REST credit cards. ([#813](https://github.com/angelleye/paypal-woocommerce/issues/813))
* Feature - Adds an option for PayPal Pro 2.0 (PayFlow) to set the default WooCommerce order status for orders where payment is completed. ([#965](https://github.com/angelleye/paypal-woocommerce/issues/965))
* Feature - Adds filter hooks for credit card field labels. ([#898](https://github.com/angelleye/paypal-woocommerce/issues/898))
* Tweak - Adds a video about Marketing Solutions under the More Info button in that section of Express Checkout settings. ([#964](https://github.com/angelleye/paypal-woocommerce/issues/964))
* Tweak - Ensures that Express Checkout orders with no shipping required include the billing name/email in order details. ([#831](https://github.com/angelleye/paypal-woocommerce/issues/831))
* Tweak - Passes the shipping address to PayPal when u sing REST direct credit cards. ([#775](https://github.com/angelleye/paypal-woocommerce/issues/775))
* Tweak - Adjusts PayPal Credit button alignment. ([#766](https://github.com/angelleye/paypal-woocommerce/issues/766))
* Tweak - Adjustments to allow / disallow gateways based on the PHP version used on the site. ([#640](https://github.com/angelleye/paypal-woocommerce/issues/640))
* Tweak - Adjustments to the way reference transaction options are displayed on the WC order page based on screen options that are enabled. ([#867](https://github.com/angelleye/paypal-woocommerce/issues/867))
* Tweak - Adjustments to default .pot language file. ([#877](https://github.com/angelleye/paypal-woocommerce/issues/877))
* Tweak - Improvements for Google Analytics / Facebook Pixel tracking with Express Checkout. ([#929](https://github.com/angelleye/paypal-woocommerce/issues/929))
* Tweak - Rearranges PayFlow credentials fields in settings to match the order displayed at manager.paypal.com. ([#940](https://github.com/angelleye/paypal-woocommerce/issues/940))
* Tweak - Adjustments for PHP 7.2 compatibility. ([#942](https://github.com/angelleye/paypal-woocommerce/issues/942))
* Tweak - Adds a notice in PayFlow about enabling reference transaction if you are using Woo token payments and/or Woo Subscriptions. ([#950](https://github.com/angelleye/paypal-woocommerce/issues/950))
* Tweak - Adjusts alignment of the option to save payment method to account (token payment). ([#969](https://github.com/angelleye/paypal-woocommerce/issues/969))
* Fix - Resolves an issue with PayPal Advanced where orders could fail when additional fees are added via other plugins. ([#767](https://github.com/angelleye/paypal-woocommerce/issues/767))
* Fix - Resolves an issue with the Terms and Conditions acceptance from the Express Checkout order review page. ([#916](https://github.com/angelleye/paypal-woocommerce/issues/916))
* Fix - Resolves an issue where the Express Checkout button would sometimes get hidden if a coupon code was entered which makes the order free. ([#924](https://github.com/angelleye/paypal-woocommerce/issues/924))
* Fix - Resolves a layout conflict with Express Checkout Marketing Solutions and Woo Pay for Payments. ([#935](https://github.com/angelleye/paypal-woocommerce/issues/935))
* Fix - Resolves an issue with Express Checkout In context based on button type and custom text settings. ([#938](https://github.com/angelleye/paypal-woocommerce/issues/938))
* Fix - Resolves PHP errors that sometimes occur with PayPal REST credit card settings. ([#943](https://github.com/angelleye/paypal-woocommerce/issues/943))
* Fix - Resolves PHP errors that occur if the order does not contain WC product data (ie. custom data store). ([#945](https://github.com/angelleye/paypal-woocommerce/issues/945))
* Fix - Better error handling if PayPal Marketing Solutions API call results in an empty response. ([#946](https://github.com/angelleye/paypal-woocommerce/issues/946))
* Fix - Resolves incorrect labels on AVS/CVV2 details provided in email notifications. ([#952](https://github.com/angelleye/paypal-woocommerce/issues/952))
* Fix - Resolves an issue where sometimes the Express Checkout In Context functionality was not working from the WC mini cart. ([#953](https://github.com/angelleye/paypal-woocommerce/issues/953))
* Fix - Resolves an issue where a duplicate refund call would be triggered if an order page that was already refunded was refreshed. ([#955](https://github.com/angelleye/paypal-woocommerce/issues/955))
* Fix - Resolves an issue with refunds related to our Multi-Account premium extension plugin. ([#956](https://github.com/angelleye/paypal-woocommerce/issues/956))
* Fix - Adjustments to ensure IPv4 is always used instead of IPv6 in API requests. ([#957](https://github.com/angelleye/paypal-woocommerce/issues/957))
* Fix - Resolves compatibility issue with WPML translation. ([#959](https://github.com/angelleye/paypal-woocommerce/issues/959))
* Fix - Resolves an issue where the PayPal Credit button was getting displayed on product pages even if no price was set for the product. ([#966](https://github.com/angelleye/paypal-woocommerce/issues/966))
* Fix - Resolves design compatibility issue with AccessPress theme. ([#900](https://github.com/angelleye/paypal-woocommerce/issues/900))

= 1.4.6.8 - 11.29.2017 =
* Tweak - Adjustments to PayPal Marketing Solutions activation request. ([#948](https://github.com/angelleye/paypal-woocommerce/issues/948))

= 1.4.6.7 - 11.15.2017 =
* Fix - Resolves PHP warnings (only displayed when error reporting is enabled).  ([#933](https://github.com/angelleye/paypal-woocommerce/issues/933))

= 1.4.6.6 - 11.15.2017 =
* Feature - Adds PayPal Marketing Solutions (Insights & Promotions) to Express Checkout.  Activate to increase avg. order amount and conversion rates! ([#904](https://github.com/angelleye/paypal-woocommerce/issues/904))
* Feature - Adds an option for whether or not to display the Express Checkout button in the Woo Minicart. ([#920](https://github.com/angelleye/paypal-woocommerce/issues/920))
* Tweak - Adjustments to PayPal Pro / PayFlow checkout styles for better display on mobile devices. ([#912](https://github.com/angelleye/paypal-woocommerce/issues/912))
* Tweak - Adjustments to cart total calculations class. ([#908](https://github.com/angelleye/paypal-woocommerce/issues/908))
* Tweak - WooCommerce 3.2.2 compatibility check. ([#919](https://github.com/angelleye/paypal-woocommerce/issues/919))
* Tweak - Adjustments to the way WC orders are created when Express Checkout is used from the WC checkout page. ([#903](https://github.com/angelleye/paypal-woocommerce/issues/903))
* Tweak - Adjustments to Settings -> PayPal for WooCommerce screen. ([#901](https://github.com/angelleye/paypal-woocommerce/issues/901))
* Fix - Resolves browser console errors displayed when Express Checkout is disabled on product pages. ([#905](https://github.com/angelleye/paypal-woocommerce/issues/905))
* Fix - Resolves an issue causing Express Checkout to fail from saved/pending orders that are sent directly to the buyer for payment. ([#914](https://github.com/angelleye/paypal-woocommerce/issues/914))
* Fix - Resolves an issue where IPv6 addresses are sometimes used in the PayPal request, which PayPal does not support.  Now we make sure IPv4 is used. ([#909](https://github.com/angelleye/paypal-woocommerce/issues/909))
* Fix - Resolves an issue where orders with both items that need shipped and items with "no shipping required" are being treated as no shipping required. ([#928](https://github.com/angelleye/paypal-woocommerce/issues/928))
* Fix - Resolves a broken admin notice link. ([#930](https://github.com/angelleye/paypal-woocommerce/issues/930))

= 1.4.6.5 - 10.12.2017 =
* Tweak - Clean up settings panel. ([#884](https://github.com/angelleye/paypal-woocommerce/issues/884))
* Tweak - Adjustments related to JavaScript errors/logs. ([#886](https://github.com/angelleye/paypal-woocommerce/issues/886))
* Fix - Resolves an issue related to discounts with Woo Subscription products. ([#888](https://github.com/angelleye/paypal-woocommerce/issues/888))
* Fix - Resolves a conflict with WooCommerce 3.2 release. ([#899](https://github.com/angelleye/paypal-woocommerce/issues/899))

= 1.4.6.4 - 09.27.2017 =
* Feature - Adds hooks necessary for multi-account setup extension plugin we are building. ([#876](https://github.com/angelleye/paypal-woocommerce/issues/876))
* Tweak - Adjustments to ensure PayPal Express Checkout image on WC checkout page uses https:// when site is running on SSL. ([#878](https://github.com/angelleye/paypal-woocommerce/issues/878))
* Tweak - Resolves conflicts with some themes using Express Checkout In Context. ([#882](https://github.com/angelleye/paypal-woocommerce/issues/882))
* Fix - Resolves a PHP failure when loading a failed Braintree order in WooCommerce. ([#879](https://github.com/angelleye/paypal-woocommerce/issues/879))
* Fix - Resolves a conflict with WPML compatibility in the WC thank you / order complete page. ([#875](https://github.com/angelleye/paypal-woocommerce/issues/875))
* Fix - Resolves an issue where the WC auto-generated password for new accounts was not being sent in the email notification. ([#881](https://github.com/angelleye/paypal-woocommerce/issues/881))

= 1.4.6.3 - 09.15.2017 =
* Feature - Adds WooCommerce Pay for Payment plugin compatibility. ([#849](https://github.com/angelleye/paypal-woocommerce/issues/849))
* Feature - Adds Kount Custom functionality to Braintree payments. ([#844](https://github.com/angelleye/paypal-woocommerce/issues/844))
* Feature - WooCommerce 3.2 compatibility. ([#853](https://github.com/angelleye/paypal-woocommerce/issues/853))
* Feature - Woo Smart Coupons Compatibility. ([#863](https://github.com/angelleye/paypal-woocommerce/issues/863))
* Tweak - Adjusts the way variation data is passed in the PayPal request. ([#734](https://github.com/angelleye/paypal-woocommerce/issues/734))
* Tweak - Adjustments to PayPal Credit button output. ([#846](https://github.com/angelleye/paypal-woocommerce/issues/846))
* Tweak - Adds INR currency code to compatible currencies in Express Checkout. ([#847](https://github.com/angelleye/paypal-woocommerce/issues/847))
* Tweak - Referrer adjustment so Google Analytics will not show PayPal as the referrer on sales. ([#605](https://github.com/angelleye/paypal-woocommerce/issues/605))
* Tweak - Adds local images for PayPal Express buttons instead of using PayPal hosted buttons because they often load slowly. ([#818](https://github.com/angelleye/paypal-woocommerce/issues/818))
* Tweak - Adds product level sandbox/testing option to the bulk updater tool. ([#845](https://github.com/angelleye/paypal-woocommerce/issues/845))
* Tweak - Adjusts Express Checkout review page based on WooCommerce settings for automatically generating user account and password. ([#848](https://github.com/angelleye/paypal-woocommerce/issues/848))
* Tweak - Adds ability to enter multiple merchant IDs for Braintree based on currency codes. ([#803](https://github.com/angelleye/paypal-woocommerce/issues/803))
* Tweak - Improvements to shipping method handling in Express Checkout. ([#677](https://github.com/angelleye/paypal-woocommerce/issues/677))
* Tweak - Adjusts reference transaction meta box to avoid potential conflicts with other meta boxes. ([#870](https://github.com/angelleye/paypal-woocommerce/issues/870))
* Tweak - Adds refund transaction ID as a custom field in the WooCommerce order when refunds are processed. ([#405](https://github.com/angelleye/paypal-woocommerce/issues/405))
* Tweak - Regenerate default language files for translation. ([#873](https://github.com/angelleye/paypal-woocommerce/issues/873))
* Tweak - Tighter integration between Express Checkout and WooCommerce review page. ([#793](https://github.com/angelleye/paypal-woocommerce/issues/793)) ([#763](https://github.com/angelleye/paypal-woocommerce/issues/763)) ([#871](https://github.com/angelleye/paypal-woocommerce/issues/871))
* Fix - Resolves a payment status problem with Braintree drop-in UI payments. ([#804](https://github.com/angelleye/paypal-woocommerce/issues/804))
* Fix - Resolves an issue with tax calculations pertaining to orders that include gift cards. ([#811](https://github.com/angelleye/paypal-woocommerce/issues/811))
* Fix - Resolves an issue where Braintree refunds were not updating the WooCommerce order status properly.  ([#780](https://github.com/angelleye/paypal-woocommerce/issues/780))
* Fix - Resolves an issue with Express Checkout In Context where PayPal buttons would sometimes disappear if the IC window was closed. ([#850](https://github.com/angelleye/paypal-woocommerce/issues/850))
* Fix - Resolves typos in WC order notes. ([#866](https://github.com/angelleye/paypal-woocommerce/issues/866))
* Fix - Resolves an issue in PayFlow where Fraud Filter flags sometimes cause duplicate order failures on re-attempts. ([#861](https://github.com/angelleye/paypal-woocommerce/issues/861))
* Fix - Resolves an issue with PayFlow where "shipping only" orders caused a failure. ([#872](https://github.com/angelleye/paypal-woocommerce/issues/872))

= 1.4.6.2 - 08.21.2017 =
* Feature - Adds filter hook to Payments Pro PayFlow which allows you to override the API endpoint when using services like NoFraud. ([#843](https://github.com/angelleye/paypal-woocommerce/issues/843))
* Tweak - More adjustments to shipping validation errors in Express Checkout review. ([#816](https://github.com/angelleye/paypal-woocommerce/issues/816))
* Fix - Resolves an issue with the order edit screen appearing blank in older versions of WooCommerce. ([#833](https://github.com/angelleye/paypal-woocommerce/issues/833))
* Fix - Resolves a problem with inventory updates when working with Authorization/Capture orders. ([#834](https://github.com/angelleye/paypal-woocommerce/issues/834))
* Fix - Resolves compatibility issue with various versions of Woo Subscriptions. ([#823](https://github.com/angelleye/paypal-woocommerce/issues/823))
* Fix - Resolves some issues with Woo Germanized compatibility. ([#757](https://github.com/angelleye/paypal-woocommerce/issues/757))

= 1.4.6.1 - 08.11.2017 =
* Fix - Further adjustments to Skip Final Review bug. ([#830](https://github.com/angelleye/paypal-woocommerce/issues/830))

= 1.4.6 - 08.11.2017 =
* Feature - Adds Kount Fraud Management to Braintree integration. ([#751](https://github.com/angelleye/paypal-woocommerce/issues/751))
* Feature - Adds option to enable the In-Context experience for Express Checkout Shortcut. ([#199](https://github.com/angelleye/paypal-woocommerce/issues/199))
* Feature - Adds ability to create reference transaction orders from the WooCommerce order edit screen. ([#761](https://github.com/angelleye/paypal-woocommerce/issues/761))
* Feature - Improves the way order authorizations and captures are handled within WooCommerce. ([#761](https://github.com/angelleye/paypal-woocommerce/issues/761)) ([#820](https://github.com/angelleye/paypal-woocommerce/issues/820)) ([#824](https://github.com/angelleye/paypal-woocommerce/issues/824))
* Tweak - Order status update adjustment. ([#805](https://github.com/angelleye/paypal-woocommerce/issues/805))
* Fix - Resolves a bug in the "Skip Final Review" option within Express Checkout when payment takes place through WooCommerce checkout page. ([#822](https://github.com/angelleye/paypal-woocommerce/issues/822))

= 1.4.5.1 - 07.07.2017 =
* Tweak - Adds shipping company name from WooCommerce checkout page to PayPal shipping address (name). ([#792](https://github.com/angelleye/paypal-woocommerce/issues/792))
* Tweak - Adds custom class for calculating totals in all gateways to avoid conflicts with WC and PayPal calculations. ([#767](https://github.com/angelleye/paypal-woocommerce/issues/767))
* Tweak - Ensures a Billing Agreement is always included with Express Checkout for Woo Subscription products. ([#789](https://github.com/angelleye/paypal-woocommerce/issues/789))
* Tweak - Adds an admin notice any time your settings would require a PayPal Express Checkout Billing agreement. ([#788](https://github.com/angelleye/paypal-woocommerce/issues/788))
* Tweak - Ignores option to "Set billing to the same as shipping" when the WooCommerce checkout page is used with Express Checkout. ([#796](https://github.com/angelleye/paypal-woocommerce/issues/796))
* Fix - Resolves a PHP fatal error. ([#762](https://github.com/angelleye/paypal-woocommerce/issues/762))
* Fix - More adjustments to Express Checkout shipping address. ([#765](https://github.com/angelleye/paypal-woocommerce/issues/765))
* Fix - Resolves some PHP warnings / failures. ([#781](https://github.com/angelleye/paypal-woocommerce/issues/781))
* Fix - Resolves a conflict between our "PayPal for WooCommerce" and "PayPal Plus for WooCommerce" review screens. ([#779](https://github.com/angelleye/paypal-woocommerce/issues/779))
* Fix - Resolves conflict with Woo Germanized plugin. ([#764](https://github.com/angelleye/paypal-woocommerce/issues/764))

= 1.4.5 - 06.06.2017 =
* Tweak - Adds a note to the settings panel about the new product-level option for enabling Express Checkout. ([#729](https://github.com/angelleye/paypal-woocommerce/issues/729))
* Tweak - Adds PayPal Credit button to product details pages when Express Checkout and Credit are enabled (previously only showed up on cart and checkout pages.) ([#741](https://github.com/angelleye/paypal-woocommerce/issues/741))
* Tweak - Adjusts CSS for Cancel Order button on Express Checkout review page. ([#748](https://github.com/angelleye/paypal-woocommerce/issues/748))
* Tweak - Removes our button to delete log files because WooCommerce now has its own button for that. ([#750](https://github.com/angelleye/paypal-woocommerce/issues/750))
* Tweak - Adjusts Authorization / Capture in Express Checkout. ([#730](https://github.com/angelleye/paypal-woocommerce/issues/730))
* Tweak - Cleans some dirty data values in some PayPal responses. ([#756](https://github.com/angelleye/paypal-woocommerce/issues/756))
* Tweak - Adjusts the Express Checkout review page to avoid confusion with addresses. ([#742](https://github.com/angelleye/paypal-woocommerce/issues/742))
* Tweak - WC 3.0 compatibility adjustments. ([#760](https://github.com/angelleye/paypal-woocommerce/issues/760))
* Fix - Resolves an issue where the shipping address from PayPal would sometimes not get saved in the WC order depending on specific settings. ([#https://github.com/angelleye/paypal-woocommerce/issues/722))
* Fix - Resolves an issue caused by an extra / in a stylesheet. ([#717](https://github.com/angelleye/paypal-woocommerce/issues/717))
* Fix - Resolves an issue with Braintree Credit Card Statement Name option. ([#724](https://github.com/angelleye/paypal-woocommerce/issues/724))
* Fix - Resolves an issue that was causing Express Checkout to fail when used through a pending / saved order in WooCommerce. ([#728](https://github.com/angelleye/paypal-woocommerce/issues/728))
* Fix - Resolves an issue keeping form validation on the WC checkout page from triggering when Express Checkout is used. ([#735](https://github.com/angelleye/paypal-woocommerce/issues/735))
* Fix - Resolves an issue where the login option for the site was not available when paying with Express Checkout. ([#737](https://github.com/angelleye/paypal-woocommerce/issues/737))
* Fix - Resolves an issue with shipping address validation from the cart page when using Express Checkout. ([#739](https://github.com/angelleye/paypal-woocommerce/issues/739))
* Fix - Resolves an issue with addresses being passed around incorrectly when using Express Checkout . ([#742](https://github.com/angelleye/paypal-woocommerce/issues/742))
* Fix - Resolves an issue where the mini-cart was still displaying a Proceed to Checkout button even with Express Checkout is the only gateway enabled. ([#747](https://github.com/angelleye/paypal-woocommerce/issues/747))
* Fix - Resolves an issue where custom fields were not populating like before. ([#733](https://github.com/angelleye/paypal-woocommerce/issues/733))
* Fix - Resolves an issue with get_cart(). ([#736](https://github.com/angelleye/paypal-woocommerce/issues/736))
* Fix - Resolves PHP notices shown in logs depending on how error reporting settings are configured. ([#738](https://github.com/angelleye/paypal-woocommerce/issues/738))
* Fix - Resolves issues with Woo Subscriptions compatibility on some versions. ([#740](https://github.com/angelleye/paypal-woocommerce/issues/740))
* Fix - Resolves an issue with Autoship compatibility. ([#749](https://github.com/angelleye/paypal-woocommerce/issues/749)) ([#752](https://github.com/angelleye/paypal-woocommerce/issues/752))
* Fix - Resolves issue with sales tax calculations in Express Checkout. ([#753](https://github.com/angelleye/paypal-woocommerce/issues/753))
* Fix - Resolves PHP notices showing up in error logs. ([#754](https://github.com/angelleye/paypal-woocommerce/issues/754)) ([#723](https://github.com/angelleye/paypal-woocommerce/issues/723))
* Fix - Resolves a problem with PayFlow when the subtotal is zero and all you are paying for is shipping. ([#758](https://github.com/angelleye/paypal-woocommerce/issues/758))

= 1.4.4 - 05.12.2017 =
* Tweak - Braintree MID functionality improvements. ([#565](https://github.com/angelleye/paypal-woocommerce/issues/565))
* Tweak - Removes default value for invoice prefix in Express Checkout. ([#708](https://github.com/angelleye/paypal-woocommerce/issues/708))
* Tweak - Adjustments to cancel button on Express Checkout review page. ([#714](https://github.com/angelleye/paypal-woocommerce/issues/714))
* Fix - Resolves an issue where "Invalid Payment Method" was coming up for some Express Checkout orders. ([#710](https://github.com/angelleye/paypal-woocommerce/issues/710))
* Fix - Resolves issue where PayPal Credit was still not available for UK merchants. ([#709](https://github.com/angelleye/paypal-woocommerce/issues/709))
* Fix - Resolves a compatibility issue with Autoship and WooCommerce 3.0+.  ([#718](https://github.com/angelleye/paypal-woocommerce/issues/718))
* Fix - Resolves a CSS conflict with Flatsome theme. ([#716](https://github.com/angelleye/paypal-woocommerce/issues/716))

= 1.4.3 - 05.09.2017 =
* Tweak - Adjusts placement of "save payment method" option in Express Checkout. ([#704](https://github.com/angelleye/paypal-woocommerce/issues/704))
* Tweak - Adds Cancel button to Express Checkout review page. ([#705](https://github.com/angelleye/paypal-woocommerce/issues/705))
* Fix - Various adjustments to the new Express Checkout integration. ([#686](https://github.com/angelleye/paypal-woocommerce/issues/686))
* Fix - Resolves an issue with Express Checkout showing up in the mini-cart. ([#687](https://github.com/angelleye/paypal-woocommerce/issues/687))
* Fix - Resolves a problem where Express Checkout shows up on the checkout page even when disabled there. ([#688](https://github.com/angelleye/paypal-woocommerce/issues/688))
* Fix - Resolves some CSS styling issues with Express Checkout buttons. ([#689](https://github.com/angelleye/paypal-woocommerce/issues/689)) ([#692](https://github.com/angelleye/paypal-woocommerce/issues/692))
* Fix - Resolves a problem with PayPal Credit showing up for UK merchants. ([#690](https://github.com/angelleye/paypal-woocommerce/issues/690))
* Fix - Resolves an issue in Express Checkout where tokens would come up invalid. ([#691](https://github.com/angelleye/paypal-woocommerce/issues/691))
* Fix - Resolves an issue where error details were not getting included in admin error email notifications as expected. ([#695](https://github.com/angelleye/paypal-woocommerce/issues/695))
* Fix - Resolves a PHP error, call to a member function get(). ([#697](https://github.com/angelleye/paypal-woocommerce/issues/697))
* Fix - Resolves issue where QTY is not always passing correctly. ([#698](https://github.com/angelleye/paypal-woocommerce/issues/698))
* Fix - Resolves a tax calculation issue in Express Checkout. ([#699](https://github.com/angelleye/paypal-woocommerce/issues/699))
* Fix - Resolves an issue where the product-level Express Checkout option was overriding the global settings. ([#702](https://github.com/angelleye/paypal-woocommerce/issues/702))

= 1.4.2 - 05.05.2017 =
* Tweak - Adjustments for backwards compatibility with WooCommerce 2.6. ([#680](https://github.com/angelleye/paypal-woocommerce/issues/680)) ([#681](https://github.com/angelleye/paypal-woocommerce/issues/681))
* Tweak - Sets product level Express Checkout option to disabled by default. ([#670](https://github.com/angelleye/paypal-woocommerce/issues/670))
* Fix - Resolves an issue where Express Checkout buttons were not displaying where they should be. ([#662](https://github.com/angelleye/paypal-woocommerce/issues/662))
* Fix - Further adjustments to resolve API credential issues. ([#668](https://github.com/angelleye/paypal-woocommerce/issues/668))
* Fix - Removes duplicate cardholder name fields on checkout form. ([#669](https://github.com/angelleye/paypal-woocommerce/issues/669))
* Fix - Resolves an issue where the Express Checkout buttons were not always displaying when they should. ([#672](https://github.com/angelleye/paypal-woocommerce/issues/672))
* Fix - Resolves issue with billing/shipping addresses in Express Checkout. ([#667](https://github.com/angelleye/paypal-woocommerce/issues/667)) ([#671](https://github.com/angelleye/paypal-woocommerce/issues/671))
* Fix - Resolves a PHP parsing error in Express Checkout with some orders. ([#674](https://github.com/angelleye/paypal-woocommerce/issues/674))
* Fix - Resolves an issue with line items being passed in Express Checkout. ([#676](https://github.com/angelleye/paypal-woocommerce/issues/676))
* Fix - Resolves issue with checkout button styling on some themes. ([#665](https://github.com/angelleye/paypal-woocommerce/issues/665))
* Fix - Resolves PHP error on call_user_func_array(). ([#679](https://github.com/angelleye/paypal-woocommerce/issues/679))

= 1.4.1 - 05.02.2017 =
* Fix - Resolves a problem with voiding authorizations. ([#660](https://github.com/angelleye/paypal-woocommerce/issues/660))
* Fix - Resolves a problem with credentials getting saved incorrectly. ([#661](https://github.com/angelleye/paypal-woocommerce/issues/661)) ([#663](https://github.com/angelleye/paypal-woocommerce/issues/663))
* Fix - Resolves a problem processing refunds through WooCommerce with some gateways. ([#664](https://github.com/angelleye/paypal-woocommerce/issues/664))

= 1.4.0 - 05.01.2017 =
* Feature - WooCommerce 3.0 compatibility. ([#653](https://github.com/angelleye/paypal-woocommerce/issues/653))
* Feature - WooCommerce Subscriptions compatibility. ([#10](https://github.com/angelleye/paypal-woocommerce/issues/10))
* Feature - Major overhaul to Express Checkout integration inside WooCommerce. ([#630](https://github.com/angelleye/paypal-woocommerce/issues/630)) ([#560](https://github.com/angelleye/paypal-woocommerce/issues/560)) ([#447](https://github.com/angelleye/paypal-woocommerce/issues/447)) ([#616](https://github.com/angelleye/paypal-woocommerce/issues/616)) ([#639](https://github.com/angelleye/paypal-woocommerce/issues/639)) ([#584](https://github.com/angelleye/paypal-woocommerce/issues/584)) ([#464](https://github.com/angelleye/paypal-woocommerce/issues/464)) ([#360](https://github.com/angelleye/paypal-woocommerce/issues/360)) ([#596](https://github.com/angelleye/paypal-woocommerce/issues/596)) ([#594](https://github.com/angelleye/paypal-woocommerce/issues/594)) ([#549](https://github.com/angelleye/paypal-woocommerce/issues/549))
* Feature - WooCommerce Subscriptions compatibility. ([#105](https://github.com/angelleye/paypal-woocommerce/issues/105))
* Feature - WooCommerce Sequential Orders compatibility. ([#145](https://github.com/angelleye/paypal-woocommerce/issues/145))
* Feature - Filter hooks for PayPal buttons. ([#588](https://github.com/angelleye/paypal-woocommerce/issues/588))
* Feature - Adds error message to the WooCommerce order notes for failed Braintree transactions. ([#647](https://github.com/angelleye/paypal-woocommerce/issues/647))
* Feature - Adds an option to include AVS / CVV2 results in admin order email notifications for credit card gateways. ([#611](https://github.com/angelleye/paypal-woocommerce/issues/611))
* Feature - Adds custom CSS class for PayPal buttons / links. ([#644](https://github.com/angelleye/paypal-woocommerce/issues/644))
* Feature - Gift Cards Pro Compatibility. ([#550](https://github.com/angelleye/paypal-woocommerce/issues/550))
* Feature - Adds filter hook for PayPal API requests so you can adjust request parameters to suit your needs. ([#510](https://github.com/angelleye/paypal-woocommerce/issues/510))
* Feature - WooCommerce MailChimp compatibility. ([#592](https://github.com/angelleye/paypal-woocommerce/issues/592))
* Feature - Adds the ability to enable / disable the PayPal Express Checkout button at the product level. ([#425](https://github.com/angelleye/paypal-woocommerce/issues/425))
* Feature - Specify whether or not Express Checkout shortcut buttons add an additional unit to the cart before redirecting to PayPal or not. ([#355](https://github.com/angelleye/paypal-woocommerce/issues/355))
* Feature - Adds Soft Descriptor setting to credit card gateways so you can set what shows on customer credit card statements. ([#634](https://github.com/angelleye/paypal-woocommerce/issues/634))
* Feature - Adds options for how to display CC month / year on the checkout form for direct credit card gateways. ([#617](https://github.com/angelleye/paypal-woocommerce/issues/617))
* Feature - Adds the ability to set PayPal sandbox / test mode at the product level. ([#204](https://github.com/angelleye/paypal-woocommerce/issues/204))
* Feature - Adds options for how to handle orders where PayPal Fraud Management Filters are flagged. ([#618](https://github.com/angelleye/paypal-woocommerce/issues/618))
* Feature - Compatibility with a variety of plugins that did not work well previously. ([#218](https://github.com/angelleye/paypal-woocommerce/issues/218)) ([#240](https://github.com/angelleye/paypal-woocommerce/issues/240)) ([#356](https://github.com/angelleye/paypal-woocommerce/issues/356)) ([#568](https://github.com/angelleye/paypal-woocommerce/issues/568)) ([#577](https://github.com/angelleye/paypal-woocommerce/issues/577)) ([#646](https://github.com/angelleye/paypal-woocommerce/issues/646))
* Tweak - Adjusts the way session data is passed around in WordPress / WooCommerce to avoid conflicts with caching / CDN services. ([#337](https://github.com/angelleye/paypal-woocommerce/issues/337))
* Tweak - Adjustments to improve compatibility with WPML. ([#387](https://github.com/angelleye/paypal-woocommerce/issues/387)) ([#641](https://github.com/angelleye/paypal-woocommerce/issues/641))
* Tweak - Adds PayPal Credit option for UK orders. ([#638](https://github.com/angelleye/paypal-woocommerce/issues/638))
* Tweak - Improves the experience for adding a custom image to payment gateways. ([#575](https://github.com/angelleye/paypal-woocommerce/issues/575))
* Tweak - Disables PayPal Express Checkout button on variable product pages until variations / options are selected. ([#555](https://github.com/angelleye/paypal-woocommerce/issues/555))
* Tweak - Removes duplicate custom field for Billing Agreement ID. ([#648](https://github.com/angelleye/paypal-woocommerce/issues/648))
* Tweak - Adjusts the logic around the product level option for "No Shipping Required". ([#571](https://github.com/angelleye/paypal-woocommerce/issues/571))
* Tweak - Adjusts the way the billing address section of checkout pages is displayed based on the address settings in the plugin. [(#633](https://github.com/angelleye/paypal-woocommerce/issues/633))
* Fix - Resolves an issue where multiple orders would show up in WooCommerce when PayPal error 10486 would occur. ([#589](https://github.com/angelleye/paypal-woocommerce/issues/589))

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

= 3.0.0 =
After updating, make sure to clear any caching / CDN plugins you may be using.  Also, go into the plugin's gateway settings, review everything, and click Save even if you do not make any changes.