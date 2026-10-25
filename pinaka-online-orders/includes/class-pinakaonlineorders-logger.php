<?php

if (!defined('ABSPATH')) exit;

class OrderOut_Logger
{
    public static function log($message)
    {
        error_log('[OrderOut] '.$message);
    }
}