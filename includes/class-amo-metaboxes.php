<?php
// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AMO_Meta_Boxes {

    public static function init() {
        add_action( 'add_meta_boxes', array( __CLASS__, 'add_targeting_metabox' ) );
        add_action( 'save_post_amo_menu_set', array( __CLASS__, 'save_targeting_data' ) );
    }

    public static function add_targeting_metabox() {
        add_meta_box(
            'amo_targeting_rules',
            '⚙️ Reglas de Asignación (¿Quién verá este menú?)',
            array( __CLASS__, 'render_targeting_metabox' ),
            'amo_menu_set',
            'normal',
            'high'
        );
    }

    public static function render_targeting_metabox( $post ) {
        // Añadir nonce para seguridad
        wp_nonce_field( 'amo_save_targeting_data', 'amo_targeting_nonce' );

        // Obtener valores guardados previamente
        $target_roles = get_post_meta( $post->ID, '_amo_target_roles', true ) ?: array();
        $target_users_include = get_post_meta( $post->ID, '_amo_target_users_include', true ) ?: array();
        $target_users_exclude = get_post_meta( $post->ID, '_amo_target_users_exclude', true ) ?: array();
        $priority = get_post_meta( $post->ID, '_amo_priority', true ) ?: 10;

        // Obtener roles de WordPress
        global $wp_roles;
        $all_roles = $wp_roles->roles;

        // Obtener usuarios (Nota: para sitios muy grandes, después lo cambiaremos a AJAX/Select2)
        $all_users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );
        ?>
        <div class="amo-meta-box-container">
            
            <div class="amo-meta-box-row">
                <label for="amo_priority">Prioridad del Menú</label>
                <input type="number" id="amo_priority" name="amo_priority" value="<?php echo esc_attr( $priority ); ?>" />
                <span class="amo-help-text">Si un usuario coincide con varios menús, se aplicará el de mayor prioridad (ej. 99 le gana a 10).</span>
            </div>

            <div class="amo-meta-box-row">
                <label for="amo_target_roles">1. Aplicar a estos Roles (Incluir):</label>
                <select id="amo_target_roles" name="amo_target_roles[]" multiple="multiple">
                    <?php foreach ( $all_roles as $role_key => $role_data ) : ?>
                        <option value="<?php echo esc_attr( $role_key ); ?>" <?php echo in_array( $role_key, $target_roles ) ? 'selected' : ''; ?>>
                            <?php echo esc_html( $role_data['name'] ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="amo-help-text">
                    Haz clic para seleccionar o deseleccionar.
                    <a href="#" class="amo-clear-select" data-target="amo_target_roles" style="color: var(--tk-purple); text-decoration: none; font-weight: 500; margin-left: 10px;">[Limpiar selección]</a>
                </span>
            </div>

            <div class="amo-meta-box-row">
                <label for="amo_target_users_include">2. Aplicar a Usuarios Específicos (Incluir):</label>
                <select id="amo_target_users_include" name="amo_target_users_include[]" multiple="multiple">
                    <?php foreach ( $all_users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>" <?php echo in_array( $user->ID, $target_users_include ) ? 'selected' : ''; ?>>
                            <?php echo esc_html( $user->display_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="amo-help-text">
                    Haz clic para seleccionar o deseleccionar.
                    <a href="#" class="amo-clear-select" data-target="amo_target_users_include" style="color: var(--tk-purple); text-decoration: none; font-weight: 500; margin-left: 10px;">[Limpiar selección]</a>
                </span>
            </div>

            <div class="amo-meta-box-row">
                <label for="amo_target_users_exclude" style="color: #dc2626;">3. Usuarios Excluidos (Prioridad Máxima):</label>
                <select id="amo_target_users_exclude" name="amo_target_users_exclude[]" multiple="multiple">
                    <?php foreach ( $all_users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>" <?php echo in_array( $user->ID, $target_users_exclude ) ? 'selected' : ''; ?>>
                            <?php echo esc_html( $user->display_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="amo-help-text">
                    Si un usuario está en esta lista, <strong>NUNCA</strong> verá este menú, sin importar su rol.
                    <a href="#" class="amo-clear-select" data-target="amo_target_users_exclude" style="color: #dc2626; text-decoration: none; font-weight: 500; margin-left: 10px;">[Limpiar selección]</a>
                </span>
            </div>

        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Botones para vaciar la selección
            var clearBtns = document.querySelectorAll('.amo-clear-select');
            clearBtns.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var targetId = this.getAttribute('data-target');
                    var select = document.getElementById(targetId);
                    if (select) {
                        for (var i = 0; i < select.options.length; i++) {
                            select.options[i].selected = false;
                        }
                    }
                });
            });

            // 2. Mejorar UX del Select Multiple (Toggle al hacer clic sin CTRL)
            var multipleSelectOptions = document.querySelectorAll('.amo-meta-box-container select[multiple] option');
            multipleSelectOptions.forEach(function(option) {
                option.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    var select = this.parentNode;
                    var scrollTop = select.scrollTop; // Guardar posición del scroll
                    
                    this.selected = !this.selected; // Alternar estado
                    
                    // Restaurar posición del scroll inmediatamente para evitar saltos molestos
                    setTimeout(function() {
                        select.scrollTop = scrollTop;
                    }, 0);
                });
            });
        });
        </script>
        <?php
    }

    public static function save_targeting_data( $post_id ) {
        // Verificaciones de seguridad (Nonce, Autosave, Permisos)
        if ( ! isset( $_POST['amo_targeting_nonce'] ) || ! wp_verify_nonce( $_POST['amo_targeting_nonce'], 'amo_save_targeting_data' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Guardar Prioridad
        if ( isset( $_POST['amo_priority'] ) ) {
            update_post_meta( $post_id, '_amo_priority', intval( $_POST['amo_priority'] ) );
        }

        // Guardar Arrays (Roles, Include, Exclude)
        $fields_to_save = array(
            'amo_target_roles' => '_amo_target_roles',
            'amo_target_users_include' => '_amo_target_users_include',
            'amo_target_users_exclude' => '_amo_target_users_exclude',
        );

        foreach ( $fields_to_save as $post_key => $meta_key ) {
            if ( isset( $_POST[ $post_key ] ) && is_array( $_POST[ $post_key ] ) ) {
                $sanitized_array = array_map( 'sanitize_text_field', wp_unslash( $_POST[ $post_key ] ) );
                update_post_meta( $post_id, $meta_key, $sanitized_array );
            } else {
                delete_post_meta( $post_id, $meta_key ); // Si deseleccionan todo, borramos la meta
            }
        }
    }
}