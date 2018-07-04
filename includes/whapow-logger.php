<?php 
if (!defined('ABSPATH')) {
    exit;
}

function dlog($string)
{
    $logger = wc_get_logger();
    $logger->debug($string, array("source", "whapow-shipment"));
}