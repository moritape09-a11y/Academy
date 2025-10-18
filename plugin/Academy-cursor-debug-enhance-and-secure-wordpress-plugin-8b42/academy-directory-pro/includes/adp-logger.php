<?php
/**
 * کلاس لاگر برای ثبت رویدادها و خطاها
 */
class ADP_Logger {
    /**
     * ثبت پیام در لاگ با structured logging
     * 
     * @param string $message پیام لاگ
     * @param string $level سطح لاگ (info, warning, error, debug)
     * @param array $context اطلاعات اضافی برای لاگ
     */
    public static function log( $message, $level = 'error', $context = array() ) {
        if ( ! get_option( 'adp_enable_logging', 0 ) ) {
            return;
        }

        $timestamp = current_time( 'Y-m-d H:i:s' );
        
        // ساخت structured log entry
        $log_entry = array(
            'timestamp' => $timestamp,
            'level' => strtoupper( $level ),
            'message' => $message,
            'user_id' => get_current_user_id(),
            'ip' => self::get_client_ip(),
        );
        
        // اضافه کردن context
        if ( ! empty( $context ) ) {
            $log_entry['context'] = $context;
        }
        
        // فرمت نهایی
        $formatted_message = sprintf( 
            "[%s] [ADP %s] [User: %d] [IP: %s]: %s", 
            $timestamp, 
            strtoupper( $level ), 
            $log_entry['user_id'],
            $log_entry['ip'],
            $message 
        );
        
        if ( ! empty( $context ) ) {
            $formatted_message .= ' | Context: ' . wp_json_encode( $context );
        }
        
        // ثبت در error_log وردپرس
        if ( WP_DEBUG && WP_DEBUG_LOG ) {
            error_log( $formatted_message );
        }
        
        // ذخیره در فایل لاگ سفارشی
        self::write_to_file( $formatted_message, $level );
    }

    /**
     * دریافت IP کاربر
     * 
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
        
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }

    /**
     * نوشتن لاگ در فایل
     */
    private static function write_to_file( $message, $level ) {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/adp-logs';
        
        // ایجاد پوشه لاگ در صورت عدم وجود
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
            // محافظت از دسترسی مستقیم
            file_put_contents( $log_dir . '/.htaccess', 'Deny from all' );
            file_put_contents( $log_dir . '/index.php', '<?php // Silence is golden' );
        }
        
        $log_file = $log_dir . '/adp-' . date( 'Y-m-d' ) . '.log';
        
        // محدود کردن حجم فایل لاگ (5MB)
        if ( file_exists( $log_file ) && filesize( $log_file ) > 5 * 1024 * 1024 ) {
            rename( $log_file, $log_file . '.old' );
        }
        
        error_log( $message . PHP_EOL, 3, $log_file );
    }

    /**
     * خواندن لاگ‌های اخیر
     * 
     * @param int $lines تعداد خطوط
     * @return array
     */
    public static function get_recent_logs( $lines = 50 ) {
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/adp-logs/adp-' . date( 'Y-m-d' ) . '.log';
        
        if ( ! file_exists( $log_file ) ) {
            return array();
        }
        
        $file = new SplFileObject( $log_file );
        $file->seek( PHP_INT_MAX );
        $total_lines = $file->key();
        
        $start_line = max( 0, $total_lines - $lines );
        $file->seek( $start_line );
        
        $logs = array();
        while ( ! $file->eof() ) {
            $logs[] = $file->current();
            $file->next();
        }
        
        return array_filter( $logs );
    }

    /**
     * پاک کردن لاگ‌های قدیمی (بیش از 30 روز)
     */
    public static function cleanup_old_logs() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/adp-logs';
        
        if ( ! file_exists( $log_dir ) ) {
            return;
        }
        
        $files = glob( $log_dir . '/adp-*.log*' );
        $thirty_days_ago = strtotime( '-30 days' );
        
        foreach ( $files as $file ) {
            if ( filemtime( $file ) < $thirty_days_ago ) {
                @unlink( $file );
            }
        }
    }
}