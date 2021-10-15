<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function ggn_add_payment_method($payment_method){
    $position = 0; // @todo: de adaugat optiune in admin pentru schimbarea pozitiei
    global $default_title, $default_description;
    $options = get_option( 'ggn_settings' );
    $new_method = [
        'card_netopia' => [
            'title' => ($options['ggn_title']) ? $options['ggn_title'] : $default_title,
            'desc' => ($options['ggn_description']) ? $options['ggn_description'] : $default_description,
        ]
    ];
    
    $payment_method_return = array_slice($payment_method, 0, $position, true) +
        $new_method +
        array_slice($payment_method, $position, NULL, true);

    return $payment_method_return;
}
add_filter('goicc_payment_method', 'ggn_add_payment_method');

function ggn_send_to_mobilpay($order, $checkout_page_permalink){
    //include the main library
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Request/Abstract.php';
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Request/Card.php';
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Invoice.php';
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Address.php';

    $order_data = get_post($order['order_id'], 'ARRAY_A');
    $order_meta = get_post_meta($order['order_id']);
    $options = get_option( 'ggn_settings' );
    $order_id_full = $order['order_id'] . '-' . $order['order_unique_id'];

    if ($options['ggn_test_mode']){
        $public_key_file_name = 'sandbox.'.$options['ggn_signature'].'.public.cer';
    }else{
        $public_key_file_name = 'live.'.$options['ggn_signature'].'.public.cer';
    }
    $public_key_file = WP_CONTENT_DIR . '/ggn/certificates/' . $public_key_file_name;

    $payment_url = ($options['ggn_test_mode']) ? 'http://sandboxsecure.mobilpay.ro/' : 'https://secure.mobilpay.ro/';

    if ( defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE !== 'ro' )
    {
        $payment_url .= 'en/';
    }

    #below is where mobilPay will send the payment result. This URL will always be called first; mandatory
    $confirm_url = $checkout_page_permalink . '?status=ggnConfirm';
    #below is where mobilPay redirects the client once the payment process is finished. Not to be mistaken for a "successURL" nor "cancelURL"; mandatory
    // $return_url = $checkout_page_permalink . '?status=success&order_id=' . $order_id_full;
    $return_url = $checkout_page_permalink . '?status=success';

    try {
        $mobilpay_request = new Mobilpay_Payment_Request_Card();
        $mobilpay_request->signature = $options['ggn_signature'];
        $mobilpay_request->orderId = $order_id_full;
        $mobilpay_request->confirmUrl = $confirm_url;
        $mobilpay_request->returnUrl = $return_url;

        $mobilpay_request->invoice = new Mobilpay_Payment_Invoice();
        $mobilpay_request->invoice->currency = 'RON';
        $mobilpay_request->invoice->amount = $order_meta['goicc_total_price'][0];
        $mobilpay_request->invoice->details	= $order_data['post_title'];

        $billing_address = new Mobilpay_Payment_Address();
        $billing_address->type = 'person'; //should be "person"
        $billing_address->firstName	 = $order_meta['goicc_order_first_name'][0];
        $billing_address->lastName	 = $order_meta['goicc_order_last_name'][0];
        $billing_address->address	 = $order_meta['address'][0];
        $billing_address->email		 = $order_meta['goicc_order_email'][0];
        $billing_address->mobilePhone = $order_meta['phone'][0];
        $mobilpay_request->invoice->setBillingAddress($billing_address);
        $mobilpay_request->invoice->setShippingAddress($billing_address);

        $mobilpay_request->encrypt($public_key_file);
    } catch(Exception $e) {}

    if(!($e instanceof Exception)) {
        $response = [
            'method' => 'ggn',
            'payment_url' => $payment_url,
            'env_key' => $mobilpay_request->getEnvKey(),
            'data' => $mobilpay_request->getEncData()
        ];
    } else {
        $response = [
            'method' => 'error',
            'error' => $e->getMessage()
        ];
    }
    return $response;
}

function ggn_send_checkout_form_edit($return_data, $payment_method, $order, $checkout_page_permalink, $user_id){
    if($payment_method !== 'card_netopia') return $return_data;

    $ggn_send_to_mobilpay = ggn_send_to_mobilpay($order, $checkout_page_permalink);

    ob_start();
    ?>
        <form action="<?php echo $ggn_send_to_mobilpay['payment_url']; ?>" method="post" style="display: none;">
            <input type="hidden" name="data" value="<?php echo $ggn_send_to_mobilpay['data']; ?>" />
            <input type="hidden" name="env_key" value="<?php echo $ggn_send_to_mobilpay['env_key']; ?>" />
        </form>
    <?php 
    $return_data['content'] = ob_get_clean();
    $return_data['method'] = 'POST';
    return $return_data;
}
add_filter('goicc_send_checkout_form', 'ggn_send_checkout_form_edit', 10, 5);

function ggn_after_submit_return(){
    if( $_GET['status'] === 'ggnConfirm' )
    {
        ggn_confirm_action();
        die();
    }
    // elseif( $_GET['status'] === 'ggnReturn' )
    // {
    //     ggn_return_action();
    // }
}
add_action('goicc_after_submit_return', 'ggn_after_submit_return', 10, 3);

function ggn_billing_history_buttons($order_id, $order_meta){ // @todo: de adaugat functionalitate pe buton
    $order_status = $order_meta['goicc_order_status'][0];
    if(in_array($order_status, array('pending', 'on-hold', 'failed'))){ ?>
        <a onclick="ggn_click_pay_btn(this)" data-id="<?php echo $order_id; ?>" data-unique_id="<?php echo $order_meta['goicc_order_unique_id'][0]; ?>" class="btn btn-sm btn-primary ggn-pay-btn">
            <?php _e('Pay now','goic-gateway-netopia'); ?>
        </a>
    <?php }
}
add_action('goicc_billing_history_buttons', 'ggn_billing_history_buttons', 10, 2);
add_action('goicc_order_details_buttons', 'ggn_billing_history_buttons', 10, 2);

function ggn_confirm_action(){
    //include the main library
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Request/Abstract.php';
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Request/Card.php';
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Request/Notify.php';
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Invoice.php';
    require_once plugin_dir_path(__FILE__) . 'Mobilpay/Payment/Address.php';

    $errorCode 		= 0;
    $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
    $errorMessage	= '';

    if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0)
    {
        if(isset($_POST['env_key']) && isset($_POST['data']))
        {
            
            $options = get_option( 'ggn_settings' );

            if ($options['ggn_test_mode']){
                $private_key_file_name = 'sandbox.'.$options['ggn_signature'].'private.key';
            }else{
                $private_key_file_name = 'live.'.$options['ggn_signature'].'private.key';
            }
            $private_key_file = WP_CONTENT_DIR . '/ggn/certificates/' . $private_key_file_name;
            
            try
            {
            $objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($_POST['env_key'], $_POST['data'], $private_key_file);
            
            $order_id = check_order($objPmReq->orderId);

            // action = status only if the associated error code is zero
            if ($objPmReq->objPmNotify->errorCode == 0) {

                // am comentat cazurile cu peinding deoarece statusul implicit este pending
                $statuses = [
                    'confirmed' => 'completed',
                    // 'confirmed_pending' => 'pending',
                    // 'paid_pending' => 'pending',
                    // 'paid' => 'pending',
                    'canceled' => 'cancelled',
                    'credit' => 'refunded'
                ];
                
                $response_action = $objPmReq->objPmNotify->action;
                
                if( array_key_exists( $response_action , $statuses ) ){
                    update_field('goicc_payment_method', 'card_netopia', $order_id);
                    update_field('goicc_order_status', $statuses[$response_action], $order_id);
                    if( $response_action === 'confirmed' )
                        update_field('goicc_payment_status', 'paid', $order_id);
                }

                switch($objPmReq->objPmNotify->action)
                    {
                    #orice action este insotit de un cod de eroare si de un mesaj de eroare. Acestea pot fi citite folosind $cod_eroare = $objPmReq->objPmNotify->errorCode; respectiv $mesaj_eroare = $objPmReq->objPmNotify->errorMessage;
                    #pentru a identifica ID-ul comenzii pentru care primim rezultatul platii folosim $id_comanda = $objPmReq->orderId;
                    case 'confirmed':
                        #cand action este confirmed avem certitudinea ca banii au plecat din contul posesorului de card si facem update al starii comenzii si livrarea produsului
                    //update DB, SET status = "confirmed/captured"
                    $errorMessage = $objPmReq->objPmNotify->errorMessage;
                    break;
                    case 'confirmed_pending':
                        #cand action este confirmed_pending inseamna ca tranzactia este in curs de verificare antifrauda. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
                    //update DB, SET status = "pending"
                    $errorMessage = $objPmReq->objPmNotify->errorMessage;
                    break;
                    case 'paid_pending':
                        #cand action este paid_pending inseamna ca tranzactia este in curs de verificare. Nu facem livrare/expediere. In urma trecerii de aceasta verificare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
                    //update DB, SET status = "pending"
                    $errorMessage = $objPmReq->objPmNotify->errorMessage;
                    break;
                    case 'paid':
                        #cand action este paid inseamna ca tranzactia este in curs de procesare. Nu facem livrare/expediere. In urma trecerii de aceasta procesare se va primi o noua notificare pentru o actiune de confirmare sau anulare.
                    //update DB, SET status = "open/preauthorized"
                    $errorMessage = $objPmReq->objPmNotify->errorMessage;
                    break;
                    case 'canceled':
                        #cand action este canceled inseamna ca tranzactia este anulata. Nu facem livrare/expediere.
                    //update DB, SET status = "canceled"
                    $errorMessage = $objPmReq->objPmNotify->errorMessage;
                    break;
                    case 'credit':
                        #cand action este credit inseamna ca banii sunt returnati posesorului de card. Daca s-a facut deja livrare, aceasta trebuie oprita sau facut un reverse. 
                    //update DB, SET status = "refunded"
                    $errorMessage = $objPmReq->objPmNotify->errorMessage;
                    break;
                default:
                    $errorType		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                    $errorCode 		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
                    $errorMessage 	= 'mobilpay_refference_action paramaters is invalid';
                    break;
                    }
            }
            else {
                //update DB, SET status = "rejected"

                update_field('goicc_payment_method', 'card_netopia', $order_id);
                update_field('goicc_order_status', 'failed', $order_id);

                $errorMessage = $objPmReq->objPmNotify->errorMessage;
            }
            }
            catch(Exception $e)
            {
                $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY;
                $errorCode		= $e->getCode();
                $errorMessage 	= $e->getMessage();
            }
        }
        else
        {
            $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
            $errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_PARAMETERS;
            $errorMessage 	= 'mobilpay.ro posted invalid parameters';
        }
    }
    else 
    {
        $errorType 		= Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
        $errorCode		= Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_POST_METHOD;
        $errorMessage 	= 'invalid request metod for payment confirmation';
    }

    header('Content-type: application/xml');
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    if($errorCode == 0)
    {
        echo "<crc>{$errorMessage}</crc>";
    }
    else
    {
        echo "<crc error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
    }
}

function ggn_return_action(){ // unused
    $destination = get_ty_page_url($_GET['order_id']);
    header('Location: '.$destination);
    die();
}