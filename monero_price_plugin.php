<?php
/*
Plugin Name: Monero Price Plugin
Description: Display real-time Monero price on a WordPress page.
*/

function fetch_monero_price() {
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=monero&vs_currencies=usd';
    $response = wp_remote_get($url);

    if (is_array($response) && !is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['monero']['usd'])) {
            return $data['monero']['usd'];
        }
    }

    return 'Error fetching Monero price.';
}

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
