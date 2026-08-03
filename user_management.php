<?php
// user_management.php
require_once __DIR__ . '/auth.php';

checkModuleAccess('user_management'); // Enforce login & superadmin access
$user = getCurrentUser();

if ($user['role'] !== 'superadmin') {
    renderAccessDeniedPage('User Management', $user);
    exit;
}

$pageTitle = 'User Management - Dashboard Warehouse';
include 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'user_management'; 
                $hidePeriodSelector = true;
                include 'components/navbar.php'; 
                ?>

                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800 font-weight-bold">User Management</h1>
                        <button class="btn btn-primary shadow-sm" id="btn-add-user" data-toggle="modal" data-target="#userModal">
                            <i class="fas fa-user-plus fa-sm text-white-50 mr-1"></i> Register Admin / User Baru
                        </button>
                    </div>

                    <!-- User List Table Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-list mr-1"></i> Daftar User &amp; Hak Akses
                            </h6>
                            <span class="badge badge-primary px-2 py-1">Super Admin Access</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 480px; overflow-y: auto; border: 1px solid #e3e6f0; border-radius: 6px;">
                                <table class="table table-bordered table-striped mb-0" id="usersTable" width="100%" cellspacing="0">
                                    <thead class="thead-light" style="position: sticky; top: 0; z-index: 2; background-color: #f8f9fc;">
                                        <tr>
                                            <th style="width: 50px; position: sticky; top: 0; background-color: #eaecf4; z-index: 3;">No.</th>
                                            <th style="position: sticky; top: 0; background-color: #eaecf4; z-index: 3;">Username</th>
                                            <th style="position: sticky; top: 0; background-color: #eaecf4; z-index: 3;" class="text-nowrap">Nama Lengkap</th>
                                            <th style="position: sticky; top: 0; background-color: #eaecf4; z-index: 3;">Role</th>
                                            <th style="position: sticky; top: 0; background-color: #eaecf4; z-index: 3;">Hak Akses Modul</th>
                                            <th style="position: sticky; top: 0; background-color: #eaecf4; z-index: 3;" class="text-nowrap">Tanggal Registrasi</th>
                                            <th style="width: 220px; position: sticky; top: 0; background-color: #eaecf4; z-index: 3;" class="text-center text-nowrap">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="users-table-body">
                                        <tr>
                                            <td colspan="7" class="text-center py-4">Memuat data user...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- User Modal (Add / Edit) -->
            <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title font-weight-bold" id="userModalLabel">
                                <i class="fas fa-user-plus mr-2"></i>Register Admin Baru
                            </h5>
                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">×</span>
                            </button>
                        </div>
                        <form id="userForm" autocomplete="off">
                            <input type="hidden" id="user_id" name="id" value="0">
                            <div class="modal-body p-4">
                                <div class="form-group mb-3">
                                    <label for="modal_username" class="small font-weight-bold text-gray-700">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="modal_username" name="username" placeholder="Masukkan Username" required autocomplete="off">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="modal_name" class="small font-weight-bold text-gray-700">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="modal_name" name="name" placeholder="Masukkan Nama Lengkap" required autocomplete="off">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="modal_password" class="small font-weight-bold text-gray-700">Password <span id="pass-required-hint" class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="modal_password" name="password" placeholder="Masukkan Password" autocomplete="new-password">
                                    <small id="pass-help" class="form-text text-muted d-none">Biarkan kosong jika tidak ingin mengubah password.</small>
                                </div>
                                <div class="form-group mb-3" id="modal_role_container">
                                    <label for="modal_role" class="small font-weight-bold text-gray-700">Role Sistem <span class="text-danger">*</span></label>
                                    <select class="form-control" id="modal_role" name="role">
                                        <option value="head_asset_warehouse_admin">Head-Asset And Warehouse Management</option>
                                        <option value="head_warehouse_admin">Head-Warehouse Management</option>
                                        <option value="inbound_admin">Inbound Administrator</option>
                                        <option value="outbound_admin">Outbound Administrator</option>
                                        <option value="warehouse_admin">Storage Administrator</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2">                                    <label class="small font-weight-bold text-gray-700">Hak Akses Modul WMS</label>
                                    <div class="card p-3 bg-light border-0 rounded">
                                        <!-- Overview Section -->
                                        <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-th-large mr-1"></i>Overview:</p>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_dashboard" value="dashboard">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_dashboard">
                                                <i class="fas fa-th-large text-primary mr-1"></i> Dashboard Overview <span class="badge badge-light border text-muted ml-1" style="font-size:0.65rem;">Head Role Only</span>
                                            </label>
                                        </div>

                                        <hr class="my-2">
                                        <!-- Main Menu Section -->
                                        <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-bars mr-1"></i>Main Menu:</p>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_inbound" value="inbound" checked disabled>
                                            <label class="custom-control-label font-weight-bold text-gray-600" for="mod_inbound">
                                                <i class="fas fa-box-open text-primary mr-1"></i> Inbound Management <span class="badge badge-light border text-muted ml-1" style="font-size:0.65rem;">Semua User</span>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_warehouse" value="warehouse" checked disabled>
                                            <label class="custom-control-label font-weight-bold text-gray-600" for="mod_warehouse">
                                                <i class="fas fa-warehouse text-primary mr-1"></i> Storage Management <span class="badge badge-light border text-muted ml-1" style="font-size:0.65rem;">Semua User</span>
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_outbound" value="outbound" checked disabled>
                                            <label class="custom-control-label font-weight-bold text-gray-600" for="mod_outbound">
                                                <i class="fas fa-truck-loading text-primary mr-1"></i> Outbound Management <span class="badge badge-light border text-muted ml-1" style="font-size:0.65rem;">Semua User</span>
                                            </label>
                                        </div>

                                        <hr class="my-2">
                                        <!-- Data Settings Section -->
                                        <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-cog mr-1"></i>Data Settings:</p>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_master_data" value="master_data">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_master_data">
                                                <i class="fas fa-database text-primary mr-1"></i> Master Data
                                            </label>
                                        </div>
                                        <!-- Master Data Sub-Checkboxes (visible when Master Data is checked) -->
                                        <div id="master-data-sub-options" class="ml-4 mb-2 pl-2" style="border-left: 2px solid #d1d3e2; display: none;">
                                            <p class="small text-muted mb-1" style="font-size:0.72rem;"><i class="fas fa-sitemap mr-1"></i>Akses Sub-Modul Master Data:</p>
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox" class="custom-control-input module-checkbox" id="mod_master_data_inbound" value="master_data_inbound">
                                                <label class="custom-control-label text-gray-700" for="mod_master_data_inbound" style="font-size:0.8rem;">
                                                    <i class="fas fa-box-open text-success mr-1"></i> Inbound Master Data
                                                </label>
                                            </div>
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox" class="custom-control-input module-checkbox" id="mod_master_data_storage" value="master_data_storage">
                                                <label class="custom-control-label text-gray-700" for="mod_master_data_storage" style="font-size:0.8rem;">
                                                    <i class="fas fa-warehouse text-primary mr-1"></i> Storage Master Data
                                                </label>
                                            </div>
                                            <div class="custom-control custom-checkbox mb-1">
                                                <input type="checkbox" class="custom-control-input module-checkbox" id="mod_master_data_outbound" value="master_data_outbound">
                                                <label class="custom-control-label text-gray-700" for="mod_master_data_outbound" style="font-size:0.8rem;">
                                                    <i class="fas fa-truck-loading text-warning mr-1"></i> Outbound Master Data
                                                </label>
                                            </div>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_inventory" value="inventory">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_inventory">
                                                <i class="fas fa-boxes text-primary mr-1"></i> Inventory
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_location" value="location">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_location">
                                                <i class="fas fa-map-marker-alt text-primary mr-1"></i> Location
                                            </label>
                                        </div>

                                        <hr class="my-2">
                                        <!-- Report Section -->
                                        <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-chart-bar mr-1"></i>Report:</p>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_reports" value="reports">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_reports">
                                                <i class="fas fa-file-alt text-primary mr-1"></i> Reports
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_analytics" value="analytics">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_analytics">
                                                <i class="fas fa-chart-line text-primary mr-1"></i> Analytics
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                                <button class="btn btn-primary" type="submit" id="btn-save-user">
                                    <i class="fas fa-save mr-1"></i> Simpan User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

<?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var currentUsersList = [];

        function setModuleCheckbox(id, isChecked, isDisabled) {
            var cb = document.getElementById(id);
            if (cb) {
                cb.checked = isChecked;
                cb.disabled = isDisabled;
            }
        }

        function applyRolePreset(val) {
            // Inbound, Warehouse, Outbound are ALWAYS accessible (checked + disabled)
            setModuleCheckbox('mod_inbound', true, true);
            setModuleCheckbox('mod_warehouse', true, true);
            setModuleCheckbox('mod_outbound', true, true);

            var isHead = (val && val.indexOf('head_') === 0) || val === 'head_warehouse_admin';

            if (isHead) {
                // Dashboard Overview is strictly for Head role (checked + disabled)
                setModuleCheckbox('mod_dashboard', true, true);
                setModuleCheckbox('mod_reports', true, false);
                setModuleCheckbox('mod_analytics', true, false);

                setModuleCheckbox('mod_master_data', true, false);
                setModuleCheckbox('mod_master_data_inbound', true, false);
                setModuleCheckbox('mod_master_data_storage', true, false);
                setModuleCheckbox('mod_master_data_outbound', true, false);
                setModuleCheckbox('mod_inventory', true, false);
                setModuleCheckbox('mod_location', true, false);
                toggleMasterDataSub(true);
            } else if (val === 'superadmin') {
                setModuleCheckbox('mod_dashboard', true, true);
                setModuleCheckbox('mod_reports', true, true);
                setModuleCheckbox('mod_analytics', true, true);

                setModuleCheckbox('mod_master_data', true, true);
                setModuleCheckbox('mod_master_data_inbound', true, true);
                setModuleCheckbox('mod_master_data_storage', true, true);
                setModuleCheckbox('mod_master_data_outbound', true, true);
                setModuleCheckbox('mod_inventory', true, true);
                setModuleCheckbox('mod_location', true, true);
                toggleMasterDataSub(true);
            } else if (val === 'inbound_admin' || val === 'warehouse_admin' || val === 'outbound_admin') {
                // Dashboard Overview is LOCKED & DISABLED (unchecked + disabled) for other administrator roles
                setModuleCheckbox('mod_dashboard', false, true);
                setModuleCheckbox('mod_reports', true, false);
                setModuleCheckbox('mod_analytics', true, false);

                setModuleCheckbox('mod_master_data', true, false);
                setModuleCheckbox('mod_inventory', true, false);
                setModuleCheckbox('mod_location', true, false);
                if (val === 'inbound_admin') {
                    setModuleCheckbox('mod_master_data_inbound', true, false);
                    setModuleCheckbox('mod_master_data_storage', false, false);
                    setModuleCheckbox('mod_master_data_outbound', false, false);
                } else if (val === 'warehouse_admin') {
                    setModuleCheckbox('mod_master_data_inbound', false, false);
                    setModuleCheckbox('mod_master_data_storage', true, false);
                    setModuleCheckbox('mod_master_data_outbound', false, false);
                } else if (val === 'outbound_admin') {
                    setModuleCheckbox('mod_master_data_inbound', false, false);
                    setModuleCheckbox('mod_master_data_storage', false, false);
                    setModuleCheckbox('mod_master_data_outbound', true, false);
                }
                toggleMasterDataSub(true);
            } else {
                setModuleCheckbox('mod_dashboard', false, true);
                setModuleCheckbox('mod_reports', false, false);
                setModuleCheckbox('mod_analytics', false, false);

                setModuleCheckbox('mod_master_data', false, false);
                setModuleCheckbox('mod_master_data_inbound', false, false);
                setModuleCheckbox('mod_master_data_storage', false, false);
                setModuleCheckbox('mod_master_data_outbound', false, false);
                setModuleCheckbox('mod_inventory', false, false);
                setModuleCheckbox('mod_location', false, false);
                toggleMasterDataSub(false);
            }
        }

        function toggleMasterDataSub(show) {
            var subEl = document.getElementById('master-data-sub-options');
            if (subEl) subEl.style.display = show ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadUsersList();

            // Role selection change event handler for automatic module check
            document.getElementById('modal_role').addEventListener('change', function () {
                applyRolePreset(this.value);
            });

            // Reset modal on opening for new user
            document.getElementById('btn-add-user').addEventListener('click', function () {
                document.getElementById('userForm').reset();
                document.getElementById('user_id').value = "0";
                document.getElementById('userModalLabel').innerHTML = '<i class="fas fa-user-plus mr-2"></i>Register Admin Baru';
                document.getElementById('modal_password').required = true;
                document.getElementById('pass-required-hint').classList.remove('d-none');
                document.getElementById('pass-help').classList.add('d-none');
                
                var roleContainer = document.getElementById('modal_role_container');
                if (roleContainer) roleContainer.style.display = 'block';

                var roleSelect = document.getElementById('modal_role');
                roleSelect.disabled = false;
                roleSelect.value = "inbound_admin";
                applyRolePreset("inbound_admin");
            });

            // Toggle Master Data sub-checkboxes visibility when parent is checked/unchecked
            document.getElementById('mod_master_data').addEventListener('change', function() {
                toggleMasterDataSub(this.checked);
            });

            // Form Submit (Create / Update)
            document.getElementById('userForm').addEventListener('submit', function (e) {
                e.preventDefault();
                saveUser();
            });
        });

        function loadUsersList() {
            fetch('api/manage_users.php')
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success' && res.data) {
                        currentUsersList = res.data;
                        renderUsersTable(res.data);
                    } else {
                        Swal.fire('Error', res.message || 'Gagal memuat daftar user.', 'error');
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire('Error', 'Gagal memuat data dari server.', 'error');
                });
        }

        function renderUsersTable(users) {
            var tbody = document.getElementById('users-table-body');
            tbody.innerHTML = '';

            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4">Belum ada data user tersimpan.</td></tr>';
                return;
            }

            users.forEach(function (u, index) {
                var tr = document.createElement('tr');
                var rowNum = index + 1;

                var roleBadge = '';
                if (u.role === 'superadmin') {
                    roleBadge = '<span class="badge badge-primary px-2 py-1"><i class="fas fa-user-shield mr-1"></i>Super Admin</span>';
                } else if (u.role === 'head_asset_warehouse_admin') {
                    roleBadge = '<span class="badge text-white px-2 py-1" style="background-color: #6f42c1;"><i class="fas fa-user mr-1"></i>Head-Asset And Warehouse Management</span>';
                } else if (u.role === 'head_warehouse_admin') {
                    roleBadge = '<span class="badge text-white px-2 py-1" style="background-color: #6f42c1;"><i class="fas fa-user mr-1"></i>Head-Warehouse Management</span>';
                } else if (u.role === 'inbound_admin') {
                    roleBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-user mr-1"></i>Inbound Administrator</span>';
                } else if (u.role === 'outbound_admin') {
                    roleBadge = '<span class="badge badge-warning text-white px-2 py-1"><i class="fas fa-user mr-1"></i>Outbound Administrator</span>';
                } else if (u.role === 'warehouse_admin') {
                    roleBadge = '<span class="badge badge-info px-2 py-1"><i class="fas fa-user mr-1"></i>Storage Administrator</span>';
                } else {
                    roleBadge = '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-user mr-1"></i>Admin Warehouse</span>';
                }

                var modulesBadges = '';
                var mods = Array.isArray(u.allowed_modules) ? u.allowed_modules : [];
                var isHeadUser = (u.role && u.role.indexOf('head_') === 0) || u.role === 'head_warehouse_admin';
                
                if (u.role === 'superadmin') {
                    modulesBadges = '<span class="badge badge-success mr-1 mb-1"><i class="fas fa-check-circle mr-1"></i>Semua Modul (Super Admin)</span>';
                } else {
                    // Show Dashboard Overview badge for Head role
                    if (isHeadUser || mods.includes('dashboard')) {
                        modulesBadges += '<span class="badge badge-secondary mr-1 mb-1"><i class="fas fa-th-large mr-1"></i>DASHBOARD</span>';
                    }
                    // All users have access to these main menu modules (always shown)
                    modulesBadges += '<span class="badge badge-success mr-1 mb-1"><i class="fas fa-box-open mr-1"></i>INBOUND</span>';
                    modulesBadges += '<span class="badge badge-primary mr-1 mb-1"><i class="fas fa-warehouse mr-1"></i>STORAGE</span>';
                    modulesBadges += '<span class="badge badge-warning text-white mr-1 mb-1"><i class="fas fa-truck-loading mr-1"></i>OUTBOUND</span>';
                    // Master Data only if explicitly granted
                    if (mods.includes('master_data')) {
                        var mdSubs = [];
                        if (mods.includes('master_data_inbound')) mdSubs.push('Inbound');
                        if (mods.includes('master_data_storage')) mdSubs.push('Storage');
                        if (mods.includes('master_data_outbound')) mdSubs.push('Outbound');
                        var subLabel = mdSubs.length > 0 ? ' (' + mdSubs.join(', ') + ')' : '';
                        modulesBadges += '<span class="badge badge-dark mr-1 mb-1"><i class="fas fa-database mr-1"></i>MASTER DATA' + subLabel + '</span>';
                    }
                    if (isHeadUser || mods.includes('inventory')) {
                        modulesBadges += '<span class="badge badge-primary mr-1 mb-1"><i class="fas fa-boxes mr-1"></i>INVENTORY</span>';
                    }
                    if (isHeadUser || mods.includes('location')) {
                        modulesBadges += '<span class="badge badge-info mr-1 mb-1"><i class="fas fa-map-marker-alt mr-1"></i>LOCATION</span>';
                    }
                    // Reports and Analytics
                    if (isHeadUser || mods.includes('reports')) {
                        modulesBadges += '<span class="badge badge-info mr-1 mb-1"><i class="fas fa-file-alt mr-1"></i>REPORTS</span>';
                    }
                    if (isHeadUser || mods.includes('analytics')) {
                        modulesBadges += '<span class="badge badge-info mr-1 mb-1"><i class="fas fa-chart-line mr-1"></i>ANALYTICS</span>';
                    }
                }

                var btnResetPass = '<button class="btn btn-sm btn-warning ml-1" title="Reset Password" onclick="resetUserPassword(' + u.id + ', \'' + escapeQuotes(u.name) + '\')"><i class="fas fa-key"></i> Reset</button>';

                var btnDelete = '';
                if (u.id != <?php echo intval($user['id']); ?>) {
                    btnDelete = '<button class="btn btn-sm btn-danger ml-1" onclick="deleteUser(' + u.id + ', \'' + escapeQuotes(u.name) + '\')"><i class="fas fa-trash"></i></button>';
                } else {
                    btnDelete = '<button class="btn btn-sm btn-light text-muted ml-1" disabled title="Akun Anda"><i class="fas fa-lock"></i></button>';
                }

                tr.innerHTML = '<td>' + rowNum + '</td>' +
                    '<td class="font-weight-bold">' + escapeHtml(u.username) + '</td>' +
                    '<td class="text-nowrap">' + escapeHtml(u.name) + '</td>' +
                    '<td>' + roleBadge + '</td>' +
                    '<td>' + modulesBadges + '</td>' +
                    '<td class="text-nowrap">' + u.created_at + '</td>' +
                    '<td class="text-center text-nowrap" style="white-space: nowrap;">' +
                        '<button class="btn btn-sm btn-info" onclick="editUser(' + u.id + ')"><i class="fas fa-edit"></i> Edit</button>' +
                        btnResetPass +
                        btnDelete +
                    '</td>';

                tbody.appendChild(tr);
            });
        }

        function editUser(userId) {
            var u = currentUsersList.find(item => item.id == userId);
            if (!u) return;

            document.getElementById('user_id').value = u.id;
            document.getElementById('modal_username').value = u.username;
            document.getElementById('modal_name').value = u.name;
            document.getElementById('modal_password').value = '';
            
            var roleContainer = document.getElementById('modal_role_container');
            var roleSelect = document.getElementById('modal_role');

            if (u.role === 'superadmin') {
                if (roleContainer) roleContainer.style.display = 'none';
                applyRolePreset('superadmin');
            } else {
                if (roleContainer) roleContainer.style.display = 'block';
                roleSelect.disabled = false;
                roleSelect.value = u.role ? u.role : 'inbound_admin';
                applyRolePreset(roleSelect.value);
            }

            document.getElementById('userModalLabel').innerHTML = '<i class="fas fa-user-edit mr-2"></i>Edit Admin / User';
            document.getElementById('modal_password').required = false;
            document.getElementById('pass-required-hint').classList.add('d-none');
            document.getElementById('pass-help').classList.remove('d-none');

            var mods = Array.isArray(u.allowed_modules) ? u.allowed_modules : [];
            // Apply role preset first to set disabled/checked defaults
            applyRolePreset(u.role || 'inbound_admin');

            var isHead = (u.role && u.role.indexOf('head_') === 0) || u.role === 'head_warehouse_admin';

            document.getElementById('mod_dashboard').checked = mods.includes('dashboard') || isHead;
            document.getElementById('mod_inventory').checked = mods.length === 0 || mods.includes('inventory') || isHead;
            document.getElementById('mod_location').checked = mods.length === 0 || mods.includes('location') || isHead;
            document.getElementById('mod_reports').checked = mods.length === 0 || mods.includes('reports') || isHead;
            document.getElementById('mod_analytics').checked = mods.length === 0 || mods.includes('analytics') || isHead;
            document.getElementById('mod_master_data').checked = mods.includes('master_data');
            document.getElementById('mod_master_data_inbound').checked = mods.includes('master_data_inbound');
            document.getElementById('mod_master_data_storage').checked = mods.includes('master_data_storage');
            document.getElementById('mod_master_data_outbound').checked = mods.includes('master_data_outbound');

            toggleMasterDataSub(document.getElementById('mod_master_data').checked);

            $('#userModal').modal('show');
        }

        function saveUser() {
            var userId = document.getElementById('user_id').value;
            var username = document.getElementById('modal_username').value;
            var name = document.getElementById('modal_name').value;
            var password = document.getElementById('modal_password').value;
            var roleSelect = document.getElementById('modal_role');
            var role = roleSelect.value;

            if (userId != "0") {
                var existingUser = currentUsersList.find(item => item.id == userId);
                if (existingUser && existingUser.role === 'superadmin') {
                    role = 'superadmin';
                }
            }

            var selectedModules = [];
            document.querySelectorAll('.module-checkbox:checked').forEach(cb => {
                selectedModules.push(cb.value);
            });

            if (role !== 'superadmin') {
                if (!selectedModules.includes('inbound')) selectedModules.push('inbound');
                if (!selectedModules.includes('warehouse')) selectedModules.push('warehouse');
                if (!selectedModules.includes('outbound')) selectedModules.push('outbound');
            }

            var payload = {
                action: 'save',
                id: userId,
                username: username,
                name: name,
                password: password,
                role: role,
                allowed_modules: selectedModules
            };

            fetch('api/manage_users.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    $('#userModal').modal('hide');
                    Swal.fire('Berhasil', res.message, 'success');
                    loadUsersList();
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire('Error', 'Gagal menyambung ke server.', 'error');
            });
        }

        function deleteUser(userId, userName) {
            Swal.fire({
                title: 'Hapus User?',
                text: 'Apakah Anda yakin ingin menghapus user ' + userName + '? User tidak akan dapat login lagi.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74a3b',
                cancelButtonColor: '#858796',
                confirmButtonText: 'Ya, Hapus!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/manage_users.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ action: 'delete', id: userId })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            Swal.fire('Terhapus!', res.message, 'success');
                            loadUsersList();
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Gagal menghapus user.', 'error');
                    });
                }
            });
        }

        function resetUserPassword(userId, userName) {
            Swal.fire({
                title: 'Reset Password User',
                text: 'Masukkan password baru untuk user ' + userName + ':',
                input: 'password',
                inputPlaceholder: 'Masukkan Password Baru',
                inputAttributes: {
                    autocapitalize: 'off',
                    autocorrect: 'off'
                },
                showCancelButton: true,
                confirmButtonText: 'Reset Password',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#f6c23e',
                cancelButtonColor: '#858796',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Password baru wajib diisi!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    fetch('api/manage_users.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'reset_password',
                            id: userId,
                            new_password: result.value
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success') {
                            Swal.fire('Berhasil!', res.message, 'success');
                            loadUsersList();
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        Swal.fire('Error', 'Gagal memproses reset password.', 'error');
                    });
                }
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        }

        function escapeQuotes(text) {
            if (!text) return '';
            return String(text).replace(/'/g, "\\'");
        }
    </script>

</body>
</html>
