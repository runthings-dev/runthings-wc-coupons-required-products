# Coupons Required Products for WooCommerce

Restrict the usage of WooCommerce coupons unless required products are in the cart.

## Description

This plugin allows you to restrict the usage of WooCommerce coupons unless specific products are in the cart.

You can specify which products are required for a coupon to be valid, providing more control over your discount strategies.

## Features

- Restrict coupon usage based on all required products being in the cart.
- Customize the error message via a filter.

## Installation

1. Upload the plugin files to the `/wp-content/plugins/runthings-wc-coupons-required-products` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to WooCommerce area of the admin panel, and look under Marketing > Coupons and edit or create a coupon.
4. In the "Usage restriction" tab, you will see the option to select required products for the coupon.

## Filters

### runthings_wc_coupons_required_products_error_message

This filter allows customization of the error message shown when a coupon is not valid due to missing required products.

#### Parameters:

| Parameter            | Type        | Description                                                                               |
| -------------------- | ----------- | ----------------------------------------------------------------------------------------- |
| `$message`           | `string`    | The default error message, e.g., `"This coupon requires specific products in the cart."`. |
| `$coupon`            | `WC_Coupon` | The coupon object being validated.                                                        |
| `$required_products` | `array`     | The required products for the coupon, in the format `[product_id => quantity]`.           |
| `$missing_products`  | `array`     | The missing products in the cart, in the format `[product_id => quantity]`.               |

#### Usage example:

A simple static message replacement:

```php
add_filter('runthings_wc_coupons_required_products_error_message', 'custom_coupon_required_products_error_message');

function custom_coupon_required_products_error_message($message) {
    return __('Custom error message for invalid coupon.', 'your-theme');
}
```

#### Advanced usage:

```php
add_filter('runthings_wc_coupons_required_products_error_message', function ($error_message, $coupon, $required_products, $missing_products) {
    $missing_product_titles = [];

    foreach ($missing_products as $product_id => $quantity) {
        $product = wc_get_product($product_id);
        if ($product) {
            $missing_product_titles[] = $product->get_name();
        }
    }

    if (!empty($missing_product_titles)) {
        $error_message = sprintf(
            __('This coupon requires specific products in the cart. You still need to add the following products: %s', 'your-theme'),
            implode(', ', $missing_product_titles)
        );
    }

    return $error_message;
}, 10, 4);
```

## Frequently Asked Questions

### What is the difference between the built-in WooCommerce "Products" field and this plugin?

The built-in WooCommerce "Products" field allows you to specify products that the coupon can be applied to.

This means that the discount will be applied if _any_ of the specified products are in the cart.

In contrast, this plugin restricts the usage of the coupon unless _all_ of the specified products are in the cart.

This means that the coupon will not be valid unless every required product is present in the cart.

### How do I restrict a coupon to specific products?

Edit the coupon and go to the "Usage restriction" tab.

In the "Required products" section, select the products that must be in the cart for the coupon to be valid.

### Can I use this plugin with other WooCommerce coupon restrictions?

Yes, this plugin works alongside other WooCommerce coupon restrictions such as minimum spend, maximum spend, and role restrictions.

### Can you implement a specific variation I need for the required products?

The plugin has been kept deliberately simple for now, because this met the client's specific needs.

There are lots of other combinations of requirements that I can think of like excluding products, involving quantities, requiring x products in a category, etc.

If you have a specific requirement, and time allows, I'll try to implement it.

Please open an issue on the repo, over at [GitHub](https://github.com/runthings-dev/runthings-wc-coupons-required-products).

## Screenshots

1. Coupon settings page with required products field.
   ![Coupon settings page with required products field](screenshot-1.png)

2. Example denied coupon usage due to missing required products.
   ![Example denied coupon usage due to missing required products](screenshot-2.png)

3. Example denied coupon usage due to missing required products, with custom message.
   ![Example denied coupon usage due to missing required products](screenshot-3.png)

## Changelog

### 1.2.0 - 6th March 2025

- Fixed a bug which caused a valid voucher to fail to verify.

### 1.1.0 - 7th February 2025

- Incorporate amends requested for WordPress plugin directory submission.
- Filter `runthings_wc_coupons_required_products_error_message` to customize error message.
- Filter `runthings_wc_coupon_required_products_error_message` deprecated.

### 1.0.0 - 29th January 2025

- Initial release.
- Restrict coupons by required products.
- Filter `runthings_wc_coupon_required_products_error_message` to customize error message.

## Contributing

If you have a specific requirement or find a bug, please open an issue on the [GitHub repository](https://github.com/runthings-dev/runthings-wc-coupons-required-products).

## License

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, see [http://www.gnu.org/licenses/gpl-3.0.html](http://www.gnu.org/licenses/gpl-3.0.html).

Icon - Discount by Gregor Cresnar, from Noun Project, https://thenounproject.com/browse/icons/term/discount/ (CC BY 3.0)

Icon - restriction by Puspito, from Noun Project, https://thenounproject.com/browse/icons/term/restriction/ (CC BY 3.0)
