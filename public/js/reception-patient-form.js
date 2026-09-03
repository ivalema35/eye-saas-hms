/**
 * Reception patient form — keyboard UX & shared helpers.
 */
(function (window) {
    'use strict';

    function initPlugins(flatpickrOptions) {
        if (typeof window.jQuery !== 'undefined' && typeof jQuery.fn.select2 !== 'undefined') {
            jQuery('.rpc-page .select2').select2({ width: '100%' });
        }

        if (typeof flatpickr !== 'undefined') {
            flatpickr('.rpc-page .flatpickr', flatpickrOptions || {
                dateFormat: 'Y-m-d',
                defaultDate: 'today',
                minDate: 'today',
            });
        }
    }

    function bindAutoOpenSelects(root) {
        var container = root || document;
        var selectors = container.querySelectorAll('.rpc-auto-open');

        selectors.forEach(function (el) {
            el.addEventListener('focus', function () {
                if (typeof jQuery !== 'undefined' && jQuery(el).data('select2')) {
                    jQuery(el).select2('open');
                } else if (el.tagName === 'SELECT') {
                    try {
                        if (typeof el.showPicker === 'function') {
                            el.showPicker();
                        }
                    } catch (e) { /* ignore */ }
                }
            });

            if (typeof jQuery !== 'undefined' && jQuery(el).data('select2')) {
                jQuery(el).on('select2:open', function () {
                    var field = el.closest('.rpc-field');
                    if (field) {
                        field.classList.add('rpc-field--focused');
                    }
                });
                jQuery(el).on('select2:close', function () {
                    var field = el.closest('.rpc-field');
                    if (field) {
                        field.classList.remove('rpc-field--focused');
                    }
                });
            }
        });
    }

    function bindSubmitFocus(formSelector, btnSelector) {
        var form = document.querySelector(formSelector);
        var btn = document.querySelector(btnSelector);
        if (!form || !btn) {
            return;
        }

        btn.addEventListener('focus', function () {
            btn.classList.add('rpc-submit--focused');
        });
        btn.addEventListener('blur', function () {
            btn.classList.remove('rpc-submit--focused');
        });
    }

    function bindOtCollapse(toggleSelector, panelSelector) {
        var toggle = document.querySelector(toggleSelector);
        var panel = document.querySelector(panelSelector);
        if (!toggle || !panel) {
            return;
        }

        toggle.addEventListener('click', function () {
            var open = panel.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            var icon = toggle.querySelector('.rpc-ot-chevron');
            if (icon) {
                icon.classList.toggle('bi-chevron-down', !open);
                icon.classList.toggle('bi-chevron-up', open);
            }
        });
    }

    function showToast(msg, isError) {
        var toast = document.getElementById('contactToast');
        var msgEl = document.getElementById('contactToastMsg');
        if (!toast || !msgEl) {
            return;
        }
        msgEl.textContent = msg;
        toast.style.background = isError ? '#C0392B' : '#1B4F72';
        toast.style.display = 'block';
        setTimeout(function () {
            toast.style.display = 'none';
        }, 3500);
    }

    window.ReceptionPatientForm = {
        initPlugins: initPlugins,
        bindAutoOpenSelects: bindAutoOpenSelects,
        bindSubmitFocus: bindSubmitFocus,
        bindOtCollapse: bindOtCollapse,
        showToast: showToast,
    };
})(window);
