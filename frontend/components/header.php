<?php
// Shared Header Component
// Accepts $pageTitle (string, default: 'Dashboard Warehouse')
if (!isset($pageTitle)) {
    $pageTitle = 'Dashboard Warehouse';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Warehouse Management System - Lintasarta">
    <meta name="author" content="Lintasarta">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <link rel="icon" href="frontend/img/LogoLintas.png">
    <link href="frontend/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="frontend/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="frontend/vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="frontend/css/excel-upload.css?v=<?= time() ?>" rel="stylesheet">
    <link href="frontend/css/custom-datatables.css?v=<?= time() ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
