<?php
/*
Plugin Name: Monero Price Plugin
Description: Display real-time Monero price on a WordPress page and update MoneroWP rates.
*/

// Shortcode for displaying Monero prices
function monero_price_shortcode($atts) {
    $atts = shortcode_atts(array(
        'currency' => 'USD', // Default currency is USD
    ), $atts);

    $currency = strtoupper($atts['currency']);
    $price = get_monero_price_from_database($currency);

    return "<p>1 XMR = {$price} {$currency}</p>";
}

add_shortcode('monero_price', 'monero_price_shortcode');

// Schedule the task to run every 5 minutes
function schedule_monero_price_update() {
    if (!wp_next_scheduled('update_monero_price')) {
        wp_schedule_event(time(), 'five_minutes', 'update_monero_price');
    }
}

add_action('wp', 'schedule_monero_price_update');

// Hook to execute the task
add_action('update_monero_price', 'update_monero_wp_rates');

// Define the interval
function add_five_minutes_interval($schedules) {
    $schedules['five_minutes'] = array(
        'interval' => 5 * 60,
        'display'  => __('Every 5 Minutes'),
    );

    return $schedules;
}

add_filter('cron_schedules', 'add_five_minutes_interval');

// Function to get Monero price from the database
function get_monero_price_from_database($currency) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'monero_gateway_live_rates';
    $query = $wpdb->prepare("SELECT rate FROM $table_name WHERE currency = %s", $currency);
    $rate = $wpdb->get_var($query);

    if ($rate !== null) {
        return number_format($rate / 1e8, 5); // Format to 5 decimal places
    }

    return 'Error fetching Monero price from the database.';
}

// Function to update MoneroWP rates in the database
function update_monero_wp_rates() {
    global $wpdb;

    // Get Live Price in USD
    $currency = 'USD';
    $url = "https://api.coingecko.com/api/v3/simple/price?ids=monero&vs_currencies={$currency}";
    $response = wp_remote_get($url);

    if (is_array($response) && !is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['monero'][$currency])) {
            $table_name = $wpdb->prefix . 'monero_gateway_live_rates';
            $rate = intval($data['monero'][$currency] * 1e8);

            $query = $wpdb->prepare("INSERT INTO $table_name (currency, rate, updated) VALUES (%s, %d, NOW()) ON DUPLICATE KEY UPDATE rate=%d, updated=NOW()", array($currency, $rate, $rate));
            $result = $wpdb->query($query);

            if (!$result) {
                error_log("[ERROR] Unable to update MoneroWP rates.");
            }
        } else {
            error_log("[ERROR] Unable to fetch USD prices from coingecko.com.");
        }
    }
}
