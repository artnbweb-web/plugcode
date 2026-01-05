<?php
/**
 * Plugin Name: سیستم پاداش و کیف پول شاهکار
 * Version: 3.1.0
 */

if (!defined('ABSPATH')) exit;

define('SRS_VERSION', '3.1.0');
define('SRS_PATH', plugin_dir_path(__FILE__));
define('SRS_URL', plugin_dir_url(__FILE__));

class Shahkar_Reward_System {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('plugins_loaded', [$this, 'init'], 20);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('init', [$this, 'handle_referral_link'], 1);
    }
    
    public function handle_referral_link() {
        if (isset($_GET['ref']) && !empty($_GET['ref'])) {
            $ref_code = sanitize_text_field($_GET['ref']);
            setcookie('shahkar_ref_code', $ref_code, time() + (86400 * 30), '/');
        }
    }
    
    public function activate() {
        $this->create_tables();
        $this->set_default_options();
    }
    
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول پاداش روزانه (اصلاح شده)
        $table_daily = $wpdb->prefix . 'srs_daily_rewards';
        $sql_daily = "CREATE TABLE IF NOT EXISTS $table_daily (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            claimed_date date NOT NULL,
            amount decimal(10,2) NOT NULL,
            streak_days int(11) DEFAULT 1,
            date int(11) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_date (user_id, claimed_date),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // جدول سطح کاربران
        $table_levels = $wpdb->prefix . 'srs_user_levels';
        $sql_levels = "CREATE TABLE IF NOT EXISTS $table_levels (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            level varchar(50) DEFAULT 'normal',
            total_spent decimal(10,2) DEFAULT 0,
            total_orders int(11) DEFAULT 0,
            referral_count int(11) DEFAULT 0,
            updated_at int(11) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_daily);
        dbDelta($sql_levels);
    }
    
    private function set_default_options() {
        $defaults = [
            'srs_enabled' => '1',
            'srs_daily_enabled' => '1',
            'srs_daily_amount' => '1000',
            
            // سطح‌بندی بر اساس تعداد سفارش
            'srs_special_min_orders' => '5',
            'srs_pro_min_orders' => '10',
            
            // درصدها
            'srs_normal_cashback' => '10',
            'srs_normal_invite' => '2000',
            'srs_normal_firstbuy' => '5000',
            'srs_normal_ref_purchase' => '3',
            
            'srs_special_cashback' => '15',
            'srs_special_invite' => '3000',
            'srs_special_firstbuy' => '7000',
            'srs_special_ref_purchase' => '5',
            
            'srs_pro_cashback' => '20',
            'srs_pro_invite' => '5000',
            'srs_pro_firstbuy' => '10000',
            'srs_pro_ref_purchase' => '7',
            
            'srs_first_purchase_bonus' => '20',
            'srs_second_purchase_bonus' => '15',
            'srs_third_purchase_bonus' => '25',
            
            'srs_referral_page' => '/panel/',
        ];
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
    
    public function init() {
        add_shortcode('srs_daily_reward', [$this, 'daily_reward_shortcode']);
        add_shortcode('srs_wallet_log', [$this, 'wallet_log_shortcode']);
        add_shortcode('srs_user_level', [$this, 'user_level_shortcode']);
        add_shortcode('srs_referral_stats', [$this, 'referral_stats_shortcode']);
        
        add_action('woocommerce_order_status_completed', [$this, 'process_order_cashback'], 10, 1);
        add_action('woocommerce_order_status_completed', [$this, 'process_referral_reward'], 20, 1);
        add_action('woocommerce_order_status_completed', [$this, 'update_user_level'], 30, 1);
        
        add_action('wp_ajax_srs_claim_daily', [$this, 'ajax_claim_daily']);
    }
    
    public function admin_menu() {
        add_menu_page('سیستم پاداش', 'سیستم پاداش', 'manage_options', 'shahkar-reward', [$this, 'admin_page'], 'dashicons-awards', 56);
        add_submenu_page('shahkar-reward', 'تنظیمات', 'تنظیمات', 'manage_options', 'shahkar-reward-settings', [$this, 'settings_page']);
        add_submenu_page('shahkar-reward', 'تراکنش‌ها', 'تراکنش‌ها', 'manage_options', 'shahkar-reward-transactions', [$this, 'transactions_page']);
    }
    
    public function admin_assets($hook) {
        if (strpos($hook, 'shahkar-reward') !== false) {
            wp_enqueue_style('srs-admin', SRS_URL . 'assets/css/style.css', [], SRS_VERSION);
        }
    }
    
    public function frontend_assets() {
        wp_enqueue_style('srs-frontend', SRS_URL . 'assets/css/style.css', [], SRS_VERSION);
        
        if (file_exists(SRS_PATH . 'assets/js/frontend.js')) {
            wp_enqueue_script('srs-frontend-js', SRS_URL . 'assets/js/frontend.js', ['jquery'], SRS_VERSION, true);
            wp_localize_script('srs-frontend-js', 'srsData', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('srs_nonce')
            ]);
        }
    }
    
    public function settings_page() {
        if (isset($_POST['srs_save_settings']) && check_admin_referer('srs_settings')) {
            $this->save_settings();
            echo '<div class="notice notice-success"><p>✓ تنظیمات ذخیره شد.</p></div>';
        }
        
        $template = SRS_PATH . 'templates/admin/settings.php';
        if (file_exists($template)) include $template;
    }
    
    private function save_settings() {
        $fields = [
            'srs_enabled', 'srs_daily_enabled', 'srs_daily_amount',
            'srs_normal_cashback', 'srs_normal_invite', 'srs_normal_firstbuy', 'srs_normal_ref_purchase',
            'srs_special_cashback', 'srs_special_invite', 'srs_special_firstbuy', 'srs_special_ref_purchase',
            'srs_special_min_orders',
            'srs_pro_cashback', 'srs_pro_invite', 'srs_pro_firstbuy', 'srs_pro_ref_purchase',
            'srs_pro_min_orders',
            'srs_first_purchase_bonus', 'srs_second_purchase_bonus', 'srs_third_purchase_bonus',
            'srs_referral_page'
        ];
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_text_field($_POST[$field]));
            }
        }
    }
    
    public function transactions_page() {
        global $wpdb;
        
        $transactions = $wpdb->get_results("
            SELECT t.*, u1.display_name as user_name, u2.display_name as ref_user_name
            FROM Shahkar_referral_trx t
            LEFT JOIN {$wpdb->users} u1 ON t.vr_inviter_user = u1.ID
            LEFT JOIN {$wpdb->users} u2 ON t.vr_current_user = u2.ID
            ORDER BY t.vr_id DESC LIMIT 100
        ");
        
        $template = SRS_PATH . 'templates/admin/transactions.php';
        if (file_exists($template)) include $template;
    }
    
    // ====================
    // کش‌بک
    // ====================
    
    public function process_order_cashback($order_id) {
        if (!get_option('srs_enabled')) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        if (get_post_meta($order_id, '_srs_cashback_processed', true)) return;
        
        $user_level = $this->get_user_level($user_id);
        $order_total = floatval($order->get_total());
        
        $cashback_percent = floatval(get_option("srs_{$user_level}_cashback", 10));
        $cashback_amount = ($order_total * $cashback_percent) / 100;
        
        $order_count = wc_get_customer_order_count($user_id);
        
        if ($order_count == 1) {
            $bonus = ($order_total * floatval(get_option('srs_first_purchase_bonus', 0))) / 100;
            $cashback_amount += $bonus;
        } elseif ($order_count == 2) {
            $bonus = ($order_total * floatval(get_option('srs_second_purchase_bonus', 0))) / 100;
            $cashback_amount += $bonus;
        } elseif ($order_count == 3) {
            $bonus = ($order_total * floatval(get_option('srs_third_purchase_bonus', 0))) / 100;
            $cashback_amount += $bonus;
        }
        
        if ($cashback_amount > 0) {
            $this->add_wallet_balance($user_id, $cashback_amount, sprintf('کش‌بک %s%% - سفارش #%d', $cashback_percent, $order_id));
            update_post_meta($order_id, '_srs_cashback_processed', '1');
        }
    }
    
    // ====================
    // رفرال
    // ====================
    
    public function process_referral_reward($order_id) {
        if (!get_option('srs_enabled')) return;
        
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        global $wpdb;
        
        $referrer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT vr_inviter_user FROM Shahkar_referral_log WHERE vr_current_user = %d LIMIT 1",
            $user_id
        ));
        
        if (!$referrer_id) return;
        if (get_post_meta($order_id, '_srs_referral_processed', true)) return;
        
        $referrer_level = $this->get_user_level($referrer_id);
        $order_total = floatval($order->get_total());
        $is_first = wc_get_customer_order_count($user_id) == 1;
        
        if ($is_first) {
            $reward = floatval(get_option("srs_{$referrer_level}_firstbuy", 5000));
            
            $wpdb->insert('Shahkar_referral_trx', [
                'vr_inviter_user' => $referrer_id,
                'vr_current_user' => $user_id,
                'vr_amount' => $reward,
                'vr_type' => 1,
                'vr_date' => time()
            ]);
            
            $this->add_wallet_balance($referrer_id, $reward, sprintf('اولین خرید زیرمجموعه #%d', $order_id));
        }
        
        $percent = floatval(get_option("srs_{$referrer_level}_ref_purchase", 3));
        $reward = ($order_total * $percent) / 100;
        
        if ($reward > 0) {
            $wpdb->insert('Shahkar_referral_trx', [
                'vr_inviter_user' => $referrer_id,
                'vr_current_user' => $user_id,
                'vr_amount' => $reward,
                'vr_type' => 2,
                'vr_date' => time()
            ]);
            
            $this->add_wallet_balance($referrer_id, $reward, sprintf('پاداش %s%% خرید #%d', $percent, $order_id));
        }
        
        update_post_meta($order_id, '_srs_referral_processed', '1');
    }
    
    // ====================
    // سطح (فقط بر اساس تعداد سفارش)
    // ====================
    
    public function update_user_level($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $user_id = $order->get_user_id();
        if (!$user_id) return;
        
        global $wpdb;
        $table = $wpdb->prefix . 'srs_user_levels';
        
        $total_spent = wc_get_customer_total_spent($user_id);
        $total_orders = wc_get_customer_order_count($user_id);
        $referral_count = $this->count_user_referrals($user_id);
        
        // تعیین سطح فقط بر اساس تعداد سفارش
        $new_level = 'normal';
        
        $pro_min = intval(get_option('srs_pro_min_orders', 10));
        $special_min = intval(get_option('srs_special_min_orders', 5));
        
        if ($total_orders >= $pro_min) {
            $new_level = 'pro';
        } elseif ($total_orders >= $special_min) {
            $new_level = 'special';
        }
        
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (user_id, level, total_spent, total_orders, referral_count, updated_at) 
             VALUES (%d, %s, %f, %d, %d, %d)
             ON DUPLICATE KEY UPDATE 
             level = %s, total_spent = %f, total_orders = %d, referral_count = %d, updated_at = %d",
            $user_id, $new_level, $total_spent, $total_orders, $referral_count, time(),
            $new_level, $total_spent, $total_orders, $referral_count, time()
        ));
    }
    
    // ====================
    // پاداش روزانه
    // ====================
    
    public function ajax_claim_daily() {
        check_ajax_referer('srs_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'لطفا وارد شوید']);
        }
        
        $result = $this->claim_daily_reward(get_current_user_id());
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    public function claim_daily_reward($user_id) {
        if (!get_option('srs_daily_enabled')) {
            return ['success' => false, 'message' => 'غیرفعال است'];
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'srs_daily_rewards';
        $today = current_time('Y-m-d');
        
        $key = 'srs_daily_' . $user_id . '_' . str_replace('-', '', $today);
        if (get_transient($key)) {
            return ['success' => false, 'message' => 'در حال پردازش'];
        }
        set_transient($key, 1, 30);
        
        $claimed = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND claimed_date = %s",
            $user_id, $today
        ));
        
        if ($claimed) {
            delete_transient($key);
            return ['success' => false, 'message' => 'امروز دریافت کرده‌اید'];
        }
        
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $last = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY claimed_date DESC LIMIT 1",
            $user_id
        ));
        
        $streak = 1;
        if ($last && $last->claimed_date == $yesterday) {
            $streak = intval($last->streak_days) + 1;
        }
        
        $amount = floatval(get_option('srs_daily_amount', 1000));
        if ($streak >= 7) $amount *= 1.5;
        
        $now = time();
        
        $result = $wpdb->insert($table, [
            'user_id' => $user_id,
            'claimed_date' => $today,
            'amount' => $amount,
            'streak_days' => $streak,
            'date' => $now
        ], ['%d', '%s', '%f', '%d', '%d']);
        
        if (!$result) {
            delete_transient($key);
            return ['success' => false, 'message' => 'خطا در ثبت'];
        }
        
        if (!$this->add_wallet_balance($user_id, $amount, sprintf('پاداش روزانه - روز %d', $streak))) {
            $wpdb->delete($table, ['user_id' => $user_id, 'claimed_date' => $today]);
            delete_transient($key);
            return ['success' => false, 'message' => 'خطا در کیف پول'];
        }
        
        set_transient($key, 1, 86400);
        
        return [
            'success' => true,
            'message' => sprintf('✓ پاداش %s تومان دریافت شد', number_format($amount)),
            'amount' => $amount,
            'streak' => $streak
        ];
    }
    
    public function can_claim_daily($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'srs_daily_rewards';
        $today = current_time('Y-m-d');
        
        return !$wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE user_id = %d AND claimed_date = %s",
            $user_id, $today
        ));
    }
    
    public function get_daily_time_remaining() {
        $now = current_time('timestamp');
        $tomorrow = strtotime('tomorrow midnight');
        $diff = $tomorrow - $now;
        
        return sprintf('%02d:%02d', floor($diff / 3600), floor(($diff % 3600) / 60));
    }
    
    // ====================
    // کیف پول
    // ====================
    
    public function add_wallet_balance($user_id, $amount, $description = '') {
        global $wpdb;
        
        $amount = floatval($amount);
        if ($amount <= 0) return false;
        
        $current = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT vw_balance FROM Shahkar_wallet WHERE vw_uid = %d",
            $user_id
        )));
        
        $new_balance = $current + $amount;
        
        if ($current > 0) {
            $wpdb->update('Shahkar_wallet', ['vw_balance' => $new_balance], ['vw_uid' => $user_id], ['%f'], ['%d']);
        } else {
            $wpdb->insert('Shahkar_wallet', ['vw_uid' => $user_id, 'vw_balance' => $new_balance], ['%d', '%f']);
        }
        
        $wpdb->insert('Shahkar_wallet_logs', [
            'vwl_uid' => $user_id,
            'vwl_for' => $description,
            'vwl_time' => time(),
            'vwl_amount' => $amount,
            'vwl_status' => 1
        ], ['%d', '%s', '%d', '%f', '%d']);
        
        return true;
    }
    
    public function get_wallet_balance($user_id) {
        global $wpdb;
        return floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(vw_balance, 0) FROM Shahkar_wallet WHERE vw_uid = %d",
            $user_id
        )));
    }
    
    public function get_all_transactions($user_id) {
        global $wpdb;
        
        $referral = $wpdb->get_results($wpdb->prepare(
            "SELECT vr_id as id, vr_amount as amount, vr_type as ref_type, vr_date as date,
                    'referral' as source,
                    (SELECT display_name FROM {$wpdb->users} WHERE ID = vr_current_user) as ref_name
             FROM Shahkar_referral_trx WHERE vr_inviter_user = %d",
            $user_id
        ));
        
        $wallet = $wpdb->get_results($wpdb->prepare(
            "SELECT vwl_id as id, vwl_amount as amount, vwl_status as status, vwl_time as date,
                    vwl_for as description, 'wallet' as source
             FROM Shahkar_wallet_logs WHERE vwl_uid = %d",
            $user_id
        ));
        
        $daily_table = $wpdb->prefix . 'srs_daily_rewards';
        $daily = $wpdb->get_results($wpdb->prepare(
            "SELECT id, amount, date, streak_days, 'daily' as source FROM $daily_table WHERE user_id = %d",
            $user_id
        ));
        
        $all = array_merge($referral ?: [], $wallet ?: [], $daily ?: []);
        
        usort($all, function($a, $b) {
            return intval($b->date) - intval($a->date);
        });
        
        return $all;
    }
    
    // ====================
    // رفرال
    // ====================
    
    public function register_referral($user_id, $ref_code) {
        global $wpdb;
        
        $referrer_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'ref_code' AND meta_value = %s",
            $ref_code
        ));
        
        if (!$referrer_id || $referrer_id == $user_id) return;
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT vr_id FROM Shahkar_referral_log WHERE vr_current_user = %d",
            $user_id
        ));
        
        if ($exists) return;
        
        $level = $this->get_user_level($referrer_id);
        $reward = floatval(get_option("srs_{$level}_invite", 2000));
        
        $wpdb->insert('Shahkar_referral_log', [
            'vr_inviter_user' => $referrer_id,
            'vr_current_user' => $user_id,
            'vr_reward_amount' => $reward,
            'vr_date' => time()
        ]);
        
        if ($reward > 0) {
            $wpdb->insert('Shahkar_referral_trx', [
                'vr_inviter_user' => $referrer_id,
                'vr_current_user' => $user_id,
                'vr_amount' => $reward,
                'vr_type' => 0,
                'vr_date' => time()
            ]);
            
            $this->add_wallet_balance($referrer_id, $reward, 'دعوت: ' . get_userdata($user_id)->display_name);
        }
    }
    
    public function count_user_referrals($user_id) {
        global $wpdb;
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM Shahkar_referral_log WHERE vr_inviter_user = %d",
            $user_id
        )));
    }
    
    public function get_user_referrals($user_id) {
        global $wpdb;
        
        $refs = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name, u.user_email
             FROM Shahkar_referral_log l
             LEFT JOIN {$wpdb->users} u ON l.vr_current_user = u.ID
             WHERE l.vr_inviter_user = %d
             ORDER BY l.vr_id DESC",
            $user_id
        ));
        
        if (!$refs) return [];
        
        foreach ($refs as $ref) {
            $uid = $ref->vr_current_user;
            
            $ref->first_name = get_user_meta($uid, 'first_name', true);
            $ref->last_name = get_user_meta($uid, 'last_name', true);
            $ref->full_name = trim($ref->first_name . ' ' . $ref->last_name) ?: $ref->display_name;
            
            $ref->phone_number = $wpdb->get_var($wpdb->prepare(
                "SELECT vp_unumber FROM Shahkar_users_profile WHERE vp_uid = %d LIMIT 1", $uid
            )) ?: '-';
            
            $ref->vr_reward_amount = floatval($wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(vr_amount), 0) FROM Shahkar_referral_trx 
                 WHERE vr_current_user = %d AND vr_inviter_user = %d",
                $uid, $user_id
            )));
        }
        
        return $refs;
    }
    
    public function sum_all_rewards($user_id) {
        global $wpdb;
        
        $ref = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(vr_amount), 0) FROM Shahkar_referral_trx WHERE vr_inviter_user = %d",
            $user_id
        )));
        
        $wallet = floatval($wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(vwl_amount), 0) FROM Shahkar_wallet_logs WHERE vwl_uid = %d AND vwl_status = 1",
            $user_id
        )));
        
        return $ref + $wallet;
    }
    
    // ====================
    // سطح
    // ====================
    
    public function get_user_level($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'srs_user_levels';
        
        $level = $wpdb->get_var($wpdb->prepare("SELECT level FROM $table WHERE user_id = %d", $user_id));
        return $level ?: 'normal';
    }
    
    public function get_level_label($level) {
        return ['normal' => 'معمولی', 'special' => 'ویژه', 'pro' => 'حرفه‌ای'][$level] ?? 'معمولی';
    }
    
    public function get_level_badge($level) {
        return ['normal' => '🥉', 'special' => '🥈', 'pro' => '🥇'][$level] ?? '🎖️';
    }
    
    public function get_level_progress($user_id) {
        $current_level = $this->get_user_level($user_id);
        $total_orders = wc_get_customer_order_count($user_id);
        
        $special_min = intval(get_option('srs_special_min_orders', 5));
        $pro_min = intval(get_option('srs_pro_min_orders', 10));
        
        if ($current_level == 'normal') {
            $next_level = 'special';
            $needed = $special_min;
        } elseif ($current_level == 'special') {
            $next_level = 'pro';
            $needed = $pro_min;
        } else {
            return ['next' => null, 'current' => $total_orders, 'needed' => $pro_min, 'remaining' => 0, 'percent' => 100];
        }
        
        $remaining = max(0, $needed - $total_orders);
        $percent = min(100, ($total_orders / $needed) * 100);
        
        return [
            'next' => $next_level,
            'current' => $total_orders,
            'needed' => $needed,
            'remaining' => $remaining,
            'percent' => round($percent, 1)
        ];
    }
    
    public function generate_custom_ref_code($user_id) {
        $code = get_user_meta($user_id, 'ref_code', true);
        if (!empty($code)) return $code;
        
        $code = 'REF' . $user_id . strtoupper(substr(md5($user_id . time()), 0, 4));
        update_user_meta($user_id, 'ref_code', $code);
        return $code;
    }
    
    // ====================
    // شورت‌کدها
    // ====================
    
    public function daily_reward_shortcode() {
        if (!is_user_logged_in()) return '<p>لطفا وارد شوید</p>';
        
        $user_id = get_current_user_id();
        $can_claim = $this->can_claim_daily($user_id);
        $time_remaining = $this->get_daily_time_remaining();
        
        ob_start();
        $t = SRS_PATH . 'templates/frontend/daily-reward.php';
        if (file_exists($t)) include $t;
        return ob_get_clean();
    }
    
    public function wallet_log_shortcode() {
        if (!is_user_logged_in()) return '<p>لطفا وارد شوید</p>';
        
        $user_id = get_current_user_id();
        $transactions = $this->get_all_transactions($user_id);
        
        ob_start();
        $t = SRS_PATH . 'templates/frontend/wallet-log.php';
        if (file_exists($t)) include $t;
        return ob_get_clean();
    }
    
    public function user_level_shortcode() {
        if (!is_user_logged_in()) return '<p>لطفا وارد شوید</p>';
        
        $user_id = get_current_user_id();
        $srs = $this;
        $level = $this->get_user_level($user_id);
        $level_label = $this->get_level_label($level);
        $level_badge = $this->get_level_badge($level);
        $progress = $this->get_level_progress($user_id);
        
        global $wpdb;
        $table = $wpdb->prefix . 'srs_user_levels';
        $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
        
        ob_start();
        $t = SRS_PATH . 'templates/frontend/user-level.php';
        if (file_exists($t)) include $t;
        return ob_get_clean();
    }
    
    public function referral_stats_shortcode() {
        if (!is_user_logged_in()) return '<p>لطفا وارد شوید</p>';
        
        $user_id = get_current_user_id();
        $srs = $this;
        $referrals = $this->get_user_referrals($user_id);
        
        $ref_code = get_user_meta($user_id, 'ref_code', true);
        if (empty($ref_code)) $ref_code = $this->generate_custom_ref_code($user_id);
        
        $panel_url = get_option('srs_referral_page', '/panel/');
        $referral_link = add_query_arg('ref', $ref_code, home_url($panel_url));
        
        ob_start();
        $t = SRS_PATH . 'templates/frontend/referral-stats.php';
        if (file_exists($t)) include $t;
        return ob_get_clean();
    }
    
    public function admin_page() {
        $t = SRS_PATH . 'templates/admin/dashboard.php';
        if (file_exists($t)) include $t;
    }
}

Shahkar_Reward_System::get_instance();

add_action('user_register', function($user_id) {
    $ref = isset($_COOKIE['shahkar_ref_code']) ? sanitize_text_field($_COOKIE['shahkar_ref_code']) : '';
    if (!empty($ref)) {
        Shahkar_Reward_System::get_instance()->register_referral($user_id, $ref);
        setcookie('shahkar_ref_code', '', time() - 3600, '/');
    }
}, 10, 1);