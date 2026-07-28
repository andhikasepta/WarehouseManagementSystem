<?php
// Shared Topbar Component
// Accepts $activePage ('inbound', 'warehouse', 'outbound', 'master_data', 'wms_select', 'user_management')
// Accepts optional $hidePeriodSelector (boolean)
// Accepts optional $hideNavLinks (boolean)
// Accepts optional $hideLoginButton (boolean)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($activePage)) {
    $activePage = '';
}

$navUser = [
    'name' => $_SESSION['name'] ?? 'Guest User',
    'role' => $_SESSION['role'] ?? 'guest',
    'is_logged_in' => isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])
];

$allowedNavModules = $_SESSION['allowed_modules'] ?? [];
$isSuperAdminNav = ($navUser['role'] === 'superadmin');
$canAccessMasterData = $navUser['is_logged_in'] && ($isSuperAdminNav || in_array('master_data', $allowedNavModules));

$shouldHideNavLinks = isset($hideNavLinks) && $hideNavLinks;
$shouldHideNavbarUl = isset($hideNavbarUl) && $hideNavbarUl;
?>
<nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 fixed-top shadow" style="z-index: 1020;">
    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
        <i class="fa fa-bars"></i>
    </button>
    <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
        <div class="input-group">
            <?php if (!empty($navUser['is_logged_in'])): ?>
                <span style="cursor: default;">
                    <img src="img/Lintasarta.png" alt="Lintasarta Logo" width="150px">
                </span>
            <?php else: ?>
                <a href="wms_select.php" title="Kembali ke Dashboard WMS">
                    <img src="img/Lintasarta.png" alt="Lintasarta Logo" width="150px">
                </a>
            <?php endif; ?>
        </div>
    </form>
    <?php if (!$shouldHideNavbarUl): ?>
    <ul class="navbar-nav ml-auto align-items-center text-nowrap" style="white-space: nowrap;">
        <?php if (!isset($hidePeriodSelector) || !$hidePeriodSelector): ?>
        <li class="nav-item dropdown no-arrow mx-1">
            <a class="nav-link dropdown-toggle text-nowrap" href="#" id="periodDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small text-nowrap" id="selected-period-text"
                    style="font-weight: bold;">PILIH DATA</span> <i
                    class="fas fa-chevron-down fa-sm fa-fw text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in p-3"
                aria-labelledby="periodDropdown" id="period-dropdown-menu"
                style="min-width: 280px;">
                <h6 class="dropdown-header px-0 pt-0 text-primary font-weight-bold">PILIH DATA</h6>
                <div class="form-group mb-2">
                    <label for="period-month-select" class="small font-weight-bold text-gray-600">Bulan</label>
                    <select class="form-control form-control-sm" id="period-month-select">
                        <option value="">-- Pilih Bulan --</option>
                    </select>
                </div>
                <div class="form-group mb-2">
                    <label for="period-year-select" class="small font-weight-bold text-gray-600">Tahun</label>
                    <select class="form-control form-control-sm" id="period-year-select">
                        <option value="">-- Pilih Tahun --</option>
                    </select>
                </div>
                <button class="btn btn-primary btn-sm btn-block" id="btn-load-period" disabled>
                    <i class="fas fa-check mr-1"></i>Tampilkan Data
                </button>
            </div>
        </li>
        <?php endif; ?>

        <?php if (!$shouldHideNavLinks): ?>
        <!-- Navigation Links -->
        <?php if ($navUser['role'] === 'superadmin'): ?>
            <li class="nav-item <?php echo ($activePage == 'user_management') ? 'active' : ''; ?>">
                <a class="nav-link text-nowrap" href="user_management.php">
                    <span class="mr-2 d-none d-lg-inline text-nowrap <?php echo ($activePage == 'user_management') ? 'text-primary font-weight-bold' : 'text-gray-600 font-weight-bold'; ?>">
                        <i class="fas fa-users-cog mr-1"></i> User Management
                    </span>
                </a>
            </li>
        <?php else: ?>
            <li class="nav-item <?php echo ($activePage == 'inbound') ? 'active' : ''; ?>">
                <a class="nav-link text-nowrap" href="inbound.php">
                    <span class="mr-2 d-none d-lg-inline text-nowrap <?php echo ($activePage == 'inbound') ? 'text-primary font-weight-bold' : 'text-gray-600 font-weight-bold'; ?>">
                        <i class="fas fa-box-open mr-1"></i> Inbound
                    </span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activePage == 'warehouse') ? 'active' : ''; ?>">
                <a class="nav-link text-nowrap" href="warehouse.php">
                    <span class="mr-2 d-none d-lg-inline text-nowrap <?php echo ($activePage == 'warehouse') ? 'text-primary font-weight-bold' : 'text-gray-600 font-weight-bold'; ?>">
                        <i class="fas fa-warehouse mr-1"></i> Storage
                    </span>
                </a>
            </li>
            <li class="nav-item <?php echo ($activePage == 'outbound') ? 'active' : ''; ?>">
                <a class="nav-link text-nowrap" href="outbound.php">
                    <span class="mr-2 d-none d-lg-inline text-nowrap <?php echo ($activePage == 'outbound') ? 'text-primary font-weight-bold' : 'text-gray-600 font-weight-bold'; ?>">
                        <i class="fas fa-truck-loading mr-1"></i> Outbound
                    </span>
                </a>
            </li>
            
            <?php if ($canAccessMasterData): ?>
            <li class="nav-item <?php echo ($activePage == 'master_data') ? 'active' : ''; ?>">
                <a class="nav-link text-nowrap" href="master_data.php">
                    <span class="mr-2 d-none d-lg-inline text-nowrap <?php echo ($activePage == 'master_data') ? 'text-primary font-weight-bold' : 'text-gray-600 font-weight-bold'; ?>">
                        <i class="fas fa-database mr-1"></i> Master Data
                    </span>
                </a>
            </li>
            <?php endif; ?>
        <?php endif; ?>
        <?php endif; ?>

        <div class="topbar-divider d-none d-sm-block"></div>

        <!-- User Information & Logout Dropdown -->
        <?php if ($navUser['is_logged_in']): ?>
        <li class="nav-item dropdown no-arrow text-nowrap">
            <a class="nav-link dropdown-toggle text-nowrap" href="#" id="userDropdown" role="button"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-700 small font-weight-bold text-nowrap">
                    <i class="fa fa-user mr-2 text-gray-400"></i><?php echo htmlspecialchars($navUser['name']); ?>
                </span>
                <i class="fas fa-chevron-down fa-sm fa-fw text-gray-400"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in p-2 text-nowrap"
                aria-labelledby="userDropdown" style="min-width: 180px;">
                <div class="dropdown-item-text small text-muted font-weight-bold border-bottom pb-2 mb-2 text-nowrap">
                    <?php 
                    $navRoleTitle = 'Admin Operasional';
                    if ($navUser['role'] === 'superadmin') $navRoleTitle = 'Super Admin';
                    elseif ($navUser['role'] === 'head_warehouse_admin') $navRoleTitle = 'Head-Warehouse Management';
                    elseif ($navUser['role'] === 'inbound_admin') $navRoleTitle = 'Inbound Administrator';
                    elseif ($navUser['role'] === 'outbound_admin') $navRoleTitle = 'Outbound Administrator';
                    elseif ($navUser['role'] === 'warehouse_admin') $navRoleTitle = 'Warehouse Administrator';
                    ?>
                    Role: <?php echo htmlspecialchars($navRoleTitle); ?>
                </div>
                <a class="dropdown-item text-danger font-weight-bold text-nowrap" href="login.php?action=logout">
                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-danger"></i> Logout
                </a>
            </div>
        </li>
        <?php elseif (!$shouldHideLoginButton): ?>
        <li class="nav-item text-nowrap">
            <a class="btn btn-primary btn-sm px-3 font-weight-bold text-nowrap" href="login.php">
                <i class="fas fa-sign-in-alt mr-1"></i> Login
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <?php endif; ?>
</nav>
