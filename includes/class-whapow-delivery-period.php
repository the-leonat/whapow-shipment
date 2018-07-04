<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once "class-whapow-period.php";

class Whapow_Delivery_Period extends Whapow_Period
{
    public function __construct()
    {
        parent::__construct();
    }

    public function from_string($delivery_period_string)
    {

        $result = preg_match("/^\b(mo|di|mi|do|fr|sa|so)\b *(\d(?:\d))?:(\d\d) *- *(\d(?:\d)?):(\d\d)\s*$/", $delivery_period_string, $matches);

        if ($result === 0) {
            return false;
        } else {
            $weekday = $this->weekday_indexes[$matches[1]];
            $hours_from = $matches[2];
            $minutes_from = $matches[3];
            $hours_to = $matches[4];
            $minutes_to = $matches[5];

            $this->set($weekday, $hours_from, $minutes_from, $weekday, $hours_to, $minutes_to);
        }

        return true;
    }

    public function to_interval($datetime)
    {
        return new Whapow_Delivery_Interval(
            $this->get_datetime_from($datetime),
            $this->get_datetime_to($datetime)
        );
    }

    public function __toString()
    {
        return "Delivery Period: " . $this->weekday_names[$this->weekday_from] . " " . $this->interval_from->format("%H:%I") . " - " . $this->interval_to->format("%H:%I");
    }
}
