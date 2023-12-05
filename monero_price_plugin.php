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
    return "<p id='monero-price'>Current Monero Price: \${$price}</p>";
}

add_shortcode('monero_price', 'monero_price_shortcode');

// Schedule the task to run every 15 seconds
function schedule_monero_price_update() {
    if (!wp_next_scheduled('update_monero_price')) {
        wp_schedule_event(time(), '15_seconds', 'update_monero_price');
    }
}

add_action('wp', 'schedule_monero_price_update');

// Hook to execute the task
add_action('update_monero_price', 'fetch_monero_price');

// Define the interval for 15 seconds
function add_fifteen_seconds_interval($schedules) {
    $schedules['15_seconds'] = array(
        'interval' => 15,
        'display'  => __('Every 15 Seconds'),
    );

    return $schedules;
}

add_filter('cron_schedules', 'add_fifteen_seconds_interval');

// Enqueue script for AJAX
function monero_price_ajax_script() {
    ?>
    <script>
        jQuery(document).ready(function($) {
            function updateMoneroPrice() {
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        'action': 'update_monero_price_ajax'
                    },
                    success: function(response) {
                        $('#monero-price').html(response);
                    }
                });
            }

            setInterval(updateMoneroPrice, 15000); // 15 seconds interval
        });
    </script>
    <?php
}

add_action('wp_footer', 'monero_price_ajax_script');

// AJAX handler for updating Monero price
function update_monero_price_ajax_handler() {
    echo fetch_monero_price();
    exit();
}

add_action('wp_ajax_update_monero_price_ajax', 'update_monero_price_ajax_handler');
