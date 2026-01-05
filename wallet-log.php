<?php
if (!defined('ABSPATH')) exit;

$srs = Shahkar_Reward_System::get_instance();
$balance = $srs->get_wallet_balance($user_id);
$total_spent = wc_get_customer_total_spent($user_id);
?>

<div class="srs-wallet-log">
    <div class="srs-wallet-log__header">
        <h3 class="srs-wallet-log__title">💰 تاریخچه تراکنش‌ها</h3>
        <div class="srs-wallet-stats">
            <div class="srs-stat-item">
                <span>موجودی کیف پول:</span>
                <strong><?php echo number_format($balance); ?> تومان</strong>
            </div>
            <div class="srs-stat-item">
                <span>مجموع خرید:</span>
                <strong><?php echo number_format($total_spent); ?> تومان</strong>
            </div>
        </div>
    </div>
    
    <?php if (!empty($transactions)): ?>
        <div class="srs-transactions-table-wrapper">
            <table class="srs-transactions-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>مبلغ تراکنش</th>
                        <th>تاریخ تراکنش</th>
                        <th>نوع تراکنش</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $admin_lang = get_option('Shahkar_admin_selected_lang', 'fa');
                    
                    foreach ($transactions as $index => $trans): 
                        // تعیین نوع و توضیحات
                        if (isset($trans->source) && $trans->source == 'referral') {
                            $types = [
                                0 => 'دعوت از دوستان',
                                1 => 'اولین خرید زیرمجموعه',
                                2 => 'پاداش خرید زیرمجموعه'
                            ];
                            $desc = isset($types[$trans->ref_type]) ? $types[$trans->ref_type] : 'پاداش رفرال';
                            if (!empty($trans->ref_name)) {
                                $desc .= ' - ' . $trans->ref_name;
                            }
                            $is_credit = true;
                            
                        } elseif (isset($trans->source) && $trans->source == 'wallet') {
                            $is_credit = ($trans->status == 1);
                            $desc = !empty($trans->description) ? $trans->description : ($is_credit ? 'افزایش اعتبار' : 'کسر اعتبار');
                            
                        } elseif (isset($trans->source) && $trans->source == 'daily') {
                            $desc = sprintf('پاداش روزانه - روز %d پیاپی', $trans->streak_days ?? 1);
                            $is_credit = true;
                        } else {
                            $desc = 'تراکنش';
                            $is_credit = true;
                        }
                        
                        // تاریخ
                        $timestamp = isset($trans->date) ? intval($trans->date) : time();
                        
                        if ($admin_lang == 'fa' && class_exists('ShahkarJdate\jdate')) {
                            $date_str = ShahkarJdate\jdate("Y/m/d", $timestamp, '', '', 'en');
                        } else {
                            $date_str = date('Y/m/d', $timestamp);
                        }
                        
                        // مبلغ
                        $amount = isset($trans->amount) ? floatval($trans->amount) : 0;
                    ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td class="<?php echo $is_credit ? 'srs-credit' : 'srs-debit'; ?>">
                                <?php echo number_format($amount); ?> تومان
                            </td>
                            <td><?php echo $date_str; ?></td>
                            <td><?php echo esc_html($desc); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="srs-empty-state">
            <div class="srs-empty-state__icon">📭</div>
            <p>هنوز تراکنشی ثبت نشده است</p>
        </div>
    <?php endif; ?>
</div>