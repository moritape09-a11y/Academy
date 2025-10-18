<?php
/**
 * کلاس امنیتی برای پلاگین
 * 
 * این کلاس شامل توابع امنیتی مختلف برای محافظت از پلاگین است
 */
class ADP_Security {
    /**
     * بررسی و پاکسازی URL
     * 
     * @param string $url
     * @return string|false
     */
    public static function sanitize_url( $url ) {
        if ( empty( $url ) ) {
            return '';
        }

        $url = esc_url_raw( $url );
        
        // بررسی پروتکل
        $allowed_protocols = array( 'http', 'https' );
        $parsed = wp_parse_url( $url );
        
        if ( isset( $parsed['scheme'] ) && ! in_array( $parsed['scheme'], $allowed_protocols, true ) ) {
            ADP_Logger::log( 'Invalid URL protocol detected: ' . $url, 'warning' );
            return false;
        }
        
        return $url;
    }

    /**
     * پاکسازی شماره تلفن
     * 
     * @param string $phone
     * @return string
     */
    public static function sanitize_phone( $phone ) {
        // فقط اعداد، +، - و فاصله و پرانتز مجاز
        $phone = preg_replace( '/[^0-9+\-\s()]/', '', $phone );
        return trim( $phone );
    }

    /**
     * اعتبارسنجی و پاکسازی JSON
     * 
     * @param string $json
     * @return array
     */
    public static function validate_json( $json ) {
        if ( empty( $json ) ) {
            return array();
        }
        
        $decoded = json_decode( $json, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            ADP_Logger::log( 'Invalid JSON: ' . json_last_error_msg(), 'error' );
            return array();
        }
        
        return $decoded;
    }

    /**
     * بررسی CSRF token
     * 
     * @param string $nonce
     * @param string $action
     * @return bool
     */
    public static function verify_nonce( $nonce, $action ) {
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            ADP_Logger::log( 'CSRF token verification failed for action: ' . $action, 'warning' );
            return false;
        }
        return true;
    }

    /**
     * پاکسازی HTML با whitelist محدود
     * 
     * @param string $html
     * @param array $allowed_tags
     * @return string
     */
    public static function sanitize_html( $html, $allowed_tags = array() ) {
        if ( empty( $allowed_tags ) ) {
            // تگ‌های امن پیش‌فرض
            $allowed_tags = array(
                'p' => array( 'class' => true, 'id' => true ),
                'br' => array(),
                'strong' => array(),
                'em' => array(),
                'a' => array( 'href' => true, 'title' => true, 'target' => true, 'rel' => true ),
            );
        }
        
        return wp_kses( $html, $allowed_tags );
    }

    /**
     * رمزنگاری داده‌های حساس
     * 
     * @param string $data
     * @return string
     */
    public static function encrypt_data( $data ) {
        if ( empty( $data ) ) {
            return '';
        }
        
        // استفاده از WordPress secret keys
        $key = wp_salt( 'secure_auth' );
        
        // استفاده از openssl برای رمزنگاری
        if ( function_exists( 'openssl_encrypt' ) ) {
            $iv = openssl_random_pseudo_bytes( 16 );
            $encrypted = openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv );
            return base64_encode( $iv . $encrypted );
        }
        
        // fallback به base64 (کمتر امن)
        return base64_encode( $data );
    }

    /**
     * رمزگشایی داده‌های رمزشده
     * 
     * @param string $encrypted_data
     * @return string
     */
    public static function decrypt_data( $encrypted_data ) {
        if ( empty( $encrypted_data ) ) {
            return '';
        }
        
        $key = wp_salt( 'secure_auth' );
        
        if ( function_exists( 'openssl_decrypt' ) ) {
            $data = base64_decode( $encrypted_data );
            $iv = substr( $data, 0, 16 );
            $encrypted = substr( $data, 16 );
            return openssl_decrypt( $encrypted, 'AES-256-CBC', $key, 0, $iv );
        }
        
        // fallback
        return base64_decode( $encrypted_data );
    }

    /**
     * بررسی دسترسی کاربر
     * 
     * @param string $capability
     * @return bool
     */
    public static function check_permission( $capability = 'manage_options' ) {
        if ( ! current_user_can( $capability ) ) {
            ADP_Logger::log( 'Permission denied for user: ' . get_current_user_id(), 'warning' );
            return false;
        }
        return true;
    }

    /**
     * پاکسازی SQL query (اگرچه باید از prepared statements استفاده شود)
     * 
     * @param string $query
     * @return string
     */
    public static function sanitize_sql( $query ) {
        global $wpdb;
        return $wpdb->prepare( '%s', $query );
    }

    /**
     * ایجاد hash امن برای مقایسه
     * 
     * @param string $data
     * @return string
     */
    public static function create_hash( $data ) {
        return hash_hmac( 'sha256', $data, wp_salt( 'secure_auth' ) );
    }

    /**
     * مقایسه امن دو رشته (جلوگیری از timing attacks)
     * 
     * @param string $a
     * @param string $b
     * @return bool
     */
    public static function safe_compare( $a, $b ) {
        if ( function_exists( 'hash_equals' ) ) {
            return hash_equals( $a, $b );
        }
        
        // fallback برای PHP < 5.6
        if ( strlen( $a ) !== strlen( $b ) ) {
            return false;
        }
        
        $result = 0;
        for ( $i = 0; $i < strlen( $a ); $i++ ) {
            $result |= ord( $a[$i] ) ^ ord( $b[$i] );
        }
        
        return $result === 0;
    }

    /**
     * فیلتر کردن ورودی‌های XSS
     * 
     * @param string $data
     * @return string
     */
    public static function prevent_xss( $data ) {
        // حذف تگ‌های خطرناک
        $data = wp_strip_all_tags( $data );
        
        // حذف کاراکترهای خاص
        $data = str_replace( array( '<', '>', '"', "'", '/', '\\' ), '', $data );
        
        return sanitize_text_field( $data );
    }

    /**
     * اعتبارسنجی email
     * 
     * @param string $email
     * @return string|false
     */
    public static function validate_email( $email ) {
        $email = sanitize_email( $email );
        
        if ( ! is_email( $email ) ) {
            ADP_Logger::log( 'Invalid email address: ' . $email, 'warning' );
            return false;
        }
        
        return $email;
    }

    /**
     * محدودسازی طول رشته
     * 
     * @param string $string
     * @param int $max_length
     * @return string
     */
    public static function limit_string_length( $string, $max_length = 255 ) {
        if ( strlen( $string ) > $max_length ) {
            ADP_Logger::log( 'String length exceeded: ' . strlen( $string ) . ' > ' . $max_length, 'warning' );
            return substr( $string, 0, $max_length );
        }
        return $string;
    }
}
