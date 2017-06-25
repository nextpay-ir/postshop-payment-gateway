<?php

/*
  Plugin Name: فروش پست ها
  Plugin URI:http://iwebpro.ir/post-shop.html
  Description: افزونه ای برای ایجاد دوره های آموزشی و فروش پست و... (Post Shop)
  Version: 4.5
  Author:بهنام رسولی
  Author URI:http://iwebpro.ir
 */
defined('ABSPATH') || exit;
define('PS_VERSION', '4.2');
define('PS_DIR', plugin_dir_path(__FILE__));
define('PS_URL', plugin_dir_url(__FILE__));
define('PS_INC_DIR', trailingslashit(PS_DIR . 'inc'));
define('PS_CSS_URL', trailingslashit(PS_URL . 'css'));
define('PS_JS_URL', trailingslashit(PS_URL . 'js'));
define('PS_IMG_URL', trailingslashit(PS_URL . 'img'));
if (is_admin()) {
    include_once PS_INC_DIR . 'front_back.php';
    include_once PS_INC_DIR . 'pages.php';
}
include_once PS_INC_DIR . 'front_end.php';
include_once PS_INC_DIR . 'widget.php';

register_activation_hook(__FILE__, 'wp_PS_install');

function wp_PS_install() {
    global $table_prefix;
    $trans_sql = 'CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'ps_transactions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_name` varchar(255) COLLATE utf8_persian_ci NOT NULL,
  `price` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  `email` varchar(50) COLLATE utf8_persian_ci NOT NULL,
  `order_id` varchar(100) NOT NULL,
  `ref_id` varchar(100) NOT NULL,
  `status` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;';


    $suser_sql = 'CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'ps_suser` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `course_id` bigint(20) NOT NULL,
  `price` int(11) NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;';


    $course_sql = 'CREATE TABLE IF NOT EXISTS `' . $table_prefix . 'ps_course` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `price` int(11) NOT NULL,
    `capacity` int(11) NOT NULL,
   `status` TINYINT NOT NULL DEFAULT 1,
  `description` varchar(255) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin AUTO_INCREMENT=1 ;';


    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($trans_sql);
    dbDelta($suser_sql);
    dbDelta($course_sql);

    if (!get_option('ps_message')) {
        $options = array(
            'ps_msg_name' => 'فروش پست',
            'ps_msg_sell' => 'خرید پست',
            'ps_msg_sell_course' => 'ثبت نام در این دوره',
            'ps_msg_success_pay' => 'خرید شما با موفقیت انجام شد.',
            'ps_msg_login' => 'برای مشاهده و خرید این پست باید وارد سایت شوید.',
            'ps_msg_login_link' => wp_login_url(),
            'ps_msg_no_sell_post' => 'شما هنوز هیچ پستی نخریده اید.',
            'ps_msg_my_post' => 'پست های من',
            'ps_msg_expire' => 'تاریخ نمایش این پست به پایان رسیده!',
            'ps_msg_nologin_sell' => 'خرید بدون نیاز به ورود',
            'ps_msg_no_login_requirement' => 'این پست نیازی به ورود به سایت ندارد!',
            'ps_msg_full_capacity' => 'ظرفیت ثبت نام در این دوره تکمیل شده!',
            'ps_msg_sended_mail' => 'محتوای پست به آدرس ایمیلی که وارد کرده بودید ارسال شد.',
            'ps_active_payment' => 0,
            'ps_msg_stock_not' => 'موجودی این مقاله به پایان رسیده!',
            'ps_msg_mobile_sms' => 'مقاله {title} به فروش رفت.',
        );
        add_option('ps_message', $options);
    }
    if (!get_option('ps_payment')) {
        $options = array(
            'ps_return_pay' => '',
            'ps_active_payment' => 0,
            'parspal_merchant_id' => '',
            'parspal_port_password' => '',
            'payline_api' => '',
            'zarinpal_merchant_id' => '',
            'nextpay_api_key' => '',
            'jahanpay_api' => '',
            'mellat_terminal_id' => '',
            'mellat_username' => '',
            'mellat_password' => '',
            'relax_number' => '',
            'relax_username' => '',
            'relax_password' => '',
        );
        add_option('ps_payment', $options);
    }
}

add_action('init', 'ps_session_start', 1);
add_action('wp_logout', 'ps_session_end');

function ps_session_start() {
    if (!session_id())
        session_start();
}

function ps_session_end() {
    if (session_id()) {
        $_SESSION = array();
        session_destroy();
    }
}

function ps_date($format, $timestamp) {
    if (function_exists('jdate'))
        return jdate($format, $timestamp);
    else
        return date($format, $timestamp);
}

function ps_get_url() {
    $host = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
    return $host . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function ps_redirect($url) {
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit;
    } else {
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . $url . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        echo '</noscript>';
        exit;
    }
}

function ps_postshop_settings_link($links) {
    $settings_link = '<a href="admin.php?page=PS_settings">تنظیمات</a>';
    array_unshift($links, $settings_link);
    return $links;
}

$plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$plugin", 'ps_postshop_settings_link');

function ps_send_sms($phone, $msg = '') {
    @ini_set("soap.wsdl_cache_enabled", "0");

    $opt_payment = get_option('ps_payment');

    $user = $opt_payment['relax_username'];
    $pass = $opt_payment['relax_password'];
    if ($user != '' && $pass != '') {
        try {
            $client = new SoapClient("http://87.107.121.52/post/send.asmx?wsdl", array('encoding' => 'UTF-8'));
            $getcredit_parameters = array(
                "username" => $user,
                "password" => $pass
            );
            $credit = $client->GetCredit($getcredit_parameters)->GetCreditResult;
            //echo "Credit: " . $credit . "<br />";
            $encoding = "UTF-8"; //CP1256, CP1252
            $textMessage = iconv($encoding, 'UTF-8//TRANSLIT', $msg);

            $sendsms_parameters = array(
                'username' => $user,
                'password' => $pass,
                'from' => $opt_payment['relax_number'],
                'to' => array($phone),
                'text' => $textMessage,
                'isflash' => false,
                'udh' => "",
                'recId' => array(0),
                'status' => 0
            );

            $status = $client->SendSms($sendsms_parameters)->SendSmsResult;
            //  echo "Status: " . $status . "<br />";
        } catch (SoapFault $ex) {
            echo $ex->faultstring;
        }
    }
}
