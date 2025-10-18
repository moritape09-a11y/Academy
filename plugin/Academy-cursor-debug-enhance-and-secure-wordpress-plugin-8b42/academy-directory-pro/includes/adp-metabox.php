<?php
class ADP_Metabox {
    public function __construct() {
        add_action( 'add_meta_boxes_academy', array( $this, 'add' ) );
        add_action( 'save_post_academy', array( $this, 'save' ) );
    }

    public function add( $post ) {
        add_meta_box( 'adp_details', __( 'اطلاعات آموزشگاه', 'academy-directory-pro' ), array( $this, 'render' ), 'academy', 'normal', 'high' );
    }

    public function render( $post ) {
        wp_nonce_field( 'adp_save_meta', 'adp_nonce' );
        $fields = apply_filters( 'adp_meta_fields', array(
            'phone'     => __( 'شماره تماس', 'academy-directory-pro' ),
            'website'   => __( 'وب‌سایت', 'academy-directory-pro' ),
            'instagram' => __( 'اینستاگرام (آدرس کامل)', 'academy-directory-pro' ),
            'subjects'  => __( 'رشته‌ها (با کاما جدا کنید، مثال: زبان,موسیقی,نرم‌افزار)', 'academy-directory-pro' ),
            'rating'    => __( 'امتیاز (1 تا 5)', 'academy-directory-pro' ),
            'priority'  => __( 'اولویت نمایش (عدد کوچک‌تر = بالاتر)', 'academy-directory-pro' ),
            'embed'     => __( 'کد HTML نقشه (مثلاً iframe)', 'academy-directory-pro' ),
            'featured'  => __( 'ویژه (بله/خیر)', 'academy-directory-pro' ),
            'lat'       => __( 'عرض جغرافیایی (برای نقشه)', 'academy-directory-pro' ),
            'lng'       => __( 'طول جغرافیایی (برای نقشه)', 'academy-directory-pro' ),
            'field_style' => __( 'استایل سفارشی فیلدها (CSS، مثال: border-radius:50%;)', 'academy-directory-pro' ),
        ) );

        $custom_fields = json_decode( get_option( 'adp_custom_fields', '[]' ), true );
        foreach ( $custom_fields as $field ) {
            $fields[$field['key']] = $field['label'];
        }

        foreach ( $fields as $key => $label ) {
            $val = get_post_meta( $post->ID, '_adp_' . $key, true );
            $icon = isset( $custom_fields[$key]['icon'] ) ? '<i class="' . esc_attr( $custom_fields[$key]['icon'] ) . '"></i> ' : '';
            echo '<p><label>' . $icon . esc_html( $label ) . ':</label><br>';
            if ( $key === 'embed' || $key === 'field_style' ) {
                echo '<textarea name="adp_' . $key . '" rows="5" style="width:100%;direction:ltr;">' . esc_textarea( $val ) . '</textarea>';
            } elseif ( $key === 'rating' ) {
                echo '<input type="number" min="1" max="5" name="adp_' . $key . '" value="' . esc_attr( $val ?: 5 ) . '" style="width:100px"/>';
            } elseif ( $key === 'priority' ) {
                echo '<input type="number" min="0" name="adp_' . $key . '" value="' . esc_attr( $val ?: 100 ) . '" style="width:100px"/>';
            } elseif ( $key === 'featured' ) {
                echo '<select name="adp_' . $key . '"><option value="no">' . __( 'خیر', 'academy-directory-pro' ) . '</option><option value="yes"' . selected( $val, 'yes' ) . '>' . __( 'بله', 'academy-directory-pro' ) . '</option></select>';
            } else {
                echo '<input type="text" name="adp_' . $key . '" value="' . esc_attr( $val ) . '" style="width:100%"/>';
            }
            echo '</p>';
        }
    }

    public function save( $post_id ) {
        if ( ! isset( $_POST['adp_nonce'] ) || ! wp_verify_nonce( $_POST['adp_nonce'], 'adp_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $allowed_iframe = array( 'iframe' => array( 'src' => true, 'width' => true, 'height' => true, 'style' => true, 'allowfullscreen' => true, 'loading' => true, 'referrerpolicy' => true, 'frameborder' => true ) );
        $allowed_css_tags = array( 'br' => array() ); // برای CSS فقط متن ساده مجاز است

        $keys = array( 'phone', 'website', 'instagram', 'subjects', 'rating', 'priority', 'embed', 'featured', 'lat', 'lng', 'field_style' );

        $custom_fields = json_decode( get_option( 'adp_custom_fields', '[]' ), true );
        foreach ( $custom_fields as $field ) {
            $keys[] = $field['key'];
        }

        foreach ( $keys as $k ) {
            if ( isset( $_POST[ 'adp_' . $k ] ) ) {
                $val = $_POST[ 'adp_' . $k ];
                if ( $k === 'embed' ) {
                    $val = wp_kses( $val, $allowed_iframe );
                } elseif ( $k === 'field_style' ) {
                    // برای CSS فقط متن ساده - حذف هرگونه تگ HTML
                    $val = wp_strip_all_tags( $val );
                    // اعتبارسنجی پایه‌ای CSS - جلوگیری از CSS injection
                    $val = preg_replace( '/<script[^>]*>.*?<\/script>/is', '', $val );
                    $val = preg_replace( '/javascript:/i', '', $val );
                } elseif ( $k === 'rating' ) {
                    $val = intval( $val );
                    $val = max( 1, min( 5, $val ) );
                } elseif ( $k === 'priority' ) {
                    $val = absint( $val );
                } elseif ( $k === 'featured' ) {
                    $val = in_array( $val, array( 'yes', 'no' ), true ) ? $val : 'no';
                } elseif ( $k === 'website' || $k === 'instagram' ) {
                    $val = esc_url_raw( $val );
                } elseif ( $k === 'lat' || $k === 'lng' ) {
                    // اگر خالی بود، پیش‌فرض 1 قرار بده
                    $val = ! empty( $val ) ? floatval( $val ) : 1.0;
                } elseif ( $k === 'phone' ) {
                    // فقط اعداد، +، - و فاصله مجاز
                    $val = preg_replace( '/[^0-9+\-\s()]/', '', $val );
                } else {
                    $val = sanitize_text_field( $val );
                }
                update_post_meta( $post_id, '_adp_' . $k, $val );
                ADP_Logger::log( "Updated meta '_adp_{$k}' for post {$post_id}", 'debug' );
            }
        }
    }
}