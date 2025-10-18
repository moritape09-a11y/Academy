<?php
/**
 * کلاس مدیریت AJAX
 */
class ADP_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_adp_filter', array( $this, 'filter' ) );
        add_action( 'wp_ajax_nopriv_adp_filter', array( $this, 'filter' ) );
    }

    public function filter() {
        check_ajax_referer( 'adp_filter_nonce', 'nonce' );

        $search = isset( $_POST['adp_q'] ) ? sanitize_text_field( $_POST['adp_q'] ) : '';
        $subject = isset( $_POST['adp_subject'] ) ? sanitize_text_field( $_POST['adp_subject'] ) : '';
        $min_rating = isset( $_POST['adp_min_rating'] ) ? intval( $_POST['adp_min_rating'] ) : 0;
        $cols = isset( $_POST['cols'] ) ? max( 1, intval( $_POST['cols'] ) ) : 3;
        $per_page = get_option( 'adp_per_page_ajax', 9 );
        $paged = isset( $_POST['paged'] ) ? intval( $_POST['paged'] ) : 1;

        // Rate limiting - محدودسازی تعداد درخواست با سیستم sliding window
        $user_ip = $this->get_user_ip();
        $is_rate_limited = $this->check_rate_limit( $user_ip );
        
        if ( $is_rate_limited ) {
            ADP_Logger::log( "Rate limit exceeded for IP: {$user_ip}", 'warning' );
            wp_send_json_error( array( 
                'message' => __( 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً چند لحظه صبر کنید.', 'academy-directory-pro' ),
                'retry_after' => 60
            ) );
        }

        // ایجاد کلید کش امن بدون استفاده از serialize
        $cache_params = array(
            'search' => $search,
            'subject' => $subject,
            'min_rating' => $min_rating,
            'per_page' => $per_page,
            'paged' => $paged,
            'cols' => $cols
        );
        $transient_key = 'adp_ajax_' . md5( wp_json_encode( $cache_params ) );
        $response = get_transient( $transient_key );
        if ( false === $response ) {
            $args = array(
                'post_type'      => 'academy',
                'posts_per_page' => $per_page,
                'paged'          => $paged,
                'orderby'        => array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ),
                'meta_key'       => '_adp_priority',
                'order'          => 'ASC',
            );

            $meta_query = array( 'relation' => 'AND' );
            if ( $min_rating > 0 ) {
                $meta_query[] = array( 'key' => '_adp_rating', 'value' => $min_rating, 'type' => 'NUMERIC', 'compare' => '>=' );
            }
            if ( !empty( $subject ) ) {
                $meta_query[] = array( 'key' => '_adp_subjects', 'value' => $subject, 'compare' => 'LIKE' );
            }
            if ( count( $meta_query ) > 1 ) {
                $args['meta_query'] = $meta_query;
            }
            if ( ! empty( $search ) ) {
                $args['s'] = $search;
            }

            $q = new WP_Query( $args );

            $html = '';
            if ( ! $q->have_posts() ) {
                $html .= '<p class="adp-empty">' . __( 'هیچ آموزشی یافت نشد.', 'academy-directory-pro' ) . '</p>';
            } else {
                // فقط Grid View
                $html .= '<div class="adp-grid adp-cols-' . esc_attr( $cols ) . '">';
                while ( $q->have_posts() ) {
                    $q->the_post();
                    $html .= ADP_Helpers::render_card( get_the_ID() );
                }
                $html .= '</div>';
                $html .= '<div class="adp-pagination">' . paginate_links( array( 'total' => $q->max_num_pages, 'current' => $paged ) ) . '</div>';
            }

            wp_reset_postdata();
            $response = array( 
                'html' => $html, 
                'max_pages' => $q->max_num_pages,
                'total' => $q->found_posts
            );
            // کش کردن برای 5 دقیقه
            set_transient( $transient_key, $response, MINUTE_IN_SECONDS * 5 );
            
            ADP_Logger::log( "AJAX filter executed: {$q->found_posts} results found", 'debug' );
        }

        wp_send_json_success( $response );
    }

    /**
     * بررسی rate limit با الگوریتم sliding window
     * 
     * @param string $user_ip
     * @return bool true اگر rate limit بیش از حد باشد
     */
    private function check_rate_limit( $user_ip ) {
        $rate_key = 'adp_rate_' . md5( $user_ip );
        $requests = get_transient( $rate_key );
        
        if ( ! $requests ) {
            $requests = array();
        }
        
        $current_time = time();
        $window_size = 60; // 60 ثانیه
        $max_requests = 15; // حداکثر 15 درخواست در 60 ثانیه
        
        // حذف درخواست‌های قدیمی خارج از window
        $requests = array_filter( $requests, function( $timestamp ) use ( $current_time, $window_size ) {
            return ( $current_time - $timestamp ) < $window_size;
        } );
        
        // بررسی تعداد درخواست‌ها
        if ( count( $requests ) >= $max_requests ) {
            return true;
        }
        
        // اضافه کردن درخواست جدید
        $requests[] = $current_time;
        set_transient( $rate_key, $requests, $window_size );
        
        return false;
    }

    /**
     * دریافت IP کاربر
     * 
     * @return string
     */
    private function get_user_ip() {
        $ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
        
        foreach ( $ip_keys as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                // در صورت وجود چند IP (proxy chain)، اولین IP معتبر را برمی‌گردانیم
                if ( strpos( $ip, ',' ) !== false ) {
                    $ip = trim( explode( ',', $ip )[0] );
                }
                if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
}