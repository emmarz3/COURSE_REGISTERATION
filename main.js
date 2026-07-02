document.addEventListener('DOMContentLoaded', function () {
    const deleteForms = document.querySelectorAll('[data-confirm]');

    deleteForms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const message = form.getAttribute('data-confirm') || 'Please confirm this action.';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});
