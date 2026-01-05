<?php
if (!defined('ABSPATH')) exit;

$srs = Shahkar_Reward_System::get_instance();
?>

<div class="srs-daily-reward">
    <div class="srs-daily-reward__icon">🎁</div>
    <div class="srs-daily-reward__title">پاداش روزانه</div>
    
    <?php if ($can_claim): ?>
        <div class="srs-daily-reward__amount">
            <?php echo number_format(get_option('srs_daily_amount', 1000)); ?> تومان
        </div>
        <button class="srs-btn" id="srs-claim-daily-btn">🎁 دریافت پاداش</button>
    <?php else: ?>
        <div class="srs-daily-reward__amount">✓</div>
        <p>پاداش امروز دریافت شد</p>
        <div class="srs-daily-reward__timer">
            ⏰ پاداش بعدی: <span><?php echo $time_remaining; ?></span>
        </div>
    <?php endif; ?>
</div>