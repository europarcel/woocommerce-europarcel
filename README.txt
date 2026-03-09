=== EuroParcel Integration for WooCommerce ===
Contributors: europarcelcom
Tags: woocommerce, shipping, europarcel, courier, romania
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.8
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Save up to 50% on shipping costs with EuroParcel. Access negotiated rates from top Romanian couriers directly in your WooCommerce store.

== Description ==

EuroParcel helps Romanian online stores ship smarter. We negotiate bulk rates with all major couriers so you can offer competitive shipping without the hassle of managing multiple contracts.

[Get started at eawb.ro](https://eawb.ro) | [Manage integration](https://eawb.ro/dashboard/integrations) | [Contact us](https://eawb.ro/contact)

**Why EuroParcel?**

* **Save up to 50%** on shipping with pre-negotiated courier rates
* **One integration, all couriers** – FAN Courier, DPD, Cargus, GLS, SameDay, FedEx and more
* **Locker delivery** – Let customers pick up from EasyBox, FanBox or DPD lockers
* **Daily reimbursements** – Get your cash-on-delivery payments faster
* **98%+ on-time delivery** – Trusted by 9,000+ Romanian businesses

**Supported Couriers:**

* FAN Courier
* DPD
* Cargus
* GLS
* SameDay
* SameDay EasyBox
* FedEx
* Dragon Star
* FanBox
* DPD Box

== Installation ==

1. Go to Plugins > Add New in your WordPress admin.
2. Search for "EuroParcel".
3. Click "Install Now" and then "Activate".
4. Go to WooCommerce > Settings > Shipping and add EuroParcel to your shipping zones.
5. Enter your EuroParcel API credentials from [eawb.ro/dashboard/integrations](https://eawb.ro/dashboard/integrations).

== Frequently Asked Questions ==

= Do I need an EuroParcel account? =

Yes, you need an active EuroParcel account. [Create a free account at eawb.ro](https://eawb.ro/inregistrare) and get your API credentials from the [integrations dashboard](https://eawb.ro/dashboard/integrations).

= Does this plugin work with WooCommerce Blocks checkout? =

Yes, the plugin supports both Classic and Block-based WooCommerce checkout.

= Can customers select locker delivery? =

Yes, customers can select delivery to lockers using our interactive map interface.

= Is this plugin HPOS compatible? =

Yes, the plugin is fully compatible with WooCommerce High-Performance Order Storage.

= Need help? =

[Contact our support team](https://eawb.ro/contact) – we're happy to help!

== Screenshots ==

1. Plugin configuration settings in eAWB
2. Shipping method setup in eAWB
3. Shipping method setup in WooCommerce
4. Interactive locker map interface

== Changelog ==

= 1.0.8 =
* Fixed: Price fields now accept decimal values (e.g., 15.99) instead of integers only

= 1.0.7 =
* Performance fix: Eliminated unnecessary API requests on cart and checkout pages

= 1.0.6 =
* Fixed locker selection bug where wrong locker was saved to order meta
* Fixed race condition between session update and checkout refresh

= 1.0.5 =
* Improved checkout integration for both Classic and Blocks checkout
* Enhanced locker selection modal interface
* Code refactoring and documentation improvements

= 1.0.4 =
* Added Dragon star courier support
* Added FedEx courier support
* Updated translation files (.pot, .po, .mo)
* Code cleanup and optimization

= 1.0.3 =
* Initial release
* Multiple courier integration
* Locker delivery support
* WooCommerce Blocks compatibility
* HPOS compatibility

== Upgrade Notice ==

= 1.0.8 =
Bug fix: Price fields now accept decimal values.

= 1.0.7 =
Performance fix: Eliminated unnecessary API requests on cart and checkout pages.

= 1.0.6 =
Fixed locker selection bug where wrong locker was saved to order meta.

= 1.0.5 =
Improved checkout integration and locker selection modal.

= 1.0.4 =
Added support for Dragon star and FedEx couriers. Updated translations.

= 1.0.1 =
Initial release of EuroParcel Integration for WooCommerce plugin.
