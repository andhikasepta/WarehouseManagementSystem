<?php
// user_management.php
require_once __DIR__ . '/../../backend/auth.php';

checkModuleAccess('user_management'); // Enforce login & superadmin access
$user = getCurrentUser();

if ($user['role'] !== 'superadmin') {
    renderAccessDeniedPage('User Management', $user);
    exit;
}

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'user_management'; 
                $hidePeriodSelector = true;
                include FRONTEND_PATH . 'components/navbar.php'; 
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
                <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
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
                                <div class="row">
                                    <!-- Left Column: Basic Info -->
                                    <div class="col-md-5">
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
                                                <option value="repository_admin">Repository Administrator</option>
                                                <option value="outsourcing">Outsourcing</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Right Column: Module Access & Permissions -->
                                    <div class="col-md-7">
                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold text-gray-700">Hak Akses Modul WMS & Permissions</label>
                                            <div class="card p-3 bg-light border-0 rounded" style="max-height: 420px; overflow-y: auto;">

                                                <!-- Permission Legend -->
                                                <div class="d-flex align-items-center mb-2 flex-wrap" style="gap: 6px;">
                                                    <span class="badge badge-pill" style="background-color: #d1ecf1; color: #0c5460; font-size: 0.62rem; padding: 2px 6px;"><i class="fas fa-eye mr-1"></i>View</span>
                                                    <span class="badge badge-pill" style="background-color: #d4edda; color: #155724; font-size: 0.62rem; padding: 2px 6px;"><i class="fas fa-plus mr-1"></i>Add/Edit</span>
                                                    <span class="badge badge-pill" style="background-color: #f8d7da; color: #721c24; font-size: 0.62rem; padding: 2px 6px;"><i class="fas fa-trash mr-1"></i>Delete</span>
                                                </div>

                                                <?php
                                                $moduleRegistry = getModuleRegistry();
                                                $sections = [
                                                    'overview'      => ['label' => 'Overview', 'icon' => 'fas fa-th-large'],
                                                    'main_menu'     => ['label' => 'Main Menu', 'icon' => 'fas fa-bars'],
                                                    'data_settings' => ['label' => 'Data Settings', 'icon' => 'fas fa-cog'],
                                                    'report'        => ['label' => 'Report', 'icon' => 'fas fa-chart-bar'],
                                                    'repository'    => ['label' => 'Repository', 'icon' => 'fas fa-folder-open'],
                                                ];

                                                $groupedModules = [];
                                                foreach ($moduleRegistry as $mKey => $mConfig) {
                                                    $sec = $mConfig['section'] ?? 'other';
                                                    $groupedModules[$sec][$mKey] = $mConfig;
                                                }

                                                $sectionKeys = array_unique(array_merge(array_keys($sections), array_keys($groupedModules)));
                                                $firstSec = true;
                                                foreach ($sectionKeys as $secKey):
                                                    if (empty($groupedModules[$secKey])) continue;
                                                    $secInfo = $sections[$secKey] ?? ['label' => ucfirst($secKey), 'icon' => 'fas fa-layer-group'];
                                                    if (!$firstSec): ?>
                                                        <hr class="my-2">
                                                    <?php endif; $firstSec = false; ?>
                                                    <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;">
                                                        <i class="<?php echo htmlspecialchars($secInfo['icon']); ?> mr-1"></i><?php echo htmlspecialchars($secInfo['label']); ?>:
                                                    </p>
                                                    <?php foreach ($groupedModules[$secKey] as $modKey => $mod): ?>
                                                        <div class="module-perm-row mb-2" data-module="<?php echo htmlspecialchars($modKey); ?>">
                                                            <div class="custom-control custom-checkbox d-inline-block">
                                                                <input type="checkbox" class="custom-control-input module-checkbox" id="mod_<?php echo htmlspecialchars($modKey); ?>" value="<?php echo htmlspecialchars($modKey); ?>">
                                                                <label class="custom-control-label font-weight-bold text-gray-800" for="mod_<?php echo htmlspecialchars($modKey); ?>">
                                                                    <i class="<?php echo htmlspecialchars($mod['icon'] ?? 'fas fa-cube text-primary'); ?> mr-1"></i> <?php echo htmlspecialchars($mod['label']); ?>
                                                                </label>
                                                            </div>
                                                            <?php if (!empty($mod['permissions'])): ?>
                                                            <div class="perm-checkboxes ml-4 mt-1" id="perm_<?php echo htmlspecialchars($modKey); ?>" style="display:none;">
                                                                <div class="d-flex flex-wrap" style="gap: 8px;">
                                                                    <?php if (in_array('view', $mod['permissions'])): ?>
                                                                    <div class="custom-control custom-checkbox custom-control-inline">
                                                                        <input type="checkbox" class="custom-control-input perm-cb" id="perm_<?php echo htmlspecialchars($modKey); ?>_view" data-module="<?php echo htmlspecialchars($modKey); ?>" data-perm="view">
                                                                        <label class="custom-control-label text-muted" for="perm_<?php echo htmlspecialchars($modKey); ?>_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    <?php if (in_array('add', $mod['permissions'])): ?>
                                                                    <div class="custom-control custom-checkbox custom-control-inline">
                                                                        <input type="checkbox" class="custom-control-input perm-cb" id="perm_<?php echo htmlspecialchars($modKey); ?>_add" data-module="<?php echo htmlspecialchars($modKey); ?>" data-perm="add">
                                                                        <label class="custom-control-label text-muted" for="perm_<?php echo htmlspecialchars($modKey); ?>_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    <?php if (in_array('delete', $mod['permissions'])): ?>
                                                                    <div class="custom-control custom-checkbox custom-control-inline">
                                                                        <input type="checkbox" class="custom-control-input perm-cb" id="perm_<?php echo htmlspecialchars($modKey); ?>_delete" data-module="<?php echo htmlspecialchars($modKey); ?>" data-perm="delete">
                                                                        <label class="custom-control-label text-muted" for="perm_<?php echo htmlspecialchars($modKey); ?>_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>

                                                            <?php if (!empty($mod['children'])): ?>
                                                            <div id="<?php echo htmlspecialchars($modKey); ?>-sub-options" class="ml-4 mb-2 pl-2 sub-module-container" style="border-left: 2px solid #d1d3e2; display: none;">
                                                                <p class="small text-muted mb-1" style="font-size:0.72rem;"><i class="fas fa-sitemap mr-1"></i>Akses Sub-Modul <?php echo htmlspecialchars($mod['label']); ?>:</p>
                                                                <?php foreach ($mod['children'] as $childKey => $child): ?>
                                                                <div class="custom-control custom-checkbox mb-1">
                                                                    <input type="checkbox" class="custom-control-input module-checkbox" id="mod_<?php echo htmlspecialchars($childKey); ?>" value="<?php echo htmlspecialchars($childKey); ?>" data-parent="<?php echo htmlspecialchars($modKey); ?>">
                                                                    <label class="custom-control-label text-gray-700" for="mod_<?php echo htmlspecialchars($childKey); ?>" style="font-size:0.8rem;">
                                                                        <i class="<?php echo htmlspecialchars($child['icon'] ?? 'fas fa-circle'); ?> mr-1"></i> <?php echo htmlspecialchars($child['label']); ?>
                                                                    </label>
                                                                </div>
                                                                <?php if (!empty($child['permissions'])): ?>
                                                                <div class="perm-checkboxes ml-4 mb-2" id="perm_<?php echo htmlspecialchars($childKey); ?>" style="display:none;">
                                                                    <div class="d-flex flex-wrap" style="gap: 8px;">
                                                                        <?php if (in_array('view', $child['permissions'])): ?>
                                                                        <div class="custom-control custom-checkbox custom-control-inline">
                                                                            <input type="checkbox" class="custom-control-input perm-cb" id="perm_<?php echo htmlspecialchars($childKey); ?>_view" data-module="<?php echo htmlspecialchars($childKey); ?>" data-perm="view">
                                                                            <label class="custom-control-label text-muted" for="perm_<?php echo htmlspecialchars($childKey); ?>_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('add', $child['permissions'])): ?>
                                                                        <div class="custom-control custom-checkbox custom-control-inline">
                                                                            <input type="checkbox" class="custom-control-input perm-cb" id="perm_<?php echo htmlspecialchars($childKey); ?>_add" data-module="<?php echo htmlspecialchars($childKey); ?>" data-perm="add">
                                                                            <label class="custom-control-label text-muted" for="perm_<?php echo htmlspecialchars($childKey); ?>_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                        <?php if (in_array('delete', $child['permissions'])): ?>
                                                                        <div class="custom-control custom-checkbox custom-control-inline">
                                                                            <input type="checkbox" class="custom-control-input perm-cb" id="perm_<?php echo htmlspecialchars($childKey); ?>_delete" data-module="<?php echo htmlspecialchars($childKey); ?>" data-perm="delete">
                                                                            <label class="custom-control-label text-muted" for="perm_<?php echo htmlspecialchars($childKey); ?>_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endforeach; ?>
                                            </div>
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

<?php include FRONTEND_PATH . 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        var currentUsersList = [];
        var MODULE_REGISTRY = <?php echo json_encode(getModuleRegistry()); ?>;

        // Build list of all modules/submodules that have IDs and permission checkboxes
        var allModuleIds = [];
        var permModules = [];
        var parentModulesWithChildren = [];

        Object.keys(MODULE_REGISTRY).forEach(function(modKey) {
            allModuleIds.push('mod_' + modKey);
            var mod = MODULE_REGISTRY[modKey];
            if (mod.permissions && Array.isArray(mod.permissions) && mod.permissions.length > 0) {
                permModules.push(modKey);
            }
            if (mod.children && typeof mod.children === 'object') {
                parentModulesWithChildren.push(modKey);
                Object.keys(mod.children).forEach(function(childKey) {
                    allModuleIds.push('mod_' + childKey);
                    var child = mod.children[childKey];
                    if (child.permissions && Array.isArray(child.permissions) && child.permissions.length > 0) {
                        permModules.push(childKey);
                    }
                });
            }
        });

        function setModuleCheckbox(id, isChecked, isDisabled) {
            var cb = document.getElementById(id);
            if (cb) {
                cb.checked = isChecked;
                cb.disabled = isDisabled;
            }
        }

        /**
         * Show/hide permission checkboxes for a module based on whether the module checkbox is checked.
         */
        function togglePermCheckboxes(moduleKey, show) {
            var permEl = document.getElementById('perm_' + moduleKey);
            if (permEl) {
                permEl.style.display = show ? 'block' : 'none';
            }
        }

        /**
         * Set all permission checkboxes for a module.
         */
        function setPermCheckboxes(moduleKey, viewVal, addVal, deleteVal, allDisabled) {
            var viewCb = document.getElementById('perm_' + moduleKey + '_view');
            var addCb = document.getElementById('perm_' + moduleKey + '_add');
            var deleteCb = document.getElementById('perm_' + moduleKey + '_delete');

            if (viewCb) { viewCb.checked = viewVal; viewCb.disabled = allDisabled; }
            if (addCb) { addCb.checked = addVal; addCb.disabled = allDisabled; }
            if (deleteCb) { deleteCb.checked = deleteVal; deleteCb.disabled = allDisabled; }
        }

        function toggleSubModuleContainer(parentKey, show) {
            var subEl = document.getElementById(parentKey + '-sub-options');
            if (subEl) subEl.style.display = show ? 'block' : 'none';
        }

        function applyRolePreset(val) {
            // Reset all module checkboxes to unchecked and enabled (superadmin decides everything)
            allModuleIds.forEach(function(id) {
                setModuleCheckbox(id, false, false);
            });

            // Reset all permission checkboxes to unchecked and enabled
            permModules.forEach(function(mod) {
                togglePermCheckboxes(mod, false);
                setPermCheckboxes(mod, false, false, false, false);
            });

            parentModulesWithChildren.forEach(function(parentKey) {
                toggleSubModuleContainer(parentKey, false);
            });

            if (val === 'repository_admin') {
                setModuleCheckbox('mod_repository_management', true, false);
                togglePermCheckboxes('repository_management', true);
                setPermCheckboxes('repository_management', true, true, true, false);
            }

            // Sync optional module permission visibility
            syncOptionalModulePerms();
        }

        function syncOptionalModulePerms() {
            permModules.forEach(function(mod) {
                var modCb = document.getElementById('mod_' + mod);
                if (modCb) {
                    togglePermCheckboxes(mod, modCb.checked);
                }
            });
            parentModulesWithChildren.forEach(function(parentKey) {
                var parentCb = document.getElementById('mod_' + parentKey);
                if (parentCb) {
                    toggleSubModuleContainer(parentKey, parentCb.checked);
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            loadUsersList();

            // Role selection change event handler
            var roleSelectEl = document.getElementById('modal_role');
            if (roleSelectEl) {
                roleSelectEl.addEventListener('change', function () {
                    applyRolePreset(this.value);
                });
            }

            // Reset modal on opening for new user
            var btnAddUser = document.getElementById('btn-add-user');
            if (btnAddUser) {
                btnAddUser.addEventListener('click', function () {
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
                    roleSelect.value = "head_asset_warehouse_admin";
                    applyRolePreset("head_asset_warehouse_admin");
                });
            }

            // Toggle parent module sub-checkboxes visibility
            parentModulesWithChildren.forEach(function(parentKey) {
                var parentCb = document.getElementById('mod_' + parentKey);
                if (parentCb) {
                    parentCb.addEventListener('change', function() {
                        toggleSubModuleContainer(parentKey, this.checked);
                    });
                }
            });

            // Toggle permission checkboxes for all modules when checked/unchecked
            permModules.forEach(function(mod) {
                var modCb = document.getElementById('mod_' + mod);
                if (modCb) {
                    modCb.addEventListener('change', function() {
                        togglePermCheckboxes(mod, this.checked);
                    });
                }
            });

            // Cascade parent module permission changes to child sub-modules
            parentModulesWithChildren.forEach(function(parentKey) {
                ['view', 'add', 'delete'].forEach(function(perm) {
                    var parentPermCb = document.getElementById('perm_' + parentKey + '_' + perm);
                    if (parentPermCb) {
                        parentPermCb.addEventListener('change', function() {
                            var isChecked = this.checked;
                            var parentInfo = MODULE_REGISTRY[parentKey];
                            if (parentInfo && parentInfo.children) {
                                Object.keys(parentInfo.children).forEach(function(childKey) {
                                    var childPermCb = document.getElementById('perm_' + childKey + '_' + perm);
                                    if (childPermCb) {
                                        childPermCb.checked = isChecked;
                                    }
                                });
                            }
                        });
                    }
                });
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

        function buildPermBadges(perms, modKey, modLabel) {
            if (!perms || !perms[modKey]) return '';
            var p = perms[modKey];
            var badges = '';
            if (p.view) badges += '<span class="badge mr-1" style="background-color:#d1ecf1;color:#0c5460;font-size:0.58rem;padding:1px 4px;"><i class="fas fa-eye mr-1"></i>V</span>';
            if (p.add) badges += '<span class="badge mr-1" style="background-color:#d4edda;color:#155724;font-size:0.58rem;padding:1px 4px;"><i class="fas fa-plus mr-1"></i>A</span>';
            if (p['delete']) badges += '<span class="badge mr-1" style="background-color:#f8d7da;color:#721c24;font-size:0.58rem;padding:1px 4px;"><i class="fas fa-trash mr-1"></i>D</span>';
            return badges;
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
                } else if (u.role === 'repository_admin') {
                    roleBadge = '<span class="badge text-white px-2 py-1" style="background-color: #20c997;"><i class="fas fa-folder-open mr-1"></i>Repository Administrator</span>';
                } else if (u.role === 'outsourcing') {
                    roleBadge = '<span class="badge px-2 py-1" style="background-color: #e67e22; color: #fff;"><i class="fas fa-user-tie mr-1"></i>Outsourcing</span>';
                } else {
                    roleBadge = '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-user mr-1"></i>Admin Warehouse</span>';
                }

                var modulesBadges = '';
                var mods = Array.isArray(u.allowed_modules) ? u.allowed_modules : [];
                var perms = (typeof u.permissions === 'object' && u.permissions !== null) ? u.permissions : {};
                
                if (u.role === 'superadmin') {
                    modulesBadges = '<span class="badge badge-success mr-1 mb-1"><i class="fas fa-check-circle mr-1"></i>Semua Modul (Super Admin)</span>';
                } else {
                    // Dynamically build badges from registry
                    Object.keys(MODULE_REGISTRY).forEach(function(modKey) {
                        var mod = MODULE_REGISTRY[modKey];
                        if (mods.includes(modKey)) {
                            var badgeClass = mod.badge_class || 'badge-secondary';
                            var badgeStyle = mod.badge_style ? 'style="' + mod.badge_style + '"' : '';
                            var iconHtml = mod.icon ? '<i class="' + mod.icon + ' mr-1"></i>' : '';
                            
                            // Check for sub modules
                            var subLabel = '';
                            if (mod.children && typeof mod.children === 'object') {
                                var activeSubs = [];
                                Object.keys(mod.children).forEach(function(childKey) {
                                    if (mods.includes(childKey)) {
                                        activeSubs.push(mod.children[childKey].label.replace('Master Data', '').trim());
                                    }
                                });
                                if (activeSubs.length > 0) {
                                    subLabel = ' (' + activeSubs.join(', ') + ')';
                                }
                            }
                            
                            modulesBadges += '<span class="badge ' + badgeClass + ' mr-1 mb-1" ' + badgeStyle + '>' + iconHtml + mod.label.toUpperCase() + subLabel + ' ' + buildPermBadges(perms, modKey, mod.label) + '</span>';
                        }
                    });

                    if (!modulesBadges) {
                        modulesBadges = '<span class="text-muted font-italic" style="font-size:0.75rem;">Tidak ada modul</span>';
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
            var perms = (typeof u.permissions === 'object' && u.permissions !== null) ? u.permissions : {};
            
            // Apply role preset first to reset all checkboxes
            applyRolePreset(u.role || 'head_asset_warehouse_admin');

            // Restore saved module checkboxes from DB data dynamically
            allModuleIds.forEach(function(domId) {
                var modVal = domId.replace('mod_', '');
                var cb = document.getElementById(domId);
                if (cb) {
                    cb.checked = mods.includes(modVal);
                }
            });

            // Toggle containers
            parentModulesWithChildren.forEach(function(parentKey) {
                var parentCb = document.getElementById('mod_' + parentKey);
                toggleSubModuleContainer(parentKey, parentCb ? parentCb.checked : false);
            });

            // Populate per-module permission checkboxes from saved data
            permModules.forEach(function(mod) {
                var modCb = document.getElementById('mod_' + mod);
                var isModActive = modCb ? modCb.checked : false;

                if (isModActive) {
                    togglePermCheckboxes(mod, true);
                    if (perms[mod]) {
                        setPermCheckboxes(mod, !!perms[mod].view, !!perms[mod].add, !!perms[mod]['delete'], false);
                    } else {
                        // Default: all unchecked
                        setPermCheckboxes(mod, false, false, false, false);
                    }
                } else {
                    togglePermCheckboxes(mod, false);
                }
            });

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

            // No forced modules - superadmin decides all access

            // Collect permissions from checkboxes
            var permissions = {};
            permModules.forEach(function(mod) {
                var viewCb = document.getElementById('perm_' + mod + '_view');
                var addCb = document.getElementById('perm_' + mod + '_add');
                var deleteCb = document.getElementById('perm_' + mod + '_delete');

                // Only include permissions for modules that are in selectedModules
                if (selectedModules.includes(mod)) {
                    permissions[mod] = {
                        view: viewCb ? viewCb.checked : true,
                        add: addCb ? addCb.checked : false,
                        'delete': deleteCb ? deleteCb.checked : false
                    };
                }
            });

            var payload = {
                action: 'save',
                id: userId,
                username: username,
                name: name,
                password: password,
                role: role,
                allowed_modules: selectedModules,
                permissions: permissions
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
