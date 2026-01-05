<?php
if (!defined('ABSPATH')) exit;

$total_referrals = $srs->count_user_referrals($user_id);
$total_earned = $srs->sum_all_rewards($user_id);
$total_spent = wc_get_customer_total_spent($user_id);
$user_level = $srs->get_user_level($user_id);
$invite_reward = get_option("srs_{$user_level}_invite", 2000);
?>

<div class="srs-referral-stats">
    <h3 class="srs-referral-stats__title">👥 سیستم ارجاع و دعوت دوستان</h3>
    
    <!-- لینک رفرال -->
    <div class="srs-referral-link">
        <div class="srs-referral-link__label">🔗 لینک دعوت شما:</div>
        <div class="srs-referral-link__url" id="srs-ref-link"><?php echo esc_url($referral_link); ?></div>
        <button class="srs-copy-btn" onclick="srsCopyRefLink()">📋 کپی</button>
    </div>

    <!-- آمار -->
    <div class="srs-referral-summary">
        <div class="srs-ref-stat">
            <div class="srs-ref-stat__icon">👥</div>
            <div class="srs-ref-stat__value"><?php echo number_format($total_referrals); ?></div>
            <div class="srs-ref-stat__label">تعداد زیرمجموعه</div>
        </div>

        <div class="srs-ref-stat">
            <div class="srs-ref-stat__icon">💰</div>
            <div class="srs-ref-stat__value"><?php echo number_format($total_earned); ?></div>
            <div class="srs-ref-stat__label">کل پاداش‌ها (تومان)</div>
        </div>

        <div class="srs-ref-stat">
            <div class="srs-ref-stat__icon">🛍️</div>
            <div class="srs-ref-stat__value"><?php echo number_format($total_spent); ?></div>
            <div class="srs-ref-stat__label">مجموع خرید (تومان)</div>
        </div>

        <div class="srs-ref-stat">
            <div class="srs-ref-stat__icon">🎁</div>
            <div class="srs-ref-stat__value"><?php echo number_format($invite_reward); ?></div>
            <div class="srs-ref-stat__label">پاداش هر دعوت (تومان)</div>
        </div>
    </div>

    <!-- لیست زیرمجموعه‌ها -->
    <?php if (!empty($referrals)): ?>
        <div class="srs-referrals-list">
            <h4>📋 افراد دعوت‌شده (<?php echo count($referrals); ?> نفر)</h4>
            
            <table class="srs-referrals-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>نام و نام خانوادگی</th>
                        <th>شماره تماس</th>
                        <th>پاداش دریافتی</th>
                        <th>تاریخ عضویت</th>
                        <th>وضعیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $admin_lang = get_option('Shahkar_admin_selected_lang', 'fa');
                    
                    foreach ($referrals as $index => $ref): 
                        $timestamp = intval($ref->vr_date);
                        
                        if ($admin_lang == 'fa' && class_exists('ShahkarJdate\jdate')) {
                            $date_str = ShahkarJdate\jdate("Y/m/d", $timestamp, '', '', 'en');
                        } else {
                            $date_str = date('Y/m/d', $timestamp);
                        }
                        
                        $order_count = wc_get_customer_order_count($ref->vr_current_user);
                        $has_order = $order_count > 0;
                    ?>
                        <tr>
                            <td><strong><?php echo $index + 1; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($ref->full_name); ?></strong>
                                <?php if (!empty($ref->user_email)): ?>
                                    <br><small style="color: #666;"><?php echo esc_html($ref->user_email); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($ref->phone_number); ?></td>
                            <td><strong style="color: #10b981;"><?php echo number_format($ref->vr_reward_amount); ?> تومان</strong></td>
                            <td><?php echo $date_str; ?></td>
                            <td>
                                <?php if ($has_order): ?>
                                    <span class="srs-badge srs-badge--success">✓ خریدار (<?php echo $order_count; ?> سفارش)</span>
                                <?php else: ?>
                                    <span class="srs-badge srs-badge--warning">⏳ در انتظار خرید</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="srs-empty-state">
            <div class="srs-empty-state__icon">👥</div>
            <p class="srs-empty-state__text">هنوز کسی را دعوت نکرده‌اید</p>
            <p style="color: #666; margin-top: 10px;">لینک دعوت خود را با دوستان به اشتراک بگذارید!</p>
        </div>
    <?php endif; ?>
</div>

<script>
function srsCopyRefLink() {
    var link = document.getElementById('srs-ref-link').textContent.trim();
    
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(function() {
            showCopySuccess();
        }).catch(function() {
            fallbackCopy(link);
        });
    } else {
        fallbackCopy(link);
    }
}

function fallbackCopy(text) {
    var temp = document.createElement('textarea');
    temp.value = text;
    temp.style.position = 'fixed';
    temp.style.opacity = '0';
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    showCopySuccess();
}

function showCopySuccess() {
    var btn = document.querySelector('.srs-copy-btn');
    var original = btn.innerHTML;
    btn.innerHTML = '✓ کپی شد!';
    btn.style.background = '#10b981';
    
    setTimeout(function() {
        btn.innerHTML = original;
        btn.style.background = '';
    }, 2000);
}
</script>