<?php
// user_management.php
require_once __DIR__ . '/auth.php';

checkModuleAccess(''); // Enforce login
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
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped" id="usersTable" width="100%" cellspacing="0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 50px;">ID</th>
                                            <th>Username</th>
                                            <th>Nama Lengkap</th>
                                            <th>Role</th>
                                            <th>Hak Akses Modul</th>
                                            <th>Tanggal Registrasi</th>
                                            <th style="width: 220px;" class="text-center text-nowrap">Aksi</th>
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
                                <div class="form-group mb-3">
                                    <label for="modal_role" class="small font-weight-bold text-gray-700">Role Sistem <span class="text-danger">*</span></label>
                                    <select class="form-control" id="modal_role" name="role">
                                        <option value="head_warehouse_admin">Head-Warehouse Management</option>
                                        <option value="inbound_admin">Inbound Administrator</option>
                                        <option value="outbound_admin">Outbound Administrator</option>
                                        <option value="warehouse_admin">Warehouse Administrator</option>
                                    </select>
                                </div>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold text-gray-700">Hak Akses Modul WMS <span class="text-danger">*</span></label>
                                    <div class="card p-3 bg-light border-0 rounded">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_inbound" value="inbound">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_inbound">
                                                <i class="fas fa-box-open text-primary mr-1"></i> Inbound Management
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_warehouse" value="warehouse">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_warehouse">
                                                <i class="fas fa-warehouse text-primary mr-1"></i> Warehouse Management
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_outbound" value="outbound">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_outbound">
                                                <i class="fas fa-truck-loading text-primary mr-1"></i> Outbound Management
                                            </label>
                                        </div>
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input module-checkbox" id="mod_master_data" value="master_data">
                                            <label class="custom-control-label font-weight-bold text-gray-800" for="mod_master_data">
                                                <i class="fas fa-database text-primary mr-1"></i> Master Data
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
            if (val === 'head_warehouse_admin') {
                // Head gets all modules (checked & restricted/locked)
                setModuleCheckbox('mod_inbound', true, true);
                setModuleCheckbox('mod_warehouse', true, true);
                setModuleCheckbox('mod_outbound', true, true);
                setModuleCheckbox('mod_master_data', true, true);
            } else if (val === 'inbound_admin') {
                // Inbound & Master Data checked; Warehouse & Outbound restricted (disabled)
                setModuleCheckbox('mod_inbound', true, false);
                setModuleCheckbox('mod_master_data', true, false);
                setModuleCheckbox('mod_warehouse', false, true);
                setModuleCheckbox('mod_outbound', false, true);
            } else if (val === 'outbound_admin') {
                // Outbound & Master Data checked; Inbound & Warehouse restricted (disabled)
                setModuleCheckbox('mod_inbound', false, true);
                setModuleCheckbox('mod_warehouse', false, true);
                setModuleCheckbox('mod_outbound', true, false);
                setModuleCheckbox('mod_master_data', true, false);
            } else if (val === 'warehouse_admin') {
                // Warehouse & Master Data checked; Inbound & Outbound restricted (disabled)
                setModuleCheckbox('mod_inbound', false, true);
                setModuleCheckbox('mod_warehouse', true, false);
                setModuleCheckbox('mod_outbound', false, true);
                setModuleCheckbox('mod_master_data', true, false);
            } else if (val === 'superadmin') {
                setModuleCheckbox('mod_inbound', true, true);
                setModuleCheckbox('mod_warehouse', true, true);
                setModuleCheckbox('mod_outbound', true, true);
                setModuleCheckbox('mod_master_data', true, true);
            } else {
                // Admin Operasional (custom) -> all enabled
                setModuleCheckbox('mod_inbound', true, false);
                setModuleCheckbox('mod_warehouse', true, false);
                setModuleCheckbox('mod_outbound', true, false);
                setModuleCheckbox('mod_master_data', true, false);
            }
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
                
                var roleSelect = document.getElementById('modal_role');
                roleSelect.disabled = false;
                roleSelect.value = "inbound_admin";
                applyRolePreset("inbound_admin");
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

            users.forEach(function (u) {
                var tr = document.createElement('tr');

                var roleBadge = '';
                if (u.role === 'superadmin') {
                    roleBadge = '<span class="badge badge-primary px-2 py-1"><i class="fas fa-user-shield mr-1"></i>Super Admin</span>';
                } else if (u.role === 'head_warehouse_admin') {
                    roleBadge = '<span class="badge text-white px-2 py-1" style="background-color: #6f42c1;"><i class="fas fa-user mr-1"></i>Head-Warehouse Management</span>';
                } else if (u.role === 'inbound_admin') {
                    roleBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-user mr-1"></i>Inbound Administrator</span>';
                } else if (u.role === 'outbound_admin') {
                    roleBadge = '<span class="badge badge-warning text-white px-2 py-1"><i class="fas fa-user mr-1"></i>Outbound Administrator</span>';
                } else if (u.role === 'warehouse_admin') {
                    roleBadge = '<span class="badge badge-info px-2 py-1"><i class="fas fa-user mr-1"></i>Warehouse Administrator</span>';
                } else {
                    roleBadge = '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-user mr-1"></i>Admin Operasional</span>';
                }

                var modulesBadges = '';
                var mods = Array.isArray(u.allowed_modules) ? u.allowed_modules : [];
                
                if (u.role === 'superadmin') {
                    modulesBadges = '<span class="badge badge-success mr-1 mb-1"><i class="fas fa-check-circle mr-1"></i>Semua Modul (Super Admin)</span>';
                } else if (mods.length === 0) {
                    modulesBadges = '<span class="badge badge-light text-muted border">Tidak Ada Modul</span>';
                } else {
                    mods.forEach(function (m) {
                        var badgeColor = 'badge-info';
                        if (m === 'inbound') badgeColor = 'badge-success';
                        if (m === 'warehouse') badgeColor = 'badge-primary';
                        if (m === 'outbound') badgeColor = 'badge-warning text-white';
                        if (m === 'master_data') badgeColor = 'badge-dark';

                        modulesBadges += '<span class="badge ' + badgeColor + ' mr-1 mb-1 text-uppercase">' + m.replace('_', ' ') + '</span>';
                    });
                }

                var btnResetPass = '<button class="btn btn-sm btn-warning ml-1" title="Reset Password" onclick="resetUserPassword(' + u.id + ', \'' + escapeQuotes(u.name) + '\')"><i class="fas fa-key"></i> Reset</button>';

                var btnDelete = '';
                if (u.id != <?php echo intval($user['id']); ?>) {
                    btnDelete = '<button class="btn btn-sm btn-danger ml-1" onclick="deleteUser(' + u.id + ', \'' + escapeQuotes(u.name) + '\')"><i class="fas fa-trash"></i></button>';
                } else {
                    btnDelete = '<button class="btn btn-sm btn-light text-muted ml-1" disabled title="Akun Anda"><i class="fas fa-lock"></i></button>';
                }

                tr.innerHTML = '<td>' + u.id + '</td>' +
                    '<td class="font-weight-bold">' + escapeHtml(u.username) + '</td>' +
                    '<td>' + escapeHtml(u.name) + '</td>' +
                    '<td>' + roleBadge + '</td>' +
                    '<td>' + modulesBadges + '</td>' +
                    '<td>' + u.created_at + '</td>' +
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
            
            var roleSelect = document.getElementById('modal_role');
            if (u.role === 'superadmin') {
                roleSelect.value = 'superadmin';
                roleSelect.disabled = true;
            } else {
                roleSelect.disabled = false;
                roleSelect.value = (u.role && u.role !== 'superadmin') ? u.role : 'inbound_admin';
            }

            document.getElementById('userModalLabel').innerHTML = '<i class="fas fa-user-edit mr-2"></i>Edit Admin / User';
            document.getElementById('modal_password').required = false;
            document.getElementById('pass-required-hint').classList.add('d-none');
            document.getElementById('pass-help').classList.remove('d-none');

            applyRolePreset(roleSelect.value);

            var mods = Array.isArray(u.allowed_modules) ? u.allowed_modules : [];
            if (roleSelect.value === 'admin') {
                document.getElementById('mod_inbound').checked = mods.includes('inbound');
                document.getElementById('mod_warehouse').checked = mods.includes('warehouse');
                document.getElementById('mod_outbound').checked = mods.includes('outbound');
                document.getElementById('mod_master_data').checked = mods.includes('master_data');
            }

            $('#userModal').modal('show');
        }

        function saveUser() {
            var userId = document.getElementById('user_id').value;
            var username = document.getElementById('modal_username').value;
            var name = document.getElementById('modal_name').value;
            var password = document.getElementById('modal_password').value;
            var role = document.getElementById('modal_role').value;

            var selectedModules = [];
            document.querySelectorAll('.module-checkbox:checked').forEach(cb => {
                selectedModules.push(cb.value);
            });

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
