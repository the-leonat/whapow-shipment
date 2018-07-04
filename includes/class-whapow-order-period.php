<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once "class-whapow-date-util.php";
require_once "class-whapow-period.php";
require_once "class-whapow-datetime-interval.php";



class Whapow_Order_Period extends Whapow_Period
{
    public function __construct()
    {
        parent::__construct();
    }

    public function from_string($order_periods_string)
    {
        $result = preg_match("/^\b(mo|di|mi|do|fr|sa|so)\b *(\d(?:\d)?):(\d\d) *- *\b(mo|di|mi|do|fr|sa|so)\b *(\d(?:\d)?):(\d\d) *-> *(\d)\s*$/", $order_periods_string, $matches);

        if ($result === 0) {
            return false;
        } else {
            $weekday_from = $this->weekday_indexes[$matches[1]];
            $hours_from = $matches[2];
            $minutes_from = $matches[3];
            $weekday_to = $this->weekday_indexes[$matches[4]];
            $hours_to = $matches[5];
            $minutes_to = $matches[6];
            $this->set($weekday_from, $hours_from, $minutes_from, $weekday_to, $hours_to, $minutes_to);
            $this->day_shift = intval($matches[7]);
        }

        return true;
    }

    public function to_interval($datetime) {
        return new Whapow_Order_Interval(
            $this->get_datetime_from($datetime),
            $this->get_datetime_to($datetime),
            $this->day_shift
        );
    }


    public function __toString() {
        return "Order Period: " . $this->weekday_names[$this->weekday_from] . " " . $this->interval_from->format("%H:%I") . " - " .  $this->weekday_names[$this->weekday_to]  . " " . $this->interval_to->format("%H:%I");
    }

}
