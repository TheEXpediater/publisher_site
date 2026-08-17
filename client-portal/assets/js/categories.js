(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('cp-category-modal');

        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
            return;
        }

        var modal = window.bootstrap.Modal.getOrCreateInstance(modalElement);
        var form = modalElement.querySelector('[data-cp-category-form]');
        var categoryId = modalElement.querySelector('[data-cp-category-id]');
        var nameField = modalElement.querySelector('[data-cp-category-name]');
        var slugField = modalElement.querySelector('[data-cp-category-slug]');
        var descriptionField = modalElement.querySelector('[data-cp-category-description]');
        var title = modalElement.querySelector('[data-cp-category-modal-title]');
        var submitButton = modalElement.querySelector('[data-cp-category-submit]');

        if (!form || !categoryId || !nameField || !slugField || !descriptionField || !title || !submitButton) {
            return;
        }

        var addTitle = modalElement.getAttribute('data-cp-add-title') || 'Add Category';
        var editTitle = modalElement.getAttribute('data-cp-edit-title') || 'Edit Category';
        var addLabel = modalElement.getAttribute('data-cp-add-label') || 'Add Category';
        var editLabel = modalElement.getAttribute('data-cp-edit-label') || 'Update Category';

        function setAddMode() {
            form.reset();
            categoryId.value = '0';
            nameField.value = '';
            slugField.value = '';
            descriptionField.value = '';
            title.textContent = addTitle;
            submitButton.textContent = addLabel;
        }

        function setEditMode(category) {
            category = category && 'object' === typeof category ? category : {};
            categoryId.value = String(parseInt(category.id, 10) || 0);
            nameField.value = category.name || '';
            slugField.value = category.slug || '';
            descriptionField.value = category.description || '';
            title.textContent = editTitle;
            submitButton.textContent = editLabel;
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

        modalElement.addEventListener('show.bs.modal', function (event) {
            var trigger = event.relatedTarget;

            if (trigger && trigger.hasAttribute('data-cp-category-edit')) {
                setEditMode(categoryFromTrigger(trigger));
                return;
            }

            if (trigger && trigger.hasAttribute('data-cp-category-add')) {
                setAddMode();
            }
        });

        modalElement.addEventListener('shown.bs.modal', function () {
            nameField.focus();
        });

        modalElement.addEventListener('hidden.bs.modal', function () {
            if ('1' !== modalElement.getAttribute('data-cp-open')) {
                setAddMode();
            }
        });

        if ('1' === modalElement.getAttribute('data-cp-open')) {
            modal.show();
            modalElement.setAttribute('data-cp-open', '0');
        }
    });
}());
