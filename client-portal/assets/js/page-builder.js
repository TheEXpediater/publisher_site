(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('cp-page-builder-form');
        if (!form) {
            return;
        }

        var blockList = form.querySelector('[data-cp-block-list]');
        var emptyState = form.querySelector('[data-cp-builder-empty]');
        var blockCount = form.querySelector('[data-cp-block-count]');
        var addToggle = form.querySelector('[data-cp-add-toggle]');
        var addOptions = form.querySelector('[data-cp-add-options]');
        var blocksField = document.getElementById('cp-page-blocks');
        var titleField = document.getElementById('cp-page-title');
        var slugField = document.getElementById('cp-page-slug');
        var permalinkPreview = form.querySelector('[data-cp-permalink-preview]');
        var draggedBlock = null;

        /* Same whitelist already trusted for article headings/paragraphs
           (assets/js/article-builder.js) - reused here rather than a second
           font system, per CLAUDE.md 8.3. */
        var FONT_FORMATS = 'Default=-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;Arial=Arial,Helvetica,sans-serif;Arial Black="Arial Black",Arial,sans-serif;Georgia=Georgia,serif;Times New Roman="Times New Roman",Times,serif;Verdana=Verdana,Geneva,sans-serif;Tahoma=Tahoma,Geneva,sans-serif;Trebuchet MS="Trebuchet MS",Helvetica,sans-serif;Courier New="Courier New",Courier,monospace';

        function headingEditorSettings() {
            return {
                selector: null,
                menubar: false,
                statusbar: false,
                toolbar1: 'fontselect fontsizeselect | bold italic underline | undo redo',
                font_formats: FONT_FORMATS,
                fontsize_formats: '20px 24px 28px 32px 36px 42px 48px',
                valid_elements: 'br,strong[style],b[style],em[style],i[style],u[style],span[class|style]',
                content_style: 'body{font-family:Georgia,serif;font-size:28px;font-weight:700;line-height:1.3;padding:14px 16px;color:#1d2939;background:#fff;}',
                height: 140
            };
        }

        function richtextEditorSettings() {
            return {
                selector: null,
                menubar: false,
                statusbar: false,
                toolbar1: 'fontselect fontsizeselect | bold italic underline | link unlink | bullist numlist | alignleft aligncenter alignright | undo redo',
                font_formats: FONT_FORMATS,
                fontsize_formats: '14px 16px 18px 20px 24px 28px',
                valid_elements: 'p[style],br,strong[style],b[style],em[style],i[style],u[style],a[href|title|target|rel|style],ul[style],ol[style],li[style],span[class|style]',
                content_style: 'body{font-family:Georgia,serif;font-size:17px;line-height:1.7;padding:14px 16px;color:#344054;background:#fff;}p{margin:0 0 .8em}',
                height: 260
            };
        }

        function refreshEmptyState() {
            var count = blockList.querySelectorAll('[data-cp-block]').length;
            if (emptyState) {
                emptyState.hidden = count > 0;
            }
            if (blockCount) {
                blockCount.textContent = String(count);
            }
        }

        function removeEditorIfAny(block) {
            var editorField = block.querySelector('[data-cp-heading-editor], [data-cp-paragraph-editor]');
            if (editorField && editorField.id && window.wp && window.wp.editor) {
                try {
                    window.wp.editor.remove(editorField.id);
                } catch (error) {
                    // Editor was never initialized for this block - nothing to remove.
                }
            }
        }

        function initEditorsIn(root) {
            if (!window.wp || !window.wp.editor) {
                return;
            }
            var fields = root.querySelectorAll('[data-cp-heading-editor], [data-cp-paragraph-editor]');
            fields.forEach(function (field) {
                if (!field.id) {
                    field.id = 'cp-editor-' + Math.random().toString(36).slice(2, 10);
                }
                window.wp.editor.initialize(field.id, field.matches('[data-cp-heading-editor]') ? headingEditorSettings() : richtextEditorSettings());
            });
        }

        function syncEditorsToTextareas(root) {
            if (!window.wp || !window.wp.editor || !window.tinymce) {
                return;
            }
            var fields = root.querySelectorAll('[data-cp-heading-editor], [data-cp-paragraph-editor]');
            fields.forEach(function (field) {
                var editor = window.tinymce.get(field.id);
                if (editor) {
                    field.value = editor.getContent();
                }
            });
        }

        /* ---- Block field <-> data extraction ---- */

        function readBlockData(block) {
            var type = block.getAttribute('data-block-type');
            var data = { type: type };

            block.querySelectorAll('[data-cp-field]').forEach(function (field) {
                data[field.getAttribute('data-cp-field')] = field.value;
            });

            if ('staff_grid' === type) {
                data.people = [];
                block.querySelectorAll('[data-cp-staff-person]').forEach(function (personEl) {
                    var person = {};
                    personEl.querySelectorAll('[data-cp-staff-field]').forEach(function (field) {
                        person[field.getAttribute('data-cp-staff-field')] = field.value;
                    });
                    // Client-only hint so a duplicated block can still preview
                    // its uploaded images; ignored by the server sanitizer.
                    var idField = personEl.querySelector('[data-cp-staff-field="attachment_id"]');
                    if (idField && idField.getAttribute('data-preview-url')) {
                        person._previewUrl = idField.getAttribute('data-preview-url');
                    }
                    data.people.push(person);
                });
            }

            return data;
        }

        function serializeBlocks() {
            syncEditorsToTextareas(blockList);
            var blocks = [];
            blockList.querySelectorAll(':scope > [data-cp-block]').forEach(function (block) {
                blocks.push(readBlockData(block));
            });
            blocksField.value = JSON.stringify(blocks);
        }

        /* ---- Media pickers ---- */

        function openImagePicker(onSelect) {
            if (!window.wp || !window.wp.media) {
                return;
            }
            var frame = window.wp.media({
                title: 'Select Image',
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                onSelect(attachment);
            });
            frame.open();
        }

        function wireImageBlock(block) {
            var selectButton = block.querySelector('[data-cp-select-image]');
            if (!selectButton) {
                return;
            }
            selectButton.addEventListener('click', function () {
                openImagePicker(function (attachment) {
                    var idField = block.querySelector('[data-cp-field="attachment_id"]');
                    var preview = block.querySelector('[data-cp-image-preview]');
                    if (idField) {
                        idField.value = attachment.id;
                    }
                    if (preview) {
                        var url = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
                        preview.innerHTML = '<img src="' + url + '" alt="">';
                        preview.hidden = false;
                    }
                });
            });
        }

        /* Staff card preview mirrors the public image priority
           (includes/page-builder.php cp_render_staff_portrait()): uploaded
           image, then bundled artwork, then placeholder. */
        function wireStaffPersonPortrait(personEl) {
            var selectButton = personEl.querySelector('[data-cp-staff-select-portrait]');
            var removeButton = personEl.querySelector('[data-cp-staff-remove-portrait]');
            var preview = personEl.querySelector('[data-cp-staff-portrait-preview]');
            var sourceLabel = personEl.querySelector('[data-cp-staff-source]');
            var idField = personEl.querySelector('[data-cp-staff-field="attachment_id"]');
            var bundledField = personEl.querySelector('[data-cp-staff-field="bundled"]');
            var attachmentUrl = (idField && parseInt(idField.value, 10) > 0) ? (idField.getAttribute('data-preview-url') || '') : '';

            function refreshPreview() {
                var bundledKey = bundledField ? bundledField.value : '';
                var bundledUrl = bundledKey && Object.prototype.hasOwnProperty.call(STAFF_ARTWORK, bundledKey) ? STAFF_ARTWORK[bundledKey] : '';
                var url = attachmentUrl || bundledUrl;
                if (preview) {
                    if (url) {
                        preview.innerHTML = '<img src="' + escapeHtml(url) + '" alt="" loading="lazy" decoding="async">';
                        preview.removeAttribute('data-empty');
                    } else {
                        preview.innerHTML = PLACEHOLDER_HTML;
                        preview.setAttribute('data-empty', '');
                    }
                }
                if (sourceLabel) {
                    sourceLabel.textContent = attachmentUrl ? 'Uploaded image' : (bundledUrl ? 'Bundled artwork' : 'Placeholder');
                }
                if (removeButton) {
                    removeButton.hidden = !attachmentUrl;
                }
            }

            if (selectButton) {
                selectButton.addEventListener('click', function () {
                    openImagePicker(function (attachment) {
                        attachmentUrl = (attachment.sizes && attachment.sizes.medium) ? attachment.sizes.medium.url : attachment.url;
                        if (idField) {
                            idField.value = attachment.id;
                            idField.setAttribute('data-preview-url', attachmentUrl);
                        }
                        refreshPreview();
                    });
                });
            }

            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    attachmentUrl = '';
                    if (idField) {
                        idField.value = '0';
                        idField.removeAttribute('data-preview-url');
                    }
                    refreshPreview();
                });
            }

            if (bundledField) {
                bundledField.addEventListener('change', refreshPreview);
            }

            refreshPreview();
        }

        var PLACEHOLDER_HTML = '<span class="cp-staff-portrait-placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Zm0 2c-4.42 0-8 2.24-8 5v2h16v-2c0-2.76-3.58-5-8-5Z"/></svg></span>';

        /* Trusted bundled Staff artwork (key => URL), localized by
           includes/helpers.php from assets/images/staff/. */
        var STAFF_ARTWORK = (window.cpPageBuilder && window.cpPageBuilder.staffArtwork && 'object' === typeof window.cpPageBuilder.staffArtwork) ? window.cpPageBuilder.staffArtwork : {};

        function escapeHtml(value) {
            return String(null === value || undefined === value ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function staffArtworkSelectHtml(selected) {
            var options = '<option value="">None</option>';
            Object.keys(STAFF_ARTWORK).forEach(function (key) {
                options += '<option value="' + escapeHtml(key) + '"' + (key === selected ? ' selected' : '') + '>' + escapeHtml(key + '.svg') + '</option>';
            });
            return '<div class="col-md-6"><label class="form-label">Bundled Artwork</label>' +
                '<select class="form-select form-select-sm" data-cp-staff-field="bundled">' + options + '</select>' +
                '<div class="form-text">Used when no uploaded image is selected.</div></div>';
        }

        /* ---- Staff sub-editor (add/remove/reorder person) ---- */

        function wireStaffGridBlock(block) {
            var list = block.querySelector('[data-cp-staff-list]');
            var addButton = block.querySelector('[data-cp-staff-add]');

            if (!list || !addButton) {
                return;
            }

            function wirePerson(personEl) {
                wireStaffPersonPortrait(personEl);

                var upButton = personEl.querySelector('[data-cp-staff-move-up]');
                var downButton = personEl.querySelector('[data-cp-staff-move-down]');
                var removeButton = personEl.querySelector('[data-cp-staff-remove]');

                if (upButton) {
                    upButton.addEventListener('click', function () {
                        var prev = personEl.previousElementSibling;
                        if (prev) {
                            list.insertBefore(personEl, prev);
                        }
                    });
                }
                if (downButton) {
                    downButton.addEventListener('click', function () {
                        var next = personEl.nextElementSibling;
                        if (next) {
                            list.insertBefore(next, personEl);
                        }
                    });
                }
                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        personEl.remove();
                    });
                }
            }

            list.querySelectorAll('[data-cp-staff-person]').forEach(wirePerson);

            addButton.addEventListener('click', function () {
                var personEl = buildStaffPersonElement({});
                list.appendChild(personEl);
                wirePerson(personEl);
            });
        }

        /* ---- Generic block chrome (move/duplicate/remove/drag) ---- */

        function renumberBlocks() {
            blockList.querySelectorAll(':scope > [data-cp-block]').forEach(function (block, index) {
                var numberEl = block.querySelector('.cp-block-number');
                if (numberEl) {
                    numberEl.textContent = String(index + 1);
                }
            });
        }

        function wireBlockChrome(block) {
            var upButton = block.querySelector('[data-cp-move-up]');
            var downButton = block.querySelector('[data-cp-move-down]');
            var duplicateButton = block.querySelector('[data-cp-duplicate]');
            var removeButton = block.querySelector('[data-cp-remove]');

            if (upButton) {
                upButton.addEventListener('click', function () {
                    var prev = block.previousElementSibling;
                    if (prev) {
                        blockList.insertBefore(block, prev);
                        renumberBlocks();
                    }
                });
            }
            if (downButton) {
                downButton.addEventListener('click', function () {
                    var next = block.nextElementSibling;
                    if (next) {
                        blockList.insertBefore(next, block);
                        renumberBlocks();
                    }
                });
            }
            if (duplicateButton) {
                duplicateButton.addEventListener('click', function () {
                    syncEditorsToTextareas(block);
                    var data = readBlockData(block);
                    addBlock(data.type, data, block);
                });
            }
            if (removeButton) {
                removeButton.addEventListener('click', function () {
                    removeEditorIfAny(block);
                    block.remove();
                    refreshEmptyState();
                });
            }

            block.setAttribute('draggable', 'true');
            block.addEventListener('dragstart', function () {
                draggedBlock = block;
                block.classList.add('is-dragging');
            });
            block.addEventListener('dragend', function () {
                draggedBlock = null;
                block.classList.remove('is-dragging');
                renumberBlocks();
            });
            block.addEventListener('dragover', function (event) {
                event.preventDefault();
                if (!draggedBlock || draggedBlock === block) {
                    return;
                }
                var rect = block.getBoundingClientRect();
                var before = (event.clientY - rect.top) < rect.height / 2;
                blockList.insertBefore(draggedBlock, before ? block : block.nextSibling);
            });
        }

        /* ---- Adding new blocks ---- */

        var BLOCK_TEMPLATES = {
            heading: function () {
                return '<div class="cp-heading-field"><div class="cp-heading-level-bar"><label>Heading Level</label><select data-cp-field="level" class="form-select form-select-sm">' +
                    [1, 2, 3, 4, 5, 6].map(function (level) { return '<option value="' + level + '"' + (2 === level ? ' selected' : '') + '>H' + level + '</option>'; }).join('') +
                    '</select></div><textarea class="form-control cp-heading-editor" data-cp-field="content" data-cp-heading-editor rows="3"></textarea></div>';
            },
            richtext: function () {
                return '<div class="cp-paragraph-field"><label class="form-label">Content</label><textarea class="form-control cp-paragraph-editor" data-cp-field="content" data-cp-paragraph-editor rows="6"></textarea></div>';
            },
            image: function () {
                return '<input type="hidden" data-cp-field="attachment_id" value="0">' +
                    '<div class="cp-image-picker"><button type="button" class="btn btn-outline-primary" data-cp-select-image><i class="bi bi-images"></i> Select Image</button>' +
                    '<div class="cp-image-preview" data-cp-image-preview hidden></div></div>' +
                    '<div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label">Alt text</label><input class="form-control" data-cp-field="alt"></div>' +
                    '<div class="col-md-6"><label class="form-label">Caption (optional)</label><input class="form-control" data-cp-field="caption"></div></div>';
            },
            staff_grid: function () {
                return '<div class="cp-staff-editor" data-cp-staff-editor><div class="cp-staff-editor-list" data-cp-staff-list></div>' +
                    '<button type="button" class="btn btn-outline-primary btn-sm" data-cp-staff-add><i class="bi bi-person-plus" aria-hidden="true"></i> Add Person</button></div>';
            },
            button: function () {
                return '<div class="row g-3"><div class="col-md-6"><label class="form-label">Button Label</label><input class="form-control" data-cp-field="label"></div>' +
                    '<div class="col-md-6"><label class="form-label">Link URL</label><input type="url" class="form-control" data-cp-field="url" placeholder="https://"></div>' +
                    '<div class="col-md-6"><label class="form-label">Style</label><select class="form-select" data-cp-field="style"><option value="primary">Primary (filled)</option><option value="outline">Outline</option></select></div></div>';
            },
            divider: function () {
                return '<label class="form-label">Spacing</label><select class="form-select" data-cp-field="size">' +
                    '<option value="small">Small</option><option value="medium" selected>Medium</option><option value="large">Large</option></select>';
            }
        };

        var BLOCK_LABELS = {
            heading: 'Heading',
            richtext: 'Rich Text',
            image: 'Image',
            staff_grid: 'Staff Grid',
            button: 'Button / CTA',
            divider: 'Divider / Spacer'
        };

        function buildBlockElement(type) {
            var wrapper = document.createElement('article');
            wrapper.className = 'cp-builder-block';
            wrapper.setAttribute('data-cp-block', '');
            wrapper.setAttribute('data-block-type', type);
            wrapper.innerHTML =
                '<div class="cp-builder-block-head"><div class="cp-builder-block-title"><span class="cp-block-number">0</span>' +
                '<div><strong>' + BLOCK_LABELS[type] + '</strong><small>Page block</small></div></div>' +
                '<div class="cp-builder-block-actions">' +
                '<button type="button" class="cp-icon-button" data-cp-move-up title="Move Up"><i class="bi bi-arrow-up"></i></button>' +
                '<button type="button" class="cp-icon-button" data-cp-move-down title="Move Down"><i class="bi bi-arrow-down"></i></button>' +
                '<button type="button" class="cp-icon-button" data-cp-duplicate title="Duplicate"><i class="bi bi-copy"></i></button>' +
                '<button type="button" class="cp-icon-button cp-icon-button-danger" data-cp-remove title="Remove"><i class="bi bi-trash"></i></button>' +
                '</div></div>' +
                '<div class="cp-builder-block-body">' + BLOCK_TEMPLATES[type]() + '</div>';
            return wrapper;
        }

        function applyBlockData(block, data) {
            block.querySelectorAll('[data-cp-field]').forEach(function (field) {
                var key = field.getAttribute('data-cp-field');
                if (Object.prototype.hasOwnProperty.call(data, key)) {
                    field.value = data[key];
                }
            });

            if ('staff_grid' === block.getAttribute('data-block-type') && Array.isArray(data.people)) {
                var list = block.querySelector('[data-cp-staff-list]');
                data.people.forEach(function (person) {
                    var temp = document.createElement('div');
                    temp.appendChild(buildStaffPersonElement(person));
                    list.appendChild(temp.firstElementChild);
                });
            }
        }

        function buildStaffPersonElement(person) {
            person = person || {};
            var el = document.createElement('div');
            el.className = 'cp-staff-editor-card';
            el.setAttribute('data-cp-staff-person', '');
            var attachmentId = parseInt(person.attachment_id, 10) || 0;
            el.innerHTML =
                '<div class="cp-staff-editor-portrait">' +
                '<div class="cp-staff-editor-portrait-preview" data-cp-staff-portrait-preview data-empty>' + PLACEHOLDER_HTML + '</div>' +
                '<small class="cp-staff-editor-source" data-cp-staff-source>Placeholder</small>' +
                '<input type="hidden" data-cp-staff-field="attachment_id" value="' + attachmentId + '"' + (attachmentId && person._previewUrl ? ' data-preview-url="' + escapeHtml(person._previewUrl) + '"' : '') + '>' +
                '<div class="cp-staff-editor-portrait-actions">' +
                '<button type="button" class="btn btn-sm btn-outline-secondary" data-cp-staff-select-portrait>Upload / Select</button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" data-cp-staff-remove-portrait hidden>Remove</button>' +
                '</div></div>' +
                '<div class="cp-staff-editor-fields"><div class="row g-2">' +
                '<div class="col-md-6"><label class="form-label">Name</label><input class="form-control form-control-sm" data-cp-staff-field="name" value="' + escapeHtml(person.name) + '"></div>' +
                '<div class="col-md-6"><label class="form-label">Position</label><input class="form-control form-control-sm" data-cp-staff-field="position" value="' + escapeHtml(person.position) + '"></div>' +
                '<div class="col-md-6"><label class="form-label">Section / Group</label><input class="form-control form-control-sm" data-cp-staff-field="group" value="' + escapeHtml(person.group) + '"></div>' +
                '<div class="col-md-6"><label class="form-label">Alt text</label><input class="form-control form-control-sm" data-cp-staff-field="alt" value="' + escapeHtml(person.alt) + '"></div>' +
                staffArtworkSelectHtml(person.bundled || '') +
                '</div></div>' +
                '<div class="cp-staff-editor-actions">' +
                '<button type="button" class="cp-icon-button" data-cp-staff-move-up title="Move Up"><i class="bi bi-arrow-up"></i></button>' +
                '<button type="button" class="cp-icon-button" data-cp-staff-move-down title="Move Down"><i class="bi bi-arrow-down"></i></button>' +
                '<button type="button" class="cp-icon-button cp-icon-button-danger" data-cp-staff-remove title="Remove Person"><i class="bi bi-trash"></i></button>' +
                '</div>';
            return el;
        }

        function initBlockBehavior(block) {
            wireBlockChrome(block);
            var type = block.getAttribute('data-block-type');
            if ('image' === type) {
                wireImageBlock(block);
            }
            if ('staff_grid' === type) {
                wireStaffGridBlock(block);
            }
            initEditorsIn(block);
        }

        function addBlock(type, data, afterBlock) {
            if (!BLOCK_TEMPLATES[type]) {
                return;
            }
            var block = buildBlockElement(type);
            if (afterBlock && afterBlock.parentNode === blockList) {
                blockList.insertBefore(block, afterBlock.nextSibling);
            } else {
                blockList.appendChild(block);
            }
            if (data) {
                applyBlockData(block, data);
            }
            initBlockBehavior(block);
            renumberBlocks();
            refreshEmptyState();
            block.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        if (addToggle && addOptions) {
            addToggle.addEventListener('click', function () {
                addOptions.hidden = !addOptions.hidden;
            });
            addOptions.querySelectorAll('[data-cp-add-type]').forEach(function (button) {
                button.addEventListener('click', function () {
                    addBlock(button.getAttribute('data-cp-add-type'), null, null);
                    addOptions.hidden = true;
                });
            });
        }

        /* ---- Slug preview ---- */

        function slugifyPreview(value) {
            return value
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-');
        }

        function updatePermalinkPreview() {
            if (!permalinkPreview) {
                return;
            }
            var base = permalinkPreview.getAttribute('data-cp-base') || permalinkPreview.textContent.replace(/[a-z0-9-]*\/?$/i, '');
            var slug = slugField && slugField.value ? slugField.value : (titleField ? slugifyPreview(titleField.value) : '');
            permalinkPreview.textContent = base + slug + (slug ? '/' : '');
        }

        if (permalinkPreview && !permalinkPreview.getAttribute('data-cp-base')) {
            var currentText = permalinkPreview.textContent;
            permalinkPreview.setAttribute('data-cp-base', currentText.replace(/[a-z0-9-]*\/?$/i, ''));
        }

        if (titleField) {
            titleField.addEventListener('input', updatePermalinkPreview);
        }
        if (slugField) {
            slugField.addEventListener('input', updatePermalinkPreview);
        }

        /* ---- Init existing blocks + submit ---- */

        blockList.querySelectorAll(':scope > [data-cp-block]').forEach(initBlockBehavior);
        refreshEmptyState();

        form.addEventListener('submit', function () {
            serializeBlocks();
        });
    });
}());
