<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ── API Documentation (public, no auth required) ─────────────────────────────
$routes->get('api/docs', 'SwaggerController::ui');
$routes->get('api/docs/json', 'SwaggerController::json');

// Authentication Routes
$routes->group("auth", static function (RouteCollection $routes) {
    // GET
    $routes->get('login', 'Auth::index');
    $routes->get('logout', 'Auth::logout');
    // POST
    $routes->post('login', 'Auth::login_handler');
    // PUT
    // DELETE
});

// Admin Routes
$routes->group('admin', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('get_stats', 'Admin\Dashboard::get_stats');
    $routes->get('get_room_visits', 'Admin\Dashboard::get_room_visits');
    $routes->get('get_locations', 'Admin\Dashboard::get_locations');
    $routes->get('get_item_clean_count', 'Admin\Dashboard::get_item_clean_count');
    $routes->group('manage', static function (RouteCollection $routes) {

        // Management User
        $routes->group('user', static function (RouteCollection $routes) {
            // GET
            $routes->get('/', 'Admin\User::index');
            // POST
            $routes->post('get_datatable', 'Admin\User::get_datatable');
            $routes->post('modal', 'Admin\User::modal');
            $routes->post('add', 'Admin\User::add');
            // PUT
            $routes->put('edit', 'Admin\User::update');
            // DELETE
            $routes->delete('delete', 'Admin\User::delete');
        });

        // Management Location
        $routes->group('task', static function (RouteCollection $routes) {
            // GET
            $routes->get('/', 'Admin\Task::index');
            $routes->get('(:num)', 'Admin\Task::index/$1');
            $routes->get('(:num)/(:num)', 'Admin\Task::index/$1/$2');
            // POST
            $routes->post('get_datatable/location', 'Admin\Task::get_datatable_location');
            $routes->post('get_datatable/item', 'Admin\Task::get_datatable_item');
            $routes->post('get_datatable/action', 'Admin\Task::get_datatable_action');
            $routes->post('modal/location', 'Admin\Task::modal_location');
            $routes->post('modal/item', 'Admin\Task::modal_item');
            $routes->post('modal/action', 'Admin\Task::modal_action');
            $routes->post('add/location', 'Admin\Task::add_location');
            $routes->post('add/item', 'Admin\Task::add_item');
            $routes->post('add/action', 'Admin\Task::add_action');
            // PUT
            $routes->put('edit/location', 'Admin\Task::update_location');
            $routes->put('edit/item', 'Admin\Task::update_item');
            $routes->put('edit/action', 'Admin\Task::update_action');
            // DELETE
            $routes->delete('delete/location', 'Admin\Task::delete_location');
            $routes->delete('delete/item', 'Admin\Task::delete_item');
            $routes->delete('delete/action', 'Admin\Task::delete_action');
        });
    });
});

$routes->group("operator", ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Operator::index');
    $routes->get('scan/(:num)', 'Operator::scan/$1');
    $routes->get('revisi', 'Operator::revisi');
    $routes->post('modal', 'Operator::modal');
    $routes->post('add', 'Operator::add_submission');
    $routes->post('increment_visit/(:num)', 'Operator::increment_visit/$1');
    $routes->delete('cancel/(:num)', 'Operator::cancel_submission/$1');
});

$routes->group("verifikator", ['filter' => 'auth'], static function (RouteCollection $routes) {
    // GET
    $routes->get('/', 'Verifikator::index');
    // POST
    $routes->post('get_datatable', 'Verifikator::get_datatable');
    $routes->post('get_locations', 'Verifikator::get_locations');
    $routes->post('get_dates', 'Verifikator::get_dates');
    $routes->post('modal', 'Verifikator::modal');
    $routes->post('verify_all', 'Verifikator::verify_all');
    $routes->post('update', 'Verifikator::update');
    // PUT
    $routes->put('edit', 'Verifikator::update');
    // Export
    $routes->get('export', 'Verifikator::export_filtered');
    // Laporan
    $routes->get('laporan/rekapitulasi', 'Verifikator::rekapitulasi');
    $routes->get('laporan/rekapitulasi/summary', 'Verifikator::get_rekapitulasi_summary');
});

// Profile Routes (all roles)
$routes->group('profile', ['filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Profile::index');
    $routes->put('update_info', 'Profile::update_info');
    $routes->put('update_password', 'Profile::update_password');
    $routes->post('update_photo', 'Profile::update_photo');
});

$routes->set404Override(function () {
    echo view('errors/vw_custom_error');
});

// Redirect
$routes->addRedirect("/", "auth/login");
