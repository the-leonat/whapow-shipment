<?php

require_once "class-whapow-date-util.php";

class Whapow_Datetime_Interval
{
    public function __construct($datetime_from, $datetime_to)
    {
        $this->datetime_from = $datetime_from;
        $this->datetime_to = $datetime_to;
        $this->weekday_names = array(0 => "Montag", 1 => "Dienstag", 2 => "Mittwoch", 3 => "Donnerstag", 4 => "Freitag", 5 => "Samstag", 6 => "Sonntag");
    }

    public function intersects($datetime)
    {
        if ($this->datetime_from < $datetime && $datetime < $this->datetime_to) {
            // dlog("matching zones:");
            // dlog("from " . $datetime_from->format('Y-m-d H:i:s'));
            // dlog("to " . $datetime_to->format('Y-m-d H:i:s'));
            return true;
        }

        return false;
    }

    public function intersects_day($date)
    {

    }

    public function after($datetime)
    {

        if ($this->datetime_from >= $datetime) {
            return true;
        }

        return false;
    }

    public function to_readable_string()
    {
        $weekday = Whapow_Date_Util::get_weekday($this->datetime_from);
        $wname = $this->weekday_names[$weekday];
        return $wname . ", der " . $this->datetime_from->format('d.m') . " zwischen " . $this->datetime_from->format('H:i') . " - " . $this->datetime_to->format('H:i') . " Uhr";
    }

}

class Whapow_Order_Interval extends Whapow_Datetime_Interval
{
    public function __construct($datetime_from, $datetime_to, $day_shift)
    {
        parent::__construct($datetime_from, $datetime_to);

        $this->day_shift = $day_shift;
    }

    public function get_closest_delivery_interval($delivery_periods, $nth = 0)
    {
        $datetime_delivery = clone $this->datetime_to;
        $nth_closest = $nth;

        // DANGEROUS CHANGE LATER
        $datetime_delivery->setTime(0, 0);
        $datetime_delivery->add(DateInterval::createFromDateString($this->day_shift . " days"));

        // echo $datetime_delivery;
        $delivery_interval = null;

        // for safety
        $counter = 0;
        while ($counter < 60) {
            foreach ($delivery_periods as $delivery_period) {
                $delivery_interval = $delivery_period->to_interval($datetime_delivery);
                if ($delivery_interval->after($datetime_delivery) === true) {
                    if ($nth_closest === 0) {
                        return $delivery_interval;
                    } else {
                        $nth_closest--;

                        // set new startdate to last possible one from this week
                        //$datetime_delivery = clone $delivery_interval->datetime_to;
                        //$datetime_delivery->setTime(0, 0);
                    }
                }
            }

            // forward to next week
            $weekday = Whapow_Date_Util::get_weekday($datetime_delivery);
            $offset = 7 - $weekday;
            $datetime_delivery->add(DateInterval::createFromDateString($offset . " day"));
            $datetime_delivery->setTime(0, 0);
            //echo $datetime_delivery->format("_d-m-y H:i_");

            $counter++;
        }
        throw new Exception('No delivery found.');
        return null;
    }

    public function __toString()
    {
        return "Order Interval: " . $this->datetime_from->format('d-m-y H:i') . " - " . $this->datetime_to->format('d-m-y H:i');
    }
}

class Whapow_Delivery_Interval extends Whapow_Datetime_Interval
{
    public function __construct($datetime_from, $datetime_to)
    {
        parent::__construct($datetime_from, $datetime_to);
    }

    public function __toString()
    {
        return "Delivery Interval: " . $this->datetime_from->format('d-m-y H:i') . " - " . $this->datetime_to->format('H:i');
    }
}
