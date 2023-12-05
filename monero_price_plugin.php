<?php
/*
Plugin Name: Monero Price Plugin
Description: Display real-time Monero prices on a WordPress page.
*/

function fetch_monero_price($currency = 'USD') {
    $currencies = array(
        'AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN',
        'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL',
        'BSD', 'BTC', 'BTN', 'BWP', 'BYN', 'BYR', 'BZD', 'CAD', 'CDF', 'CHF',
        'CLF', 'CLP', 'CNY', 'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF',
        'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP',
        'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL',
        'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK',
        'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW',
        'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LTL', 'LVL',
        'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MUR',
        'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR',
        'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR',
        'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG', 'SEK', 'SGD',
        'SHP', 'SLL', 'SOS', 'SRD', 'STD', 'SVC', 'SYP', 'SZL', 'THB', 'TJS',
        'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD',
        'UYU', 'UZS', 'VEF', 'VND', 'VUV', 'WST', 'XAF', 'XAG', 'XAU', 'XCD',
        'XDR', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMK', 'ZMW', 'ZWL'
    );

    $ids = implode(',', array_map('strtolower', $currencies));
    $url = 'https://api.coingecko.com/api/v3/simple/price?ids=monero&vs_currencies=' . $ids;
    
    $response = wp_remote_get($url);

    if (is_array($response) && !is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['monero'][$currency])) {
            $price = $data['monero'][$currency];
            return $price;
        }
    }

    return 'Error fetching Monero price.';
}

function monero_price_shortcode($atts) {
    $atts = shortcode_atts(array(
        'currency' => 'USD',
    ), $atts);

    $price = fetch_monero_price(strtoupper($atts['currency']));

    if (!is_numeric($price)) {
        return "<p>Error: $price</p>";
    }

    $output = "1 XMR = $price {$atts['currency']}";

    return $output;
}

add_shortcode('monero-price', 'monero_price_shortcode');

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
