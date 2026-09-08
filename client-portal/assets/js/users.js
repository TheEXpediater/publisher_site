(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.querySelector('[data-cp-profile-photo-input]');
        var preview = document.querySelector('[data-cp-profile-photo-preview]');
        var filenameLabel = document.querySelector('[data-cp-profile-photo-filename]');
        var removeField = document.querySelector('[data-cp-remove-profile-image]');
        var removeButton = document.querySelector('[data-cp-remove-profile-photo]');

        if (!input || !preview || !filenameLabel) {
            return;
        }

        var defaultFilenameText = filenameLabel.textContent;
        var objectUrl = null;
        var placeholderHtml = '<span class="cp-profile-photo-placeholder" aria-hidden="true"><i class="bi bi-person"></i></span>';

        input.addEventListener('change', function () {
            var file = input.files && input.files[0] ? input.files[0] : null;

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            if (!file) {
                filenameLabel.textContent = defaultFilenameText;
                return;
            }

            // Picking a new file cancels any pending Remove Photo request
            // from earlier in the same edit session.
            if (removeField) {
                removeField.value = '0';
            }

            filenameLabel.textContent = file.name;

            if (window.URL && window.URL.createObjectURL) {
                objectUrl = URL.createObjectURL(file);
                preview.innerHTML = '<img class="cp-profile-photo-preview-image" src="' + objectUrl + '" alt="">';
            }
        });

        if (removeButton && removeField) {
            removeButton.addEventListener('click', function () {
                removeField.value = '1';
                input.value = '';

                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                    objectUrl = null;
                }

                filenameLabel.textContent = defaultFilenameText;
                preview.innerHTML = placeholderHtml;
                removeButton.hidden = true;
            });
        }
    });
}());
