<?php
/**
 * Plugin Name: Whapow Mehrweg Versand
 * Plugin URI:
 * Description: Mehrwegversand Integration für myEnso
 * Author: Leonard Puhl
 * Author URI: http://leonat.de
 * Version: 0.4
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

    function get_delivery_time_from_options()
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
                    $delivery  = $shipment->get_closest_delivery_interval(new DateTime("NOW"), 0);
                    return $delivery->to_readable_string();
                } catch (Exception $e) {
                    return null;
                }
                return null;
            }
        }
        return null;
    }

    function whapow_shipping_method_chosen($method)
    {
        $deliveryString = get_delivery_time_from_options();

        if ($deliveryString !== null) {
            $options = get_shipping_method_options();
            $provider_mail = $options['provider_mail'];
            WC()->session->set('whapow_shipment_delivery_date', $deliveryString);
            WC()->session->set('whapow_shipment_provider_mail', $provider_mail);
        } else {
            WC()->session->__unset('whapow_shipment_delivery_date');
            WC()->session->__unset('whapow_shipment_provider_mail');
        }
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
            $provider_mail = WC()->session->get("whapow_shipment_provider_mail");
            $order->update_meta_data('whapow_shipment_delivery_date', $delivery_string);
            $order->update_meta_data('whapow_shipment_provider_mail', $provider_mail);

        }
    }

    function whapow_estimated_delivery_checkout_page()
    {
        if (WC()->session->__isset("whapow_shipment_delivery_date")) {
            echo '<tr class="whapow-delivery-datetime"><td colspan="2"><b>Lieferung</b><br /><span>' . WC()->session->get('whapow_shipment_delivery_date') . '.</span>';
            echo "<br />Bitte sei im oben genannten Zeitfenster zuhause. <a target='_blank' href='https://whapow.de/faq/#shipping'>Warum?</a>";
            echo "<br />Mit hinterlegter Mobilnummer SMS-Ankündigung <b>45 Minuten vorher</b>.";
            echo '</td></tr>';
        }
    }

    function whapow_email_order_details_wrapper($order)
    {
        $data = $order->get_data();

        if (count($data["meta_data"] > 0)) {
            $meta = $data["meta_data"];
            foreach ($meta as $meta_object) {
                $meta_object = $meta_object->get_data();
                if ($meta_object["key"] === "whapow_shipment_delivery_date") {
                    echo "<div class='whapow-delivery-datetime-wrapper'>";
                    echo "<h2>Lieferung</h2>";
                    echo '<p class="whapow-delivery-datetime">Übergabe am <span style="color:#009c41"><b>' . $meta_object["value"] . '</b></span>.</p>';
                    echo "<p>WHAPOW wird im Mehrweg-System direkt und persönlich übergeben ohne Styroporkiste und muss sofort wieder in die Tiefkühltruhe (kein Abstellservice möglich!).</p>";
                    echo "<p>Mit hinterlegter Mobilnummer <b>SMS-Ankündigung 45 Minuten vorher</b>. Bitte sei im oben genannten Zeitfenster zuhause.</p>";
                    echo "</div>";
                    break;
                }
            }
        }
    }

    function whapow_order_details_before_order_table_items($order)
    {
        $data = $order->get_data();

        if (count($data["meta_data"] > 0)) {
            $meta = $data["meta_data"];
            foreach ($meta as $meta_object) {
                $meta_object = $meta_object->get_data();
                if ($meta_object["key"] === "whapow_shipment_delivery_date") {
                    echo '<tr class="whapow-delivery-datetime"><td colspan="2"><b>Lieferung</b><br />Übergabe am <span style="color:#009c41"><b>' . $meta_object["value"] . '</b></span>.';
                    echo "<br />WHAPOW wird im Mehrweg-System direkt und persönlich übergeben ohne Styroporkiste und muss sofort wieder in die Tiefkühltruhe (kein Abstellservice möglich!).";
                    echo "<br />Mit hinterlegter Mobilnummer <b>SMS-Ankündigung 45 Minuten vorher</b>. Bitte sei im oben genannten Zeitfenster zuhause.";
                    echo '</td></tr>';
                    break;
                }
            }
        }
    }

    function add_dropship_email($email_classes)
    {

        // include our custom email class
        include 'includes/class-whapow-dropship-email.php';

        // add the email class to the list of email classes that WooCommerce loads
        $email_classes['whapow_shipment_notification'] = new Whapow_Dropship_Email();

        return $email_classes;

    }
    add_filter('woocommerce_email_classes', 'add_dropship_email');

    /**
     * Auto Complete all WooCommerce orders.
     */
    add_action('woocommerce_thankyou', 'custom_woocommerce_auto_complete_order');
    function custom_woocommerce_auto_complete_order($order_id)
    {
        if (!$order_id) {
            return;
        }

        $order = wc_get_order($order_id);
        $order->update_status('completed');
    }

    function whapow_email_attachment($attach_documents)
    {
        //var_dump($pdf_path);
        $attach_documents["packing-slip"][] = "whapow_shipment_notification";
        // $attach_documents["packing-slip"][] = "customer_completed_order";

        //var_dump($attach_documents);

        return $attach_documents;
    }

    function whapow_test_attachments($attachments, $email_id, $order)
    {
        dlog("gotcha");
        dlog($email_id);
        dlog(implode(", ", $attachments));

        return $attachments;
    }

    add_filter('woocommerce_email_attachments', "whapow_test_attachments", 100, 3);
    add_action('woocommerce_checkout_process', 'whapow_shipping_order_validation', 20);

    function whapow_shipping_order_validation()
    {
        global $woocommerce;
        $items = $woocommerce->cart->get_cart();

        // check if 6er box is only selected for specified plz-zones

        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
            $p = wc_get_product($values["product_id"]);

            if ($p->get_sku() === "whapow_box_6") {
                if (!WC()->session->__isset("whapow_shipment_delivery_date")) {
                    wc_add_notice(__("Die 6er-Box ist in deiner Region leider nicht verfügbar.", "whapow"), 'error');
                }
            }
        }

        // check if delivery time changed and notifiy user
        if (WC()->session->__isset("whapow_shipment_delivery_date")) {
            $deliveryStringOld = WC()->session->get('whapow_shipment_delivery_date');
            $deliveryStringNew = get_delivery_time_from_options();

            if ($deliveryStringOld !== $deliveryStringNew) {
                WC()->session->set('whapow_shipment_delivery_date', $deliveryStringNew);
                wc_add_notice(__("Die Lieferzeit hat sich über die Zeit deiner Bestellung verändert. Bitte schau nach ob dir das immer noch passt.", "whapow"), 'error');
            }
        }
    }

    function whapow_test_attachment_creation($order, $email_id, $document_type)
    {
        dlog($email_id);
        dlog($document_type);
    }

    function whapow_enqueue_script() {   
        wp_enqueue_script( 'whapow_shipment_script', plugin_dir_url( __FILE__ ) . 'js/whapow-shipment.js', array( 'jquery' )  );
    }

    add_action('wp_enqueue_scripts', 'whapow_enqueue_script');

    add_action('wpo_wcpdf_before_attachment_creation', 'whapow_test_attachment_creation', 99, 3);

    add_filter("wpo_wcpdf_attach_documents", "whapow_email_attachment", 99, 1);

    //add_filter('wpo_wcpdf_attach_documents', "whapow_wcpdf_attach_documents");

    // load custom style sheet
    add_action('wp_enqueue_scripts', 'whapow_load_plugin_css');

    // save delivery times in order
    add_action('woocommerce_checkout_create_order', 'before_checkout_create_order', 20, 2);

    // add delivery times to checkout page
    add_action('woocommerce_order_details_before_order_table_items', 'whapow_order_details_before_order_table_items', 10, 10);

    // add delivery times to email template
    add_action('woocommerce_email_before_order_table', 'whapow_email_order_details_wrapper', 10, 10);

    // push the correct deliverytimes into sessionstorage
    add_action('woocommerce_shipping_method_chosen', 'whapow_shipping_method_chosen', 10, 1);

    // update delivery times on checkout page
    add_action('woocommerce_review_order_after_shipping', 'whapow_estimated_delivery_checkout_page', 10, 0);

    // setting up shipping method
    add_filter('woocommerce_shipping_methods', 'whapow_add_shipping_method');
    add_action('woocommerce_shipping_init', 'whapow_shipping_method');
}
