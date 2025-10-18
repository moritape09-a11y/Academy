<?php
/**
 * Plugin Name: Academy Directory Pro
 * Plugin URI: https://your-site.com/academy-directory-pro
 * Description: دایرکتوری جامع آموزشی - مدیریت آموزشگاه‌ها، مدارس و معلمین با امکانات پیشرفته
 * Description: Comprehensive Educational Directory - Manage Academies, Schools, and Teachers with Advanced Features
 * Version: 3.3
 * Author: Mahdi Aslani
 * Author URI: https://your-site.com
 * Text Domain: academy-directory-pro
 * Domain Path: /languages
 * License: GPL-2.0+
 * Requires at least: 5.8
 * Tested up to: 6.6
 * Requires PHP: 7.4
 *
 * @package Academy_Directory_Pro
 * 
 * نسخه 3.3 - امکانات جدید:
 * - پشتیبانی از سه نوع موجودیت: آموزشگاه، مدرسه، معلم/مربی
 * - سیستم Taxonomy برای دسته‌بندی پیشرفته
 * - طراحی مدرن و ریسپانسیو با رنگ‌بندی اختصاصی
 * - فیلدهای سفارشی برای هر نوع موجودیت
 * - شورت‌کد یکپارچه و انعطاف‌پذیر
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ADP_VERSION', '3.3' );
define( 'ADP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ADP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register( function( $class ) {
    if ( strpos( $class, 'ADP_' ) === 0 ) {
        $file_name = str_replace( '_', '-', strtolower( $class ) ) . '.php';
        $file = ADP_PLUGIN_DIR . 'includes/' . $file_name;
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
} );

/**
 * شروع پلاگین
 */
function adp_init_plugin() {
    $adp = new ADP_Core();
    $adp->init();
}
add_action( 'plugins_loaded', 'adp_init_plugin' );

/**
 * فعال‌سازی پلاگین
 */
function adp_activate() {
    $adp = new ADP_Core();
    $adp->activate();
}
register_activation_hook( __FILE__, 'adp_activate' );

/**
 * غیرفعال‌سازی پلاگین
 */
function adp_deactivate() {
    $adp = new ADP_Core();
    $adp->deactivate();
    // پاک کردن cron job
    wp_clear_scheduled_hook( 'adp_cleanup_logs' );
}
register_deactivation_hook( __FILE__, 'adp_deactivate' );
