<script nonce="<?= $csp_nonce ?>">
(function() {
    var html = document.documentElement;
    var toggle = document.getElementById('darkModeToggle');
    var icon = toggle && toggle.querySelector('iconify-icon');
    var span = toggle && toggle.querySelector('.nav-link-title');
    if (localStorage.getItem('dark_mode') === '1') {
        html.setAttribute('data-bs-theme', 'dark');
        if (icon) icon.setAttribute('icon', 'mdi:weather-sunny');
        if (span) span.textContent = 'Modo claro';
    }
    if (toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            var isDark = html.getAttribute('data-bs-theme') === 'dark';
            if (isDark) {
                html.removeAttribute('data-bs-theme');
            } else {
                html.setAttribute('data-bs-theme', 'dark');
            }
            localStorage.setItem('dark_mode', html.getAttribute('data-bs-theme') === 'dark' ? '1' : '0');
            if (icon) icon.setAttribute('icon', html.getAttribute('data-bs-theme') === 'dark' ? 'mdi:weather-sunny' : 'mdi:weather-night');
            if (span) span.textContent = html.getAttribute('data-bs-theme') === 'dark' ? 'Modo claro' : 'Modo oscuro';
        });
    }
})();
</script>
