<?php
class ADP_CPT {
    public function __construct() {
        add_action( 'init', array( $this, 'register' ) );
        // مدیریت ستون‌های ادمین برای نمایش نوع و امتیاز
        add_filter( 'manage_academy_posts_columns', array( $this, 'admin_columns' ) );
        add_action( 'manage_academy_posts_custom_column', array( $this, 'render_admin_column' ), 10, 2 );
    }

    public static function register() {
        $labels = array(
            'name'          => __( 'مراکز/مدارس/معلمان', 'academy-directory-pro' ),
            'singular_name' => __( 'موجودیت آموزشی', 'academy-directory-pro' ),
            'menu_name'     => __( 'دایرکتوری آموزشی', 'academy-directory-pro' ),
        );
        $args = array(
            'labels'        => $labels,
            'public'        => true,
            'has_archive'   => true,
            'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
            'rewrite'       => array( 'slug' => 'academy' ),
            'show_in_rest'  => true,
        );
        register_post_type( 'academy', $args );

        // taxonomy برای نوع موجودیت آموزشی (آموزشگاه، مدرسه، معلم، مربی)
        $tax_labels = array(
            'name'                       => __( 'نوع موجودیت', 'academy-directory-pro' ),
            'singular_name'              => __( 'نوع موجودیت', 'academy-directory-pro' ),
            'search_items'               => __( 'جستجوی نوع', 'academy-directory-pro' ),
            'all_items'                  => __( 'همه نوع‌ها', 'academy-directory-pro' ),
            'edit_item'                  => __( 'ویرایش نوع', 'academy-directory-pro' ),
            'update_item'                => __( 'به‌روزرسانی نوع', 'academy-directory-pro' ),
            'add_new_item'               => __( 'افزودن نوع جدید', 'academy-directory-pro' ),
            'new_item_name'              => __( 'نام نوع جدید', 'academy-directory-pro' ),
            'menu_name'                  => __( 'نوع موجودیت', 'academy-directory-pro' ),
        );
        register_taxonomy( 'adp_entity_type', array( 'academy' ), array(
            'labels'            => $tax_labels,
            'public'            => true,
            'show_in_rest'      => true,
            'hierarchical'      => false,
            'rewrite'           => array( 'slug' => 'entity-type' ),
            'show_admin_column' => true,
        ) );
    }

    /**
     * اطمینان از ایجاد ترم‌های پیش‌فرض برای taxonomy نوع موجودیت
     */
    public static function ensure_default_terms() {
        if ( ! taxonomy_exists( 'adp_entity_type' ) ) {
            return;
        }

        $defaults = array(
            'academy' => __( 'آموزشگاه', 'academy-directory-pro' ),
            'school'  => __( 'مدرسه', 'academy-directory-pro' ),
            'teacher' => __( 'معلم', 'academy-directory-pro' ),
            'coach'   => __( 'مربی', 'academy-directory-pro' ),
        );

        foreach ( $defaults as $slug => $name ) {
            if ( ! term_exists( $slug, 'adp_entity_type' ) ) {
                wp_insert_term( $name, 'adp_entity_type', array( 'slug' => $slug ) );
            }
        }
    }

    /**
     * ستون‌های سفارشی در جدول ادمین
     */
    public function admin_columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            if ( $key === 'title' ) {
                $new[$key] = $label;
                $new['adp_type'] = __( 'نوع', 'academy-directory-pro' );
                $new['adp_rating'] = __( 'امتیاز', 'academy-directory-pro' );
            } else {
                $new[$key] = $label;
            }
        }
        return $new;
    }

    /**
     * رندر مقدار ستون‌های سفارشی
     */
    public function render_admin_column( $column, $post_id ) {
        if ( $column === 'adp_type' ) {
            $terms = wp_get_post_terms( $post_id, 'adp_entity_type', array( 'fields' => 'names' ) );
            if ( is_wp_error( $terms ) || empty( $terms ) ) {
                echo '—';
            } else {
                echo esc_html( implode( ', ', $terms ) );
            }
        } elseif ( $column === 'adp_rating' ) {
            $rating = get_post_meta( $post_id, '_adp_rating', true );
            echo esc_html( $rating !== '' ? $rating : '-' );
        }
    }
}