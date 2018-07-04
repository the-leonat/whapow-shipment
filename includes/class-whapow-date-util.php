<?php

class Whapow_Date_Util
{

    public static function is_future($datetime, $datetime_future): bool
    {
        return $datetime_future > $datetime;
    }

    public static function is_past($datetime, $datetime_past): bool
    {
        return $datetime_past < $datetime;
    }

    public static function get_weekday($datetime): int
    {
        $day = $datetime->format('N');

        return intval($day) - 1;
    }

}
