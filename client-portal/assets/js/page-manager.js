/**
 * Page Manager screen (templates/page-manager.php): the "About Us
 * Navigation" settings modal opened from the gear button next to the
 * Pages heading. Lets an administrator reorder which managed child Pages
 * (Staff, Join the Publication, future ones) appear in the public About Us
 * dropdown, via drag-and-drop or keyboard Move Up/Down - mirroring the
 * Category Manager's own View/Edit Menu drag+move pattern (assets/js/categories.js)
 * so the interaction feels consistent across both admin screens, while
 * saving to its own dedicated AJAX action (cp_save_about_nav_order,
 * includes/pages.php) since it persists a different option
 * (cp_about_pages_order) than the category menu order.
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('cp-about-nav-modal');
        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
            return;
        }

        var config = {};
        try {
            config = JSON.parse(modalElement.getAttribute('data-cp-about-nav-editor') || '{}');
        } catch (error) {
            config = {};
        }

        function normalizePages(raw) {
            return Array.isArray(raw) ? raw.map(function (page) {
                return { id: parseInt(page.id, 10) || 0, title: String(page.title || '') };
            }).filter(function (page) {
                return page.id > 0;
            }) : [];
        }

        var savedActivePages = normalizePages(config.activePages);
        var inactivePages = normalizePages(config.inactivePages);

        var activePages = savedActivePages.map(function (page) {
            return { id: page.id, title: page.title };
        });

        var feedback = modalElement.querySelector('[data-cp-about-nav-feedback]');
        var list = modalElement.querySelector('[data-cp-about-nav-list]');
        var emptyEl = modalElement.querySelector('[data-cp-about-nav-empty]');
        var previewEl = modalElement.querySelector('[data-cp-about-nav-preview]');
        var inactiveSection = modalElement.querySelector('[data-cp-about-nav-inactive-section]');
        var inactiveList = modalElement.querySelector('[data-cp-about-nav-inactive-list]');
        var saveButton = modalElement.querySelector('[data-cp-about-nav-save]');
        var draggedId = null;
        var isSaving = false;

        if (!list || !previewEl || !saveButton) {
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

        /**
         * A small mock of the real About Us dropdown - deliberately never a
         * real <a href> (unlike the Category Manager's View Menu preview,
         * which reuses real navigation markup and then intercepts clicks on
         * it), so there is no navigation to prevent here in the first
         * place: this preview exists purely to show the pending order.
         */
        function renderPreview() {
            previewEl.classList.toggle('is-inactive', !config.aboutUsActive);

            var html = '<div class="cp-about-nav-preview-toggle"><span>' + escapeHtml('ABOUT US') + '</span> <i class="bi bi-chevron-down" aria-hidden="true"></i></div>';
            if (activePages.length) {
                html += '<ul class="cp-about-nav-preview-list">' + activePages.map(function (page) {
                    return '<li>' + escapeHtml(page.title) + '</li>';
                }).join('') + '</ul>';
            } else {
                html += '<p class="cp-about-nav-preview-empty">' + escapeHtml('No pages are currently shown here.') + '</p>';
            }
            previewEl.innerHTML = html;
        }

        function renderInactiveList() {
            if (!inactiveSection || !inactiveList) {
                return;
            }
            if (!inactivePages.length) {
                inactiveSection.hidden = true;
                return;
            }
            inactiveSection.hidden = false;
            inactiveList.innerHTML = '';
            inactivePages.forEach(function (page) {
                var item = document.createElement('li');
                item.className = 'cp-menu-editor-item cp-menu-editor-item-inactive';
                item.innerHTML = '<span class="cp-menu-editor-item-name">' + escapeHtml(page.title) + '</span>'
                    + '<span class="cp-menu-editor-item-badge">' + escapeHtml('Inactive') + '</span>';
                inactiveList.appendChild(item);
            });
        }

        function moveActivePage(fromIndex, toIndex) {
            if (toIndex < 0 || toIndex >= activePages.length || fromIndex === toIndex) {
                return;
            }
            var moved = activePages.splice(fromIndex, 1)[0];
            activePages.splice(toIndex, 0, moved);
            renderList();
            renderPreview();
        }

        function renderList() {
            list.innerHTML = '';

            if (!activePages.length) {
                if (emptyEl) {
                    emptyEl.hidden = false;
                }
                return;
            }
            if (emptyEl) {
                emptyEl.hidden = true;
            }

            activePages.forEach(function (page, index) {
                var item = document.createElement('li');
                item.className = 'cp-menu-editor-item';
                item.draggable = true;
                item.setAttribute('data-page-id', String(page.id));

                var handle = document.createElement('span');
                handle.className = 'cp-menu-editor-drag-handle';
                handle.setAttribute('aria-hidden', 'true');
                handle.innerHTML = '<i class="bi bi-grip-vertical"></i>';

                var label = document.createElement('span');
                label.className = 'cp-menu-editor-item-name';
                label.textContent = page.title;

                var controls = document.createElement('span');
                controls.className = 'cp-menu-editor-item-controls';

                var upButton = document.createElement('button');
                upButton.type = 'button';
                upButton.className = 'cp-menu-editor-move-btn';
                upButton.setAttribute('aria-label', 'Move ' + page.title + ' up');
                upButton.innerHTML = '<i class="bi bi-arrow-up" aria-hidden="true"></i>';
                upButton.disabled = 0 === index;
                upButton.addEventListener('click', function () {
                    moveActivePage(index, index - 1);
                });

                var downButton = document.createElement('button');
                downButton.type = 'button';
                downButton.className = 'cp-menu-editor-move-btn';
                downButton.setAttribute('aria-label', 'Move ' + page.title + ' down');
                downButton.innerHTML = '<i class="bi bi-arrow-down" aria-hidden="true"></i>';
                downButton.disabled = index === activePages.length - 1;
                downButton.addEventListener('click', function () {
                    moveActivePage(index, index + 1);
                });

                controls.appendChild(upButton);
                controls.appendChild(downButton);

                item.appendChild(handle);
                item.appendChild(label);
                item.appendChild(controls);

                item.addEventListener('dragstart', function () {
                    draggedId = page.id;
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
                    if (null === draggedId || draggedId === page.id) {
                        return;
                    }
                    var fromIndex = activePages.findIndex(function (p) { return p.id === draggedId; });
                    var toIndex = activePages.findIndex(function (p) { return p.id === page.id; });
                    if (fromIndex > -1 && toIndex > -1) {
                        moveActivePage(fromIndex, toIndex);
                    }
                });

                list.appendChild(item);
            });
        }

        function resetSaveButton() {
            saveButton.disabled = false;
            saveButton.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i> Save Changes';
        }

        /**
         * The actual AJAX save, only ever invoked from the confirm dialog's
         * onConfirm callback below - never directly from the Save click -
         * so a save can't be triggered without the admin explicitly
         * confirming it first.
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
            form.append('action', 'cp_save_about_nav_order');
            form.append('nonce', config.nonce || '');
            activePages.forEach(function (page) {
                form.append('page_order[]', page.id);
            });

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
                    throw new Error(json && json.data && json.data.message ? json.data.message : 'Unable to update About Us navigation. Please try again.');
                }

                // The saved order comes from what the server actually
                // accepted, not merely assumed from the local pending state
                // that was sent.
                var data = json.data || {};
                if (Array.isArray(data.order)) {
                    var byId = {};
                    activePages.forEach(function (page) {
                        byId[page.id] = page;
                    });
                    activePages = data.order
                        .map(function (id) { return byId[parseInt(id, 10)]; })
                        .filter(Boolean);
                }

                savedActivePages = activePages.map(function (page) {
                    return { id: page.id, title: page.title };
                });
                renderList();
                renderPreview();
                showFeedback('success', data.message || 'About Us navigation updated successfully.');
            }).catch(function (error) {
                showFeedback('danger', error && error.message ? error.message : 'Unable to update About Us navigation. Please try again.');
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
                if (window.confirm('Save this page arrangement?')) { // eslint-disable-line no-alert
                    performSave();
                }
                return;
            }

            window.cpConfirmAction({
                title: 'Update About Us Navigation',
                message: 'Save this page arrangement?',
                confirmLabel: 'Save Changes',
                tone: 'default',
                returnFocusTo: saveButton,
                onConfirm: performSave
            });
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            // Cancel (or any other dismissal) discards unsaved drag/move
            // edits and restores the last-saved order, so reopening the
            // modal never shows a stale pending arrangement.
            activePages = savedActivePages.map(function (page) {
                return { id: page.id, title: page.title };
            });
            renderList();
            renderPreview();
            clearFeedback();
        });

        renderList();
        renderInactiveList();
        renderPreview();
    });
}());
