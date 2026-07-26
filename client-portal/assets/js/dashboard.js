(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var userModal = document.getElementById('cp-user-modal');

        if (userModal && '1' === userModal.getAttribute('data-cp-open') && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(userModal).show();
        }

        initHomepageFeaturePicker();
    });

    function initHomepageFeaturePicker() {
        var config = window.cpDashboardFeaturePicker || {};
        var card = document.querySelector('[data-cp-feature-card]');
        var picker = document.querySelector('[data-cp-feature-picker]');
        var confirm = document.querySelector('[data-cp-feature-confirm]');
        var clearConfirm = document.querySelector('[data-cp-feature-clear-confirm]');

        if (!card || !picker || !config.canManage) {
            return;
        }

        var state = {
            page: 1,
            maxPages: 1,
            search: '',
            category: '0',
            selectedArticle: null,
            currentArticle: articleFromCard(card),
            currentRequest: 0,
            loading: false,
            saving: false,
            lastOpener: null,
            activeModal: null
        };

        var strings = config.strings || {};
        var searchInput = picker.querySelector('[data-cp-feature-search]');
        var clearSearch = picker.querySelector('[data-cp-feature-search-clear]');
        var categoryFilter = picker.querySelector('[data-cp-feature-category-filter]');
        var results = picker.querySelector('[data-cp-feature-results]');
        var loadingState = picker.querySelector('[data-cp-feature-loading]');
        var emptyState = picker.querySelector('[data-cp-feature-empty]');
        var errorState = picker.querySelector('[data-cp-feature-error]');
        var resultCount = picker.querySelector('[data-cp-feature-result-count]');
        var pageStatus = picker.querySelector('[data-cp-feature-page-status]');
        var prevButton = picker.querySelector('[data-cp-feature-prev]');
        var nextButton = picker.querySelector('[data-cp-feature-next]');
        var continueButton = picker.querySelector('[data-cp-feature-continue]');
        var openButton = card.querySelector('[data-cp-feature-picker-open]');
        var clearOpenButton = card.querySelector('[data-cp-feature-clear-open]');
        var notice = card.querySelector('[data-cp-feature-notice]');

        var debouncedSearch = debounce(function () {
            state.page = 1;
            loadArticles();
        }, 300);

        openButton.addEventListener('click', openPicker);
        if (clearOpenButton) {
            clearOpenButton.addEventListener('click', openClearConfirmation);
        }

        picker.querySelector('[data-cp-feature-picker-close]').addEventListener('click', closePicker);
        picker.querySelector('[data-cp-feature-picker-cancel]').addEventListener('click', closePicker);
        continueButton.addEventListener('click', openConfirmation);
        prevButton.addEventListener('click', function () {
            if (state.page > 1) {
                state.page -= 1;
                loadArticles();
            }
        });
        nextButton.addEventListener('click', function () {
            if (state.page < state.maxPages) {
                state.page += 1;
                loadArticles();
            }
        });

        searchInput.addEventListener('input', function () {
            state.search = searchInput.value.trim();
            clearSearch.hidden = '' === state.search;
            debouncedSearch();
        });
        searchInput.addEventListener('keydown', function (event) {
            if ('Enter' === event.key) {
                event.preventDefault();
                debouncedSearch.cancel();
                state.search = searchInput.value.trim();
                state.page = 1;
                loadArticles();
            }
        });
        clearSearch.addEventListener('click', function () {
            searchInput.value = '';
            state.search = '';
            state.page = 1;
            clearSearch.hidden = true;
            loadArticles();
            searchInput.focus();
        });
        categoryFilter.addEventListener('change', function () {
            state.category = categoryFilter.value || '0';
            state.page = 1;
            loadArticles();
        });
        results.addEventListener('click', function (event) {
            var item = event.target.closest('[data-cp-feature-item]');
            if (!item) {
                return;
            }

            selectArticle(articleFromElement(item));
        });
        results.addEventListener('keydown', function (event) {
            if ('Enter' === event.key || ' ' === event.key) {
                var item = event.target.closest('[data-cp-feature-item]');
                if (item) {
                    event.preventDefault();
                    selectArticle(articleFromElement(item));
                }
            }
        });

        confirm.querySelector('[data-cp-feature-confirm-close]').addEventListener('click', closeConfirmation);
        confirm.querySelector('[data-cp-feature-confirm-back]').addEventListener('click', backToPicker);
        confirm.querySelector('[data-cp-feature-confirm-save]').addEventListener('click', saveSelection);

        clearConfirm.querySelector('[data-cp-feature-clear-close]').addEventListener('click', closeClearConfirmation);
        clearConfirm.querySelector('[data-cp-feature-clear-cancel]').addEventListener('click', closeClearConfirmation);
        clearConfirm.querySelector('[data-cp-feature-clear-confirm-action]').addEventListener('click', clearSelection);

        document.addEventListener('keydown', handleModalKeydown);

        function openPicker(event) {
            state.lastOpener = event ? event.currentTarget : openButton;
            state.page = 1;
            state.selectedArticle = null;
            continueButton.disabled = true;
            showModal(picker, searchInput);
            loadArticles();
        }

        function closePicker() {
            if (state.saving) {
                return;
            }

            hideModal(picker);
        }

        function loadArticles() {
            var requestId = state.currentRequest + 1;
            state.currentRequest = requestId;
            state.loading = true;
            setPickerLoading(true);

            ajax('cp_search_homepage_feature_articles', {
                search: state.search,
                category: state.category,
                paged: state.page,
                perPage: config.perPage || 20
            }).then(function (payload) {
                if (requestId !== state.currentRequest) {
                    return;
                }

                state.maxPages = Number(payload.maxPages || 1);
                renderResults(payload.articles || []);
                renderPagination(payload);
            }).catch(function (error) {
                if (requestId !== state.currentRequest) {
                    return;
                }

                showError(errorState, error.message || strings.error || 'Articles could not be loaded.');
            }).finally(function () {
                if (requestId !== state.currentRequest) {
                    return;
                }

                state.loading = false;
                setPickerLoading(false);
            });
        }

        function renderResults(articles) {
            results.textContent = '';
            emptyState.hidden = articles.length > 0;

            articles.forEach(function (article) {
                var item = document.createElement('button');
                item.type = 'button';
                item.className = 'enterprise-feature-picker-item';
                item.setAttribute('data-cp-feature-item', '');
                item.setAttribute('data-article', JSON.stringify(article));
                item.setAttribute('aria-pressed', 'false');

                if (state.selectedArticle && Number(state.selectedArticle.id) === Number(article.id)) {
                    item.classList.add('enterprise-feature-picker-item-selected');
                    item.setAttribute('aria-pressed', 'true');
                }

                var thumb = document.createElement('span');
                thumb.className = 'enterprise-feature-picker-thumb';
                if (article.thumbnail) {
                    var image = document.createElement('img');
                    image.src = article.thumbnail;
                    image.alt = '';
                    image.loading = 'lazy';
                    image.decoding = 'async';
                    thumb.appendChild(image);
                } else {
                    thumb.textContent = strings.noImage || 'No Image';
                }

                var copy = document.createElement('span');
                copy.className = 'enterprise-feature-picker-copy';
                var metaTop = document.createElement('span');
                metaTop.className = 'enterprise-feature-picker-meta-top';
                metaTop.textContent = article.category || '';
                if (article.isCurrent) {
                    var current = document.createElement('span');
                    current.className = 'enterprise-feature-picker-current';
                    current.textContent = strings.currentFeatured || 'Current Featured';
                    metaTop.appendChild(current);
                }
                var title = document.createElement('strong');
                title.textContent = article.title || '';
                var meta = document.createElement('span');
                meta.className = 'enterprise-feature-picker-meta';
                meta.textContent = [article.author, article.date].filter(Boolean).join(' - ');
                copy.appendChild(metaTop);
                copy.appendChild(title);
                copy.appendChild(meta);

                item.appendChild(thumb);
                item.appendChild(copy);
                results.appendChild(item);
            });
        }

        function renderPagination(payload) {
            var total = Number(payload.total || 0);
            var from = Number(payload.from || 0);
            var to = Number(payload.to || 0);
            state.maxPages = Number(payload.maxPages || 1);

            resultCount.textContent = total > 0
                ? format(strings.showing || 'Showing %1$s to %2$s of %3$s articles', from, to, total)
                : '';
            pageStatus.textContent = total > 0 ? String(state.page) + ' / ' + String(state.maxPages) : '';
            prevButton.disabled = state.page <= 1 || state.loading;
            nextButton.disabled = state.page >= state.maxPages || state.loading;
        }

        function selectArticle(article) {
            if (!article || !article.id) {
                return;
            }

            state.selectedArticle = article;
            continueButton.disabled = false;
            results.querySelectorAll('[data-cp-feature-item]').forEach(function (item) {
                var itemArticle = articleFromElement(item);
                var selected = Number(itemArticle.id) === Number(article.id);
                item.classList.toggle('enterprise-feature-picker-item-selected', selected);
                item.setAttribute('aria-pressed', selected ? 'true' : 'false');
            });
        }

        function openConfirmation() {
            if (!state.selectedArticle) {
                return;
            }

            renderConfirmation();
            picker.hidden = true;
            showModal(confirm, confirm.querySelector('[data-cp-feature-confirm-save]'), true);
        }

        function closeConfirmation() {
            if (state.saving) {
                return;
            }

            hideModal(confirm);
        }

        function backToPicker() {
            if (state.saving) {
                return;
            }

            confirm.hidden = true;
            showModal(picker, continueButton, true);
        }

        function renderConfirmation() {
            var article = state.selectedArticle;
            var cardTarget = confirm.querySelector('[data-cp-feature-confirm-card]');
            var replace = confirm.querySelector('[data-cp-feature-confirm-replace]');
            var message = confirm.querySelector('[data-cp-feature-confirm-message]');
            var error = confirm.querySelector('[data-cp-feature-confirm-error]');

            cardTarget.textContent = '';
            cardTarget.appendChild(articleSummary(article));
            error.hidden = true;
            error.textContent = '';

            if (state.currentArticle && Number(state.currentArticle.id) !== Number(article.id)) {
                replace.hidden = false;
                replace.textContent = format(strings.replaceCurrent || 'This will replace "%s" as the homepage featured article.', state.currentArticle.title);
            } else {
                replace.hidden = true;
                replace.textContent = '';
            }

            message.textContent = format(strings.featureConfirm || 'Feature "%s" on the homepage?', article.title);
        }

        function saveSelection() {
            if (!state.selectedArticle || state.saving) {
                return;
            }

            state.saving = true;
            setDialogSaving(confirm, true);
            ajax('cp_set_homepage_featured_article', {
                postId: state.selectedArticle.id
            }).then(function (payload) {
                state.currentArticle = payload.article;
                updateCard(payload.article);
                hideModal(confirm);
                showNotice(payload.message || format('"%s" is now featured on the homepage.', payload.article.title));
            }).catch(function (error) {
                showError(confirm.querySelector('[data-cp-feature-confirm-error]'), error.message || strings.error || 'The selection could not be saved.');
            }).finally(function () {
                state.saving = false;
                setDialogSaving(confirm, false);
            });
        }

        function openClearConfirmation(event) {
            if (!state.currentArticle || !state.currentArticle.id) {
                return;
            }

            state.lastOpener = event ? event.currentTarget : clearOpenButton;
            clearConfirm.querySelector('[data-cp-feature-clear-message]').textContent = format(strings.clearConfirm || 'Remove "%s" from the homepage featured position?', state.currentArticle.title);
            showError(clearConfirm.querySelector('[data-cp-feature-clear-error]'), '');
            showModal(clearConfirm, clearConfirm.querySelector('[data-cp-feature-clear-confirm-action]'));
        }

        function closeClearConfirmation() {
            if (state.saving) {
                return;
            }

            hideModal(clearConfirm);
        }

        function clearSelection() {
            if (state.saving) {
                return;
            }

            state.saving = true;
            setDialogSaving(clearConfirm, true);
            ajax('cp_clear_homepage_featured_article', {}).then(function (payload) {
                state.currentArticle = null;
                state.selectedArticle = null;
                updateCard(null);
                hideModal(clearConfirm);
                showNotice(payload.message || strings.fallback || 'Homepage Featured Article cleared.');
            }).catch(function (error) {
                showError(clearConfirm.querySelector('[data-cp-feature-clear-error]'), error.message || strings.error || 'The feature could not be cleared.');
            }).finally(function () {
                state.saving = false;
                setDialogSaving(clearConfirm, false);
            });
        }

        function setPickerLoading(loading) {
            loadingState.hidden = !loading;
            errorState.hidden = true;
            if (loading) {
                emptyState.hidden = true;
            }
            prevButton.disabled = loading || state.page <= 1;
            nextButton.disabled = loading || state.page >= state.maxPages;
        }

        function updateCard(article) {
            var title = card.querySelector('[data-cp-feature-title]');
            var category = card.querySelector('[data-cp-feature-category]');
            var meta = card.querySelector('[data-cp-feature-meta]');
            var links = card.querySelector('[data-cp-feature-links]');
            var thumb = card.querySelector('[data-cp-feature-thumb]');

            card.setAttribute('data-current-feature-id', article && article.id ? String(article.id) : '0');
            thumb.textContent = '';

            if (article && article.id) {
                if (article.thumbnail) {
                    var image = document.createElement('img');
                    image.src = article.thumbnail;
                    image.alt = '';
                    image.loading = 'lazy';
                    image.decoding = 'async';
                    thumb.appendChild(image);
                } else {
                    var noImage = document.createElement('span');
                    noImage.textContent = strings.noImage || 'No Image';
                    thumb.appendChild(noImage);
                }

                category.hidden = false;
                category.textContent = article.category || '';
                title.textContent = article.title || '';
                meta.textContent = '';
                var author = document.createElement('span');
                author.textContent = article.author || '';
                var separator = document.createElement('span');
                separator.setAttribute('aria-hidden', 'true');
                separator.textContent = ' - ';
                var date = document.createElement('time');
                date.dateTime = article.datetime || '';
                date.textContent = article.date || '';
                meta.appendChild(author);
                meta.appendChild(separator);
                meta.appendChild(date);

                links.hidden = false;
                links.textContent = '';
                links.appendChild(link(article.permalink, strings.viewArticle || 'View Article', true));
                if (article.editUrl) {
                    links.appendChild(link(article.editUrl, strings.editArticle || 'Edit Article', false));
                }
                openButton.textContent = strings.change || 'Change Featured Article';
                if (clearOpenButton) {
                    clearOpenButton.hidden = false;
                }
                return;
            }

            var placeholder = document.createElement('span');
            placeholder.textContent = strings.noImage || 'No Image';
            thumb.appendChild(placeholder);
            category.hidden = true;
            category.textContent = '';
            title.textContent = 'No manual homepage feature selected';
            meta.textContent = strings.fallback || 'The newest published Enterprise article will appear until another article is selected.';
            links.hidden = true;
            links.textContent = '';
            openButton.textContent = strings.choose || 'Choose Featured Article';
            if (clearOpenButton) {
                clearOpenButton.hidden = true;
            }
        }

        function showNotice(message) {
            if (!notice || !message) {
                return;
            }

            notice.textContent = message;
            notice.hidden = false;
        }

        function showModal(modal, focusTarget, keepOpener) {
            if (!keepOpener && document.activeElement) {
                state.lastOpener = document.activeElement;
            }

            modal.hidden = false;
            state.activeModal = modal;
            document.body.classList.add('enterprise-feature-picker-open');
            window.setTimeout(function () {
                (focusTarget || firstFocusable(modal) || modal).focus();
            }, 0);
        }

        function hideModal(modal) {
            modal.hidden = true;
            if (state.activeModal === modal) {
                state.activeModal = null;
            }
            if (picker.hidden && confirm.hidden && clearConfirm.hidden) {
                document.body.classList.remove('enterprise-feature-picker-open');
                if (state.lastOpener && 'function' === typeof state.lastOpener.focus) {
                    state.lastOpener.focus();
                }
            }
        }

        function handleModalKeydown(event) {
            if (!state.activeModal || state.activeModal.hidden) {
                return;
            }

            if ('Escape' === event.key) {
                event.preventDefault();
                if (state.saving) {
                    return;
                }
                if (state.activeModal === picker) {
                    closePicker();
                } else if (state.activeModal === confirm) {
                    closeConfirmation();
                } else {
                    closeClearConfirmation();
                }
                return;
            }

            if ('Tab' !== event.key) {
                return;
            }

            var focusable = focusableElements(state.activeModal);
            if (!focusable.length) {
                event.preventDefault();
                return;
            }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function setDialogSaving(modal, saving) {
            modal.querySelectorAll('button, input, select').forEach(function (control) {
                control.disabled = saving;
            });
        }

        function ajax(action, data) {
            var form = new FormData();
            form.append('action', action);
            form.append('nonce', config.nonce || '');
            Object.keys(data || {}).forEach(function (key) {
                form.append(key, data[key]);
            });

            return window.fetch(config.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                body: form
            }).then(function (response) {
                return response.json().then(function (json) {
                    if (!response.ok || !json.success) {
                        throw new Error(json && json.data && json.data.message ? json.data.message : 'Request failed.');
                    }
                    return json.data || {};
                });
            });
        }
    }

    function articleSummary(article) {
        var wrap = document.createElement('div');
        wrap.className = 'enterprise-feature-confirmation-summary';

        var thumb = document.createElement('span');
        thumb.className = 'enterprise-feature-picker-thumb';
        if (article.thumbnail) {
            var image = document.createElement('img');
            image.src = article.thumbnail;
            image.alt = '';
            thumb.appendChild(image);
        } else {
            thumb.textContent = 'No Image';
        }

        var copy = document.createElement('span');
        copy.className = 'enterprise-feature-picker-copy';
        var category = document.createElement('span');
        category.className = 'enterprise-feature-picker-meta-top';
        category.textContent = article.category || '';
        var title = document.createElement('strong');
        title.textContent = article.title || '';
        var meta = document.createElement('span');
        meta.className = 'enterprise-feature-picker-meta';
        meta.textContent = [article.author, article.date].filter(Boolean).join(' - ');

        copy.appendChild(category);
        copy.appendChild(title);
        copy.appendChild(meta);
        wrap.appendChild(thumb);
        wrap.appendChild(copy);

        return wrap;
    }

    function articleFromCard(card) {
        var id = Number(card.getAttribute('data-current-feature-id') || 0);
        if (!id) {
            return null;
        }

        return {
            id: id,
            title: text(card, '[data-cp-feature-title]'),
            category: text(card, '[data-cp-feature-category]'),
            author: text(card, '[data-cp-feature-author]'),
            date: text(card, '[data-cp-feature-date]')
        };
    }

    function articleFromElement(element) {
        try {
            return JSON.parse(element.getAttribute('data-article') || '{}');
        } catch (error) {
            return {};
        }
    }

    function text(root, selector) {
        var element = root.querySelector(selector);
        return element ? element.textContent.trim() : '';
    }

    function link(url, label, external) {
        var anchor = document.createElement('a');
        anchor.href = url || '#';
        anchor.textContent = label;
        if (external) {
            anchor.target = '_blank';
            anchor.rel = 'noopener';
        }
        return anchor;
    }

    function showError(element, message) {
        if (!element) {
            return;
        }

        element.textContent = message || '';
        element.hidden = '' === (message || '');
    }

    function focusableElements(root) {
        return Array.prototype.filter.call(
            root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'),
            function (element) {
                return !element.hasAttribute('hidden') && null !== element.offsetParent;
            }
        );
    }

    function firstFocusable(root) {
        return focusableElements(root)[0] || null;
    }

    function format(template) {
        var args = Array.prototype.slice.call(arguments, 1);
        return String(template).replace(/%(\d+\$)?s/g, function (match, index) {
            if (index) {
                return args[Number(index.replace('$', '')) - 1] || '';
            }
            return args.shift() || '';
        });
    }

    function debounce(callback, wait) {
        var timer = null;
        var debounced = function () {
            window.clearTimeout(timer);
            timer = window.setTimeout(callback, wait);
        };
        debounced.cancel = function () {
            window.clearTimeout(timer);
        };
        return debounced;
    }
}());
