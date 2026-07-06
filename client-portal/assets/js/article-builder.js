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
        var confirmModal = window.bootstrap ? window.bootstrap.Modal.getOrCreateInstance(confirmElement) : null;
        var heroField = form.querySelector('[data-cp-hero-image]');
        var heroWarning = confirmElement.querySelector('[data-cp-hero-warning]');
        var confirmed = false;
        var nextId = Date.now();
        var labels = { heading: 'Heading', paragraph: 'Paragraph', image: 'Image', video: 'Video URL' };

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
            actions.appendChild(iconButton('bi-trash', 'data-cp-remove', 'Remove', true));
            head.appendChild(actions);
            card.appendChild(head);

            var body = element('div', 'cp-builder-block-body');
            if ('heading' === type) {
                var row = element('div', 'row g-3');
                var level = element('select', 'form-select');
                level.id = id + '-level';
                level.setAttribute('data-cp-field', 'level');
                for (var i = 1; i <= 6; i += 1) {
                    var option = element('option', '', 'H' + i);
                    option.value = String(i);
                    option.selected = Number(data.level || 2) === i;
                    level.appendChild(option);
                }
                row.appendChild(labelledField('Heading level', level, 'col-md-3'));
                var heading = inputField(id + '-content', 'content', 'text', data.content);
                heading.required = true;
                row.appendChild(labelledField('Heading text', heading, 'col-md-9'));
                body.appendChild(row);
            } else if ('paragraph' === type) {
                var paragraph = element('textarea', 'form-control');
                paragraph.id = id + '-content';
                paragraph.rows = 6;
                paragraph.required = true;
                paragraph.value = data.content || '';
                paragraph.setAttribute('data-cp-field', 'content');
                body.appendChild(labelledField('Paragraph content', paragraph));
            } else if ('image' === type) {
                buildImageFields(body, id, data);
            } else if ('video' === type) {
                var video = inputField(id + '-url', 'url', 'url', data.url);
                video.required = true;
                var videoWrap = labelledField('Video URL', video, 'mb-3');
                videoWrap.appendChild(element('div', 'form-text', 'Paste a video URL from YouTube, Vimeo, Facebook, TikTok, or another supported oEmbed provider.'));
                body.appendChild(videoWrap);
                body.appendChild(labelledField('Caption (optional)', inputField(id + '-caption', 'caption', 'text', data.caption)));
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

        function fieldValue(card, field) {
            var input = card.querySelector('[data-cp-field="' + field + '"]');
            return input ? input.value : '';
        }

        function serializeCard(card) {
            var type = card.getAttribute('data-block-type');
            if ('heading' === type) {
                return { type: type, level: Number(fieldValue(card, 'level') || 2), content: fieldValue(card, 'content') };
            }
            if ('paragraph' === type) {
                return { type: type, content: fieldValue(card, 'content') };
            }
            if ('image' === type) {
                var previewImage = card.querySelector('[data-cp-image-preview] img');
                return { type: type, source: fieldValue(card, 'source'), attachment_id: Number(fieldValue(card, 'attachment_id') || 0), url: fieldValue(card, 'url'), alt: fieldValue(card, 'alt'), caption: fieldValue(card, 'caption'), preview_url: previewImage ? previewImage.src : '' };
            }
            return { type: 'video', url: fieldValue(card, 'url'), caption: fieldValue(card, 'caption') };
        }

        function collectBlocks() {
            return Array.prototype.map.call(blockList.querySelectorAll('[data-cp-block]'), serializeCard);
        }

        function refreshBlocks() {
            var cards = blockList.querySelectorAll('[data-cp-block]');
            cards.forEach(function (card, index) {
                card.querySelector('.cp-block-number').textContent = String(index + 1);
                card.querySelector('[data-cp-move-up]').disabled = 0 === index;
                card.querySelector('[data-cp-move-down]').disabled = index === cards.length - 1;
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

        function validateBuilder(blocks) {
            if (!blocks.length) {
                showError('Add at least one content block before saving.');
                return false;
            }
            for (var i = 0; i < blocks.length; i += 1) {
                if ('image' === blocks[i].type && 'media' === blocks[i].source && !blocks[i].attachment_id) {
                    showError('Image block ' + (i + 1) + ' needs a Media Library image.');
                    return false;
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
                showError('The WordPress Media Library is unavailable on this page.');
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
                card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                window.setTimeout(function () {
                    var firstField = card.querySelector('input:not([type="hidden"]), textarea, select');
                    if (firstField) {
                        firstField.focus({ preventScroll: true });
                    }
                }, 450);
                window.setTimeout(function () { card.classList.remove('cp-block-new'); }, 1400);
            });
        });

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
            if (event.target.closest('[data-cp-remove]')) {
                card.remove();
                refreshBlocks();
                return;
            }
            if (event.target.closest('[data-cp-duplicate]')) {
                var duplicate = createCard(card.getAttribute('data-block-type'), serializeCard(card));
                card.insertAdjacentElement('afterend', duplicate);
                refreshBlocks();
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
            }
        });

        function moveCard(card, direction) {
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
            }, 170);
        }

        function openMediaLibrary(card) {
            if (!window.wp || !window.wp.media) {
                showError('The WordPress Media Library is unavailable on this page.');
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

        form.addEventListener('submit', function (event) {
            if (confirmed) {
                return;
            }
            event.preventDefault();
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
            confirmElement.querySelector('[data-cp-summary-count]').textContent = String(blocks.length);
            heroWarning.hidden = heroImageExists();
            var summary = confirmElement.querySelector('[data-cp-summary-blocks]');
            summary.textContent = '';
            blocks.forEach(function (block) { summary.appendChild(element('li', '', labels[block.type] || 'Content Block')); });
            if (confirmModal) {
                confirmModal.show();
            } else if (window.confirm('Confirm and save this article?')) {
                confirmed = true;
                form.requestSubmit();
            }
        });

        confirmElement.querySelector('[data-cp-confirm-save]').addEventListener('click', function () {
            hiddenBlocks.value = JSON.stringify(collectBlocks());
            confirmed = true;
            form.requestSubmit();
        });

        blockList.querySelectorAll('[data-cp-block][data-block-type="image"]').forEach(function (card) {
            setImageSource(card, fieldValue(card, 'source'));
        });
        refreshBlocks();
    });
}());
