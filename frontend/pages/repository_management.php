<?php
// frontend/pages/repository_management.php - Management for Repository Documents & Work Instructions (WI)
require_once __DIR__ . '/../../backend/auth.php';

checkModuleAccess('repository_management');
$user = getCurrentUser();
$isSuperAdmin = ($user['role'] === 'superadmin');
$isRepoAdmin = ($user['role'] === 'repository_admin');
$allowedModules = is_array($user['allowed_modules'] ?? null) ? $user['allowed_modules'] : [];
$hasRepoAccess = $isSuperAdmin || $isRepoAdmin || in_array('repository_management', $allowedModules);

if (!$hasRepoAccess) {
    renderAccessDeniedPage('repository_management', $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
    exit;
}

$canUploadDoc = $isSuperAdmin || canAdd('repository_management');
$canDeleteDoc = $isSuperAdmin || canDelete('repository_management');

$pageTitle = 'WMS - Documents Repository Management';
include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'repository_management'; 
                $hidePeriodSelector = true;
                include FRONTEND_PATH . 'components/navbar.php'; 
                ?>

                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">
                                Documents Repository (WI)
                            </h1>
                            <p class="text-muted small mb-0">Format Dokumen : PDF (Maks. 25MB)</p>
                        </div>
                        <?php if ($canUploadDoc): ?>
                        <button class="btn btn-primary shadow-sm mt-3 mt-sm-0 font-weight-bold" id="btn-add-document" data-toggle="modal" data-target="#uploadDocModal">
                            <i class="fas fa-file-upload fa-sm text-white-50 mr-1"></i> Upload Dokumen PDF
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Statistics / Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Dokumen PDF</div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="statTotalDocs">0 Dokumen</div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-file-pdf fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Document Table Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-wrap align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-list mr-1"></i> Daftar Dokumen &amp; Work Instruction (WI)
                            </h6>
                            <div class="d-flex align-items-center gap-2 mt-2 mt-sm-0">
                                <div class="input-group input-group-sm" style="max-width: 250px;">
                                    <input type="text" class="form-control" id="tableSearchInput" placeholder="Search...">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary ml-2" id="btnRefreshTable" title="Muat Ulang">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 520px; overflow-y: auto; border: 1px solid #e3e6f0; border-radius: 8px;">
                                <table class="table table-bordered table-hover mb-0" id="documentsTable" width="100%" cellspacing="0">
                                    <thead class="thead-light" style="position: sticky; top: 0; z-index: 2; background-color: #f8f9fc;">
                                        <tr>
                                            <th style="width: 45px; text-align: center;">No.</th>
                                            <th style="min-width: 240px;">Nama Dokumen / File PDF</th>
                                            <th style="width: 200px; text-align: center;">Segment Dokumen</th>
                                            <th style="width: 110px; text-align: center;">Ukuran File</th>
                                            <th style="width: 140px;">Diupload Oleh</th>
                                            <th style="width: 150px;">Tanggal Upload</th>
                                            <th style="width: 160px; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="documents-table-body">
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat daftar dokumen...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.container-fluid -->
            </div>

            <!-- ======================================================== -->
            <!-- MODAL: UPLOAD DOKUMEN WORK INSTRUCTION (PDF)            -->
            <!-- ======================================================== -->
            <div class="modal fade" id="uploadDocModal" tabindex="-1" role="dialog" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                        <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #0b192c 0%, #1e3e62 100%);">
                            <h5 class="modal-title font-weight-bold my-auto" id="uploadDocModalLabel">
                                <i class="fas fa-file-upload mr-2"></i>Upload File PDF (WI)
                            </h5>
                            <button type="button" class="close text-white opacity-75 my-auto" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="formUploadDocument" enctype="multipart/form-data">
                            <div class="modal-body p-4 bg-white">
                                <input type="hidden" name="action" value="upload">

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Nama Dokumen / File <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" id="uploadTitleInput" placeholder="Contoh: WI - Penerimaan Barang & Staging Area.pdf" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Segment Dokumen <span class="text-danger">*</span></label>
                                    <select class="form-control" name="category" id="uploadCategorySelect" required>
                                        <option value="Policy Document">Policy Document</option>
                                        <option value="Procedure Document">Procedure Document</option>
                                        <option value="Working Instruction (WI) Document" selected>Working Instruction (WI) Document</option>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Divisi <span class="text-danger">*</span></label>
                                    <select class="form-control" name="division" id="uploadDivisionSelect" required>
                                        <option value="Supply Chain Management" selected>Supply Chain Management</option>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Bagian <span class="text-danger">*</span></label>
                                    <select class="form-control" name="bagian" id="uploadBagianSelect" required>
                                        <option value="">-- Pilih Bagian --</option>
                                        <option value="Asset And Warehouse Management">Asset And Warehouse Management</option>
                                        <option value="Facility Management">Facility Management</option>
                                        <option value="Procurement Center Of Excellence">Procurement Center Of Excellence</option>
                                        <option value="Procurement Operation">Procurement Operation</option>
                                        <option value="Regional Procurement">Regional Procurement</option>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Sub Bagian <span class="text-danger">*</span></label>
                                    <select class="form-control" name="sub_bagian" id="uploadSubBagianSelect" required>
                                        <option value="">-- Pilih Sub Bagian --</option>
                                        <option value="ASP and System Governance">ASP and System Governance</option>
                                        <option value="Asset Management">Asset Management</option>
                                        <option value="Warehouse Management">Warehouse Management</option>
                                        <option value="Partner Care">Partner Care</option>
                                        <option value="Partner Sourcing">Partner Sourcing</option>
                                        <option value="Strategic Sourcing">Strategic Sourcing</option>
                                    </select>
                                </div>

                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold text-gray-700">File Dokumen PDF <span class="text-danger">*</span></label>
                                    <div class="p-4 text-center rounded border" id="uploadDropZone" style="background: #f8fafc; border: 2px dashed #cbd5e1 !important; cursor: pointer;" onclick="document.getElementById('uploadFileInput').click();">
                                        <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                                        <p class="mb-1 font-weight-bold text-gray-800" id="uploadFileLabel">Klik atau Seret file PDF ke sini</p>
                                        <p class="text-muted small mb-0">Format: .pdf (Maksimum 25MB)</p>
                                        <input type="file" id="uploadFileInput" name="pdf_file" accept=".pdf,application/pdf" style="display: none;" required>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light px-4 py-3 border-top-0 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary font-weight-bold px-3" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-primary font-weight-bold px-4" id="btnSubmitUpload" style="background: #1e3e62; border-color: #1e3e62;">
                                    <i class="fas fa-upload mr-1"></i>Simpan &amp; Upload
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- MODAL: EDIT DOKUMEN WORK INSTRUCTION                    -->
            <!-- ======================================================== -->
            <div class="modal fade" id="editDocModal" tabindex="-1" role="dialog" aria-labelledby="editDocModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                        <div class="modal-header bg-info text-white py-3 px-4">
                            <h5 class="modal-title font-weight-bold my-auto" id="editDocModalLabel">
                                <i class="fas fa-edit mr-2"></i>Edit Dokumen PDF
                            </h5>
                            <button type="button" class="close text-white opacity-75 my-auto" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="formEditDocument" enctype="multipart/form-data">
                            <div class="modal-body p-4 bg-white">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="id" id="editDocId">

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Nama Dokumen / File <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="title" id="editDocTitle" required>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Segment Dokumen <span class="text-danger">*</span></label>
                                    <select class="form-control" name="category" id="editDocCategory" required>
                                        <option value="Policy Document">Policy Document</option>
                                        <option value="Procedure Document">Procedure Document</option>
                                        <option value="Working Instruction (WI) Document">Working Instruction (WI) Document</option>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Divisi <span class="text-danger">*</span></label>
                                    <select class="form-control" name="division" id="editDocDivision" required>
                                        <option value="Supply Chain Management" selected>Supply Chain Management</option>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Bagian <span class="text-danger">*</span></label>
                                    <select class="form-control" name="bagian" id="editDocBagian" required>
                                        <option value="">-- Pilih Bagian --</option>
                                        <option value="Asset And Warehouse Management">Asset And Warehouse Management</option>
                                        <option value="Facility Management">Facility Management</option>
                                        <option value="Procurement Center Of Excellence">Procurement Center Of Excellence</option>
                                        <option value="Procurement Operation">Procurement Operation</option>
                                        <option value="Regional Procurement">Regional Procurement</option>
                                    </select>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold text-gray-700">Sub Bagian <span class="text-danger">*</span></label>
                                    <select class="form-control" name="sub_bagian" id="editDocSubBagian" required>
                                        <option value="">-- Pilih Sub Bagian --</option>
                                        <option value="ASP and System Governance">ASP and System Governance</option>
                                        <option value="Asset Management">Asset Management</option>
                                        <option value="Warehouse Management">Warehouse Management</option>
                                        <option value="Partner Care">Partner Care</option>
                                        <option value="Partner Sourcing">Partner Sourcing</option>
                                        <option value="Strategic Sourcing">Strategic Sourcing</option>
                                    </select>
                                </div>

                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold text-gray-700">Ganti File PDF <span class="text-muted">(Opsional)</span></label>
                                    <div class="p-3 text-center rounded border" style="background: #f8fafc; border: 2px dashed #cbd5e1 !important; cursor: pointer;" onclick="document.getElementById('editDocFileInput').click();">
                                        <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                                        <p class="mb-0 small font-weight-bold text-gray-800" id="editFileLabel">Klik untuk memilih file PDF pengganti</p>
                                        <input type="file" id="editDocFileInput" name="pdf_file" accept=".pdf,application/pdf" style="display: none;">
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light px-4 py-3 border-top-0 d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary font-weight-bold px-3" data-dismiss="modal">
                                    <i class="fas fa-times mr-1"></i>Batal
                                </button>
                                <button type="submit" class="btn btn-info font-weight-bold px-4" id="btnSubmitEdit">
                                    <i class="fas fa-save mr-1"></i>Perbarui
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- MODAL: DELETE CONFIRMATION                               -->
            <!-- ======================================================== -->
            <div class="modal fade" id="deleteDocModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
                        <div class="modal-body p-4 text-center">
                            <div class="mb-3">
                                <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 shadow-sm" style="width: 60px; height: 60px; background: #fee2e2; color: #dc2626;">
                                    <i class="fas fa-trash-alt fa-2x"></i>
                                </div>
                            </div>
                            <h6 class="font-weight-bold text-gray-800 mb-2">Hapus File PDF?</h6>
                            <p class="text-muted small mb-4" id="deleteDocPrompt">Apakah Anda yakin ingin menghapus file ini dari repositori? File akan dihapus permanen.</p>
                            <div class="d-flex justify-content-center">
                                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3 mr-2" data-dismiss="modal">Batal</button>
                                <button type="button" id="btnConfirmDeleteDoc" class="btn btn-danger btn-sm rounded-pill px-4 font-weight-bold shadow-sm">
                                    <i class="fas fa-trash mr-1"></i> Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <?php include FRONTEND_PATH . 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    (function () {
        const CAN_UPLOAD_DOC = <?php echo ($canUploadDoc) ? 'true' : 'false'; ?>;
        const CAN_DELETE_DOC = <?php echo ($canDeleteDoc) ? 'true' : 'false'; ?>;
        let loadedDocuments = [];
        let searchKeyword = '';
        let targetDeleteId = null;

        // Fetch Documents from API
        function loadDocumentsTable() {
            let url = 'api/manage_repository.php?action=list';
            if (searchKeyword.trim() !== '') {
                url += '&search=' + encodeURIComponent(searchKeyword.trim());
            }

            const tbody = $('#documents-table-body');
            tbody.html(`
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin mr-1"></i> Memuat daftar dokumen...
                    </td>
                </tr>
            `);

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    if (res && res.success) {
                        loadedDocuments = res.data || [];
                        $('#statTotalDocs').text(loadedDocuments.length + ' Dokumen');
                        renderTable(loadedDocuments);
                    } else {
                        tbody.html(`
                            <tr>
                                <td colspan="7" class="text-center py-4 text-danger">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> ${res.message || 'Gagal memuat dokumen.'}
                                </td>
                            </tr>
                        `);
                    }
                },
                error: function () {
                    tbody.html(`
                        <tr>
                            <td colspan="7" class="text-center py-4 text-danger">
                                <i class="fas fa-exclamation-circle mr-1"></i> Terjadi kesalahan saat menghubungi server.
                            </td>
                        </tr>
                    `);
                }
            });
        }

        // Render Table Rows
        function renderTable(docs) {
            const tbody = $('#documents-table-body');
            tbody.empty();

            if (!docs || docs.length === 0) {
                tbody.html(`
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-folder-open fa-2x mb-2 d-block text-gray-300"></i>
                            Tidak ada dokumen yang ditemukan.
                        </td>
                    </tr>
                `);
                return;
            }

            docs.forEach(function (doc, index) {
                let displayName = doc.title || doc.original_name || 'Dokumen.pdf';
                let uploader = doc.uploaded_by || 'Super Admin';
                let formattedDate = doc.formatted_date || doc.created_at || '-';
                let size = doc.formatted_size || '-';
                let category = doc.category || 'Working Instruction (WI) Document';

                let badgeHtml = '<span class="badge badge-success px-2 py-1"><i class="fas fa-file-alt mr-1"></i>WI Document</span>';
                if (category === 'Policy Document') {
                    badgeHtml = '<span class="badge badge-primary px-2 py-1"><i class="fas fa-shield-alt mr-1"></i>Policy Document</span>';
                } else if (category === 'Procedure Document') {
                    badgeHtml = '<span class="badge badge-info px-2 py-1"><i class="fas fa-project-diagram mr-1"></i>Procedure Document</span>';
                }

                let bagianBadge = doc.bagian ? `<span class="badge badge-light border text-primary" style="font-size: 0.72rem;">${escapeHtml(doc.bagian)}</span>` : '';
                let subBagianBadge = doc.sub_bagian ? `<span class="badge badge-light border text-info" style="font-size: 0.72rem;">${escapeHtml(doc.sub_bagian)}</span>` : '';

                let editBtnHtml = CAN_UPLOAD_DOC ? `
                    <button type="button" class="btn btn-outline-info btn-edit-doc" data-id="${doc.id}" title="Edit Nama / Segment / File">
                        <i class="fas fa-pencil-alt"></i>
                    </button>` : '';

                let deleteBtnHtml = CAN_DELETE_DOC ? `
                    <button type="button" class="btn btn-outline-danger btn-delete-doc" data-id="${doc.id}" data-title="${escapeHtml(displayName)}" title="Hapus Dokumen">
                        <i class="fas fa-trash-alt"></i>
                    </button>` : '';

                let rowHtml = `
                    <tr>
                        <td class="text-center align-middle font-weight-bold text-gray-600">${index + 1}</td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <div class="p-2 rounded mr-3" style="background: #fee2e2; color: #dc2626;">
                                    <i class="fas fa-file-pdf fa-lg"></i>
                                </div>
                                <div>
                                    <div class="font-weight-bold text-gray-800" style="font-size: 0.92rem;">${escapeHtml(displayName)}</div>
                                    <div class="d-flex flex-wrap align-items-center mt-1" style="gap: 5px;">
                                        <small class="text-muted mr-1">${escapeHtml(doc.original_name || '')}</small>
                                        ${bagianBadge}
                                        ${subBagianBadge}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            ${badgeHtml}
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge badge-light font-weight-bold text-dark border px-2 py-1">${escapeHtml(size)}</span>
                        </td>
                        <td class="align-middle">
                            <span class="small font-weight-bold text-gray-700">
                                <i class="fas fa-user-circle text-primary mr-1"></i> ${escapeHtml(uploader)}
                            </span>
                        </td>
                        <td class="align-middle small text-gray-600">
                            ${escapeHtml(formattedDate)}
                        </td>
                        <td class="text-center align-middle">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="api/manage_repository.php?action=view&id=${doc.id}" target="_blank" class="btn btn-outline-primary" title="Preview PDF">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="api/manage_repository.php?action=download&id=${doc.id}" class="btn btn-outline-success" title="Download PDF">
                                    <i class="fas fa-download"></i>
                                </a>
                                ${editBtnHtml}
                                ${deleteBtnHtml}
                            </div>
                        </td>
                    </tr>
                `;
                tbody.append(rowHtml);
            });
        }

        // Helper XSS
        function escapeHtml(str) {
            if (!str) return '';
            const entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;'
            };
            return String(str).replace(/[&<>"'\/]/g, s => entityMap[s]);
        }

        // Live Search
        let searchTimeout = null;
        $('#tableSearchInput').on('input', function () {
            clearTimeout(searchTimeout);
            searchKeyword = $(this).val();
            searchTimeout = setTimeout(function () {
                loadDocumentsTable();
            }, 300);
        });

        $('#btnRefreshTable').on('click', function () {
            loadDocumentsTable();
        });

        // File Selection Handlers for Upload
        const uploadFileInput = document.getElementById('uploadFileInput');
        const uploadFileLabel = document.getElementById('uploadFileLabel');
        const uploadTitleInput = document.getElementById('uploadTitleInput');

        if (uploadFileInput) {
            uploadFileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    let file = this.files[0];
                    if (!file.name.toLowerCase().endsWith('.pdf')) {
                        Swal.fire('Warning', 'Hanya file PDF (.pdf) yang diperbolehkan.', 'warning');
                        this.value = '';
                        return;
                    }
                    if (file.size > 25 * 1024 * 1024) {
                        Swal.fire('Warning', 'Ukuran file PDF melebihi batas 25MB.', 'warning');
                        this.value = '';
                        return;
                    }
                    let sizeMB = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                    uploadFileLabel.innerHTML = '<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>' + escapeHtml(file.name) + ' (' + sizeMB + ')</span>';
                    if (uploadTitleInput && !uploadTitleInput.value.trim()) {
                        uploadTitleInput.value = file.name.replace(/\.pdf$/i, '');
                    }
                }
            });
        }

        // Upload Form Submit
        $('#formUploadDocument').on('submit', function (e) {
            e.preventDefault();
            const btn = $('#btnSubmitUpload');
            const form = this;
            const formData = new FormData(form);

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengunggah...');

            $.ajax({
                url: 'api/manage_repository.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> Simpan &amp; Upload');
                    if (res && res.success) {
                        $('#uploadDocModal').modal('hide');
                        form.reset();
                        if (uploadFileLabel) {
                            uploadFileLabel.innerText = 'Klik atau Seret file PDF ke sini';
                        }
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil Diunggah!',
                            text: res.message || 'File PDF berhasil diunggah.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadDocumentsTable();
                    } else {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan saat mengunggah file.', 'error');
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-upload mr-1"></i> Simpan &amp; Upload');
                    Swal.fire('Error', 'Gagal terhubung ke server.', 'error');
                }
            });
        });

        // Edit Form Handlers
        const editDocFileInput = document.getElementById('editDocFileInput');
        const editFileLabel = document.getElementById('editFileLabel');

        if (editDocFileInput) {
            editDocFileInput.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    let file = this.files[0];
                    if (!file.name.toLowerCase().endsWith('.pdf')) {
                        Swal.fire('Warning', 'Hanya file PDF (.pdf) yang diperbolehkan.', 'warning');
                        this.value = '';
                        return;
                    }
                    if (file.size > 25 * 1024 * 1024) {
                        Swal.fire('Warning', 'Ukuran file PDF melebihi 25MB.', 'warning');
                        this.value = '';
                        return;
                    }
                    let sizeMB = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
                    editFileLabel.innerHTML = '<span class="text-success font-weight-bold"><i class="fas fa-check-circle mr-1"></i>' + escapeHtml(file.name) + ' (' + sizeMB + ')</span>';
                }
            });
        }

        $(document).on('click', '.btn-edit-doc', function () {
            const id = $(this).data('id');
            const doc = loadedDocuments.find(d => parseInt(d.id) === parseInt(id));
            if (!doc) return;

            $('#editDocId').val(doc.id);
            $('#editDocTitle').val(doc.title || doc.original_name || '');
            let currentCat = doc.category || 'Working Instruction (WI) Document';
            if (currentCat !== 'Policy Document' && currentCat !== 'Procedure Document' && currentCat !== 'Working Instruction (WI) Document') {
                currentCat = 'Working Instruction (WI) Document';
            }
            $('#editDocCategory').val(currentCat);
            $('#editDocDivision').val(doc.division || 'Supply Chain Management');
            $('#editDocBagian').val(doc.bagian || '');
            $('#editDocSubBagian').val(doc.sub_bagian || '');
            if (editFileLabel) {
                editFileLabel.innerText = 'Klik untuk memilih file PDF pengganti (Opsional)';
            }
            $('#editDocFileInput').val('');
            $('#editDocModal').modal('show');
        });

        $('#formEditDocument').on('submit', function (e) {
            e.preventDefault();
            const btn = $('#btnSubmitEdit');
            const form = this;
            const formData = new FormData(form);

            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

            $.ajax({
                url: 'api/manage_repository.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Perbarui');
                    if (res && res.success) {
                        $('#editDocModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: res.message || 'Dokumen berhasil diperbarui.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadDocumentsTable();
                    } else {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                    }
                },
                error: function (xhr) {
                    btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Perbarui');
                    let errMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Gagal memperbarui dokumen.';
                    Swal.fire('Error', errMsg, 'error');
                }
            });
        });

        // Delete Handlers
        $(document).on('click', '.btn-delete-doc', function () {
            targetDeleteId = $(this).data('id');
            const title = $(this).data('title');
            $('#deleteDocPrompt').html('Hapus file <strong>"' + escapeHtml(title) + '"</strong> dari repositori? File akan dihapus permanen.');
            $('#deleteDocModal').modal('show');
        });

        $('#btnConfirmDeleteDoc').on('click', function () {
            if (!targetDeleteId) return;
            const btn = $(this);
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menghapus...');

            $.ajax({
                url: 'api/manage_repository.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: targetDeleteId
                },
                dataType: 'json',
                success: function (res) {
                    btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i> Hapus');
                    $('#deleteDocModal').modal('hide');
                    if (res && res.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Terhapus!',
                            text: res.message || 'File PDF berhasil dihapus.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        loadDocumentsTable();
                    } else {
                        Swal.fire('Gagal', res.message || 'Terjadi kesalahan.', 'error');
                    }
                },
                error: function () {
                    btn.prop('disabled', false).html('<i class="fas fa-trash mr-1"></i> Hapus');
                    $('#deleteDocModal').modal('hide');
                    Swal.fire('Error', 'Gagal menghapus dokumen.', 'error');
                }
            });
        });

        // Initial Load
        loadDocumentsTable();
    })();
    </script>
</body>
</html>
