# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Is

EuroParcel Integration for WooCommerce — a WordPress/WooCommerce shipping plugin that integrates multiple Romanian couriers (FAN Courier, DPD, Cargus, GLS, SameDay, Dragon Star, FedEx, Bookurier) through the EuroParcel API (`https://api.europarcel.com/api/`). Supports both standard delivery and locker-based delivery with interactive map selection.

## Requirements

- PHP 7.4+, WordPress 5.0+, WooCommerce 5.0+
- Text domain: `europarcel-com`
- No composer or npm dependencies — vanilla WordPress plugin

## Build & Package

```bash
# Package for WordPress.org distribution (outputs zip to Desktop)
bash build.sh

# Validate internationalization strings
bash check-i18n.sh
```

There are no test suites, linters, or CI pipelines configured.

## Architecture

### Class Hierarchy

All classes use the `EuroParcelComWC_` prefix. Core classes in `includes/`:

- **`europarcel-com.php`** — Bootstrap: loads dependencies, registers hooks, initializes the shipping method
- **`class-europarcel-main.php`** — Entry point class: sets up checkout handler and AJAX hooks
- **`class-europarcel-shipping.php`** — Extends `WC_Shipping_Method`: settings UI, rate calculation, free shipping logic, price markups
- **`class-europarcel-checkout.php`** — Detects checkout type (Classic vs WooCommerce Blocks) and renders locker selection UI
- **`class-europarcel-customer.php`** — Handles API calls for billing addresses, pricing, and carrier availability
- **`class-europarcel-http-request.php`** — HTTP wrapper: GET/POST to API with `X-API-Key` authentication
- **`class-europarcel-request-data.php`** — Builds request payloads for API shipping calculations
- **`class-europarcel-constants.php`** — Carrier configs, service IDs, available services mapping (namespace: `EuroparcelComWCShipping`)

### Data Flow

1. Shipping rates: `WC_Shipping_Method::calculate_shipping()` → `RequestData` builds payload → `HttpRequest` calls API → rates displayed
2. Locker selection: JS triggers AJAX (`europarcelcomwc_get_locker_carriers`) → API returns lockers → user picks from map → selection saved to user meta (`europarcelcom_wc_carrier_lockers`)

### Frontend Assets (`assets/`)

- **`js/europarcel-locker-selector.js`** — Main frontend: locker selection UI, AJAX calls, checkout integration
- **`js/europarcel-modal.js`** — Modal window for locker picker map
- **`js/europarcel-admin.js`** — Admin settings page enhancements
- Scripts depend on jQuery; enqueued conditionally on checkout/admin pages

### Key WordPress Hooks

- `woocommerce_shipping_init` / `woocommerce_shipping_methods` — Register shipping method
- `before_woocommerce_init` — Declare HPOS compatibility
- `woocommerce_review_order_after_shipping` — Classic checkout locker button
- AJAX actions: `europarcelcomwc_get_locker_carriers`, `europarcelcomwc_update_locker_shipping`

## Important Conventions

- Shipping method settings are stored per-instance: `woocommerce_europarcelcom_wc_shipping_{INSTANCE_ID}_settings`
- Namespaced classes (`EuroparcelComWCShipping`) are in `includes/` alongside global-namespace classes
- All PHP files guard with `defined('ABSPATH')` check
- Plugin supports both WooCommerce Classic Checkout and Block-based Checkout
- HPOS (High-Performance Order Storage) compatible
