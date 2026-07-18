/**
 * International phone input helper.
 * Allows digits and a single leading "+". Max 15 digits (E.164).
 *
 * Usage: add data-intl-phone on inputs, or call HmsIntlPhone.bind(el) / .scan().
 */
(function () {
    'use strict';

    function sanitize(value) {
        value = String(value || '');
        var wantsPlus = value.length > 0 && value.charAt(0) === '+';
        // DB columns are varchar(15): with "+" allow 14 digits, else 15.
        var digits = value.replace(/\D/g, '').slice(0, wantsPlus ? 14 : 15);
        return wantsPlus ? '+' + digits : digits;
    }

    function bind(el) {
        if (!el || el.dataset.intlPhoneBound === '1') {
            return;
        }
        el.dataset.intlPhoneBound = '1';
        el.setAttribute('inputmode', 'tel');
        el.setAttribute('autocomplete', el.getAttribute('autocomplete') || 'tel');
        el.setAttribute('maxlength', '15');
        el.setAttribute('pattern', '\\+?[0-9]{7,15}');
        if (!el.getAttribute('title')) {
            el.setAttribute('title', '7–15 digits, optional leading +');
        }

        el.addEventListener('keydown', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey) {
                return;
            }
            var key = e.key;
            if (key.length !== 1) {
                return;
            }
            if (key >= '0' && key <= '9') {
                return;
            }
            if (key === '+' && el.selectionStart === 0 && el.value.indexOf('+') === -1) {
                return;
            }
            e.preventDefault();
        });

        el.addEventListener('input', function () {
            el.value = sanitize(el.value);
        });

        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text');
            el.value = sanitize(text);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // Normalize any pre-filled value once.
        if (el.value) {
            el.value = sanitize(el.value);
        }
    }

    function scan(root) {
        (root || document).querySelectorAll('[data-intl-phone]').forEach(bind);
    }

    document.addEventListener('DOMContentLoaded', function () {
        scan(document);
    });

    window.HmsIntlPhone = {
        sanitize: sanitize,
        bind: bind,
        scan: scan,
    };
})();
