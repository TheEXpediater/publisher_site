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
        var addBlockArea = form.querySelector('[data-cp-add-block]');
        var addToggle = form.querySelector('[data-cp-add-toggle]');
        var blocksField = document.getElementById('cp-page-blocks');
        var titleField = document.getElementById('cp-page-title');
        var slugField = document.getElementById('cp-page-slug');
        var permalinkPreview = form.querySelector('[data-cp-permalink-preview]');
        var chooser = document.querySelector('[data-cp-block-chooser]');
        var chooserTitle = chooser ? chooser.querySelector('[data-cp-chooser-title]') : null;
        var blockMenu = document.querySelector('[data-cp-block-menu]');
        var config = window.cpPageBuilder && 'object' === typeof window.cpPageBuilder ? window.cpPageBuilder : {};
        var strings = config.strings || {};
        var EDITOR_SELECTOR = '[data-cp-heading-editor], [data-cp-paragraph-editor]';
        var TEXT_TYPES = ['heading', 'richtext'];
        var nextId = 0;
        var draggedBlock = null;
        var failureTimers = {};
        var editorFailureLogged = false;
        var chooserState = null;
        var menuState = null;

        function text(key, fallback) {
            return strings[key] || fallback;
        }

        /* ------------------------------------------------------------------
         * Visual editor (WordPress TinyMCE via wp.editor)
         *
         * wp.editor.initialize() only starts TinyMCE when the settings carry
         * a `tinymce` key (wp-admin/js/editor.js: `if ( window.tinymce &&
         * settings.tinymce )`) - the same {mediaButtons, quicktags, tinymce}
         * shape the Article Builder uses. Font families are the Article
         * Builder's existing whitelist; valid_elements mirror the server
         * sanitizers (includes/article-builder.php) so what the editor shows
         * is what a save keeps.
         * ---------------------------------------------------------------- */

        var FONT_FORMATS = 'Default=-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;Arial=Arial,Helvetica,sans-serif;Arial Black="Arial Black",Arial,sans-serif;Georgia=Georgia,serif;Times New Roman="Times New Roman",Times,serif;Verdana=Verdana,Geneva,sans-serif;Tahoma=Tahoma,Geneva,sans-serif;Trebuchet MS="Trebuchet MS",Helvetica,sans-serif;Courier New="Courier New",Courier,monospace';
        var HEADING_ELEMENTS = 'br,strong[style],b[style],em[style],i[style],u[style],a[href|title|target|rel|style],span[class|style]';
        var RICH_TEXT_ELEMENTS = 'p[class|style],br,strong[style],b[style],em[style],i[style],u[style],a[href|title|target|rel|style],ul[class|style],ol[class|style],li[style],blockquote[class|style],span[class|style],table[class|style],caption[style],thead,tbody,tfoot,tr[style],th[colspan|rowspan|scope|style],td[colspan|rowspan|style]';
        // A fixed publication palette (color only - no arbitrary CSS).
        var TEXT_COLORS = ['000029', 'Enterprise Navy', '640D00', 'Enterprise Maroon', '11111C', 'Ink', '475467', 'Slate', '686872', 'Gray', 'B42318', 'Red', 'B54708', 'Amber', '067647', 'Green', '175CD3', 'Blue', 'FFFFFF', 'White'];
        var ALIGN_CSS = '.cp-align-left{text-align:left}.cp-align-center{text-align:center}.cp-align-right{text-align:right}';

        function richTextSettings() {
            return {
                mediaButtons: false,
                quicktags: false,
                tinymce: {
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    elementpath: false,
                    wpautop: false,
                    // No newlines added between blocks, so saving unchanged
                    // content stores exactly what was loaded.
                    indent: false,
                    height: 220,
                    toolbar1: 'fontselect fontsizeselect | bold italic underline strikethrough forecolor removeformat | alignleft aligncenter alignright | bullist numlist | link unlink | undo redo',
                    toolbar2: '',
                    font_formats: FONT_FORMATS,
                    fontsize_formats: '12px 14px 16px 17px 18px 20px 24px 28px 32px',
                    textcolor_map: TEXT_COLORS,
                    textcolor_cols: 5,
                    custom_colors: false,
                    valid_elements: RICH_TEXT_ELEMENTS,
                    formats: {
                        alignleft: { selector: 'p,ul,ol,blockquote', classes: 'cp-align-left' },
                        aligncenter: { selector: 'p,ul,ol,blockquote', classes: 'cp-align-center' },
                        alignright: { selector: 'p,ul,ol,blockquote', classes: 'cp-align-right' },
                        // WordPress's default is <del>, which the sanitizer strips.
                        strikethrough: { inline: 'span', styles: { textDecoration: 'line-through' }, exact: true }
                    },
                    content_style: 'body{font-family:Georgia,"Times New Roman",serif;font-size:17px;line-height:1.75;padding:14px 18px;margin:0;color:#11111c;background:#fff}p{margin:0 0 1em}ul,ol{margin:0 0 1em;padding-left:1.5em}a{color:#000029}blockquote{border-left:4px solid #d0d5dd;margin:1em 0;padding:.25em 1em}' + ALIGN_CSS,
                    setup: function (editor) {
                        watchEditor(editor);
                    }
                }
            };
        }

        function headingSettings() {
            return {
                mediaButtons: false,
                quicktags: false,
                tinymce: {
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    elementpath: false,
                    wpautop: false,
                    forced_root_block: false,
                    height: 76,
                    toolbar1: 'fontselect fontsizeselect | bold italic underline | cp_align_left cp_align_center cp_align_right | undo redo',
                    toolbar2: '',
                    font_formats: FONT_FORMATS,
                    fontsize_formats: '16px 18px 20px 24px 26px 28px 32px 36px 40px 48px 56px',
                    valid_elements: HEADING_ELEMENTS,
                    // Mirrors the public heading sizes (assets/css/frontend-pages.css).
                    content_style: 'body{font-family:Georgia,"Times New Roman",serif;font-weight:700;line-height:1.25;padding:12px 18px;margin:0;color:#000029;background:#fff;font-size:20px}body.cp-level-1{font-size:40px}body.cp-level-2{font-size:26px}a{color:#000029}',
                    setup: function (editor) {
                        addHeadingAlignButtons(editor);
                        watchEditor(editor);
                    }
                }
            };
        }

        function editorApiAvailable() {
            return !!(window.wp && window.wp.editor && 'function' === typeof window.wp.editor.initialize && window.tinymce);
        }

        function getEditor(textarea) {
            return textarea && textarea.id && window.tinymce ? window.tinymce.get(textarea.id) : null;
        }

        function editorsIn(root) {
            if (!root) {
                return [];
            }
            if (root.matches && root.matches(EDITOR_SELECTOR)) {
                return [root];
            }
            return Array.prototype.slice.call(root.querySelectorAll(EDITOR_SELECTOR));
        }

        function blockForEditor(editor) {
            var textarea = editor && editor.id ? document.getElementById(editor.id) : null;
            return textarea ? textarea.closest('[data-cp-block]') : null;
        }

        function watchEditor(editor) {
            editor.on('init', function () {
                var textarea = document.getElementById(editor.id);
                if (!textarea) {
                    return;
                }
                textarea.setAttribute('data-cp-editor-state', 'ready');
                window.clearTimeout(failureTimers[editor.id]);
                hideEditorNotice(textarea);
                var block = blockForEditor(editor);
                if (block && 'heading' === block.getAttribute('data-block-type')) {
                    applyHeadingPresentation(block);
                }
                if (textarea.hasAttribute('data-cp-focus-on-init')) {
                    textarea.removeAttribute('data-cp-focus-on-init');
                    editor.focus();
                }
            });
            editor.on('focus', function () {
                setActiveBlock(blockForEditor(editor));
            });
            // Key presses inside the editor iframe never reach this document.
            editor.on('keydown', function (event) {
                var block = blockForEditor(editor);
                if ('Escape' === event.key && block && block.classList.contains('cp-block-fullscreen')) {
                    event.preventDefault();
                    exitFullscreen(block);
                }
            });
        }

        function showEditorNotice(textarea) {
            var field = textarea.parentNode;
            if (!field || field.querySelector('[data-cp-editor-notice]')) {
                return;
            }
            var notice = document.createElement('div');
            notice.className = 'cp-editor-fallback-notice';
            notice.setAttribute('role', 'alert');
            notice.setAttribute('data-cp-editor-notice', '');
            notice.innerHTML = '<i class="bi bi-exclamation-triangle" aria-hidden="true"></i>';
            var message = document.createElement('span');
            message.textContent = text('editorFailed', 'The visual editor could not load, so this block is showing its underlying HTML. Reload the page to try again.');
            notice.appendChild(message);
            field.insertBefore(notice, textarea);
        }

        function hideEditorNotice(textarea) {
            var notice = textarea.parentNode ? textarea.parentNode.querySelector('[data-cp-editor-notice]') : null;
            if (notice) {
                notice.remove();
            }
            textarea.classList.remove('cp-paragraph-editor-fallback');
        }

        function markEditorFailed(textarea, reason) {
            textarea.setAttribute('data-cp-editor-state', 'failed');
            textarea.classList.add('cp-paragraph-editor-fallback');
            showEditorNotice(textarea);
            if (!editorFailureLogged && window.console) {
                editorFailureLogged = true;
                window.console.warn('Page Builder: visual editor unavailable (' + reason + ').');
            }
        }

        function initEditor(textarea) {
            if (!textarea || !textarea.id) {
                return;
            }
            var state = textarea.getAttribute('data-cp-editor-state');
            if ('ready' === state || 'pending' === state || getEditor(textarea)) {
                return;
            }
            if (!editorApiAvailable()) {
                markEditorFailed(textarea, 'wp.editor/TinyMCE not loaded');
                return;
            }
            textarea.setAttribute('data-cp-editor-state', 'pending');
            try {
                window.wp.editor.initialize(textarea.id, textarea.matches('[data-cp-heading-editor]') ? headingSettings() : richTextSettings());
            } catch (error) {
                markEditorFailed(textarea, error && error.message ? error.message : 'initialize threw');
                return;
            }
            failureTimers[textarea.id] = window.setTimeout(function () {
                if ('pending' === textarea.getAttribute('data-cp-editor-state')) {
                    markEditorFailed(textarea, 'initialization timed out');
                }
            }, 12000);
        }

        /* Copies the live editor HTML back into its textarea - the textarea
           is what serialization reads. Never reads an editor that hasn't
           finished loading (that would return empty content). */
        function syncEditor(textarea) {
            var editor = getEditor(textarea);
            if (editor && editor.initialized) {
                textarea.value = editor.getContent();
            }
        }

        function removeEditor(textarea) {
            syncEditor(textarea);
            window.clearTimeout(failureTimers[textarea.id]);
            var editor = getEditor(textarea);
            if (editor) {
                var value = textarea.value;
                try {
                    window.wp.editor.remove(textarea.id);
                } catch (error) {
                    try {
                        editor.remove();
                    } catch (innerError) {
                        // Already gone.
                    }
                }
                textarea.value = value;
            }
            textarea.removeAttribute('data-cp-editor-state');
        }

        /* ---- Heading: level presentation + alignment ---- */

        function headingAlignField(block) {
            return block ? block.querySelector('[data-cp-field="align"]') : null;
        }

        function effectiveHeadingAlign(block) {
            var field = headingAlignField(block);
            var level = block.querySelector('[data-cp-field="level"]');
            if (field && field.value) {
                return field.value;
            }
            // Matches the public default: H1 centered, others left.
            return level && '1' === level.value ? 'center' : 'left';
        }

        function applyHeadingPresentation(block) {
            var textarea = block.querySelector('[data-cp-heading-editor]');
            var editor = getEditor(textarea);
            if (!editor || !editor.initialized) {
                return;
            }
            var body = editor.getBody();
            var level = block.querySelector('[data-cp-field="level"]');
            [1, 2, 3, 4, 5, 6].forEach(function (value) {
                body.classList.remove('cp-level-' + value);
            });
            body.classList.add('cp-level-' + (level ? level.value : '2'));
            body.style.textAlign = effectiveHeadingAlign(block);
            editor.fire('cp-align-change');
        }

        function setHeadingAlign(editor, align) {
            var block = blockForEditor(editor);
            var field = headingAlignField(block);
            if (field) {
                field.value = align;
                applyHeadingPresentation(block);
            }
        }

        function addHeadingAlignButtons(editor) {
            // The heading allows inline markup only, so the core Justify
            // commands (also bound to Shift+Alt+L/C/R by WordPress) set the
            // block's align field instead of wrapping text in a block.
            editor.on('init', function () {
                ['Left', 'Center', 'Right'].forEach(function (name) {
                    editor.addCommand('Justify' + name, function () {
                        setHeadingAlign(editor, name.toLowerCase());
                    });
                });
            });
            [
                ['left', 'alignleft', text('alignLeft', 'Align left')],
                ['center', 'aligncenter', text('alignCenter', 'Align center')],
                ['right', 'alignright', text('alignRight', 'Align right')]
            ].forEach(function (entry) {
                editor.addButton('cp_align_' + entry[0], {
                    icon: entry[1],
                    tooltip: entry[2],
                    onclick: function () {
                        setHeadingAlign(editor, entry[0]);
                    },
                    onPostRender: function () {
                        var button = this;
                        editor.on('cp-align-change', function () {
                            var block = blockForEditor(editor);
                            button.active(!!block && effectiveHeadingAlign(block) === entry[0]);
                        });
                    }
                });
            });
        }

        /* ------------------------------------------------------------------
         * Block data <-> DOM
         * ---------------------------------------------------------------- */

        function allBlocks() {
            return Array.prototype.slice.call(blockList.querySelectorAll(':scope > [data-cp-block]'));
        }

        function previousBlock(block) {
            var node = block.previousElementSibling;
            return node && node.matches('[data-cp-block]') ? node : null;
        }

        function nextBlock(block) {
            var node = block.nextElementSibling;
            return node && node.matches('[data-cp-block]') ? node : null;
        }

        function isTextBlock(block) {
            return TEXT_TYPES.indexOf(block.getAttribute('data-block-type')) !== -1;
        }

        function readBlockData(block) {
            var type = block.getAttribute('data-block-type');
            var data = { type: type };

            block.querySelectorAll('[data-cp-field]').forEach(function (field) {
                data[field.getAttribute('data-cp-field')] = field.value;
            });

            if ('image' === type) {
                // Client-only hint so a duplicated block can preview its image;
                // ignored by the server sanitizer.
                var image = block.querySelector('[data-cp-image-preview] img');
                if (image) {
                    data._previewUrl = image.getAttribute('src');
                }
            }

            if ('staff_grid' === type) {
                data.people = [];
                block.querySelectorAll('[data-cp-staff-person]').forEach(function (personEl) {
                    var person = {};
                    personEl.querySelectorAll('[data-cp-staff-field]').forEach(function (field) {
                        person[field.getAttribute('data-cp-staff-field')] = field.value;
                    });
                    var idField = personEl.querySelector('[data-cp-staff-field="attachment_id"]');
                    if (idField && idField.getAttribute('data-preview-url')) {
                        person._previewUrl = idField.getAttribute('data-preview-url');
                    }
                    data.people.push(person);
                });
            }

            return data;
        }

        function applyBlockData(block, data) {
            block.querySelectorAll('[data-cp-field]').forEach(function (field) {
                var key = field.getAttribute('data-cp-field');
                if (Object.prototype.hasOwnProperty.call(data, key) && null !== data[key] && undefined !== data[key]) {
                    field.value = String(data[key]);
                }
            });

            var type = block.getAttribute('data-block-type');
            if ('image' === type) {
                setImagePreview(block, parseInt(data.attachment_id, 10) > 0 ? (data._previewUrl || '') : '');
            }
            if ('staff_grid' === type && Array.isArray(data.people)) {
                var list = block.querySelector('[data-cp-staff-list]');
                data.people.forEach(function (person) {
                    var personEl = createStaffPerson(person);
                    if (personEl) {
                        list.appendChild(personEl);
                    }
                });
            }
            refreshDivider(block);
            refreshButtonPreview(block);
        }

        function serializeBlocks() {
            editorsIn(blockList).forEach(syncEditor);
            blocksField.value = JSON.stringify(allBlocks().map(readBlockData));
        }

        /* New blocks are copies of the server-rendered <template> for their
           type (templates/page-builder.php), with a unique ID swapped in. */
        function createBlock(type, data) {
            var template = document.querySelector('template[data-cp-block-template="' + type + '"]');
            if (!template) {
                return null;
            }
            nextId += 1;
            var uid = 'cp-page-block-new-' + Date.now().toString(36) + '-' + nextId;
            var holder = document.createElement('div');
            holder.innerHTML = template.innerHTML.split('__CPID__').join(uid);
            var block = holder.querySelector('[data-cp-block]');
            if (block && data) {
                applyBlockData(block, data);
            }
            return block;
        }

        function createStaffPerson(person) {
            var template = document.querySelector('template[data-cp-staff-person-template]');
            if (!template) {
                return null;
            }
            var holder = document.createElement('div');
            holder.innerHTML = template.innerHTML;
            var personEl = holder.querySelector('[data-cp-staff-person]');
            person = person || {};
            personEl.querySelectorAll('[data-cp-staff-field]').forEach(function (field) {
                var key = field.getAttribute('data-cp-staff-field');
                if (Object.prototype.hasOwnProperty.call(person, key) && null !== person[key] && undefined !== person[key]) {
                    field.value = String(person[key]);
                }
            });
            var idField = personEl.querySelector('[data-cp-staff-field="attachment_id"]');
            if (idField && parseInt(idField.value, 10) > 0 && person._previewUrl) {
                idField.setAttribute('data-preview-url', person._previewUrl);
            }
            refreshStaffPreview(personEl);
            return personEl;
        }

        /* ------------------------------------------------------------------
         * Block list state
         * ---------------------------------------------------------------- */

        function refreshBlocks() {
            var blocks = allBlocks();
            blocks.forEach(function (block, index) {
                var numberEl = block.querySelector('.cp-block-number');
                if (numberEl) {
                    numberEl.textContent = String(index + 1);
                }
                var up = block.querySelector('[data-cp-move-up]');
                var down = block.querySelector('[data-cp-move-down]');
                if (up) {
                    up.disabled = 0 === index;
                }
                if (down) {
                    down.disabled = index === blocks.length - 1;
                }
            });
            if (emptyState) {
                emptyState.hidden = blocks.length > 0;
            }
            if (blockCount) {
                blockCount.textContent = String(blocks.length);
            }
        }

        function setActiveBlock(block) {
            allBlocks().forEach(function (other) {
                other.classList.toggle('cp-block-active', other === block);
            });
        }

        function flash(block, className, duration) {
            block.classList.add(className);
            window.setTimeout(function () {
                block.classList.remove(className);
            }, duration);
        }

        function focusBlock(block) {
            var textarea = block.querySelector(EDITOR_SELECTOR);
            var editor = getEditor(textarea);
            block.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setActiveBlock(block);
            if (editor && editor.initialized) {
                editor.focus();
                return;
            }
            if (textarea && 'pending' === textarea.getAttribute('data-cp-editor-state')) {
                textarea.setAttribute('data-cp-focus-on-init', '');
                return;
            }
            var field = block.querySelector('.cp-builder-block-body input:not([type="hidden"]), .cp-builder-block-body textarea, .cp-builder-block-body select, .cp-builder-block-body button');
            if (field) {
                field.focus({ preventScroll: true });
            }
        }

        function initBlock(block) {
            editorsIn(block).forEach(initEditor);
        }

        function insertBlock(type, reference, position, data) {
            var block = createBlock(type, data || null);
            if (!block) {
                return null;
            }
            if (reference && reference.parentNode === blockList) {
                blockList.insertBefore(block, 'before' === position ? reference : reference.nextElementSibling);
            } else {
                blockList.appendChild(block);
            }
            initBlock(block);
            refreshBlocks();
            flash(block, 'cp-block-new', 1400);
            focusBlock(block);
            return block;
        }

        function duplicateBlock(block) {
            exitFullscreen(block);
            editorsIn(block).forEach(syncEditor);
            var data = readBlockData(block);
            return insertBlock(data.type, block, 'after', data);
        }

        /* Moving a TinyMCE iframe in the DOM reloads it blank, so the moved
           block's editors are synced, removed, and re-created around the
           move. Only `block` itself is ever re-parented - never a sibling -
           so other blocks' editors are untouched. */
        function moveBlock(block, direction, focusTarget) {
            var sibling = 'up' === direction ? previousBlock(block) : nextBlock(block);
            if (!sibling) {
                return;
            }
            exitFullscreen(block);
            var editors = editorsIn(block);
            editors.forEach(removeEditor);
            blockList.insertBefore(block, 'up' === direction ? sibling : sibling.nextElementSibling);
            editors.forEach(initEditor);
            refreshBlocks();
            flash(block, 'cp-block-just-moved', 600);
            if (focusTarget && block.contains(focusTarget)) {
                if (focusTarget.disabled) {
                    focusTarget = block.querySelector('up' === direction ? '[data-cp-move-down]' : '[data-cp-move-up]') || focusTarget;
                }
                focusTarget.focus({ preventScroll: true });
            }
            block.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function deleteBlock(block) {
            // The confirm dialog would otherwise open underneath the full-screen block.
            exitFullscreen(block);
            var remove = function () {
                editorsIn(block).forEach(removeEditor);
                var neighbor = nextBlock(block) || previousBlock(block);
                block.remove();
                refreshBlocks();
                var focusTarget = neighbor ? neighbor.querySelector('[data-cp-block-menu-toggle]') : addToggle;
                if (focusTarget) {
                    focusTarget.focus({ preventScroll: true });
                }
            };
            if ('function' === typeof window.cpConfirmAction) {
                window.cpConfirmAction({
                    title: text('deleteTitle', 'Delete Block'),
                    message: text('deleteMessage', 'Remove this block from the page?'),
                    confirmLabel: text('deleteLabel', 'Delete Block'),
                    tone: 'danger',
                    onConfirm: remove
                });
            } else if (window.confirm(text('deleteMessage', 'Remove this block from the page?'))) {
                remove();
            }
        }

        /* ---- Full Screen / Focus (reuses the Article Builder's styles) ---- */

        function setFullscreenButton(block, expanded) {
            var button = block.querySelector('[data-cp-fullscreen]');
            if (!button) {
                return;
            }
            var label = expanded ? text('exitFullScreen', 'Exit Full Screen') : text('fullScreen', 'Full Screen');
            button.title = label;
            button.setAttribute('aria-label', label);
            button.setAttribute('aria-pressed', expanded ? 'true' : 'false');
            var icon = button.querySelector('i');
            if (icon) {
                icon.className = 'bi ' + (expanded ? 'bi-fullscreen-exit' : 'bi-arrows-fullscreen');
            }
        }

        function exitFullscreen(block) {
            if (!block || !block.classList.contains('cp-block-fullscreen')) {
                return;
            }
            block.classList.remove('cp-block-fullscreen');
            document.body.classList.remove('cp-builder-fullscreen-open');
            setFullscreenButton(block, false);
        }

        function toggleFullscreen(block) {
            if (!block || !isTextBlock(block)) {
                return;
            }
            if (block.classList.contains('cp-block-fullscreen')) {
                exitFullscreen(block);
                return;
            }
            allBlocks().forEach(exitFullscreen);
            block.classList.add('cp-block-fullscreen');
            document.body.classList.add('cp-builder-fullscreen-open');
            setFullscreenButton(block, true);
            setActiveBlock(block);
            var editor = getEditor(block.querySelector(EDITOR_SELECTOR));
            if (editor && editor.initialized) {
                editor.focus();
            }
        }

        /* ------------------------------------------------------------------
         * Block-specific controls
         * ---------------------------------------------------------------- */

        function openImagePicker(onSelect) {
            if (!window.wp || !window.wp.media) {
                window.alert(text('mediaUnavailable', 'The Media Library is unavailable on this page.'));
                return;
            }
            var frame = window.wp.media({
                title: text('selectImage', 'Select Image'),
                multiple: false,
                library: { type: 'image' }
            });
            frame.on('select', function () {
                onSelect(frame.state().get('selection').first().toJSON());
            });
            frame.open();
        }

        function attachmentPreviewUrl(attachment, size) {
            return attachment.sizes && attachment.sizes[size] ? attachment.sizes[size].url : attachment.url;
        }

        function setImagePreview(block, url) {
            var preview = block.querySelector('[data-cp-image-preview]');
            var empty = block.querySelector('[data-cp-image-empty]');
            var removeButton = block.querySelector('[data-cp-remove-image]');
            var label = block.querySelector('[data-cp-select-image-label]');
            var idField = block.querySelector('[data-cp-field="attachment_id"]');
            var hasImage = idField && parseInt(idField.value, 10) > 0;
            if (preview) {
                preview.textContent = '';
                if (url) {
                    var image = document.createElement('img');
                    image.src = url;
                    image.alt = '';
                    preview.appendChild(image);
                }
                preview.hidden = !url;
            }
            if (empty) {
                empty.hidden = !!url;
            }
            if (removeButton) {
                removeButton.hidden = !hasImage;
            }
            if (label) {
                label.textContent = hasImage ? text('replaceImage', 'Replace Image') : text('selectImage', 'Select Image');
            }
        }

        function selectBlockImage(block) {
            openImagePicker(function (attachment) {
                var idField = block.querySelector('[data-cp-field="attachment_id"]');
                var altField = block.querySelector('[data-cp-field="alt"]');
                if (idField) {
                    idField.value = String(attachment.id);
                }
                if (altField && !altField.value && attachment.alt) {
                    altField.value = attachment.alt;
                }
                setImagePreview(block, attachmentPreviewUrl(attachment, 'medium_large'));
            });
        }

        function removeBlockImage(block) {
            var idField = block.querySelector('[data-cp-field="attachment_id"]');
            if (idField) {
                idField.value = '0';
            }
            setImagePreview(block, '');
        }

        function refreshButtonPreview(block) {
            var preview = block.querySelector('[data-cp-button-preview]');
            if (!preview) {
                return;
            }
            var label = block.querySelector('[data-cp-field="label"]');
            var style = block.querySelector('[data-cp-field="style"]');
            preview.textContent = label && label.value.trim() ? label.value : (preview.getAttribute('data-placeholder') || '');
            preview.classList.toggle('is-placeholder', !(label && label.value.trim()));
            preview.classList.toggle('is-outline', !!style && 'outline' === style.value);
            preview.classList.toggle('is-primary', !style || 'outline' !== style.value);
        }

        function refreshDivider(block) {
            var field = block.querySelector('[data-cp-field="size"]');
            if (!field) {
                return;
            }
            block.querySelectorAll('[data-cp-divider-size]').forEach(function (choice) {
                choice.setAttribute('aria-pressed', choice.getAttribute('data-cp-divider-size') === field.value ? 'true' : 'false');
            });
        }

        /* Staff card preview mirrors the public image priority
           (includes/page-builder.php cp_render_staff_portrait()): uploaded
           image, then bundled artwork, then placeholder. State is read from
           the card's own fields, so it survives duplication and reordering. */

        var PLACEHOLDER_HTML = '<span class="cp-staff-portrait-placeholder" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false"><path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5Zm0 2c-4.42 0-8 2.24-8 5v2h16v-2c0-2.76-3.58-5-8-5Z"/></svg></span>';

        /* Trusted bundled Staff artwork (key => URL), localized by
           includes/helpers.php from assets/images/staff/. */
        var STAFF_ARTWORK = config.staffArtwork && 'object' === typeof config.staffArtwork ? config.staffArtwork : {};

        function refreshStaffPreview(personEl) {
            var preview = personEl.querySelector('[data-cp-staff-portrait-preview]');
            var sourceLabel = personEl.querySelector('[data-cp-staff-source]');
            var removeButton = personEl.querySelector('[data-cp-staff-remove-portrait]');
            var idField = personEl.querySelector('[data-cp-staff-field="attachment_id"]');
            var bundledField = personEl.querySelector('[data-cp-staff-field="bundled"]');
            var attachmentUrl = idField && parseInt(idField.value, 10) > 0 ? (idField.getAttribute('data-preview-url') || '') : '';
            var bundledKey = bundledField ? bundledField.value : '';
            var bundledUrl = bundledKey && Object.prototype.hasOwnProperty.call(STAFF_ARTWORK, bundledKey) ? STAFF_ARTWORK[bundledKey] : '';
            var url = attachmentUrl || bundledUrl;

            if (preview) {
                if (url) {
                    preview.textContent = '';
                    var image = document.createElement('img');
                    image.src = url;
                    image.alt = '';
                    image.loading = 'lazy';
                    image.decoding = 'async';
                    preview.appendChild(image);
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

        function handleStaffClick(event, block) {
            var personEl = event.target.closest('[data-cp-staff-person]');
            var list = block.querySelector('[data-cp-staff-list]');

            if (event.target.closest('[data-cp-staff-add]')) {
                var added = createStaffPerson({});
                if (added && list) {
                    list.appendChild(added);
                    var name = added.querySelector('[data-cp-staff-field="name"]');
                    if (name) {
                        name.focus();
                    }
                }
                return true;
            }
            if (!personEl) {
                return false;
            }
            if (event.target.closest('[data-cp-staff-select-portrait]')) {
                openImagePicker(function (attachment) {
                    var idField = personEl.querySelector('[data-cp-staff-field="attachment_id"]');
                    if (idField) {
                        idField.value = String(attachment.id);
                        idField.setAttribute('data-preview-url', attachmentPreviewUrl(attachment, 'medium'));
                    }
                    refreshStaffPreview(personEl);
                });
                return true;
            }
            if (event.target.closest('[data-cp-staff-remove-portrait]')) {
                var idField = personEl.querySelector('[data-cp-staff-field="attachment_id"]');
                if (idField) {
                    idField.value = '0';
                    idField.removeAttribute('data-preview-url');
                }
                refreshStaffPreview(personEl);
                return true;
            }
            if (event.target.closest('[data-cp-staff-move-up]')) {
                if (personEl.previousElementSibling) {
                    list.insertBefore(personEl, personEl.previousElementSibling);
                    event.target.closest('button').focus();
                }
                return true;
            }
            if (event.target.closest('[data-cp-staff-move-down]')) {
                if (personEl.nextElementSibling) {
                    list.insertBefore(personEl, personEl.nextElementSibling.nextElementSibling);
                    event.target.closest('button').focus();
                }
                return true;
            }
            if (event.target.closest('[data-cp-staff-remove]')) {
                personEl.remove();
                return true;
            }
            return false;
        }

        /* ------------------------------------------------------------------
         * Floating panels: Insert Block chooser + block context menu
         * ---------------------------------------------------------------- */

        function placePanel(panel, x, y) {
            panel.style.left = '0px';
            panel.style.top = '0px';
            var width = panel.offsetWidth;
            var height = panel.offsetHeight;
            var maxX = window.innerWidth - width - 8;
            var maxY = window.innerHeight - height - 8;
            panel.style.left = Math.max(8, Math.min(x, maxX)) + 'px';
            panel.style.top = Math.max(8, Math.min(y, maxY)) + 'px';
        }

        function placeBelow(panel, anchor, centered) {
            var rect = anchor.getBoundingClientRect();
            panel.style.left = '0px';
            panel.style.top = '0px';
            var below = rect.bottom + 6;
            var y = below + panel.offsetHeight > window.innerHeight - 8 ? rect.top - panel.offsetHeight - 6 : below;
            var x = centered ? rect.left + (rect.width - panel.offsetWidth) / 2 : rect.right - panel.offsetWidth;
            placePanel(panel, x, y);
        }

        function setAddPanel(open) {
            if (!addBlockArea || !addToggle) {
                return;
            }
            addBlockArea.classList.toggle('cp-add-block-open', open);
            addToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        }

        function openChooser(block, position, anchor) {
            if (!chooser) {
                return;
            }
            closeMenu(false);
            setAddPanel(false);
            chooserState = { block: block, position: position, returnFocus: anchor };
            if (chooserTitle) {
                chooserTitle.textContent = 'before' === position ? text('insertAbove', 'Insert block above') : text('insertBelow', 'Insert block below');
            }
            chooser.hidden = false;
            var fromInsertButton = !!(anchor && anchor.hasAttribute('data-cp-insert-below'));
            placeBelow(chooser, anchor && document.body.contains(anchor) ? anchor : block, fromInsertButton);
            setActiveBlock(block);
            var first = chooser.querySelector('[data-cp-add-type]');
            if (first) {
                first.focus({ preventScroll: true });
            }
        }

        function closeChooser(restoreFocus) {
            if (!chooser || chooser.hidden) {
                return;
            }
            chooser.hidden = true;
            var target = chooserState ? chooserState.returnFocus : null;
            chooserState = null;
            if (restoreFocus && target && document.body.contains(target)) {
                target.focus({ preventScroll: true });
            }
        }

        function menuItems() {
            return Array.prototype.slice.call(blockMenu.querySelectorAll('[role="menuitem"]')).filter(function (item) {
                return !item.hidden && !item.disabled;
            });
        }

        function openMenu(block, options) {
            if (!blockMenu) {
                return;
            }
            closeChooser(false);
            closeMenu(false);
            var toggle = block.querySelector('[data-cp-block-menu-toggle]');
            menuState = { block: block, returnFocus: options.returnFocus || toggle, toggle: options.fromToggle ? toggle : null };

            var textBlock = isTextBlock(block);
            var fullscreenItem = blockMenu.querySelector('[data-cp-menu-action="fullscreen"]');
            var fullscreenLabel = blockMenu.querySelector('[data-cp-menu-fullscreen-label]');
            fullscreenItem.hidden = !textBlock;
            if (fullscreenLabel) {
                fullscreenLabel.textContent = block.classList.contains('cp-block-fullscreen') ? text('exitFullScreen', 'Exit Full Screen') : text('fullScreen', 'Full Screen');
            }
            blockMenu.querySelector('[data-cp-menu-action="move-up"]').disabled = !previousBlock(block);
            blockMenu.querySelector('[data-cp-menu-action="move-down"]').disabled = !nextBlock(block);

            blockMenu.hidden = false;
            if (menuState.toggle) {
                menuState.toggle.setAttribute('aria-expanded', 'true');
            }
            if ('number' === typeof options.x) {
                placePanel(blockMenu, options.x, options.y);
            } else {
                placeBelow(blockMenu, toggle || block);
            }
            setActiveBlock(block);
            var items = menuItems();
            if (items.length) {
                items[0].focus({ preventScroll: true });
            }
        }

        function closeMenu(restoreFocus) {
            if (!blockMenu || blockMenu.hidden) {
                return;
            }
            blockMenu.hidden = true;
            var state = menuState;
            menuState = null;
            if (state && state.toggle) {
                state.toggle.setAttribute('aria-expanded', 'false');
            }
            if (restoreFocus && state && state.returnFocus && document.body.contains(state.returnFocus)) {
                state.returnFocus.focus({ preventScroll: true });
            }
        }

        function runMenuAction(action, block) {
            var toggle = block.querySelector('[data-cp-block-menu-toggle]');
            closeMenu(false);
            if ('focus' !== action && 'fullscreen' !== action) {
                exitFullscreen(block);
            }
            if ('focus' === action) {
                focusBlock(block);
            } else if ('fullscreen' === action) {
                toggleFullscreen(block);
            } else if ('insert-above' === action) {
                openChooser(block, 'before', toggle);
            } else if ('insert-below' === action) {
                openChooser(block, 'after', toggle);
            } else if ('duplicate' === action) {
                duplicateBlock(block);
            } else if ('move-up' === action) {
                moveBlock(block, 'up', toggle);
            } else if ('move-down' === action) {
                moveBlock(block, 'down', toggle);
            } else if ('delete' === action) {
                deleteBlock(block);
            }
        }

        function moveFocusWithin(items, event) {
            var index = items.indexOf(document.activeElement);
            var next = null;
            if ('ArrowDown' === event.key || 'ArrowRight' === event.key) {
                next = items[(index + 1) % items.length];
            } else if ('ArrowUp' === event.key || 'ArrowLeft' === event.key) {
                next = items[(index - 1 + items.length) % items.length];
            } else if ('Home' === event.key) {
                next = items[0];
            } else if ('End' === event.key) {
                next = items[items.length - 1];
            }
            if (next) {
                event.preventDefault();
                next.focus();
            }
        }

        if (blockMenu) {
            blockMenu.addEventListener('click', function (event) {
                var item = event.target.closest('[data-cp-menu-action]');
                if (item && !item.disabled && menuState) {
                    runMenuAction(item.getAttribute('data-cp-menu-action'), menuState.block);
                }
            });
            blockMenu.addEventListener('keydown', function (event) {
                if ('Escape' === event.key) {
                    event.preventDefault();
                    event.stopPropagation();
                    closeMenu(true);
                } else if ('Tab' === event.key) {
                    event.preventDefault();
                    closeMenu(true);
                } else {
                    moveFocusWithin(menuItems(), event);
                }
            });
        }

        if (chooser) {
            chooser.addEventListener('click', function (event) {
                if (event.target.closest('[data-cp-chooser-close]')) {
                    closeChooser(true);
                    return;
                }
                var choice = event.target.closest('[data-cp-add-type]');
                if (choice && chooserState) {
                    var state = chooserState;
                    closeChooser(false);
                    insertBlock(choice.getAttribute('data-cp-add-type'), state.block, state.position);
                }
            });
            chooser.addEventListener('keydown', function (event) {
                if ('Escape' === event.key) {
                    event.preventDefault();
                    event.stopPropagation();
                    closeChooser(true);
                } else if (event.target.closest('[data-cp-add-type]')) {
                    moveFocusWithin(Array.prototype.slice.call(chooser.querySelectorAll('[data-cp-add-type]')), event);
                }
            });
            chooser.addEventListener('focusout', function (event) {
                if (event.relatedTarget && !chooser.contains(event.relatedTarget)) {
                    closeChooser(false);
                }
            });
        }

        document.addEventListener('pointerdown', function (event) {
            if (blockMenu && !blockMenu.hidden && !blockMenu.contains(event.target) && !event.target.closest('[data-cp-block-menu-toggle]')) {
                closeMenu(false);
            }
            if (chooser && !chooser.hidden && !chooser.contains(event.target) && !event.target.closest('[data-cp-insert-below]')) {
                closeChooser(false);
            }
        });

        window.addEventListener('resize', function () {
            closeMenu(false);
            closeChooser(false);
        });

        document.addEventListener('keydown', function (event) {
            if ('Escape' !== event.key) {
                return;
            }
            if (addBlockArea && addBlockArea.classList.contains('cp-add-block-open')) {
                setAddPanel(false);
                addToggle.focus();
                return;
            }
            var fullscreenBlock = blockList.querySelector('.cp-block-fullscreen');
            if (fullscreenBlock) {
                event.preventDefault();
                exitFullscreen(fullscreenBlock);
                var button = fullscreenBlock.querySelector('[data-cp-fullscreen]');
                if (button) {
                    button.focus({ preventScroll: true });
                }
            }
        });

        /* ------------------------------------------------------------------
         * Delegated block events - one set of listeners for saved and newly
         * added blocks alike, so nothing needs re-wiring after DOM changes.
         * ---------------------------------------------------------------- */

        blockList.addEventListener('click', function (event) {
            var block = event.target.closest('[data-cp-block]');
            if (!block || block.parentNode !== blockList) {
                return;
            }
            var button = event.target.closest('button');

            if (event.target.closest('[data-cp-move-up]')) {
                moveBlock(block, 'up', button);
            } else if (event.target.closest('[data-cp-move-down]')) {
                moveBlock(block, 'down', button);
            } else if (event.target.closest('[data-cp-duplicate]')) {
                duplicateBlock(block);
            } else if (event.target.closest('[data-cp-fullscreen]')) {
                toggleFullscreen(block);
            } else if (event.target.closest('[data-cp-block-menu-toggle]')) {
                if (menuState && menuState.block === block) {
                    closeMenu(true);
                } else {
                    openMenu(block, { fromToggle: true, returnFocus: button });
                }
            } else if (event.target.closest('[data-cp-remove]')) {
                deleteBlock(block);
            } else if (event.target.closest('[data-cp-insert-below]')) {
                if (chooserState && chooserState.block === block) {
                    closeChooser(true);
                } else {
                    openChooser(block, 'after', button);
                }
            } else if (event.target.closest('[data-cp-select-image]')) {
                selectBlockImage(block);
            } else if (event.target.closest('[data-cp-remove-image]')) {
                removeBlockImage(block);
            } else if (event.target.closest('[data-cp-divider-size]')) {
                var sizeField = block.querySelector('[data-cp-field="size"]');
                if (sizeField) {
                    sizeField.value = event.target.closest('[data-cp-divider-size]').getAttribute('data-cp-divider-size');
                    refreshDivider(block);
                }
            } else if ('staff_grid' === block.getAttribute('data-block-type')) {
                handleStaffClick(event, block);
            }
        });

        /* Right-click on the block's own chrome (header, number, toolbar)
           opens the block menu. Text inside fields - and inside the editor
           iframe, whose events never reach this document - keeps the normal
           browser/TinyMCE context menu. Shift+F10 / the Menu key on a
           focused toolbar button opens it too. */
        blockList.addEventListener('contextmenu', function (event) {
            var chrome = event.target.closest('[data-cp-block-chrome]');
            var block = chrome ? chrome.closest('[data-cp-block]') : null;
            if (!block || block.classList.contains('cp-block-fullscreen')) {
                return;
            }
            event.preventDefault();
            var fromKeyboard = 0 === event.clientX && 0 === event.clientY;
            openMenu(block, fromKeyboard
                ? { returnFocus: document.activeElement }
                : { x: event.clientX, y: event.clientY, returnFocus: block.querySelector('[data-cp-block-menu-toggle]') });
        });

        blockList.addEventListener('input', function (event) {
            if (event.target.matches('[data-cp-field="label"]')) {
                refreshButtonPreview(event.target.closest('[data-cp-block]'));
            }
        });

        blockList.addEventListener('change', function (event) {
            var block = event.target.closest('[data-cp-block]');
            if (!block) {
                return;
            }
            if (event.target.matches('[data-cp-field="level"]')) {
                applyHeadingPresentation(block);
            } else if (event.target.matches('[data-cp-field="style"]')) {
                refreshButtonPreview(block);
            } else if (event.target.matches('[data-cp-staff-field="bundled"]')) {
                refreshStaffPreview(event.target.closest('[data-cp-staff-person]'));
            }
        });

        blockList.addEventListener('focusin', function (event) {
            var block = event.target.closest('[data-cp-block]');
            if (block) {
                setActiveBlock(block);
            }
        });

        /* ---- Drag to reorder (from the grip handle only, so text in the
           block's own fields stays selectable) ---- */

        blockList.addEventListener('pointerdown', function (event) {
            var handle = event.target.closest('[data-cp-drag-handle]');
            if (handle) {
                handle.closest('[data-cp-block]').setAttribute('draggable', 'true');
            }
        });

        document.addEventListener('pointerup', function () {
            if (!draggedBlock) {
                allBlocks().forEach(function (block) {
                    block.removeAttribute('draggable');
                });
            }
        });

        blockList.addEventListener('dragstart', function (event) {
            var block = event.target.closest && event.target.closest('[data-cp-block]');
            if (!block || 'true' !== block.getAttribute('draggable')) {
                return;
            }
            draggedBlock = block;
            if (event.dataTransfer) {
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', '');
            }
            closeMenu(false);
            closeChooser(false);
            // After the browser has captured the drag image.
            window.setTimeout(function () {
                if (draggedBlock !== block) {
                    return;
                }
                editorsIn(block).forEach(removeEditor);
                block.classList.add('is-dragging');
                form.classList.add('cp-is-dragging-block');
            }, 0);
        });

        blockList.addEventListener('dragover', function (event) {
            if (!draggedBlock) {
                return;
            }
            event.preventDefault();
            var target = event.target.closest && event.target.closest('[data-cp-block]');
            if (!target || target === draggedBlock || target.parentNode !== blockList) {
                return;
            }
            var rect = target.getBoundingClientRect();
            var before = (event.clientY - rect.top) < rect.height / 2;
            blockList.insertBefore(draggedBlock, before ? target : target.nextElementSibling);
        });

        blockList.addEventListener('drop', function (event) {
            if (draggedBlock) {
                event.preventDefault();
            }
        });

        blockList.addEventListener('dragend', function () {
            var block = draggedBlock;
            draggedBlock = null;
            form.classList.remove('cp-is-dragging-block');
            if (!block) {
                return;
            }
            block.classList.remove('is-dragging');
            block.removeAttribute('draggable');
            editorsIn(block).forEach(initEditor);
            refreshBlocks();
            flash(block, 'cp-block-just-moved', 600);
        });

        /* ---- Add Block (bottom of the page) ---- */

        if (addToggle && addBlockArea) {
            addToggle.addEventListener('click', function () {
                closeChooser(false);
                closeMenu(false);
                setAddPanel(!addBlockArea.classList.contains('cp-add-block-open'));
            });
            addBlockArea.addEventListener('click', function (event) {
                var choice = event.target.closest('[data-cp-add-type]');
                if (choice) {
                    setAddPanel(false);
                    insertBlock(choice.getAttribute('data-cp-add-type'), null, 'after');
                }
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

        allBlocks().forEach(function (block) {
            initBlock(block);
            refreshDivider(block);
            refreshButtonPreview(block);
        });
        refreshBlocks();

        form.addEventListener('submit', function () {
            serializeBlocks();
        });
    });
}());
