<?php

function PS_my_post_fun() {
    global $wpdb;
    echo '<div class="wrap post-shop">';
    include_once PS_INC_DIR . 'class/ps_my_post_table.php';
    $ps_suser = new ps_my_post_table();
    $ps_suser->prepare_items();
    ?>
    <form method="post">
        <input type="hidden" name="page" value="<?php echo $_GET['page'] ?>">
        <?php
        $ps_suser->display();
        echo '</form>';
        echo '</div>';
    }

    function PS_admin_dashboard() {
        global $wpdb;
        echo '<div class="wrap post-shop">';
        echo '<h2>پست ها و دوره های خریداری شده</h2>';
        include_once PS_INC_DIR . 'class/ps_suser_table.php';
        $ps_suser = new ps_suser_table();
        $ps_suser->prepare_items();
        ?>
        <form method="post">
            <input type="hidden" name="page" value="<?php echo $_GET['page'] ?>">
            <?php
            $ps_suser->search_box('جستجو کاربران', 's');
            $ps_suser->display();
            echo '</form>';
            echo '</div>';
        }

        function PS_admin_course() {
            global $wpdb;
            echo '<div class="wrap post-shop">';
            echo '<h2>دوره ها</h2>';

            if (isset($_POST['edit_course'])):
                $wpdb->update($wpdb->prefix . 'ps_course', array(
                    'name' => sanitize_text_field($_POST['course_name']),
                    'description' => sanitize_text_field($_POST['course_desc']),
                    'price' => sanitize_text_field($_POST['course_price']),
                    'capacity' => sanitize_text_field($_POST['course_capacity']),
                    'status' => intval($_POST['course_status']),
                        ), array('id' => intval($_POST['course_id']))
                );
                echo '<div class="updated"><p>دوره مورد نظر با موفقیت ویرایش شد.</p></div>';

            elseif (isset($_POST['add_course'])):
                $wpdb->insert($wpdb->prefix . 'ps_course', array(
                    'name' => sanitize_text_field($_POST['course_name']),
                    'description' => sanitize_text_field($_POST['course_desc']),
                    'price' => sanitize_text_field($_POST['course_price']),
                    'capacity' => sanitize_text_field($_POST['course_capacity']),
                    'status' => intval($_POST['course_status']),
                ));
                echo '<div class="updated"><p>دوره مورد نظر با موفقیت اضافه شد.</p></div>';
            endif;

            if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']))
                $wpdb->delete($wpdb->prefix . 'ps_course', array('id' => intval($_GET['id'])));

            include_once PS_INC_DIR . 'class/ps_course_table.php';
            $ps_course = new ps_course_table();
            $ps_course->prepare_items();
            ?>
            <form method="post">
                <input type="hidden" name="page" value="<?php echo $_GET['page'] ?>">
                <?php
                $ps_course->display();
                ?>
            </form>
            <hr />
            <form method="post" action="?page=<?php echo $_GET['page']; ?>" class="ps_form">
                <?php
                $edit = FALSE;
                if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
                    $cource_get_result = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}ps_course WHERE id = '" . intval($_GET['id']) . "'", OBJECT);
                    echo '<input type="hidden" name="course_id" value="' . intval($_GET['id']) . '" />';
                    $edit = TRUE;
                }
                ?>

                <label for = "course_name">نام دوره :</label><br />
                <input type = "text" name = "course_name" id = "course_name" value ="<?php echo ($edit ? $cource_get_result->name : ''); ?>" required /><Br />

                <label for = "course_price">قیمت (تومان):</label><br />
                <input type = "number" name = "course_price" dir = "ltr" id = "course_price" value = "<?php echo ($edit ? $cource_get_result->price : ''); ?>" required /><Br />


                <label for = "course_capacity">ظرفیت (0 به معنای بدون محدودیت) :</label><br />
                <input type = "number" name = "course_capacity" dir = "ltr" id = "course_capacity" value = "<?php echo ($edit ? $cource_get_result->capacity : '0'); ?>" required /><Br />

                <label for = "course_status">وضعیت ثبت نام :</label><br />
                <select name="course_status" id="course_status">
                    <option value="1" <?php
                    if ($edit) {
                        if ($cource_get_result->status == 1) {
                            echo 'selected="true"';
                        }
                    } else
                        echo 'selected="true"';
                    ?> >باز</option>
                    <option value="0" <?php
                    if ($edit) {
                        if ($cource_get_result->status == 0) {
                            echo 'selected="true"';
                        }
                    }
                    ?>>بسته</option>
                </select><Br />


                <label for = "course_desc">
                    توضیحات :
                </label><br />
                <textarea name = "course_desc" id = "course_desc"><?php echo ($edit ? $cource_get_result->description : ''); ?></textarea>

                <?php
                if ($edit)
                    echo '<input type="submit" name="edit_course" class="button button-primary" value="بروز رسانی" />';
                else
                    echo '<input type="submit" name="add_course" class="button button-primary" value="ذخیره کردن" />';
                ?>
            </form>
            <?php
            echo '</div>';
        }

        function PS_admin_transactions() {
            global $wpdb;
            echo '<div class="wrap post-shop">';
            echo '<h2>تراکنش ها</h2>';
            include_once PS_INC_DIR . 'class/ps_trans_table.php';
            $ps_trans = new ps_trans_table();
            $ps_trans->prepare_items();
            ?>
            <form method="post">
                <input type="hidden" name="page" value="<?php echo $_GET['page'] ?>">
                <?php
                $ps_trans->search_box('جستجو شماره پیگیری', 's');
                $ps_trans->display();
                echo '</form>';
                echo '</div>';
            }

            function PS_admin_settings() {
                echo ' <div class="wrap post-shop">';
                if (isset($_POST['ps_submit_setting'])) {
                    $options = array(
                        'ps_active_payment' => $_POST['ps_active_payment'],
                        'parspal_merchant_id' => $_POST['parspal_merchant_id'],
                        'parspal_port_password' => $_POST['parspal_port_password'],
                        'payline_api' => $_POST['payline_api'],
                        'zarinpal_merchant_id' => $_POST['zarinpal_merchant_id'],
                        'nextpay_api_key' => $_POST['nextpay_api_key'],
                        'jahanpay_api' => $_POST['jahanpay_api'],
                        'mellat_terminal_id' => $_POST['mellat_terminal_id'],
                        'mellat_username' => $_POST['mellat_username'],
                        'mellat_password' => $_POST['mellat_password'],
                        'relax_number' => $_POST['relax_number'],
                        'relax_username' => $_POST['relax_username'],
                        'relax_password' => $_POST['relax_password'],
                    );
                    update_option('ps_payment', $options);
                    echo '<div class="updated"><p>تنظیمات ذخیره شد.</p></div>';
                } elseif (isset($_POST['ps_msg_setting'])) {

                    $data = array(
                        'ps_msg_name' => $_POST['ps_msg_name'],
                        'ps_msg_sell_course' => $_POST['ps_msg_sell_course'],
                        'ps_msg_sell' => $_POST['ps_msg_sell'],
                        'ps_msg_success_pay' => $_POST['ps_msg_success_pay'],
                        'ps_msg_login' => $_POST['ps_msg_login'],
                        'ps_msg_login_link' => addslashes($_POST['ps_msg_login_link']),
                        'ps_msg_no_sell_post' => $_POST['ps_msg_no_sell_post'],
                        'ps_msg_my_post' => $_POST['ps_msg_my_post'],
                        'ps_msg_expire' => $_POST['ps_msg_expire'],
                        'ps_msg_nologin_sell' => $_POST['ps_msg_nologin_sell'],
                        'ps_msg_no_login_requirement' => $_POST['ps_msg_no_login_requirement'],
                        'ps_msg_full_capacity' => $_POST['ps_msg_full_capacity'],
                        'ps_msg_sended_mail' => $_POST['ps_msg_sended_mail'],
                        'ps_msg_stock_not' => $_POST['ps_msg_stock_not'],
                        'ps_msg_mobile_sms' => $_POST['ps_msg_mobile_sms'],
                    );
                    update_option('ps_message', $data);

                    echo '<div class="updated"><p>تنظیمات ذخیره شد.</p></div>';
                }
                $opt_msg = get_option('ps_message');
                $opt_payment = get_option('ps_payment');
                ?>
                <div class="wrap ps-option-panel">
                    <h2 class="nav-tab-wrapper">
                        <a go="ps-payment" class="nav-tab nav-tab-active">درگاه پرداخت</a>
                        <a go="ps-message" class="nav-tab">پیغام ها</a>
                    </h2>
                    <div class="ps-tab-content" id="ps-payment">
                        <form method="post">
                            <br />
                            <label for="ps_active_payment">درگاه پرداخت فعال : </label>
                            <select id="ps_active_payment" name="ps_active_payment">
                                <option value="0" <?php echo ($opt_payment['ps_active_payment'] === 0) ? 'selected' : ''; ?>>هیچ درگاهی</option>
                                <option value="test_parspal" <?php echo ($opt_payment['ps_active_payment'] === 'test_parspal') ? 'selected' : ''; ?>>درگاه آزمایشی-پارس پال</option>
                                <option value="parspal" <?php echo ($opt_payment['ps_active_payment'] === 'parspal') ? 'selected' : ''; ?>>پارس پال</option>
                                <option value="payline" <?php echo ($opt_payment['ps_active_payment'] === 'payline') ? 'selected' : ''; ?>>پی لاین</option>
                                <option value="zarinpal" <?php echo ($opt_payment['ps_active_payment'] === 'zarinpal') ? 'selected' : ''; ?>>زرین پال</option>
                                <option value="nextpay" <?php echo ($opt_payment['ps_active_payment'] === 'nextpay') ? 'selected' : ''; ?>>نکست پی</option>
                                <option value="jahanpay" <?php echo ($opt_payment['ps_active_payment'] === 'jahanpay') ? 'selected' : ''; ?>>جهان پی</option>
                                <option value="mellat" <?php echo ($opt_payment['ps_active_payment'] === 'mellat') ? 'selected' : ''; ?>>بانک ملت</option>
                            </select>
                            <p class="ps_alert">
                                هشدار : از گزینه پرداخت آزمایشی فقط برای تست پلاگین استفاده کنید،اگه این گزینه فعال باشد کاربران  بدون پرداخت وجه می توانند هر یک از پست ها را خریداری نمایند.
                            </p>

                            <div id="mellat_payment" class="ps_payment_box">
                                <h2>تنظیمات بانک ملت</h2>
                                <div class="section">
                                    <div class="setting-label">
                                        <label for="mellat_terminal_id">شماره پايانه ( terminalId )</label>
                                    </div>
                                    <input type="text" name="mellat_terminal_id" id="mellat_terminal_id" value="<?php echo $opt_payment['mellat_terminal_id']; ?>" />
                                </div>

                                <div class="section">
                                    <div class="setting-label">
                                        <label for="mellat_username">نام کاربری</label>
                                    </div>
                                    <input type="text" name="mellat_username" id="mellat_username" value="<?php echo $opt_payment['mellat_username']; ?>" />
                                </div>


                                <div class="section">
                                    <div class="setting-label">
                                        <label for="mellat_password">رمز عبور</label>
                                    </div>
                                    <input type="text" name="mellat_password" id="mellat_password" value="<?php echo $opt_payment['mellat_password']; ?>" />
                                </div>
                            </div>


                            <div id="parspal_payment" class="ps_payment_box">
                                <h2>تنظیمات پارس پال</h2>
                                <div class="section">
                                    <div class="setting-label"><label for="parspal_merchant_id">شناسه درگاه ( Merchant ID ) پارس پال</label></div>
                                    <input type="text" name="parspal_merchant_id" id="parspal_merchant_id" value="<?php echo $opt_payment['parspal_merchant_id']; ?>" />
                                </div>

                                <div class="section">
                                    <div class="setting-label"><label for="parspal_port_password">کلمه عبور درگاه پارس پال</label></div>
                                    <input type="text" name="parspal_port_password" id="parspal_port_password" value="<?php echo $opt_payment['parspal_port_password']; ?>" />
                                </div>

                            </div>

                            <div id="payline_payment" class="ps_payment_box">
                                <h2>تنظیمات پی لاین</h2>
                                <div class="section">
                                    <div class="setting-label"><label for="payline_api">API پی لاین</label></div>
                                    <input type="text" name="payline_api" id="payline_api" value="<?php echo $opt_payment['payline_api']; ?>" />
                                </div>
                            </div>

                            <div id="zarinpal_payment" class="ps_payment_box">
                                <h2>تنظیمات زرین پال</h2>
                                <div class="section">
                                    <div class="setting-label"><label for="zarinpal_merchant_id">کد دروازه پرداخت زرین پال</label></div>
                                    <input type="text" name="zarinpal_merchant_id" id="zarinpal_merchant_id" value="<?php echo $opt_payment['zarinpal_merchant_id']; ?>" />
                                </div>
                            </div>

                            <div id="nextpay_payment" class="ps_payment_box">
                                <h2>تنظیمات نکست پی</h2>
                                <div class="section">
                                    <div class="setting-label"><label for="nextpay_api_key">کلید مجوزدهی نکست پی</label></div>
                                    <input type="text" name="nextpay_api_key" id="nextpay_api_key" value="<?php echo $opt_payment['nextpay_api_key']; ?>" />
                                </div>
                            </div>


                            <div id="jahapay_payment" class="ps_payment_box">
                                <h2>تنظیمات جهان پی</h2>
                                <div class="section">
                                    <div class="setting-label"><label for="jahanpay_api">API جهان پی</label></div>
                                    <input type="text" name="jahanpay_api" id="jahanpay_api" value="<?php echo $opt_payment['jahanpay_api']; ?>" />
                                </div>
                            </div>


                            <div id="relax_payment" class="ps_payment_box">
                                <h2>پنل sms ریلکس</h2>(با اضافه کردن ویژگی موبایل به شورتکد میتوانید بعد از هر خرید یک sms دریاقت کنید.)
                                <code>[post_shop mobile=09121111111]...[/post_shop]</code>
                                <div class="section">
                                    <div class="setting-label"><label for="relax_number">شماره : </label></div>
                                    <input type="text" name="relax_number" id="relax_number" value="<?php echo $opt_payment['relax_number']; ?>" />
                                </div>

                                <div class="section">
                                    <div class="setting-label"><label for="relax_username">نام کاربری : </label></div>
                                    <input type="text" name="relax_username" id="relax_username" value="<?php echo $opt_payment['relax_username']; ?>" />
                                </div>


                                <div class="section">
                                    <div class="setting-label"><label for="relax_password">پسورد : </label></div>
                                    <input type="text" name="relax_password" id="relax_password" value="<?php echo $opt_payment['relax_password']; ?>" />
                                </div>
                            </div>



                            <p>
                                <input type="submit" name="ps_submit_setting" value="ذخیره" class="button button-primary">
                            </p>
                        </form>
                    </div><!-- end payment setting -->


                    <div class="ps-tab-content" id="ps-message"> 
                        <form action="" method="post">
                            <div class="PS_panel">
                                <table class="ps_msg_table">
                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_name">نام فروشگاه :</label></td>
                                        <td><input name="ps_msg_name" id="ps_msg_name" type="text" class="input" value="<?php echo $opt_msg['ps_msg_name']; ?>"></td>
                                    </tr>

                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_login_link">لینک ورود به سایت:</label></td>
                                        <td><input dir="ltr" name="ps_msg_login_link" id="ps_msg_login_link" type="text" class="input" value="<?php echo $opt_msg['ps_msg_login_link']; ?>"></td>
                                    </tr>


                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_sell">متن "خرید پست " :</label></td>
                                        <td><input name="ps_msg_sell" id="ps_msg_sell" type="text" class="input" value="<?php echo $opt_msg['ps_msg_sell']; ?>"></td>
                                    </tr>

                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_sell_course">متن "خرید دوره " :</label></td>
                                        <td><input name="ps_msg_sell_course" id="ps_msg_sell_course" type="text" class="input" value="<?php echo $opt_msg['ps_msg_sell_course']; ?>"></td>
                                    </tr>

                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_success_pay">متن "خرید موفق" :</label></td>
                                        <td><input name="ps_msg_success_pay" type="text" id="ps_msg_success_pay" class="input" value="<?php echo $opt_msg['ps_msg_success_pay']; ?>"></td>
                                    </tr>

                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_login">متن "ورود به سایت" :</label></td>
                                        <td><input name="ps_msg_login" id="ps_msg_login" type="text" class="input" value="<?php echo $opt_msg['ps_msg_login']; ?>"></td>
                                    </tr>

                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_no_sell_post">متن "شما پستی نخریده اید" :</label></td>
                                        <td><input name="ps_msg_no_sell_post" id="ps_msg_no_sell_post" type="text" class="input" value="<?php echo $opt_msg['ps_msg_no_sell_post']; ?>"></td>
                                    </tr>

                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_my_post">متن "پست های من" :</label></td>
                                        <td><input name="ps_msg_my_post" id="ps_msg_my_post" type="text" class="input" value="<?php echo $opt_msg['ps_msg_my_post']; ?>"></td>
                                    </tr>


                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_expire">متن "تاریخ نمایش این پست به پایان رسیده!" :</label></td>
                                        <td><input name="ps_msg_expire" id="ps_msg_expire" type="text" class="input" value="<?php echo $opt_msg['ps_msg_expire']; ?>"></td>
                                    </tr>

                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_nologin_sell">متن "خرید بدون نیاز به ورود" :</label></td>
                                        <td><input name="ps_msg_nologin_sell" id="ps_msg_nologin_sell" type="text" class="input" value="<?php echo $opt_msg['ps_msg_nologin_sell']; ?>"></td>
                                    </tr>


                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_no_login_requirement">متن "این پست نیازی به ورود به سایت ندارد!" :</label></td>
                                        <td><input name="ps_msg_no_login_requirement" id="ps_msg_no_login_requirement" type="text" class="input" value="<?php echo $opt_msg['ps_msg_no_login_requirement']; ?>"></td>
                                    </tr>


                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_full_capacity">متن "ظرفیت ثبت نام در این دوره تکمیل شده!" :</label></td>
                                        <td><input name="ps_msg_full_capacity" id="ps_msg_full_capacity" type="text" class="input" value="<?php echo $opt_msg['ps_msg_full_capacity']; ?>"></td>
                                    </tr>


                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_sended_mail">متن "محتوای پست به آدرس ایمیلی که وارد کرده بودید ارسال شد." :</label></td>
                                        <td><input name="ps_msg_sended_mail" id="ps_msg_sended_mail" type="text" class="input" value="<?php echo $opt_msg['ps_msg_sended_mail']; ?>"></td>
                                    </tr>



                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_stock_not"> پیغام ناموجود شدن موجودی پست : </label></td>
                                        <td><input name="ps_msg_stock_not" id="ps_msg_stock_no" type="text" class="input" value="<?php echo $opt_msg['ps_msg_stock_not']; ?>"></td>
                                    </tr>


                                    <tr>
                                        <td class="ps_col"><label for="ps_msg_mobile_sms">متنsms اطلاع رسانی به صاحب مقاله : </label></td>
                                        <td><input name="ps_msg_mobile_sms" id="ps_msg_mobile_sms" type="text" class="input" value="<?php echo $opt_msg['ps_msg_mobile_sms']; ?>"></td>
                                        <td>
                                            <p>
                                                {title} عنوان پست
                                            </p>
                                        </td>
                                    </tr>

                                </table>
                                <p>
                                    <input type="submit" name="ps_msg_setting" value="ذخیره" class="button button-primary">
                                </p>
                            </div>
                        </form>
                    </div><!--End message setting -->
                </div>
                <?php
                echo '</div><!-- end post shop wrap -->';
            }

            function PS_admin_help() {
                ?>
                <div class="wrap post-shop">
                    <div class="ps_container">
                        <h2>ارتباط با برنامه نویس</h2>

                        طراحی و برنامه نویسی : <a href="http://iwebpro.ir" target="_blank">بهنام رسولی</a><br />
                        برای ارسال پیشنهادات و گزارش باگ و همچنین سفارشی سازی افزونه می توانید به آدرس <code>bmrbehnam@gmail.com</code> ایمیل بزنید.

                        <hr />
                        <h2>راهنمای استفاده از فروش پست</h2>
                        <ol>
                            <li>به بخش تنظیمات افزونه رفته و اطلاعات مربوط به درگاه پرداخت را وارد کنید.</li>
                            <li>
                                برای فروش پست در قسمت افزودن نوشته بخشی به نام “ post shop-فروش پست” وجود داره که می تونید در این قسمت قیمت ،تاریخ انقضاء پست و همچنین دوره مورد نظر خود را انتخاب کنید.
                            </li>
                            <li>
                                برای به فروش گذاشتن پست شما باید محتوایی رو که قصد دارید بعد از پرداخت کابر مشاهده کند را بین دو شورت کد <code dir="ltr">[post_shop]</code> …. <code dir="ltr">[/post_shop]</code> قرار دهید(با استفاده دکمه اضافه شده به ویرایشگر وردپرس)
                            </li>
                            <li>
                                برای جلوگیری از نمایش تعداد ثبت نام کنندگان در دوره ها کافیست پارامتر <code>count</code> را در شورتکد <code>post_shop</code> برابر hide قرار بدید.<BR/>
                                <code>[post_shop count=hide]...[/post_shop]</code><br />

                            </li>

                        </ol>
                        <hr />
                        <h3>دوره ها</h3>
                        هر پستی را که می خواهید به دوره مورد نظرتان متصل کنید کافیست در هنگام ثبت پست و یا ویرایش از قسمت "فروش پست -post shop" دوره مورد نظر خود را انتخاب نمایید.


                        <br />
                        برای نمایش  دوره ها از شورت کد <code dir="ltr">[ps_course /]</code> استفاده کنید.
                        <Br /> برای عدم نمایش تعداد ثبت نام کنندگان در لیست دوره ها از شورت کد زیر استفاده نمایید :
                        <code>[ps_course count=hide /]</code>


                        <hr />
                        <h3>نمایش پست های خریداری شده هر کاربر</h3>
                        <li>
                            با استفاده از شورتکد زیر میتوانید پست های خریداری شده کاربر جاری به خودش نمایش دهید:

                            <code>[ps_course count=hide /]</code>
                        </li>
                        <li>
                            نمایش پست های خریداری شده کاربر با استفاده از <a href="<?php echo admin_url('/widgets.php'); ?>">ابزارک</a>
                        </li>
                        <li>
                            نمایش پست های خریداری شده کاربر از طریق پنل کاربری :
                            <code><a href="<?php echo ps_get_my_post_link(); ?>">http://yoursite.com/wp-admin/admin.php?page=PS_my_post</a></code>
                        </li>

                        <hr />
                        <h3>پنل  پیامکی ریلکس</h3>

                        (با اضافه کردن ویژگی موبایل به شورتکد میتوانید بعد از هر خرید یک sms دریاقت کنید.)
                        <code>[post_shop mobile=09121111111]...[/post_shop]</code>
                        متن sms هم در قسمت تنظیمات افزونه قابل تغییر است.
                    </div>
                </div>
                <?php
            }
            