<?php
/**
 * کلاس مدیریت Metabox برای موجودیت‌های آموزشی
 */
class ADP_Metabox {
    
    /**
     * فیلدهای مشترک بین همه موجودیت‌ها
     */
    private static $common_fields = array(
        'phone'     => array(
            'label' => 'شماره تماس',
            'type'  => 'text',
            'icon'  => 'fa-solid fa-phone',
        ),
        'email'     => array(
            'label' => 'ایمیل',
            'type'  => 'email',
            'icon'  => 'fa-solid fa-envelope',
        ),
        'website'   => array(
            'label' => 'وب‌سایت',
            'type'  => 'url',
            'icon'  => 'fa-solid fa-globe',
        ),
        'instagram' => array(
            'label' => 'اینستاگرام (آدرس کامل)',
            'type'  => 'url',
            'icon'  => 'fa-brands fa-instagram',
        ),
        'telegram'  => array(
            'label' => 'تلگرام (آدرس کامل)',
            'type'  => 'url',
            'icon'  => 'fa-brands fa-telegram',
        ),
        'rating'    => array(
            'label' => 'امتیاز (1 تا 5)',
            'type'  => 'number',
            'icon'  => 'fa-solid fa-star',
            'min'   => 1,
            'max'   => 5,
        ),
        'priority'  => array(
            'label' => 'اولویت نمایش (عدد کوچک‌تر = بالاتر)',
            'type'  => 'number',
            'icon'  => 'fa-solid fa-arrow-up-wide-short',
            'min'   => 0,
        ),
        'featured'  => array(
            'label' => 'ویژه (بله/خیر)',
            'type'  => 'select',
            'icon'  => 'fa-solid fa-star',
            'options' => array(
                'no'  => 'خیر',
                'yes' => 'بله',
            ),
        ),
    );
    
    /**
     * فیلدهای اختصاصی آموزشگاه و مدرسه
     */
    private static $institution_fields = array(
        'subjects'  => array(
            'label'       => 'رشته‌ها (با کاما جدا کنید، مثال: زبان,موسیقی,نرم‌افزار)',
            'type'        => 'textarea',
            'icon'        => 'fa-solid fa-book',
            'rows'        => 3,
        ),
        'address'   => array(
            'label' => 'آدرس کامل',
            'type'  => 'textarea',
            'icon'  => 'fa-solid fa-location-dot',
            'rows'  => 2,
        ),
        'established_year' => array(
            'label' => 'سال تاسیس',
            'type'  => 'number',
            'icon'  => 'fa-solid fa-calendar',
            'min'   => 1300,
        ),
        'capacity'  => array(
            'label' => 'ظرفیت دانش‌آموز',
            'type'  => 'number',
            'icon'  => 'fa-solid fa-users',
            'min'   => 0,
        ),
        'embed'     => array(
            'label' => 'کد HTML نقشه (مثلاً iframe)',
            'type'  => 'textarea',
            'icon'  => 'fa-solid fa-map-location-dot',
            'rows'  => 5,
        ),
        'lat'       => array(
            'label' => 'عرض جغرافیایی (برای نقشه)',
            'type'  => 'text',
            'icon'  => 'fa-solid fa-location-crosshairs',
        ),
        'lng'       => array(
            'label' => 'طول جغرافیایی (برای نقشه)',
            'type'  => 'text',
            'icon'  => 'fa-solid fa-location-crosshairs',
        ),
    );
    
    /**
     * فیلدهای اختصاصی معلم/مربی
     */
    private static $teacher_fields = array(
        'specialization' => array(
            'label' => 'تخصص (مثال: ریاضی، فیزیک، زبان)',
            'type'  => 'text',
            'icon'  => 'fa-solid fa-graduation-cap',
        ),
        'experience_years' => array(
            'label' => 'سابقه تدریس (سال)',
            'type'  => 'number',
            'icon'  => 'fa-solid fa-business-time',
            'min'   => 0,
        ),
        'education_degree' => array(
            'label' => 'مدرک تحصیلی',
            'type'  => 'select',
            'icon'  => 'fa-solid fa-user-graduate',
            'options' => array(
                'diploma'    => 'دیپلم',
                'associate'  => 'کاردانی',
                'bachelor'   => 'کارشناسی',
                'master'     => 'کارشناسی ارشد',
                'phd'        => 'دکتری',
            ),
        ),
        'teaching_method' => array(
            'label' => 'روش تدریس (حضوری، آنلاین، هیبریدی)',
            'type'  => 'text',
            'icon'  => 'fa-solid fa-chalkboard-user',
        ),
        'hourly_rate' => array(
            'label' => 'شهریه ساعتی (تومان)',
            'type'  => 'number',
            'icon'  => 'fa-solid fa-money-bill',
            'min'   => 0,
        ),
        'affiliated_institution' => array(
            'label' => 'موسسه وابسته (نام آموزشگاه یا مدرسه)',
            'type'  => 'text',
            'icon'  => 'fa-solid fa-building',
        ),
    );
    
    public function __construct() {
        // اضافه کردن metabox برای همه انواع
        add_action( 'add_meta_boxes_academy', array( $this, 'add' ) );
        add_action( 'add_meta_boxes_school', array( $this, 'add' ) );
        add_action( 'add_meta_boxes_teacher', array( $this, 'add' ) );
        
        // ذخیره برای همه انواع
        add_action( 'save_post_academy', array( $this, 'save' ) );
        add_action( 'save_post_school', array( $this, 'save' ) );
        add_action( 'save_post_teacher', array( $this, 'save' ) );
    }

    public function add( $post ) {
        $post_type = get_post_type( $post );
        $titles = array(
            'academy' => __( 'اطلاعات آموزشگاه', 'academy-directory-pro' ),
            'school'  => __( 'اطلاعات مدرسه', 'academy-directory-pro' ),
            'teacher' => __( 'اطلاعات معلم/مربی', 'academy-directory-pro' ),
        );
        
        $title = isset( $titles[ $post_type ] ) ? $titles[ $post_type ] : __( 'اطلاعات', 'academy-directory-pro' );
        
        add_meta_box( 
            'adp_details', 
            $title, 
            array( $this, 'render' ), 
            $post_type, 
            'normal', 
            'high' 
        );
    }

    public function render( $post ) {
        wp_nonce_field( 'adp_save_meta', 'adp_nonce' );
        
        $post_type = get_post_type( $post );
        
        // ترکیب فیلدها بر اساس نوع پست
        $fields = self::$common_fields;
        
        if ( $post_type === 'academy' || $post_type === 'school' ) {
            $fields = array_merge( $fields, self::$institution_fields );
        }
        
        if ( $post_type === 'teacher' ) {
            $fields = array_merge( $fields, self::$teacher_fields );
        }
        
        // اعمال فیلتر برای امکان افزودن فیلدهای سفارشی
        $fields = apply_filters( 'adp_meta_fields', $fields, $post_type );
        
        // نمایش فیلدها با طراحی بهتر
        echo '<div class="adp-metabox-wrapper" style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">';
        
        foreach ( $fields as $key => $field_config ) {
            $val = get_post_meta( $post->ID, '_adp_' . $key, true );
            $icon = ! empty( $field_config['icon'] ) ? '<i class="' . esc_attr( $field_config['icon'] ) . '"></i> ' : '';
            $full_width = in_array( $field_config['type'], array( 'textarea' ) ) ? 'style="grid-column:1/-1;"' : '';
            
            echo '<div class="adp-field" ' . $full_width . '>';
            echo '<label style="display:block;margin-bottom:5px;font-weight:600;">';
            echo $icon . esc_html( $field_config['label'] ) . ':';
            echo '</label>';
            
            $this->render_field( $key, $field_config, $val );
            
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * نمایش یک فیلد بر اساس نوعش
     */
    private function render_field( $key, $config, $value ) {
        $type = $config['type'];
        $name = 'adp_' . $key;
        $style = 'width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;';
        
        switch ( $type ) {
            case 'textarea':
                $rows = isset( $config['rows'] ) ? $config['rows'] : 3;
                echo '<textarea name="' . esc_attr( $name ) . '" rows="' . esc_attr( $rows ) . '" style="' . $style . '">';
                echo esc_textarea( $value );
                echo '</textarea>';
                break;
                
            case 'number':
                $min = isset( $config['min'] ) ? 'min="' . esc_attr( $config['min'] ) . '"' : '';
                $max = isset( $config['max'] ) ? 'max="' . esc_attr( $config['max'] ) . '"' : '';
                $default = isset( $config['min'] ) ? $config['min'] : 0;
                
                if ( $key === 'rating' ) {
                    $default = 5;
                } elseif ( $key === 'priority' ) {
                    $default = 100;
                }
                
                echo '<input type="number" name="' . esc_attr( $name ) . '" ';
                echo 'value="' . esc_attr( $value !== '' ? $value : $default ) . '" ';
                echo $min . ' ' . $max . ' style="' . $style . '"/>';
                break;
                
            case 'select':
                echo '<select name="' . esc_attr( $name ) . '" style="' . $style . '">';
                foreach ( $config['options'] as $opt_value => $opt_label ) {
                    $selected = selected( $value, $opt_value, false );
                    echo '<option value="' . esc_attr( $opt_value ) . '"' . $selected . '>';
                    echo esc_html( $opt_label );
                    echo '</option>';
                }
                echo '</select>';
                break;
                
            case 'email':
            case 'url':
                echo '<input type="' . esc_attr( $type ) . '" name="' . esc_attr( $name ) . '" ';
                echo 'value="' . esc_attr( $value ) . '" style="' . $style . '"/>';
                break;
                
            default: // text
                echo '<input type="text" name="' . esc_attr( $name ) . '" ';
                echo 'value="' . esc_attr( $value ) . '" style="' . $style . '"/>';
                break;
        }
    }

    public function save( $post_id ) {
        if ( ! isset( $_POST['adp_nonce'] ) || ! wp_verify_nonce( $_POST['adp_nonce'], 'adp_save_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $post_type = get_post_type( $post_id );
        
        // ترکیب تمام کلیدهای ممکن
        $all_fields = self::$common_fields;
        if ( $post_type === 'academy' || $post_type === 'school' ) {
            $all_fields = array_merge( $all_fields, self::$institution_fields );
        }
        if ( $post_type === 'teacher' ) {
            $all_fields = array_merge( $all_fields, self::$teacher_fields );
        }
        
        $allowed_iframe = array( 
            'iframe' => array( 
                'src' => true, 
                'width' => true, 
                'height' => true, 
                'style' => true, 
                'allowfullscreen' => true, 
                'loading' => true, 
                'referrerpolicy' => true, 
                'frameborder' => true 
            ) 
        );

        foreach ( $all_fields as $key => $config ) {
            if ( ! isset( $_POST[ 'adp_' . $key ] ) ) {
                continue;
            }
            
            $val = $_POST[ 'adp_' . $key ];
            $type = $config['type'];
            
            // Sanitize بر اساس نوع فیلد
            switch ( $type ) {
                case 'textarea':
                    if ( $key === 'embed' ) {
                        $val = wp_kses( $val, $allowed_iframe );
                    } else {
                        $val = sanitize_textarea_field( $val );
                    }
                    break;
                    
                case 'number':
                    $val = intval( $val );
                    if ( isset( $config['min'] ) ) {
                        $val = max( $config['min'], $val );
                    }
                    if ( isset( $config['max'] ) ) {
                        $val = min( $config['max'], $val );
                    }
                    break;
                    
                case 'email':
                    $val = sanitize_email( $val );
                    break;
                    
                case 'url':
                    $val = esc_url_raw( $val );
                    break;
                    
                case 'select':
                    $allowed = array_keys( $config['options'] );
                    $val = in_array( $val, $allowed, true ) ? $val : $allowed[0];
                    break;
                    
                default:
                    if ( $key === 'phone' ) {
                        // فقط اعداد، +، - و فاصه و پرانتز مجاز
                        $val = preg_replace( '/[^0-9+\-\s()]/', '', $val );
                    } elseif ( $key === 'lat' || $key === 'lng' ) {
                        $val = ! empty( $val ) ? floatval( $val ) : '';
                    } else {
                        $val = sanitize_text_field( $val );
                    }
                    break;
            }
            
            update_post_meta( $post_id, '_adp_' . $key, $val );
            
            if ( class_exists( 'ADP_Logger' ) ) {
                ADP_Logger::log( "Updated meta '_adp_{$key}' for post {$post_id} (type: {$post_type})", 'debug' );
            }
        }
    }
}