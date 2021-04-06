<?php
add_action( 'wp_ajax_nopriv_ggn_send_checkout_form', 'ggn_send_checkout_form' );
add_action( 'wp_ajax_ggn_send_checkout_form', 'ggn_send_checkout_form' );
function ggn_send_checkout_form(){
    $order = [
        'order_id' => $_POST['order_id'],
        'order_unique_id' => $_POST['order_unique_id']
    ];
    $ggn_send_to_mobilpay = ggn_send_to_mobilpay($order, CHECKOUT_PAGE_PERMALINK);

    ob_start();
    ?>
        <form action="<?php echo $ggn_send_to_mobilpay['payment_url']; ?>" method="post" style="display: none;">
            <input type="hidden" name="data" value="<?php echo $ggn_send_to_mobilpay['data']; ?>" />
            <input type="hidden" name="env_key" value="<?php echo $ggn_send_to_mobilpay['env_key']; ?>" />
        </form>
    <?php 
    $return_data = [
        'content' => ob_get_clean(),
        'method' => 'POST',
        'success' => true,
        'message' => __('Form successfully submitted.','goicc')
    ];
    wp_send_json($return_data);
}