<?php
class Pinaka_Pos_Shift_Cron_Deactivator {
	public static function deactivate() {
		self::deactivate_shift_cron_tb();
	}
    // public static function deactivate_shift_cron_tb() {
    //     $timestamp = wp_next_scheduled('pinaka_check_shift_status_cron');
    //     if ($timestamp) wp_unschedule_event($timestamp, 'pinaka_check_shift_status_cron');
    // }
    public static function deactivate_shift_cron_tb() {
        $timestamp = wp_next_scheduled('pinaka_check_shift_status_cron');
        
        if ($timestamp !== false) {
            wp_unschedule_event($timestamp, 'pinaka_check_shift_status_cron');
        }
    }
}
