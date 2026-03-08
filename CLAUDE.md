# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

PayPal for WooCommerce (by Angell EYE) — a WordPress/WooCommerce payment gateway plugin supporting multiple PayPal products. Version 4.6.5, requires PHP 5.4+, WordPress 5.8+, WooCommerce 3.0+.

## Commands

```bash
# Install dev dependencies
composer install

# Lint (PHPCS with WordPress standards)
composer lint              # Full lint check
composer lint:errors       # Errors only (uses phpcs-errors.xml.dist)
composer lint:fix          # Auto-fix with phpcbf

# Lint a specific file
vendor/bin/phpcs --standard=phpcs.xml.dist path/to/file.php
vendor/bin/phpcbf --standard=phpcs.xml.dist path/to/file.php
```

No build step — JS/CSS are maintained directly (no Webpack/Gulp). No automated test suite exists.

## Architecture

### Entry Point & Bootstrap

`paypal-for-woocommerce.php` — main plugin file. Loads shared includes from `angelleye-includes/`, then instantiates `AngellEYE_Gateway_Paypal` (the central controller class defined in the same file). This class registers all payment gateways via the `woocommerce_payment_gateways` filter at priority 1000.

### Gateway Structure

Two generations of gateways coexist:

**Modern (PPCP) — `ppcp-gateway/`:**
- `WC_Gateway_PPCP_AngellEYE` — main gateway class (`angelleye_ppcp`)
- `AngellEYE_PayPal_PPCP_Payment` — payment processing (largest file, ~310KB)
- `AngellEYE_PayPal_PPCP_Smart_Button` — smart button orchestration
- Child gateways: `WC_Gateway_CC_AngellEYE` (cards), `WC_Gateway_Apple_Pay_AngellEYE`, `WC_Gateway_Google_Pay_AngellEYE`
- Traits for shared behavior: `WC_Gateway_Base_AngellEYE`, `AngellEye_PPCP_Core`, `WC_PPCP_Pre_Orders_Trait`, `WC_Gateway_PPCP_Angelleye_Subscriptions_Base`

**Legacy — `classes/`:**
- PayPal Express Checkout (v1 & v2), Pro (DoDirectPayment), Pro PayFlow, Advanced, REST Credit Cards, Braintree
- Each has a corresponding subscriptions subclass in `classes/subscriptions/`
- PayPal/Braintree SDKs bundled in `classes/lib/`

### Key Directories

| Directory | Purpose |
|-----------|---------|
| `ppcp-gateway/` | Modern PayPal Commerce Platform gateway (active development focus) |
| `ppcp-gateway/subscriptions/` | WooCommerce Subscriptions support for PPCP |
| `ppcp-gateway/checkout-block/` | WooCommerce Blocks integration |
| `ppcp-gateway/funnelkit/` | FunnelKit (Aero Checkout, Upsells) integration |
| `ppcp-gateway/ppcp-payment-token/` | Payment tokenization/vaulting |
| `classes/` | Legacy gateway implementations |
| `angelleye-includes/` | Shared utilities, functions, session management |
| `template/` | Admin, email, and customer-facing templates |
| `assets/` | Legacy JS/CSS/images |
| `ppcp-gateway/js/`, `ppcp-gateway/css/` | PPCP-specific frontend assets |

### Integrations

The plugin integrates with: CartFlows (`ppcp-gateway/cartflow/`, `angelleye-includes/cartflows-pro/`), FunnelKit (`ppcp-gateway/funnelkit/`), WooCommerce Subscriptions, WooCommerce Pre-Orders, and WooCommerce Blocks.

## Coding Conventions

- **Class naming**: `WC_Gateway_*_AngellEYE` or `AngellEYE_*` prefix
- **Function naming**: `angelleye_*` or `angelleye_ppcp_*`, wrapped in `if (!function_exists())` checks
- **File naming**: `class-wc-gateway-*.php` for classes, `angelleye-*.php` for function files
- **Constants**: `PAYPAL_*` or `AE_*` prefix, guarded with `if (!defined())`
- **Singleton pattern**: `protected static $_instance` with `instance()` method
- **Code reuse**: Traits over inheritance for cross-cutting concerns (subscriptions, pre-orders, base gateway)
- **Standards**: WordPress Core/Extra/Docs via PHPCS; short array syntax `[]` is allowed
- **Namespace usage**: Minimal — only bundled PayPal/Braintree SDKs use namespaces; plugin code uses class prefixes
- **Logging**: `AngellEYE_PFW_Payment_Logger` singleton; log path via `angelleye_get_log_path()`
