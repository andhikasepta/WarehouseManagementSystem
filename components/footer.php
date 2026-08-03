<?php
if (!function_exists('getSystemAppVersion')) {
    @include_once __DIR__ . '/../config/database.php';
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

    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin-2.min.js"></script>
