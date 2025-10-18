<?php
class ADP_Settings {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu() {
        add_menu_page( __( 'تنظیمات ADP', 'academy-directory-pro' ), __( 'تنظیمات ADP', 'academy-directory-pro' ), 'manage_options', 'adp-settings', array( $this, 'render_page' ), 'dashicons-book' );
    }

    public function register_settings() {
        register_setting( 'adp_settings', 'adp_per_page', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'adp_settings', 'adp_cols', array( 'sanitize_callback' => array( $this, 'sanitize_cols' ) ) );
        register_setting( 'adp_settings', 'adp_per_page_ajax', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'adp_settings', 'adp_enable_ajax', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'adp_settings', 'adp_custom_fields', array( 'sanitize_callback' => array( $this, 'sanitize_json' ) ) );
        register_setting( 'adp_settings', 'adp_custom_css', array( 'sanitize_callback' => array( $this, 'sanitize_css' ) ) );
        register_setting( 'adp_settings', 'adp_enable_logging', array( 'sanitize_callback' => 'absint' ) );
        register_setting( 'adp_settings', 'adp_google_maps_api', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'adp_settings', 'adp_theme', array( 'sanitize_callback' => array( $this, 'sanitize_theme' ) ) );
    }

    /**
     * اعتبارسنجی تعداد ستون‌ها
     */
    public function sanitize_cols( $value ) {
        $value = absint( $value );
        return max( 1, min( 4, $value ) );
    }

    /**
     * اعتبارسنجی JSON
     */
    public function sanitize_json( $value ) {
        if ( empty( $value ) ) {
            return '[]';
        }
        
        $decoded = json_decode( $value, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            add_settings_error( 'adp_custom_fields', 'invalid_json', __( 'فرمت JSON معتبر نیست.', 'academy-directory-pro' ) );
            ADP_Logger::log( 'Invalid JSON in custom fields: ' . json_last_error_msg(), 'error' );
            return '[]';
        }
        
        return wp_json_encode( $decoded );
    }

    /**
     * اعتبارسنجی CSS
     */
    public function sanitize_css( $value ) {
        if ( empty( $value ) ) {
            return '';
        }
        
        // حذف تگ‌های HTML
        $value = wp_strip_all_tags( $value );
        
        // حذف کدهای خطرناک
        $value = preg_replace( '/javascript:/i', '', $value );
        $value = preg_replace( '/<script/i', '', $value );
        $value = preg_replace( '/expression\s*\(/i', '', $value );
        
        return sanitize_textarea_field( $value );
    }

    /**
     * اعتبارسنجی تم
     */
    public function sanitize_theme( $value ) {
        $allowed_themes = array( 'yellow', 'blue' );
        return in_array( $value, $allowed_themes, true ) ? $value : 'yellow';
    }

    public function render_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'تنظیمات Academy Directory Pro', 'academy-directory-pro' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'adp_settings' );
                do_settings_sections( 'adp-settings' );
                ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'تعداد پیش‌فرض در صفحه', 'academy-directory-pro' ); ?></th>
                        <td><input type="number" name="adp_per_page" value="<?php echo esc_attr( get_option( 'adp_per_page', 9 ) ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'ستون‌های پیش‌فرض', 'academy-directory-pro' ); ?></th>
                        <td><input type="number" name="adp_cols" value="<?php echo esc_attr( get_option( 'adp_cols', 3 ) ); ?>" min="1" max="4" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'تعداد در AJAX', 'academy-directory-pro' ); ?></th>
                        <td><input type="number" name="adp_per_page_ajax" value="<?php echo esc_attr( get_option( 'adp_per_page_ajax', 9 ) ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'فعال کردن فیلترینگ AJAX', 'academy-directory-pro' ); ?></th>
                        <td><input type="checkbox" name="adp_enable_ajax" value="1" <?php checked( get_option( 'adp_enable_ajax', 1 ), 1 ); ?> /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'فیلدهای سفارشی (JSON)', 'academy-directory-pro' ); ?></th>
                        <td><textarea name="adp_custom_fields" rows="5"><?php echo esc_textarea( get_option( 'adp_custom_fields', '[]' ) ); ?></textarea><br><small>مثال: [{"key":"address","label":"آدرس","icon":"fa-map-marker"}]</small></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'متغیرهای CSS سفارشی', 'academy-directory-pro' ); ?></th>
                        <td><textarea name="adp_custom_css" rows="5"><?php echo esc_textarea( get_option( 'adp_custom_css' ) ); ?></textarea><br><small>مثال: --adp-yellow: #ffcc00;</small></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'فعال کردن لاگینگ', 'academy-directory-pro' ); ?></th>
                        <td><input type="checkbox" name="adp_enable_logging" value="1" <?php checked( get_option( 'adp_enable_logging', 0 ), 1 ); ?> /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'کلید API گوگل مپس', 'academy-directory-pro' ); ?></th>
                        <td><input type="text" name="adp_google_maps_api" value="<?php echo esc_attr( get_option( 'adp_google_maps_api' ) ); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'تم طراحی', 'academy-directory-pro' ); ?></th>
                        <td>
                            <select name="adp_theme">
                                <option value="yellow" <?php selected( get_option( 'adp_theme', 'yellow' ), 'yellow' ); ?>><?php esc_html_e( 'زرد (پیش‌فرض)', 'academy-directory-pro' ); ?></option>
                                <option value="blue" <?php selected( get_option( 'adp_theme' ), 'blue' ); ?>><?php esc_html_e( 'آبی', 'academy-directory-pro' ); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <hr style="margin: 20px 0;">
            <h2><?php esc_html_e( 'ورود و خروج داده‌ها', 'academy-directory-pro' ); ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'adp_export_csv_action', 'adp_export_csv_nonce' ); ?>
                <input type="hidden" name="adp_export_csv" value="1">
                <?php submit_button( __( 'خروجی CSV', 'academy-directory-pro' ), 'secondary', 'submit', false ); ?>
            </form>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field( 'adp_import_csv_action', 'adp_import_csv_nonce' ); ?>
                <input type="file" name="adp_import_csv" accept=".csv">
                <input type="hidden" name="adp_import_csv_submit" value="1">
                <?php submit_button( __( 'ورودی CSV', 'academy-directory-pro' ), 'secondary', 'submit', false ); ?>
            </form>

            <?php if ( get_option( 'adp_enable_logging', 0 ) ) : ?>
            <hr style="margin: 20px 0;">
            <h2><?php esc_html_e( 'لاگ‌های سیستم', 'academy-directory-pro' ); ?></h2>
            <div style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; direction: ltr; text-align: left;">
                <?php
                $logs = ADP_Logger::get_recent_logs( 100 );
                if ( ! empty( $logs ) ) {
                    foreach ( $logs as $log ) {
                        echo esc_html( $log ) . '<br>';
                    }
                } else {
                    echo '<em>' . __( 'هیچ لاگی یافت نشد.', 'academy-directory-pro' ) . '</em>';
                }
                ?>
            </div>
            <form method="post" action="" style="margin-top: 10px;">
                <?php wp_nonce_field( 'adp_clear_logs_action', 'adp_clear_logs_nonce' ); ?>
                <input type="hidden" name="adp_clear_logs" value="1">
                <?php submit_button( __( 'پاک کردن لاگ‌ها', 'academy-directory-pro' ), 'delete', 'submit', false ); ?>
            </form>
            <?php endif; ?>
        </div>
        <?php
        if ( isset( $_POST['adp_export_csv'] ) && check_admin_referer( 'adp_export_csv_action', 'adp_export_csv_nonce' ) ) {
            ADP_Export_Import::export_csv();
        }
        if ( isset( $_POST['adp_import_csv_submit'] ) && check_admin_referer( 'adp_import_csv_action', 'adp_import_csv_nonce' ) ) {
            ADP_Export_Import::import_csv();
        }
        if ( isset( $_POST['adp_clear_logs'] ) && check_admin_referer( 'adp_clear_logs_action', 'adp_clear_logs_nonce' ) ) {
            ADP_Logger::cleanup_old_logs();
            echo '<div class="notice notice-success"><p>' . __( 'لاگ‌ها پاک شدند.', 'academy-directory-pro' ) . '</p></div>';
        }
    }
}