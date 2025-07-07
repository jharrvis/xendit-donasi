<?php
/**
 * Kelas untuk menangani cron jobs
 */
class WP_Xendit_Cron_Handler {

    /**
     * Initialize cron hooks
     */
    public static function init() {
        add_action('wp_xendit_update_exchange_rates', array(__CLASS__, 'update_exchange_rates_cron'));
        add_filter('cron_schedules', array(__CLASS__, 'add_custom_cron_schedules'));
        
        // Add AJAX handlers for cron management
        add_action('wp_ajax_reschedule_exchange_cron', array(__CLASS__, 'reschedule_cron'));
        add_action('wp_ajax_clear_exchange_cron', array(__CLASS__, 'clear_cron'));
        add_action('wp_ajax_check_rate_updates', array(__CLASS__, 'check_rate_updates'));
    }

    /**
     * Add custom cron schedules
     */
    public static function add_custom_cron_schedules($schedules) {
        $schedules['sixhourly'] = array(
            'interval' => 6 * HOUR_IN_SECONDS,
            'display' => 'Every 6 Hours'
        );
        
        return $schedules;
    }

    /**
     * Cron job callback for updating exchange rates
     */
    public static function update_exchange_rates_cron() {
        // Check if auto-update is enabled
        if (!get_option('wp_xendit_donation_auto_update_rate', 1)) {
            return;
        }

        try {
            $rate = WP_Xendit_Exchange_Rate_API::update_exchange_rates();
            
            if ($rate) {
                error_log('WP Xendit: Exchange rate updated via cron. New rate: 1 USD = ' . $rate . ' IDR');
            } else {
                error_log('WP Xendit: Failed to update exchange rate via cron');
            }
        } catch (Exception $e) {
            error_log('WP Xendit Cron Error: ' . $e->getMessage());
        }
    }

    /**
     * Reschedule cron job
     */
    public static function reschedule_cron() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_xendit_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        // Clear existing cron
        wp_clear_scheduled_hook('wp_xendit_update_exchange_rates');

        // Get interval setting
        $interval = get_option('wp_xendit_donation_update_interval', 6);
        $schedule = 'hourly';
        
        switch ($interval) {
            case 1:
                $schedule = 'hourly';
                break;
            case 6:
                $schedule = 'sixhourly';
                break;
            case 12:
                $schedule = 'twicedaily';
                break;
            case 24:
                $schedule = 'daily';
                break;
        }

        // Schedule new cron
        $scheduled = wp_schedule_event(time(), $schedule, 'wp_xendit_update_exchange_rates');

        if ($scheduled !== false) {
            wp_send_json_success(array('message' => 'Cron rescheduled successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to reschedule cron'));
        }
    }

    /**
     * Clear cron job
     */
    public static function clear_cron() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_xendit_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }

        $cleared = wp_clear_scheduled_hook('wp_xendit_update_exchange_rates');

        if ($cleared !== false) {
            wp_send_json_success(array('message' => 'Cron cleared successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to clear cron'));
        }
    }

    /**
     * Check for rate updates
     */
    public static function check_rate_updates() {
        if (!wp_verify_nonce($_POST['nonce'], 'wp_xendit_admin_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        $current_rate = WP_Xendit_Currency_Converter::get_exchange_rate();
        $last_update = get_option('wp_xendit_last_rate_update', '');
        
        // Check if rate was updated in the last minute
        $updated_recently = false;
        if ($last_update) {
            $last_update_time = strtotime($last_update);
            $updated_recently = (time() - $last_update_time) < 60; // Less than 1 minute ago
        }

        wp_send_json_success(array(
            'updated' => $updated_recently,
            'rate' => $current_rate,
            'formatted' => WP_Xendit_Currency_Converter::format_currency($current_rate, 'IDR'),
            'last_update' => $last_update
        ));
    }
}

// Initialize cron handler
WP_Xendit_Cron_Handler::init();