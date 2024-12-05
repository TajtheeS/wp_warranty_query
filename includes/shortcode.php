<?php
// Tạo shortcode
function wp_warranty_query_shortcode($atts) {
    ob_start();
    ?>
    <form id="warranty-query-form">
        <label for="phone_number">Enter Phone Number:</label>
        <input type="text" id="phone_number" name="phone_number">
        <input type="submit" value="Check Warranty">
    </form>
    <div id="warranty-result"></div>
    <script type="text/javascript">
        document.getElementById('warranty-query-form').addEventListener('submit', function(event) {
            event.preventDefault();
            var phoneNumber = document.getElementById('phone_number').value;
            var data = {
                'action': 'warranty_query',
                'phone_number': phoneNumber
            };
            jQuery.post(ajaxurl, data, function(response) {
                if (response.success) {
                    var resultHTML = '<ul>';
                    response.data.forEach(function(item) {
                        resultHTML += '<li>Customer: ' + item.customer_name + ', Product: ' + item.product_name + ', Warranty Until: ' + item.warranty_until + ', Days Remaining: ' + item.days_remaining + '</li>';
                    });
                    resultHTML += '</ul>';
                    document.getElementById('warranty-result').innerHTML = resultHTML;
                } else {
                    document.getElementById('warranty-result').innerText = response.data;
                }
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('warranty_query', 'wp_warranty_query_shortcode');

function wp_warranty_query_ajax_handler() {
    global $wpdb;
    $phone_number = $_POST['phone_number'];
    $table_name = $wpdb->prefix . 'warranty_query';
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE phone_number = %s", $phone_number));

    if ($results) {
        $data = array();
        foreach ($results as $result) {
            $days_remaining = (new DateTime($result->warranty_until))->diff(new DateTime())->days;
            $data[] = array(
                'customer_name' => $result->customer_name,
                'product_name' => $result->product_name,
                'warranty_until' => $result->warranty_until,
                'days_remaining' => $days_remaining
            );
        }
        wp_send_json_success($data);
    } else {
        wp_send_json_error('No warranty information found for this phone number.');
    }
}
add_action('wp_ajax_warranty_query', 'wp_warranty_query_ajax_handler');
add_action('wp_ajax_nopriv_warranty_query', 'wp_warranty_query_ajax_handler');
