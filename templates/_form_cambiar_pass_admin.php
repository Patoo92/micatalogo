<form method="POST">
    <?php echo csrf_field(); ?>
    <div class="mb-3">
        <label class="form-label">Nueva contraseña</label>
        <div class="input-group">
            <span class="input-group-text"><iconify-icon icon="mdi:lock" width="18"></iconify-icon></span>
            <input type="password" name="password" class="form-control" minlength="10" required autocomplete="new-password">
        </div>
        <div class="form-text">Mínimo 10 caracteres.</div>
    </div>
    <div class="mb-4">
        <label class="form-label">Confirmar nueva contraseña</label>
        <div class="input-group">
            <span class="input-group-text"><iconify-icon icon="mdi:lock-check" width="18"></iconify-icon></span>
            <input type="password" name="password_confirm" class="form-control" minlength="10" required autocomplete="new-password">
        </div>
    </div>
    <button type="submit" class="btn btn-success w-100 fw-bold py-2">
        <iconify-icon icon="mdi:content-save" width="18"></iconify-icon> Guardar Nueva Contraseña
    </button>
    <div class="text-center mt-3">
        <a href="super-admin.php" class="text-decoration-none small text-secondary">← Volver al panel</a>
    </div>
</form>
