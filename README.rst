###################
Introduction
###################

This is a PayPal extension for the WooCommerce shopping cart system on WordPress.

*******************
Requirements
*******************

-  PHP version 5.3+.
-  WordPress 3.8+
-  WooCommerce 2.3+

************
Installation
************

Automatic Installation
----------------------
Automatic installation is the easiest option as WordPress handles the file transfers itself and you don't need to leave your web browser. To do an automatic install of PayPal for WooCommerce, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type PayPal for WooCommerce and click Search Plugins. Once you've found our plugin you can view details about it such as the the point release, rating and description. Most importantly of course, you can install it by simply clicking Install Now.

Manual Installation
-------------------
 1. Unzip the files and upload the folder into your plugins folder (wp-content/plugins/) overwriting old versions if they exist
 2. Activate the plugin in your WordPress admin area.
 3. Open the settings page for WooCommerce and click the "Checkout" tab
 4. Click on the sub-item for PayPal Express Checkout or Payments Pro.
 5. Configure your settings accordingly.

*********
Setup
*********

Login to your WordPress control panel and go to WooCommerce -> Settings.  Then click into the Checkout tab.

You'll see the following Checkout Options have been added.

- PayPal Website Payments Pro (DoDirectPayment)
- PayPal Payments Pro 2.0 (PayFlow) 
- PayPal Express Checkout

For each one that you would like to activate, simply click into that section, enter your API credentials, and setup the options however you like.

*********
Resources
*********

-  `Obtain Sandbox API Credentials <https://www.sandbox.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run>`_
-  `Obtain Live API Credentials <https://www.paypal.com/us/cgi-bin/webscr?cmd=_login-api-run>`_