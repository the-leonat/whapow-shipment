<?php
/**
 * Plugin Name: Whapow Mehrweg Versand
 * Plugin URI:
 * Description: Mehrwegversand Integration für myEnso
 * Author: Leonard Puhl
 * Author URI: http://leonat.de
 * Version: 0.2
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once "includes/whapow-logger.php";
require_once 'includes/class-whapow-shipment.php';

/*
 * Check if WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    function whapow_shipping_method()
    {
        require_once 'includes/class-whapow-shipping-method.php';
    }

    function whapow_add_shipping_method($methods)
    {
        $methods["whapow"] = 'Whapow_Shipping_Method';
        return $methods;
    }

    function get_shipping_method_options()
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];
        $chosen_rate = WC()->session->get('shipping_for_package_0')["rates"][$chosen_shipping];

        return get_option("woocommerce_" . $chosen_rate->method_id . "_" . $chosen_rate->instance_id . "_settings");

    }

    function get_active_shipping_method_id()
    {
        $chosen_methods = WC()->session->get('chosen_shipping_methods');
        $chosen_shipping = $chosen_methods[0];
        $chosen_rate = WC()->session->get('shipping_for_package_0')["rates"][$chosen_shipping];

        return $chosen_rate->method_id;
    }

    function whapow_estimated_delivery_checkout_page()
    {
        if (WC()->session->__isset("whapow_shipment_delivery_date")) {
            echo '<tr class="whapow-delivery-datetime"><td colspan="2"><b>Lieferung</b><br /><span>' . WC()->session->get('whapow_shipment_delivery_date') . '.</span>';
            echo "<br /> Bitte sei um diese Zeit Zuhause damit du das Paket direkt in Empfang nehmen kannst. <a href='//faq/#shipping'>Warum?</a>";
            echo '</td></tr>';
        }
    }

    function whapow_shipping_method_chosen()
    {
        if (get_active_shipping_method_id() === "whapow") {
            $options = get_shipping_method_options();

            //var_dump($options);
            $shipment = new Whapow_Shipment();
            $delivery_periods_string = $options['delivery_periods'];
            $order_periods_string = $options['order_periods'];
            $holidays_string = $options['holidays'];

            //build objects
            if ($shipment->delivery_periods_from_string($delivery_periods_string) &&
                $shipment->order_periods_from_string($order_periods_string) &&
                $shipment->holidays_from_string($holidays_string)) {
                try {
                    $delivery = $shipment->get_closest_delivery_interval(new DateTime("NOW"), 0);
                    WC()->session->set('whapow_shipment_delivery_date', $delivery->to_readable_string());

                } catch (Exception $e) {
                    WC()->session->__unset('whapow_shipment_delivery_date');
                }
                $delivery = $shipment->get_closest_delivery_interval(new DateTime("NOW"), 0);
                WC()->session->set('whapow_shipment_delivery_date', $delivery->to_readable_string());
                dlog(WC()->session->get('whapow_shipment_delivery_date'));
                return;
            }
        }

        WC()->session->__unset('whapow_shipment_delivery_date');
    }

    function whapow_load_plugin_css()
    {
        $plugin_url = plugin_dir_url(__FILE__);

        wp_enqueue_style('style1', $plugin_url . 'css/style.css');
    }

    // save sessions values as order meta
    function before_checkout_create_order($order, $data)
    {
        if (WC()->session->__isset("whapow_shipment_delivery_date")) {
            $delivery_string = WC()->session->get("whapow_shipment_delivery_date");
            $order->update_meta_data('whapow_shipment_delivery_date', $delivery_string);
        }
    }

    function whapow_email_order_details_wrapper($order)
    {
        $data = $order->get_data();
        $meta = $data["meta_data"][0]->get_data();
        // echo var_dump($data);

        if (isset($meta["value"])) {
            echo "<div class='whapow-delivery-datetime-wrapper'>";
            echo "<h2>Lieferung</h2>";

            echo '<p class="whapow-delivery-datetime"><span>' . $meta["value"] . '.</span></p>';
            echo "<p> Bitte sei um diese Zeit Zuhause damit du das Paket direkt in Empfang nehmen kannst.</p>";
            echo "</div>";

        }
    }

    function whapow_order_details_after_order_table_items($order)
    {
        $data = $order->get_data();
        $meta = $data["meta_data"][0]->get_data();

        if (isset($meta["value"])) {
            echo '<tr class="whapow-delivery-datetime"><td colspan="2"><b>Lieferung</b><br /><span>' . $meta["value"] . '.</span>';
            echo "<br /> Bitte sei um diese Zeit Zuhause damit du das Paket direkt in Empfang nehmen kannst.";
            echo '</td></tr>';
        }
    }

    // load custom style sheet
    add_action('wp_enqueue_scripts', 'whapow_load_plugin_css');

    // save delivery times in order 
    add_action('woocommerce_checkout_create_order', 'before_checkout_create_order', 20, 2);

    // add delivery times to checkout page
    add_action('woocommerce_order_details_after_order_table_items', 'whapow_order_details_after_order_table_items', 10, 10);

    // add delivery times to email template
    add_action('woocommerce_email_after_order_table', 'whapow_email_order_details_wrapper', 10, 10);

    // push the correct deliverytimes into sessionstorage
    add_action('woocommerce_shipping_method_chosen', 'whapow_shipping_method_chosen', 10, 1);

    // update delivery times on checkout page
    add_action('woocommerce_review_order_after_shipping', 'whapow_estimated_delivery_checkout_page', 10, 0);

    // setting up shipping method
    add_filter('woocommerce_shipping_methods', 'whapow_add_shipping_method');
    add_action('woocommerce_shipping_init', 'whapow_shipping_method');
}