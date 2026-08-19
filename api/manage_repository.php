<?php
// api/manage_repository.php - Repository & Work Instruction (WI) Handler

require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/auth.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Public/Guest access is not allowed - user must be logged in
if (!isLoggedIn()) {
    if (in_array($action, ['download', 'view'])) {
        header("Location: ../login.php?redirect=" . urlencode('repository.php'));
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu untuk mengakses repositori.']);
    exit;
}

$currentUser = getCurrentUser();
$isSuperAdmin = ($currentUser['role'] ?? '') === 'superadmin';
$storageDir = realpath(__DIR__ . '/../uploads/repository') ?: (__DIR__ . '/../uploads/repository');

if (!is_dir($storageDir)) {
    @mkdir($storageDir, 0755, true);
}

// ----------------------------------------------------
// 1. ACTION: VIEW (Inline PDF Stream)
// ----------------------------------------------------
if ($action === 'view') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        die('ID dokumen tidak valid.');
    }

    $stmt = $pdo->prepare("SELECT * FROM repository_documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        die('Dokumen tidak ditemukan.');
    }

    $fileName = basename($doc['file_name']);
    $fullPath = realpath($storageDir . DIRECTORY_SEPARATOR . $fileName);

    // Verify path boundary
    if (!$fullPath || !file_exists($fullPath) || strpos($fullPath, realpath($storageDir)) !== 0) {
        http_response_code(404);
        die('File fisik tidak ditemukan pada server.');
    }

    $downloadName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $doc['original_name'] ?: 'dokumen_wi.pdf');
    if (!preg_match('/\.pdf$/i', $downloadName)) {
        $downloadName .= '.pdf';
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    readfile($fullPath);
    exit;
}

// ----------------------------------------------------
// 2. ACTION: DOWNLOAD (Attachment PDF Stream)
// ----------------------------------------------------
if ($action === 'download') {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        die('ID dokumen tidak valid.');
    }

    $stmt = $pdo->prepare("SELECT * FROM repository_documents WHERE id = ?");
    $stmt->execute([$id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doc) {
        http_response_code(404);
        die('Dokumen tidak ditemukan.');
    }

    $fileName = basename($doc['file_name']);
    $fullPath = realpath($storageDir . DIRECTORY_SEPARATOR . $fileName);

    if (!$fullPath || !file_exists($fullPath) || strpos($fullPath, realpath($storageDir)) !== 0) {
        http_response_code(404);
        die('File fisik tidak ditemukan pada server.');
    }

    $downloadName = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $doc['original_name'] ?: 'dokumen_wi.pdf');
    if (!preg_match('/\.pdf$/i', $downloadName)) {
        $downloadName .= '.pdf';
    }

    header('Content-Description: File Transfer');
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . filesize($fullPath));
    header('Content-Transfer-Encoding: binary');
    header('X-Content-Type-Options: nosniff');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

    readfile($fullPath);
    exit;
}

// All remaining actions return JSON
header('Content-Type: application/json');

// Ensure division, bagian, and sub_bagian columns exist
function ensureRepositoryColumnsExist($pdo) {
    static $checked = false;
    if ($checked) return;
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $columnExists = function ($table, $column) use ($pdo, $driver) {
        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
            $stmt->execute([$table, $column]);
            return (int) $stmt->fetchColumn() > 0;
        }
    };
    try {
        if (!$columnExists('repository_documents', 'division')) {
            $pdo->exec("ALTER TABLE repository_documents ADD COLUMN division VARCHAR(150) NULL DEFAULT 'Supply Chain Management'");
        }
        if (!$columnExists('repository_documents', 'bagian')) {
            $pdo->exec("ALTER TABLE repository_documents ADD COLUMN bagian VARCHAR(150) NULL");
        }
        if (!$columnExists('repository_documents', 'sub_bagian')) {
            $pdo->exec("ALTER TABLE repository_documents ADD COLUMN sub_bagian VARCHAR(150) NULL");
        }
    } catch (Exception $e) {}
    $checked = true;
}

// ----------------------------------------------------
// 3. ACTION: LIST
// ----------------------------------------------------
if ($action === 'list') {
    try {
        ensureRepositoryColumnsExist($pdo);
        $category = trim($_GET['category'] ?? '');
        $division = trim($_GET['division'] ?? '');
        $bagian = trim($_GET['bagian'] ?? '');
        $subBagian = trim($_GET['sub_bagian'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $sql = "SELECT id, title, document_code, category, division, bagian, sub_bagian, description, file_name, original_name, file_size, uploaded_by, created_at, updated_at FROM repository_documents WHERE 1=1";
        $params = [];

        if (!empty($category) && $category !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if (!empty($division) && $division !== 'all') {
            $sql .= " AND division = ?";
            $params[] = $division;
        }

        if (!empty($bagian) && $bagian !== 'all') {
            $sql .= " AND bagian = ?";
            $params[] = $bagian;
        }

        if (!empty($subBagian) && $subBagian !== 'all') {
            $sql .= " AND sub_bagian = ?";
            $params[] = $subBagian;
        }

        if (!empty($search)) {
            $sql .= " AND (title LIKE ? OR document_code LIKE ? OR description LIKE ? OR bagian LIKE ? OR sub_bagian LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format file sizes & timestamps for display
        foreach ($documents as &$doc) {
            $bytes = intval($doc['file_size']);
            if ($bytes >= 1048576) {
                $doc['formatted_size'] = number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                $doc['formatted_size'] = number_format($bytes / 1024, 1) . ' KB';
            } else {
                $doc['formatted_size'] = $bytes . ' B';
            }

            $time = strtotime($doc['created_at']);
            $doc['formatted_date'] = date('d M Y, H:i', $time) . ' WIB';
        }
        unset($doc);

        echo json_encode([
            'success' => true,
            'data' => $documents,
            'is_superadmin' => $isSuperAdmin
        ]);
    } catch (PDOException $e) {
        error_log('manage_repository.php list error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal memuat daftar dokumen.']);
    }
    exit;
}

// ----------------------------------------------------
// Verification for Write/Edit/Delete actions
// ----------------------------------------------------
$isRepoAdmin = ($currentUser['role'] ?? '') === 'repository_admin';
$allowedModules = is_array($currentUser['allowed_modules'] ?? []) ? $currentUser['allowed_modules'] : [];
$hasRepoMgmtAccess = $isSuperAdmin || $isRepoAdmin || in_array('repository_management', $allowedModules);

if (!$hasRepoMgmtAccess) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin untuk mengelola dokumen Work Instruction.']);
    exit;
}

// Validate CSRF on all write/mutating actions
validateCsrf();

if ($action === 'upload' || $action === 'update') {
    if (!$isSuperAdmin && !canAdd('repository_management')) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin untuk menambah atau mengubah dokumen.']);
        exit;
    }
} elseif ($action === 'delete') {
    if (!$isSuperAdmin && !canDelete('repository_management')) {
        echo json_encode(['success' => false, 'message' => 'Akses ditolak: Anda tidak memiliki izin untuk menghapus dokumen.']);
        exit;
    }
}

// ----------------------------------------------------
// 4. ACTION: UPLOAD
// ----------------------------------------------------
if ($action === 'upload') {
    try {
        ensureRepositoryColumnsExist($pdo);
        $title = trim($_POST['title'] ?? '');
        $allowedSegments = ['Policy Document', 'Procedure Document', 'Working Instruction (WI) Document'];
        $category = trim($_POST['category'] ?? 'Working Instruction (WI) Document');
        if (!in_array($category, $allowedSegments)) {
            $category = 'Working Instruction (WI) Document';
        }
        $division = trim($_POST['division'] ?? 'Supply Chain Management');
        $bagian = trim($_POST['bagian'] ?? '');
        $subBagian = trim($_POST['sub_bagian'] ?? '');
        $docCode = trim($_POST['document_code'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Judul Work Instruction wajib diisi.']);
            exit;
        }

        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            $errCode = $_FILES['pdf_file']['error'] ?? 'NO_FILE';
            echo json_encode(['success' => false, 'message' => 'File PDF belum dipilih atau gagal diunggah (Error Code: ' . $errCode . ').']);
            exit;
        }

        $file = $_FILES['pdf_file'];
        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        // 1. Strict extension check
        if ($ext !== 'pdf') {
            echo json_encode(['success' => false, 'message' => 'Hanya file format PDF (.pdf) yang diperbolehkan.']);
            exit;
        }

        // 2. File size limit (max 25MB)
        $maxSizeBytes = 25 * 1024 * 1024;
        if ($file['size'] > $maxSizeBytes) {
            echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimum ukuran file adalah 25MB.']);
            exit;
        }

        // 3. MIME type inspection
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if ($mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
            echo json_encode(['success' => false, 'message' => 'Validasi tipe MIME gagal. File bukan dokumen PDF yang valid.']);
            exit;
        }

        // 4. Magic bytes validation (%PDF-)
        $handle = fopen($file['tmp_name'], 'rb');
        $header = fread($handle, 5);
        fclose($handle);
        if ($header !== '%PDF-') {
            echo json_encode(['success' => false, 'message' => 'Validasi header file gagal. Format file korup atau bukan PDF murni.']);
            exit;
        }

        // 5. Store with randomized filename
        $uniqueFilename = 'wi_' . uniqid() . '_' . bin2hex(random_bytes(6)) . '.pdf';
        $destination = $storageDir . DIRECTORY_SEPARATOR . $uniqueFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file ke direktori server.']);
            exit;
        }

        $fileSize = filesize($destination);
        $filePath = 'uploads/repository/' . $uniqueFilename;

        // 6. Insert into database
        $stmt = $pdo->prepare("INSERT INTO repository_documents (title, document_code, category, division, bagian, sub_bagian, description, file_path, file_name, original_name, file_size, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title,
            $docCode,
            $category,
            $division,
            $bagian,
            $subBagian,
            $description,
            $filePath,
            $uniqueFilename,
            $origName,
            $fileSize,
            $currentUser['name'] ?: 'Super Admin'
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Dokumen Work Instruction berhasil diunggah!'
        ]);
    } catch (Throwable $e) {
        error_log('manage_repository.php upload error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem saat mengunggah dokumen.']);
    }
    exit;
}

// ----------------------------------------------------
// 5. ACTION: UPDATE
// ----------------------------------------------------
if ($action === 'update') {
    try {
        ensureRepositoryColumnsExist($pdo);
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID dokumen tidak valid.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM repository_documents WHERE id = ?");
        $stmt->execute([$id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existing) {
            echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $allowedSegments = ['Policy Document', 'Procedure Document', 'Working Instruction (WI) Document'];
        $category = trim($_POST['category'] ?? ($existing['category'] ?? 'Working Instruction (WI) Document'));
        if (!in_array($category, $allowedSegments)) {
            $category = 'Working Instruction (WI) Document';
        }
        $division = trim($_POST['division'] ?? ($existing['division'] ?? 'Supply Chain Management'));
        $bagian = trim($_POST['bagian'] ?? ($existing['bagian'] ?? ''));
        $subBagian = trim($_POST['sub_bagian'] ?? ($existing['sub_bagian'] ?? ''));
        $docCode = trim($_POST['document_code'] ?? ($existing['document_code'] ?? ''));
        $description = trim($_POST['description'] ?? ($existing['description'] ?? ''));

        if (empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Judul dokumen wajib diisi.']);
            exit;
        }

        // Check if new PDF file was uploaded to replace existing
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['pdf_file'];
            $origName = basename($file['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                echo json_encode(['success' => false, 'message' => 'File pengganti harus berformat PDF (.pdf).']);
                exit;
            }

            if ($file['size'] > 25 * 1024 * 1024) {
                echo json_encode(['success' => false, 'message' => 'Ukuran file pengganti melebihi batas 25MB.']);
                exit;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if ($mime !== 'application/pdf' && $mime !== 'application/x-pdf') {
                echo json_encode(['success' => false, 'message' => 'Tipe MIME file pengganti tidak valid.']);
                exit;
            }

            $handle = fopen($file['tmp_name'], 'rb');
            $header = fread($handle, 5);
            fclose($handle);
            if ($header !== '%PDF-') {
                echo json_encode(['success' => false, 'message' => 'Header file pengganti bukan PDF valid.']);
                exit;
            }

            $uniqueFilename = 'wi_' . uniqid() . '_' . bin2hex(random_bytes(6)) . '.pdf';
            $destination = $storageDir . DIRECTORY_SEPARATOR . $uniqueFilename;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file pengganti ke server.']);
                exit;
            }

            // Remove old physical file
            $oldPath = realpath($storageDir . DIRECTORY_SEPARATOR . basename($existing['file_name']));
            if ($oldPath && file_exists($oldPath) && strpos($oldPath, realpath($storageDir)) === 0) {
                @unlink($oldPath);
            }

            $fileSize = filesize($destination);
            $filePath = 'uploads/repository/' . $uniqueFilename;

            $updateStmt = $pdo->prepare("UPDATE repository_documents SET title = ?, document_code = ?, category = ?, division = ?, bagian = ?, sub_bagian = ?, description = ?, file_path = ?, file_name = ?, original_name = ?, file_size = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$title, $docCode, $category, $division, $bagian, $subBagian, $description, $filePath, $uniqueFilename, $origName, $fileSize, $id]);
        } else {
            // Update metadata only
            $updateStmt = $pdo->prepare("UPDATE repository_documents SET title = ?, document_code = ?, category = ?, division = ?, bagian = ?, sub_bagian = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $updateStmt->execute([$title, $docCode, $category, $division, $bagian, $subBagian, $description, $id]);
        }

        echo json_encode(['success' => true, 'message' => 'Dokumen berhasil diperbarui!']);
    } catch (Throwable $e) {
        error_log('manage_repository.php update error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui dokumen karena kesalahan sistem.']);
    }
    exit;
}

// ----------------------------------------------------
// 6. ACTION: DELETE (Super Admin Only)
// ----------------------------------------------------
if ($action === 'delete') {
    try {
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID dokumen tidak valid.']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM repository_documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$doc) {
            echo json_encode(['success' => false, 'message' => 'Dokumen tidak ditemukan.']);
            exit;
        }

        // Unlink physical file
        $fileName = basename($doc['file_name']);
        $filePath = realpath($storageDir . DIRECTORY_SEPARATOR . $fileName);
        if ($filePath && file_exists($filePath) && strpos($filePath, realpath($storageDir)) === 0) {
            @unlink($filePath);
        }

        // Delete from database
        $delStmt = $pdo->prepare("DELETE FROM repository_documents WHERE id = ?");
        $delStmt->execute([$id]);

        echo json_encode(['success' => true, 'message' => 'Dokumen Work Instruction berhasil dihapus.']);
    } catch (Throwable $e) {
        error_log('manage_repository.php delete error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus dokumen karena kesalahan sistem.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Aksi tidak dikenali.']);
