<?php
/*
* Plugin Name: WP Warranty Query
* Plugin URI: https://osakannn.com/
* Description: Plugin to manage and query product warranty period. Base on AI.
* Version: 1.0
* Requires at least: 5.0
* Requires PHP: 7.1
* Author: imBot
* Author URI: https://osakannn.com/
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
* Text Domain: wp-warranty-query
*/

// Bao gồm các tệp cần thiết
include_once plugin_dir_path(__FILE__) . 'includes/admin.php';
include_once plugin_dir_path(__FILE__) . 'includes/css_js.php';
include_once plugin_dir_path(__FILE__) . 'includes/shortcode.php';

// Tải thư viện SweetAlert
function enqueue_sweetalert() {
    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array('jquery'), null, true);
}
add_action('admin_enqueue_scripts', 'enqueue_sweetalert');

// Xử lý AJAX cho thêm mới bảo hành
function wp_warranty_query_add_warranty() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_query';
    $wpdb->insert(
        $table_name,
        array(
            'customer_name' => $_POST['new_customer_name'],
            'product_name' => $_POST['new_product_name'],
            'phone_number' => $_POST['new_phone_number'],
            'warranty_until' => $_POST['new_warranty_until']
        )
    );
    wp_send_json_success();
}
add_action('wp_ajax_add_warranty', 'wp_warranty_query_add_warranty');

// Xử lý AJAX cho lấy dữ liệu bảo hành
function wp_warranty_query_get_warranty() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_query';
    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $_POST['id']));
    if ($result) {
        wp_send_json_success($result);
    } else {
        wp_send_json_error();
    }
}
add_action('wp_ajax_get_warranty', 'wp_warranty_query_get_warranty');

// Xử lý AJAX cho chỉnh sửa bảo hành
function wp_warranty_query_edit_warranty() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_query';
    $wpdb->update(
        $table_name,
        array(
            'customer_name' => $_POST['edit_customer_name'],
            'product_name' => $_POST['edit_product_name'],
            'phone_number' => $_POST['edit_phone_number'],
            'warranty_until' => $_POST['edit_warranty_until']
        ),
        array('id' => $_POST['edit_id'])
    );
    wp_send_json_success();
}
add_action('wp_ajax_edit_warranty', 'wp_warranty_query_edit_warranty');

// Xử lý AJAX cho xóa bảo hành
function wp_warranty_query_delete_warranty() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_query';
    $wpdb->delete($table_name, array('id' => $_POST['id']));
    wp_send_json_success();
}
add_action('wp_ajax_delete_warranty', 'wp_warranty_query_delete_warranty');

// Xử lý xuất CSV
if (isset($_POST['export_csv'])) {
    add_action('admin_init', 'wp_warranty_query_export_csv');
}

function wp_warranty_query_export_csv() {
    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_query';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    $filename = "warranty_data_" . date('Ymd') . ".csv";

    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: text/csv; charset=UTF-8");

    $file = fopen('php://output', 'w');

    $header = array("ID", "Customer Name", "Product Name", "Phone Number", "Warranty Until");
    fputcsv($file, $header);

    foreach ($results as $row) {
        fputcsv($file, (array)$row);
    }

    fclose($file);
    exit;
}

// Xử lý nhập CSV
function wp_warranty_query_import_csv() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_FILES['import_csv']['tmp_name'])) {
        $file = fopen($_FILES['import_csv']['tmp_name'], 'r');

        global $wpdb;
        $table_name = $wpdb->prefix . 'warranty_query';

        // Bỏ qua dòng tiêu đề
        fgetcsv($file);

        while ($row = fgetcsv($file)) {
            $wpdb->insert(
                $table_name,
                array(
                    'customer_name' => $row[1],
                    'product_name' => $row[2],
                    'phone_number' => $row[3],
                    'warranty_until' => $row[4]
                )
            );
        }

        fclose($file);
    }
}
add_action('admin_post_import_csv', 'wp_warranty_query_import_csv');
