<?php
if (!defined('ABSPATH')) exit;

$total_spent = wc_get_customer_total_spent($user_id);
$total_orders = wc_get_customer_order_count($user_id);
?>

<div class="srs-user-level">
    <div class="srs-user-level__header">
        <span class="srs-user-level__badge"><?php echo $level_badge; ?></span>
        <h2 class="srs-user-level__title"><?php echo $level_label; ?></h2>
        <span class="srs-level-badge srs-level-badge--<?php echo $level; ?>">
            سطح <?php echo $level_label; ?>
        </span>
    </div>
    
    <!-- Progress Bar -->
    <?php if ($progress['next']): ?>
        <div class="srs-level-progress">
            <h3>🎯 پیشرفت تا سطح <?php echo $srs->get_level_label($progress['next']); ?></h3>
            
            <div class="srs-progress-info">
                <span><?php echo $progress['current']; ?> از <?php echo $progress['needed']; ?> سفارش</span>
                <span><?php echo $progress['percent']; ?>%</span>
            </div>
            
            <div class="srs-progress-bar">
                <div class="srs-progress-bar__fill" style="width: <?php echo $progress['percent']; ?>%"></div>
            </div>
            
            <p class="srs-progress-message">
                <?php if ($progress['remaining'] > 0): ?>
                    🎁 تنها <strong><?php echo $progress['remaining']; ?> سفارش</strong> تا ارتقا به سطح <strong><?php echo $srs->get_level_label($progress['next']); ?></strong>!
                <?php else: ?>
                    🎉 تبریک! شما واجد شرایط ارتقا هستید.
                <?php endif; ?>
            </p>
        </div>
    <?php else: ?>
        <div class="srs-level-progress">
            <div class="srs-progress-max">
                <span class="srs-progress-max__icon">🏆</span>
                <h3>تبریک! شما در بالاترین سطح هستید</h3>
                <p>شما یک کاربر حرفه‌ای هستید و از بیشترین مزایا بهره‌مند می‌شوید</p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- آمار کاربر -->
    <div class="srs-user-level__stats">
        <div class="srs-stat-card">
            <div class="srs-stat-card__icon">🛍️</div>
            <div class="srs-stat-card__value"><?php echo number_format($total_orders); ?></div>
            <div class="srs-stat-card__label">تعداد سفارش</div>
        </div>
        
        <div class="srs-stat-card">
            <div class="srs-stat-card__icon">💰</div>
            <div class="srs-stat-card__value"><?php echo number_format($total_spent); ?></div>
            <div class="srs-stat-card__label">مجموع خرید (تومان)</div>
        </div>
        
        <div class="srs-stat-card">
            <div class="srs-stat-card__icon">👥</div>
            <div class="srs-stat-card__value"><?php echo $srs->count_user_referrals($user_id); ?></div>
            <div class="srs-stat-card__label">زیرمجموعه‌ها</div>
        </div>
        
        <div class="srs-stat-card">
            <div class="srs-stat-card__icon">💳</div>
            <div class="srs-stat-card__value"><?php echo number_format($srs->get_wallet_balance($user_id)); ?></div>
            <div class="srs-stat-card__label">موجودی کیف پول</div>
        </div>
    </div>
    
    <!-- مزایای سطح فعلی -->
    <div class="srs-benefits">
        <h3>🎁 مزایای سطح <?php echo $level_label; ?></h3>
        <ul>
            <li>کش‌بک <strong><?php echo get_option("srs_{$level}_cashback"); ?>%</strong> از هر خرید</li>
            <li>پاداش دعوت: <strong><?php echo number_format(get_option("srs_{$level}_invite")); ?> تومان</strong></li>
            <li>پاداش اولین خرید زیرمجموعه: <strong><?php echo number_format(get_option("srs_{$level}_firstbuy")); ?> تومان</strong></li>
            <li>کمیسیون خرید زیرمجموعه: <strong><?php echo get_option("srs_{$level}_ref_purchase"); ?>%</strong></li>
        </ul>
    </div>
    
    <!-- نحوه ارتقا سطح -->
    <div class="srs-upgrade-guide">
        <h3>📈 نحوه ارتقا سطح</h3>
        <div class="srs-upgrade-levels">
            <div class="srs-upgrade-level <?php echo $level == 'normal' ? 'active' : ''; ?>">
                <span class="srs-upgrade-level__badge">🥉</span>
                <h4>کاربر معمولی</h4>
                <p>شروع عضویت</p>
            </div>
            
            <div class="srs-upgrade-arrow">→</div>
            
            <div class="srs-upgrade-level <?php echo $level == 'special' ? 'active' : ''; ?>">
                <span class="srs-upgrade-level__badge">🥈</span>
                <h4>کاربر ویژه</h4>
                <p><?php echo get_option('srs_special_min_orders', 5); ?> سفارش موفق</p>
            </div>
            
            <div class="srs-upgrade-arrow">→</div>
            
            <div class="srs-upgrade-level <?php echo $level == 'pro' ? 'active' : ''; ?>">
                <span class="srs-upgrade-level__badge">🥇</span>
                <h4>کاربر حرفه‌ای</h4>
                <p><?php echo get_option('srs_pro_min_orders', 10); ?> سفارش موفق</p>
            </div>
        </div>
    </div>
</div>

<style>
.srs-level-progress {
    background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 100%);
    padding: 30px;
    border-radius: 16px;
    margin: 30px 0;
    border: 2px solid var(--srs-border);
}

.srs-level-progress h3 {
    margin: 0 0 20px 0;
    color: var(--srs-text);
    font-size: 20px;
}

.srs-progress-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-weight: 600;
    color: var(--srs-text);
}

.srs-progress-bar {
    height: 30px;
    background: #e5e7eb;
    border-radius: 50px;
    overflow: hidden;
    position: relative;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
}

.srs-progress-bar__fill {
    height: 100%;
    background: linear-gradient(90deg, var(--srs-primary) 0%, var(--srs-secondary) 100%);
    border-radius: 50px;
    transition: width 1s ease;
    position: relative;
    overflow: hidden;
}

.srs-progress-bar__fill::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.srs-progress-message {
    margin-top: 15px;
    text-align: center;
    font-size: 16px;
    color: var(--srs-text-light);
}

.srs-progress-max {
    text-align: center;
    padding: 20px;
}

.srs-progress-max__icon {
    font-size: 80px;
    display: block;
    margin-bottom: 15px;
}

.srs-progress-max h3 {
    color: var(--srs-success);
}

.srs-upgrade-guide {
    background: var(--srs-bg);
    padding: 30px;
    border-radius: 16px;
    margin-top: 30px;
}

.srs-upgrade-guide h3 {
    margin: 0 0 25px 0;
}

.srs-upgrade-levels {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 15px;
}

.srs-upgrade-level {
    flex: 1;
    min-width: 150px;
    background: white;
    padding: 25px 20px;
    border-radius: 12px;
    text-align: center;
    border: 2px solid var(--srs-border);
    transition: all 0.3s ease;
}

.srs-upgrade-level.active {
    border-color: var(--srs-primary);
    box-shadow: 0 8px 20px rgba(99, 102, 241, 0.2);
    transform: scale(1.05);
}

.srs-upgrade-level__badge {
    font-size: 50px;
    display: block;
    margin-bottom: 10px;
}

.srs-upgrade-level h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
}

.srs-upgrade-level p {
    margin: 0;
    font-size: 13px;
    color: var(--srs-text-light);
}

.srs-upgrade-arrow {
    font-size: 30px;
    color: var(--srs-text-light);
}

@media (max-width: 768px) {
    .srs-upgrade-levels {
        flex-direction: column;
    }
    
    .srs-upgrade-arrow {
        transform: rotate(90deg);
    }
}
</style>