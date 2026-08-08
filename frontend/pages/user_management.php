<?php
// user_management.php
require_once __DIR__ . '/../../backend/auth.php';

checkModuleAccess('user_management'); // Enforce login & superadmin access
$user = getCurrentUser();

if ($user['role'] !== 'superadmin') {
    renderAccessDeniedPage('User Management', $user);
    exit;
}

$pageTitle = 'User Management - Dashboard Warehouse';
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

                                                <!-- Overview Section -->
                                                <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-th-large mr-1"></i>Overview:</p>
                                                <div class="module-perm-row mb-2" data-module="dashboard">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_dashboard" value="dashboard">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_dashboard">
                                                            <i class="fas fa-th-large text-primary mr-1"></i> Dashboard Overview
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_dashboard" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_dashboard_view" data-module="dashboard" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_dashboard_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_dashboard_add" data-module="dashboard" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_dashboard_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_dashboard_delete" data-module="dashboard" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_dashboard_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr class="my-2">
                                                <!-- Main Menu Section -->
                                                <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-bars mr-1"></i>Main Menu:</p>

                                                <!-- Inbound -->
                                                <div class="module-perm-row mb-2" data-module="inbound">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_inbound" value="inbound">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_inbound">
                                                            <i class="fas fa-box-open text-primary mr-1"></i> Inbound Management
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_inbound" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_inbound_view" data-module="inbound" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_inbound_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_inbound_add" data-module="inbound" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_inbound_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_inbound_delete" data-module="inbound" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_inbound_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Warehouse / Storage -->
                                                <div class="module-perm-row mb-2" data-module="warehouse">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_warehouse" value="warehouse">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_warehouse">
                                                            <i class="fas fa-warehouse text-primary mr-1"></i> Storage Management
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_warehouse" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_warehouse_view" data-module="warehouse" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_warehouse_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_warehouse_add" data-module="warehouse" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_warehouse_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_warehouse_delete" data-module="warehouse" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_warehouse_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Outbound -->
                                                <div class="module-perm-row mb-2" data-module="outbound">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_outbound" value="outbound">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_outbound">
                                                            <i class="fas fa-truck-loading text-primary mr-1"></i> Outbound Management
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_outbound" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_outbound_view" data-module="outbound" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_outbound_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_outbound_add" data-module="outbound" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_outbound_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_outbound_delete" data-module="outbound" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_outbound_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr class="my-2">
                                                <!-- Data Settings Section -->
                                                <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-cog mr-1"></i>Data Settings:</p>

                                                <!-- Master Data -->
                                                <div class="module-perm-row mb-2" data-module="master_data">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_master_data" value="master_data">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_master_data">
                                                            <i class="fas fa-database text-primary mr-1"></i> Master Data
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_master_data" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_master_data_view" data-module="master_data" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_master_data_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_master_data_add" data-module="master_data" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_master_data_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_master_data_delete" data-module="master_data" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_master_data_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Master Data Sub-Checkboxes -->
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

                                                <!-- Inventory -->
                                                <div class="module-perm-row mb-2" data-module="inventory">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_inventory" value="inventory">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_inventory">
                                                            <i class="fas fa-boxes text-primary mr-1"></i> Inventory
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_inventory" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_inventory_view" data-module="inventory" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_inventory_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_inventory_add" data-module="inventory" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_inventory_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_inventory_delete" data-module="inventory" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_inventory_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Location -->
                                                <div class="module-perm-row mb-2" data-module="location">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_location" value="location">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_location">
                                                            <i class="fas fa-map-marker-alt text-primary mr-1"></i> Location
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_location" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_location_view" data-module="location" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_location_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_location_add" data-module="location" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_location_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_location_delete" data-module="location" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_location_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <hr class="my-2">
                                                <!-- Report Section -->
                                                <p class="small text-muted mb-2 font-weight-bold" style="line-height:1.3;"><i class="fas fa-chart-bar mr-1"></i>Report:</p>

                                                <!-- Reports -->
                                                <div class="module-perm-row mb-2" data-module="reports">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_reports" value="reports">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_reports">
                                                            <i class="fas fa-file-alt text-primary mr-1"></i> Reports
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_reports" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_reports_view" data-module="reports" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_reports_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_reports_add" data-module="reports" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_reports_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_reports_delete" data-module="reports" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_reports_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Analytics -->
                                                <div class="module-perm-row mb-2" data-module="analytics">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_analytics" value="analytics">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_analytics">
                                                            <i class="fas fa-chart-line text-primary mr-1"></i> Analytics
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_analytics" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_analytics_view" data-module="analytics" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_analytics_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_analytics_add" data-module="analytics" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_analytics_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_analytics_delete" data-module="analytics" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_analytics_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- KPI Monitoring -->
                                                <div class="module-perm-row mb-2" data-module="kpi_monitoring">
                                                    <div class="custom-control custom-checkbox d-inline-block">
                                                        <input type="checkbox" class="custom-control-input module-checkbox" id="mod_kpi_monitoring" value="kpi_monitoring">
                                                        <label class="custom-control-label font-weight-bold text-gray-800" for="mod_kpi_monitoring">
                                                            <i class="fas fa-tachometer-alt text-primary mr-1"></i> KPI Monitoring
                                                        </label>
                                                    </div>
                                                    <div class="perm-checkboxes ml-4 mt-1" id="perm_kpi_monitoring" style="display:none;">
                                                        <div class="d-flex flex-wrap" style="gap: 8px;">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_kpi_monitoring_view" data-module="kpi_monitoring" data-perm="view">
                                                                <label class="custom-control-label text-muted" for="perm_kpi_monitoring_view" style="font-size:0.75rem;"><i class="fas fa-eye mr-1"></i>View</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_kpi_monitoring_add" data-module="kpi_monitoring" data-perm="add">
                                                                <label class="custom-control-label text-muted" for="perm_kpi_monitoring_add" style="font-size:0.75rem;"><i class="fas fa-plus mr-1"></i>Add/Edit</label>
                                                            </div>
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" class="custom-control-input perm-cb" id="perm_kpi_monitoring_delete" data-module="kpi_monitoring" data-perm="delete">
                                                                <label class="custom-control-label text-muted" for="perm_kpi_monitoring_delete" style="font-size:0.75rem;"><i class="fas fa-trash mr-1"></i>Delete</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
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

        // List of all modules that have permission checkboxes
        var permModules = ['dashboard', 'inbound', 'warehouse', 'outbound', 'master_data', 'inventory', 'location', 'reports', 'analytics', 'kpi_monitoring'];

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

        function applyRolePreset(val) {
            // Reset all module checkboxes to unchecked and enabled (superadmin decides everything)
            var allModuleIds = ['mod_dashboard', 'mod_inbound', 'mod_warehouse', 'mod_outbound',
                'mod_master_data', 'mod_master_data_inbound', 'mod_master_data_storage', 'mod_master_data_outbound',
                'mod_inventory', 'mod_location', 'mod_reports', 'mod_analytics', 'mod_kpi_monitoring'];
            allModuleIds.forEach(function(id) {
                setModuleCheckbox(id, false, false);
            });

            // Reset all permission checkboxes to unchecked and enabled
            permModules.forEach(function(mod) {
                togglePermCheckboxes(mod, false);
                setPermCheckboxes(mod, false, false, false, false);
            });

            toggleMasterDataSub(false);

            // Sync optional module permission visibility
            syncOptionalModulePerms();
        }

        /**
         * For optional modules (master_data, inventory, location, reports, analytics, dashboard),
         * show/hide permission checkboxes based on whether the module checkbox is checked.
         */
        function syncOptionalModulePerms() {
            permModules.forEach(function(mod) {
                var modCb = document.getElementById('mod_' + mod);
                if (modCb) {
                    togglePermCheckboxes(mod, modCb.checked);
                }
            });
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
                roleSelect.value = "head_asset_warehouse_admin";
                applyRolePreset("head_asset_warehouse_admin");
            });

            // Toggle Master Data sub-checkboxes visibility when parent is checked/unchecked
            document.getElementById('mod_master_data').addEventListener('change', function() {
                toggleMasterDataSub(this.checked);
                togglePermCheckboxes('master_data', this.checked);
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
                } else if (u.role === 'outsourcing') {
                    roleBadge = '<span class="badge px-2 py-1" style="background-color: #e67e22; color: #fff;"><i class="fas fa-user-tie mr-1"></i>Outsourcing</span>';
                } else {
                    roleBadge = '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-user mr-1"></i>Admin Warehouse</span>';
                }

                var modulesBadges = '';
                var mods = Array.isArray(u.allowed_modules) ? u.allowed_modules : [];
                var perms = (typeof u.permissions === 'object' && u.permissions !== null) ? u.permissions : {};
                var isHeadUser = (u.role && u.role.indexOf('head_') === 0) || u.role === 'head_warehouse_admin';
                
                if (u.role === 'superadmin') {
                    modulesBadges = '<span class="badge badge-success mr-1 mb-1"><i class="fas fa-check-circle mr-1"></i>Semua Modul (Super Admin)</span>';
                } else {
                    if (mods.includes('dashboard')) {
                        modulesBadges += '<span class="badge badge-secondary mr-1 mb-1"><i class="fas fa-th-large mr-1"></i>DASHBOARD ' + buildPermBadges(perms, 'dashboard', 'Dashboard') + '</span>';
                    }
                    if (mods.includes('inbound')) {
                        modulesBadges += '<span class="badge badge-success mr-1 mb-1"><i class="fas fa-box-open mr-1"></i>INBOUND ' + buildPermBadges(perms, 'inbound', 'Inbound') + '</span>';
                    }
                    if (mods.includes('warehouse')) {
                        modulesBadges += '<span class="badge badge-primary mr-1 mb-1"><i class="fas fa-warehouse mr-1"></i>STORAGE ' + buildPermBadges(perms, 'warehouse', 'Storage') + '</span>';
                    }
                    if (mods.includes('outbound')) {
                        modulesBadges += '<span class="badge badge-warning text-white mr-1 mb-1"><i class="fas fa-truck-loading mr-1"></i>OUTBOUND ' + buildPermBadges(perms, 'outbound', 'Outbound') + '</span>';
                    }
                    if (mods.includes('master_data')) {
                        var mdSubs = [];
                        if (mods.includes('master_data_inbound')) mdSubs.push('Inbound');
                        if (mods.includes('master_data_storage')) mdSubs.push('Storage');
                        if (mods.includes('master_data_outbound')) mdSubs.push('Outbound');
                        var subLabel = mdSubs.length > 0 ? ' (' + mdSubs.join(', ') + ')' : '';
                        modulesBadges += '<span class="badge badge-dark mr-1 mb-1"><i class="fas fa-database mr-1"></i>MASTER DATA' + subLabel + ' ' + buildPermBadges(perms, 'master_data', 'Master Data') + '</span>';
                    }
                    if (mods.includes('inventory')) {
                        modulesBadges += '<span class="badge badge-primary mr-1 mb-1"><i class="fas fa-boxes mr-1"></i>INVENTORY ' + buildPermBadges(perms, 'inventory', 'Inventory') + '</span>';
                    }
                    if (mods.includes('location')) {
                        modulesBadges += '<span class="badge badge-info mr-1 mb-1"><i class="fas fa-map-marker-alt mr-1"></i>LOCATION ' + buildPermBadges(perms, 'location', 'Location') + '</span>';
                    }
                    if (mods.includes('reports')) {
                        modulesBadges += '<span class="badge badge-info mr-1 mb-1"><i class="fas fa-file-alt mr-1"></i>REPORTS ' + buildPermBadges(perms, 'reports', 'Reports') + '</span>';
                    }
                    if (mods.includes('analytics')) {
                        modulesBadges += '<span class="badge badge-info mr-1 mb-1"><i class="fas fa-chart-line mr-1"></i>ANALYTICS ' + buildPermBadges(perms, 'analytics', 'Analytics') + '</span>';
                    }
                    if (mods.includes('kpi_monitoring')) {
                        modulesBadges += '<span class="badge badge-info mr-1 mb-1"><i class="fas fa-tachometer-alt mr-1"></i>KPI MONITORING ' + buildPermBadges(perms, 'kpi_monitoring', 'KPI Monitoring') + '</span>';
                    }
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

            // Restore saved module checkboxes from DB data
            document.getElementById('mod_dashboard').checked = mods.includes('dashboard');
            document.getElementById('mod_inbound').checked = mods.includes('inbound');
            document.getElementById('mod_warehouse').checked = mods.includes('warehouse');
            document.getElementById('mod_outbound').checked = mods.includes('outbound');
            document.getElementById('mod_inventory').checked = mods.includes('inventory');
            document.getElementById('mod_location').checked = mods.includes('location');
            document.getElementById('mod_reports').checked = mods.includes('reports');
            document.getElementById('mod_analytics').checked = mods.includes('analytics');
            document.getElementById('mod_kpi_monitoring').checked = mods.includes('kpi_monitoring');
            document.getElementById('mod_master_data').checked = mods.includes('master_data');
            document.getElementById('mod_master_data_inbound').checked = mods.includes('master_data_inbound');
            document.getElementById('mod_master_data_storage').checked = mods.includes('master_data_storage');
            document.getElementById('mod_master_data_outbound').checked = mods.includes('master_data_outbound');

            toggleMasterDataSub(document.getElementById('mod_master_data').checked);

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
