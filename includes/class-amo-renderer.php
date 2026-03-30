<?php
// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AMO_Renderer {

    public static function init() {
        // Usamos una prioridad muy alta (999) para asegurarnos de que corremos
        // DESPUÉS de que todos los demás plugins hayan registrado sus menús.
        add_action( 'admin_menu', array( __CLASS__, 'replace_admin_menu' ), 999 );
    }

    /**
     * Busca qué menú debe aplicarse al usuario actual.
     */
    private static function get_user_menu_config_id( $user ) {
        // Obtener todos los menús activos ordenados por prioridad (de mayor a menor)
        $menus = get_posts( array(
            'post_type'      => 'amo_menu_set',
            'posts_per_page' => -1,
            'post_status'    => 'publish', // Solo los publicados
            'meta_key'       => '_amo_priority',
            'orderby'        => 'meta_value_num',
            'order'          => 'DESC'
        ) );

        if ( empty( $menus ) ) return false;

        $user_id = $user->ID;
        $user_roles = (array) $user->roles;

        foreach ( $menus as $menu_post ) {
            $post_id = $menu_post->ID;

            // 1. ¿Está en la lista de exclusión? (Prioridad Absoluta)
            $exclude = get_post_meta( $post_id, '_amo_target_users_exclude', true ) ?: array();
            if ( in_array( $user_id, $exclude ) ) {
                continue; // Saltamos este menú y revisamos el siguiente
            }

            // 2. ¿Está en la lista de inclusión directa?
            $include = get_post_meta( $post_id, '_amo_target_users_include', true ) ?: array();
            if ( in_array( $user_id, $include ) ) {
                return $post_id; // ¡Bingo!
            }

            // 3. ¿Tiene un rol que coincide?
            $roles = get_post_meta( $post_id, '_amo_target_roles', true ) ?: array();
            $common_roles = array_intersect( $user_roles, $roles );
            if ( ! empty( $common_roles ) ) {
                return $post_id; // ¡Bingo!
            }
        }

        return false; // Ningún menú configurado coincide con este usuario
    }

    /**
     * Reemplaza la variable global $menu y $submenu de WordPress
     */
    public static function replace_admin_menu() {
        global $menu, $submenu;

        $current_user = wp_get_current_user();
        if ( ! $current_user->exists() ) return;

        // 1. Buscar configuración
        $config_id = self::get_user_menu_config_id( $current_user );
        
        // Si no hay configuración para este usuario, dejamos el menú de WP intacto
        if ( ! $config_id ) return; 

        // 2. Obtener el JSON
        $json_data = get_post_meta( $config_id, '_amo_menu_structure', true );
        if ( empty( $json_data ) || $json_data === '[]' ) return;

        $structure = json_decode( $json_data, true );
        if ( ! is_array( $structure ) ) return;

        // 3. ¡Vaciamos el menú visual de WordPress!
        $menu = array();
        $submenu = array();

        // 4. Reconstruimos basándonos en nuestro JSON
        $position = 1; // Contador para las posiciones del menú principal

        foreach ( $structure as $item ) {
            // Slug / URL del elemento padre
            $slug = ( ! empty( $item['url'] ) && $item['url'] !== '#' ) ? $item['url'] : 'amo-link-' . $position;
            $icon = ( ! empty( $item['icon'] ) ) ? $item['icon'] : 'dashicons-admin-links';

            // Estructura WP: array( Titulo, Capacidad, Slug, Titulo Página, Clases CSS, Hook, Icono )
            $menu[ $position ] = array(
                $item['title'],
                'read', // Usamos 'read' para que se muestre, la seguridad real la da la página de destino
                $slug,
                $item['title'],
                'menu-top',
                'menu-top',
                $icon
            );

            // 5. Tiene Submenús?
            if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
                $submenu[ $slug ] = array(); // Inicializamos el array hijo para este slug padre
                
                $sub_pos = 1;
                foreach ( $item['children'] as $child ) {
                    $child_slug = ( ! empty( $child['url'] ) && $child['url'] !== '#' ) ? $child['url'] : 'amo-sublink-' . $position . '-' . $sub_pos;
                    
                    // Estructura Submenú WP: array( Titulo, Capacidad, Slug, Titulo Página )
                    $submenu[ $slug ][] = array(
                        $child['title'],
                        'read',
                        $child_slug,
                        $child['title']
                    );
                    $sub_pos++;
                }
            }

            $position++;
        }

        // 6. Salvavidas ESTRICTO: Visible para el Fundador (ID 1) o para el Autor que creó este menú específico
        $menu_author_id = get_post_field( 'post_author', $config_id );
        
        if ( $current_user->ID == 1 || $current_user->ID == $menu_author_id ) {
            $menu[9999] = array(
                'Gestor AMO (Seguridad)',
                'manage_options',
                'edit.php?post_type=amo_menu_set',
                'Gestor AMO',
                'menu-top',
                'menu-top',
                'dashicons-shield-alt'
            );
        }
    }
}