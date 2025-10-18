<?php
/**
 * کلاس اصلی پلاگین
 */
class ADP_Core {
    public function init() {
        // بارگذاری ترجمه‌ها
        load_plugin_textdomain( 'academy-directory-pro', false, basename( ADP_PLUGIN_DIR ) . '/languages' );

        // اضافه کردن هدرهای امنیتی
        add_action( 'send_headers', array( $this, 'add_security_headers' ) );

        // بارگذاری کلاس امنیتی
        if ( ! class_exists( 'ADP_Security' ) ) {
            require_once ADP_PLUGIN_DIR . 'includes/adp-security.php';
        }

        new ADP_CPT();
        new ADP_Metabox();
        new ADP_Shortcode();
        new ADP_Ajax();
        new ADP_Settings();

        new ADP_Export_Import();
        new ADP_Woo_Integration();
        new ADP_SEO();

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        add_filter( 'the_content', array( $this, 'filter_single_content' ) );

        add_action( 'comment_post', array( $this, 'update_average_rating' ) );

        add_action( 'elementor/widgets/register', array( $this, 'register_elementor_widget' ) );

        // زمان‌بندی پاکسازی لاگ‌های قدیمی
        if ( ! wp_next_scheduled( 'adp_cleanup_logs' ) ) {
            wp_schedule_event( time(), 'daily', 'adp_cleanup_logs' );
        }
        add_action( 'adp_cleanup_logs', array( 'ADP_Logger', 'cleanup_old_logs' ) );
    }

    public function register_elementor_widget( $widgets_manager ) {
        if ( class_exists( 'Elementor\Widget_Base' ) ) {
            require_once ADP_PLUGIN_DIR . 'includes/adp-elementor-widget.php';
            $widgets_manager->register( new ADP_Elementor_Widget() );
        }
    }

    public function update_average_rating( $comment_id ) {
        $post_id = get_comment( $comment_id )->comment_post_ID;
        if ( get_post_type( $post_id ) === 'academy' ) {
            $comments = get_comments( array( 'post_id' => $post_id ) );
            $total = $count = 0;
            foreach ( $comments as $comment ) {
                $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
                if ( $rating ) {
                    $total += intval( $rating );
                    $count++;
                }
            }
            $average = $count ? $total / $count : 0;
            update_post_meta( $post_id, '_adp_average_rating', $average );
        }
    }

    public function activate() {
        ADP_CPT::register();
        flush_rewrite_rules();
        ADP_Logger::log( 'Plugin activated.', 'info' );
    }

    public function deactivate() {
        flush_rewrite_rules();
        ADP_Logger::log( 'Plugin deactivated.', 'info' );
    }

    public function enqueue_frontend_assets() {
        $post = get_post();
        if ( ( $post && has_shortcode( $post->post_content, 'academy_directory_pro' ) ) || is_singular( 'academy' ) || ( function_exists( 'elementor_load_plugin_textdomain' ) && \Elementor\Plugin::$instance->preview->is_preview_mode() ) ) {
            wp_enqueue_style( 'adp-google-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap', array(), null );
            wp_enqueue_style( 'adp-fa', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0' );

            $theme = get_option( 'adp_theme', 'yellow' );
            if ( $theme === 'blue' ) {
                wp_enqueue_style( 'adp-style', ADP_PLUGIN_URL . 'assets/css/blue-theme.css', array(), ADP_VERSION );
            } else {
                wp_enqueue_style( 'adp-style', ADP_PLUGIN_URL . 'assets/css/style.css', array(), ADP_VERSION );
            }

            wp_enqueue_script( 'adp-script', ADP_PLUGIN_URL . 'assets/js/script.js', array( 'jquery' ), ADP_VERSION, true );
            wp_localize_script( 'adp-script', 'adp_ajax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'adp_filter_nonce' ),
                'google_maps_api' => sanitize_text_field( get_option( 'adp_google_maps_api', '' ) ),
            ) );
            $google_maps_key = sanitize_text_field( get_option( 'adp_google_maps_api', '' ) );
            if ( ! empty( $google_maps_key ) ) {
                wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $google_maps_key ), array(), null, true );
                
                // Marker Clusterer library
                wp_enqueue_script( 'markerclusterer', 'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js', array( 'google-maps' ), null, true );
            }
        }
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'post.php' === $hook || 'post-new.php' === $hook || 'toplevel_page_adp-settings' === $hook ) {
            wp_enqueue_style( 'adp-admin-style', ADP_PLUGIN_URL . 'assets/css/admin.css', array(), ADP_VERSION );
        }
    }

    public function filter_single_content( $content ) {
        if ( is_singular( 'academy' ) && in_the_loop() && is_main_query() ) {
            return ADP_Helpers::render_single_content( $content );
        }
        return $content;
    }

    /**
     * اضافه کردن هدرهای امنیتی HTTP پیشرفته
     */
    public function add_security_headers() {
        if ( ! is_admin() ) {
            // جلوگیری از MIME type sniffing
            header( 'X-Content-Type-Options: nosniff' );
            
            // محافظت در برابر Clickjacking
            header( 'X-Frame-Options: SAMEORIGIN' );
            
            // فعال‌سازی XSS Protection در مرورگرها
            header( 'X-XSS-Protection: 1; mode=block' );
            
            // سیاست Referrer
            header( 'Referrer-Policy: strict-origin-when-cross-origin' );
            
            // Permissions Policy (قبلاً Feature-Policy)
            header( 'Permissions-Policy: geolocation=(self), microphone=(), camera=()' );
            
            // Content Security Policy (CSP)
            $csp = array(
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://maps.googleapis.com https://maps.gstatic.com https://cdnjs.cloudflare.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
                "img-src 'self' data: https: https://maps.googleapis.com https://maps.gstatic.com",
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com",
                "connect-src 'self' https://maps.googleapis.com https://maps.gstatic.com",
                "frame-src 'self' https://www.google.com https://maps.google.com https://maps.googleapis.com",
                "object-src 'none'",
                "base-uri 'self'"
            );
            header( 'Content-Security-Policy: ' . implode( '; ', $csp ) );
            
            // محافظت در برابر Downgrade attacks
            if ( is_ssl() ) {
                header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
            }
        }
    }
}