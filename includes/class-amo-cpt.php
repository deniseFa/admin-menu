<?php
// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AMO_CPT {

    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
    }

    public static function register_post_type() {
        $labels = array(
            'name'                  => 'Menús Personalizados',
            'singular_name'         => 'Menú Personalizado',
            'menu_name'             => 'Gestor de Menús',
            'add_new'               => 'Crear Nuevo Menú',
            'add_new_item'          => 'Crear Nuevo Menú Personalizado',
            'edit_item'             => 'Editar Menú',
            'all_items'             => 'Todos los Menús',
        );

        $args = array(
            'labels'             => $labels,
            'public'             => false, // No accesible desde el frontend
            'show_ui'            => true,  // Mostrar en el panel de admin
            'show_in_menu'       => true,  // Mostrar en el menú lateral
            'menu_icon'          => 'dashicons-menu-alt3', // Icono para el plugin
            'capability_type'    => 'post',
            'hierarchical'       => false,
            'supports'           => array( 'title' ), // Solo necesitamos título, el resto irá en Metaboxes
            'show_in_rest'       => false, // No necesitamos Gutenberg para esto
        );

        register_post_type( 'amo_menu_set', $args );
    }
}