<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */

// ── Auth ──────────────────────────────────────────────────
$routes->get('/',      'AuthController::login');
$routes->get('login',  'AuthController::login');
$routes->post('login', 'AuthController::loginProcess');
$routes->post('logout', 'AuthController::logout');

// ── Admin ─────────────────────────────────────────────────
$routes->group('admin', ['filter' => 'auth:admin'], function($routes) {
    $routes->get('dashboard',      'Admin\DashboardController::index');

    // Centers
    $routes->get('centers',                'Admin\CenterController::index');
    $routes->get('centers/create',         'Admin\CenterController::create');
    $routes->post('centers/store',         'Admin\CenterController::store');
    $routes->get('centers/edit/(:num)',    'Admin\CenterController::edit/$1');
    $routes->post('centers/update/(:num)', 'Admin\CenterController::update/$1');
    $routes->get('centers/import',         'Admin\CenterController::importForm');
    $routes->post('centers/import',        'Admin\CenterController::importProcess');
    $routes->get('centers/template',       'Admin\CenterController::downloadTemplate');
    $routes->post('centers/toggle/(:num)',   'Admin\CenterController::toggle/$1');

    // Projects
    $routes->get('projects',                 'Admin\ProjectController::index');
    $routes->get('projects/create',          'Admin\ProjectController::create');
    $routes->post('projects/store',          'Admin\ProjectController::store');
    $routes->get('projects/(:num)/otp',      'Admin\ProjectController::showOtp/$1');
    $routes->post('projects/(:num)/otp/regenerate', 'Admin\ProjectController::regenerateOtp/$1');
    $routes->get('projects/(:num)/tickets',  'Admin\ProjectController::tickets/$1');
    $routes->get('projects/edit/(:num)',     'Admin\ProjectController::edit/$1');
    $routes->post('projects/update/(:num)',  'Admin\ProjectController::update/$1');
    $routes->post('projects/toggle/(:num)', 'Admin\ProjectController::toggle/$1');

    // Common Issues (dropdown list management)
    $routes->get('issues',                  'Admin\IssueController::index');
    $routes->post('issues/store',           'Admin\IssueController::store');
    $routes->post('issues/update/(:num)',   'Admin\IssueController::update/$1');
    $routes->post('issues/toggle/(:num)',   'Admin\IssueController::toggle/$1');
    $routes->post('issues/delete/(:num)',   'Admin\IssueController::delete/$1');

    // Attachment serving
    $routes->get('attachments/(:num)', 'Admin\TicketController::serveAttachment/$1');
    $routes->get('ping',               'Admin\DashboardController::ping');
    $routes->get('api/open-ticket-count', 'Admin\DashboardController::openTicketCount');

    // Tickets admin - add retag route
    $routes->post('tickets/retag/(:num)',         'Admin\TicketController::retag/$1');
    // Tickets (search MUST be before (:num) so it isn't matched as an ID)
    $routes->get('tickets',                       'Admin\TicketController::index');
    $routes->get('tickets/search',                'Admin\TicketController::search');
    $routes->get('tickets/download',              'Admin\TicketController::downloadReport');
    $routes->get('tickets/(:num)',                'Admin\TicketController::view/$1');
    $routes->post('tickets/update-status/(:num)', 'Admin\TicketController::updateStatus/$1');
});

// ── Center Portal ─────────────────────────────────────────
$routes->group('center', ['filter' => 'auth:center'], function($routes) {
    $routes->get('dashboard',              'Center\DashboardController::index');
    $routes->get('tickets/raise',          'Center\TicketController::raiseForm');
    $routes->post('tickets/raise',         'Center\TicketController::raiseStore');
    $routes->get('tickets',                'Center\TicketController::myTickets');
    $routes->get('tickets/(:num)/status',  'Center\TicketController::ticketStatus/$1');
    $routes->get('tickets/(:num)',         'Center\TicketController::view/$1');
    $routes->post('tickets/reopen/(:num)', 'Center\TicketController::reopen/$1');
    $routes->get('attachments/(:num)',     'Center\TicketController::serveAttachment/$1');
    $routes->get('ping',                   'Center\TicketController::pingSession');
});
