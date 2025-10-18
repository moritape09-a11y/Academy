<?php
/**
 * کلاس مدیریت Custom Post Types برای موجودیت‌های آموزشی
 * 
 * این کلاس مسئول ثبت انواع پست سفارشی است:
 * - آموزشگاه‌ها (Academies)
 * - مدارس (Schools)
 * - معلمین و مربی‌ها (Teachers/Trainers)
 */
class ADP_CPT {
    
    /**
     * تعریف انواع موجودیت‌های آموزشی
     */
    private static $entity_types = array(
        'academy' => array(
            'singular'     => 'آموزشگاه',
            'plural'       => 'آموزشگاه‌ها',
            'icon'         => 'dashicons-welcome-learn-more',
            'slug'         => 'academy',
            'description'  => 'مدیریت آموزشگاه‌های حرفه‌ای و مهارتی',
        ),
        'school' => array(
            'singular'     => 'مدرسه',
            'plural'       => 'مدارس',
            'icon'         => 'dashicons-building',
            'slug'         => 'school',
            'description'  => 'مدیریت مدارس و موسسات تحصیلی',
        ),
        'teacher' => array(
            'singular'     => 'معلم/مربی',
            'plural'       => 'معلمین و مربی‌ها',
            'icon'         => 'dashicons-businessperson',
            'slug'         => 'teacher',
            'description'  => 'مدیریت معلمین، مربی‌ها و استادان',
        ),
    );
    
    public function __construct() {
        add_action( 'init', array( $this, 'register' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
    }

    /**
     * ثبت تمام Custom Post Types
     */
    public static function register() {
        foreach ( self::$entity_types as $type => $config ) {
            self::register_entity_type( $type, $config );
        }
    }
    
    /**
     * ثبت یک نوع موجودیت
     * 
     * @param string $type نوع موجودیت
     * @param array $config تنظیمات
     */
    private static function register_entity_type( $type, $config ) {
        $labels = array(
            'name'               => $config['plural'],
            'singular_name'      => $config['singular'],
            'menu_name'          => $config['plural'],
            'add_new'            => sprintf( __( 'افزودن %s جدید', 'academy-directory-pro' ), $config['singular'] ),
            'add_new_item'       => sprintf( __( 'افزودن %s جدید', 'academy-directory-pro' ), $config['singular'] ),
            'edit_item'          => sprintf( __( 'ویرایش %s', 'academy-directory-pro' ), $config['singular'] ),
            'new_item'           => sprintf( __( '%s جدید', 'academy-directory-pro' ), $config['singular'] ),
            'view_item'          => sprintf( __( 'مشاهده %s', 'academy-directory-pro' ), $config['singular'] ),
            'search_items'       => sprintf( __( 'جستجوی %s', 'academy-directory-pro' ), $config['plural'] ),
            'not_found'          => sprintf( __( 'هیچ %s یافت نشد', 'academy-directory-pro' ), $config['singular'] ),
            'not_found_in_trash' => sprintf( __( 'هیچ %s در زباله‌دان یافت نشد', 'academy-directory-pro' ), $config['singular'] ),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => true,
            'has_archive'         => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'menu_position'       => 20,
            'menu_icon'           => $config['icon'],
            'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
            'rewrite'             => array( 'slug' => $config['slug'], 'with_front' => false ),
            'show_in_rest'        => true,
            'description'         => $config['description'],
        );
        
        register_post_type( $type, $args );
    }
    
    /**
     * ثبت Taxonomies برای دسته‌بندی
     */
    public static function register_taxonomies() {
        // دسته‌بندی رشته‌ها (برای همه انواع)
        $labels = array(
            'name'              => __( 'رشته‌ها', 'academy-directory-pro' ),
            'singular_name'     => __( 'رشته', 'academy-directory-pro' ),
            'search_items'      => __( 'جستجوی رشته', 'academy-directory-pro' ),
            'all_items'         => __( 'همه رشته‌ها', 'academy-directory-pro' ),
            'edit_item'         => __( 'ویرایش رشته', 'academy-directory-pro' ),
            'update_item'       => __( 'بروزرسانی رشته', 'academy-directory-pro' ),
            'add_new_item'      => __( 'افزودن رشته جدید', 'academy-directory-pro' ),
            'new_item_name'     => __( 'نام رشته جدید', 'academy-directory-pro' ),
            'menu_name'         => __( 'رشته‌ها', 'academy-directory-pro' ),
        );
        
        register_taxonomy( 'subject', array( 'academy', 'school', 'teacher' ), array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'subject' ),
        ) );
        
        // دسته‌بندی نوع موسسه (دولتی/خصوصی)
        $labels = array(
            'name'              => __( 'نوع موسسه', 'academy-directory-pro' ),
            'singular_name'     => __( 'نوع', 'academy-directory-pro' ),
            'menu_name'         => __( 'نوع موسسه', 'academy-directory-pro' ),
        );
        
        register_taxonomy( 'institution_type', array( 'academy', 'school' ), array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'institution-type' ),
        ) );
        
        // مقطع تحصیلی
        $labels = array(
            'name'              => __( 'مقطع تحصیلی', 'academy-directory-pro' ),
            'singular_name'     => __( 'مقطع', 'academy-directory-pro' ),
            'menu_name'         => __( 'مقطع تحصیلی', 'academy-directory-pro' ),
        );
        
        register_taxonomy( 'education_level', array( 'school', 'teacher' ), array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'show_in_rest'      => true,
            'rewrite'           => array( 'slug' => 'education-level' ),
        ) );
    }
    
    /**
     * دریافت لیست انواع موجودیت‌ها
     * 
     * @return array
     */
    public static function get_entity_types() {
        return self::$entity_types;
    }
    
    /**
     * دریافت تنظیمات یک نوع موجودیت
     * 
     * @param string $type
     * @return array|null
     */
    public static function get_entity_config( $type ) {
        return isset( self::$entity_types[ $type ] ) ? self::$entity_types[ $type ] : null;
    }
}