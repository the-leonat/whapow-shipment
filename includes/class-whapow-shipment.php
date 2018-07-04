<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once "class-whapow-delivery-period.php";
require_once "class-whapow-order-period.php";
require_once "whapow-logger.php";
require_once "class-whapow-date-util.php";
require_once "class-whapow-datetime-interval.php";

class Whapow_Shipment
{
    public function __contruct()
    {
        $this->order_periods = array();
        $this->delivery_periods = array();
        $this->holidays = array();

        date_default_timezone_set('Europe/Berlin');
    }

    // PARSING -----

    public function holidays_from_string($holidays_string): bool
    {

        $datestring_array = explode("\n", $holidays_string);
        $validation = true;
        foreach ($datestring_array as &$datestring) {
            //matches a date

            $datestring = trim($datestring);

            $holiday_date = DateTime::createFromFormat('d.m.y', $datestring, new DateTimeZone('Europe/Berlin'));
            $holiday_date = $holiday_date->setTime(0,0);

            if ($holiday_date === false) {
                //dlog("Holiday Validation Failed with String: " . $datestring);
                $validation = false;
                break;
            } else {
                $this->holidays[] = $holiday_date;
            }
        }

        return $validation;
    }

    public function delivery_periods_from_string($delivery_periods_string): bool
    {
        $string_array = explode("\n", $delivery_periods_string);
        $validation = true;
        foreach ($string_array as &$periodstring) {
            $delivery_period = new Whapow_Delivery_Period();
            if ($delivery_period->from_string($periodstring) === false) {
                $validation = false;
                //dlog("Delivery Period Validation Failed with String: " . $periodstring);
                break;
            } else {
                $this->delivery_periods[] = $delivery_period;
            }
        }
        return $validation;
    }

    public function order_periods_from_string($order_periods_string): bool
    {
        $string_array = explode("\n", $order_periods_string);
        $validation = true;
        foreach ($string_array as &$periodstring) {
            $order_period = new Whapow_Order_Period();
            if ($order_period->from_string($periodstring) === false) {
                $validation = false;
                //dlog("Order Period Validation Failed with String: " . $periodstring);
                break;
            } else {
                $this->order_periods[] = $order_period;
            }
        }

        return $validation;
    }

    // GETTER / SETTER

    public function is_holiday($datetime): bool
    {
        foreach ($this->holidays as &$holiday) {
            //echo "inteval: " . $interval;
            if ($holiday->format("d-m-y") === $datetime->format("d-m-y") ) {
                return true;
            }
        }
        return false;
    }

    public function get_closest_business_day($datetime) {
        $counter == 0;
        $date = clone $datetime;
        while($this->is_holiday($date)) {
            
            $date->add(DateInterval::createFromDateString("1 day"));

            if($counter > 15) return null;
            $counter++;
        }

        return $date;  
    }

    public function get_order_interval($datetime)
    {
        $datetime = $this->get_closest_business_day($datetime);

        foreach ($this->order_periods as $order_period) {
            $order_interval = $order_period->to_interval($datetime);
            if ($order_interval->intersects($datetime) === true) {
                return $order_interval;
            }

        }
        return null;
    }

    public function get_closest_delivery_interval($datetime, $nth_closest = 0)
    {
        $order_interval = $this->get_order_interval($datetime);
        if ($order_interval !== null) {
            $delivery_interval = $order_interval->get_closest_delivery_interval($this->delivery_periods, $nth_closest);
            $counter = 0;
            while($this->is_holiday($delivery_interval->datetime_from)) {
                $nth_closest++;
                $delivery_interval = $order_interval->get_closest_delivery_interval($this->delivery_periods, $nth_closest);
                //echo $delivery_interval;
                if($counter > 60) {
                    throw new Exception('No delivery found.');
                }
            }
            return $delivery_interval;
        } else {
            return null;
        }

    }
}
