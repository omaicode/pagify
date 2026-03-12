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
        var buttons = document.querySelectorAll('[data-theme-preset]');

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                var value = button.getAttribute('data-theme-preset') || DEFAULT_PRESET;
                applyPreset(value);
            });
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
        applyPreset(initial);
    });
})();
