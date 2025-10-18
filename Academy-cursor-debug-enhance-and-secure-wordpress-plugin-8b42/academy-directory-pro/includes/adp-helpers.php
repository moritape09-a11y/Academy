<?php
/**
 * کلاس توابع کمکی
 */
class ADP_Helpers {
    /**
     * نمایش ستاره‌های امتیاز
     * 
     * @param int $rating امتیاز (1 تا 5)
     * @return string HTML ستاره‌ها
     */
    public static function render_stars( $rating ) {
        $rating = intval( $rating );
        $rating = max( 1, min( 5, $rating ) );
        $out = '<div class="adp-stars" aria-hidden="true">';
        for ( $i = 1; $i <= 5; $i++ ) {
            $out .= ( $i <= $rating ) ? '<i class="fa-solid fa-star fa-fw star-on"></i>' : '<i class="fa-regular fa-star fa-fw star-off"></i>';
        }
        $out .= '</div>';
        return $out;
    }

    /**
     * نمایش فرم فیلتر
     * 
     * @param string $search جستجو
     * @param string $subject رشته
     * @param int $min_rating حداقل امتیاز
     * @param int $cols تعداد ستون‌ها
     * @param string $entity_type نوع موجودیت فعلی
     * @param array $post_types انواع پست قابل نمایش
     * @return string HTML فرم
     */
    public static function render_filter_form( $search, $subject, $min_rating, $cols, $entity_type = 'academy', $post_types = array() ) {
        $out = '<form id="adp-filter-form" class="adp-filter" method="get">';
        $out .= '<div class="adp-filter-row">';
        
        // فیلتر نوع موجودیت (فقط اگر بیش از یک نوع وجود داشت)
        if ( count( $post_types ) > 1 ) {
            $out .= '<select name="adp_type" class="adp-input adp-input-type">';
            $out .= '<option value="all"' . selected( $entity_type, 'all', false ) . '>' . __( '🏛️ همه موارد', 'academy-directory-pro' ) . '</option>';
            
            $type_labels = array(
                'academy' => '🎓 آموزشگاه‌ها',
                'school'  => '🏫 مدارس',
                'teacher' => '👨‍🏫 معلمین و مربی‌ها',
            );
            
            foreach ( $post_types as $type ) {
                if ( isset( $type_labels[ $type ] ) ) {
                    $selected = ( $entity_type === $type ) ? ' selected' : '';
                    $out .= '<option value="' . esc_attr( $type ) . '"' . $selected . '>';
                    $out .= esc_html( $type_labels[ $type ] );
                    $out .= '</option>';
                }
            }
            $out .= '</select>';
        }
        
        // جستجو
        $out .= '<input type="text" name="adp_q" placeholder="' . esc_attr__( 'جستجو...', 'academy-directory-pro' ) . '" value="' . esc_attr( $search ) . '" class="adp-input adp-input-search"/>';
        
        // جستجوی رشته
        $out .= '<input type="text" name="adp_subject" placeholder="' . esc_attr__( 'رشته (مثل ریاضی، زبان)...', 'academy-directory-pro' ) . '" value="' . esc_attr( $subject ) . '" class="adp-input"/>';
        
        // امتیاز
        $out .= '<select name="adp_min_rating" class="adp-input">';
        $out .= '<option value="0">' . __( 'همه امتیازها', 'academy-directory-pro' ) . '</option>';
        for ( $i = 1; $i <= 5; $i++ ) {
            $sel = ( $min_rating == $i ) ? ' selected' : '';
            $out .= '<option value="' . $i . '"' . $sel . '>' . $i . ' ' . __( 'ستاره و بالاتر', 'academy-directory-pro' ) . '</option>';
        }
        $out .= '</select>';
        
        // دکمه‌ها
        $out .= '<button type="submit" class="adp-btn adp-btn-primary">';
        $out .= '<i class="fa-solid fa-magnifying-glass"></i> ';
        $out .= __( 'جستجو', 'academy-directory-pro' );
        $out .= '</button>';
        
        $out .= '<button type="button" id="adp-reset" class="adp-btn">';
        $out .= '<i class="fa-solid fa-rotate-right"></i> ';
        $out .= __( 'بازنشانی', 'academy-directory-pro' );
        $out .= '</button>';
        
        $out .= '<input type="hidden" name="cols" value="' . esc_attr( $cols ) . '">';
        $out .= '</div></form>';
        return $out;
    }
    
    /**
     * دریافت آیکون و رنگ برای هر نوع موجودیت
     * 
     * @param string $post_type
     * @return array
     */
    public static function get_entity_style( $post_type ) {
        $styles = array(
            'academy' => array(
                'icon'        => 'fa-solid fa-graduation-cap',
                'color'       => '#ffc400', // زرد
                'bg'          => '#fff8e1',
                'label'       => 'آموزشگاه',
            ),
            'school' => array(
                'icon'        => 'fa-solid fa-school',
                'color'       => '#2196F3', // آبی
                'bg'          => '#e3f2fd',
                'label'       => 'مدرسه',
            ),
            'teacher' => array(
                'icon'        => 'fa-solid fa-chalkboard-user',
                'color'       => '#4CAF50', // سبز
                'bg'          => '#e8f5e9',
                'label'       => 'معلم/مربی',
            ),
        );
        
        return isset( $styles[ $post_type ] ) ? $styles[ $post_type ] : $styles['academy'];
    }

    /**
     * نمایش کارت موجودیت آموزشی (آموزشگاه، مدرسه، معلم)
     * 
     * @param int $post_id شناسه پست
     * @return string HTML کارت
     */
    public static function render_card( $post_id ) {
        $post_type = get_post_type( $post_id );
        $entity_style = self::get_entity_style( $post_type );
        
        // دریافت اطلاعات عمومی
        $phone = get_post_meta( $post_id, '_adp_phone', true );
        $email = get_post_meta( $post_id, '_adp_email', true );
        $instagram = get_post_meta( $post_id, '_adp_instagram', true );
        $subjects = get_post_meta( $post_id, '_adp_subjects', true );
        $rating = get_post_meta( $post_id, '_adp_rating', true ) ?: 5;
        $featured = get_post_meta( $post_id, '_adp_featured', true ) === 'yes' ? ' adp-featured' : '';
        
        // اطلاعات اختصاصی بر اساس نوع
        $extra_info = self::get_entity_extra_info( $post_id, $post_type );
        
        $out = '<article class="adp-card adp-card-' . esc_attr( $post_type ) . esc_attr( $featured ) . '" itemscope itemtype="https://schema.org/Organization" data-type="' . esc_attr( $post_type ) . '">';
        $out .= '<div class="adp-card-inner">';
        
        // بج نوع موجودیت
        $out .= '<div class="adp-entity-badge" style="background:' . esc_attr( $entity_style['bg'] ) . ';color:' . esc_attr( $entity_style['color'] ) . ';">';
        $out .= '<i class="' . esc_attr( $entity_style['icon'] ) . '"></i> ';
        $out .= esc_html( $entity_style['label'] );
        $out .= '</div>';
        
        // تصویر
        if ( has_post_thumbnail( $post_id ) ) {
            $out .= '<div class="adp-thumb"><a href="' . get_permalink( $post_id ) . '">';
            $out .= get_the_post_thumbnail( $post_id, 'medium', array( 'itemprop' => 'image' ) );
            $out .= '</a></div>';
        } else {
            // تصویر پیش‌فرض با آیکون
            $out .= '<div class="adp-thumb adp-thumb-default" style="background:' . esc_attr( $entity_style['bg'] ) . ';">';
            $out .= '<a href="' . get_permalink( $post_id ) . '">';
            $out .= '<i class="' . esc_attr( $entity_style['icon'] ) . '" style="color:' . esc_attr( $entity_style['color'] ) . ';font-size:64px;"></i>';
            $out .= '</a></div>';
        }
        
        $out .= '<div class="adp-content">';
        $out .= '<h3 class="adp-title" itemprop="name"><a href="' . get_permalink( $post_id ) . '">' . get_the_title( $post_id ) . '</a></h3>';
        $out .= self::render_stars( $rating );
        
        // نمایش اطلاعات اضافی
        if ( ! empty( $extra_info ) ) {
            $out .= '<div class="adp-extra-info">';
            foreach ( $extra_info as $info ) {
                $out .= '<span class="adp-info-item"><i class="' . esc_attr( $info['icon'] ) . '"></i> ' . esc_html( $info['text'] ) . '</span>';
            }
            $out .= '</div>';
        }
        
        // رشته‌ها / تخصص‌ها
        if ( $subjects ) {
            $tags = array_map( 'trim', explode( ',', $subjects ) );
            $out .= '<div class="adp-tags" itemprop="keywords">';
            foreach ( array_slice( $tags, 0, 3 ) as $t ) { // نمایش حداکثر 3 تگ
                if ( $t ) $out .= '<span class="adp-tag" style="background:' . esc_attr( $entity_style['color'] ) . ';">' . esc_html( $t ) . '</span>';
            }
            if ( count( $tags ) > 3 ) {
                $out .= '<span class="adp-tag adp-tag-more">+' . ( count( $tags ) - 3 ) . '</span>';
            }
            $out .= '</div>';
        }
        
        $out .= '<div class="adp-excerpt">' . wp_trim_words( get_the_excerpt( $post_id ), 15 ) . '</div>';
        
        // اطلاعات تماس
        $out .= '<ul class="adp-meta">';
        if ( $phone ) {
            $out .= '<li itemprop="telephone"><i class="fa-solid fa-phone fa-fw"></i> ' . esc_html( $phone ) . '</li>';
        }
        if ( $email ) {
            $out .= '<li><i class="fa-solid fa-envelope fa-fw"></i> ' . esc_html( $email ) . '</li>';
        }
        if ( $instagram ) {
            $out .= '<li><i class="fa-brands fa-instagram fa-fw"></i> <a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener">' . __( 'اینستاگرام', 'academy-directory-pro' ) . '</a></li>';
        }
        $out .= '</ul>';
        
        // دکمه‌های عملیات
        $out .= '<div class="adp-card-actions">';
        $out .= '<a href="' . get_permalink( $post_id ) . '" class="adp-btn adp-btn-view" style="background:' . esc_attr( $entity_style['color'] ) . ';">';
        $out .= '<i class="fa-solid fa-arrow-left"></i> ' . __( 'مشاهده', 'academy-directory-pro' );
        $out .= '</a>';
        $out .= '<button type="button" class="adp-btn adp-btn-icon adp-btn-wishlist" data-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'افزودن به علاقه‌مندی‌ها', 'academy-directory-pro' ) . '">';
        $out .= '<i class="fa-regular fa-heart"></i>';
        $out .= '</button>';
        $out .= '</div>';
        
        $out .= '</div></div></article>';
        return $out;
    }
    
    /**
     * دریافت اطلاعات اضافی برای نمایش در کارت
     * 
     * @param int $post_id
     * @param string $post_type
     * @return array
     */
    private static function get_entity_extra_info( $post_id, $post_type ) {
        $info = array();
        
        switch ( $post_type ) {
            case 'academy':
            case 'school':
                $established = get_post_meta( $post_id, '_adp_established_year', true );
                if ( $established ) {
                    $info[] = array(
                        'icon' => 'fa-solid fa-calendar',
                        'text' => 'تاسیس: ' . $established,
                    );
                }
                
                $capacity = get_post_meta( $post_id, '_adp_capacity', true );
                if ( $capacity ) {
                    $info[] = array(
                        'icon' => 'fa-solid fa-users',
                        'text' => 'ظرفیت: ' . $capacity . ' نفر',
                    );
                }
                break;
                
            case 'teacher':
                $specialization = get_post_meta( $post_id, '_adp_specialization', true );
                if ( $specialization ) {
                    $info[] = array(
                        'icon' => 'fa-solid fa-graduation-cap',
                        'text' => $specialization,
                    );
                }
                
                $experience = get_post_meta( $post_id, '_adp_experience_years', true );
                if ( $experience ) {
                    $info[] = array(
                        'icon' => 'fa-solid fa-business-time',
                        'text' => $experience . ' سال سابقه',
                    );
                }
                
                $degree = get_post_meta( $post_id, '_adp_education_degree', true );
                $degree_labels = array(
                    'diploma'   => 'دیپلم',
                    'associate' => 'کاردانی',
                    'bachelor'  => 'کارشناسی',
                    'master'    => 'کارشناسی ارشد',
                    'phd'       => 'دکتری',
                );
                if ( $degree && isset( $degree_labels[ $degree ] ) ) {
                    $info[] = array(
                        'icon' => 'fa-solid fa-user-graduate',
                        'text' => $degree_labels[ $degree ],
                    );
                }
                break;
        }
        
        return $info;
    }

    /**
     * نمایش آیتم لیستی آموزشگاه (برای List View)
     * 
     * @param int $post_id شناسه پست
     * @return string HTML آیتم لیستی
     */
    public static function render_list_item( $post_id ) {
        $phone = get_post_meta( $post_id, '_adp_phone', true );
        $website = get_post_meta( $post_id, '_adp_website', true );
        $instagram = get_post_meta( $post_id, '_adp_instagram', true );
        $subjects = get_post_meta( $post_id, '_adp_subjects', true );
        $rating = get_post_meta( $post_id, '_adp_rating', true ) ?: 5;
        $featured = get_post_meta( $post_id, '_adp_featured', true ) === 'yes' ? ' adp-featured' : '';
        
        $out = '<article class="adp-list-item' . esc_attr( $featured ) . '" itemscope itemtype="https://schema.org/Organization">';
        
        // تصویر
        $out .= '<div class="adp-list-item-image">';
        if ( has_post_thumbnail() ) {
            $out .= '<a href="' . esc_url( get_permalink() ) . '">';
            $out .= get_the_post_thumbnail( null, 'medium', array( 'itemprop' => 'image' ) );
            $out .= '</a>';
        } else {
            $out .= '<a href="' . esc_url( get_permalink() ) . '" class="adp-no-image">';
            $out .= '<i class="fa-solid fa-graduation-cap"></i>';
            $out .= '</a>';
        }
        $out .= '</div>';
        
        // محتوا
        $out .= '<div class="adp-list-item-content">';
        
        // عنوان و امتیاز
        $out .= '<div class="adp-list-item-header">';
        $out .= '<h3 class="adp-list-item-title" itemprop="name">';
        $out .= '<a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a>';
        $out .= '</h3>';
        $out .= '<div class="adp-list-item-rating">';
        $out .= self::render_stars( $rating );
        $out .= '</div>';
        $out .= '</div>';
        
        // رشته‌ها
        if ( $subjects ) {
            $tags = array_map( 'trim', explode( ',', $subjects ) );
            $out .= '<div class="adp-list-item-tags" itemprop="keywords">';
            foreach ( $tags as $t ) {
                if ( $t ) {
                    $out .= '<span class="adp-tag adp-tag-yellow">' . esc_html( $t ) . '</span>';
                }
            }
            $out .= '</div>';
        }
        
        // خلاصه
        $out .= '<div class="adp-list-item-excerpt">' . wp_trim_words( get_the_excerpt(), 25 ) . '</div>';
        
        // اطلاعات تماس
        $out .= '<div class="adp-list-item-meta">';
        if ( $phone ) {
            $out .= '<span class="adp-meta-item" itemprop="telephone">';
            $out .= '<i class="fa-solid fa-phone"></i> ' . esc_html( $phone );
            $out .= '</span>';
        }
        if ( $website ) {
            $out .= '<span class="adp-meta-item">';
            $out .= '<i class="fa-solid fa-globe"></i> ';
            $out .= '<a href="' . esc_url( $website ) . '" target="_blank" rel="noopener" itemprop="url">' . __( 'وب‌سایت', 'academy-directory-pro' ) . '</a>';
            $out .= '</span>';
        }
        if ( $instagram ) {
            $out .= '<span class="adp-meta-item">';
            $out .= '<i class="fa-brands fa-instagram"></i> ';
            $out .= '<a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener">' . __( 'اینستاگرام', 'academy-directory-pro' ) . '</a>';
            $out .= '</span>';
        }
        $out .= '</div>';
        
        $out .= '</div>'; // .adp-list-item-content
        
        // دکمه‌های عملیات
        $out .= '<div class="adp-list-item-actions">';
        $out .= '<a href="' . esc_url( get_permalink() ) . '" class="adp-btn adp-btn-primary">';
        $out .= '<i class="fa-solid fa-arrow-left"></i> ' . __( 'مشاهده جزئیات', 'academy-directory-pro' );
        $out .= '</a>';
        $out .= '<button type="button" class="adp-btn adp-btn-icon adp-btn-wishlist" data-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'افزودن به علاقه‌مندی‌ها', 'academy-directory-pro' ) . '">';
        $out .= '<i class="fa-regular fa-heart"></i>';
        $out .= '</button>';
        $out .= '<button type="button" class="adp-btn adp-btn-icon adp-btn-compare" data-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'افزودن به مقایسه', 'academy-directory-pro' ) . '">';
        $out .= '<i class="fa-solid fa-code-compare"></i>';
        $out .= '</button>';
        $out .= '</div>';
        
        $out .= '</article>';
        
        return $out;
    }

    /**
     * نمایش صفحه تک آموزشگاه
     * 
     * @param string $content محتوای پیش‌فرض
     * @return string HTML صفحه تک
     */
    public static function render_single_content( $content ) {
        global $post;
        $post_id = $post->ID;
        $phone = get_post_meta( $post_id, '_adp_phone', true );
        $website = get_post_meta( $post_id, '_adp_website', true );
        $instagram = get_post_meta( $post_id, '_adp_instagram', true );
        $subjects = get_post_meta( $post_id, '_adp_subjects', true );
        $rating = get_post_meta( $post_id, '_adp_rating', true ) ?: 5;
        $priority = get_post_meta( $post_id, '_adp_priority', true ) ?: 100;
        $embed = get_post_meta( $post_id, '_adp_embed', true );
        $lat = get_post_meta( $post_id, '_adp_lat', true );
        $lng = get_post_meta( $post_id, '_adp_lng', true );

        $out = '<div class="adp-single" itemscope itemtype="https://schema.org/Organization">';
        $out .= '<div class="adp-single-header">';
        if ( has_post_thumbnail() ) {
            $out .= '<div class="adp-single-thumb">' . get_the_post_thumbnail( null, 'large', array( 'itemprop' => 'image' ) ) . '</div>';
        }
        $out .= '<div class="adp-single-info">';
        $out .= '<h1 class="adp-single-title" itemprop="name">' . get_the_title() . '</h1>';
        $out .= self::render_stars( $rating );
        $out .= '</div>';
        if ( $subjects ) {
            $tags = array_map( 'trim', explode( ',', $subjects ) );
            $out .= '<div class="adp-tags adp-tags-single" itemprop="keywords">';
            foreach ( $tags as $t ) {
                if ( $t ) $out .= '<span class="adp-tag adp-tag-yellow">' . esc_html( $t ) . '</span>';
            }
            $out .= '</div>';
        }
        $out .= '<div class="adp-single-content" itemprop="description">' . $content . '</div>';
        $out .= '<div class="adp-single-contact-box">';
        $out .= '<h3 class="adp-box-title"><i class="fa-solid fa-address-card"></i> ' . __( 'اطلاعات تماس و جزئیات', 'academy-directory-pro' ) . '</h3>';
        $out .= '<ul class="adp-single-meta">';
        if ( $phone ) $out .= '<li itemprop="telephone"><i class="fa-solid fa-phone"></i> <span>' . __( 'شماره تماس:', 'academy-directory-pro' ) . '</span> <a href="tel:' . esc_attr( $phone ) . '">' . esc_html( $phone ) . '</a></li>';
        if ( $website ) $out .= '<li itemprop="url"><i class="fa-solid fa-globe"></i> <span>' . __( 'وب‌سایت:', 'academy-directory-pro' ) . '</span> <a href="' . esc_url( $website ) . '" target="_blank" rel="noopener">' . esc_html( $website ) . '</a></li>';
        if ( $instagram ) $out .= '<li><i class="fa-brands fa-instagram"></i> <span>' . __( 'اینستاگرام:', 'academy-directory-pro' ) . '</span> <a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener">' . __( 'صفحه اینستاگرام', 'academy-directory-pro' ) . '</a></li>';
        if ( $priority !== '' ) $out .= '<li><i class="fa-solid fa-arrow-up-wide-short"></i> <span>' . __( 'اولویت نمایش:', 'academy-directory-pro' ) . '</span> ' . esc_html( $priority ) . '</li>';
        $out .= '</ul>';
        $out .= '</div>';
        if ( $embed ) {
            $out .= '<div class="adp-single-map-box">';
            $out .= '<h3 class="adp-box-title"><i class="fa-solid fa-map-location-dot"></i> ' . __( 'موقعیت آموزشگاه روی نقشه', 'academy-directory-pro' ) . '</h3>';
            // نمایش iframe نقشه - iframe از قبل sanitize شده
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
            $out .= '<div class="adp-embed adp-embed-single">' . wp_kses( $embed, $allowed_iframe ) . '</div>';
            $out .= '</div>';
        } elseif ( $lat && $lng ) {
            $out .= '<div class="adp-single-map-box">';
            $out .= '<h3 class="adp-box-title"><i class="fa-solid fa-map-location-dot"></i> ' . __( 'موقعیت روی نقشه', 'academy-directory-pro' ) . '</h3>';
            $out .= '<div id="adp-map" style="height:350px;"></div>';
            $out .= '<script>function initMap() { var map = new google.maps.Map(document.getElementById("adp-map"), {zoom: 15, center: {lat: ' . floatval( $lat ) . ', lng: ' . floatval( $lng ) . '}}); var marker = new google.maps.Marker({position: {lat: ' . floatval( $lat ) . ', lng: ' . floatval( $lng ) . '}, map: map}); } window.initMap = initMap;</script>';
            $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
    }
}