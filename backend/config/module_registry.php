<?php
/**
 * Module Registry Configuration
 * 
 * Central registry for all modules, sub-pages, access routes, and UAC permissions (view, add, delete).
 * Adding a new page or sub-page here will automatically:
 * 1. Register page access routing in backend/auth.php
 * 2. Render permission checkboxes (View, Add/Edit, Delete) in User Management
 * 3. Support role assignments and badges across the system
 */

return [
    'dashboard' => [
        'label' => 'Dashboard Overview',
        'icon'  => 'fas fa-th-large text-primary',
        'badge_class' => 'badge-secondary',
        'section' => 'overview',
        'pages' => ['dashboard.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
    'inbound' => [
        'label' => 'Inbound Management',
        'icon'  => 'fas fa-box-open text-primary',
        'badge_class' => 'badge-success',
        'section' => 'main_menu',
        'pages' => ['inbound.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
    'warehouse' => [
        'label' => 'Storage Management',
        'icon'  => 'fas fa-warehouse text-primary',
        'badge_class' => 'badge-primary',
        'section' => 'main_menu',
        'pages' => ['warehouse.php', 'storage_hub.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
    'outbound' => [
        'label' => 'Outbound Management',
        'icon'  => 'fas fa-truck-loading text-primary',
        'badge_class' => 'badge-warning text-white',
        'section' => 'main_menu',
        'pages' => ['outbound.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
    'kpi_monitoring' => [
        'label' => 'KPI Monitoring',
        'icon'  => 'fas fa-tachometer-alt text-primary',
        'badge_class' => 'badge-info',
        'section' => 'main_menu',
        'pages' => ['kpi_monitoring.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
    'master_data' => [
        'label' => 'Master Data',
        'icon'  => 'fas fa-database text-primary',
        'badge_class' => 'badge-dark',
        'section' => 'data_settings',
        'pages' => ['master_data.php', 'master_data_select.php'],
        'permissions' => ['view', 'add', 'delete'],
        'children' => [
            'master_data_inbound' => [
                'label' => 'Inbound Master Data',
                'icon'  => 'fas fa-box-open text-success',
                'pages' => []
            ],
            'master_data_storage' => [
                'label' => 'Storage Master Data',
                'icon'  => 'fas fa-warehouse text-primary',
                'pages' => []
            ],
            'master_data_outbound' => [
                'label' => 'Outbound Master Data',
                'icon'  => 'fas fa-truck-loading text-warning',
                'pages' => []
            ],
            'site_location' => [
                'label' => 'Site Location Warehouse',
                'icon'  => 'fas fa-map-marked-alt text-success',
                'pages' => ['site_location.php'],
                'permissions' => ['view', 'add', 'delete']
            ],
        ],
    ],
    'reports' => [
        'label' => 'Reports',
        'icon'  => 'fas fa-file-alt text-primary',
        'badge_class' => 'badge-info',
        'section' => 'report',
        'pages' => ['reports.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
    'analytics' => [
        'label' => 'Analytics',
        'icon'  => 'fas fa-chart-line text-primary',
        'badge_class' => 'badge-info',
        'section' => 'report',
        'pages' => ['analytics.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
    'repository_management' => [
        'label' => 'Repository Documents (WI)',
        'icon'  => 'fas fa-folder-open text-primary',
        'badge_class' => 'badge-teal text-white',
        'badge_style' => 'background-color:#20c997;',
        'section' => 'repository',
        'pages' => ['repository_management.php'],
        'permissions' => ['view', 'add', 'delete'],
    ],
];
