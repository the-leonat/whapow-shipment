<?php

require_once "class-whapow-date-util.php";

class Whapow_Period {
    public function __construct()
    {
        $this->weekday_indexes = array("mo" => 0, "di" => 1, "mi" => 2, "do" => 3, "fr" => 4, "sa" => 5, "so" => 6);
        $this->weekday_names = array(0 => "Montag", 1 => "Dienstag", 2 => "Mittwoch", 3 => "Donnerstag", 4 => "Freitag", 5 => "Samstag", 6 => "Sonntag");

        date_default_timezone_set('Europe/Berlin');
    }

    public function set($weekday_from, $hours_from, $minutes_from, $weekday_to, $hours_to, $minutes_to) {
        $this->interval_from = new DateInterval("PT" . $hours_from . "H" . $minutes_from . "M");
        $this->interval_to = new DateInterval("PT" . $hours_to . "H" . $minutes_to . "M");
        $this->weekday_from = $weekday_from;
        $this->weekday_to = $weekday_to;
    }
    
    public function get_datetime_from($datetime_to_check = null)
    {
        if ($datetime_to_check === null) {
            $datetime_to_check = new DateTime("NOW");
        }

        $weekday = Whapow_Date_Util::get_weekday($datetime_to_check);
        $weekday_offset_start = $this->weekday_from - $weekday;

        $datetime_from = clone $datetime_to_check;
        $datetime_from->setTime(0, 0);
        $datetime_from->add($this->interval_from);
        $datetime_from->add(DateInterval::createFromDateString($weekday_offset_start . " day"));

        return $datetime_from;
    }

    public function get_datetime_to($datetime_to_check = null)
    {
        if ($datetime_to_check === null) {
            $datetime_to_check = new DateTime("NOW");
        }

        $weekday = Whapow_Date_Util::get_weekday($datetime_to_check);
        $weekday_offset_end = $this->weekday_to - $weekday;

        $datetime_to = clone $datetime_to_check;
        $datetime_to->setTime(0, 0);
        $datetime_to->add($this->interval_to);
        $datetime_to->add(DateInterval::createFromDateString($weekday_offset_end . " day"));
        // if periods is spanning over a week + add a week to the date_to object
        if ($this->weekday_from > $this->weekday_to) {
            $datetime_to->add(DateInterval::createFromDateString("1 week"));
        }

        return $datetime_to;
    }
}