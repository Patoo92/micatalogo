<aside class="sidebar-admin d-none d-lg-flex flex-column">
    <div class="sidebar-brand">
        <a href="admin.php" class="d-flex align-items-center gap-2 text-white text-decoration-none">
            <iconify-icon icon="mdi:store" width="26" height="26"></iconify-icon>
            <span class="fw-bold"><?php echo htmlspecialchars($tienda_nombre ?? $_SESSION['tienda_nombre'] ?? 'Mi Tienda'); ?></span>
            <span class="badge ms-auto fw-normal" style="font-size:0.6rem;letter-spacing:0.05em;text-transform:uppercase;background:<?php echo $_SESSION['plan'] === 'business' ? '#f59e0b' : ($_SESSION['plan'] === 'pro' ? '#3b82f6' : '#64748b'); ?>;color:#000;"><?php echo htmlspecialchars($_SESSION['plan']); ?></span>
        </a>
    </div>

    <nav class="sidebar-nav flex-grow-1">
        <a href="dashboard.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <iconify-icon icon="mdi:chart-bar" width="18"></iconify-icon>
            Dashboard
        </a>
        <a href="admin.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>">
            <iconify-icon icon="mdi:package-variant-closed" width="18"></iconify-icon>
            Productos
        </a>
        <a href="pedidos.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'pedidos.php' ? 'active' : ''; ?>">
            <iconify-icon icon="mdi:format-list-bulleted" width="18"></iconify-icon>
            Pedidos
        </a>
        <a href="staff.php" class="sidebar-link <?php echo in_array(basename($_SERVER['PHP_SELF']), ['staff.php','staff-nuevo.php','staff-editar.php']) ? 'active' : ''; ?>">
            <iconify-icon icon="mdi:account-group" width="18"></iconify-icon>
            Staff
        </a>
        <a href="historial.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'historial.php' ? 'active' : ''; ?>">
            <iconify-icon icon="mdi:history" width="18"></iconify-icon>
            Historial
        </a>
        <a href="configuracion.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'configuracion.php' ? 'active' : ''; ?>">
            <iconify-icon icon="mdi:cog" width="18"></iconify-icon>
            Configuración
        </a>
        <a href="backup.php" class="sidebar-link <?php echo basename($_SERVER['PHP_SELF']) === 'backup.php' ? 'active' : ''; ?>">
            <iconify-icon icon="mdi:database-export" width="18"></iconify-icon>
            Respaldo
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="#" id="darkModeToggle" class="sidebar-link">
            <iconify-icon icon="mdi:weather-night" width="18"></iconify-icon>
            <span>Modo oscuro</span>
        </a>
        <a href="index.php?tienda=<?php echo htmlspecialchars($_SESSION['tienda_slug']); ?>" target="_blank" class="sidebar-link">
            <iconify-icon icon="mdi:eye" width="18"></iconify-icon>
            Ver tienda
        </a>
        <a href="logout.php" class="sidebar-link text-danger">
            <iconify-icon icon="mdi:logout" width="18"></iconify-icon>
            Salir
        </a>
    </div>
</aside>
