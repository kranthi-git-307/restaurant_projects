<?php
if (!defined('ABSPATH')) exit;

class Pinaka_Pos_Shift_Cron_Activator 
{
    public static function activate() {
        $self = new self();
        $self->pinaka_schedule_shift_close_cron();
    }

    // public function pinaka_schedule_shift_close_cron() 
    // {
    //     $timestamp = wp_next_scheduled('pinaka_check_shift_status_cron');
    //     if ($timestamp) {
    //         wp_unschedule_event($timestamp, 'pinaka_check_shift_status_cron');
    //     }

    //     $shift_settings = get_option('pinaka_pos_shift_settings', [
    //         'shift_count' => 1,
    //         'total_hours' => 24,
    //         'shifts' => [['shift_id' => 1, 'name' => '', 'start' => '', 'end' => '']]
    //     ]);

    //     if (empty($shift_settings['shifts'])) {
    //         return;
    //     }

    //     $today = current_time('Y-m-d');
    //     $last_end_timestamp = null;

    //     foreach ($shift_settings['shifts'] as $shift) 
    //     {
    //         if (!empty($shift['end'])) 
    //         {
    //             $end_time = strtotime($today . ' ' . $shift['end']);
    //             if ($last_end_timestamp === null || $end_time > $last_end_timestamp) 
    //             {
    //                 $last_end_timestamp = $end_time;
    //             }
    //         }
    //     }
    //     if (!$last_end_timestamp) {
    //         return;
    //     }

    //     // $cron_time = $last_end_timestamp + 60;
    //     $cron_time = $last_end_timestamp + (10 * MINUTE_IN_SECONDS);
    //     $current_time = current_time('timestamp');

    //     if ($current_time >= $cron_time) {
    //         $cron_time = strtotime('+1 day', $cron_time);
    //     }
    //     // error_log(date('Y-m-d h:i:s',$cron_time));
    //     $gmt_offset = get_option('gmt_offset');
    //     $utc_cron_time = $cron_time - ($gmt_offset * HOUR_IN_SECONDS);
    //     wp_schedule_event($utc_cron_time, 'daily', 'pinaka_check_shift_status_cron');

    //     // $wp_timezone = wp_timezone();
    //     // $datetime_local = new DateTime('now', $wp_timezone);
    //     // $datetime_local->setTimestamp($cron_time);

    //     // // Convert to UTC timestamp
    //     // $datetime_local->setTimezone(new DateTimeZone('UTC'));
    //     // $utc_cron_time = $datetime_local->getTimestamp();

    //     // // Schedule daily cron at correct UTC time
    //     // wp_schedule_event($utc_cron_time, 'daily', 'pinaka_check_shift_status_cron');
    // }
    public function pinaka_schedule_shift_close_cron() {
        // Remove any previously scheduled events to prevent duplicates
        wp_clear_scheduled_hook('pinaka_check_shift_status_cron');

        // Fetch shift settings from the database
        $shift_settings = get_option('pinaka_pos_shift_settings', [
            'shift_count' => 1,
            'total_hours' => 24,
            'shifts' => [['shift_id' => 1, 'name' => '', 'start' => '', 'end' => '']]
        ]);

        // Validate the shifts data
        if (empty($shift_settings['shifts']) || !is_array($shift_settings['shifts'])) {
            return;
        }

        // Use WordPress timezone (DST-safe)
        $wp_timezone = wp_timezone();

        // Get today’s local date in WordPress timezone
        $current_date = wp_date('Y-m-d', null, $wp_timezone);
        $last_end_timestamp = null;

        // Find the latest shift end time
        foreach ($shift_settings['shifts'] as $shift) {
            $start_time_str = trim($shift['start'] ?? '');
            $end_time_str   = trim($shift['end'] ?? '');

            if (!$start_time_str || !$end_time_str) {
                continue;
            }

            try {
                // Create DateTime objects in WP timezone
                $start_datetime = new DateTime("$current_date $start_time_str", $wp_timezone);
                $end_datetime   = new DateTime("$current_date $end_time_str", $wp_timezone);

                // Handle overnight shifts (e.g., 19:00 → 00:30)
                if ($end_datetime < $start_datetime) {
                    $end_datetime->modify('+1 day');
                }

                // Get local timestamp for the shift end
                $end_timestamp = $end_datetime->getTimestamp();

                // Keep track of the latest shift end
                if ($last_end_timestamp === null || $end_timestamp > $last_end_timestamp) {
                    $last_end_timestamp = $end_timestamp;
                }

            } catch (Exception $e) {
                // error_log("[PINAKA CRON] Invalid shift time format: " . $e->getMessage());
                continue;
            }
        }

        // No valid shift end found
        if (!$last_end_timestamp) {
            return;
        }

        // Add 10 minutes after the last shift ends
        $cron_time = $last_end_timestamp + (10 * MINUTE_IN_SECONDS);

        // Get the current local timestamp
        $current_time = current_time('timestamp');

        // If the calculated cron time already passed, move it to the next day
        if ($current_time >= $cron_time) {
            $cron_time = strtotime('+1 day', $cron_time);
        }

        $datetime_local = new DateTime('@' . $cron_time);
        $datetime_local->setTimezone($wp_timezone);           // interpret as local WP time
        $datetime_local->setTimezone(new DateTimeZone('UTC')); // convert to UTC
        $utc_cron_time = $datetime_local->getTimestamp();

        wp_schedule_event($utc_cron_time, 'daily', 'pinaka_check_shift_status_cron');

        // if (defined('WP_DEBUG') && WP_DEBUG) {
        //     $local_time_str = wp_date('Y-m-d H:i:s', $cron_time, $wp_timezone);
        //     $utc_time_str   = gmdate('Y-m-d H:i:s', $utc_cron_time);
        //     error_log("[PINAKA CRON] Shift Close Scheduled -> Local: {$local_time_str}, UTC: {$utc_time_str}");
        // }
    }
}