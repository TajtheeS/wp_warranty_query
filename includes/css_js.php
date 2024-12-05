<?php
// Đăng ký và tải CSS và JS
function wp_warranty_query_scripts() {
    wp_enqueue_style('wp-warranty-query-css', plugins_url('css/style.css', __FILE__));
    wp_enqueue_script('wp-warranty-query-js', plugins_url('js/script.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('wp-warranty-query-js', 'ajaxurl', admin_url('admin-ajax.php'));
}
add_action('admin_enqueue_scripts', 'wp_warranty_query_scripts');
add_action('wp_enqueue_scripts', 'wp_warranty_query_scripts');
