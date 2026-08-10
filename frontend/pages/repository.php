<?php
// frontend/pages/repository.php - Standalone Warehouse Management Repository
require_once __DIR__ . '/../../backend/auth.php';

// Authentication guard: user must be logged in to view repository
if (!isLoggedIn()) {
    header("Location: login.php?redirect=repository.php");
    exit;
}

$user = getCurrentUser();
$isSuperAdmin = ($user['role'] ?? '') === 'superadmin';
$userRoleDisplay = 'User';
if ($user['role'] === 'superadmin') $userRoleDisplay = 'Super Admin';
elseif ($user['role'] === 'head_warehouse_admin' || $user['role'] === 'head_asset_warehouse_admin') $userRoleDisplay = 'Head Admin';
elseif ($user['role'] === 'inbound_admin') $userRoleDisplay = 'Inbound Admin';
elseif ($user['role'] === 'warehouse_admin') $userRoleDisplay = 'Storage Admin';
elseif ($user['role'] === 'outbound_admin') $userRoleDisplay = 'Outbound Admin';
elseif ($user['role'] === 'outsourcing') $userRoleDisplay = 'Outsourcing';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Asset & Warehouse Management Repository - PT. Aplikanusa Lintasarta">
    <meta name="author" content="PT. Aplikanusa Lintasarta">

    <title>Repository — PT. Aplikanusa Lintasarta</title>

    <link rel="icon" href="frontend/img/LogoLintas.png">
    <link href="frontend/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="frontend/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        :root {
            --repo-primary: #1e3e62;
            --repo-bg: #f8fafc;
            --repo-surface: #ffffff;
            --repo-text-dark: #0f172a;
            --repo-text-muted: #64748b;
            --repo-border: #e2e8f0;
        }

        body {
            font-family: 'DM Sans', 'Outfit', sans-serif;
            background-color: var(--repo-bg);
            color: var(--repo-text-dark);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            -webkit-font-smoothing: antialiased;
        }

        /* ── Top Bar ── */
        .repo-nav {
            background: linear-gradient(135deg, #0b192c 0%, #112236 50%, #1e3e62 100%);
            color: #ffffff;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        }

        .repo-nav__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1040px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .repo-nav__brand {
            display: flex;
            align-items: center;
            gap: 14px;
            text-decoration: none !important;
            color: #ffffff !important;
        }

        .repo-nav__logo-img {
            max-height: 46px;
            height: 46px;
            width: auto;
            filter: brightness(0) invert(1);
            transition: transform 0.2s ease;
        }

        .repo-nav__logo-img:hover {
            transform: scale(1.03);
        }

        .repo-nav__separator {
            width: 1.5px;
            height: 38px;
            background: rgba(255, 255, 255, 0.35);
        }

        .repo-nav__subtitle {
            font-size: 1rem;
            font-weight: 700;
            color: #93c5fd;
            letter-spacing: 0.3px;
        }

        .repo-nav__actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* ── User Dropdown ── */
        .repo-user-btn {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff !important;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 24px;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none !important;
        }

        .repo-user-btn:hover, .repo-user-btn:focus {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .repo-dropdown-menu {
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 6px 0;
            min-width: 200px;
            font-size: 0.85rem;
            margin-top: 8px;
        }

        .repo-dropdown-menu .dropdown-item {
            padding: 8px 16px;
            color: #334155;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .repo-dropdown-menu .dropdown-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .repo-dropdown-menu .dropdown-item.text-danger:hover {
            background: #fee2e2;
            color: #dc2626;
        }

        /* ── Hero Section ── */
        .repo-hero {
            padding: 45px 20px 25px 20px;
            text-align: center;
            max-width: 860px;
            margin: 0 auto;
        }

        .repo-hero__title {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            line-height: 1.2;
        }

        .repo-hero__title span {
            color: #2563eb;
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .repo-hero__description {
            color: #64748b;
            font-size: 1rem;
            line-height: 1.5;
            margin: 0 auto;
            max-width: 600px;
        }

        /* ── Main Section Container ── */
        .repo-main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px 50px 20px;
            flex-grow: 1;
            width: 100%;
        }

        /* ── Segment Section ── */
        .repo-section {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid var(--repo-border);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
            padding: 24px 28px;
            margin-bottom: 25px;
            transition: all 0.2s ease;
        }

        .repo-section__top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
        }

        .repo-section__header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .repo-section__icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .repo-section__title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .repo-section__count {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 12px;
        }

        /* ── Search Bar Toolbar ── */
        .repo-search-toolbar {
            margin-bottom: 25px;
        }

        .repo-search-box {
            position: relative;
            width: 100%;
        }

        .repo-search-box input {
            width: 100%;
            padding: 11px 16px 11px 42px;
            font-size: 0.9rem;
            border-radius: 14px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .repo-search-box input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .repo-search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.95rem;
        }

        /* ── Document Item List (Simple PDF File rows) ── */
        .privy-doc-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .privy-doc-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 18px;
            border-radius: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            gap: 16px;
        }

        .privy-doc-item:hover {
            border-color: #3b82f6;
            background: #fafcff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .privy-doc-item__left {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }

        .privy-doc-item__icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            background: #fee2e2;
            color: #dc2626;
        }

        .privy-doc-item__info {
            flex: 1;
            min-width: 0;
        }

        .privy-doc-item__name {
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ── Actions on Right (Preview & Download) ── */
        .privy-doc-item__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .privy-doc-item__action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none !important;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .action-preview {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .action-preview:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .action-download {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .action-download:hover {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 3px 10px rgba(37, 99, 235, 0.25);
        }

        .segment-empty-notice {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
        }

        /* ── Footer ── */
        .repo-footer {
            background: #ffffff;
            border-top: 1px solid #e2e8f0;
            padding: 20px 0;
            margin-top: auto;
            text-align: center;
            color: #64748b;
            font-size: 0.82rem;
        }

        @media (max-width: 768px) {
            .repo-hero__title {
                font-size: 1.65rem;
            }
            .privy-doc-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .privy-doc-item__actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
    </style>
</head>

<body>

    <!-- ── Top Header Navigation ── -->
    <nav class="repo-nav">
        <div class="repo-nav__inner">
            <a href="index.php" class="repo-nav__brand">
                <img src="frontend/img/Lintasarta.png" alt="Lintasarta" class="repo-nav__logo-img">
                <span class="repo-nav__separator"></span>
                <span class="repo-nav__subtitle">Asset &amp; Warehouse Repository</span>
            </a>
            <div class="repo-nav__actions">
                <!-- User Dropdown Menu for Logout & Portal -->
                <div class="dropdown">
                    <button class="repo-user-btn dropdown-toggle" type="button" id="userDropdownMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle text-info"></i>
                        <span><?php echo htmlspecialchars($user['name'] ?: $user['username']); ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right repo-dropdown-menu shadow" aria-labelledby="userDropdownMenu">
                        <a class="dropdown-item py-2" href="index.php">
                            <i class="fas fa-th mr-2 text-primary"></i> Landing Page Portal
                        </a>
                        <div class="dropdown-divider my-1"></div>
                        <a class="dropdown-item text-danger py-2" href="login.php?action=logout">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- ── Hero Section ── -->
    <header class="repo-hero">
        <h1 class="repo-hero__title">
            Sentralisasi Dokumen
        </h1>
        <p class="repo-hero__description">
            Repository Dokumen AWM &amp; Work Instruction (WI).
        </p>
    </header>

    <!-- ── Main Content Area ── -->
    <main class="repo-main">
        <!-- Search Bar Toolbar -->
        <div class="repo-search-toolbar">
            <div class="repo-search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchDocInput" placeholder="Cari nama dokumen PDF...">
            </div>
        </div>

        <!-- Loading State -->
        <div id="repoLoadingState" class="text-center py-5">
            <div class="spinner-border text-primary" role="status" style="width: 2.2rem; height: 2.2rem;">
                <span class="sr-only">Memuat...</span>
            </div>
            <p class="text-muted mt-2 small font-weight-bold">Memuat daftar file repositori...</p>
        </div>

        <!-- 3 Segments Container -->
        <div id="repoSegmentsContainer" style="display: none;">
            
            <!-- ── SEGMENT 1: Policy Document ── -->
            <section class="repo-section" id="section-policy">
                <div class="repo-section__top">
                    <div class="repo-section__header">
                        <div class="repo-section__icon" style="background: #eff6ff; color: #2563eb;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h2 class="repo-section__title">Policy Document</h2>
                        </div>
                    </div>
                    <span class="repo-section__count" id="count-policy">0 Documents</span>
                </div>
                <div class="privy-doc-list" id="list-policy">
                    <!-- Dynamically loaded -->
                </div>
                <div class="segment-empty-notice" id="empty-policy" style="display: none;">
                    <i class="fas fa-info-circle mr-1"></i> Belum ada dokumen Policy.
                </div>
            </section>

            <!-- ── SEGMENT 2: Procedure Document ── -->
            <section class="repo-section" id="section-procedure">
                <div class="repo-section__top">
                    <div class="repo-section__header">
                        <div class="repo-section__icon" style="background: #f0fdf4; color: #16a34a;">
                            <i class="fas fa-list"></i>
                        </div>
                        <div>
                            <h2 class="repo-section__title">Procedure Document</h2>
                        </div>
                    </div>
                    <span class="repo-section__count" id="count-procedure">0 Documents</span>
                </div>
                <div class="privy-doc-list" id="list-procedure">
                    <!-- Dynamically loaded -->
                </div>
                <div class="segment-empty-notice" id="empty-procedure" style="display: none;">
                    <i class="fas fa-info-circle mr-1"></i> Belum ada dokumen Procedure.
                </div>
            </section>

            <!-- ── SEGMENT 3: Working Instruction (WI) Document ── -->
            <section class="repo-section" id="section-wi">
                <div class="repo-section__top">
                    <div class="repo-section__header">
                        <div class="repo-section__icon" style="background: #fef2f2; color: #dc2626;">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div>
                            <h2 class="repo-section__title">Working Instruction (WI)</h2>
                        </div>
                    </div>
                    <span class="repo-section__count" id="count-wi">0 Documents</span>
                </div>
                <div class="privy-doc-list" id="list-wi">
                    <!-- Dynamically loaded -->
                </div>
                <div class="segment-empty-notice" id="empty-wi" style="display: none;">
                    <i class="fas fa-info-circle mr-1"></i> Belum ada dokumen Working Instruction (WI).
                </div>
            </section>

        </div>

        <!-- Global Empty State (when 0 documents match search across all segments) -->
        <div id="repoEmptyState" class="text-center py-5" style="display: none;">
            <div class="mb-3">
                <i class="fas fa-file-pdf fa-3x text-muted opacity-50"></i>
            </div>
            <h6 class="font-weight-bold text-gray-700 mb-1">Tidak Ada File PDF</h6>
            <p class="text-muted small mx-auto mb-3" style="max-width: 400px;">
                Tidak ada dokumen PDF yang sesuai dengan kata kunci pencarian Anda.
            </p>
        </div>
    </main>

    <!-- ── Footer ── -->
    <footer class="repo-footer">
        <div class="container">
            <p class="mb-0">
                &copy; <?php echo date('Y'); ?> PT. Aplikanusa Lintasarta. All rights reserved.
            </p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="frontend/vendor/jquery/jquery.min.js"></script>
    <script src="frontend/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
    (function () {
        let repoDocuments = [];
        let currentSearch = '';

        // Fetch Documents from API
        function loadDocuments() {
            $('#repoLoadingState').show();
            $('#repoSegmentsContainer').hide();
            $('#repoEmptyState').hide();

            let url = 'api/manage_repository.php?action=list';
            if (currentSearch.trim() !== '') {
                url += '&search=' + encodeURIComponent(currentSearch.trim());
            }

            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    $('#repoLoadingState').hide();
                    if (res && res.success) {
                        repoDocuments = res.data || [];
                        renderSegmentedDocuments(repoDocuments);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Memuat File',
                            text: (res && res.message) ? res.message : 'Terjadi kesalahan.'
                        });
                    }
                },
                error: function () {
                    $('#repoLoadingState').hide();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Koneksi',
                        text: 'Tidak dapat terhubung ke server.'
                    });
                }
            });
        }

        // Render 3 Segments
        function renderSegmentedDocuments(docs) {
            const isSearching = currentSearch.trim() !== '';

            if (!docs || docs.length === 0) {
                $('#repoSegmentsContainer').hide();
                $('#repoEmptyState').show();
                return;
            }

            $('#repoEmptyState').hide();
            $('#repoSegmentsContainer').show();

            const policyDocs = [];
            const procedureDocs = [];
            const wiDocs = [];

            docs.forEach(function (doc) {
                let cat = (doc.category || '').trim();
                if (cat === 'Policy Document') {
                    policyDocs.push(doc);
                } else if (cat === 'Procedure Document') {
                    procedureDocs.push(doc);
                } else {
                    // Default / WI
                    wiDocs.push(doc);
                }
            });

            // Render Segment 1: Policy
            renderSegmentList($('#list-policy'), $('#count-policy'), $('#empty-policy'), $('#section-policy'), policyDocs, isSearching);

            // Render Segment 2: Procedure
            renderSegmentList($('#list-procedure'), $('#count-procedure'), $('#empty-procedure'), $('#section-procedure'), procedureDocs, isSearching);

            // Render Segment 3: Working Instruction (WI)
            renderSegmentList($('#list-wi'), $('#count-wi'), $('#empty-wi'), $('#section-wi'), wiDocs, isSearching);
        }

        // Helper to render individual segment list
        function renderSegmentList(containerEl, countBadgeEl, emptyNoticeEl, sectionEl, items, isSearching) {
            containerEl.empty();
            countBadgeEl.text(items.length + ' Documents');

            if (items.length === 0) {
                if (isSearching) {
                    sectionEl.hide();
                } else {
                    sectionEl.show();
                    emptyNoticeEl.show();
                }
                return;
            }

            sectionEl.show();
            emptyNoticeEl.hide();

            items.forEach(function (doc) {
                let displayName = doc.title || doc.original_name || 'Dokumen.pdf';

                let itemHtml = `
                    <div class="privy-doc-item">
                        <div class="privy-doc-item__left">
                            <div class="privy-doc-item__icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="privy-doc-item__info">
                                <h6 class="privy-doc-item__name" title="${escapeHtml(displayName)}">${escapeHtml(displayName)}</h6>
                            </div>
                        </div>
                        <div class="privy-doc-item__actions">
                            <a href="api/manage_repository.php?action=view&id=${doc.id}" target="_blank" class="privy-doc-item__action-btn action-preview" title="Preview PDF">
                                <i class="fas fa-eye"></i> Preview
                            </a>
                            <a href="api/manage_repository.php?action=download&id=${doc.id}" class="privy-doc-item__action-btn action-download" title="Unduh File PDF">
                                <i class="fas fa-arrow-down"></i> Download
                            </a>
                        </div>
                    </div>
                `;
                containerEl.append(itemHtml);
            });
        }

        // Helper for XSS Escaping
        function escapeHtml(string) {
            if (!string) return '';
            const entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '/': '&#x2F;'
            };
            return String(string).replace(/[&<>"'\/]/g, function (s) {
                return entityMap[s];
            });
        }

        // Search Input
        let searchTimeout = null;
        $('#searchDocInput').on('input', function () {
            clearTimeout(searchTimeout);
            currentSearch = $(this).val();
            searchTimeout = setTimeout(function () {
                loadDocuments();
            }, 300);
        });

        // Initial Load
        loadDocuments();
    })();
    </script>
</body>
</html>
