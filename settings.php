<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1>⚙️ تنظیمات سیستم پاداش</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('srs_settings'); ?>
        
        <!-- کلی -->
        <h2>🔧 تنظیمات کلی</h2>
        <table class="form-table">
            <tr>
                <th>فعال‌سازی سیستم</th>
                <td>
                    <label>
                        <input type="checkbox" name="srs_enabled" value="1" <?php checked(get_option('srs_enabled'), '1'); ?>>
                        فعال
                    </label>
                </td>
            </tr>
            <tr>
                <th>صفحه لینک رفرال</th>
                <td>
                    <input type="text" name="srs_referral_page" value="<?php echo esc_attr(get_option('srs_referral_page', '/panel/')); ?>" class="regular-text">
                    <p class="description">مثال: /panel/ یا /register/</p>
                </td>
            </tr>
        </table>
        
        <!-- روزانه -->
        <h2>🎁 پاداش روزانه</h2>
        <table class="form-table">
            <tr>
                <th>فعال‌سازی</th>
                <td>
                    <input type="checkbox" name="srs_daily_enabled" value="1" <?php checked(get_option('srs_daily_enabled'), '1'); ?>>
                </td>
            </tr>
            <tr>
                <th>مبلغ پاداش (تومان)</th>
                <td>
                    <input type="number" name="srs_daily_amount" value="<?php echo esc_attr(get_option('srs_daily_amount', '1000')); ?>">
                </td>
            </tr>
        </table>
        
        <!-- سطح‌بندی -->
        <h2>🎖️ تنظیمات سطح‌بندی</h2>
        <table class="form-table">
            <tr>
                <th>حداقل سفارش برای سطح ویژه</th>
                <td>
                    <input type="number" name="srs_special_min_orders" value="<?php echo esc_attr(get_option('srs_special_min_orders', '5')); ?>">
                    <p class="description">تعداد سفارش مورد نیاز برای ارتقا به کاربر ویژه</p>
                </td>
            </tr>
            <tr>
                <th>حداقل سفارش برای سطح حرفه‌ای</th>
                <td>
                    <input type="number" name="srs_pro_min_orders" value="<?php echo esc_attr(get_option('srs_pro_min_orders', '10')); ?>">
                    <p class="description">تعداد سفارش مورد نیاز برای ارتقا به کاربر حرفه‌ای</p>
                </td>
            </tr>
        </table>
        
        <!-- معمولی -->
        <h2>🥉 سطح معمولی</h2>
        <table class="form-table">
            <tr>
                <th>درصد کش‌بک (%)</th>
                <td><input type="number" step="0.5" name="srs_normal_cashback" value="<?php echo esc_attr(get_option('srs_normal_cashback', '10')); ?>"></td>
            </tr>
            <tr>
                <th>پاداش دعوت (تومان)</th>
                <td><input type="number" name="srs_normal_invite" value="<?php echo esc_attr(get_option('srs_normal_invite', '2000')); ?>"></td>
            </tr>
            <tr>
                <th>پاداش اولین خرید (تومان)</th>
                <td><input type="number" name="srs_normal_firstbuy" value="<?php echo esc_attr(get_option('srs_normal_firstbuy', '5000')); ?>"></td>
            </tr>
            <tr>
                <th>درصد خرید زیرمجموعه (%)</th>
                <td><input type="number" step="0.5" name="srs_normal_ref_purchase" value="<?php echo esc_attr(get_option('srs_normal_ref_purchase', '3')); ?>"></td>
            </tr>
        </table>
        
        <!-- ویژه -->
        <h2>🥈 سطح ویژه</h2>
        <table class="form-table">
            <tr>
                <th>درصد کش‌بک (%)</th>
                <td><input type="number" step="0.5" name="srs_special_cashback" value="<?php echo esc_attr(get_option('srs_special_cashback', '15')); ?>"></td>
            </tr>
            <tr>
                <th>پاداش دعوت (تومان)</th>
                <td><input type="number" name="srs_special_invite" value="<?php echo esc_attr(get_option('srs_special_invite', '3000')); ?>"></td>
            </tr>
            <tr>
                <th>پاداش اولین خرید (تومان)</th>
                <td><input type="number" name="srs_special_firstbuy" value="<?php echo esc_attr(get_option('srs_special_firstbuy', '7000')); ?>"></td>
            </tr>
            <tr>
                <th>درصد خرید زیرمجموعه (%)</th>
                <td><input type="number" step="0.5" name="srs_special_ref_purchase" value="<?php echo esc_attr(get_option('srs_special_ref_purchase', '5')); ?>"></td>
            </tr>
        </table>
        
        <!-- حرفه‌ای -->
        <h2>🥇 سطح حرفه‌ای</h2>
        <table class="form-table">
            <tr>
                <th>درصد کش‌بک (%)</th>
                <td><input type="number" step="0.5" name="srs_pro_cashback" value="<?php echo esc_attr(get_option('srs_pro_cashback', '20')); ?>"></td>
            </tr>
            <tr>
                <th>پاداش دعوت (تومان)</th>
                <td><input type="number" name="srs_pro_invite" value="<?php echo esc_attr(get_option('srs_pro_invite', '5000')); ?>"></td>
            </tr>
            <tr>
                <th>پاداش اولین خرید (تومان)</th>
                <td><input type="number" name="srs_pro_firstbuy" value="<?php echo esc_attr(get_option('srs_pro_firstbuy', '10000')); ?>"></td>
            </tr>
            <tr>
                <th>درصد خرید زیرمجموعه (%)</th>
                <td><input type="number" step="0.5" name="srs_pro_ref_purchase" value="<?php echo esc_attr(get_option('srs_pro_ref_purchase', '7')); ?>"></td>
            </tr>
        </table>
        
        <!-- بونوس خرید -->
        <h2>🎯 بونوس خریدهای اول، دوم، سوم</h2>
        <table class="form-table">
            <tr>
                <th>بونوس خرید اول (%)</th>
                <td><input type="number" step="0.5" name="srs_first_purchase_bonus" value="<?php echo esc_attr(get_option('srs_first_purchase_bonus', '20')); ?>"></td>
            </tr>
            <tr>
                <th>بونوس خرید دوم (%)</th>
                <td><input type="number" step="0.5" name="srs_second_purchase_bonus" value="<?php echo esc_attr(get_option('srs_second_purchase_bonus', '15')); ?>"></td>
            </tr>
            <tr>
                <th>بونوس خرید سوم (%)</th>
                <td><input type="number" step="0.5" name="srs_third_purchase_bonus" value="<?php echo esc_attr(get_option('srs_third_purchase_bonus', '25')); ?>"></td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" name="srs_save_settings" class="button button-primary button-large">
                💾 ذخیره تنظیمات</button>
        </p>
    </form>
</div>