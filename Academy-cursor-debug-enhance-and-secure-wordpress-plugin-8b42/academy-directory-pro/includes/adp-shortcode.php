<?php
/**
 * کلاس Shortcode برای نمایش موجودیت‌های آموزشی
 * 
 * شورت‌کد پشتیبانی می‌کند از:
 * - نمایش آموزشگاه‌ها
 * - نمایش مدارس
 * - نمایش معلمین/مربی‌ها
 * - نمایش ترکیبی همه موجودیت‌ها
 */
class ADP_Shortcode {
    public function __construct() {
        add_shortcode( 'academy_directory_pro', array( $this, 'render' ) );
        add_shortcode( 'edu_directory', array( $this, 'render' ) ); // نام جدید شورت‌کد
    }

    /**
     * رندر شورت‌کد
     * 
     * پارامترهای قابل قبول:
     * - type: نوع موجودیت (academy, school, teacher, all)
     * - per_page: تعداد آیتم در هر صفحه
     * - cols: تعداد ستون‌ها در Grid
     * 
     * مثال استفاده:
     * [edu_directory type="all" cols="3" per_page="9"]
     * [edu_directory type="teacher" cols="4"]
     * [edu_directory type="school,academy" cols="3"]
     */
    public function render( $atts ) {
        $atts = shortcode_atts( array( 
            'type'     => 'academy', // academy, school, teacher, all یا ترکیب با کاما
            'per_page' => get_option( 'adp_per_page', 9 ), 
            'cols'     => get_option( 'adp_cols', 3 )
        ), $atts );

        // دریافت پارامترهای فیلتر از URL
        $search = isset( $_REQUEST['adp_q'] ) ? sanitize_text_field( $_REQUEST['adp_q'] ) : '';
        $subject = isset( $_REQUEST['adp_subject'] ) ? sanitize_text_field( $_REQUEST['adp_subject'] ) : '';
        $min_rating = isset( $_REQUEST['adp_min_rating'] ) ? intval( $_REQUEST['adp_min_rating'] ) : 0;
        $entity_type = isset( $_REQUEST['adp_type'] ) ? sanitize_text_field( $_REQUEST['adp_type'] ) : $atts['type'];
        
        $per_page = intval( $atts['per_page'] );
        $cols = max( 1, intval( $atts['cols'] ) );
        $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
        
        // تعیین انواع موجودیت برای کوئری
        $post_types = $this->get_post_types( $entity_type );

        // ایجاد کلید کش امن
        $cache_params = array(
            'search'      => $search,
            'subject'     => $subject,
            'min_rating'  => $min_rating,
            'per_page'    => $per_page,
            'cols'        => $cols,
            'paged'       => $paged,
            'post_types'  => $post_types,
        );
        $transient_key = 'adp_query_' . md5( wp_json_encode( $cache_params ) );
        $q = get_transient( $transient_key );
        
        if ( false === $q ) {
            $args = array(
                'post_type'      => $post_types,
                'posts_per_page' => $per_page,
                'paged'          => $paged,
                'orderby'        => array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ),
                'meta_key'       => '_adp_priority',
                'order'          => 'ASC',
            );

            $meta_query = array( 'relation' => 'AND' );
            if ( $min_rating > 0 ) {
                $meta_query[] = array( 
                    'key'     => '_adp_rating', 
                    'value'   => $min_rating, 
                    'type'    => 'NUMERIC', 
                    'compare' => '>=' 
                );
            }
            if ( ! empty( $subject ) ) {
                $meta_query[] = array( 
                    'key'     => '_adp_subjects', 
                    'value'   => $subject, 
                    'compare' => 'LIKE' 
                );
            }
            if ( count( $meta_query ) > 1 ) {
                $args['meta_query'] = $meta_query;
            }
            if ( ! empty( $search ) ) {
                $args['s'] = $search;
            }

            $q = new WP_Query( $args );
            set_transient( $transient_key, $q, 300 );
        }

        // فرم فیلتر با امکان انتخاب نوع
        $out = ADP_Helpers::render_filter_form( $search, $subject, $min_rating, $cols, $entity_type, $post_types );

        // نتایج - Grid View
        $out .= '<div id="adp-result-wrapper" data-cols="' . esc_attr( $cols ) . '">';
        
        if ( ! $q->have_posts() ) {
            $out .= '<p class="adp-empty">' . __( 'هیچ موردی یافت نشد.', 'academy-directory-pro' ) . '</p>';
        } else {
            $out .= '<div class="adp-grid adp-cols-' . esc_attr( $cols ) . '">';
            while ( $q->have_posts() ) {
                $q->the_post();
                $out .= ADP_Helpers::render_card( get_the_ID() );
            }
            $out .= '</div>';
            $out .= '<div class="adp-pagination">' . paginate_links( array( 
                'total'   => $q->max_num_pages, 
                'current' => $paged 
            ) ) . '</div>';
        }
        
        $out .= '</div>';

        wp_reset_postdata();
        return $out;
    }
    
    /**
     * تبدیل نوع موجودیت به آرایه post types
     * 
     * @param string $type
     * @return array
     */
    private function get_post_types( $type ) {
        $type = strtolower( trim( $type ) );
        
        // اگر 'all' است، همه را برگردان
        if ( $type === 'all' ) {
            return array( 'academy', 'school', 'teacher' );
        }
        
        // اگر چند نوع با کاما جدا شده
        if ( strpos( $type, ',' ) !== false ) {
            $types = array_map( 'trim', explode( ',', $type ) );
            $valid_types = array();
            foreach ( $types as $t ) {
                if ( in_array( $t, array( 'academy', 'school', 'teacher' ), true ) ) {
                    $valid_types[] = $t;
                }
            }
            return ! empty( $valid_types ) ? $valid_types : array( 'academy' );
        }
        
        // یک نوع واحد
        if ( in_array( $type, array( 'academy', 'school', 'teacher' ), true ) ) {
            return array( $type );
        }
        
        // پیش‌فرض
        return array( 'academy' );
    }
}