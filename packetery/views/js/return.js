/**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

(function () {
    'use strict';

    document.querySelectorAll('form.packetery-return-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            form.classList.add('packetery-was-validated');
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
            }
        });
    });
})();
