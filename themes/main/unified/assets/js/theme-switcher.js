(function () {
    var STORAGE_KEY = 'pagify_unified_color_preset';
    var DEFAULT_PRESET = 'ocean';
    var ALLOWED_PRESETS = ['ocean', 'forest', 'sunset'];

    function normalizePreset(value) {
        return ALLOWED_PRESETS.indexOf(value) >= 0 ? value : DEFAULT_PRESET;
    }

    function applyPreset(preset) {
        var normalized = normalizePreset(preset);

        document.body.setAttribute('data-color-preset', normalized);

        var presetSelect = document.querySelector('[data-theme-preset-select]');

        if (presetSelect) {
            presetSelect.value = normalized;
        }

        var buttons = document.querySelectorAll('[data-theme-preset]');

        buttons.forEach(function (button) {
            var value = button.getAttribute('data-theme-preset');
            button.classList.toggle('is-active', value === normalized);
        });

        var statusNode = document.getElementById('theme-status');
        if (statusNode) {
            statusNode.textContent = 'Theme color preset: ' + normalized;
        }

        try {
            window.localStorage.setItem(STORAGE_KEY, normalized);
        } catch (error) {
            // Ignore storage failures and continue with in-memory preset.
        }
    }

    function bindPresetSwitch() {
        var presetSelect = document.querySelector('[data-theme-preset-select]');

        if (presetSelect) {
            presetSelect.addEventListener('change', function () {
                var value = presetSelect.value || DEFAULT_PRESET;
                applyPreset(value);
            });
        }

        var buttons = document.querySelectorAll('[data-theme-preset]');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var value = button.getAttribute('data-theme-preset') || DEFAULT_PRESET;
                applyPreset(value);
            });
        });
    }

    function bindMobileMenu() {
        var trigger = document.querySelector('[data-mobile-menu-toggle]');
        var menu = document.querySelector('[data-mobile-menu]');

        if (!trigger || !menu) {
            return;
        }

        trigger.addEventListener('click', function () {
            var expanded = trigger.getAttribute('aria-expanded') === 'true';
            var next = !expanded;

            trigger.setAttribute('aria-expanded', next ? 'true' : 'false');
            menu.classList.toggle('is-open', next);
            trigger.textContent = next ? 'Close' : 'Menu';
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var initial = DEFAULT_PRESET;

        try {
            initial = normalizePreset(window.localStorage.getItem(STORAGE_KEY));
        } catch (error) {
            initial = DEFAULT_PRESET;
        }

        bindPresetSwitch();
        bindMobileMenu();
        applyPreset(initial);
    });
})();
