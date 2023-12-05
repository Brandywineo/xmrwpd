<?php
/*
Plugin Name: Monero Price Plugin
Description: Display real-time Monero price on a WordPress page.
*/

// Function to fetch Monero price from Coingecko
function fetch_monero_price() {
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=monero&vs_currencies=usd';
    $response = wp_remote_get($url);

    if (is_array($response) && !is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['monero']['usd'])) {
            // Write Monero price to the database
            write_monero_price_to_database('usd', intval($data['monero']['usd'] * 1e8));

            return $data['monero']['usd'];
        }
    }

    return 'Error fetching Monero price.';
}

// Function to write Monero prices to the database
function write_monero_price_to_database($currency, $rate) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'monero_gateway_live_rates';

    $query = $wpdb->prepare("INSERT INTO $table_name (currency, rate, updated) VALUES (%s, %d, NOW()) ON DUPLICATE KEY UPDATE rate=%d, updated=NOW()", array($currency, $rate, $rate));

    $result = $wpdb->query($query);

    if ($result === false) {
        // Log an error if writing to the database fails
        error_log("[ERROR] Unable to write Monero price to the database.");
    }
}

// Shortcode to display Monero price
function monero_price_shortcode() {
    $price = fetch_monero_price();
    return "<p>Current Monero Price: \${$price}</p>";
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
add_action('update_monero_price', 'fetch_monero_price');

// Define the interval
function add_five_minutes_interval($schedules) {
    $schedules['five_minutes'] = array(
        'interval' => 5 * 60,
        'display'  => __('Every 5 Minutes'),
    );

    return $schedules;
}

add_filter('cron_schedules', 'add_five_minutes_interval');
