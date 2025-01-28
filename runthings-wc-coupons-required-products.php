<?php

/**
 * Plugin Name: Coupons Required Products for WooCommerce
 * Plugin URI: https://runthings.dev/wordpress-plugins/wc-coupons-required-products/
 * Description: Restrict the usage of coupons unless required products are in the cart.
 * Version: 0.0.0
 * Author: runthingsdev
 * Author URI: https://runthings.dev/
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 6.4
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Text Domain: runthings-wc-coupons-required-products
 * Domain Path: /languages
 */

/*
Copyright 2025 Matthew Harris

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

namespace Runthings\WCCouponsRequiredProducts;

use Exception;
use WC_Coupon;
use WC_Discounts;

if (!defined('WPINC')) {
    die;
}

class CouponsRequiredProducts
{
    const PLUGIN_VERSION = '1.0.0';
    const REQUIRED_PRODUCTS_META_KEY = 'runthings_wc_required_products';

    public function __construct()
    {
        if (!$this->is_woocommerce_active()) {
            add_action('admin_notices', [$this, 'admin_notice_wc_inactive']);
            return;
        }

        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('woocommerce_coupon_options_usage_restriction', [$this, 'add_required_products_fields'], 10);
        add_action('woocommerce_coupon_options_save', [$this, 'save_required_products_fields'], 10, 1);
        add_filter('woocommerce_coupon_is_valid', [$this, 'validate_coupon_based_on_required_products'], 10, 3);
    }

    private function is_woocommerce_active(): bool
    {
        return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true) ||
            (is_multisite() && array_key_exists('woocommerce/woocommerce.php', get_site_option('active_sitewide_plugins', [])));
    }

    public function admin_notice_wc_inactive(): void
    {
        echo '<div class="error"><p>';
        esc_html_e('Coupons Required Products for WooCommerce requires WooCommerce to be active. Please install and activate WooCommerce.', 'runthings-wc-coupons-required-products');
        echo '</p></div>';
    }

    public function load_textdomain(): void
    {
        load_plugin_textdomain('runthings-wc-coupons-required-products', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function add_required_products_fields(): void
    {
        global $post;

        echo '<div class="options_group">';
        wp_nonce_field('runthings_save_required_products', 'runthings_required_products_nonce');

        $required_products_meta = get_post_meta($post->ID, self::REQUIRED_PRODUCTS_META_KEY, true);
        $required_products = [];

        if (!empty($required_products_meta) && isset($required_products_meta['required_products'])) {
            $required_products = array_keys($required_products_meta['required_products']);
        }

        echo '<p class="form-field">';
        echo '<label for="' . esc_attr(self::REQUIRED_PRODUCTS_META_KEY) . '">' . esc_html__('Required products', 'runthings-wc-coupons-required-products') . '</label>';
        echo '<select class="wc-product-search" multiple="multiple" style="width: 50%;" id="' . esc_attr(self::REQUIRED_PRODUCTS_META_KEY) . '" name="' . esc_attr(self::REQUIRED_PRODUCTS_META_KEY) . '[]" data-placeholder="' . esc_attr__('Search for a product&hellip;', 'runthings-wc-coupons-required-products') . '" data-action="woocommerce_json_search_products_and_variations">';
        foreach ($required_products as $product_id) {
            $product = wc_get_product($product_id);
            if (is_object($product)) {
                echo '<option value="' . esc_attr($product_id) . '" selected="selected">' . wp_kses_post($product->get_formatted_name()) . '</option>';
            }
        }
        echo '</select>';
        // reason: wc_help_tip already escapes the output
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo wc_help_tip(__('Select products that are required for this coupon.', 'runthings-wc-coupons-required-products'));
        echo '</p>';

        echo '</div>';
    }

    public function save_required_products_fields(int $post_id): void
    {
        if (!isset($_POST['runthings_required_products_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['runthings_required_products_nonce'])), 'runthings_save_required_products')) {
            return;
        }

        $required_products = isset($_POST[self::REQUIRED_PRODUCTS_META_KEY]) ? array_map('intval', (array) wp_unslash($_POST[self::REQUIRED_PRODUCTS_META_KEY])) : [];

        // Convert the input array to a single array with quantities
        $required_products_array = [];
        foreach ($required_products as $product_id) {
            $required_products_array[$product_id] = 1; // Default quantity to 1
        }

        // Add version to the data structure
        $data_to_save = [
            'version' => self::PLUGIN_VERSION,
            'required_products' => $required_products_array
        ];

        update_post_meta($post_id, self::REQUIRED_PRODUCTS_META_KEY, $data_to_save);
    }

    public function validate_coupon_based_on_required_products(bool $is_valid, WC_Coupon $coupon, WC_Discounts $discount): bool
    {
        if (!$is_valid) {
            // should never occur, as fail throws an exception, but just in case
            return $is_valid;
        }

        $required_products_meta = get_post_meta($coupon->get_id(), self::REQUIRED_PRODUCTS_META_KEY, true);

        if (
            empty($required_products_meta)
            || !isset($required_products_meta['version'])
            || empty($required_products_meta['required_products'])
            || !is_array($required_products_meta['required_products'])
        ) {
            return $is_valid;
        }


        $cart_products = [];
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product_id = $cart_item['product_id'];
            if (isset($cart_products[$product_id])) {
                $cart_products[$product_id] += $cart_item['quantity'];
            } else {
                $cart_products[$product_id] = $cart_item['quantity'];
            }
        }

        // Check version and handle accordingly
        if (version_compare($required_products_meta['version'], '1.0.0', '!=')) {
            $error_message = __('This coupon is not valid.', 'runthings-wc-coupons-required-products');
            throw new Exception(esc_html($error_message));
        }

        $missing_products = [];
        foreach ($required_products_meta['required_products'] as $product_id => $quantity) {
            if (!isset($cart_products[$product_id]) || $cart_products[$product_id] < $quantity) {
                $missing_products[$product_id] = $quantity;
            }
        }

        if (empty($missing_products)) {
            return $is_valid;
        }

        $error_message = __('This coupon requires specific products in the cart.', 'runthings-wc-coupons-required-products');
        throw new Exception(esc_html($error_message));
    }
}

new CouponsRequiredProducts();
