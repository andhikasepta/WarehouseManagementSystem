<?php
// announcements.php - Super Admin Management for Maintenance Notifications & Announcements
require_once __DIR__ . '/../../backend/auth.php';

checkModuleAccess('user_management'); // Enforce login & superadmin access
$user = getCurrentUser();

if ($user['role'] !== 'superadmin') {
    renderAccessDeniedPage('user_management', $user, 'Akun Anda tidak diberikan izin akses ke modul ini.');
    exit;
}

$pageTitle = 'Pengumuman Maintenance - Portal System';
include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100">
            <div id="content" class="flex-grow-1">

                <?php 
                $activePage = 'announcements'; 
                $hidePeriodSelector = true;
                include FRONTEND_PATH . 'components/navbar.php'; 
                ?>

                <div class="container-fluid" style="padding-top: 100px;">
                    <!-- Page Heading -->
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h1 class="h3 mb-1 text-gray-800 font-weight-bold">Pengumuman &amp; Maintenance</h1>
                            <p class="text-muted small mb-0">Kelola notifikasi pemeliharaan sistem dan pesan Pengumuman untuk seluruh pengguna portal.</p>
                        </div>
                        <button class="btn btn-primary shadow-sm mt-3 mt-sm-0 font-weight-bold" id="btn-add-announcement" data-toggle="modal" data-target="#announcementModal">
                            <i class="fas fa-bullhorn fa-sm text-white-50 mr-1"></i> Tambah Pengumuman Baru
                        </button>
                    </div>

                    <!-- Announcement List Table Card -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-list mr-1"></i> Daftar Pengumuman &amp; Jadwal Maintenance
                            </h6>
                            <span class="badge badge-primary px-2 py-1">Super Admin Access</span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 520px; overflow-y: auto; border: 1px solid #e3e6f0; border-radius: 6px;">
                                <table class="table table-bordered table-striped mb-0" id="announcementsTable" width="100%" cellspacing="0">
                                    <thead class="thead-light" style="position: sticky; top: 0; z-index: 2; background-color: #f8f9fc;">
                                        <tr>
                                            <th style="width: 45px; text-align: center;">No.</th>
                                            <th style="width: 180px;">Judul Pengumuman</th>
                                            <th style="min-width: 350px;">Deskripsi / Pesan</th>
                                            <th style="width: 170px;">Broadcast Periode</th>
                                            <th style="width: 120px; text-align: center;">Status Periode</th>
                                            <th style="width: 110px; text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="announcements-table-body">
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat data Pengumuman...
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

            <!-- Create / Edit Announcement Modal -->
            <div class="modal fade" id="announcementModal" tabindex="-1" role="dialog" aria-labelledby="announcementModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                        <div class="modal-header bg-primary text-white py-3 px-4">
                            <h5 class="modal-title font-weight-bold" id="announcementModalLabel">
                                <i class="fas fa-bullhorn mr-2"></i>Tambah Pengumuman Maintenance
                            </h5>
                            <button type="button" class="close text-white opacity-75" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <form id="formAnnouncement">
                            <input type="hidden" id="announcement_id" name="id" value="0">
                            <div class="modal-body p-4 bg-white">
                                <div class="alert alert-info small mb-3">
                                    <i class="fas fa-info-circle mr-1"></i> Broadcasting Pengumuman Ke Seluruh User.
                                </div>
                                <div class="form-group mb-3">
                                    <label for="modal_type" class="font-weight-bold text-gray-800 small">Tipe Pengumuman <span class="text-danger">*</span></label>
                                    <select class="form-control" id="modal_type" name="type" onchange="toggleVersionGroup(true)" required>
                                        <option value="maintenance">Maintenance</option>
                                        <option value="update">Update</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3" id="modal_version_group" style="display: none;">
                                    <label for="modal_version" class="font-weight-bold text-gray-800 small">Versi Aplikasi <span class="text-muted font-weight-normal">(Khusus Update)</span></label>
                                    <input type="text" class="form-control" id="modal_version" name="version" placeholder="Contoh: Beta-v1.0.1">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="modal_title" class="font-weight-bold text-gray-800 small">Judul Pengumuman <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="modal_title" name="title" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="modal_description" class="font-weight-bold text-gray-800 small">Deskripsi / Pesan Notifikasi <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="modal_description" name="description" rows="4" required></textarea>
                                </div>
                                <div class="form-row mb-3">
                                    <div class="form-group col-md-6 mb-0">
                                        <label for="modal_start_datetime" id="label_start_datetime" class="font-weight-bold text-gray-800 small">Waktu Mulai Periode <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="modal_start_datetime" name="start_datetime" required>
                                    </div>
                                    <div class="form-group col-md-6 mb-0">
                                        <label for="modal_end_datetime" id="label_end_datetime" class="font-weight-bold text-gray-800 small">Waktu Selesai Periode <span class="text-danger">*</span></label>
                                        <input type="datetime-local" class="form-control" id="modal_end_datetime" name="end_datetime" required>
                                    </div>
                                </div>
                                <div class="form-group mb-0">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="modal_is_active" name="is_active" value="1" checked>
                                        <label class="custom-control-label font-weight-bold text-gray-800 small" for="modal_is_active">Aktifkan Pengumuman Ini</label>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light py-3 px-4">
                                <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal">Batal</button>
                                <button type="submit" class="btn btn-primary px-4 font-weight-bold" id="btn-save-announcement">
                                    <i class="fas fa-save mr-1"></i> Simpan Pengumuman
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php include FRONTEND_PATH . 'components/footer.php'; ?>
        </div>
    </div>

    <script>
    var currentAnnouncements = [];

    $(document).ready(function() {
        loadAnnouncements();

        $('#btn-add-announcement').on('click', function() {
            resetForm();
        });

        $('#formAnnouncement').on('submit', function(e) {
            e.preventDefault();
            saveAnnouncement();
        });
    });

    function loadAnnouncements() {
        $.ajax({
            url: 'api/manage_announcements.php?action=list',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    currentAnnouncements = res.data || [];
                    renderAnnouncementsTable(currentAnnouncements);
                } else {
                    alert('Gagal memuat data: ' + (res.message || 'Error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', xhr.status, xhr.responseText);
                alert('Terjadi kesalahan koneksi server: ' + (xhr.responseJSON ? xhr.responseJSON.message : error));
            }
        });
    }

    function renderAnnouncementsTable(data) {
        var tbody = $('#announcements-table-body');
        tbody.empty();

        if (!data || data.length === 0) {
            tbody.html('<tr><td colspan="6" class="text-center py-4 text-muted">Belum ada Pengumuman tersimpan.</td></tr>');
            return;
        }

        var now = new Date();

        data.forEach(function(item, idx) {
            var start = new Date(item.start_datetime.replace(/-/g, "/"));
            var end = new Date(item.end_datetime.replace(/-/g, "/"));
            var isActive = parseInt(item.is_active) === 1;

            var statusBadge = '';
            if (!isActive) {
                statusBadge = '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-eye-slash mr-1"></i>Nonaktif</span>';
            } else if (now >= start && now <= end) {
                statusBadge = '<span class="badge badge-success px-2 py-1 shadow-sm"><i class="fas fa-broadcast-tower mr-1"></i>Sedang Tampil</span>';
            } else if (now < start) {
                statusBadge = '<span class="badge badge-info px-2 py-1"><i class="fas fa-clock mr-1"></i>Dijadwalkan</span>';
            } else {
                statusBadge = '<span class="badge badge-warning text-white px-2 py-1"><i class="fas fa-history mr-1"></i>End</span>';
            }

            var startFmt = formatDateIndo(item.start_datetime);
            var endFmt = formatDateIndo(item.end_datetime);
            var periodText = startFmt + '<br><span class="text-muted small">s/d</span> ' + endFmt;

            var typeBadge = (item.type === 'update') 
                ? '<span class="badge badge-warning text-dark ml-2 px-2 py-1"><i class="fas fa-exclamation-circle mr-1"></i>Update' + (item.version ? ' (' + escapeHtml(item.version) + ')' : '') + '</span>'
                : '<span class="badge badge-primary ml-2 px-2 py-1"><i class="fas fa-tools mr-1"></i>Maintenance</span>';

            var tr = $('<tr>');
            tr.append('<td class="text-center font-weight-bold">' + (idx + 1) + '</td>');
            tr.append('<td class="font-weight-bold text-gray-800">' + escapeHtml(item.title) + typeBadge + '</td>');
            tr.append('<td class="small text-gray-700" style="white-space: pre-line;">' + escapeHtml(item.description) + '</td>');
            tr.append('<td class="small">' + periodText + '</td>');
            tr.append('<td class="text-center">' + statusBadge + '</td>');

            var actions = '<td class="text-center text-nowrap">';
            actions += '<button class="btn btn-sm btn-info mr-1" onclick="editAnnouncement(' + item.id + ')" title="Edit"><i class="fas fa-edit"></i></button>';
            actions += '<button class="btn btn-sm ' + (isActive ? 'btn-secondary' : 'btn-success') + ' mr-1" onclick="toggleStatus(' + item.id + ')" title="' + (isActive ? 'Nonaktifkan' : 'Aktifkan') + '"><i class="fas ' + (isActive ? 'fa-eye-slash' : 'fa-check') + '"></i></button>';
            actions += '<button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(' + item.id + ')" title="Hapus"><i class="fas fa-trash"></i></button>';
            actions += '</td>';

            tr.append(actions);
            tbody.append(tr);
        });
    }

    function formatLocalDateTime(d) {
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        var hours = String(d.getHours()).padStart(2, '0');
        var minutes = String(d.getMinutes()).padStart(2, '0');
        return year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
    }

    function getTodayDateISO() {
        var d = new Date();
        var year = d.getFullYear();
        var month = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function getTomorrowDateISO() {
        var now = new Date();
        var tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        var year = tomorrow.getFullYear();
        var month = String(tomorrow.getMonth() + 1).padStart(2, '0');
        var day = String(tomorrow.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function toggleVersionGroup(isUserAction) {
        var type = $('#modal_type').val();
        var annId = parseInt($('#announcement_id').val() || '0');
        var todayDate = getTodayDateISO();
        var nowDateTime = formatLocalDateTime(new Date());

        if (type === 'update') {
            $('#modal_version_group').slideDown(150);
            $('#label_start_datetime').html('Tanggal Mulai Broadcast <span class="text-danger">*</span>');
            $('#label_end_datetime').html('Tanggal Selesai Broadcast <span class="text-danger">*</span>');
            
            // Switch inputs to date picker (no time) and restrict backdating
            $('#modal_start_datetime').attr('type', 'date').attr('min', todayDate);
            $('#modal_end_datetime').attr('type', 'date').attr('min', todayDate);

            if (isUserAction && (annId === 0 || $('#modal_title').val() === 'System Maintenance' || $('#modal_title').val() === '')) {
                $('#modal_title').val('Update Versi Aplikasi');
            }
            if (isUserAction && annId === 0) {
                $('#modal_start_datetime').val(todayDate);
                $('#modal_end_datetime').val(getTomorrowDateISO());
            }
        } else {
            $('#modal_version_group').slideUp(150);
            $('#label_start_datetime').html('Waktu Mulai Periode <span class="text-danger">*</span>');
            $('#label_end_datetime').html('Waktu Selesai Periode <span class="text-danger">*</span>');

            // Switch inputs to datetime-local picker and restrict backdating
            $('#modal_start_datetime').attr('type', 'datetime-local').attr('min', nowDateTime);
            $('#modal_end_datetime').attr('type', 'datetime-local').attr('min', nowDateTime);

            if (isUserAction && (annId === 0 || $('#modal_title').val() === 'Update Versi Aplikasi' || $('#modal_title').val() === '')) {
                $('#modal_title').val('System Maintenance');
            }
            if (isUserAction && annId === 0) {
                var now = new Date();
                var nextTwoHours = new Date(now.getTime() + 2 * 60 * 60 * 1000);
                $('#modal_start_datetime').val(formatLocalDateTime(now));
                $('#modal_end_datetime').val(formatLocalDateTime(nextTwoHours));
            }
        }
    }

    function resetForm() {
        $('#announcement_id').val(0);
        $('#modal_type').val('maintenance');
        $('#modal_title').val('System Maintenance');
        $('#modal_description').val('');
        $('#modal_version').val('');
        toggleVersionGroup(false);
        
        var now = new Date();
        var nextTwoHours = new Date(now.getTime() + 2 * 60 * 60 * 1000);

        $('#modal_start_datetime').val(formatLocalDateTime(now));
        $('#modal_end_datetime').val(formatLocalDateTime(nextTwoHours));
        $('#modal_is_active').prop('checked', true);

        $('#announcementModalLabel').html('<i class="fas fa-bullhorn mr-2"></i>Tambah Pengumuman');
    }

    function editAnnouncement(id) {
        var item = currentAnnouncements.find(function(a) { return parseInt(a.id) === parseInt(id); });
        if (!item) return;

        $('#announcement_id').val(item.id);
        $('#modal_title').val(item.title);
        $('#modal_description').val(item.description);
        $('#modal_type').val(item.type || 'maintenance');
        $('#modal_version').val(item.version || '');
        toggleVersionGroup(false);

        var isUpdate = (item.type === 'update');
        var startVal = isUpdate ? item.start_datetime.slice(0, 10) : item.start_datetime.replace(' ', 'T').slice(0, 16);
        var endVal = isUpdate ? item.end_datetime.slice(0, 10) : item.end_datetime.replace(' ', 'T').slice(0, 16);

        $('#modal_start_datetime').val(startVal);
        $('#modal_end_datetime').val(endVal);
        $('#modal_is_active').prop('checked', parseInt(item.is_active) === 1);

        $('#announcementModalLabel').html('<i class="fas fa-edit mr-2"></i>Edit Pengumuman');
        $('#announcementModal').modal('show');
    }

    function saveAnnouncement() {
        var startRaw = $('#modal_start_datetime').val() || '';
        var endRaw = $('#modal_end_datetime').val() || '';

        var data = {
            id: $('#announcement_id').val(),
            title: $('#modal_title').val(),
            description: $('#modal_description').val(),
            type: $('#modal_type').val(),
            version: $('#modal_version').val(),
            start_datetime: startRaw,
            end_datetime: endRaw,
            is_active: $('#modal_is_active').is(':checked') ? 1 : 0
        };

        var action = parseInt(data.id) > 0 ? 'update' : 'create';

        $.ajax({
            url: 'api/manage_announcements.php?action=' + action,
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $('#announcementModal').modal('hide');
                    loadAnnouncements();
                    // Force navbar to update immediately
                    if (typeof window.__wmsForceAnnouncementPoll === 'function') {
                        window.__wmsForceAnnouncementPoll();
                    }
                } else {
                    alert('Gagal menyimpan: ' + (res.message || 'Error'));
                }
            },
            error: function(xhr) {
                var errJson = null;
                try { errJson = JSON.parse(xhr.responseText); } catch(e){}
                var msg = errJson && errJson.message ? errJson.message : 'Terjadi kesalahan server saat menyimpan data.';
                alert(msg);
            }
        });
    }

    function toggleStatus(id) {
        $.ajax({
            url: 'api/manage_announcements.php?action=toggle_status',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    loadAnnouncements();
                    // Force navbar to update immediately
                    if (typeof window.__wmsForceAnnouncementPoll === 'function') {
                        window.__wmsForceAnnouncementPoll();
                    }
                } else {
                    alert('Gagal mengubah status: ' + (res.message || 'Error'));
                }
            }
        });
    }

    function deleteAnnouncement(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus Pengumuman ini?')) return;

        $.ajax({
            url: 'api/manage_announcements.php?action=delete',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    loadAnnouncements();
                    // Force navbar to update immediately
                    if (typeof window.__wmsForceAnnouncementPoll === 'function') {
                        window.__wmsForceAnnouncementPoll();
                    }
                } else {
                    alert('Gagal menghapus: ' + (res.message || 'Error'));
                }
            }
        });
    }

    function formatDateIndo(dtStr) {
        if (!dtStr) return '-';
        var parts = dtStr.split(' ');
        var dateParts = parts[0].split('-');
        var timeParts = parts[1] ? parts[1].split(':') : ['00', '00'];

        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var monthIdx = parseInt(dateParts[1], 10) - 1;

        return dateParts[2] + ' ' + months[monthIdx] + ' ' + dateParts[0] + ' ' + timeParts[0] + ':' + timeParts[1];
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    </script>
</body>
</html>


