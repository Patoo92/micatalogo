<div class="toast-container-custom" id="toastContainerGlobal">
    <div id="globalToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3500">
        <div class="d-flex">
            <div id="globalToastBody" class="toast-body d-flex align-items-center gap-2"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script nonce="<?= $csp_nonce ?>">
function mostrarToast(mensaje, tipo) {
    var el = document.getElementById('globalToast');
    el.className = 'toast align-items-center border-0 show text-bg-' + tipo;
    document.getElementById('globalToastBody').innerHTML = '<iconify-icon icon="mdi:' + (tipo === 'success' ? 'check-circle' : 'alert-circle') + '" width="20"></iconify-icon> ' + mensaje;
    setTimeout(function() { el.classList.remove('show'); }, 3500);
}

document.addEventListener('submit', function(e) {
    var btn = e.target.querySelector('button[type="submit"][data-loading]');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + btn.getAttribute('data-loading');
    }
});
</script>
