=== Cart2Cart: Wp e-commerce to WooCommerce Migration ===

Contributors: Cart2Cart
Tags: wp e-commerce to woocommerce, wp e-commerce to woocommerce migration, migrate wp e-commerce to woocommerce
Requires at least: 3.8.1
Tested up to: 4.0
Stable tag: 4.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin is developed to help you with migration from WP e-commerce to WooCommerce.

== Description ==
With the help of this plugin you will easily migrate your WP e-commerce store to WooCommerce. All products, customers and orders will be transferred automatically. Moreover, you don’t have to be tech savvy to perform a successful migration. Take into consideration that the plugin is free, however Full Migration is paid, starting from $69.

= Benefits of the automated migration: =
- **Simple** - migration is easy and painless and requires only few steps to accomplish.   
- **Quick** - only few hours separate you from a desirable WooCommerce store.
- **Reliable** -  technical personnel is always ready to help you. 

[youtube https://www.youtube.com/watch?v=B5IIsPaMJAI]

= Check what entities you can migrate: =
* Products, product images, product attributes, product variants
* Categories, category images
* Customers, customer shipping address, customer billing address
* Orders, order statuses
* Manufacturers, manufacturer images

*Supported WP e-Commerce versions:* 3.6.x, 3.7.x 3.8.x 
*Supported WooCommerce versions:* 1.4.x, 1.5.x, 1.6.x, 2.x (new software versions are constantly being added).

**Please note.** You will be able to move only entities, a theme won’t be converted. 

The plugin performs installations of connection bridges to WP e-Commerce and WooCommerce stores in order to implement data interaction between them.  After that you will be redirected to Cart2Cart site to proceed with your migration.

= Steps to Take before Migration =
1. Perform installation of WooCommerce. Note that WP e-Commerce and WooCommerce should be online and visible from web.
1. Prepare WP e-Commerce access details (host, username, password) - you will need them to install a connection bridge to your WP e-commerce.

== Installation ==
1. Download the plugin zip file
1. Extract plugin zip file to your PC
1. Upload extracted file to wp-content/plugin directory
1. Go to Admin -> Plugins, find “Cart2Cart WP e-commerce to WooCommerce Migration” and click Activate
1. Provide WP e-commerce FTP details and download a connection bridge. 
1. The connection bridge for your WooCommerce store will be uploaded automatically.
1. Press Start Migration button. After you will be  redirected to Cart2Cart website in order to complete your migration.  

== Frequently Asked Questions ==

= Does migration influence the speed of my store? =
The migration process doesn’t influence the speed of your store performance. Needless, to say a newly established store without data will work faster than a store with thousands of products, orders and customers. The most common reasons that may cause a slowdown of your store are:
amount of products doesn’t meet a memory limit of your hosting plan;
wide range of third-party modules;
numerous product variants, product images;
viruses;
resource consuming template with animations;
large amount of visitors.

= What is order statuses mapping? =
Cart2Cart allows to map order statuses on source shopping cart with the corresponding ones on target cart. Order status mapping helps to migrate the order data properly.

Order statuses have to be created by store owner on target shopping cart, so that they were shown on Migration Wizard when the migration is set. Create order statuses on target store from admin panel and map them on the order statuses mapping step of wizard to have orders migrated properly.

You can name them according to your needs - similarly as they are on your current cart or in a different way. Thus, the names of statuses can differ, however while mapping you will preserve the correspondence of data. For example status "Completed" on source store can be mapped with status "Delivered" on target store and the appropriate order data will be transferred.


= How can I enable Custom fields in WooCommerce? = 
Cart2Cart supports migration of Custom fields to WooCommerce 2.x. However, WooCommerce doesn’t display Custom fields by default.
To enable this option you have to:
go to "store/wp-content/plugins/woocommerce/templates/single-product/tabs"
find file "additional-information.php"
add the following code: echo '<table class="shop_attributes"><tbody>'; foreach(get_post_meta($post->ID, $key, true) as $key => $attr) { if(strpos($key, "_") !== 0) { echo '<th>'.$key.'</th>'; echo '<td>'.array_shift($attr).'</td>'; echo '</tr>'; } }	 echo '</tbody></table>';

= Is there a possibility to migrate reviews to WooCommerce? = 
Yes, Cart2Cart supports migration of reviews while moving to WooCommerce. Moreover, review ratings will be moved together with reviews.

= WooCommerce multiple languages migration =
"No other languages apart from English were migrated to WooCommerce. The products/orders/customers in other languages do not show." It is due to the technical characteristics of the platform that multiple languages are not migrated. WooCommerce doesn't support multi-languages by default. So, Cart2Cart migrates only one default language.
= Invalid response received =
Сontact us at support@shopping-cart-migration.com

= An error occurred when trying to connect to your site =
Сontact us at support@shopping-cart-migration.com

= An unknown error occurred =
Сontact us at support@shopping-cart-migration.com
== Screenshots ==
1. /assets/screenshot-1.jpg
2. /assets/screenshot-2.jpg
3. /assets/screenshot-3.jpg

== Changelog ==
= 1.0.0 = * Initial commit