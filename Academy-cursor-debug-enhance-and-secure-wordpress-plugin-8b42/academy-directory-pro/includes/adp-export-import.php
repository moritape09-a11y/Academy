<?php
class ADP_Export_Import {
    public static function export_csv() {
        // بررسی دسترسی
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'شما دسترسی به این بخش ندارید.', 'academy-directory-pro' ) );
        }

        $args = array( 'post_type' => 'academy', 'posts_per_page' => -1 );
        $posts = get_posts( $args );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=academies.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'ID', 'Title', 'Phone', 'Website', 'Instagram', 'Subjects', 'Rating', 'Priority', 'Embed', 'Featured', 'Lat', 'Lng' ) );

        foreach ( $posts as $post ) {
            $row = array(
                $post->ID,
                $post->post_title,
                get_post_meta( $post->ID, '_adp_phone', true ),
                get_post_meta( $post->ID, '_adp_website', true ),
                get_post_meta( $post->ID, '_adp_instagram', true ),
                get_post_meta( $post->ID, '_adp_subjects', true ),
                get_post_meta( $post->ID, '_adp_rating', true ),
                get_post_meta( $post->ID, '_adp_priority', true ),
                get_post_meta( $post->ID, '_adp_embed', true ),
                get_post_meta( $post->ID, '_adp_featured', true ),
                get_post_meta( $post->ID, '_adp_lat', true ),
                get_post_meta( $post->ID, '_adp_lng', true ),
            );
            fputcsv( $output, $row );
        }
        fclose( $output );
        exit;
    }

    public static function import_csv() {
        // بررسی دسترسی
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'شما دسترسی به این بخش ندارید.', 'academy-directory-pro' ) );
        }

        // بررسی فایل آپلود شده
        if ( ! isset( $_FILES['adp_import_csv'] ) || $_FILES['adp_import_csv']['error'] !== UPLOAD_ERR_OK ) {
            echo '<div class="notice notice-error"><p>' . __( 'خطا در آپلود فایل.', 'academy-directory-pro' ) . '</p></div>';
            ADP_Logger::log( 'CSV import failed: File upload error', 'error' );
            return;
        }

        // بررسی حجم فایل (حداکثر 5MB)
        $max_size = 5 * 1024 * 1024; // 5MB
        if ( $_FILES['adp_import_csv']['size'] > $max_size ) {
            echo '<div class="notice notice-error"><p>' . __( 'حجم فایل بیش از 5 مگابایت است.', 'academy-directory-pro' ) . '</p></div>';
            ADP_Logger::log( 'CSV import failed: File too large', 'error' );
            return;
        }

        // بررسی نوع فایل
        $file_type = wp_check_filetype( $_FILES['adp_import_csv']['name'] );
        if ( $file_type['ext'] !== 'csv' ) {
            echo '<div class="notice notice-error"><p>' . __( 'فرمت فایل باید CSV باشد.', 'academy-directory-pro' ) . '</p></div>';
            ADP_Logger::log( 'CSV import failed: Invalid file type', 'error' );
            return;
        }

        // بررسی MIME type
        $allowed_mimes = array( 'text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values', 'application/vnd.ms-excel' );
        if ( ! in_array( $_FILES['adp_import_csv']['type'], $allowed_mimes, true ) ) {
            echo '<div class="notice notice-error"><p>' . __( 'نوع MIME فایل معتبر نیست.', 'academy-directory-pro' ) . '</p></div>';
            ADP_Logger::log( 'CSV import failed: Invalid MIME type - ' . $_FILES['adp_import_csv']['type'], 'error' );
            return;
        }

        $file = $_FILES['adp_import_csv']['tmp_name'];
        if ( ( $handle = fopen( $file, 'r' ) ) !== false ) {
            $header = fgetcsv( $handle ); // رد کردن هدر
            $imported = 0;
            $skipped = 0;
            $errors = 0;
            $allowed_iframe = array( 
                'iframe' => array( 
                    'src' => true, 'width' => true, 'height' => true, 'style' => true, 
                    'allowfullscreen' => true, 'loading' => true, 'referrerpolicy' => true, 'frameborder' => true 
                ) 
            );

            // محدودیت تعداد ردیف‌ها (1000 ردیف)
            $max_rows = 1000;
            $row_count = 0;

            while ( ( $data = fgetcsv( $handle ) ) !== false ) {
                $row_count++;
                if ( $row_count > $max_rows ) {
                    echo '<div class="notice notice-warning"><p>' . sprintf( __( 'تعداد ردیف‌ها بیش از حد مجاز (%d) است. فقط %d ردیف اول وارد می‌شوند.', 'academy-directory-pro' ), $max_rows, $max_rows ) . '</p></div>';
                    ADP_Logger::log( "CSV import: Exceeded max rows limit ({$max_rows})", 'warning' );
                    break;
                }

                if ( ! isset( $data[1] ) || empty( trim( $data[1] ) ) ) {
                    $skipped++;
                    continue; // عنوان خالی
                }

                $post_id = wp_insert_post( array( 
                    'post_type' => 'academy', 
                    'post_title' => sanitize_text_field( $data[1] ), 
                    'post_status' => 'publish' 
                ), true );

                if ( is_wp_error( $post_id ) ) {
                    $errors++;
                    ADP_Logger::log( 'Failed to import: ' . $data[1] . ' - Error: ' . $post_id->get_error_message(), 'error' );
                    continue;
                }

                if ( $post_id ) {
                    update_post_meta( $post_id, '_adp_phone', isset( $data[2] ) ? sanitize_text_field( $data[2] ) : '' );
                    update_post_meta( $post_id, '_adp_website', isset( $data[3] ) ? esc_url_raw( $data[3] ) : '' );
                    update_post_meta( $post_id, '_adp_instagram', isset( $data[4] ) ? esc_url_raw( $data[4] ) : '' );
                    update_post_meta( $post_id, '_adp_subjects', isset( $data[5] ) ? sanitize_text_field( $data[5] ) : '' );
                    update_post_meta( $post_id, '_adp_rating', isset( $data[6] ) ? max( 1, min( 5, intval( $data[6] ) ) ) : 5 );
                    update_post_meta( $post_id, '_adp_priority', isset( $data[7] ) ? absint( $data[7] ) : 100 );
                    update_post_meta( $post_id, '_adp_embed', isset( $data[8] ) ? wp_kses( $data[8], $allowed_iframe ) : '' );
                    update_post_meta( $post_id, '_adp_featured', isset( $data[9] ) && in_array( $data[9], array( 'yes', 'no' ) ) ? $data[9] : 'no' );
                    update_post_meta( $post_id, '_adp_lat', isset( $data[10] ) ? floatval( $data[10] ) : '' );
                    update_post_meta( $post_id, '_adp_lng', isset( $data[11] ) ? floatval( $data[11] ) : '' );
                    $imported++;
                }
            }
            fclose( $handle );
            ADP_Logger::log( "CSV import completed: {$imported} imported, {$skipped} skipped, {$errors} errors.", 'info' );
            
            // نمایش گزارش کامل
            $message = sprintf( __( 'ورودی انجام شد. موفق: %d، رد شده: %d، خطا: %d', 'academy-directory-pro' ), $imported, $skipped, $errors );
            echo '<div class="notice notice-success"><p>' . esc_html( $message ) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __( 'خطا در خواندن فایل.', 'academy-directory-pro' ) . '</p></div>';
            ADP_Logger::log( 'CSV import failed: Cannot open file', 'error' );
            return;
        }
    }
}