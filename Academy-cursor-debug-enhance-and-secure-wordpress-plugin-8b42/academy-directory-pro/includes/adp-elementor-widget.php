<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ADP_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'academy_directory_pro';
    }

    public function get_title() {
        return __( 'Academy Directory Pro', 'academy-directory-pro' );
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_shortcode',
            [
                'label' => __( 'تنظیمات', 'academy-directory-pro' ),
            ]
        );

        $this->add_control(
            'per_page',
            [
                'label' => __( 'تعداد در صفحه', 'academy-directory-pro' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => get_option( 'adp_per_page', 9 ),
                'min' => 1,
            ]
        );

        $this->add_control(
            'cols',
            [
                'label' => __( 'تعداد ستون‌ها', 'academy-directory-pro' ),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'default' => get_option( 'adp_cols', 3 ),
                'min' => 1,
                'max' => 4,
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $atts = [
            'per_page' => $settings['per_page'],
            'cols' => $settings['cols'],
        ];
        echo do_shortcode( '[academy_directory_pro per_page="' . esc_attr( $settings['per_page'] ) . '" cols="' . esc_attr( $settings['cols'] ) . '"]' );
    }
}