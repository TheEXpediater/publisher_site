(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('cp-category-modal');

        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
            return;
        }

        var form = modalElement.querySelector('[data-cp-category-form]');
        var categoryId = modalElement.querySelector('[data-cp-category-id]');
        var modeField = modalElement.querySelector('[data-cp-category-mode]');
        var activeField = modalElement.querySelector('[data-cp-category-active]');
        var activeLabel = modalElement.querySelector('[data-cp-category-active-label]');
        var nameField = modalElement.querySelector('[data-cp-category-name]');
        var slugField = modalElement.querySelector('[data-cp-category-slug]');
        var descriptionField = modalElement.querySelector('[data-cp-category-description]');
        var title = modalElement.querySelector('[data-cp-category-modal-title]');
        var submitButton = modalElement.querySelector('[data-cp-category-submit]');

        if (!form || !categoryId || !modeField || !activeField || !nameField || !slugField || !descriptionField || !title || !submitButton) {
            return;
        }

        var addTitle = modalElement.getAttribute('data-cp-add-title') || 'Add Category';
        var editTitle = modalElement.getAttribute('data-cp-edit-title') || 'Edit Category';
        var addLabel = modalElement.getAttribute('data-cp-add-label') || 'Add Category';
        var editLabel = modalElement.getAttribute('data-cp-edit-label') || 'Update Category';
        var addConfirm = modalElement.getAttribute('data-cp-add-confirm') || 'Create this category?';
        var editConfirm = modalElement.getAttribute('data-cp-edit-confirm') || 'Save these category changes?';
        var editConfirmDeactivating = modalElement.getAttribute('data-cp-edit-confirm-deactivating') || editConfirm;
        var editConfirmActivating = modalElement.getAttribute('data-cp-edit-confirm-activating') || editConfirm;
        var activeText = 'Active';
        var inactiveText = 'Inactive';

        // isEditMode/initialState are the single source of truth for whether
        // this submit is an update or a create. They are set synchronously,
        // directly from the button that was actually clicked (see below) -
        // never inferred indirectly from a Bootstrap modal event's
        // relatedTarget, which is one layer removed from the real click and
        // was the root cause of edits being submitted as creates.
        var isEditMode = false;
        var initialState = null;

        function currentState() {
            return {
                active: activeField.checked,
                name: nameField.value,
                slug: slugField.value,
                description: descriptionField.value
            };
        }

        function statesMatch(a, b) {
            return a.active === b.active && a.name === b.name && a.slug === b.slug && a.description === b.description;
        }

        function refreshActiveLabel() {
            if (activeLabel) {
                activeLabel.textContent = activeField.checked ? activeText : inactiveText;
            }
        }

        /**
         * Keeps all three confirm-dialog attributes (title, message, button
         * label - read by app.js's initConfirmModal() from this button at
         * click time) in sync with the actual current mode. Previously only
         * data-cp-confirm (the message) was updated here; data-cp-confirm-title
         * and data-cp-confirm-label were left exactly as PHP first rendered
         * them (Add-mode text, since the page normally loads with no
         * category being edited) and never touched again - so switching
         * into Edit mode showed a mismatched dialog: an Edit-correct
         * message alongside stale "Add Category" title/button text.
         */
        function refreshConfirmMessage() {
            if (!isEditMode) {
                submitButton.setAttribute('data-cp-confirm-title', addTitle);
                submitButton.setAttribute('data-cp-confirm', addConfirm);
                submitButton.setAttribute('data-cp-confirm-label', addLabel);
                return;
            }

            submitButton.setAttribute('data-cp-confirm-title', editTitle);
            submitButton.setAttribute('data-cp-confirm-label', editLabel);

            if (initialState && activeField.checked !== initialState.active) {
                submitButton.setAttribute('data-cp-confirm', activeField.checked ? editConfirmActivating : editConfirmDeactivating);
                return;
            }

            submitButton.setAttribute('data-cp-confirm', editConfirm);
        }

        function refreshDirtyState() {
            refreshConfirmMessage();

            if (!isEditMode) {
                submitButton.disabled = false;
                return;
            }

            var isClean = initialState && statesMatch(currentState(), initialState);
            submitButton.disabled = Boolean(isClean);
        }

        function captureInitialState() {
            initialState = currentState();
            refreshDirtyState();
        }

        function setAddMode() {
            isEditMode = false;
            initialState = null;
            form.reset();
            categoryId.value = '0';
            modeField.value = 'create';
            activeField.checked = true;
            nameField.value = '';
            slugField.value = '';
            descriptionField.value = '';
            title.textContent = addTitle;
            submitButton.textContent = addLabel;
            refreshActiveLabel();
            refreshDirtyState();
        }

        function setEditMode(category) {
            category = category && 'object' === typeof category ? category : {};
            var id = parseInt(category.id, 10) || 0;

            // A category row with no resolvable ID cannot be edited - fall
            // back to add mode rather than silently submitting id=0, which
            // is exactly what would route the save into "create a new
            // category" instead of updating the intended one.
            if (!id) {
                setAddMode();
                return;
            }

            isEditMode = true;
            categoryId.value = String(id);
            modeField.value = 'edit';
            activeField.checked = false !== category.active;
            nameField.value = category.name || '';
            slugField.value = category.slug || '';
            descriptionField.value = category.description || '';
            title.textContent = editTitle;
            submitButton.textContent = editLabel;
            refreshActiveLabel();
            captureInitialState();
        }

        function categoryFromTrigger(trigger) {
            if (!trigger) {
                return null;
            }

            var encoded = trigger.getAttribute('data-cp-category');
            if (!encoded) {
                return null;
            }

            try {
                return JSON.parse(encoded);
            } catch (error) {
                return null;
            }
        }

        [activeField, nameField, slugField, descriptionField].forEach(function (field) {
            field.addEventListener('input', function () {
                refreshDirtyState();
            });
            field.addEventListener('change', function () {
                refreshActiveLabel();
                refreshDirtyState();
            });
        });

        // Set edit/add state directly from the click on the specific row's
        // trigger button, at click time - not from the modal's own
        // show.bs.modal event. Bootstrap still opens the modal via its own
        // data-bs-toggle="modal" handling on the same click; this listener
        // only owns which category (if any) the form is populated with.
        document.querySelectorAll('[data-cp-category-edit]').forEach(function (button) {
            button.addEventListener('click', function () {
                setEditMode(categoryFromTrigger(button));
            });
        });

        var addButton = document.querySelector('[data-cp-category-add]');
        if (addButton) {
            addButton.addEventListener('click', function () {
                setAddMode();
            });
        }

        modalElement.addEventListener('shown.bs.modal', function () {
            nameField.focus();
        });

        if ('1' === modalElement.getAttribute('data-cp-open') && parseInt(categoryId.value, 10) > 0) {
            // The server already rendered this modal pre-filled for an
            // existing category (e.g. redisplaying after a validation
            // error) - the fields are correct as-is, this just needs to
            // flip on edit-mode tracking against that already-rendered state.
            isEditMode = true;
            captureInitialState();
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        } else {
            setAddMode();
        }
        modalElement.setAttribute('data-cp-open', '0');
    });
}());

/**
 * Category Manager "View Menu" / "Edit Menu" preview and editor.
 *
 * The View mode preview is the exact markup cp_render_primary_navigation_markup()/
 * cp_render_footer_navigation_markup() produce for the live site (see
 * templates/categories.php) - never a separate, fabricated preview
 * dataset. Edit mode works from two independent pending states (header,
 * footer), each with its own saved order and typography (includes/nav-menu.php),
 * rendered into their own Header/Footer tabs; nothing is written to
 * WordPress until Save is confirmed, and switching tabs never discards the
 * other tab's pending edits. Every preview - View mode's and both tabs' -
 * is built by the same buildSurfaceMarkup() below from category term
 * id/name/url data, so a rename in the Category Manager (which changes
 * only the term's name, never its id) is reflected the next time this
 * modal is opened without the saved order ever needing to be touched.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('cp-menu-modal');
        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
            return;
        }

        var config = {};
        try {
            config = JSON.parse(modalElement.getAttribute('data-cp-menu-editor') || '{}');
        } catch (error) {
            config = {};
        }

        var maxVisible = parseInt(config.maxVisible, 10) || 7;
        var aboutUsUrl = config.aboutUsUrl || '#';
        var fontFamilyCssBySurfaceKey = {
            'default': '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif',
            'arial': 'Arial,Helvetica,sans-serif',
            'arial-black': '"Arial Black",Arial,sans-serif',
            'georgia': 'Georgia,serif',
            'times': '"Times New Roman",Times,serif',
            'verdana': 'Verdana,Geneva,sans-serif',
            'tahoma': 'Tahoma,Geneva,sans-serif',
            'trebuchet': '"Trebuchet MS",Helvetica,sans-serif',
            'courier': '"Courier New",Courier,monospace'
        };

        function normalizeSurfaceConfig(raw) {
            raw = raw && 'object' === typeof raw ? raw : {};
            return {
                categories: Array.isArray(raw.categories) ? raw.categories.map(function (category) {
                    return { id: parseInt(category.id, 10) || 0, name: String(category.name || ''), url: String(category.url || '') };
                }) : [],
                fontFamily: raw.fontFamily || 'default',
                fontSize: parseInt(raw.fontSize, 10) || 13
            };
        }

        var savedState = {
            header: normalizeSurfaceConfig(config.header),
            footer: normalizeSurfaceConfig(config.footer)
        };

        function cloneSurface(surface) {
            return {
                categories: surface.categories.map(function (category) {
                    return { id: category.id, name: category.name, url: category.url };
                }),
                fontFamily: surface.fontFamily,
                fontSize: surface.fontSize
            };
        }

        function cloneState(source) {
            return { header: cloneSurface(source.header), footer: cloneSurface(source.footer) };
        }

        var state = cloneState(savedState);
        var activeTab = 'header';
        var isEditing = false;
        var isSaving = false;
        var draggedId = null;
        var idSequence = 0;

        var viewModeEl = modalElement.querySelector('[data-cp-menu-view-mode]');
        var editModeEl = modalElement.querySelector('[data-cp-menu-edit-mode]');
        var feedback = modalElement.querySelector('[data-cp-menu-feedback]');
        var editButton = modalElement.querySelector('[data-cp-menu-edit]');
        var closeButton = modalElement.querySelector('[data-cp-menu-close]');
        var cancelButton = modalElement.querySelector('[data-cp-menu-cancel]');
        var saveButton = modalElement.querySelector('[data-cp-menu-save]');
        var tabButtons = Array.prototype.slice.call(modalElement.querySelectorAll('[data-cp-menu-tab]'));
        var tabPanels = {};
        modalElement.querySelectorAll('[data-cp-menu-tab-panel]').forEach(function (panel) {
            tabPanels[panel.getAttribute('data-cp-menu-tab-panel')] = panel;
        });

        if (!viewModeEl || !editModeEl || !editButton || !saveButton) {
            return;
        }

        function escapeHtml(value) {
            var div = document.createElement('div');
            div.textContent = String(value == null ? '' : value);
            return div.innerHTML;
        }

        function showFeedback(type, message) {
            if (!feedback) {
                return;
            }
            feedback.className = 'cp-menu-editor-feedback cp-menu-editor-feedback-' + type;
            feedback.textContent = message;
            feedback.hidden = false;
        }

        function clearFeedback() {
            if (!feedback) {
                return;
            }
            feedback.hidden = true;
            feedback.textContent = '';
            feedback.className = 'cp-menu-editor-feedback';
        }

        function wireAboutToggle(root) {
            var toggle = root.querySelector('.cp-primary-nav-about-toggle');
            var dropdown = root.querySelector('.cp-primary-nav-about-menu');
            if (!toggle || !dropdown) {
                return;
            }
            toggle.addEventListener('click', function () {
                var open = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
                if (open) {
                    dropdown.setAttribute('hidden', 'hidden');
                } else {
                    dropdown.removeAttribute('hidden');
                }
            });
        }

        function applyTypography(root, surfaceState) {
            var navEl = root.querySelector('.cp-primary-nav, .cp-footer-nav');
            if (!navEl) {
                return;
            }
            var fontFamilyCss = fontFamilyCssBySurfaceKey[surfaceState.fontFamily] || fontFamilyCssBySurfaceKey.default;
            navEl.style.setProperty('--cp-nav-font-family', fontFamilyCss);
            navEl.style.setProperty('--cp-nav-font-size', surfaceState.fontSize + 'px');
        }

        /**
         * Builds the same markup structure cp_render_primary_navigation_markup()/
         * cp_render_footer_navigation_markup() produce, from local pending
         * state, so a preview can update instantly as categories are
         * reordered without a server round-trip. The header applies the
         * 7-direct-category cap plus the About Us overflow dropdown; the
         * footer lists every category flat, matching the live footer's own
         * uncapped presentation.
         */
        function buildSurfaceMarkup(surface, surfaceState, uniqueSuffix) {
            if ('footer' === surface) {
                var footerItems = surfaceState.categories.map(function (category) {
                    return '<li class="cp-footer-nav-item"><a href="' + escapeHtml(category.url) + '">' + escapeHtml(category.name) + '</a></li>';
                }).join('');
                footerItems += '<li class="cp-footer-nav-item cp-footer-nav-about"><a href="' + escapeHtml(aboutUsUrl) + '">About Us</a></li>';
                return '<nav class="cp-footer-nav"><ul class="cp-footer-nav-menu">' + footerItems + '</ul></nav>';
            }

            var visible = surfaceState.categories.slice(0, maxVisible);
            var overflow = surfaceState.categories.slice(maxVisible);
            var menuId = 'cp-menu-preview-menu-' + uniqueSuffix;
            var aboutMenuId = 'cp-menu-preview-about-menu-' + uniqueSuffix;

            var html = visible.map(function (category) {
                return '<li class="cp-primary-nav-item"><a href="' + escapeHtml(category.url) + '">' + escapeHtml(category.name) + '</a></li>';
            }).join('');

            html += '<li class="cp-primary-nav-item cp-primary-nav-about">'
                + '<a class="cp-primary-nav-about-link" href="' + escapeHtml(aboutUsUrl) + '">About Us</a>';

            if (overflow.length) {
                html += '<button type="button" class="cp-primary-nav-about-toggle" aria-expanded="false" aria-controls="' + aboutMenuId + '" aria-label="Show more sections"><span aria-hidden="true"></span></button>'
                    + '<ul id="' + aboutMenuId + '" class="cp-primary-nav-about-menu" hidden>'
                    + overflow.map(function (category) {
                        return '<li><a href="' + escapeHtml(category.url) + '">' + escapeHtml(category.name) + '</a></li>';
                    }).join('')
                    + '</ul>';
            }

            html += '</li>';

            return '<nav class="cp-primary-nav"><div class="cp-primary-nav-inner">'
                + '<ul id="' + menuId + '" class="cp-primary-nav-menu">' + html + '</ul>'
                + '</div></nav>';
        }

        function renderPreviewInto(container, surface, surfaceState) {
            if (!container) {
                return;
            }
            idSequence += 1;
            container.innerHTML = buildSurfaceMarkup(surface, surfaceState, 'p' + idSequence);
            wireAboutToggle(container);
            applyTypography(container, surfaceState);
        }

        function renderViewPreviews() {
            renderPreviewInto(modalElement.querySelector('[data-cp-menu-view-preview="header"]'), 'header', state.header);
            renderPreviewInto(modalElement.querySelector('[data-cp-menu-view-preview="footer"]'), 'footer', state.footer);
        }

        function renderTabPreview(surface) {
            renderPreviewInto(modalElement.querySelector('[data-cp-menu-preview="' + surface + '"]'), surface, state[surface]);
        }

        function renderEditorList(surface) {
            var list = modalElement.querySelector('[data-cp-menu-list="' + surface + '"]');
            if (!list) {
                return;
            }
            var surfaceState = state[surface];
            list.innerHTML = '';

            if (!surfaceState.categories.length) {
                var empty = document.createElement('li');
                empty.className = 'cp-menu-editor-empty';
                empty.textContent = 'No categories yet.';
                list.appendChild(empty);
                return;
            }

            surfaceState.categories.forEach(function (category, index) {
                var item = document.createElement('li');
                item.className = 'cp-menu-editor-item';
                item.draggable = true;
                item.setAttribute('data-term-id', String(category.id));
                if ('header' === surface && index === maxVisible - 1) {
                    item.classList.add('cp-menu-editor-item-last-visible');
                }

                var handle = document.createElement('span');
                handle.className = 'cp-menu-editor-drag-handle';
                handle.setAttribute('aria-hidden', 'true');
                handle.innerHTML = '<i class="bi bi-grip-vertical"></i>';

                var label = document.createElement('span');
                label.className = 'cp-menu-editor-item-name';
                label.textContent = category.name;

                var controls = document.createElement('span');
                controls.className = 'cp-menu-editor-item-controls';

                if ('header' === surface) {
                    var badge = document.createElement('span');
                    badge.className = 'cp-menu-editor-item-badge';
                    badge.textContent = index < maxVisible ? 'Header' : 'About Us dropdown';
                    item.appendChild(handle);
                    item.appendChild(label);
                    item.appendChild(badge);
                } else {
                    item.appendChild(handle);
                    item.appendChild(label);
                }

                var upButton = document.createElement('button');
                upButton.type = 'button';
                upButton.className = 'cp-menu-editor-move-btn';
                upButton.setAttribute('aria-label', 'Move ' + category.name + ' left');
                upButton.innerHTML = '<i class="bi bi-chevron-left" aria-hidden="true"></i>';
                upButton.disabled = 0 === index;
                upButton.addEventListener('click', function () {
                    moveCategory(surface, index, index - 1);
                });

                var downButton = document.createElement('button');
                downButton.type = 'button';
                downButton.className = 'cp-menu-editor-move-btn';
                downButton.setAttribute('aria-label', 'Move ' + category.name + ' right');
                downButton.innerHTML = '<i class="bi bi-chevron-right" aria-hidden="true"></i>';
                downButton.disabled = index === surfaceState.categories.length - 1;
                downButton.addEventListener('click', function () {
                    moveCategory(surface, index, index + 1);
                });

                controls.appendChild(upButton);
                controls.appendChild(downButton);
                item.appendChild(controls);

                item.addEventListener('dragstart', function () {
                    draggedId = category.id;
                    item.classList.add('is-dragging');
                });
                item.addEventListener('dragend', function () {
                    draggedId = null;
                    item.classList.remove('is-dragging');
                    list.querySelectorAll('.drag-over').forEach(function (el) {
                        el.classList.remove('drag-over');
                    });
                });
                item.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    item.classList.add('drag-over');
                });
                item.addEventListener('dragleave', function () {
                    item.classList.remove('drag-over');
                });
                item.addEventListener('drop', function (event) {
                    event.preventDefault();
                    item.classList.remove('drag-over');
                    if (null === draggedId || draggedId === category.id) {
                        return;
                    }
                    var fromIndex = surfaceState.categories.findIndex(function (c) { return c.id === draggedId; });
                    var toIndex = surfaceState.categories.findIndex(function (c) { return c.id === category.id; });
                    if (fromIndex > -1 && toIndex > -1) {
                        moveCategory(surface, fromIndex, toIndex);
                    }
                });

                list.appendChild(item);
            });
        }

        function moveCategory(surface, fromIndex, toIndex) {
            var surfaceState = state[surface];
            if (toIndex < 0 || toIndex >= surfaceState.categories.length || fromIndex === toIndex) {
                return;
            }
            var moved = surfaceState.categories.splice(fromIndex, 1)[0];
            surfaceState.categories.splice(toIndex, 0, moved);
            renderEditorList(surface);
            renderTabPreview(surface);
        }

        function renderTab(surface) {
            renderTabPreview(surface);
            renderEditorList(surface);

            var familyField = modalElement.querySelector('[data-cp-menu-font-family="' + surface + '"]');
            var sizeField = modalElement.querySelector('[data-cp-menu-font-size="' + surface + '"]');
            if (familyField) {
                familyField.value = state[surface].fontFamily;
            }
            if (sizeField) {
                sizeField.value = String(state[surface].fontSize);
            }
        }

        function switchTab(surface) {
            activeTab = surface;
            tabButtons.forEach(function (button) {
                var isActive = button.getAttribute('data-cp-menu-tab') === surface;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.tabIndex = isActive ? 0 : -1;
            });
            Object.keys(tabPanels).forEach(function (key) {
                tabPanels[key].hidden = key !== surface;
            });
            renderTab(surface);
        }

        tabButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                switchTab(button.getAttribute('data-cp-menu-tab'));
            });
        });

        function enterEditMode() {
            isEditing = true;
            clearFeedback();
            viewModeEl.hidden = true;
            editModeEl.hidden = false;
            editButton.hidden = true;
            saveButton.hidden = false;
            if (closeButton) {
                closeButton.hidden = true;
            }
            if (cancelButton) {
                cancelButton.hidden = false;
            }
            switchTab('header');
        }

        function exitEditMode(discard) {
            isEditing = false;
            if (discard) {
                state = cloneState(savedState);
            }
            viewModeEl.hidden = false;
            editModeEl.hidden = true;
            editButton.hidden = false;
            saveButton.hidden = true;
            if (closeButton) {
                closeButton.hidden = false;
            }
            if (cancelButton) {
                cancelButton.hidden = true;
            }
            renderViewPreviews();
        }

        editButton.addEventListener('click', enterEditMode);

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                clearFeedback();
                exitEditMode(true);
            });
        }

        ['header', 'footer'].forEach(function (surface) {
            var familyField = modalElement.querySelector('[data-cp-menu-font-family="' + surface + '"]');
            var sizeField = modalElement.querySelector('[data-cp-menu-font-size="' + surface + '"]');
            if (familyField) {
                familyField.addEventListener('change', function () {
                    state[surface].fontFamily = familyField.value;
                    renderTabPreview(surface);
                });
            }
            if (sizeField) {
                sizeField.addEventListener('change', function () {
                    state[surface].fontSize = parseInt(sizeField.value, 10) || state[surface].fontSize;
                    renderTabPreview(surface);
                });
            }
        });

        function resetSaveButton() {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i> Save Changes';
        }

        /**
         * The actual AJAX save, only ever invoked from the confirm dialog's
         * onConfirm callback below - never directly from the Save click -
         * so a save can't be triggered without the admin explicitly
         * confirming it first, and isSaving blocks a second confirm/save
         * from firing while one is already in flight. Saves both tabs'
         * pending state together in one request.
         */
        function performSave() {
            if (isSaving) {
                return;
            }
            isSaving = true;
            clearFeedback();
            saveButton.disabled = true;
            saveButton.textContent = 'Saving...';

            var form = new FormData();
            form.append('action', 'cp_save_nav_menu');
            form.append('nonce', config.nonce || '');
            state.header.categories.forEach(function (category) {
                form.append('header_order[]', category.id);
            });
            state.footer.categories.forEach(function (category) {
                form.append('footer_order[]', category.id);
            });
            form.append('header_font_family', state.header.fontFamily);
            form.append('header_font_size', String(state.header.fontSize));
            form.append('footer_font_family', state.footer.fontFamily);
            form.append('footer_font_size', String(state.footer.fontSize));

            window.fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: form
            }).then(function (response) {
                return response.json().catch(function () {
                    return null;
                }).then(function (json) {
                    return { ok: response.ok, json: json };
                });
            }).then(function (result) {
                var json = result.json;
                if (!result.ok || !json || !json.success) {
                    throw new Error(json && json.data && json.data.message ? json.data.message : 'Unable to save menu changes. Please try again.');
                }

                // The saved state comes from what the server actually
                // accepted, not merely assumed from the local client state
                // that was sent.
                var data = json.data || {};
                ['header', 'footer'].forEach(function (surface) {
                    var surfaceData = data[surface];
                    if (!surfaceData) {
                        return;
                    }
                    if (Array.isArray(surfaceData.order)) {
                        var byId = {};
                        state[surface].categories.forEach(function (category) {
                            byId[category.id] = category;
                        });
                        state[surface].categories = surfaceData.order
                            .map(function (id) { return byId[parseInt(id, 10)]; })
                            .filter(Boolean);
                    }
                    if (surfaceData.typography) {
                        if (surfaceData.typography.font_family) {
                            state[surface].fontFamily = surfaceData.typography.font_family;
                        }
                        if (surfaceData.typography.font_size) {
                            state[surface].fontSize = parseInt(surfaceData.typography.font_size, 10) || state[surface].fontSize;
                        }
                    }
                });

                savedState = cloneState(state);
                exitEditMode(false);
                showFeedback('success', (data.message || 'Menu updated successfully.'));
            }).catch(function (error) {
                showFeedback('danger', error && error.message ? error.message : 'Unable to save menu changes. Please try again.');
            }).then(function () {
                isSaving = false;
                resetSaveButton();
            });
        }

        saveButton.addEventListener('click', function () {
            if (isSaving) {
                return;
            }

            if ('function' !== typeof window.cpConfirmAction) {
                // The shared confirm dialog (templates/layout.php,
                // assets/js/app.js) is always present on portal pages;
                // this is only a defensive fallback if it's ever missing.
                if (window.confirm('Save menu changes? Your category order and navigation typography will be updated on the live site.')) { // eslint-disable-line no-alert
                    performSave();
                }
                return;
            }

            window.cpConfirmAction({
                title: 'Save menu changes?',
                message: 'Your category order and navigation typography will be updated on the live site.',
                confirmLabel: 'Save Changes',
                tone: 'default',
                returnFocusTo: saveButton,
                onConfirm: performSave
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            if (isEditing) {
                exitEditMode(true);
            }
            clearFeedback();
        });

        // View mode's initial preview is the server-rendered markup already
        // in the DOM (see templates/categories.php) - only its About Us
        // dropdown needs JS wiring here, not a full rebuild.
        var initialHeaderPreview = modalElement.querySelector('[data-cp-menu-view-preview="header"]');
        if (initialHeaderPreview) {
            wireAboutToggle(initialHeaderPreview);
        }
    });
}());
