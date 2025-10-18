<?php
class ADP_SEO {
    public function __construct() {
        add_action( 'wp_head', array( $this, 'add_meta_tags' ) );
        add_filter( 'wp_sitemaps_add_provider', array( $this, 'add_sitemap' ), 10, 2 );
    }

    public function add_meta_tags() {
        if ( is_singular( 'academy' ) ) {
            $post_id = get_the_ID();
            $description = get_the_excerpt();
            $image = has_post_thumbnail() ? wp_get_attachment_url( get_post_thumbnail_id() ) : '';
            echo '<meta name="description" content="' . esc_attr( $description ) . '">';
            echo '<meta property="og:title" content="' . esc_attr( get_the_title() ) . '">';
            echo '<meta property="og:description" content="' . esc_attr( $description ) . '">';
            echo '<meta property="og:image" content="' . esc_url( $image ) . '">';
        }
    }

    public function add_sitemap( $provider, $name ) {
        if ( $name === 'posts' ) {
            // اضافه کردن آکادمی‌ها به sitemap
            // کد سفارشی برای provider
        }
        return $provider;
    }
}