<?php

require_once "whapow-logger.php";
require_once "class-whapow-shipment.php";

class Whapow_Shipping_Method extends WC_Shipping_Method
{
    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct($instance_id = 0)
    {
        $this->id = 'whapow';
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Whapow Mehrweg Versand', 'whapow');
        $this->method_description = __('Mehrwegversand über myEnso', 'whapow');

        $this->supports = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );

        $this->init();
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    public function init()
    {
        //dlog("SHIPMENT PLUGIN INIT");
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->shipment = new Whapow_Shipment();

        $delivery_periods_string = $this->get_option('delivery_periods');
        $order_periods_string = $this->get_option('order_periods');
        $holidays_string = $this->get_option('holidays');

        $this->min_amount = $this->get_option('min_free_shipping');
        $this->cost = $this->get_option('shipping_cost');
        $this->provider_mail = $this->get_option('provider_mail');


        //build objects
        if ($this->shipment->delivery_periods_from_string($delivery_periods_string) &&
            $this->shipment->order_periods_from_string($order_periods_string &&
                $this->shipment->holidays_from_string($holidays_string))) {

            // for($i = 0; $i < 10; $i++) {

            // }

        }

        // Save settings in admin if you have any defined
        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Define settings field for this shipping
     * @return void
     */
    public function init_form_fields()
    {

        $this->instance_form_fields = array(

            'title' => array(
                'title' => __('Title', 'whapow'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'whapow'),
                'default' => $this->method_title,
                'desc_tip' => true,
            ),

            'min_free_shipping' => array(
                'title' => __('Min Amount for Free Shipping', 'whapow'),
                'type' => 'number',
                'description' => __('Min Amount for free Shipping', 'whapow'),
                'default' => '75',
                'desc_tip' => true,
            ),

            'shipping_cost' => array(
                'title' => __('Shipping Cost', 'whapow'),
                'type' => 'number',
                'description' => __('Shipping Cost', 'whapow'),
                'default' => '4.9',
                'desc_tip' => true,
            ),

            'holidays' => array(
                'title' => __('Non Shipping Days', 'whapow'),
                'type' => 'textarea',
                'description' => __('Dates in the format DD.MM.YYYY. one per line', 'whapow'),
                'default' => '24.12.2018',
                'desc_tip' => true,
            ),

            'delivery_periods' => array(
                'title' => __('Delivery Periods', 'whapow'),
                'type' => 'textarea',
                'description' => __('Weekday + Start and Endtime. one per line. (01-19:00-21:00', 'whapow'),
                'default' => 'mo 19:00 - 21:00',
                'desc_tip' => true,
            ),

            'order_periods' => array(
                'title' => __('Order Periods', 'whapow'),
                'type' => 'textarea',
                'description' => __('Order Periods. Format: Weekday | Time | Weekday | Time | Dayshift', 'whapow'),
                'default' => 'do 12:00 - mo 12:00 -> 1',
                'desc_tip' => true,
            ),

            'provider_mail' => array(
                'title' => __('Provider E-Mail', 'whapow'),
                'type' => 'text',
                'description' => __('E-Mail where orders are sent to', 'whapow'),
                'default' => 'a@b.de',
                'desc_tip' => true,
            ),

        );
    }

    public function validate_holidays_field($key, $value)
    {

        if ($this->shipment->holidays_from_string($value) === true) {
            return $value;
        } else {
            //not validated
            $this->add_error('Non Shipping Days field not in the the right format.');
            return $this->get_option($key);
        }
    }

    public function validate_delivery_periods_field($key, $value)
    {
        if ($this->shipment->delivery_periods_from_string($value) === true) {
            return $value;
        } else {
            //not validated
            $this->add_error('Delivery Periods field in wrong format.');
            return $this->get_option($key);
        }
        //matches a date
    }

    public function validate_order_periods_field($key, $value)
    {
        if ($this->shipment->order_periods_from_string($value) === true) {
            return $value;
        } else {
            //not validated
            $this->add_error('Order Periods field in wrong format.');
            return $this->get_option($key);
        }
    }

    /**
     * Get setting form fields for instances of this shipping method within zones.
     *
     * @return array
     */
    public function get_instance_form_fields()
    {
        return parent::get_instance_form_fields();
    }

    /**
     * See if shipping is available based on the package and cart.
     *
     * @param array $package Shipping package.
     * @return bool
     */
    public function is_available($package)
    {

        return apply_filters('woocommerce_shipping_' . $this->id . '_is_available', true, $package, $this);
    }

    /**
     * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping($package = array())
    {
        $total = WC()->cart->get_displayed_subtotal();
        if (WC()->cart->display_prices_including_tax()) {
            $total = round($total - (WC()->cart->get_discount_total() + WC()->cart->get_discount_tax()), wc_get_price_decimals());
        } else {
            $total = round($total - WC()->cart->get_discount_total(), wc_get_price_decimals());
        }

        $cost = $this->cost;

        if ($total >= $this->min_amount) {
            $cost = 0;   
        }

       

        $rate = array(
            'label' => $this->title,
            'cost' => $cost,
            'taxes' => false,
            'package' => $package,
        );

        $this->add_rate($rate);

    }
}
