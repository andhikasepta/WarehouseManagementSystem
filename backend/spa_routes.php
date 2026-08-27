<?php
/**
 * SPA Route Map
 * Maps page slugs to their frontend page files and module keys for access control.
 * Used by: index.php (front controller) and api/spa_load.php (AJAX loader)
 */

return [
    // Overview
    'dashboard' => [
        'file' => 'dashboard.php',
        'module' => 'dashboard',
        'active_page' => 'dashboard',
    ],
    
    // Main Menu
    'inbound' => [
        'file' => 'inbound.php',
        'module' => 'inbound',
        'active_page' => 'inbound',
    ],
    'warehouse' => [
        'file' => 'warehouse.php',
        'module' => 'warehouse',
        'active_page' => 'warehouse',
    ],
    'storage_hub' => [
        'file' => 'storage_hub.php',
        'module' => 'warehouse',
        'active_page' => 'storage_hub',
    ],
    'outbound' => [
        'file' => 'outbound.php',
        'module' => 'outbound',
        'active_page' => 'outbound',
    ],
    'kpi_monitoring' => [
        'file' => 'kpi_monitoring.php',
        'module' => 'kpi_monitoring',
        'active_page' => 'kpi_monitoring',
    ],

    // Data Settings
    'master_data' => [
        'file' => 'master_data_select.php',
        'module' => 'master_data',
        'active_page' => 'master_data',
    ],
    'master_data_detail' => [
        'file' => 'master_data.php',
        'module' => 'master_data',
        'active_page' => 'master_data',
    ],
    'site_location' => [
        'file' => 'site_location.php',
        'module' => 'master_data',
        'active_page' => 'master_data',
    ],

    // Reports
    'reports' => [
        'file' => 'reports.php',
        'module' => 'reports',
        'active_page' => 'reports',
    ],
    'analytics' => [
        'file' => 'analytics.php',
        'module' => 'analytics',
        'active_page' => 'analytics',
    ],

    // System
    'user_management' => [
        'file' => 'user_management.php',
        'module' => 'user_management',
        'active_page' => 'user_management',
    ],
    'announcements' => [
        'file' => 'announcements.php',
        'module' => 'user_management',
        'active_page' => 'announcements',
    ],
    'repository_management' => [
        'file' => 'repository_management.php',
        'module' => 'repository_management',
        'active_page' => 'repository_management',
    ],

    // Select pages
    'wms_select' => [
        'file' => 'wms_select.php',
        'module' => '',
        'active_page' => '',
    ],
];
