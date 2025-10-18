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
     * @return string HTML فرم
     */
    public static function render_filter_form( $search, $subject, $min_rating, $cols ) {
        $out = '<form id="adp-filter-form" class="adp-filter" method="get">';
        $out .= '<div class="adp-filter-row">';
        $out .= '<input type="text" name="adp_q" placeholder="' . esc_attr__( 'جستجو (نام)...', 'academy-directory-pro' ) . '" value="' . esc_attr( $search ) . '" class="adp-input adp-input-search"/>';
        $out .= '<input type="text" name="adp_subject" placeholder="' . esc_attr__( 'جستجوی رشته/مهارت...', 'academy-directory-pro' ) . '" value="' . esc_attr( $subject ) . '" class="adp-input"/>';

        // فیلتر نوع موجودیت
        $out .= '<select name="adp_type" class="adp-input">';
        $out .= '<option value="">' . esc_html__( 'همه نوع‌ها', 'academy-directory-pro' ) . '</option>';
        $types = get_terms( array( 'taxonomy' => 'adp_entity_type', 'hide_empty' => false ) );
        if ( ! is_wp_error( $types ) ) {
            $current_type = isset( $_REQUEST['adp_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['adp_type'] ) ) : '';
            foreach ( $types as $type_term ) {
                $selected = selected( $current_type, $type_term->slug, false );
                $out .= '<option value="' . esc_attr( $type_term->slug ) . '" ' . $selected . '>' . esc_html( $type_term->name ) . '</option>';
            }
        }
        $out .= '</select>';

        $out .= '<select name="adp_min_rating" class="adp-input">';
        $out .= '<option value="0">' . __( 'همه امتیازها', 'academy-directory-pro' ) . '</option>';
        for ( $i = 1; $i <= 5; $i++ ) {
            $sel = ( $min_rating == $i ) ? ' selected' : '';
            $out .= '<option value="' . $i . '"' . $sel . '>' . $i . ' ' . __( 'ستاره و بالاتر', 'academy-directory-pro' ) . '</option>';
        }
        $out .= '</select>';
        $out .= '<button type="submit" class="adp-btn adp-btn-primary">' . __( 'جستجو', 'academy-directory-pro' ) . '</button>';
        $out .= '<button type="button" id="adp-reset" class="adp-btn">' . __( 'بازنشانی', 'academy-directory-pro' ) . '</button>';
        $out .= '<input type="hidden" name="cols" value="' . esc_attr( $cols ) . '">';
        $out .= '</div></form>';
        return $out;
    }

    /**
     * نمایش کارت آموزشگاه
     * 
     * @param int $post_id شناسه پست
     * @return string HTML کارت
     */
    public static function render_card( $post_id ) {
        $phone = get_post_meta( $post_id, '_adp_phone', true );
        $instagram = get_post_meta( $post_id, '_adp_instagram', true );
        $subjects = get_post_meta( $post_id, '_adp_subjects', true );
        $rating = get_post_meta( $post_id, '_adp_rating', true ) ?: 5;
        $featured = get_post_meta( $post_id, '_adp_featured', true ) === 'yes' ? ' adp-featured' : '';
        $out = '<article class="adp-card' . esc_attr( $featured ) . '" itemscope itemtype="https://schema.org/Organization">';
        $out .= '<div class="adp-card-inner">';
        if ( has_post_thumbnail() ) {
            $out .= '<div class="adp-thumb"><a href="' . get_permalink() . '">' . get_the_post_thumbnail( null, 'medium', array( 'itemprop' => 'image' ) ) . '</a></div>';
        }
        $out .= '<div class="adp-content">';
        $out .= '<h3 class="adp-title" itemprop="name"><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
        $out .= self::render_stars( $rating );
        if ( $subjects ) {
            $tags = array_map( 'trim', explode( ',', $subjects ) );
            $out .= '<div class="adp-tags" itemprop="keywords">';
            foreach ( $tags as $t ) {
                if ( $t ) $out .= '<span class="adp-tag adp-tag-yellow">' . esc_html( $t ) . '</span>';
            }
            $out .= '</div>';
        }
        $out .= '<div class="adp-excerpt">' . wp_trim_words( get_the_excerpt(), 15 ) . '</div>';
        $out .= '<ul class="adp-meta">';
        if ( $phone ) $out .= '<li itemprop="telephone"><i class="fa-solid fa-phone fa-fw"></i> ' . esc_html( $phone ) . '</li>';
        if ( $instagram ) $out .= '<li><i class="fa-brands fa-instagram fa-fw"></i> <a href="' . esc_url( $instagram ) . '" target="_blank" rel="noopener">' . __( 'اینستاگرام', 'academy-directory-pro' ) . '</a></li>';
        $out .= '</ul>';
        
        // دکمه‌های عملیات در Grid
        $out .= '<div class="adp-card-actions">';
        $out .= '<button type="button" class="adp-btn adp-btn-icon adp-btn-wishlist" data-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'افزودن به علاقه‌مندی‌ها', 'academy-directory-pro' ) . '">';
        $out .= '<i class="fa-regular fa-heart"></i>';
        $out .= '</button>';
        $out .= '<button type="button" class="adp-btn adp-btn-icon adp-btn-compare" data-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'افزودن به مقایسه', 'academy-directory-pro' ) . '">';
        $out .= '<i class="fa-solid fa-code-compare"></i>';
        $out .= '</button>';
        $out .= '</div>';
        
        $out .= '</div></div></article>';
        return $out;
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