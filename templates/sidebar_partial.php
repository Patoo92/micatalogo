<aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false">
            <span class="navbar-toggler-icon"></span>
        </button>
        <h1 class="navbar-brand navbar-brand-autodark">
            <a href="admin.php" class="d-flex align-items-center gap-2 text-white text-decoration-none">
                <iconify-icon icon="mdi:store" width="26" height="26"></iconify-icon>
                <span class="fw-bold"><?php echo htmlspecialchars($tienda_nombre ?? $_SESSION['tienda_nombre'] ?? 'Mi Tienda'); ?></span>
                <span class="badge ms-auto fw-normal" style="font-size:0.6rem;letter-spacing:0.05em;text-transform:uppercase;background:<?php echo $_SESSION['plan'] === 'business' ? '#f59e0b' : ($_SESSION['plan'] === 'pro' ? '#3b82f6' : '#64748b'); ?>;color:#000;"><?php echo htmlspecialchars($_SESSION['plan']); ?></span>
            </a>
        </h1>
        <div class="collapse navbar-collapse" id="sidebar-menu">
            <div class="d-flex flex-column flex-fill">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? ' active' : ''; ?>" href="dashboard.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:chart-bar" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? ' active' : ''; ?>" href="admin.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:package-variant-closed" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Productos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'pedidos.php' ? ' active' : ''; ?>" href="pedidos.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:format-list-bulleted" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Pedidos</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo in_array(basename($_SERVER['PHP_SELF']), ['staff.php','staff-nuevo.php','staff-editar.php']) ? ' active' : ''; ?>" href="staff.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:account-group" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Staff</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'historial.php' ? ' active' : ''; ?>" href="historial.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:history" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Historial</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'configuracion.php' ? ' active' : ''; ?>" href="configuracion.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:cog" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Configuración</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) === 'backup.php' ? ' active' : ''; ?>" href="backup.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:database-export" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Respaldo</span>
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav mt-auto">
                <li class="nav-item">
                    <a class="nav-link" href="#" id="darkModeToggle">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:weather-night" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Modo oscuro</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="index.php?tienda=<?php echo htmlspecialchars($_SESSION['tienda_slug']); ?>" target="_blank">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:eye" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Ver tienda</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="logout.php">
                        <span class="nav-link-icon"><iconify-icon icon="mdi:logout" width="18"></iconify-icon></span>
                        <span class="nav-link-title">Salir</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>
