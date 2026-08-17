(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('cp-article-builder-form');
        if (!form) {
            return;
        }

        var blockList = form.querySelector('[data-cp-block-list]');
        var emptyState = form.querySelector('[data-cp-builder-empty]');
        var blockCount = form.querySelector('[data-cp-block-count]');
        var hiddenBlocks = document.getElementById('cp-article-blocks');
        var confirmElement = document.getElementById('cp-confirm-article-modal');
        var confirmModal = window.bootstrap && confirmElement ? window.bootstrap.Modal.getOrCreateInstance(confirmElement) : null;
        var heroField = form.querySelector('[data-cp-hero-image]');
        var heroWarning = confirmElement ? confirmElement.querySelector('[data-cp-hero-warning]') : null;
        var homepageFeatureInput = form.querySelector('[data-cp-homepage-feature]');
        var authorDisplay = form.querySelector('[data-cp-author-display]');
        var authorMode = form.querySelector('[data-cp-author-mode]');
        var authorUserId = form.querySelector('[data-cp-author-user-id]');
        var authorCustom = form.querySelector('[data-cp-author-custom]');
        var authorHelp = form.querySelector('[data-cp-author-help]');
        var authorEditButton = form.querySelector('[data-cp-author-edit]');
        var authorChoiceElement = document.getElementById('cp-author-choice-modal');
        var authorAccountsElement = document.getElementById('cp-author-accounts-modal');
        var authorCustomElement = document.getElementById('cp-author-custom-modal');
        var authorConfirmElement = document.getElementById('cp-author-confirm-modal');
        var authorChoiceModal = window.bootstrap && authorChoiceElement ? window.bootstrap.Modal.getOrCreateInstance(authorChoiceElement) : null;
        var authorAccountsModal = window.bootstrap && authorAccountsElement ? window.bootstrap.Modal.getOrCreateInstance(authorAccountsElement) : null;
        var authorCustomModal = window.bootstrap && authorCustomElement ? window.bootstrap.Modal.getOrCreateInstance(authorCustomElement) : null;
        var authorConfirmModal = window.bootstrap && authorConfirmElement ? window.bootstrap.Modal.getOrCreateInstance(authorConfirmElement) : null;
        var pendingAuthor = null;
        var confirmed = false;
        var nextId = Date.now();
        var labels = { heading: 'Heading', paragraph: 'Paragraph', image: 'Image', video: 'Video URL', table: 'Table' };
        var tableLimits = { rows: 20, columns: 10 };
        var inlineTablePicker = null;
        var inlineTablePickerEditor = null;
        var inlineTablePickerBookmark = null;
        var inlineTablePickerAnchor = null;
        var detailsSettingsButton = form.querySelector('[data-cp-details-settings]');
        var detailsEditorElement = document.getElementById('cp-article-details-editor-modal');
        var detailsEditorModal = window.bootstrap && detailsEditorElement ? window.bootstrap.Modal.getOrCreateInstance(detailsEditorElement) : null;
        var detailsApplyButton = detailsEditorElement ? detailsEditorElement.querySelector('[data-cp-details-apply]') : null;
        var detailsTitleTextarea = detailsEditorElement ? detailsEditorElement.querySelector('[data-cp-details-title-editor]') : null;
        var detailsExcerptTextarea = detailsEditorElement ? detailsEditorElement.querySelector('[data-cp-details-excerpt-editor]') : null;
        var titleRichInput = form.querySelector('[data-cp-title-rich]');
        var excerptRichInput = form.querySelector('[data-cp-excerpt-rich]');
        var mainTitleInput = form.querySelector('[name="title"]');
        var mainExcerptInput = form.querySelector('[name="excerpt"]');
        var syncingDetailsFields = false;

        function element(tag, className, text) {
            var node = document.createElement(tag);
            if (className) {
                node.className = className;
            }
            if (typeof text === 'string') {
                node.textContent = text;
            }
            return node;
        }

        function iconButton(icon, action, title, danger) {
            var button = element('button', 'cp-icon-button' + (danger ? ' cp-icon-button-danger' : ''));
            button.type = 'button';
            button.title = title;
            button.setAttribute(action, '');
            var iconNode = element('i', 'bi ' + icon);
            button.appendChild(iconNode);
            return button;
        }

        function labelledField(labelText, input, className) {
            var wrapper = element('div', className || '');
            var label = element('label', 'form-label', labelText);
            label.htmlFor = input.id;
            wrapper.appendChild(label);
            wrapper.appendChild(input);
            return wrapper;
        }

        function inputField(id, field, type, value) {
            var input = element('input', 'form-control');
            input.id = id;
            input.type = type || 'text';
            input.value = value || '';
            input.setAttribute('data-cp-field', field);
            return input;
        }

        function paragraphEditorSettings() {
            return {
                mediaButtons: false,
                quicktags: false,
                tinymce: {
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    resize: true,
                    height: 300,
                    toolbar1: 'fontselect fontsizeselect | bold italic underline blockquote | bullist numlist | link unlink | alignleft aligncenter alignright | cp_table | undo redo',
                    toolbar2: '',
                    font_formats: 'Default=-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;Arial=Arial,Helvetica,sans-serif;Arial Black="Arial Black",Arial,sans-serif;Georgia=Georgia,serif;Times New Roman="Times New Roman",Times,serif;Verdana=Verdana,Geneva,sans-serif;Tahoma=Tahoma,Geneva,sans-serif;Trebuchet MS="Trebuchet MS",Helvetica,sans-serif;Courier New="Courier New",Courier,monospace',
                    fontsize_formats: '10px 11px 12px 14px 16px 18px 20px 24px 28px 32px 36px 48px',
                    extended_valid_elements: 'table[class|style],caption[style],thead,tbody,tfoot,tr[style],th[colspan|rowspan|scope|style],td[colspan|rowspan|style],span[class|style]',
                    valid_children: '+body[table],+td[p|br|span|strong|b|em|i|u|a|ul|ol],+th[p|br|span|strong|b|em|i|u|a|ul|ol]',
                    formats: {
                        alignleft: { selector: 'p,blockquote,ul,ol,table', classes: 'cp-align-left' },
                        aligncenter: { selector: 'p,blockquote,ul,ol,table', classes: 'cp-align-center' },
                        alignright: { selector: 'p,blockquote,ul,ol,table', classes: 'cp-align-right' }
                    },
                    setup: function (editor) {
                        editor.addButton('cp_table', {
                            icon: 'cp-table',
                            tooltip: 'Insert table',
                            onclick: function () {
                                openInlineTablePicker(editor);
                            }
                        });

                        editor.on('focus', function () {
                            setActiveBlock(paragraphCardForEditor(editor));
                        });

                        editor.on('blur', function () {
                            window.setTimeout(function () {
                                var card = paragraphCardForEditor(editor);
                                if (card && !card.matches(':hover') && !card.contains(document.activeElement) && inlineTablePickerEditor !== editor) {
                                    card.classList.remove('cp-block-active');
                                }
                            }, 120);
                        });

                        editor.on('remove', function () {
                            if (inlineTablePickerEditor === editor) {
                                closeInlineTablePicker(false);
                            }
                        });
                    },
                    content_style: 'body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;font-size:16px;line-height:1.7;padding:18px 20px;color:#1d2939;background:#fff;}p{margin:0 0 1em}blockquote{border-left:4px solid #d0d5dd;margin:1em 0;padding:.25em 1em}.cp-align-left{text-align:left}.cp-align-center{text-align:center}.cp-align-right{text-align:right}.cp-inline-table{width:100%;margin:1em 0;border-collapse:collapse;table-layout:auto}.cp-inline-table td,.cp-inline-table th{min-width:90px;padding:9px 10px;border:1px solid #b8bec8;vertical-align:top}.cp-inline-table td:focus,.cp-inline-table th:focus{outline:2px solid #5b5ce2;outline-offset:-2px}'
                }
            };
        }

        function headingEditorSettings() {
            return {
                mediaButtons: false,
                quicktags: false,
                tinymce: {
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    resize: true,
                    height: 190,
                    forced_root_block: false,
                    toolbar1: 'fontselect fontsizeselect | bold italic underline | link unlink | undo redo',
                    toolbar2: '',
                    font_formats: 'Default=-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;Arial=Arial,Helvetica,sans-serif;Arial Black="Arial Black",Arial,sans-serif;Georgia=Georgia,serif;Times New Roman="Times New Roman",Times,serif;Verdana=Verdana,Geneva,sans-serif;Tahoma=Tahoma,Geneva,sans-serif;Trebuchet MS="Trebuchet MS",Helvetica,sans-serif;Courier New="Courier New",Courier,monospace',
                    fontsize_formats: '16px 18px 20px 24px 28px 32px 36px 42px 48px 56px',
                    valid_elements: 'br,strong[style],b[style],em[style],i[style],u[style],a[href|title|target|rel|style],span[class|style]',
                    setup: function (editor) {
                        editor.on('focus', function () { setActiveBlock(paragraphCardForEditor(editor)); });
                        editor.on('blur', function () {
                            window.setTimeout(function () {
                                var card = paragraphCardForEditor(editor);
                                if (card && !card.matches(':hover') && !card.contains(document.activeElement)) {
                                    card.classList.remove('cp-block-active');
                                }
                            }, 120);
                        });
                    },
                    content_style: 'body{font-family:Georgia,serif;font-size:28px;font-weight:700;line-height:1.25;padding:18px 20px;color:#1d2939;background:#fff;}a{color:#3b3db8}'
                }
            };
        }

        function detailsTitleEditorSettings() {
            var settings = headingEditorSettings();
            settings.tinymce.height = 180;
            settings.tinymce.setup = function () {};
            return settings;
        }

        function detailsExcerptEditorSettings() {
            return {
                mediaButtons: false,
                quicktags: false,
                tinymce: {
                    menubar: false,
                    statusbar: false,
                    branding: false,
                    resize: true,
                    height: 280,
                    toolbar1: 'fontselect fontsizeselect | bold italic underline | link unlink | alignleft aligncenter alignright | undo redo',
                    toolbar2: '',
                    font_formats: 'Default=-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;Arial=Arial,Helvetica,sans-serif;Arial Black="Arial Black",Arial,sans-serif;Georgia=Georgia,serif;Times New Roman="Times New Roman",Times,serif;Verdana=Verdana,Geneva,sans-serif;Tahoma=Tahoma,Geneva,sans-serif;Trebuchet MS="Trebuchet MS",Helvetica,sans-serif;Courier New="Courier New",Courier,monospace',
                    fontsize_formats: '10px 11px 12px 14px 16px 18px 20px 24px 28px 32px 36px',
                    valid_elements: 'p[style],br,strong[style],b[style],em[style],i[style],u[style],a[href|title|target|rel|style],span[class|style]',
                    content_style: 'body{font-family:Georgia,serif;font-size:18px;line-height:1.6;padding:18px 20px;color:#344054;background:#fff;}p{margin:0 0 .8em}'
                }
            };
        }

        function paragraphCardForEditor(editor) {
            if (!editor || !editor.id) {
                return null;
            }
            var textarea = document.getElementById(editor.id);
            return textarea ? textarea.closest('[data-cp-block]') : null;
        }

        function setActiveBlock(card) {
            if (!card) {
                return;
            }
            blockList.querySelectorAll('[data-cp-block].cp-block-active').forEach(function (activeCard) {
                if (activeCard !== card) {
                    activeCard.classList.remove('cp-block-active');
                }
            });
            card.classList.add('cp-block-active');
        }

        function buildInlineTableMarkup(rows, columns) {
            var html = '<table class="cp-inline-table"><tbody>';
            for (var row = 0; row < rows; row += 1) {
                html += '<tr>';
                for (var column = 0; column < columns; column += 1) {
                    html += '<td><br></td>';
                }
                html += '</tr>';
            }
            return html + '</tbody></table><p><br></p>';
        }

        function highlightInlineTableGrid(rows, columns) {
            if (!inlineTablePicker) {
                return;
            }
            inlineTablePicker.querySelectorAll('[data-cp-inline-table-cell]').forEach(function (cell) {
                var cellRow = Number(cell.getAttribute('data-row'));
                var cellColumn = Number(cell.getAttribute('data-column'));
                cell.classList.toggle('is-selected', cellRow <= rows && cellColumn <= columns);
            });
            var status = inlineTablePicker.querySelector('[data-cp-inline-table-status]');
            if (status) {
                status.textContent = columns + ' × ' + rows + ' table';
            }
        }

        function positionInlineTablePicker() {
            if (!inlineTablePicker || inlineTablePicker.hidden || !inlineTablePickerAnchor) {
                return;
            }
            var rect = inlineTablePickerAnchor.getBoundingClientRect();
            var pickerRect = inlineTablePicker.getBoundingClientRect();
            var left = Math.min(Math.max(12, rect.left), Math.max(12, window.innerWidth - pickerRect.width - 12));
            var top = rect.bottom + 8;
            if (top + pickerRect.height > window.innerHeight - 12) {
                top = Math.max(12, rect.top - pickerRect.height - 8);
            }
            inlineTablePicker.style.left = left + 'px';
            inlineTablePicker.style.top = top + 'px';
        }

        function closeInlineTablePicker(restoreEditorFocus) {
            if (!inlineTablePicker || inlineTablePicker.hidden) {
                return;
            }
            var editor = inlineTablePickerEditor;
            var bookmark = inlineTablePickerBookmark;
            inlineTablePicker.hidden = true;
            inlineTablePicker.classList.remove('is-open');
            inlineTablePickerEditor = null;
            inlineTablePickerBookmark = null;
            inlineTablePickerAnchor = null;
            if (restoreEditorFocus && editor) {
                editor.focus();
                if (bookmark) {
                    try {
                        editor.selection.moveToBookmark(bookmark);
                    } catch (error) {
                        // The editor will place the caret at its latest valid selection.
                    }
                }
            }
        }

        function ensureInlineTablePicker() {
            if (inlineTablePicker) {
                return inlineTablePicker;
            }

            var picker = element('div', 'cp-inline-table-picker');
            picker.hidden = true;
            picker.setAttribute('role', 'dialog');
            picker.setAttribute('aria-label', 'Choose table size');

            var heading = element('div', 'cp-inline-table-picker-heading');
            heading.appendChild(element('strong', '', 'Insert table'));
            var status = element('span', '', '1 × 1 table');
            status.setAttribute('data-cp-inline-table-status', '');
            heading.appendChild(status);
            picker.appendChild(heading);

            var grid = element('div', 'cp-inline-table-picker-grid');
            grid.setAttribute('role', 'grid');
            for (var row = 1; row <= 10; row += 1) {
                for (var column = 1; column <= 10; column += 1) {
                    var cell = element('button', 'cp-inline-table-picker-cell');
                    cell.type = 'button';
                    cell.setAttribute('role', 'gridcell');
                    cell.setAttribute('data-cp-inline-table-cell', '');
                    cell.setAttribute('data-row', String(row));
                    cell.setAttribute('data-column', String(column));
                    cell.setAttribute('aria-label', column + ' columns by ' + row + ' rows');
                    cell.addEventListener('mouseenter', function () {
                        highlightInlineTableGrid(Number(this.getAttribute('data-row')), Number(this.getAttribute('data-column')));
                    });
                    cell.addEventListener('focus', function () {
                        highlightInlineTableGrid(Number(this.getAttribute('data-row')), Number(this.getAttribute('data-column')));
                    });
                    cell.addEventListener('click', function () {
                        var editor = inlineTablePickerEditor;
                        var bookmark = inlineTablePickerBookmark;
                        var selectedRows = Number(this.getAttribute('data-row'));
                        var selectedColumns = Number(this.getAttribute('data-column'));
                        if (!editor) {
                            closeInlineTablePicker(false);
                            return;
                        }
                        inlineTablePicker.hidden = true;
                        inlineTablePicker.classList.remove('is-open');
                        inlineTablePickerEditor = null;
                        inlineTablePickerBookmark = null;
                        inlineTablePickerAnchor = null;
                        editor.focus();
                        if (bookmark) {
                            try {
                                editor.selection.moveToBookmark(bookmark);
                            } catch (error) {
                                // Continue with the editor's current caret position.
                            }
                        }
                        editor.insertContent(buildInlineTableMarkup(selectedRows, selectedColumns));
                        editor.nodeChanged();
                        setActiveBlock(paragraphCardForEditor(editor));
                    });
                    grid.appendChild(cell);
                }
            }
            picker.appendChild(grid);
            document.body.appendChild(picker);
            inlineTablePicker = picker;

            document.addEventListener('mousedown', function (event) {
                if (!inlineTablePicker || inlineTablePicker.hidden) {
                    return;
                }
                if (!inlineTablePicker.contains(event.target) && (!inlineTablePickerAnchor || !inlineTablePickerAnchor.contains(event.target))) {
                    closeInlineTablePicker(false);
                }
            });
            document.addEventListener('keydown', function (event) {
                if ('Escape' === event.key && inlineTablePicker && !inlineTablePicker.hidden) {
                    event.preventDefault();
                    closeInlineTablePicker(true);
                }
            });
            window.addEventListener('resize', positionInlineTablePicker);
            window.addEventListener('scroll', positionInlineTablePicker, true);

            return picker;
        }

        function openInlineTablePicker(editor) {
            var picker = ensureInlineTablePicker();
            if (inlineTablePickerEditor === editor && !picker.hidden) {
                closeInlineTablePicker(true);
                return;
            }

            closeInlineTablePicker(false);
            inlineTablePickerEditor = editor;
            inlineTablePickerBookmark = editor.selection.getBookmark(2, true);
            var container = editor.getContainer();
            var icon = container ? container.querySelector('.mce-i-cp-table') : null;
            inlineTablePickerAnchor = icon ? icon.closest('.mce-btn') : null;
            if (!inlineTablePickerAnchor && container) {
                inlineTablePickerAnchor = container.querySelector('[aria-label="Insert table"], [title="Insert table"]');
            }
            if (!inlineTablePickerAnchor) {
                inlineTablePickerAnchor = container || document.body;
            }

            picker.hidden = false;
            picker.classList.add('is-open');
            highlightInlineTableGrid(1, 1);
            positionInlineTablePicker();
            var firstCell = picker.querySelector('[data-cp-inline-table-cell]');
            if (firstCell) {
                firstCell.focus({ preventScroll: true });
            }
            setActiveBlock(paragraphCardForEditor(editor));
        }

        function getTinyMceEditor(textarea) {
            if (!textarea || !window.tinymce || !textarea.id) {
                return null;
            }
            return window.tinymce.get(textarea.id);
        }

        function cpInitializeParagraphEditor(target) {
            var textareas = [];
            if (target && target.matches && target.matches('[data-cp-paragraph-editor], [data-cp-heading-editor]')) {
                textareas = [target];
            } else if (target && target.querySelectorAll) {
                textareas = Array.prototype.slice.call(target.querySelectorAll('[data-cp-paragraph-editor], [data-cp-heading-editor]'));
            } else {
                textareas = Array.prototype.slice.call(blockList.querySelectorAll('[data-cp-paragraph-editor], [data-cp-heading-editor]'));
            }

            textareas.forEach(function (textarea) {
                if (!textarea.id || textarea.getAttribute('data-cp-editor-initialized') === '1' || getTinyMceEditor(textarea)) {
                    return;
                }
                if (!window.wp || !window.wp.editor || typeof window.wp.editor.initialize !== 'function') {
                    textarea.classList.add('cp-paragraph-editor-fallback');
                    return;
                }

                try {
                    window.wp.editor.initialize(textarea.id, textarea.matches('[data-cp-heading-editor]') ? headingEditorSettings() : paragraphEditorSettings());
                    textarea.setAttribute('data-cp-editor-initialized', '1');
                    textarea.required = false;
                } catch (error) {
                    textarea.classList.add('cp-paragraph-editor-fallback');
                    textarea.required = true;
                }
            });
        }

        function cpSyncParagraphEditors(scope) {
            var root = scope && scope.querySelectorAll ? scope : blockList;
            var textareas = root.matches && root.matches('[data-cp-paragraph-editor], [data-cp-heading-editor]')
                ? [root]
                : Array.prototype.slice.call(root.querySelectorAll('[data-cp-paragraph-editor], [data-cp-heading-editor]'));

            textareas.forEach(function (textarea) {
                var editor = getTinyMceEditor(textarea);
                if (editor) {
                    editor.save();
                    textarea.value = editor.getContent({ format: 'html' });
                }
            });
        }

        function cpRemoveParagraphEditor(target) {
            var textareas = [];
            if (target && target.matches && target.matches('[data-cp-paragraph-editor], [data-cp-heading-editor]')) {
                textareas = [target];
            } else if (target && target.querySelectorAll) {
                textareas = Array.prototype.slice.call(target.querySelectorAll('[data-cp-paragraph-editor], [data-cp-heading-editor]'));
            }

            textareas.forEach(function (textarea) {
                cpSyncParagraphEditors(textarea);
                if (window.wp && window.wp.editor && typeof window.wp.editor.remove === 'function' && textarea.id) {
                    try {
                        window.wp.editor.remove(textarea.id);
                    } catch (error) {
                        var editor = getTinyMceEditor(textarea);
                        if (editor) {
                            editor.remove();
                        }
                    }
                } else {
                    var editor = getTinyMceEditor(textarea);
                    if (editor) {
                        editor.remove();
                    }
                }
                textarea.removeAttribute('data-cp-editor-initialized');
                textarea.required = true;
            });
        }

        window.cpInitializeParagraphEditor = cpInitializeParagraphEditor;
        window.cpRemoveParagraphEditor = cpRemoveParagraphEditor;
        window.cpSyncParagraphEditors = cpSyncParagraphEditors;

        function createCard(type, data) {
            data = data || {};
            nextId += 1;
            var id = 'cp-block-new-' + nextId;
            var card = element('article', 'cp-builder-block cp-block-entering');
            card.setAttribute('data-cp-block', '');
            card.setAttribute('data-block-type', type);

            var head = element('div', 'cp-builder-block-head');
            var title = element('div', 'cp-builder-block-title');
            title.appendChild(element('span', 'cp-block-number', '0'));
            var titleText = element('div');
            titleText.appendChild(element('strong', '', labels[type] || 'Content Block'));
            titleText.appendChild(element('small', '', 'Content block'));
            title.appendChild(titleText);
            head.appendChild(title);
            var actions = element('div', 'cp-builder-block-actions');
            actions.appendChild(iconButton('bi-arrow-up', 'data-cp-move-up', 'Move Up'));
            actions.appendChild(iconButton('bi-arrow-down', 'data-cp-move-down', 'Move Down'));
            actions.appendChild(iconButton('bi-copy', 'data-cp-duplicate', 'Duplicate'));
            if ('heading' === type || 'paragraph' === type) {
                actions.appendChild(iconButton('bi-arrows-fullscreen', 'data-cp-fullscreen', 'Full Screen'));
            }
            actions.appendChild(iconButton('bi-trash', 'data-cp-remove', 'Remove', true));
            head.appendChild(actions);
            card.appendChild(head);

            var body = element('div', 'cp-builder-block-body');
            if ('heading' === type) {
                var headingField = element('div', 'cp-heading-field');
                var levelBar = element('div', 'cp-heading-level-bar');
                var levelLabel = element('label', '', 'Heading Level');
                var level = element('select', 'form-select form-select-sm');
                level.id = id + '-level';
                levelLabel.htmlFor = level.id;
                level.setAttribute('data-cp-field', 'level');
                for (var i = 1; i <= 6; i += 1) {
                    var option = element('option', '', 'H' + i);
                    option.value = String(i);
                    option.selected = Number(data.level || 2) === i;
                    level.appendChild(option);
                }
                levelBar.appendChild(levelLabel);
                levelBar.appendChild(level);
                levelBar.appendChild(element('span', '', 'Choose the heading hierarchy, then format the heading below.'));
                headingField.appendChild(levelBar);
                var heading = element('textarea', 'form-control cp-heading-editor');
                heading.id = id + '-content';
                heading.rows = 4;
                heading.required = true;
                heading.value = data.content || '';
                heading.setAttribute('data-cp-field', 'content');
                heading.setAttribute('data-cp-heading-editor', '');
                headingField.appendChild(heading);
                body.appendChild(headingField);
            } else if ('paragraph' === type) {
                var paragraph = element('textarea', 'form-control cp-paragraph-editor');
                paragraph.id = id + '-content';
                paragraph.rows = 6;
                paragraph.required = true;
                paragraph.value = data.content || '';
                paragraph.setAttribute('data-cp-field', 'content');
                paragraph.setAttribute('data-cp-paragraph-editor', '');
                body.appendChild(labelledField('Paragraph content', paragraph, 'cp-paragraph-field'));
            } else if ('image' === type) {
                buildImageFields(body, id, data);
            } else if ('video' === type) {
                var video = inputField(id + '-url', 'url', 'url', data.url);
                video.required = true;
                var videoWrap = labelledField('Video URL', video, 'mb-3');
                videoWrap.appendChild(element('div', 'form-text', 'Paste a video URL from YouTube, Vimeo, Facebook, TikTok, or another supported oEmbed provider.'));
                body.appendChild(videoWrap);
                body.appendChild(labelledField('Caption (optional)', inputField(id + '-caption', 'caption', 'text', data.caption)));
            } else if ('table' === type) {
                buildTableFields(body, id, data);
            }
            card.appendChild(body);
            window.setTimeout(function () { card.classList.remove('cp-block-entering'); }, 260);
            return card;
        }

        function buildImageFields(body, id, data) {
            var source = 'url' === data.source ? 'url' : 'media';
            var sourceWrap = element('div', 'mb-3');
            sourceWrap.appendChild(element('label', 'form-label', 'Image Source'));
            var selector = element('div', 'cp-source-selector');
            ['media', 'url'].forEach(function (value) {
                var label = element('label');
                var radio = element('input');
                radio.type = 'radio';
                radio.name = id + '-source';
                radio.value = value;
                radio.checked = source === value;
                radio.setAttribute('data-cp-image-source', '');
                label.appendChild(radio);
                label.appendChild(element('span', '', 'media' === value ? 'Upload / Media Library' : 'Image URL'));
                selector.appendChild(label);
            });
            sourceWrap.appendChild(selector);
            body.appendChild(sourceWrap);

            var sourceInput = inputField(id + '-source-value', 'source', 'hidden', source);
            sourceInput.className = '';
            body.appendChild(sourceInput);
            var attachmentInput = inputField(id + '-attachment', 'attachment_id', 'hidden', String(data.attachment_id || 0));
            attachmentInput.className = '';
            body.appendChild(attachmentInput);

            var mediaFields = element('div');
            mediaFields.setAttribute('data-cp-media-fields', '');
            mediaFields.hidden = 'media' !== source;
            var selectButton = element('button', 'btn btn-outline-primary');
            selectButton.type = 'button';
            selectButton.setAttribute('data-cp-select-image', '');
            selectButton.appendChild(element('i', 'bi bi-images'));
            selectButton.appendChild(document.createTextNode(' Select Image'));
            mediaFields.appendChild(selectButton);
            var preview = element('div', 'cp-image-preview');
            preview.setAttribute('data-cp-image-preview', '');
            preview.hidden = !data.preview_url;
            if (data.preview_url) {
                var image = element('img');
                image.src = data.preview_url;
                image.alt = '';
                preview.appendChild(image);
            }
            mediaFields.appendChild(preview);
            body.appendChild(mediaFields);

            var urlFields = element('div');
            urlFields.setAttribute('data-cp-url-fields', '');
            urlFields.hidden = 'url' !== source;
            urlFields.appendChild(labelledField('Image URL', inputField(id + '-url', 'url', 'url', data.url)));
            body.appendChild(urlFields);

            var row = element('div', 'row g-3 mt-1');
            row.appendChild(labelledField('Alt text', inputField(id + '-alt', 'alt', 'text', data.alt), 'col-md-6'));
            row.appendChild(labelledField('Caption (optional)', inputField(id + '-caption', 'caption', 'text', data.caption), 'col-md-6'));
            body.appendChild(row);
        }

        function normalizeTableRows(data) {
            if (data && Array.isArray(data.rows) && data.rows.length) {
                return data.rows.map(function (row) {
                    return Array.isArray(row) && row.length ? row.map(function (cell) { return String(cell || ''); }) : [''];
                });
            }
            return [['', ''], ['', ''], ['', '']];
        }

        function buildTableFields(body, id, data) {
            var editor = element('div', 'cp-table-editor');
            editor.setAttribute('data-cp-table-editor', '');

            var settings = element('div', 'cp-table-settings');
            var caption = inputField(id + '-caption', 'caption', 'text', data.caption || '');
            caption.removeAttribute('data-cp-field');
            caption.setAttribute('data-cp-table-caption', '');
            settings.appendChild(labelledField('Table caption', caption));

            var headerLabel = element('label', 'cp-table-header-toggle');
            var header = element('input');
            header.type = 'checkbox';
            header.checked = data.has_header !== false;
            header.setAttribute('data-cp-table-header', '');
            headerLabel.appendChild(header);
            headerLabel.appendChild(element('span', '', 'Use first row as header'));
            settings.appendChild(headerLabel);
            editor.appendChild(settings);

            var controls = element('div', 'cp-table-controls');
            controls.setAttribute('aria-label', 'Table controls');
            [
                ['Add row', 'data-cp-table-add-row'],
                ['Remove row', 'data-cp-table-remove-row'],
                ['Add column', 'data-cp-table-add-column'],
                ['Remove column', 'data-cp-table-remove-column']
            ].forEach(function (control) {
                var button = element('button', 'btn btn-outline-secondary btn-sm', control[0]);
                button.type = 'button';
                button.setAttribute(control[1], '');
                controls.appendChild(button);
            });
            var dimensions = element('span', 'cp-table-dimensions');
            dimensions.setAttribute('data-cp-table-dimensions', '');
            controls.appendChild(dimensions);
            editor.appendChild(controls);

            var gridWrap = element('div', 'cp-table-grid-wrap');
            var table = element('table', 'cp-table-grid');
            table.setAttribute('data-cp-table-grid', '');
            var tbody = element('tbody');
            normalizeTableRows(data).forEach(function (row) {
                tbody.appendChild(createTableRow(row));
            });
            table.appendChild(tbody);
            gridWrap.appendChild(table);
            editor.appendChild(gridWrap);
            editor.appendChild(element('p', 'form-text', 'Maximum 20 rows and 10 columns.'));
            body.appendChild(editor);
            refreshTableEditor(editor);
        }

        function createTableRow(values) {
            var row = element('tr');
            row.setAttribute('data-cp-table-row', '');
            values.forEach(function (value) {
                var cell = element('td');
                var input = element('input', 'form-control form-control-sm');
                input.type = 'text';
                input.value = value || '';
                input.setAttribute('data-cp-table-cell', '');
                cell.appendChild(input);
                row.appendChild(cell);
            });
            return row;
        }

        function tableRows(editor) {
            return Array.prototype.slice.call(editor.querySelectorAll('[data-cp-table-row]'));
        }

        function tableColumnCount(editor) {
            var firstRow = editor.querySelector('[data-cp-table-row]');
            return firstRow ? firstRow.querySelectorAll('[data-cp-table-cell]').length : 0;
        }

        function refreshTableEditor(editor) {
            if (!editor) {
                return;
            }
            var rows = tableRows(editor);
            var columns = tableColumnCount(editor);
            rows.forEach(function (row, rowIndex) {
                Array.prototype.forEach.call(row.querySelectorAll('[data-cp-table-cell]'), function (input, columnIndex) {
                    input.setAttribute('aria-label', 'Row ' + (rowIndex + 1) + ', column ' + (columnIndex + 1));
                });
            });
            var dimensions = editor.querySelector('[data-cp-table-dimensions]');
            if (dimensions) {
                dimensions.textContent = rows.length + ' rows × ' + columns + ' columns';
            }
            editor.querySelector('[data-cp-table-add-row]').disabled = rows.length >= tableLimits.rows;
            editor.querySelector('[data-cp-table-remove-row]').disabled = rows.length <= 1;
            editor.querySelector('[data-cp-table-add-column]').disabled = columns >= tableLimits.columns;
            editor.querySelector('[data-cp-table-remove-column]').disabled = columns <= 1;
        }

        function rowHasContent(row) {
            return Array.prototype.some.call(row.querySelectorAll('[data-cp-table-cell]'), function (input) {
                return input.value.trim() !== '';
            });
        }

        function lastColumnHasContent(editor) {
            return tableRows(editor).some(function (row) {
                var cells = row.querySelectorAll('[data-cp-table-cell]');
                return cells.length && cells[cells.length - 1].value.trim() !== '';
            });
        }

        function addTableRow(editor) {
            var rows = tableRows(editor);
            if (rows.length >= tableLimits.rows) {
                return;
            }
            var columns = Math.max(1, tableColumnCount(editor));
            editor.querySelector('tbody').appendChild(createTableRow(new Array(columns).fill('')));
            refreshTableEditor(editor);
        }

        function removeTableRow(editor) {
            var rows = tableRows(editor);
            if (rows.length <= 1) {
                return;
            }
            var row = rows[rows.length - 1];
            if (rowHasContent(row) && !window.confirm('Remove the last row? Its populated cells will be lost.')) {
                return;
            }
            row.remove();
            refreshTableEditor(editor);
        }

        function addTableColumn(editor) {
            var columns = tableColumnCount(editor);
            if (columns >= tableLimits.columns) {
                return;
            }
            tableRows(editor).forEach(function (row) {
                var cell = element('td');
                var input = element('input', 'form-control form-control-sm');
                input.type = 'text';
                input.setAttribute('data-cp-table-cell', '');
                cell.appendChild(input);
                row.appendChild(cell);
            });
            refreshTableEditor(editor);
        }

        function removeTableColumn(editor) {
            var columns = tableColumnCount(editor);
            if (columns <= 1) {
                return;
            }
            if (lastColumnHasContent(editor) && !window.confirm('Remove the last column? Its populated cells will be lost.')) {
                return;
            }
            tableRows(editor).forEach(function (row) {
                var cells = row.querySelectorAll('td');
                if (cells.length) {
                    cells[cells.length - 1].remove();
                }
            });
            refreshTableEditor(editor);
        }

        function fieldValue(card, field) {
            var input = card.querySelector('[data-cp-field="' + field + '"]');
            return input ? input.value : '';
        }

        function serializeTable(card) {
            var editor = card.querySelector('[data-cp-table-editor]');
            return {
                type: 'table',
                caption: editor.querySelector('[data-cp-table-caption]').value,
                has_header: editor.querySelector('[data-cp-table-header]').checked,
                rows: tableRows(editor).map(function (row) {
                    return Array.prototype.map.call(row.querySelectorAll('[data-cp-table-cell]'), function (input) {
                        return input.value;
                    });
                })
            };
        }

        function serializeCard(card) {
            var type = card.getAttribute('data-block-type');
            if ('heading' === type) {
                cpSyncParagraphEditors(card);
                return { type: type, level: Number(fieldValue(card, 'level') || 2), content: fieldValue(card, 'content') };
            }
            if ('paragraph' === type) {
                cpSyncParagraphEditors(card);
                return { type: type, content: fieldValue(card, 'content') };
            }
            if ('image' === type) {
                var previewImage = card.querySelector('[data-cp-image-preview] img');
                return { type: type, source: fieldValue(card, 'source'), attachment_id: Number(fieldValue(card, 'attachment_id') || 0), url: fieldValue(card, 'url'), alt: fieldValue(card, 'alt'), caption: fieldValue(card, 'caption'), preview_url: previewImage ? previewImage.src : '' };
            }
            if ('table' === type) {
                return serializeTable(card);
            }
            return { type: 'video', url: fieldValue(card, 'url'), caption: fieldValue(card, 'caption') };
        }

        function collectBlocks() {
            cpSyncParagraphEditors(blockList);
            return Array.prototype.map.call(blockList.querySelectorAll('[data-cp-block]'), serializeCard);
        }

        function refreshBlocks() {
            var cards = blockList.querySelectorAll('[data-cp-block]');
            cards.forEach(function (card, index) {
                card.querySelector('.cp-block-number').textContent = String(index + 1);
                card.querySelector('[data-cp-move-up]').disabled = 0 === index;
                card.querySelector('[data-cp-move-down]').disabled = index === cards.length - 1;
                if ('table' === card.getAttribute('data-block-type')) {
                    refreshTableEditor(card.querySelector('[data-cp-table-editor]'));
                }
            });
            blockCount.textContent = String(cards.length);
            emptyState.hidden = cards.length > 0;
        }

        function setImageSource(card, source) {
            card.querySelector('[data-cp-field="source"]').value = source;
            card.querySelector('[data-cp-media-fields]').hidden = 'media' !== source;
            card.querySelector('[data-cp-url-fields]').hidden = 'url' !== source;
            var urlInput = card.querySelector('[data-cp-field="url"]');
            if (urlInput) {
                urlInput.required = 'url' === source;
            }
        }

        function showError(message) {
            var notice = form.querySelector('.cp-builder-client-notice');
            if (!notice) {
                notice = element('div', 'cp-builder-client-notice alert alert-danger');
                notice.setAttribute('role', 'alert');
                form.insertBefore(notice, form.firstChild);
            }
            notice.textContent = message;
            notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function stripHtml(html) {
            var documentFragment = document.createElement('div');
            documentFragment.innerHTML = html || '';
            return (documentFragment.textContent || '').replace(/\s+/g, ' ').trim();
        }

        function validateBuilder(blocks) {
            if (!blocks.length) {
                showError('Add at least one content block before saving.');
                return false;
            }
            for (var i = 0; i < blocks.length; i += 1) {
                var block = blocks[i];
                if ('heading' === block.type && !stripHtml(block.content)) {
                    showError('Heading block ' + (i + 1) + ' is incomplete.');
                    return false;
                }
                if ('paragraph' === block.type && !stripHtml(block.content)) {
                    showError('Paragraph block ' + (i + 1) + ' is empty.');
                    return false;
                }
                if ('image' === block.type && 'media' === block.source && !block.attachment_id) {
                    showError('Image block ' + (i + 1) + ' needs a Media Library image.');
                    return false;
                }
                if ('image' === block.type && 'url' === block.source && !/^https?:\/\//i.test(block.url || '')) {
                    showError('Image block ' + (i + 1) + ' needs a valid image URL.');
                    return false;
                }
                if ('video' === block.type && !/^https?:\/\//i.test(block.url || '')) {
                    showError('Video URL block ' + (i + 1) + ' needs a valid URL.');
                    return false;
                }
                if ('table' === block.type) {
                    if (!Array.isArray(block.rows) || block.rows.length < 1 || block.rows.length > tableLimits.rows) {
                        showError('Table block ' + (i + 1) + ' must contain between 1 and 20 rows.');
                        return false;
                    }
                    var columns = Array.isArray(block.rows[0]) ? block.rows[0].length : 0;
                    if (columns < 1 || columns > tableLimits.columns || block.rows.some(function (row) { return !Array.isArray(row) || row.length !== columns; })) {
                        showError('Table block ' + (i + 1) + ' has malformed rows or columns.');
                        return false;
                    }
                    var hasCells = block.rows.some(function (row) { return row.some(function (cell) { return String(cell || '').trim() !== ''; }); });
                    if (!hasCells && !String(block.caption || '').trim()) {
                        showError('Table block ' + (i + 1) + ' is empty.');
                        return false;
                    }
                }
            }
            var notice = form.querySelector('.cp-builder-client-notice');
            if (notice) {
                notice.remove();
            }
            return true;
        }

        function heroSource() {
            var selected = heroField ? heroField.querySelector('[data-cp-hero-source]:checked') : null;
            return selected ? selected.value : 'none';
        }

        function setHeroSource(source) {
            if (!heroField) {
                return;
            }
            heroField.querySelector('[data-cp-hero-media]').hidden = 'media' !== source;
            heroField.querySelector('[data-cp-hero-url-panel]').hidden = 'url' !== source;
            var urlInput = heroField.querySelector('[data-cp-hero-url]');
            if (urlInput) {
                urlInput.required = false;
            }
        }

        function setHeroPreview(container, url) {
            if (!container) {
                return;
            }
            container.textContent = '';
            if (!url) {
                container.hidden = true;
                return;
            }
            var image = element('img');
            image.alt = '';
            image.addEventListener('error', function () {
                container.textContent = '';
                container.hidden = true;
            });
            image.src = url;
            container.appendChild(image);
            container.hidden = false;
        }

        function heroImageExists() {
            var source = heroSource();
            if ('media' === source) {
                return Number(heroField.querySelector('[data-cp-hero-attachment]').value || 0) > 0;
            }
            if ('url' === source) {
                try {
                    var heroUrl = new URL(heroField.querySelector('[data-cp-hero-url]').value.trim());
                    return 'http:' === heroUrl.protocol || 'https:' === heroUrl.protocol;
                } catch (error) {
                    return false;
                }
            }
            return false;
        }

        function openHeroMediaLibrary() {
            if (!window.wp || !window.wp.media) {
                showError('The Media Library is unavailable on this page.');
                return;
            }
            var frame = window.wp.media({ title: 'Select Hero Image', button: { text: 'Use as hero image' }, library: { type: 'image' }, multiple: false });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                heroField.querySelector('[data-cp-hero-attachment]').value = String(attachment.id);
                var previewUrl = attachment.sizes && attachment.sizes.medium_large ? attachment.sizes.medium_large.url : attachment.url;
                setHeroPreview(heroField.querySelector('[data-cp-hero-media-preview]'), previewUrl);
            });
            frame.open();
        }

        form.querySelector('[data-cp-add-toggle]').addEventListener('click', function () {
            form.querySelector('[data-cp-add-block]').classList.toggle('cp-add-block-open');
        });

        form.querySelectorAll('[data-cp-add-type]').forEach(function (button) {
            button.addEventListener('click', function () {
                var card = createCard(button.getAttribute('data-cp-add-type'));
                card.classList.add('cp-block-new');
                blockList.appendChild(card);
                form.querySelector('[data-cp-add-block]').classList.remove('cp-add-block-open');
                refreshBlocks();
                if ('paragraph' === card.getAttribute('data-block-type') || 'heading' === card.getAttribute('data-block-type')) {
                    cpInitializeParagraphEditor(card);
                }
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(function () {
                    var editor = card.querySelector('[data-cp-paragraph-editor], [data-cp-heading-editor]');
                    var tinyEditor = getTinyMceEditor(editor);
                    var firstField = card.querySelector('input:not([type="hidden"]), textarea, select');
                    if (tinyEditor) {
                        tinyEditor.focus();
                    } else if (firstField) {
                        firstField.focus({ preventScroll: true });
                    }
                }, 450);
                window.setTimeout(function () { card.classList.remove('cp-block-new'); }, 1400);
            });
        });

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, function (character) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character];
            });
        }

        function htmlToPlainText(html, keepLineBreaks) {
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html || '';
            if (keepLineBreaks) {
                wrapper.querySelectorAll('br').forEach(function (node) { node.replaceWith(document.createTextNode('\n')); });
                wrapper.querySelectorAll('p').forEach(function (node) { node.appendChild(document.createTextNode('\n')); });
            }
            var value = wrapper.textContent || '';
            if (keepLineBreaks) {
                return value.replace(/[ \t]+\n/g, '\n').replace(/\n{3,}/g, '\n\n').trim();
            }
            return value.replace(/\s+/g, ' ').trim();
        }

        function initializeDetailsEditors() {
            if (!window.wp || !window.wp.editor || typeof window.wp.editor.initialize !== 'function') {
                return;
            }
            [[detailsTitleTextarea, detailsTitleEditorSettings], [detailsExcerptTextarea, detailsExcerptEditorSettings]].forEach(function (entry) {
                var textarea = entry[0];
                if (!textarea || getTinyMceEditor(textarea) || textarea.getAttribute('data-cp-editor-initialized') === '1') {
                    return;
                }
                try {
                    window.wp.editor.initialize(textarea.id, entry[1]());
                    textarea.setAttribute('data-cp-editor-initialized', '1');
                } catch (error) {
                    textarea.classList.add('cp-paragraph-editor-fallback');
                }
            });
        }

        function setDetailsEditorContent(textarea, html) {
            if (!textarea) {
                return;
            }
            var editor = getTinyMceEditor(textarea);
            if (editor) {
                editor.setContent(html || '');
            } else {
                textarea.value = html || '';
            }
        }

        function detailsEditorContent(textarea) {
            if (!textarea) {
                return '';
            }
            var editor = getTinyMceEditor(textarea);
            if (editor) {
                return editor.getContent({ format: 'html' });
            }
            return textarea.value || '';
        }

        function loadDetailsEditors() {
            initializeDetailsEditors();
            var titleHtml = titleRichInput && titleRichInput.value.trim() ? titleRichInput.value : escapeHtml(mainTitleInput ? mainTitleInput.value : '');
            var excerptHtml = excerptRichInput && excerptRichInput.value.trim() ? excerptRichInput.value : escapeHtml(mainExcerptInput ? mainExcerptInput.value : '').replace(/\r?\n/g, '<br>');
            setDetailsEditorContent(detailsTitleTextarea, titleHtml);
            setDetailsEditorContent(detailsExcerptTextarea, excerptHtml);
            window.setTimeout(function () {
                var editor = getTinyMceEditor(detailsTitleTextarea);
                if (editor) { editor.focus(); } else if (detailsTitleTextarea) { detailsTitleTextarea.focus(); }
            }, 80);
        }

        function applyDetailsEditors() {
            var titleHtml = detailsEditorContent(detailsTitleTextarea);
            var excerptHtml = detailsEditorContent(detailsExcerptTextarea);
            var plainTitle = htmlToPlainText(titleHtml, false);
            var plainExcerpt = htmlToPlainText(excerptHtml, true);
            if (!plainTitle) {
                showError('Article title is required.');
                return;
            }
            syncingDetailsFields = true;
            if (mainTitleInput) { mainTitleInput.value = plainTitle; }
            if (mainExcerptInput) { mainExcerptInput.value = plainExcerpt; }
            if (titleRichInput) { titleRichInput.value = titleHtml; }
            if (excerptRichInput) { excerptRichInput.value = excerptHtml; }
            syncingDetailsFields = false;
            if (detailsEditorModal) { detailsEditorModal.hide(); }
        }

        if (detailsSettingsButton && detailsEditorElement) {
            detailsSettingsButton.addEventListener('click', function () {
                if (detailsEditorModal) {
                    detailsEditorModal.show();
                } else {
                    detailsEditorElement.classList.add('show');
                    detailsEditorElement.style.display = 'block';
                    loadDetailsEditors();
                }
            });
            detailsEditorElement.addEventListener('shown.bs.modal', loadDetailsEditors);
            if (detailsApplyButton) { detailsApplyButton.addEventListener('click', applyDetailsEditors); }
        }

        if (mainTitleInput) {
            mainTitleInput.addEventListener('input', function () { if (!syncingDetailsFields && titleRichInput) { titleRichInput.value = ''; } });
        }
        if (mainExcerptInput) {
            mainExcerptInput.addEventListener('input', function () { if (!syncingDetailsFields && excerptRichInput) { excerptRichInput.value = ''; } });
        }

        function setFullscreenButtonState(card, expanded) {
            var button = card ? card.querySelector('[data-cp-fullscreen]') : null;
            if (!button) { return; }
            var icon = button.querySelector('i');
            button.title = expanded ? 'Exit Full Screen' : 'Full Screen';
            button.setAttribute('aria-label', button.title);
            if (icon) { icon.className = 'bi ' + (expanded ? 'bi-fullscreen-exit' : 'bi-arrows-fullscreen'); }
        }

        function exitBlockFullscreen(card) {
            if (!card || !card.classList.contains('cp-block-fullscreen')) { return; }
            card.classList.remove('cp-block-fullscreen');
            document.body.classList.remove('cp-builder-fullscreen-open');
            setFullscreenButtonState(card, false);
        }

        function toggleBlockFullscreen(card) {
            if (!card || !['heading', 'paragraph'].includes(card.getAttribute('data-block-type'))) { return; }
            var opening = !card.classList.contains('cp-block-fullscreen');
            blockList.querySelectorAll('.cp-block-fullscreen').forEach(function (other) { if (other !== card) { exitBlockFullscreen(other); } });
            if (opening) {
                card.classList.add('cp-block-fullscreen');
                document.body.classList.add('cp-builder-fullscreen-open');
                setFullscreenButtonState(card, true);
                setActiveBlock(card);
                window.setTimeout(function () {
                    var textarea = card.querySelector('[data-cp-paragraph-editor], [data-cp-heading-editor]');
                    var editor = getTinyMceEditor(textarea);
                    if (editor) { editor.focus(); }
                }, 60);
            } else {
                exitBlockFullscreen(card);
            }
        }

        document.addEventListener('keydown', function (event) {
            if ('Escape' !== event.key) { return; }
            var card = blockList.querySelector('.cp-block-fullscreen');
            if (card) {
                event.preventDefault();
                exitBlockFullscreen(card);
            }
        });

        function showModalAfterHidden(currentElement, currentModal, nextModal) {
            if (!nextModal) {
                return;
            }
            if (!currentElement || !currentModal) {
                nextModal.show();
                return;
            }

            currentElement.addEventListener('hidden.bs.modal', function handler() {
                currentElement.removeEventListener('hidden.bs.modal', handler);
                nextModal.show();
            });
            currentModal.hide();
        }

        function prepareAuthorConfirmation(author) {
            if (!authorConfirmElement || !authorConfirmModal) {
                return;
            }

            pendingAuthor = author;
            authorConfirmElement.querySelector('[data-cp-author-confirm-type]').textContent = 'custom' === author.mode ? 'Typed author' : 'WordPress account';
            authorConfirmElement.querySelector('[data-cp-author-confirm-name]').textContent = author.name;
            var roleRow = authorConfirmElement.querySelector('[data-cp-author-confirm-role-row]');
            var roleValue = authorConfirmElement.querySelector('[data-cp-author-confirm-role]');
            var warning = authorConfirmElement.querySelector('[data-cp-author-confirm-warning]');
            roleRow.hidden = 'account' !== author.mode;
            roleValue.textContent = author.role || '';
            warning.hidden = 'custom' !== author.mode;
        }

        if (authorEditButton && authorChoiceModal) {
            authorEditButton.addEventListener('click', function () {
                authorChoiceModal.show();
            });
        }

        if (authorChoiceElement) {
            var openAccounts = authorChoiceElement.querySelector('[data-cp-author-open-accounts]');
            var openCustom = authorChoiceElement.querySelector('[data-cp-author-open-custom]');

            if (openAccounts) {
                openAccounts.addEventListener('click', function () {
                    showModalAfterHidden(authorChoiceElement, authorChoiceModal, authorAccountsModal);
                });
            }

            if (openCustom) {
                openCustom.addEventListener('click', function () {
                    var customInput = authorCustomElement ? authorCustomElement.querySelector('[data-cp-author-custom-input]') : null;
                    if (customInput) {
                        customInput.value = 'custom' === authorMode.value ? authorCustom.value : authorDisplay.value;
                        customInput.classList.remove('is-invalid');
                    }
                    showModalAfterHidden(authorChoiceElement, authorChoiceModal, authorCustomModal);
                });
            }
        }

        if (authorAccountsElement) {
            authorAccountsElement.addEventListener('click', function (event) {
                var selectButton = event.target.closest('[data-cp-author-select-account]');
                if (!selectButton) {
                    return;
                }

                prepareAuthorConfirmation({
                    mode: 'account',
                    userId: selectButton.getAttribute('data-author-id') || '',
                    name: selectButton.getAttribute('data-author-name') || '',
                    role: selectButton.getAttribute('data-author-role') || ''
                });
                showModalAfterHidden(authorAccountsElement, authorAccountsModal, authorConfirmModal);
            });
        }

        if (authorCustomElement) {
            var customInput = authorCustomElement.querySelector('[data-cp-author-custom-input]');
            var customReview = authorCustomElement.querySelector('[data-cp-author-review-custom]');

            if (customInput) {
                customInput.addEventListener('input', function () {
                    customInput.classList.remove('is-invalid');
                });
            }

            if (customReview) {
                customReview.addEventListener('click', function () {
                    var customName = customInput ? customInput.value.trim().replace(/\s+/g, ' ') : '';
                    if (!customName) {
                        if (customInput) {
                            customInput.classList.add('is-invalid');
                            customInput.focus();
                        }
                        return;
                    }

                    prepareAuthorConfirmation({
                        mode: 'custom',
                        userId: authorUserId ? authorUserId.value : '',
                        name: customName,
                        role: ''
                    });
                    showModalAfterHidden(authorCustomElement, authorCustomModal, authorConfirmModal);
                });
            }
        }

        if (authorConfirmElement) {
            var applyAuthor = authorConfirmElement.querySelector('[data-cp-author-confirm-apply]');
            if (applyAuthor) {
                applyAuthor.addEventListener('click', function () {
                    if (!pendingAuthor || !authorDisplay || !authorMode || !authorUserId || !authorCustom) {
                        return;
                    }

                    authorMode.value = pendingAuthor.mode;
                    authorDisplay.value = pendingAuthor.name;
                    if ('account' === pendingAuthor.mode) {
                        authorUserId.value = pendingAuthor.userId;
                        authorCustom.value = '';
                        if (authorHelp) {
                            authorHelp.textContent = 'Linked to a WordPress account';
                        }
                    } else {
                        authorCustom.value = pendingAuthor.name;
                        if (authorHelp) {
                            authorHelp.textContent = 'Typed author name';
                        }
                    }

                    pendingAuthor = null;
                    if (authorConfirmModal) {
                        authorConfirmModal.hide();
                    }
                });
            }
        }

        if (homepageFeatureInput) {
            homepageFeatureInput.addEventListener('change', function () {
                homepageFeatureInput.setAttribute('aria-checked', homepageFeatureInput.checked ? 'true' : 'false');
            });
        }

        if (heroField) {
            heroField.addEventListener('change', function (event) {
                if (event.target.matches('[data-cp-hero-source]')) {
                    setHeroSource(event.target.value);
                }
                if (event.target.matches('[data-cp-hero-url]')) {
                    var value = event.target.value.trim();
                    setHeroPreview(heroField.querySelector('[data-cp-hero-url-preview]'), /^https?:\/\//i.test(value) ? value : '');
                }
            });
            heroField.querySelector('[data-cp-hero-select]').addEventListener('click', openHeroMediaLibrary);
            heroField.querySelectorAll('.cp-hero-preview img').forEach(function (image) {
                image.addEventListener('error', function () {
                    image.parentNode.hidden = true;
                    image.remove();
                });
            });
            setHeroSource(heroSource());
        }

        blockList.addEventListener('focusin', function (event) {
            var card = event.target.closest('[data-cp-block]');
            if (card) {
                setActiveBlock(card);
            }
        });

        blockList.addEventListener('focusout', function (event) {
            var card = event.target.closest('[data-cp-block]');
            if (!card) {
                return;
            }
            window.setTimeout(function () {
                if (!card.contains(document.activeElement) && !card.matches(':hover')) {
                    card.classList.remove('cp-block-active');
                }
            }, 120);
        });

        blockList.addEventListener('pointerdown', function (event) {
            var card = event.target.closest('[data-cp-block]');
            if (card) {
                setActiveBlock(card);
            }
        });

        blockList.addEventListener('change', function (event) {
            if (event.target.matches('[data-cp-image-source]')) {
                setImageSource(event.target.closest('[data-cp-block]'), event.target.value);
            }
        });

        blockList.addEventListener('click', function (event) {
            var card = event.target.closest('[data-cp-block]');
            if (!card) {
                return;
            }
            if (event.target.closest('[data-cp-fullscreen]')) {
                toggleBlockFullscreen(card);
                return;
            }
            if (event.target.closest('[data-cp-remove]')) {
                exitBlockFullscreen(card);
                cpRemoveParagraphEditor(card);
                card.remove();
                refreshBlocks();
                return;
            }
            if (event.target.closest('[data-cp-duplicate]')) {
                var duplicate = createCard(card.getAttribute('data-block-type'), serializeCard(card));
                card.insertAdjacentElement('afterend', duplicate);
                refreshBlocks();
                if ('paragraph' === duplicate.getAttribute('data-block-type') || 'heading' === duplicate.getAttribute('data-block-type')) {
                    cpInitializeParagraphEditor(duplicate);
                }
                return;
            }
            if (event.target.closest('[data-cp-move-up]') && card.previousElementSibling) {
                moveCard(card, 'up');
                return;
            }
            if (event.target.closest('[data-cp-move-down]') && card.nextElementSibling) {
                moveCard(card, 'down');
                return;
            }
            if (event.target.closest('[data-cp-select-image]')) {
                openMediaLibrary(card);
                return;
            }

            var tableEditor = event.target.closest('[data-cp-table-editor]');
            if (!tableEditor) {
                return;
            }
            if (event.target.closest('[data-cp-table-add-row]')) {
                addTableRow(tableEditor);
            } else if (event.target.closest('[data-cp-table-remove-row]')) {
                removeTableRow(tableEditor);
            } else if (event.target.closest('[data-cp-table-add-column]')) {
                addTableColumn(tableEditor);
            } else if (event.target.closest('[data-cp-table-remove-column]')) {
                removeTableColumn(tableEditor);
            }
        });

        function moveCard(card, direction) {
            var hasRichEditor = 'paragraph' === card.getAttribute('data-block-type') || 'heading' === card.getAttribute('data-block-type');
            exitBlockFullscreen(card);
            if (hasRichEditor) {
                cpSyncParagraphEditors(card);
                cpRemoveParagraphEditor(card);
            }
            card.classList.add('up' === direction ? 'cp-block-moving-up' : 'cp-block-moving-down');
            window.setTimeout(function () {
                if ('up' === direction) {
                    blockList.insertBefore(card, card.previousElementSibling);
                } else {
                    blockList.insertBefore(card.nextElementSibling, card);
                }
                card.classList.remove('cp-block-moving-up', 'cp-block-moving-down');
                card.classList.add('cp-block-just-moved');
                window.setTimeout(function () { card.classList.remove('cp-block-just-moved'); }, 500);
                refreshBlocks();
                if (hasRichEditor) {
                    cpInitializeParagraphEditor(card);
                }
            }, 170);
        }

        function openMediaLibrary(card) {
            if (!window.wp || !window.wp.media) {
                showError('The Media Library is unavailable on this page.');
                return;
            }
            var frame = window.wp.media({ title: 'Select Article Image', button: { text: 'Use this image' }, library: { type: 'image' }, multiple: false });
            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                card.querySelector('[data-cp-field="attachment_id"]').value = String(attachment.id);
                var alt = card.querySelector('[data-cp-field="alt"]');
                if (alt && !alt.value && attachment.alt) {
                    alt.value = attachment.alt;
                }
                var preview = card.querySelector('[data-cp-image-preview]');
                preview.textContent = '';
                var image = element('img');
                image.src = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
                image.alt = '';
                preview.appendChild(image);
                preview.hidden = false;
            });
            frame.open();
        }

        function blockSummary(block) {
            var label = labels[block.type] || 'Content Block';
            var preview = '';
            if ('heading' === block.type) {
                preview = stripHtml(block.content);
            } else if ('paragraph' === block.type) {
                preview = stripHtml(block.content);
            } else if ('table' === block.type) {
                preview = String(block.caption || '').trim();
            } else if ('image' === block.type) {
                preview = String(block.caption || block.alt || '').trim();
            } else if ('video' === block.type) {
                preview = String(block.caption || '').trim();
            }
            if (preview.length > 80) {
                preview = preview.slice(0, 77) + '...';
            }
            return preview ? label + ': ' + preview : label;
        }

        form.addEventListener('submit', function (event) {
            if (confirmed) {
                cpSyncParagraphEditors(blockList);
                return;
            }
            event.preventDefault();
            cpSyncParagraphEditors(blockList);
            if (!form.reportValidity()) {
                return;
            }
            var blocks = collectBlocks();
            if (!validateBuilder(blocks)) {
                return;
            }
            hiddenBlocks.value = JSON.stringify(blocks);
            confirmElement.querySelector('[data-cp-summary-title]').textContent = form.querySelector('[name="title"]').value;
            confirmElement.querySelector('[data-cp-summary-status]').textContent = form.querySelector('[name="status"] option:checked').textContent;
            confirmElement.querySelector('[data-cp-summary-category]').textContent = form.querySelector('[name="category"] option:checked').textContent;
            confirmElement.querySelector('[data-cp-summary-author]').textContent = authorDisplay ? authorDisplay.value : '';
            confirmElement.querySelector('[data-cp-summary-count]').textContent = String(blocks.length);
            confirmElement.querySelector('[data-cp-summary-homepage-feature]').textContent = homepageFeatureInput && homepageFeatureInput.checked ? 'Yes' : 'No';
            if (heroWarning) {
                heroWarning.hidden = heroImageExists();
            }
            var summary = confirmElement.querySelector('[data-cp-summary-blocks]');
            summary.textContent = '';
            blocks.forEach(function (block) { summary.appendChild(element('li', '', blockSummary(block))); });
            if (confirmModal) {
                confirmModal.show();
            } else if (window.confirm('Confirm and save this article?')) {
                confirmed = true;
                form.requestSubmit();
            }
        });

        confirmElement.querySelector('[data-cp-confirm-save]').addEventListener('click', function () {
            cpSyncParagraphEditors(blockList);
            var blocks = collectBlocks();
            if (!validateBuilder(blocks)) {
                if (confirmModal) {
                    confirmModal.hide();
                }
                return;
            }
            hiddenBlocks.value = JSON.stringify(blocks);
            confirmed = true;
            form.requestSubmit();
        });

        blockList.querySelectorAll('[data-cp-block][data-block-type="image"]').forEach(function (card) {
            setImageSource(card, fieldValue(card, 'source'));
        });
        blockList.querySelectorAll('[data-cp-table-editor]').forEach(refreshTableEditor);
        refreshBlocks();
        cpInitializeParagraphEditor(blockList);
    });
}());
