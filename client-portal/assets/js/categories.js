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

        function refreshConfirmMessage() {
            if (!isEditMode) {
                submitButton.setAttribute('data-cp-confirm', addConfirm);
                return;
            }

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
