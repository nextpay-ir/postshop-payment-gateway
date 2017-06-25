<?php
/*
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
 */

function post_shop_content($atts, $content = '') {
    ob_start();

    if (isset($_POST['bmr_post_page']))
        ps_submit_payment();
    else
        ps_verify_payment();

    global $post, $wpdb, $table_prefix;
    $a = shortcode_atts(array('count' => 'show', 'stock' => 1, 'mobile' => '', 'price' => 0), $atts);
    $post_shop_price = get_post_meta($post->ID, "post_ps_price", true);
    $post_shop_course = get_post_meta($post->ID, "post_ps_course", true);
    $post_shop_stock = get_post_meta($post->ID, "post_ps_stock", true);

    if ($post_shop_stock == '')
        $post_shop_stock = -1;

    /*   if ($a['price'] != 0) {
      $post_shop_price = intval($a['price']);
      update_post_meta($post->ID, 'post_ps_price', $post_shop_price);
      }

     */

    // if (isset($_SESSION['post_shop_test']))
    //     var_dump($_SESSION['post_shop_test']);


    if (isset($_SESSION['post_shop_mobile']))
        unset($_SESSION['post_shop_mobile']);

    if ($a['mobile'] != '')
        $_SESSION['post_shop_mobile'] = $a['mobile'];

//​[post_shop mobile=09211212122 price=2500]

    $opt_msg = get_option('ps_message');
    if ($post_shop_price == 0 && $post_shop_course == 0):
        return do_shortcode($content);
    else :
        $user_id = get_current_user_id();
        $ps_login = get_post_meta($post->ID, "post_ps_login", TRUE);
        //  $ps_login = 3;
        if ($user_id == 0 && ( $ps_login == 2 || $ps_login == 3 )) {
            if ($post_shop_price != 0) {
                if ($post_shop_stock != -1 && $post_shop_stock - 1 < 0) {
                    echo '<div class="ps-alert ps-alert-danger text-center">';
                    echo $opt_msg['ps_msg_stock_not'];
                    echo '</div>';
                } else {
                    echo '<div class="ps-alert ps-alert-info text-center">';
                    echo ps_buy_post_form($post->ID, $opt_msg['ps_msg_nologin_sell'], $post_shop_price, TRUE);
                    echo '</div>';
                }
            }
            if ($ps_login == 3 && ($post_shop_stock == -1 || $post_shop_stock - 1 >= 0))
                echo '<div class="ps-alert ps-alert-danger">' . $opt_msg['ps_msg_login'] . ' <a href="' . $opt_msg['ps_msg_login_link'] . '">ورود به سایت</a></div>';
        }
        if ($user_id != 0 && $ps_login == 2) {
            echo '<div class="ps-alert ps-alert-danger text-center">' . $opt_msg['ps_msg_no_login_requirement'] . '<br /><a href="' . wp_logout_url() . '">خروج از حساب کاربری</a></div>';
        }

        if ($user_id == 0 && $ps_login == 1 && ($post_shop_stock == -1 || $post_shop_stock - 1 >= 0))
            echo '<div class="ps-alert ps-alert-danger">' . $opt_msg['ps_msg_login'] . ' <a href="' . $opt_msg['ps_msg_login_link'] . '">ورود به سایت</a></div>';

        if (($ps_login == 1 || $ps_login == 3) && is_user_logged_in()) {
            if ($post_shop_course != 0)
                $get_user_permision = $wpdb->get_row("SELECT * FROM {$table_prefix}ps_suser WHERE (`user_id` = '" . $user_id . "') AND (course_id={$post_shop_course} OR `post_id`='" . $post->ID . "') ");
            else
                $get_user_permision = $wpdb->get_row("SELECT * FROM {$table_prefix}ps_suser WHERE (`user_id` = '" . $user_id . "') AND (`post_id`='" . $post->ID . "') ");

            if (count($get_user_permision) > 0) {
                $expire = intval(get_post_meta(intval($post->ID), "post_ps_expire", true));
                if ((time() + (3600 * 24 * $expire) > time()) || $expire == 0)
                    return do_shortcode($content);
                else {
                    return '<div class="ps-alert ps-alert-danger">' . $opt_msg['ps_msg_expire'] . '</div><div class="ps-alert ps-alert-info text-center">' .
                            ps_buy_post_form($post->ID, $opt_msg['ps_msg_sell'], $post_shop_price) . '</div>';
                }
            } elseif (current_user_can('manage_options'))
                return do_shortcode($content);
            else {
                if ($post_shop_course != 0) {
                    $get_course = $wpdb->get_row("SELECT name,id,price,status FROM {$table_prefix}ps_course WHERE id= '" . $post_shop_course . "'  ");

                    $msg = '<div class="ps-alert ps-alert-info text-center">';
                    if (count($get_course) > 0) {
                        $msg.= '<p>کاربر گرامی این مطلب متعلق به "<b>' . $get_course->name . '</b>" می باشد.</p>';

                        if ($a['count'] == 'show') {
                            $ps_num = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}ps_suser WHERE post_id=0 AND course_id='" . $get_course->id . "' ");
                            $msg.='<p>' . $ps_num . ' نفر تاکنون در این دوره ثبت نام کرده اند.</p>';
                        }
                    } else {
                        return '</div>کاربر گرامی این مطلب متعلق به ' . $get_course->id . ' می باشد.';
                    }

                    if ($get_course->status == 1):
                        $msg.='
    <form method="post" id="form_post_shop">
    <input type="hidden" name="bmr_post_page" value="1" />
    <input type="hidden" name="cid" id="cid" value="' . $post_shop_course . '" />
    <button type="submit" name="submit_post_shop" class="ps-btn ps-btn-success">' . $opt_msg['ps_msg_sell_course'] . ' ' . number_format($get_course->price) . ' تومان</button>
  ' . wp_nonce_field(plugin_basename(__FILE__), 'course_post_shop_nonce') . '
</form>';
                    else:
                        $msg.='<p>ثبت نام در این دوره فعلا امکان پذیر نیست!</p>';
                    endif;

                    if ($post_shop_price != 0) // post access to sell
                        $msg.='' . ps_buy_post_form($post->ID, $opt_msg['ps_msg_sell'], $post_shop_price);

                    $msg.="</div>";
                    return $msg;
                }
                else {
                    if ($post_shop_stock != -1 && $post_shop_stock - 1 < 0) {
                        return '<div class="ps-alert ps-alert-danger text-center">' . $opt_msg['ps_msg_stock_not'] . '</div>';
                    } else {
                        return'
<div class="ps-alert ps-alert-info text-center">' . ps_buy_post_form($post->ID, $opt_msg['ps_msg_sell'], $post_shop_price) . '</div>';
                    }
                }
            }
        }
    endif;
    return ob_get_clean();
}

add_shortcode('post_shop', 'post_shop_content');

function ps_buy_post_form($id, $msg, $price, $email = FALSE) {
    $form = '<form name="form_post_shop" method="post" class="form_post_shop">
        <input type="hidden" name="bmr_post_page" value="1" />
<input type="hidden" name="pid" id="pid" value="' . $id . '" />';
    if ($email) {
        $form.='<label for="ps_email">ایمیل خود را وارد کنید :</label><br />
<input type="email" name="ps_email" id="ps_email" class="form-control" required="true" />';
    }

    $form.='<button type="submit" name="submit_post_shop" class="ps-btn ps-btn-success">' . $msg . ' ' . number_format($price) . ' تومان</button>
' . wp_nonce_field(plugin_basename(__FILE__), 'post_shop_nonce') . '</form>';
    return $form;
}

function shortcode_ps_show_course($atts) {
    ob_start();
    $a = shortcode_atts(array('id' => 0, 'count' => 'show'), $atts);
    global $wpdb;
    /*
      if (isset($_POST['bmr_form_box']))
      ps_submit_payment();
      else
      ps_verify_payment();
     */

    $opt_msg = get_option('ps_message');
    $table_name = $wpdb->prefix . 'ps_course';
    $msg = '';
    if ($a['id'] == 0)
        $where = "1=1";
    else
        $where = "id=" . intval($a['id']);

    $data = $wpdb->get_results("SELECT * FROM $table_name WHERE {$where} ORDER BY id desc");
    $user_id = get_current_user_id();
    foreach ($data as $course):
        ?>
        <div class="ps-course-box">
            <h3 class="ps-course-title"><?php echo $course->name; ?></h3>
            <p>
                <?php echo $course->description; ?>
            </p>
            <?php
            if (is_user_logged_in()) {
                // if (!current_user_can("manage_options")) {
                $get_course = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}ps_suser WHERE course_id= '" . $course->id . "' AND user_id='" . $user_id . "' ");

                if (count($get_course) == 0) {

                    $the_query = new WP_Query("meta_key=post_ps_course&meta_value=" . $course->id . "&order=ASC");
                    if ($the_query->have_posts()) {
                        $the_query->the_post();
                        echo '<a href="' . get_permalink($the_query->ID) . '" target="_blank">ثبت نام دوره</a>';
                    }
                    /*
                      $msg.='
                      <form method="post" id="form_post_shop">
                      <input type="hidden" name="bmr_form_box" value="1" />
                      <input type="hidden" name="cid" id="cid" value="' . $course->id . '" />
                      <button type="submit" name="submit_post_shop" class="ps-btn ps-btn-success">' . $opt_msg['ps_msg_sell_course'] . ' ' . number_format($course->price) . ' تومان</button>
                      ' . wp_nonce_field(plugin_basename(__FILE__), 'course_post_shop_nonce') . '
                      </form>';
                     */
                }
                // }
            } else
                echo '<a href="' . $opt_msg['ps_msg_login_link'] . '" >ورود به سایت</a><Br />';
            ?><Br />
            <i>قیمت : <?php echo number_format($course->price); ?> تومان | ظرفیت  : <?php echo ($course->capacity == 0 ? 'بدون محدودیت' : $course->capacity); ?>
                <?php
                if ($a['count'] == 'show') {
                    $ps_num = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}ps_suser WHERE post_id=0 AND course_id='" . $course->id . "' ");
                    ?>
                    | ثبت نام کنندگان : <?php echo number_format($ps_num); ?> نفر
                <?php } ?>
            </i>
        </div>
        <?php
    endforeach;
    return ob_get_clean();
}

add_shortcode('ps_course', 'shortcode_ps_show_course');

function ps_submit_payment() {
    global $current_user;
    $opt_payment = get_option('ps_payment');
    $pid = 0;
    $cid = 0;

    include_once 'payment/bank.php';

    if (isset($_POST['pid']) && isset($_POST['post_shop_nonce']) && wp_verify_nonce($_POST['post_shop_nonce'], plugin_basename(__FILE__)))
        $pid = intval($_POST['pid']);

    if ($pid != 0 && !current_user_can("manage_options")) {
        global $wpdb;

        // $_SESSION['post_shop_test'] = array();

        if (isset($_SESSION['ps']))
            unset($_SESSION['ps']);

        $order_id = time() . rand(10, 1000);
        $price = intval(get_post_meta($pid, "post_ps_price", true));
        $stock = intval(get_post_meta($pid, "post_ps_stock", true));

        $_SESSION['ps']['post_id'] = $pid;
        $_SESSION['ps']['order_id'] = $order_id;

        //  $_SESSION['post_shop_test'][] = 'firste created:' . $_SESSION['ps']['order_id'];

        if (intval($price) < 100) {
            echo '<div class="ps-alert ps-alert-danger">مبلغ مورد نظر شما باید بیشتر از 100 تومن باشد !</div>';
            unset($_SESSION['ps']);
            return;
        }
        if ($stock != -1 && $stock - 1 < 0) {
            $opt_msg = get_option('ps_message');
            echo '<div class="ps-alert ps-alert-danger">' . $opt_msg['ps_msg_stock_not'] . '</div>';
            unset($_SESSION['ps']);
            return;
        }

        if (is_user_logged_in()) {
            $username = $current_user->user_login;
            $email = $current_user->user_email;
        } else {
            $username = 'مهمان';
            $email = (isset($_POST['ps_email']) ? $_POST['ps_email'] : '');
        }
        $callback = ps_get_url();


        // $_SESSION['post_shop_test'][] = 'befre send mellat function:' . $order_id;
///////////////////// Send request ////////////////////
        switch ($opt_payment['ps_active_payment']) {
            case 'parspal':
                ps_parspal_send(FALSE, $opt_payment['parspal_merchant_id'], $opt_payment['parspal_port_password'], $callback, $price, $username, $email, $order_id);
                break;

            case 'test_parspal':
                ps_parspal_send(TRUE, $opt_payment['parspal_merchant_id'], $opt_payment['parspal_port_password'], $callback, $price, $username, $email, $order_id);
                break;

            case 'payline':
                ps_payline_send($opt_payment['payline_api'], $price, $callback, $order_id, $username, $email);
                break;

            case 'zarinpal':
                ps_zarinpal_send($opt_payment['zarinpal_merchant_id'], $callback, $price, $username, $email, $order_id);
                break;

            case 'nextpay':
                ps_nextpay_send($opt_payment['nextpay_api_key'], $callback, $price, $username, $email, $order_id);
                break;

            case 'jahanpay':
                ps_jahanpay_send($opt_payment['jahanpay_api'], $callback, $price, $username, $email, $order_id);
                break;

            case 'mellat':
                ps_mellat_send($opt_payment['mellat_terminal_id'], $opt_payment['mellat_username'], $opt_payment['mellat_password'], $callback, $price, $username, $email, $order_id);
                break;
        }
    } elseif (isset($_POST['cid']) && is_user_logged_in() && isset($_POST['course_post_shop_nonce']) && wp_verify_nonce($_POST['course_post_shop_nonce'], plugin_basename(__FILE__))) {
        global $wpdb;
        $opt_msg = get_option('ps_message');

        if (isset($_SESSION['ps']))
            unset($_SESSION['ps']);


        $cid = intval($_POST['cid']);

        $get_course = $wpdb->get_row("SELECT price,capacity,status FROM {$wpdb->prefix}ps_course WHERE id = '" . $cid . "'", OBJECT);
        if (count($get_course) <= 0)
            return '';

        $ps_num = $wpdb->get_var("SELECT count(*) FROM {$wpdb->prefix}ps_suser WHERE post_id=0 AND course_id='" . $cid . "' ");
        if ($get_course->capacity != 0 && $ps_num >= $get_course->capacity) {
            echo '<div class="ps-alert ps-alert-danger">' . $opt_msg['ps_msg_full_capacity'] . '</div>';
            return;
        }
        if ($get_course->status == 0)
            return '';

        $order_id = time() . rand(10, 1000);
        $price = $get_course->price;
        $_SESSION['ps']['course_id'] = $cid;
        $_SESSION['ps']['order_id'] = $order_id;

        if (intval($price) < 100) {
            echo '<div class="ps-alert ps-alert-danger">مبلغ مورد نظر شما باید بیشتر از 100 تومن باشد !</div>';
            unset($_SESSION['ps']);
            return;
        }

        $username = $current_user->user_login;
        $email = $current_user->user_email;
        $callback = ps_get_url();

        switch ($opt_payment['ps_active_payment']) {
            case 'parspal':
                ps_parspal_send(FALSE, $opt_payment['parspal_merchant_id'], $opt_payment['parspal_port_password'], $callback, $price, $username, $email, $order_id);
                break;

            case 'test_parspal':
                ps_parspal_send(TRUE, $opt_payment['parspal_merchant_id'], $opt_payment['parspal_port_password'], $callback, $price, $username, $email, $order_id);
                break;

            case 'payline':
                ps_payline_send($opt_payment['payline_api'], $price, $callback, $order_id, $username, $email);
                break;

            case 'zarinpal':
                ps_zarinpal_send($opt_payment['zarinpal_merchant_id'], $callback, $price, $username, $email, $order_id);
                break;

            case 'nextpay':
                ps_nextpay_send($opt_payment['nextpay_api_key'], $callback, $price, $username, $email, $order_id);
                break;

            case 'jahanpay':
                ps_jahanpay_send($opt_payment['jahanpay_api'], $callback, $price, $username, $email, $order_id);
                break;

            case 'mellat':
                ps_mellat_send($opt_payment['mellat_terminal_id'], $opt_payment['mellat_username'], $opt_payment['mellat_password'], $callback, $price, $username, $email, $order_id);
                break;
        }

        return 'در حال انتقال به درگاه بانکی ...';
    }
}

function ps_verify_payment() {
    $opt_payment = get_option('ps_payment');
    // veryfy
    if (isset($_SESSION['ps']['order_id'])) {
        include_once 'payment/bank.php';
        $post_id = 0;
        $course_id = 0;
        $order_id = $_SESSION['ps']['order_id'];
        $price = 0;

        //  $_SESSION['post_shop_test'][] = 'before verify:' . $_SESSION['ps']['order_id'];

        if (isset($_SESSION['ps']['post_id'])) {
            $post_id = intval($_SESSION['ps']['post_id']);
            $price = get_post_meta($post_id, "post_ps_price", true);
        } elseif (isset($_SESSION['ps']['course_id'])) {
            $course_id = intval($_SESSION['ps']['course_id']);
            global $wpdb;
            $get_course = $wpdb->get_row("SELECT price FROM {$wpdb->prefix}ps_course WHERE id = '" . intval($_SESSION['ps']['course_id']) . "'", OBJECT);
            if (count($get_course) <= 0)
                return '';

            $price = $get_course->price;
        }

        switch ($opt_payment['ps_active_payment']) {
            case 'parspal':
                ps_parspal_verify(FALSE, $opt_payment['parspal_merchant_id'], $opt_payment['parspal_port_password'], $price, $post_id, $order_id, $course_id);
                break;

            case 'test_parspal':
                ps_parspal_verify(TRUE, $opt_payment['parspal_merchant_id'], $opt_payment['parspal_port_password'], $price, $post_id, $order_id, $course_id);
                break;

            case 'payline':
                ps_payline_verfy($opt_payment['payline_api'], $price, $post_id, $order_id, $course_id);
                break;

            case 'zarinpal':
                ps_zarinpal_verify($opt_payment['zarinpal_merchant_id'], $price, $post_id, $order_id, $course_id);
                break;

            case 'nextpay':
                ps_nextpay_verify($opt_payment['nextpay_api_key'], $price, $post_id, $order_id, $course_id);
                break;

            case 'jahanpay':
                ps_jahanpay_verify($opt_payment['jahanpay_api'], $price, $post_id, $order_id, $course_id);
                break;

            case 'mellat':
                ps_mellat_verify($opt_payment['mellat_terminal_id'], $opt_payment['mellat_username'], $opt_payment['mellat_password'], $price, $post_id, $order_id, $course_id);
                break;
        }
    }
}

add_action('wp_enqueue_scripts', 'PS_user_scripts');

function PS_user_scripts() {
    if (!is_admin()) {
        wp_register_style('PS_user_styles', PS_CSS_URL . 'ps_user_style.css');
        wp_enqueue_style('PS_user_styles');
    }
}

/////////////////////////////////////////////////
function ps_my_post_list() {
    global $wpdb;
    $user_id = get_current_user_id();
    $opt_msg = get_option('ps_message');
    if ($user_id == 0)
        echo '<div class="ps-login"><a href="' . $opt_msg['ps_msg_login_link'] . '" >ورود به سایت</a></div>';
    else {
        $susers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ps_suser WHERE user_id={$user_id} ORDER BY id DESC");
        if (count($susers)):
            echo '<ul class="ps_my_post">';
            foreach ($susers as $suser) {
                if ($suser->post_id != 0)
                    echo '<li><a title="' . number_format($suser->price) . ' تومان" href="' . get_permalink($suser->post_id) . '">' . get_the_title($suser->post_id) . '</a></li>';
                else {
                    $course = $wpdb->get_row("SELECT name FROM {$wpdb->prefix}ps_course WHERE id='" . $suser->course_id . "'", OBJECT);
                    echo '<li title="' . number_format($suser->price) . ' تومان"> ' . $course->name . '</li>';
                }
            }
            echo '</ul>';
        else :
            echo $opt_msg['ps_msg_no_sell_post'];
        endif;
    }
}

add_shortcode('ps_my_post', 'ps_my_post_list');

function ps_get_my_post_link() {
    echo admin_url('admin.php?page=PS_my_post');
}

add_shortcode('ps_my_post_link', 'ps_get_my_post_link');
