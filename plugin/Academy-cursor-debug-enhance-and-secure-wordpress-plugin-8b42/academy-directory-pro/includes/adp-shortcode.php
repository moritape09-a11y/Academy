<?php
class ADP_Shortcode {
    public function __construct() {
        add_shortcode( 'academy_directory_pro', array( $this, 'render' ) );
    }

    public function render( $atts ) {
        $atts = shortcode_atts( array( 'per_page' => get_option( 'adp_per_page', 9 ), 'cols' => get_option( 'adp_cols', 3 ) ), $atts, 'academy_directory_pro' );

        $search = isset( $_REQUEST['adp_q'] ) ? sanitize_text_field( $_REQUEST['adp_q'] ) : '';
        $subject = isset( $_REQUEST['adp_subject'] ) ? sanitize_text_field( $_REQUEST['adp_subject'] ) : '';
        $min_rating = isset( $_REQUEST['adp_min_rating'] ) ? intval( $_REQUEST['adp_min_rating'] ) : 0;
        $type = isset( $_REQUEST['adp_type'] ) ? sanitize_text_field( $_REQUEST['adp_type'] ) : '';
        $per_page = intval( $atts['per_page'] );
        $cols = max( 1, intval( $atts['cols'] ) );
        $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

        // ایجاد کلید کش امن
        $cache_params = array(
            'search' => $search,
            'subject' => $subject,
            'min_rating' => $min_rating,
            'per_page' => $per_page,
            'cols' => $cols,
            'paged' => $paged
        );
        $transient_key = 'adp_query_' . md5( wp_json_encode( $cache_params ) );
        $q = get_transient( $transient_key );
        if ( false === $q ) {
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
            if ( ! empty( $type ) ) {
                $args['tax_query'] = array(
                    array(
                        'taxonomy' => 'adp_entity_type',
                        'field'    => 'slug',
                        'terms'    => array( $type ),
                    )
                );
            }

            $q = new WP_Query( $args );
            set_transient( $transient_key, $q, 300 );
        }

        // فرم فیلتر
        $out = ADP_Helpers::render_filter_form( $search, $subject, $min_rating, $cols );

        // نتایج - فقط Grid View
        $out .= '<div id="adp-result-wrapper" data-cols="' . esc_attr( $cols ) . '">';
        
        if ( ! $q->have_posts() ) {
            $out .= '<p class="adp-empty">' . __( 'هیچ آموزشی یافت نشد.', 'academy-directory-pro' ) . '</p>';
        } else {
            $out .= '<div class="adp-grid adp-cols-' . esc_attr( $cols ) . '">';
            while ( $q->have_posts() ) {
                $q->the_post();
                $out .= ADP_Helpers::render_card( get_the_ID() );
            }
            $out .= '</div>';
            $out .= '<div class="adp-pagination">' . paginate_links( array( 'total' => $q->max_num_pages, 'current' => $paged ) ) . '</div>';
        }
        
        $out .= '</div>';

        wp_reset_postdata();
        return $out;
    }
}