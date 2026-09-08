<?php
/**
 * WMS SPA Front Controller
 * 
 * Single entry point for all WMS pages. The URL stays as wmsdev.my.id/
 * - If not logged in: renders the login page
 * - If logged in: renders the SPA shell with AJAX-loaded content
 * - repository.php is excluded (accessed directly)
 */

require_once __DIR__ . '/backend/paths.php';
require_once BACKEND_PATH . 'auth.php';

// ── Handle /repository directly in Front Controller ────────────────
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if ($requestPath === '/repository' || $requestPath === '/repository/') {
    require FRONTEND_PATH . 'pages/repository.php';
    exit;
}
if ($requestPath === '/repository.php') {
    header("Location: /repository", true, 301);
    exit;
}

// ── Handle /portal or view=portal directly in Front Controller ─────
if ($requestPath === '/portal' || $requestPath === '/portal/' || ($_GET['view'] ?? '') === 'portal') {
    require FRONTEND_PATH . 'pages/index.php';
    exit;
}
if ($requestPath === '/portal.php') {
    header("Location: /portal", true, 301);
    exit;
}

// ── Handle Logout ──────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    
    $reasonParam = isset($_GET['reason']) ? '&reason=' . urlencode($_GET['reason']) : '';
    $redirectParam = isset($_GET['redirect']) ? '&redirect=' . urlencode($_GET['redirect']) : '';
    header("Location: /?view=login" . $reasonParam . $redirectParam);
    exit;
}

// ── Not logged in ──────────────────────────────────────────────────
if (!isLoggedIn()) {
    $view = $_GET['view'] ?? '';
    // Show login if explicitly requested, if submitting form, or if error/redirect query params exist
    if ($view === 'login' || $_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['reason']) || isset($_GET['access_denied']) || isset($_GET['redirect'])) {
        include FRONTEND_PATH . 'pages/login.php';
    } else {
        // Default entry point: 3-card Landing Page Portal
        include FRONTEND_PATH . 'pages/index.php';
    }
    exit;
}

// ── Logged in → render SPA shell ───────────────────────────────────
$currentUser = getCurrentUser();
$spaRoutes = include BACKEND_PATH . 'spa_routes.php';

/**
 * Determine the default page to load based on user role and permissions.
 * Superadmin-configured allowed_modules takes highest priority.
 */
function getDefaultSpaPage($user, $routes) {
    $role = $user['role'] ?? '';
    $allowed = is_array($user['allowed_modules'] ?? null) ? $user['allowed_modules'] : [];

    if ($role === 'superadmin') return 'user_management';
    if ($role === 'repository_admin') return 'repository_management';

    // 1. If user has explicit allowed_modules assigned in User Management, prioritize them
    if (!empty($allowed)) {
        // Preferred landing order if multiple modules assigned
        $preferredOrder = ['dashboard', 'inbound', 'warehouse', 'storage_hub', 'outbound', 'kpi_monitoring', 'master_data', 'reports', 'analytics', 'repository_management'];
        foreach ($preferredOrder as $mod) {
            if (in_array($mod, $allowed) && isset($routes[$mod])) {
                return $mod;
            }
        }
        // Sub-module checks
        if (in_array('master_data_inbound', $allowed) || in_array('master_data_storage', $allowed) || in_array('master_data_outbound', $allowed)) {
            return 'master_data';
        }
        if (in_array('site_location', $allowed)) {
            return 'site_location';
        }
        foreach ($allowed as $mod) {
            if (isset($routes[$mod])) {
                return $mod;
            }
        }
    }

    // 2. Role fallback if allowed_modules is empty
    $roleMap = [
        'inbound_admin' => 'inbound',
        'warehouse_admin' => 'warehouse',
        'head_warehouse_admin' => 'warehouse',
        'head_asset_warehouse_admin' => 'warehouse',
        'outbound_admin' => 'outbound',
        'repository_admin' => 'repository_management',
        'outsourcing' => 'dashboard'
    ];
    if (isset($roleMap[$role]) && isset($routes[$roleMap[$role]])) {
        return $roleMap[$role];
    }

    return 'dashboard';
}

$defaultPage = getDefaultSpaPage($currentUser, $spaRoutes);

$pageTitle = 'WMS - PT. Aplikanusa Lintasarta';
include FRONTEND_PATH . 'components/header.php';
?>

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper" class="d-flex flex-column min-vh-100 bg-light">
            <div id="content" class="flex-grow-1">

                <?php
                $activePage = ''; // Will be managed by JavaScript
                include FRONTEND_PATH . 'components/navbar.php';
                ?>

                <!-- SPA Content Area -->
                <div id="spa-content">
                    <div class="d-flex justify-content-center align-items-center" style="padding-top: 200px;">
                        <div class="text-center">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <div class="text-muted small">Memuat halaman...</div>
                        </div>
                    </div>
                </div>

            </div>
            <?php include FRONTEND_PATH . 'components/footer.php'; ?>
        </div>
    </div>

    <!-- SPA Router Script -->
    <script>
    (function() {
        'use strict';

        var SPA = {
            currentPage: '',
            defaultPage: '<?php echo htmlspecialchars($defaultPage); ?>',
            isLoading: false,
            loadingHtml: '<div class="d-flex justify-content-center align-items-center" style="padding-top: 200px;">' +
                '<div class="text-center">' +
                '<div class="spinner-border text-primary mb-3" role="status" style="width: 2.5rem; height: 2.5rem;">' +
                '<span class="sr-only">Loading...</span></div>' +
                '<div class="text-muted small">Memuat halaman...</div>' +
                '</div></div>',

            init: function() {
                this.bindNavigation();
                this.bindPopState();
                
                // Track user ID to prevent cross-account cache collision
                var currentUserId = '<?php echo (int)($currentUser['id'] ?? 0); ?>';
                var lastUserId = sessionStorage.getItem('wms_spa_user_id');
                if (lastUserId !== currentUserId) {
                    sessionStorage.setItem('wms_spa_user_id', currentUserId);
                    sessionStorage.removeItem('wms_spa_active_page');
                }
                
                // Load initial page: always use user's permitted default page on login/switch
                var savedPage = sessionStorage.getItem('wms_spa_active_page');
                var hash = window.location.hash.replace('#', '');
                var initialPage = hash || savedPage || this.defaultPage;
                
                // Clear any leftover hash from the URL bar immediately
                if (window.location.hash) {
                    window.history.replaceState({ page: initialPage }, '', window.location.pathname);
                }
                
                var noPeriodPages = ['master_data', 'master_data_detail', 'site_location', 'user_management', 'announcements', 'repository_management', 'wms_select'];
                if (noPeriodPages.indexOf(initialPage) !== -1) {
                    $('#nav-item-period-selector, #periodDropdown').closest('.nav-item').hide();
                } else if (initialPage === 'kpi_monitoring') {
                    $('#month-select-group, #period-month-select').closest('.form-group').hide();
                    $('#batch-select-group, #period-batch-select').closest('.form-group').hide();
                    $('#site-select-group').hide();
                }

                this.loadPage(initialPage, false);
            },

            bindNavigation: function() {
                var self = this;

                // Intercept sidebar nav clicks
                $(document).on('click', '#wms-sidebar a.nav-link[data-spa-page]', function(e) {
                    e.preventDefault();
                    var page = $(this).data('spa-page');
                    if (page && page !== self.currentPage) {
                        self.loadPage(page, true);
                    }
                });

                // Intercept sidebar sub-link clicks
                $(document).on('click', '#wms-sidebar a.sub-link[data-spa-page]', function(e) {
                    e.preventDefault();
                    var page = $(this).data('spa-page');
                    if (page && page !== self.currentPage) {
                        self.loadPage(page, true);
                    }
                });

                // Intercept any other internal SPA links
                $(document).on('click', 'a[data-spa-page]', function(e) {
                    e.preventDefault();
                    var page = $(this).data('spa-page');
                    if (page) {
                        self.loadPage(page, true);
                    }
                });
            },

            bindPopState: function() {
                var self = this;
                window.addEventListener('popstate', function(e) {
                    if (e.state && e.state.page) {
                        self.loadPage(e.state.page, false);
                    }
                });
            },

            loadPage: function(pageName, pushState) {
                if (this.isLoading) return;
                this.isLoading = true;

                var self = this;
                var $content = $('#spa-content');

                // Show loading spinner
                $content.html(this.loadingHtml);

                // Cleanup before loading new page
                this.cleanup();

                $.ajax({
                    url: 'api/spa_load.php',
                    method: 'GET',
                    data: { page: pageName },
                    dataType: 'html',
                    success: function(html) {
                        // Insert content — jQuery .html() executes inline scripts
                        $content.html(html);
                        self.currentPage = pageName;
                        self.updateActiveNav(pageName);

                        // Save current page state
                        sessionStorage.setItem('wms_spa_active_page', pageName);

                        // Toggle navbar period dropdown visibility based on active page
                        var noPeriodPages = ['master_data', 'master_data_detail', 'site_location', 'user_management', 'announcements', 'repository_management', 'wms_select'];
                        if (noPeriodPages.indexOf(pageName) !== -1) {
                            $('#nav-item-period-selector, #periodDropdown').closest('.nav-item').hide();
                        } else {
                            $('#nav-item-period-selector, #periodDropdown').closest('.nav-item').show();
                            // KPI Monitoring shows only Tahun in period dropdown
                            if (pageName === 'kpi_monitoring') {
                                $('#month-select-group, #period-month-select').closest('.form-group').hide();
                                $('#batch-select-group, #period-batch-select').closest('.form-group').hide();
                                $('#site-select-group').hide();
                            } else {
                                $('#month-select-group, #period-month-select').closest('.form-group').show();
                                if (pageName !== 'storage_hub') {
                                    $('#site-select-group').hide();
                                }
                                $('#batch-select-group, #period-batch-select').closest('.form-group').show();
                            }
                        }

                        // Keep browser address bar strictly clean as / (NO # in URL)
                        if (pushState) {
                            window.history.pushState({ page: pageName }, '', window.location.pathname);
                        } else {
                            window.history.replaceState({ page: pageName }, '', window.location.pathname);
                        }

                        // Scroll to top
                        window.scrollTo(0, 0);
                    },
                    error: function(xhr) {
                        if (xhr.status === 401) {
                            // Session expired — reload to show login
                            window.location.reload();
                            return;
                        }
                        if (xhr.status === 404) {
                            $content.html(xhr.responseText || '<div class="text-center py-5"><h4>Halaman tidak ditemukan</h4></div>');
                        } else {
                            $content.html(
                                '<div class="d-flex justify-content-center align-items-center" style="padding-top: 200px;">' +
                                '<div class="text-center">' +
                                '<div class="mb-3"><i class="fas fa-exclamation-triangle fa-3x text-danger"></i></div>' +
                                '<h5 class="text-gray-800">Gagal memuat halaman</h5>' +
                                '<p class="text-muted">Terjadi kesalahan saat memuat konten.</p>' +
                                '<button class="btn btn-primary btn-sm" onclick="WMS_SPA.loadPage(\'' + pageName + '\', false);">' +
                                '<i class="fas fa-redo mr-1"></i>Coba Lagi</button>' +
                                '</div></div>'
                            );
                        }
                    },
                    complete: function() {
                        self.isLoading = false;
                    }
                });
            },

            cleanup: function() {
                // Destroy DataTables to prevent memory leaks
                try {
                    $('#spa-content .dataTable').each(function() {
                        if ($.fn.DataTable.isDataTable(this)) {
                            $(this).DataTable().destroy();
                        }
                    });
                } catch(e) {}

                // Destroy any Chart.js instances
                try {
                    if (window.Chart) {
                        var canvases = document.querySelectorAll('#spa-content canvas');
                        canvases.forEach(function(canvas) {
                            var chart = Chart.getChart(canvas);
                            if (chart) chart.destroy();
                        });
                    }
                } catch(e) {}

                // Close any open modals
                try {
                    $('#spa-content .modal.show').modal('hide');
                    $('body').removeClass('modal-open');
                    $('.modal-backdrop').remove();
                } catch(e) {}

                // Clear any Select2 instances
                try {
                    $('#spa-content .select2-hidden-accessible').each(function() {
                        $(this).select2('destroy');
                    });
                } catch(e) {}
            },

            updateActiveNav: function(pageName) {
                var $sidebar = $('#wms-sidebar');
                
                // Remove all active states
                $sidebar.find('.nav-item').removeClass('active');
                $sidebar.find('.sub-link').removeClass('active');
                
                // Find and activate the matching nav item
                var $navLink = $sidebar.find('a[data-spa-page="' + pageName + '"]');
                if ($navLink.length) {
                    // Direct nav link
                    $navLink.closest('.nav-item').addClass('active');
                    
                    // If it's a sub-link, also activate parent and expand collapse
                    if ($navLink.hasClass('sub-link')) {
                        $navLink.addClass('active');
                        var $collapse = $navLink.closest('.collapse');
                        if ($collapse.length && !$collapse.hasClass('show')) {
                            $collapse.collapse('show');
                        }
                        $collapse.closest('.nav-item').addClass('active');
                    }
                }
            }
        };

        // Expose globally for retry buttons etc.
        window.WMS_SPA = SPA;

        // Initialize when DOM is ready
        $(document).ready(function() {
            SPA.init();
        });
    })();
    </script>

</body>
</html>