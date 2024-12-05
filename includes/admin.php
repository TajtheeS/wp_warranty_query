<?php
// Thêm menu vào quản trị
function wp_warranty_query_menu() {
    add_menu_page(
        'Warranty Query',
        'Warranty Query',
        'manage_options',
        'wp-warranty-query',
        'wp_warranty_query_admin_page',
        'dashicons-search',
        6
    );
}
add_action('admin_menu', 'wp_warranty_query_menu');

// Tạo bảng trong cơ sở dữ liệu
function wp_warranty_query_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_query';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        customer_name varchar(255) NOT NULL,
        product_name varchar(255) NOT NULL,
        phone_number varchar(20) NOT NULL,
        warranty_until date NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Gọi hàm tạo bảng khi plugin được kích hoạt
register_activation_hook(__FILE__, 'wp_warranty_query_create_table');

// Trang quản trị plugin
function wp_warranty_query_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'warranty_query';

    // Gọi hàm tạo bảng khi truy cập trang quản trị plugin
    wp_warranty_query_create_table();

    // Xử lý form lọc
    $filter = '';
    if (isset($_POST['filter_name'])) {
        $filter = $_POST['filter_name'];
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE customer_name LIKE %s", '%' . $wpdb->esc_like($filter) . '%'));
    } else {
        // Lấy tất cả dữ liệu từ bảng nếu không có bộ lọc
        $results = $wpdb->get_results("SELECT * FROM $table_name");
    }

    ?>
    <div class="wrap">
        <h1>Warranty Query</h1>

        <!-- Nút mở form thêm mới -->
        <button id="add-warranty-button" class="button button-primary">Add New Warranty</button>

        <!-- Nút nhập dữ liệu -->
        <button id="import-warranty-button" class="button">Import CSV</button>
        <!-- Form nhập CSV -->
        <div id="import-warranty-modal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Import Warranty Data from CSV</h2>
                <form id="import-warranty-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="import_csv">
                    <input type="file" name="import_csv" accept=".csv" required>
                    <input type="submit" class="button button-primary" value="Import CSV">
                </form>
            </div>
        </div>
        <!-- Nút xuất dữ liệu -->
        <form method="post" action="">
            <input type="hidden" name="export_csv" value="1">
            <input type="submit" class="button" value="Export CSV">
        </form>

        <!-- Form lọc thông tin -->
        <h2>Filter Warranties</h2>
        <form method="post" id="filter-form">
            <input type="text" name="filter_name" placeholder="Enter customer name" value="<?php echo esc_attr($filter); ?>" style="width:100%; padding:8px; margin-bottom:20px; border:1px solid #ddd; border-radius:4px; box-sizing:border-box;">
            <?php submit_button('Filter'); ?>
        </form>

        <!-- Danh sách dữ liệu -->
        <h2>Existing Warranties</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Product Name</th>
                    <th>Phone Number</th>
                    <th>Warranty Until</th>
                    <th>Days Remaining</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row) : 
                    $days_remaining = (new DateTime($row->warranty_until))->diff(new DateTime())->days;
                    ?>
                    <tr>
                        <td><?php echo $row->id; ?></td>
                        <td><?php echo $row->customer_name; ?></td>
                        <td><?php echo $row->product_name; ?></td>
                        <td><?php echo $row->phone_number; ?></td>
                        <td><?php echo $row->warranty_until; ?></td>
                        <td><?php echo $days_remaining; ?></td>
                        <td>
                            <button class="edit-button button" data-id="<?php echo $row->id; ?>">Edit</button>
                            <button class="delete-button button" data-id="<?php echo $row->id; ?>">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- JavaScript để xử lý các pop-up -->
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Mở form thêm mới
            $('#add-warranty-button').click(function() {
                Swal.fire({
                    title: 'Add New Warranty',
                    html:
                        '<input id="swal-input1" class="swal2-input" placeholder="Customer Name">' +
                        '<input id="swal-input2" class="swal2-input" placeholder="Product Name">' +
                        '<input id="swal-input3" class="swal2-input" placeholder="Phone Number">' +
                        '<input id="swal-input4" class="swal2-input" type="date" placeholder="Warranty Until">',
                    showCancelButton: true,
                    confirmButtonText: 'Add Warranty',
                    preConfirm: () => {
                        return {
                            customer_name: $('#swal-input1').val(),
                            product_name: $('#swal-input2').val(),
                            phone_number: $('#swal-input3').val(),
                            warranty_until: $('#swal-input4').val()
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'add_warranty',
                                new_customer_name: result.value.customer_name,
                                new_product_name: result.value.product_name,
                                new_phone_number: result.value.phone_number,
                                new_warranty_until: result.value.warranty_until
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Added!', 'Warranty has been added.', 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error!', 'Failed to add warranty.', 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Mở form chỉnh sửa
            $('.edit-button').click(function() {
                var id = $(this).data('id');
                // Lấy dữ liệu bảo hành từ cơ sở dữ liệu (sử dụng AJAX)
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'get_warranty',
                        id: id
                    },
                    success: function(response) {
                        if (response.success) {
                            var warranty = response.data;
                            Swal.fire({
                                title: 'Edit Warranty',
                                html:
                                    '<input id="swal-edit-id" type="hidden" value="' + warranty.id + '">' +
                                    '<input id="swal-edit1" class="swal2-input" placeholder="Customer Name" value="' + warranty.customer_name + '">' +
                                    '<input id="swal-edit2" class="swal2-input" placeholder="Product Name" value="' + warranty.product_name + '">' +
                                    '<input id="swal-edit3" class="swal2-input" placeholder="Phone Number" value="' + warranty.phone_number + '">' +
                                    '<input id="swal-edit4" class="swal2-input" type="date" placeholder="Warranty Until" value="' + warranty.warranty_until + '">',
                                showCancelButton: true,
                                confirmButtonText: 'Update Warranty',
                                preConfirm: () => {
                                    return {
                                        id: $('#swal-edit-id').val(),
                                        customer_name: $('#swal-edit1').val(),
                                        product_name: $('#swal-edit2').val(),
                                        phone_number: $('#swal-edit3').val(),
                                        warranty_until: $('#swal-edit4').val()
                                    }
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $.ajax({
                                        url: ajaxurl,
                                        method: 'POST',
                                        data: {
                                            action: 'edit_warranty',
                                            edit_id: result.value.id,
                                            edit_customer_name: result.value.customer_name,
                                            edit_product_name: result.value.product_name,
                                            edit_phone_number: result.value.phone_number,
                                            edit_warranty_until: result.value.warranty_until
                                        },
                                        success: function(response) {
                                            if (response.success) {
                                                Swal.fire('Updated!', 'Warranty has been updated.', 'success').then(() => {
                                                    location.reload();
                                                });
                                            } else {
                                                Swal.fire('Error!', 'Failed to update warranty.', 'error');
                                            }
                                        }
                                    });
                                }
                            });
                        } else {
                            Swal.fire('Error!', 'Failed to fetch warranty data.', 'error');
                        }
                    }
                });
            });

            // Xử lý xóa bảo hành
            $('.delete-button').click(function() {
                var id = $(this).data('id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You will not be able to recover this warranty!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, keep it'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'delete_warranty',
                                id: id
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire('Deleted!', 'Warranty has been deleted.', 'success').then(() => {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire('Error!', 'Failed to delete warranty.', 'error');
                                }
                            }
                        });
                    }
                });
            });
        });
    </script>
    <?php
}
?>
