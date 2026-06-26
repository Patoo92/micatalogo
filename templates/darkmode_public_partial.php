<script nonce="<?= $csp_nonce ?>">
(function() {
    var key = 'dm_public_<?php echo $tienda_id; ?>';
    var body = document.body;
    var toggle = document.getElementById('darkModeTogglePublic');
    var icon = document.getElementById('dmIcon');
    if (localStorage.getItem(key) === '1') {
        body.classList.add('public-dark-mode');
        if (icon) icon.innerHTML = '&#9728;';
    }
    if (toggle) {
        toggle.addEventListener('click', function() {
            body.classList.toggle('public-dark-mode');
            var isDark = body.classList.contains('public-dark-mode');
            localStorage.setItem(key, isDark ? '1' : '0');
            if (icon) icon.innerHTML = isDark ? '&#9728;' : '&#9790;';
        });
    }
})();
</script>
