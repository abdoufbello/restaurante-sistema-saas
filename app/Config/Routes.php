<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

// Restaurant Authentication and Admin Routes
$routes->group('auth', function($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::authenticate');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::store');
    $routes->get('logout', 'Auth::logout');
});

// Privacy and LGPD compliance routes
$routes->group('privacy', ['filter' => 'security'], static function ($routes) {
    $routes->get('consent', 'PrivacyController::consent');
    $routes->post('process-consent', 'PrivacyController::processConsent');
    $routes->get('data-export', 'PrivacyController::dataExport');
    $routes->post('request-export', 'PrivacyController::requestDataExport');
    $routes->get('download-export/(:num)', 'PrivacyController::downloadExport/$1');
    $routes->get('data-deletion', 'PrivacyController::dataDeletion');
    $routes->post('request-deletion', 'PrivacyController::requestDataDeletion');
    $routes->get('policy', 'PrivacyController::policy');
});

// Dashboard (requires authentication and tenant isolation)
$routes->group('dashboard', ['filter' => 'auth|tenant|security'], function($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->post('update-profile', 'Dashboard::updateProfile');
    $routes->post('change-password', 'Dashboard::changePassword');
    
    // Employee Management
    $routes->get('employees', 'Dashboard::employees');
    $routes->post('employees/create', 'Dashboard::createEmployee', ['filter' => 'usage:employee']);
    $routes->post('employees/update/(:num)', 'Dashboard::updateEmployee/$1');
    $routes->post('employees/delete/(:num)', 'Dashboard::deleteEmployee/$1');
    
    // Restaurant Management
    $routes->get('restaurant', 'Dashboard::restaurant');
    $routes->post('restaurant/update', 'Dashboard::updateRestaurant');
    
    // Category Management
    $routes->get('categories', 'Categories::index');
    $routes->post('categories/create', 'Categories::create');
    $routes->post('categories/update/(:num)', 'Categories::update/$1');
    $routes->post('categories/delete/(:num)', 'Categories::delete/$1');
    $routes->post('categories/reorder', 'Categories::reorder');
    
    // Dish Management
    $routes->get('dishes', 'Dishes::index');
    $routes->get('dishes/category/(:num)', 'Dishes::byCategory/$1');
    $routes->post('dishes/create', 'Dishes::create');
    $routes->post('dishes/update/(:num)', 'Dishes::update/$1');
    $routes->post('dishes/delete/(:num)', 'Dishes::delete/$1');
    $routes->post('dishes/toggle-availability/(:num)', 'Dishes::toggleAvailability/$1');
    $routes->post('dishes/toggle-featured/(:num)', 'Dishes::toggleFeatured/$1');
    
    // Order Management
    $routes->get('orders', 'Orders::index');
    $routes->get('orders/(:num)', 'Orders::view/$1');
    $routes->post('orders/update-status/(:num)', 'Orders::updateStatus/$1');
    $routes->post('orders/cancel/(:num)', 'Orders::cancel/$1');
    
    // Reports and Analytics
    $routes->get('reports', 'Reports::index');
    $routes->get('reports/sales', 'Reports::sales');
    $routes->get('reports/dishes', 'Reports::dishes');
    $routes->get('reports/export', 'Reports::export');
    
    // Subscription Management
    $routes->get('subscription', 'SubscriptionController::index');
    $routes->get('subscription/plans', 'SubscriptionController::plans');
    $routes->get('subscription/start-trial', 'SubscriptionController::startTrial');
    $routes->get('subscription/subscribe/(:segment)', 'SubscriptionController::subscribe/$1');
    $routes->get('subscription/change-plan/(:segment)', 'SubscriptionController::changePlan/$1');
    $routes->post('subscription/cancel', 'SubscriptionController::cancel');
    $routes->get('subscription/usage', 'SubscriptionController::usage');
});

// ========================================
// API ROUTES
// ========================================

$routes->group('api', ['namespace' => 'App\Controllers\Api'], function($routes) {
    
    // ========================================
    // AUTHENTICATION ROUTES
    // ========================================
    
    $routes->group('auth', function($routes) {
        $routes->post('login', 'AuthController::login');
        $routes->post('register', 'AuthController::register');
        $routes->post('refresh', 'AuthController::refresh');
        $routes->post('logout', 'AuthController::logout', ['filter' => 'jwt_auth']);
        $routes->post('logout-all', 'AuthController::logoutAll', ['filter' => 'jwt_auth']);
        $routes->post('forgot-password', 'AuthController::forgotPassword');
        $routes->post('reset-password', 'AuthController::resetPassword');
        $routes->get('me', 'AuthController::me', ['filter' => 'jwt_auth']);
        $routes->put('profile', 'AuthController::updateProfile', ['filter' => 'jwt_auth']);
        $routes->put('password', 'AuthController::changePassword', ['filter' => 'jwt_auth']);
        $routes->get('sessions', 'AuthController::getSessions', ['filter' => 'jwt_auth']);
        $routes->delete('sessions/(:num)', 'AuthController::revokeSession/$1', ['filter' => 'jwt_auth']);
        $routes->get('verify-token', 'AuthController::verifyToken', ['filter' => 'jwt_auth']);
    });
    
    // ========================================
    // PROTECTED API ROUTES
    // ========================================
    
    $routes->group('', ['filter' => 'jwt_auth'], function($routes) {
        
        // ========================================
        // USERS ROUTES
        // ========================================
        
        $routes->group('users', function($routes) {
            $routes->get('', 'UsersController::index');
            $routes->get('search', 'UsersController::search');
            $routes->get('stats', 'UsersController::stats');
            $routes->get('(:num)', 'UsersController::show/$1');
            $routes->post('', 'UsersController::create');
            $routes->put('(:num)', 'UsersController::update/$1');
            $routes->delete('(:num)', 'UsersController::delete/$1');
            $routes->put('(:num)/password', 'UsersController::changePassword/$1');
            $routes->put('(:num)/status', 'UsersController::changeStatus/$1');
        });
        
        // ========================================
        // PRODUCTS ROUTES
        // ========================================
        
        $routes->group('products', function($routes) {
            $routes->get('', 'ProductsController::index');
            $routes->get('search', 'ProductsController::search');
            $routes->get('stats', 'ProductsController::stats');
            $routes->get('(:num)', 'ProductsController::show/$1');
            $routes->post('', 'ProductsController::create');
            $routes->put('(:num)', 'ProductsController::update/$1');
            $routes->delete('(:num)', 'ProductsController::delete/$1');
            $routes->put('(:num)/stock', 'ProductsController::updateStock/$1');
        });
        
        // ========================================
        // ORDERS ROUTES
        // ========================================
        
        $routes->group('orders', function($routes) {
            $routes->get('', 'OrdersController::index');
            $routes->get('stats', 'OrdersController::stats');
            $routes->get('(:num)', 'OrdersController::show/$1');
            $routes->post('', 'OrdersController::create');
            $routes->put('(:num)/status', 'OrdersController::updateStatus/$1');
            $routes->put('(:num)/cancel', 'OrdersController::cancel/$1');
        });
        
        // ========================================
        // CATEGORIES ROUTES
        // ========================================
        
        $routes->group('categories', function($routes) {
            $routes->get('', 'CategoriesController::index');
            $routes->get('search', 'CategoriesController::search');
            $routes->get('stats', 'CategoriesController::stats');
            $routes->get('(:num)', 'CategoriesController::show/$1');
            $routes->post('', 'CategoriesController::create');
            $routes->put('(:num)', 'CategoriesController::update/$1');
            $routes->delete('(:num)', 'CategoriesController::delete/$1');
        });
        
        // ========================================
        // CUSTOMERS ROUTES
        // ========================================
        
        $routes->group('customers', function($routes) {
            $routes->get('', 'CustomersController::index');
            $routes->get('search', 'CustomersController::search');
            $routes->get('stats', 'CustomersController::stats');
            $routes->get('(:num)', 'CustomersController::show/$1');
            $routes->post('', 'CustomersController::create');
            $routes->put('(:num)', 'CustomersController::update/$1');
            $routes->delete('(:num)', 'CustomersController::delete/$1');
            $routes->get('(:num)/orders', 'CustomersController::getOrders/$1');
        });
        
        // ========================================
        // PAYMENTS ROUTES
        // ========================================
        
        $routes->group('payments', function($routes) {
            $routes->get('', 'PaymentsController::index');
            $routes->get('stats', 'PaymentsController::stats');
            $routes->get('(:num)', 'PaymentsController::show/$1');
            $routes->post('', 'PaymentsController::create');
            $routes->put('(:num)/status', 'PaymentsController::updateStatus/$1');
            $routes->post('(:num)/refund', 'PaymentsController::refund/$1');
        });
        
        // ========================================
        // SUBSCRIPTIONS ROUTES
        // ========================================
        
        $routes->group('subscriptions', function($routes) {
            $routes->get('', 'SubscriptionsController::index');
            $routes->get('plans', 'SubscriptionsController::getPlans');
            $routes->get('current', 'SubscriptionsController::getCurrentSubscription');
            $routes->post('subscribe', 'SubscriptionsController::subscribe');
            $routes->put('upgrade', 'SubscriptionsController::upgrade');
            $routes->put('cancel', 'SubscriptionsController::cancel');
            $routes->get('invoices', 'SubscriptionsController::getInvoices');
            $routes->get('usage', 'SubscriptionsController::getUsage');
        });
        
        // ========================================
        // NOTIFICATIONS ROUTES
        // ========================================
        
        $routes->group('notifications', function($routes) {
            $routes->get('', 'NotificationsController::index');
            $routes->get('unread', 'NotificationsController::getUnread');
            $routes->get('stats', 'NotificationsController::stats');
            $routes->get('(:num)', 'NotificationsController::show/$1');
            $routes->post('', 'NotificationsController::create');
            $routes->put('(:num)/read', 'NotificationsController::markAsRead/$1');
            $routes->put('mark-all-read', 'NotificationsController::markAllAsRead');
            $routes->delete('(:num)', 'NotificationsController::delete/$1');
            $routes->post('send-bulk', 'NotificationsController::sendBulk');
        });
        
        // ========================================
        // ANALYTICS ROUTES
        // ========================================
        
        $routes->group('analytics', function($routes) {
            $routes->get('dashboard', 'AnalyticsController::dashboard');
            $routes->get('events', 'AnalyticsController::getEvents');
            $routes->post('events', 'AnalyticsController::trackEvent');
            $routes->get('stats', 'AnalyticsController::getStats');
            $routes->get('revenue', 'AnalyticsController::getRevenue');
            $routes->get('customers', 'AnalyticsController::getCustomerStats');
            $routes->get('products', 'AnalyticsController::getProductStats');
            $routes->get('performance', 'AnalyticsController::getPerformance');
            $routes->get('funnel', 'AnalyticsController::getFunnel');
            $routes->get('cohort', 'AnalyticsController::getCohort');
            $routes->get('export', 'AnalyticsController::exportData');
        });
        
        // ========================================
        // REPORTS ROUTES
        // ========================================
        
        $routes->group('reports', function($routes) {
            $routes->get('', 'ReportsController::index');
            $routes->get('templates', 'ReportsController::getTemplates');
            $routes->get('(:num)', 'ReportsController::show/$1');
            $routes->post('', 'ReportsController::create');
            $routes->put('(:num)', 'ReportsController::update/$1');
            $routes->delete('(:num)', 'ReportsController::delete/$1');
            $routes->post('(:num)/generate', 'ReportsController::generate/$1');
            $routes->get('(:num)/download', 'ReportsController::download/$1');
            $routes->post('(:num)/schedule', 'ReportsController::schedule/$1');
            $routes->put('(:num)/favorite', 'ReportsController::toggleFavorite/$1');
        });
        
        // ========================================
        // SETTINGS ROUTES
        // ========================================
        
        $routes->group('settings', function($routes) {
            $routes->get('', 'SettingsController::index');
            $routes->get('(:segment)', 'SettingsController::getGroup/$1');
            $routes->put('(:segment)', 'SettingsController::updateGroup/$1');
            $routes->post('backup', 'SettingsController::createBackup');
            $routes->get('backups', 'SettingsController::getBackups');
            $routes->post('restore/(:num)', 'SettingsController::restoreBackup/$1');
        });
        
        // ========================================
        // FILE UPLOAD ROUTES
        // ========================================
        
        $routes->group('uploads', function($routes) {
            $routes->post('image', 'UploadsController::uploadImage');
            $routes->post('file', 'UploadsController::uploadFile');
            $routes->delete('(:segment)', 'UploadsController::deleteFile/$1');
            $routes->get('(:segment)', 'UploadsController::getFile/$1');
        });
        
        // ========================================
        // PAYMENT GATEWAY ROUTES
        // ========================================
        
        $routes->group('payment-gateways', function($routes) {
            $routes->get('', 'PaymentGatewayController::index');
            $routes->post('', 'PaymentGatewayController::create');
            $routes->put('(:num)', 'PaymentGatewayController::update/$1');
            $routes->delete('(:num)', 'PaymentGatewayController::delete/$1');
            $routes->post('process', 'PaymentGatewayController::processPayment');
            $routes->get('status/(:num)', 'PaymentGatewayController::getPaymentStatus/$1');
            $routes->post('refund/(:num)', 'PaymentGatewayController::refundPayment/$1');
            $routes->get('transactions', 'PaymentGatewayController::getTransactions');
            $routes->get('stats', 'PaymentGatewayController::getPaymentStats');
        });
        
        // ========================================
        // DELIVERY INTEGRATION ROUTES
        // ========================================
        
        $routes->group('delivery-integrations', function($routes) {
            $routes->get('', 'DeliveryIntegrationController::index');
            $routes->post('', 'DeliveryIntegrationController::create');
            $routes->put('(:num)', 'DeliveryIntegrationController::update/$1');
            $routes->delete('(:num)', 'DeliveryIntegrationController::delete/$1');
            $routes->post('(:num)/test', 'DeliveryIntegrationController::testConnection/$1');
            $routes->post('(:num)/sync-menu', 'DeliveryIntegrationController::syncMenu/$1');
            $routes->get('(:num)/orders', 'DeliveryIntegrationController::getOrders/$1');
            $routes->put('orders/(:num)/status', 'DeliveryIntegrationController::updateOrderStatus/$1');
            $routes->get('orders', 'DeliveryIntegrationController::getAllOrders');
            $routes->get('orders/stats', 'DeliveryIntegrationController::getOrderStats');
            $routes->get('platforms', 'DeliveryIntegrationController::getAvailablePlatforms');
            $routes->get('sync-status/(:num)', 'DeliveryIntegrationController::getSyncStatus/$1');
        });
        
        // ========================================
        // LGPD COMPLIANCE ROUTES
        // ========================================
        
        $routes->group('lgpd', function($routes) {
            
            // Consent Management
            $routes->post('consent', 'LGPDController::recordConsent');
            $routes->delete('consent/(:segment)/(:segment)', 'LGPDController::revokeConsent/$1/$2');
            $routes->get('consent/(:segment)', 'LGPDController::checkConsent/$1');
            $routes->put('consent/(:segment)', 'LGPDController::updateConsentPreferences/$1');
            
            // Data Protection & Rights
            $routes->get('data-portability/(:segment)', 'LGPDController::requestDataPortability/$1');
            $routes->delete('data-erasure/(:segment)', 'LGPDController::requestDataErasure/$1');
            $routes->get('data-access-check', 'LGPDController::checkDataAccess');
            
            // Privacy Policy
            $routes->get('privacy-policy', 'LGPDController::getPrivacyPolicy');
            $routes->post('privacy-policy/accept', 'LGPDController::acceptPrivacyPolicy');
            $routes->get('privacy-policy/acceptance/(:segment)', 'LGPDController::checkPolicyAcceptance/$1');
            
            // Audit & Reporting (Admin only)
            $routes->get('audit/report', 'LGPDController::getAuditReport');
            $routes->get('audit/suspicious-activities', 'LGPDController::getSuspiciousActivities');
            $routes->post('audit/data-breach', 'LGPDController::reportDataBreach');
            
            // Administrative Functions (Admin only)
            $routes->post('admin/privacy-policy', 'LGPDController::createPrivacyPolicy');
            $routes->get('admin/policy-compliance/(:num)', 'LGPDController::analyzePolicyCompliance/$1');
            $routes->delete('admin/cleanup-logs', 'LGPDController::cleanupLogs');
            
            // Compliance Status
            $routes->get('compliance-status', 'LGPDController::getComplianceStatus');
        });
        
        // ========================================
        // SYSTEM ROUTES
        // ========================================
        
        $routes->group('system', function($routes) {
            $routes->get('info', 'SystemController::getInfo');
            $routes->get('health', 'SystemController::healthCheck');
            $routes->get('logs', 'SystemController::getLogs');
            $routes->post('cache/clear', 'SystemController::clearCache');
            $routes->get('database/status', 'SystemController::getDatabaseStatus');
            $routes->post('maintenance/enable', 'SystemController::enableMaintenance');
            $routes->post('maintenance/disable', 'SystemController::disableMaintenance');
        });
    });
});

// ========================================
// LGPD EXAMPLE PAGES
// ========================================

$routes->group('lgpd', function($routes) {
    $routes->get('example', 'LGPDExampleController::index');
    $routes->get('privacy-policy', 'LGPDExampleController::privacyPolicy');
    $routes->get('terms-of-use', 'LGPDExampleController::termsOfUse');
    $routes->get('data-subject-rights', 'LGPDExampleController::dataSubjectRights');
    $routes->get('privacy-settings', 'LGPDExampleController::privacySettings');
    $routes->post('accept-policy', 'LGPDExampleController::acceptPolicy');
    $routes->get('compliance-dashboard', 'LGPDExampleController::complianceDashboard');
    $routes->get('audit-reports', 'LGPDExampleController::auditReports');
});

// ========================================
// PUBLIC API ROUTES (sem autenticação)
// ========================================
    
    $routes->group('public', function($routes) {
        
        // Webhook routes
        $routes->group('webhooks', function($routes) {
            $routes->post('payment/(:segment)', 'WebhooksController::handlePayment/$1');
            $routes->post('subscription/(:segment)', 'WebhooksController::handleSubscription/$1');
            $routes->post('delivery/(:segment)', 'DeliveryIntegrationController::webhook/$1');
        });
        
        // Health check
        $routes->get('health', 'PublicController::health');
        
        // API documentation
        $routes->get('docs', 'PublicController::docs');
        
        // Rate limit info
        $routes->get('rate-limit', 'PublicController::rateLimit');
    });
    
    // ========================================
    // LEGACY API ROUTES (v1 - compatibilidade)
    // ========================================
    
    $routes->group('v1', ['filter' => 'apiauth|tenant'], function($routes) {
        // Menu API for Totems
        $routes->get('menu', 'Api\Menu::index');
        $routes->get('menu/categories', 'Api\Menu::categories');
        $routes->get('menu/dishes/(:num)', 'Api\Menu::dishes/$1');
        $routes->get('menu/featured', 'Api\Menu::featured');
        
        // Order API for Totems
        $routes->post('orders', 'Api\Orders::create');
        $routes->get('orders/(:segment)', 'Api\Orders::status/$1');
        $routes->post('orders/(:segment)/payment', 'Api\Orders::payment/$1');
    });
