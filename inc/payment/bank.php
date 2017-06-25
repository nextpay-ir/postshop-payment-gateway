<?php

function ps_insert_trans($username, $price, $order_id, $email = "") {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ps_transactions';
    $insert = $wpdb->insert($table_name, array(
        'user_name' => $username,
        'price' => $price,
        'date' => time(),
        'email' => $email,
        'order_id' => $order_id,
        'ref_id' => 0,
        'status' => 0,
    ));
}

function ps_end_trans($ref, $order_id, $price, $post_id = 0, $course_id = 0) {
    global $wpdb;
    $table_name_trans = $wpdb->prefix . 'ps_transactions';
    $table_name_user = $wpdb->prefix . 'ps_suser';

    $wpdb->update($table_name_trans, array('ref_id' => $ref, 'status' => 1), array('order_id' => $order_id));

    $user_id = 0;

    if (is_user_logged_in())
        $user_id = get_current_user_id();

    $wpdb->insert($table_name_user, array(
        'post_id' => $post_id,
        'user_id' => $user_id,
        'price' => $price,
        'date' => time(),
        'course_id' => $course_id
    ));

    if ($post_id != 0) {
        $stock = intval(get_post_meta($post_id, "post_ps_stock", true));
        if ($stock != -1)
            update_post_meta($post_id, 'post_ps_stock', $stock - 1);
    }

    if (!is_user_logged_in()) {
        $ps_post = get_post($post_id);
        $message = $ps_post->post_content;

        $get_trans = $wpdb->get_row("SELECT email FROM {$table_name_trans} WHERE order_id = '" . $order_id . "'", OBJECT);

        if (count($get_trans) > 0 && $get_trans->email != "") {
            $msg_content = str_replace('[post_shop]', ' ', $message);
            $msg_content = preg_replace('/\[post_shop+ [a-z0-9=]* [a-z0-9=]*\]+/i', '', $msg_content);
            $msg_content = str_replace('[/post_shop]', ' ', $msg_content);
            $msg_content = '<p dir="rtl">' . $msg_content . '</p>';
            add_filter('wp_mail_content_type', 'ps_mail_set_content_type');

            function ps_mail_set_content_type($content_type) {
                return 'text/html';
            }

            wp_mail($get_trans->email, 'خرید ' . $ps_post->post_title . ' از : ' . get_option('blogname'), do_shortcode($msg_content));
        }
    }

    $opt_msg = get_option('ps_message');

    // send sms
    if (isset($_SESSION['post_shop_mobile']) && $_SESSION['post_shop_mobile'] != '') {
        $msg = $opt_msg['ps_msg_mobile_sms'];
        if ($post_id != 0) {
            $ps_post = get_post($post_id);
            $msg = str_replace('{title}', $ps_post->post_title, $msg);
        } else {
            $get_course = $wpdb->get_row("SELECT name FROM {$wpdb->prefix}ps_course WHERE id= '" . $course_id . "'  ");
            if (count($get_course) > 0)
                $msg = str_replace('{title}', $get_course->name, $msg);
        }
        ps_send_sms($_SESSION['post_shop_mobile'], $msg);
    }

    // succrss message

    $email = '';
    if (!is_user_logged_in()) {
        $email = '<Br />' . $opt_msg['ps_msg_sended_mail'];
    }
    echo '<div class="ps-alert ps-alert-success text-center">' . $opt_msg['ps_msg_success_pay'] . '<br />
                        شماره پیگیری : ' . $ref . $email . '</div>';
}

// ------------------------------------ start parspal -------------------------------------------------
function ps_parspal_send($test = false, $parspal_merchant_id, $parspal_port_password, $callback, $price, $username, $email, $order_id) {
    try {
        if ($test)
            $client = new SoapClient('http://sandbox.parspal.com/WebService.asmx?wsdl');
        else
            $client = new SoapClient('http://merchant.parspal.com/WebService.asmx?wsdl');

        $parameters = array(
            'MerchantID' => $parspal_merchant_id,
            'Password' => $parspal_port_password,
            'Price' => $price,
            'ReturnPath' => $callback,
            'ResNumber' => time(),
            'Description' => 'خرید از سایت با استفاده از افزونه فروش پست',
            'Paymenter' => $username,
            'Email' => $email,
            'Mobile' => '----'
        );

        $res = $client->RequestPayment($parameters);
        $PayPath = $res->RequestPaymentResult->PaymentPath;
        $Status = $res->RequestPaymentResult->ResultStatus;
    } catch (Exception $ex) {
        $Status = 'error';
    }

    if ($Status == 'Succeed') {
        ps_insert_trans($username, $price, $order_id, $email);
        echo '<div class="ps-alert ps-alert-info">در حال اتصال به درگاه ...</div>';
        ps_redirect($PayPath);
    } else
        echo '<div class="ps-alert ps-alert-danger">خطا در متصل شدن به درگاه !</div>';
}

function ps_parspal_verify($test = false, $parspal_merchant_id, $parspal_port_password, $price, $post_id, $order_id, $course_id = 0) {
    if (isset($_POST['status']) && $_POST['status'] == 100) {

        if ($test)
            $client = new SoapClient('http://sandbox.parspal.com/WebService.asmx?wsdl');
        else
            $client = new SoapClient('http://merchant.parspal.com/WebService.asmx?wsdl');

        $Refnumber = (isset($_POST['refnumber']) ? $_POST['refnumber'] : 0);
        $paramters = array(
            'MerchantID' => $parspal_merchant_id,
            'Password' => $parspal_port_password,
            "Price" => $price,
            "RefNum" => $Refnumber
        );
        $res = $client->VerifyPayment($paramters);
        $Status = $res->verifyPaymentResult->ResultStatus;
        $PayPrice = $res->verifyPaymentResult->PayementedPrice;
        if ($Status == 'success') {
            ps_end_trans($Refnumber, $order_id, $price, $post_id, $course_id);
        } else {
            echo '<div class="ps-alert ps-alert-danger">
			خطا در پردازش عملیات پرداخت !
			</div>';
        }

        unset($_SESSION['ps']);
    }
}

/*  ------------------------------------- End parpal --------------------------------------------------- */

// -------------------------------------- Start Payline ------------------------------------------------
function ps_payline_send_pay($url, $api, $amount, $redirect) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api&amount=$amount&redirect=$redirect");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function ps_payline_get_pay($url, $api, $trans_id, $id_get) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "api=$api&id_get=$id_get&trans_id=$trans_id");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function ps_payline_send($api, $price, $callback, $order_id, $username, $email) {
    $price*=10;
    $url = 'http://payline.ir/payment-test/gateway-send';
    $result = ps_payline_send_pay($url, $api, $price, $callback);
    if ($result > 0 && is_numeric($result)) {
        $go = "http://payline.ir/payment-test/gateway-$result";
        ps_insert_trans($username, $price, $order_id, $email);
        echo '<div class="ps-alert ps-alert-info">در حال اتصال به درگاه ...</div>';
        ps_redirect($go);
    } else
        echo '<div class="ps-alert ps-alert-danger">خطا در متصل شدن به درگاه !</div>' . $result;
}

function ps_payline_verfy($api, $price, $post_id, $order_id, $course_id = 0) {
    if (isset($_POST['trans_id']) && isset($_POST['id_get'])):
        $url = 'http://payline.ir/payment-test/gateway-result-second';

        $trans_id = $_POST['trans_id'];
        $id_get = $_POST['id_get'];
        $result = ps_payline_get_pay($url, $api, $trans_id, $id_get);

        if ($result == 1) {
            ps_end_trans($trans_id, $order_id, $price, $post_id, $course_id);
        } else {
            echo '<div class="ps-alert ps-alert-danger">
                    خطا  دوباره تلاش کنید !
			</div>';
        }
        unset($_SESSION['ps']);
    endif;
}

/* -------------------------------------- End Payline ------------------------------------------------ */

// -------------------------------------- Start Zarinpal ------------------------------------------------
function ps_zarinpal_send($merchant_id, $callback, $price, $username, $email, $order_id) {
    $error = FALSE;
    try {
        $client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8'));
        $result = $client->PaymentRequest(array(
            'MerchantID' => $merchant_id,
            'Amount' => $price,
            'Description' => 'خرید از سایت با استفاده از افزونه فروش پست',
            'Email' => $email,
            'Mobile' => '----',
            'CallbackURL' => $callback
        ));
    } catch (Exception $ex) {
        $error = TRUE;
    }

    if ($error == FALSE && $result->Status == 100) {
        $insert = ps_insert_trans($username, $price, $order_id, $email);
        echo '<div class="ps-alert ps-alert-info">در حال اتصال به درگاه ...</div>';
        ps_redirect('https://www.zarinpal.com/pg/StartPay/' . $result->Authority);
    } else
        echo '<div class="ps-alert ps-alert-danger">خطا در متصل شدن به درگاه !</div>';
}

function ps_zarinpal_verify($merchant_id, $price, $post_id, $order_id, $course_id = 0) {
    if (isset($_GET['Status']) && $_GET['Status'] == 'OK' && isset($_GET['Authority'])) {
        $Authority = $_GET['Authority'];
        $client = new SoapClient('https://de.zarinpal.com/pg/services/WebGate/wsdl', array('encoding' => 'UTF-8'));
        $result = $client->PaymentVerification(array(
            'MerchantID' => $merchant_id,
            'Authority' => $Authority,
            'Amount' => $price
        ));

        if ($result->Status == 100) {
            ps_end_trans($Authority, $order_id, $price, $post_id, $course_id);
        } else {
            echo '<div class="alert alert-danger">
			خطا در پردازش عملیات پرداخت ، نتیجه پرداخت : ' . $Status . ' !
			<br /></div>';
        }
        unset($_SESSION['ps']);
    }
}

/* -------------------------------------- End Zarinpal ------------------------------------------------ */


// -------------------------------------- Start Nextpay ------------------------------------------------
function ps_nextpay_send($api_key, $callback, $price, $username, $email, $order_id) {
    $error = FALSE;
    try {
        $client = new SoapClient('https://api.nextpay.org/gateway/token.wsdl', array('encoding' => 'UTF-8'));
        $result = $client->TokenGenerator(array(
            'api_key' 	=> $api_key,
            'order_id'	=> $order_id,
            'amount' 		=> $price,
            'callback_uri' 	=> $callback
        ));
    } catch (Exception $ex) {
      die($ex);
        $error = TRUE;
    }

    $result = $result->TokenGeneratorResult;

    if ($error == FALSE && $result->code == -1) {
        $insert = ps_insert_trans($username, $price, $order_id, $email);
        echo '<div class="ps-alert ps-alert-info">در حال اتصال به درگاه ...</div>';
        ps_redirect('https://api.nextpay.org/gateway/payment/' . $result->trans_id);
    } else
        echo '<div class="ps-alert ps-alert-danger">خطا در متصل شدن به درگاه !</div>';
}

function ps_nextpay_verify($api_key, $price, $post_id, $order_id, $course_id = 0) {
    if (isset($_POST['trans_id']) && isset($_POST['order_id'])) {
        $trans_id = $_POST['trans_id'];
        // $order_id = $_POST['order_id'];
        $client = new SoapClient('https://api.nextpay.org/gateway/verify.wsdl', array('encoding' => 'UTF-8'));
        $result = $client->PaymentVerification(array(
            'api_key' => $api_key,
            'trans_id' => $trans_id,
            'amount' => $price,
            'order_id' => $order_id
        ));

        $result = $result->PaymentVerificationResult;

        if ($result->code == 0) {
            ps_end_trans($trans_id, $order_id, $price, $post_id, $course_id);
        } else {
            echo '<div class="alert alert-danger">
			خطا در پردازش عملیات پرداخت ، نتیجه پرداخت : ' . $result->code . ' !
			<br /></div>';
        }
        unset($_SESSION['ps']);
    }
}

/* -------------------------------------- End Nextpay ------------------------------------------------ */

// -------------------------------------- Start Jahanpay ------------------------------------------------
function ps_jahanpay_send($api, $callback, $price, $username, $email, $order_id) {
    include_once 'jahanpay.class.php';
    $client = new ps_jahanpay;
    $txt = urlencode('خرید از سایت با استفاده از افزونه پست شاپ');
    $res = $client->call('requestpayment', array($api, $price, $callback, $order_id, $txt));
    $insert = ps_insert_trans($username, $price, $order_id, $email);
    echo '<div class="ps-alert ps-alert-info">در حال اتصال به درگاه ...</div>';
    ps_redirect("http://www.jahanpay.com/pay_invoice/{$res}");
}

function ps_jahanpay_verify($api, $price, $post_id, $order_id, $course_id = 0) {
    if (isset($_GET['order_id']) && isset($_GET["au"])):
        include_once 'jahanpay.class.php';
        $orderId = (int) $_GET["order_id"];
        $Refnumber = $_GET["au"];
        $client = new ps_jahanpay;
        $result = $client->call('verification', array($api, $price, $_GET["au"]));
        if ($result == 1)
            ps_end_trans($Refnumber, $order_id, $price, $post_id, $course_id);
        else {
            echo '<div class="alert alert-danger">
			خطا در پردازش عملیات پرداخت ، نتیجه پرداخت : ' . $Status . ' !
			<br /></div>';
        }
        unset($_SESSION['ps']);
    endif;
}

// -------------------------------------- End Jahanpay ------------------------------------------------



/* -------------------------------------- Start Mellat ------------------------------------------------ */
function ps_mellat_send($mterminal, $musername, $mpassword, $callback, $price, $username, $email, $order_id) {
    include_once 'mellat.class.php';

    $mellat = new ps_mellat($mterminal, $musername, $mpassword);
    $status = $mellat->request($price, $order_id, $callback);

    if ($status != -6) {
        ps_insert_trans($username, $price, $order_id, $email);
        $mellat->go2bank($status);
    } else
        echo '<div class="ps-alert ps-alert-danger">خطا در متصل شدن به درگاه !</div>';
}

function ps_mellat_verify($mterminal, $musername, $mpassword, $price, $post_id, $order_id, $course_id = 0) {

    if (isset($_POST['RefId']) && isset($_POST['ResCode']) && isset($_POST['SaleOrderId']) && isset($_POST['SaleReferenceId'])) {
        include_once 'mellat.class.php';

        //   $_SESSION['post_shop_test'][] = 'in function verify mellat session:' . $_SESSION['ps']['order_id'];
        //   $_SESSION['post_shop_test'][] = 'in function verify mellat $order_id:' . $order_id;
        //    $_SESSION['post_shop_test'][] = 'in function verify sale id:' . $_POST['SaleOrderId'];

        $mellat = new ps_mellat($mterminal, $musername, $mpassword);
        $status = $mellat->verify($price, $order_id);

        if (count($status) == 0)
            ps_end_trans($_POST['RefId'], $order_id, $price, $post_id, $course_id);
        else {
            // var_dump($status);
            echo '<div class="ps-alert ps-alert-danger">خطا در پردازش عملیات پرداخت !</div>';
            //  exit();
        }

        unset($_SESSION['ps']);
    }
}
