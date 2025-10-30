=== EuroParcel Integration for WooCommerce ===
Contributors: europarcelcom
Tags: woocommerce, shipping, europarcel, courier, romania
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Connect your WooCommerce store with EuroParcel shipping platform for seamless courier integration in Romania.

== Description ==

EuroParcel Integration for WooCommerce provides a comprehensive shipping solution for Romanian online stores. Connect your WooCommerce store directly with EuroParcel's platform to offer multiple courier services and locker delivery options to your customers.

**Key Features:**

* Multiple courier integration (Cargus, DPD, FanCourier, GLS, SameDay, and more)
* Locker delivery options with interactive map selection
* Support for both WooCommerce Classic and Block-based checkout
* Real-time shipping cost calculation
* Advanced shipping zone configuration
* HPOS (High-Performance Order Storage) compatible

**Supported Couriers:**

* Cargus National
* DPD Standard
* FanCourier Standard
* GLS National
* SameDay
* SameDay EasyBox
* FanCourier Box
* DPD Box

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/europarcel-plugin` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to WooCommerce > Settings > Shipping to configure the plugin.
4. Add EuroParcel shipping method to your shipping zones.
5. Enter your EuroParcel API credentials to connect your account.

== Frequently Asked Questions ==

= Do I need an EuroParcel account? =

Yes, you need an active EuroParcel account and API credentials to use this plugin.

= Does this plugin work with WooCommerce Blocks checkout? =

Yes, the plugin supports both Classic and Block-based WooCommerce checkout.

= Can customers select locker delivery? =

Yes, customers can select delivery to lockers using our interactive map interface.

= Is this plugin HPOS compatible? =

Yes, the plugin is fully compatible with WooCommerce High-Performance Order Storage.

== Screenshots ==

1. Plugin configuration settings
2. Shipping method setup in WooCommerce
3. Customer checkout with locker selection
4. Interactive locker map interface

== External Services ==

This plugin connects to EuroParcel's external API services to provide shipping functionality:

**EuroParcel API**
- Service URL: https://api.europarcel.com/
- Purpose: Calculate shipping rates, retrieve courier services, and manage shipping requests
- Data sent: Order details (weight, dimensions, destination address), customer information
- Privacy Policy: https://www.eawb.ro/politica-confidentialitate
- Terms of Service: https://www.eawb.ro/termeni-conditii-eawb
- Required: Yes, API key from your EuroParcel account is required for the plugin to function

**EuroParcel Maps Service**
- Service URL: https://maps.europarcel.com/
- Purpose: Display interactive map for locker selection
- Data sent: Customer location (county/city) for filtering nearby lockers
- Required: Yes, for locker delivery functionality

== Changelog ==

= 1.0.1 =
* Initial release
* Multiple courier integration
* Locker delivery support
* WooCommerce Blocks compatibility
* HPOS compatibility

== Upgrade Notice ==

= 1.0.1 =
Initial release of EuroParcel Integration for WooCommerce plugin.
