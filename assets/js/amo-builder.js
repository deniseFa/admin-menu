document.addEventListener('DOMContentLoaded', function() {
    
    const listEl = document.getElementById('amo-menu-list');
    const hiddenInput = document.getElementById('amo_menu_structure');
    const emptyState = document.getElementById('amo-empty-state');
    
    if (!listEl || !hiddenInput) return;

    // 1. Cargar datos iniciales desde el input oculto
    let menuData = [];
    try {
        menuData = JSON.parse(hiddenInput.value);
    } catch(e) {
        menuData = [];
    }

    // 2. Inicializar SortableJS para el nivel principal (Padres)
    const mainSortable = new Sortable(listEl, {
        group: 'parents', // Los padres solo se ordenan entre padres
        animation: 150,
        ghostClass: 'amo-sortable-ghost',
        handle: '.amo-item-header',
        onEnd: function () {
            updateDataFromDOM();
        }
    });

    // 3. Renderizar items iniciales
    renderMenu();

    // 4. Agregar Nuevo Elemento (Nivel Principal)
    document.getElementById('amo-add-item-btn').addEventListener('click', function() {
        const titleInput = document.getElementById('amo-new-title');
        const urlInput = document.getElementById('amo-new-url');
        const iconInput = document.getElementById('amo-new-icon');

        if (!titleInput.value.trim()) {
            alert('Por favor, ingresa un título para el menú.');
            return;
        }

        const newItem = {
            id: 'menu_' + Date.now(),
            title: titleInput.value.trim(),
            url: urlInput.value.trim() || '#',
            icon: iconInput.value.trim() || 'dashicons-admin-links',
            children: [] // Iniciamos con array de submenús vacío
        };

        menuData.push(newItem);
        
        // Limpiar formulario
        titleInput.value = ''; urlInput.value = ''; iconInput.value = '';
        
        renderMenu();
        updateHiddenInput();
    });

    // 5. Renderizar todo el menú
    function renderMenu() {
        listEl.innerHTML = ''; 
        
        if (menuData.length === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
            menuData.forEach(item => {
                const li = createMenuDOMElement(item, false);
                listEl.appendChild(li);
            });
        }
    }

    // 6. Constructor de Elementos DOM (Padres e Hijos)
    function createMenuDOMElement(item, isChild = false) {
        const li = document.createElement('li');
        // Asignamos clases distintas si es hijo o padre
        li.className = isChild ? 'amo-menu-item amo-child-item' : 'amo-menu-item amo-parent-item';
        li.dataset.id = item.id;

        // Si es hijo, no tiene input de icono ni botón de añadir sub-menú
        const iconInput = isChild ? '' : `
            <div class="amo-form-group">
                <label>Icono</label>
                <input type="text" class="amo-edit-icon" value="${item.icon || ''}">
            </div>`;
        const iconDisplay = isChild ? '' : `<span class="dashicons ${item.icon || 'dashicons-admin-links'}"></span>`;
        const addSubBtn = isChild ? '' : `<button type="button" class="amo-add-child-btn amo-btn-pill" style="margin-top: 5px; font-size: 12px; padding: 6px 14px; background-color: var(--tk-text-dark);">+ Añadir Sub-menú</button>`;

        li.innerHTML = `
            <div class="amo-item-header">
                <div class="amo-item-title">
                    ${iconDisplay}
                    <span class="amo-display-title">${item.title}</span>
                </div>
                <div class="amo-item-actions">
                    <button type="button" class="amo-edit-btn">✏️ Editar</button>
                    <button type="button" class="amo-delete-btn">❌</button>
                </div>
            </div>
            <div class="amo-item-body">
                <div class="amo-form-group">
                    <label>Título</label>
                    <input type="text" class="amo-edit-title" value="${item.title}">
                </div>
                <div class="amo-form-group">
                    <label>URL</label>
                    <input type="text" class="amo-edit-url" value="${item.url}">
                </div>
                ${iconInput}
                ${addSubBtn}
            </div>
            ${!isChild ? `<ul class="amo-sub-sortable-list"></ul>` : ''}
        `;

        // Eventos: Desplegar editor (usamos :scope para no afectar a los hijos)
        li.querySelector(':scope > .amo-item-header .amo-edit-btn').addEventListener('click', function() {
            li.querySelector(':scope > .amo-item-body').classList.toggle('is-open');
        });

        // Eventos: Eliminar
        li.querySelector(':scope > .amo-item-header .amo-delete-btn').addEventListener('click', function() {
            if (confirm('¿Eliminar este elemento?')) {
                li.remove();
                updateDataFromDOM();
            }
        });

        // Eventos: Escribir y actualizar título visual en tiempo real
        const inputs = li.querySelectorAll(':scope > .amo-item-body input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.classList.contains('amo-edit-title')) {
                    li.querySelector(':scope > .amo-item-header .amo-display-title').innerText = this.value || '(Sin título)';
                }
                if (this.classList.contains('amo-edit-icon') && !isChild) {
                    const iconSpan = li.querySelector(':scope > .amo-item-header .dashicons');
                    if(iconSpan) iconSpan.className = 'dashicons ' + this.value;
                }
                updateDataFromDOM(); 
            });
        });

        // Lógica exclusiva para elementos Padre (Manejo de submenús)
        if (!isChild) {
            const subList = li.querySelector(':scope > .amo-sub-sortable-list');
            
            // Si el item ya tiene hijos en el JSON, los renderizamos
            if (item.children && item.children.length > 0) {
                item.children.forEach(child => {
                    subList.appendChild(createMenuDOMElement(child, true));
                });
            }

            // Inicializar Sortable anidado para los hijos de este padre
            new Sortable(subList, {
                group: 'children', // Los hijos pueden moverse entre diferentes listas de submenús
                animation: 150,
                ghostClass: 'amo-sortable-ghost',
                handle: '.amo-item-header',
                onEnd: function () {
                    updateDataFromDOM();
                }
            });

            // Acción del botón para crear un nuevo hijo
            const addBtn = li.querySelector(':scope > .amo-item-body .amo-add-child-btn');
            addBtn.addEventListener('click', function() {
                const newChild = {
                    id: 'sub_' + Date.now(),
                    title: 'Nuevo Sub-menú',
                    url: '#'
                };
                const childEl = createMenuDOMElement(newChild, true);
                subList.appendChild(childEl);
                updateDataFromDOM();
            });
        }

        return li;
    }

    // 7. Reconstruir JSON a partir del DOM (Ahora lee estructura de árbol)
    function updateDataFromDOM() {
        // Seleccionamos solo los items principales (padres directos)
        const parentItems = listEl.querySelectorAll(':scope > .amo-parent-item');
        const newData = [];

        parentItems.forEach(parentLi => {
            const parentObj = {
                id: parentLi.dataset.id,
                title: parentLi.querySelector(':scope > .amo-item-body .amo-edit-title').value,
                url: parentLi.querySelector(':scope > .amo-item-body .amo-edit-url').value,
                icon: parentLi.querySelector(':scope > .amo-item-body .amo-edit-icon').value,
                children: []
            };

            // Buscamos los hijos dentro de este padre específico
            const childItems = parentLi.querySelectorAll(':scope > .amo-sub-sortable-list > .amo-child-item');
            childItems.forEach(childLi => {
                parentObj.children.push({
                    id: childLi.dataset.id,
                    title: childLi.querySelector(':scope > .amo-item-body .amo-edit-title').value,
                    url: childLi.querySelector(':scope > .amo-item-body .amo-edit-url').value
                });
            });

            newData.push(parentObj);
        });

        menuData = newData;
        updateHiddenInput();

        // Controlar el mensaje de estado vacío
        if (menuData.length === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }
    }

    function updateHiddenInput() {
        hiddenInput.value = JSON.stringify(menuData);
    }
});