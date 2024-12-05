// JavaScript cho plugin (nếu cần thêm)
console.log('WP Warranty Query script loaded');
// JavaScript để xử lý mở và đóng modal nhập CSV
$('#import-warranty-button').click(function() {
    $('#import-warranty-modal').show();
});

$('.close').click(function() {
    $('.modal').hide();
});
