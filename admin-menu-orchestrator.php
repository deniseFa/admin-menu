<?php
/**
 * Plugin Name: Admin Menu Orchestrator
 * Description: Constructor visual avanzado para gestionar permisos y enlaces del menú de administración.
 * Version: 1.0.0
 * Author: Iara Fabero
 * Text Domain: amo-plugin
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'AMO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AMO_VERSION', '1.0.0' );

// Archivos Funcionales
require_once AMO_PLUGIN_DIR . 'includes/class-amo-cpt.php';
require_once AMO_PLUGIN_DIR . 'includes/class-amo-metaboxes.php';
require_once AMO_PLUGIN_DIR . 'includes/class-amo-builder.php'; 
require_once AMO_PLUGIN_DIR . 'includes/class-amo-renderer.php'; // <-- NUEVO: El renderizador

// Inicializar clases
AMO_CPT::init();
AMO_Meta_Boxes::init();
AMO_Builder::init(); 
AMO_Renderer::init(); // <-- NUEVO: Iniciar el renderizador

// Encolar scripts y estilos
add_action( 'admin_enqueue_scripts', 'amo_enqueue_admin_assets' );
function amo_enqueue_admin_assets( $hook ) {
    global $post;
    
    // Estilos globales de AMO
    wp_enqueue_style( 'amo-google-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', array(), null );
    wp_enqueue_style( 'amo-admin-css', AMO_PLUGIN_URL . 'assets/css/amo-admin.css', array(), AMO_VERSION );

    // Si estamos en la pantalla de edición de nuestro CPT, cargar el constructor
    if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && ( isset($post) && 'amo_menu_set' === $post->post_type ) ) {
        // CSS del Builder
        wp_enqueue_style( 'amo-builder-css', AMO_PLUGIN_URL . 'assets/css/amo-builder.css', array(), AMO_VERSION );

        // JS Librería: SortableJS (Para el Drag and Drop)
        wp_enqueue_script( 'sortable-js', 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js', array(), null, true );
        
        // JS Nuestro Builder
        wp_enqueue_script( 'amo-builder-js', AMO_PLUGIN_URL . 'assets/js/amo-builder.js', array( 'sortable-js' ), AMO_VERSION, true );
    }
}