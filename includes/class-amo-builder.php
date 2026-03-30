<?php
// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AMO_Builder {

    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_builder_metabox' ) );
        add_action( 'save_post_amo_menu_set', array( __CLASS__, 'save_builder_data' ) );
    }

    public static function add_builder_metabox() {
        add_meta_box(
            'amo_menu_builder',
            '🛠️ Constructor Visual del Menú',
            array( __CLASS__, 'render_builder_metabox' ),
            'amo_menu_set',
            'normal',
            'high' // Lo ponemos arriba de las reglas de asignación
        );
    }

    public static function render_builder_metabox( $post ) {
        wp_nonce_field( 'amo_save_builder_data', 'amo_builder_nonce' );

        // Obtener el JSON guardado previamente (si existe)
        $menu_json = get_post_meta( $post->ID, '_amo_menu_structure', true );
        if ( empty( $menu_json ) ) {
            $menu_json = '[]'; // Array vacío por defecto
        }
        ?>
        <div class="amo-builder-wrapper">
            
            <!-- Panel Lateral: Agregar Elementos -->
            <div class="amo-builder-sidebar">
                <h4>Añadir Enlace Personalizado</h4>
                <div class="amo-form-group">
                    <label>Título del Menú</label>
                    <input type="text" id="amo-new-title" placeholder="Ej: Tienda, Soporte, Ventas...">
                </div>
                <div class="amo-form-group">
                    <label>URL o Slug</label>
                    <input type="text" id="amo-new-url" placeholder="Ej: admin.php?page=woocommerce">
                </div>
                <div class="amo-form-group">
                    <label>Icono (Dashicon)</label>
                    <input type="text" id="amo-new-icon" placeholder="Ej: dashicons-cart">
                    <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank" class="amo-help-link">Ver Dashicons</a>
                </div>
                <button type="button" id="amo-add-item-btn" class="amo-btn-pill" style="width: 100%;">+ Añadir al Menú</button>
            </div>

            <!-- Panel Principal: El Lienzo Drag & Drop -->
            <div class="amo-builder-canvas">
                <div class="amo-canvas-header">
                    <h4>Estructura del Menú</h4>
                    <span class="amo-help-text">Arrastra los elementos para ordenarlos.</span>
                </div>
                
                <!-- Aquí se renderizarán los items con JS -->
                <ul id="amo-menu-list" class="amo-sortable-list"></ul>
                
                <div id="amo-empty-state" style="display: none; text-align: center; padding: 40px; color: var(--tk-text-gray);">
                    Aún no hay elementos en el menú. Añade uno desde el panel izquierdo.
                </div>
            </div>

            <!-- EL CEREBRO OCULTO: Aquí guardamos todo como JSON para PHP -->
            <input type="hidden" name="amo_menu_structure" id="amo_menu_structure" value="<?php echo esc_attr( $menu_json ); ?>">
        </div>
        <?php
    }

    public static function save_builder_data( $post_id ) {
        if ( ! isset( $_POST['amo_builder_nonce'] ) || ! wp_verify_nonce( $_POST['amo_builder_nonce'], 'amo_save_builder_data' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        if ( isset( $_POST['amo_menu_structure'] ) ) {
            // Validamos que sea un JSON válido antes de guardar
            $json_data = wp_unslash( $_POST['amo_menu_structure'] );
            if ( is_array( json_decode( $json_data, true ) ) ) {
                update_post_meta( $post_id, '_amo_menu_structure', sanitize_text_field( $json_data ) );
            }
        }
    }
}