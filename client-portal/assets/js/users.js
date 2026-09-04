(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var input = document.querySelector('[data-cp-profile-photo-input]');
        var preview = document.querySelector('[data-cp-profile-photo-preview]');
        var filenameLabel = document.querySelector('[data-cp-profile-photo-filename]');

        if (!input || !preview || !filenameLabel) {
            return;
        }

        var defaultFilenameText = filenameLabel.textContent;
        var objectUrl = null;

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

            filenameLabel.textContent = file.name;

            if (window.URL && window.URL.createObjectURL) {
                objectUrl = URL.createObjectURL(file);
                preview.innerHTML = '<img class="cp-profile-photo-preview-image" src="' + objectUrl + '" alt="">';
            }
        });
    });
}());
