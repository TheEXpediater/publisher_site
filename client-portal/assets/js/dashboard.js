(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var userModal = document.getElementById('cp-user-modal');

        if (userModal && '1' === userModal.getAttribute('data-cp-open') && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(userModal).show();
        }
    });
}());
