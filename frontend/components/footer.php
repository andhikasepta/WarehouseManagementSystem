<?php
if (!function_exists('getSystemAppVersion')) {
    if (defined('BACKEND_PATH')) {
        @include_once BACKEND_PATH . 'config/database.php';
    } else {
        @include_once __DIR__ . '/../../backend/config/database.php';
    }
}
$currentAppVer = function_exists('getSystemAppVersion') ? getSystemAppVersion($pdo ?? null) : 'Beta-v1.0.0';
?>
            <footer class="sticky-footer bg-white py-2" style="padding: 0.4rem 0 !important;">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto" style="font-size: 0.7rem; color: #94a3b8;">
                        <span><?php echo htmlspecialchars($currentAppVer); ?> &copy; PT. Aplikanusa Lintasarta</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Modal Detail Chart Data (Styled matching inbound PO status detail modal) -->
    <div class="modal fade" id="chartDetailModal" tabindex="-1" role="dialog" aria-labelledby="chartDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow" style="border-radius: 10px; background-color: #ffffff; overflow: hidden;">
                <!-- Header with distinct light background (matching inbound modal) -->
                <div class="modal-header border-bottom py-2.5 px-3.5 align-items-center" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0;">
                    <div>
                        <h5 class="modal-title font-weight-bold text-gray-800 my-auto" id="chartDetailModalLabel" style="line-height: 1.3;">
                            <span id="chartDetailTitle" class="font-weight-bold text-primary" style="font-size: 1.05rem;">Detail Data</span>
                        </h5>
                        <div class="mt-1 d-flex flex-wrap align-items-center" id="chartDetailSubtitle"></div>
                    </div>
                    <button type="button" class="close text-gray-600 my-auto" data-dismiss="modal" aria-label="Close" style="padding: 0.25rem 0.5rem; margin: 0;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Clean White Body -->
                <div class="modal-body p-3 bg-white" style="max-height: 60vh; overflow-y: auto;">
                    <!-- Table Container (Clean Minimalist White) -->
                    <div class="table-responsive border rounded p-1" style="border-color: #eaecf4 !important; overflow-y: visible !important;">
                        <table class="table text-center mb-0 w-100" id="chartDetailTable" style="font-size: 0.85rem;">
                            <thead class="bg-light text-gray-700 font-weight-bold" style="border-bottom: 2px solid #eaecf4; font-size: 0.85rem;">
                                <tr>
                                    <th class="py-2 border-top-0 text-center" style="width: 40px;">NO</th>
                                    <th class="py-2 border-top-0 text-left">SPEC CODE</th>
                                    <th class="py-2 border-top-0 text-left">REG NO</th>
                                    <th class="py-2 border-top-0 text-left">SPEC NAME</th>
                                    <th class="py-2 border-top-0 text-right">NBV</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 px-3.5 justify-content-between">
                    <div class="text-muted small">Total Records: <strong id="chartDetailRecordCount">0</strong></div>
                    <button type="button" class="btn btn-secondary btn-sm px-3 font-weight-bold" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="frontend/vendor/jquery/jquery.min.js"></script>
    <script src="frontend/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- CSRF Auto-Configuration (must run after jQuery) -->
    <script>
    (function() {
        var csrfMeta = document.querySelector('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

        // Auto-configure jQuery $.ajax to include CSRF token header
        if (window.jQuery && csrfToken) {
            $.ajaxSetup({
                beforeSend: function(xhr, settings) {
                    if (settings.type && settings.type.toUpperCase() !== 'GET') {
                        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
                    }
                }
            });
        }

        // Monkey-patch window.fetch to auto-inject CSRF header on mutating requests
        if (csrfToken) {
            var originalFetch = window.fetch;
            window.fetch = function(url, options) {
                options = options || {};
                var method = (options.method || 'GET').toUpperCase();
                if (method !== 'GET' && method !== 'HEAD') {
                    options.headers = options.headers || {};
                    // Support both Headers object and plain object
                    if (options.headers instanceof Headers) {
                        if (!options.headers.has('X-CSRF-TOKEN')) {
                            options.headers.set('X-CSRF-TOKEN', csrfToken);
                        }
                    } else {
                        if (!options.headers['X-CSRF-TOKEN']) {
                            options.headers['X-CSRF-TOKEN'] = csrfToken;
                        }
                    }
                }
                return originalFetch.call(this, url, options);
            };
        }

        // Expose token globally for manual use
        window.WMS_CSRF_TOKEN = csrfToken;
    })();
    </script>

    <!-- Core plugin JavaScript-->
    <script src="frontend/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="frontend/js/sb-admin-2.min.js"></script>

    <!-- Page level plugins for DataTables -->
    <script src="frontend/vendor/datatables/jquery.dataTables.min.js"></script>
    <script src="frontend/vendor/datatables/dataTables.bootstrap4.min.js"></script>

    <!-- Global Modal Scroll Lock Handler -->
    <script>
    if (typeof $ !== 'undefined') {
        $(document).on('show.bs.modal', '.modal', function () {
            $('body').addClass('modal-open').css('overflow', 'hidden');
        });

        $(document).on('hidden.bs.modal', '.modal', function () {
            if ($('.modal:visible').length === 0) {
                $('body').removeClass('modal-open').css('overflow', '');
            } else {
                $('body').addClass('modal-open').css('overflow', 'hidden');
            }
        });
    }
    </script>
