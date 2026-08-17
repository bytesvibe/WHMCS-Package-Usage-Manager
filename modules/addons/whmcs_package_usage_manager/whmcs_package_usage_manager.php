<?php
/**
 * WHMCS Package Usage Manager
 *
 * @author Riduan Chowdhury
 * @company Bytes Vibe (bytesvibe.com)
 * @version 1.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

use WHMCS\Database\Capsule;

function whmcs_package_usage_manager_config()
{
    return array(
        'name' => 'WHMCS Package Usage Manager',
        'description' => 'Lightweight admin reporting for customers and services using WHMCS products.',
        'author' => 'Riduan Chowdhury',
        'language' => 'english',
        'version' => '1.0.0',
        'fields' => array(),
    );
}

function whmcs_package_usage_manager_activate()
{
    return array(
        'status' => 'success',
        'description' => 'WHMCS Package Usage Manager activated. It reads native WHMCS product, service, client, and server data only.',
    );
}

function whmcs_package_usage_manager_deactivate()
{
    return array(
        'status' => 'success',
        'description' => 'WHMCS Package Usage Manager deactivated. No custom tables or data were created.',
    );
}

function whmcs_package_usage_manager_upgrade($vars)
{
    return array(
        'status' => 'success',
        'description' => 'No database migration is required for this release.',
    );
}

function pum_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function pum_request_string($key, $default = '')
{
    return isset($_REQUEST[$key]) && is_string($_REQUEST[$key]) ? trim($_REQUEST[$key]) : $default;
}

function pum_request_int($key, $default = 0)
{
    $value = pum_request_string($key, '');
    return ctype_digit($value) ? (int) $value : $default;
}

function pum_allowed($value, $allowed, $default = '')
{
    return in_array($value, $allowed, true) ? $value : $default;
}

function pum_build_url($modulelink, $filters, $extra = array())
{
    $params = array_merge($filters, $extra);
    $params = array_filter($params, function ($value) {
        return $value !== '' && $value !== null && $value !== 0;
    });
    return $modulelink . (strpos($modulelink, '?') === false ? '?' : '&') . http_build_query($params, '', '&');
}

function pum_filter_state()
{
    $dateFrom = pum_request_string('date_from');
    $dateTo = pum_request_string('date_to');

    return array(
        'product_group_id' => pum_request_int('product_group_id'),
        'product_id' => pum_request_int('product_id'),
        'service_status' => pum_allowed(pum_request_string('service_status'), array('Active', 'Pending', 'Suspended', 'Terminated', 'Cancelled', 'Fraud')),
        'client_status' => pum_allowed(pum_request_string('client_status'), array('Active', 'Inactive', 'Closed'), ''),
        'billingcycle' => pum_allowed(pum_request_string('billingcycle'), array('Free Account', 'One Time', 'Monthly', 'Quarterly', 'Semi-Annually', 'Annually', 'Biennially', 'Triennially')),
        'server_id' => pum_request_int('server_id'),
        'date_field' => pum_allowed(pum_request_string('date_field', 'regdate'), array('regdate', 'nextduedate'), 'regdate'),
        'date_from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) ? $dateFrom : '',
        'date_to' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) ? $dateTo : '',
        'search' => substr(pum_request_string('search'), 0, 100),
    );
}

function pum_base_query($filters)
{
    $query = Capsule::table('tblhosting as h')
        ->join('tblclients as c', 'c.id', '=', 'h.userid')
        ->join('tblproducts as p', 'p.id', '=', 'h.packageid')
        ->leftJoin('tblservers as s', 's.id', '=', 'h.server')
        ->select(
            'h.id as service_id',
            'h.userid as client_id',
            'h.packageid as product_id',
            'h.domain',
            'h.username',
            'h.regdate',
            'h.nextduedate',
            'h.billingcycle',
            'h.domainstatus as service_status',
            'c.firstname',
            'c.lastname',
            'c.email',
            'c.status as client_status',
            'p.name as product_name',
            's.name as server_name'
        );

    if (!empty($filters['product_group_id'])) {
        $query->where('p.gid', $filters['product_group_id']);
    }
    if (!empty($filters['product_id'])) {
        $query->where('h.packageid', $filters['product_id']);
    }
    if ($filters['service_status'] !== '') {
        $query->where('h.domainstatus', $filters['service_status']);
    }
    if ($filters['client_status'] !== '') {
        $query->where('c.status', $filters['client_status']);
    }
    if ($filters['billingcycle'] !== '') {
        $query->where('h.billingcycle', $filters['billingcycle']);
    }
    if (!empty($filters['server_id'])) {
        $query->where('h.server', $filters['server_id']);
    }
    if ($filters['date_from'] !== '') {
        $query->where('h.' . $filters['date_field'], '>=', $filters['date_from']);
    }
    if ($filters['date_to'] !== '') {
        $query->where('h.' . $filters['date_field'], '<=', $filters['date_to'] . ' 23:59:59');
    }
    if ($filters['search'] !== '') {
        $like = '%' . $filters['search'] . '%';
        $query->where(function ($subquery) use ($like) {
            $subquery->where('c.firstname', 'like', $like)
                ->orWhere('c.lastname', 'like', $like)
                ->orWhere('c.email', 'like', $like)
                ->orWhere('h.domain', 'like', $like)
                ->orWhere('h.username', 'like', $like)
                ->orWhere('p.name', 'like', $like);
        });
    }

    return $query;
}

function whmcs_package_usage_manager_output($vars)
{
    $modulelink = $vars['modulelink'];
    $filters = pum_filter_state();
    $statusOptions = array('Active', 'Pending', 'Suspended', 'Terminated', 'Cancelled', 'Fraud');
    $billingOptions = array('Free Account', 'One Time', 'Monthly', 'Quarterly', 'Semi-Annually', 'Annually', 'Biennially', 'Triennially');
    $clientStatusOptions = array('Active', 'Inactive', 'Closed');
    $error = '';
    $groups = array();
    $products = array();
    $servers = array();
    $rows = array();
    $total = 0;
    $clients = 0;
    $activeServices = 0;
    $page = max(1, pum_request_int('page', 1));
    $perPage = 20;
    $pages = 1;

    try {
        $groups = Capsule::table('tblproductgroups')->orderBy('name')->get(array('id', 'name'));
        $productOptionsQuery = Capsule::table('tblproducts')->orderBy('name');
        if (!empty($filters['product_group_id'])) {
            $productOptionsQuery->where('gid', $filters['product_group_id']);
        }
        $products = $productOptionsQuery->get(array('id', 'name'));
        $servers = Capsule::table('tblservers')->orderBy('name')->get(array('id', 'name'));

        $query = pum_base_query($filters);
        $total = (int) (clone $query)->count();
        $clients = (int) (clone $query)->distinct()->count('c.id');
        $activeServices = (int) (clone $query)->where('h.domainstatus', 'Active')->count();
        $pages = max(1, (int) ceil($total / $perPage));
        if ($page > $pages) {
            $page = $pages;
        }
        $rows = $query->orderBy('h.id', 'desc')->offset(($page - 1) * $perPage)->limit($perPage)->get();
    } catch (Exception $exception) {
        $page = 1;
        $error = 'The report could not be loaded. Please verify that the WHMCS database is available.';
    }

    $pageFilters = $filters;
    $previousUrl = $page > 1 ? pum_build_url($modulelink, $pageFilters, array('page' => $page - 1)) : '';
    $nextUrl = $page < $pages ? pum_build_url($modulelink, $pageFilters, array('page' => $page + 1)) : '';

    echo '<div class="container-fluid">';
    echo '<div class="row"><div class="col-md-12"><h2>Package Usage Manager</h2><p class="text-muted">Find customers and services using your WHMCS products.</p></div></div>';

    if ($error !== '') {
        echo '<div class="alert alert-danger">' . pum_h($error) . '</div>';
    }

    echo '<div class="row">';
    echo '<div class="col-sm-4"><div class="panel panel-default"><div class="panel-body"><strong>Matching services</strong><h3>' . number_format($total) . '</h3><span class="text-muted">Current filter set</span></div></div></div>';
    echo '<div class="col-sm-4"><div class="panel panel-default"><div class="panel-body"><strong>Unique customers</strong><h3>' . number_format($clients) . '</h3><span class="text-muted">Distinct client accounts</span></div></div></div>';
    echo '<div class="col-sm-4"><div class="panel panel-default"><div class="panel-body"><strong>Active services</strong><h3>' . number_format($activeServices) . '</h3><span class="text-muted">Within current result set</span></div></div></div>';
    echo '</div>';

    echo '<div class="panel panel-default"><div class="panel-heading"><strong>Filter services</strong></div><div class="panel-body">';
    echo '<form method="get" action="' . pum_h($modulelink) . '">';
    echo '<input type="hidden" name="module" value="whmcs_package_usage_manager">';
    echo '<div class="row">';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-product-group">Product group</label><select id="pum-product-group" class="form-control" name="product_group_id"><option value="">All product groups</option>';
    foreach ($groups as $group) {
        echo '<option value="' . (int) $group->id . '"' . ((int) $filters['product_group_id'] === (int) $group->id ? ' selected' : '') . '>' . pum_h($group->name) . '</option>';
    }
    echo '</select></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-product">Product / package</label><select id="pum-product" class="form-control" name="product_id"><option value="">All products</option>';
    foreach ($products as $product) {
        echo '<option value="' . (int) $product->id . '"' . ((int) $filters['product_id'] === (int) $product->id ? ' selected' : '') . '>' . pum_h($product->name) . ' (#' . (int) $product->id . ')</option>';
    }
    echo '</select></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-service-status">Service status</label><select id="pum-service-status" class="form-control" name="service_status"><option value="">All service statuses</option>';
    foreach ($statusOptions as $status) {
        echo '<option value="' . pum_h($status) . '"' . ($filters['service_status'] === $status ? ' selected' : '') . '>' . pum_h($status) . '</option>';
    }
    echo '</select></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-client-status">Client status</label><select id="pum-client-status" class="form-control" name="client_status"><option value="">All client statuses</option>';
    foreach ($clientStatusOptions as $status) {
        echo '<option value="' . pum_h($status) . '"' . ($filters['client_status'] === $status ? ' selected' : '') . '>' . pum_h($status) . '</option>';
    }
    echo '</select></div></div>';
    echo '</div><div class="row">';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-billing-cycle">Billing cycle</label><select id="pum-billing-cycle" class="form-control" name="billingcycle"><option value="">All billing cycles</option>';
    foreach ($billingOptions as $cycle) {
        echo '<option value="' . pum_h($cycle) . '"' . ($filters['billingcycle'] === $cycle ? ' selected' : '') . '>' . pum_h($cycle) . '</option>';
    }
    echo '</select></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-server">Server</label><select id="pum-server" class="form-control" name="server_id"><option value="">All servers</option>';
    foreach ($servers as $server) {
        echo '<option value="' . (int) $server->id . '"' . ((int) $filters['server_id'] === (int) $server->id ? ' selected' : '') . '>' . pum_h($server->name) . '</option>';
    }
    echo '</select></div></div>';
    echo '<div class="col-md-6"><div class="form-group"><label for="pum-search">Search customer, email, domain, username, or product</label><input id="pum-search" class="form-control" type="search" name="search" value="' . pum_h($filters['search']) . '" placeholder="Enter a name, email, domain, username, or product"></div></div>';
    echo '</div><div class="row">';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-date-field">Date filter</label><select id="pum-date-field" class="form-control" name="date_field"><option value="regdate"' . ($filters['date_field'] === 'regdate' ? ' selected' : '') . '>Signup date</option><option value="nextduedate"' . ($filters['date_field'] === 'nextduedate' ? ' selected' : '') . '>Next due date</option></select></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-date-from">From date</label><input id="pum-date-from" class="form-control" type="date" name="date_from" value="' . pum_h($filters['date_from']) . '"></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label for="pum-date-to">To date</label><input id="pum-date-to" class="form-control" type="date" name="date_to" value="' . pum_h($filters['date_to']) . '"></div></div>';
    echo '<div class="col-md-3"><div class="form-group"><label>&nbsp;</label><div><button class="btn btn-primary" type="submit">Apply filters</button> <a class="btn btn-default" href="' . pum_h($modulelink) . '">Reset</a></div></div></div>';
    echo '</div></form></div></div>';

    echo '<div class="panel panel-default"><div class="panel-heading"><strong>Customer service usage</strong><span class="pull-right text-muted">Page ' . (int) $page . ' of ' . (int) $pages . '</span></div><div class="table-responsive"><table class="table table-striped table-hover">';
    echo '<thead><tr><th>Customer</th><th>Product</th><th>Service</th><th>Status</th><th>Billing cycle</th><th>Dates</th><th>Action</th></tr></thead><tbody>';
    if (count($rows) === 0) {
        echo '<tr><td colspan="7" class="text-center text-muted">No matching services were found.</td></tr>';
    } else {
        foreach ($rows as $row) {
            $customer = trim($row->firstname . ' ' . $row->lastname);
            $customerLabel = $customer !== '' ? $customer : 'Client #' . $row->client_id;
            $serviceLabel = $row->domain !== '' ? $row->domain : ($row->username !== '' ? $row->username : 'Service #' . $row->service_id);
            $statusClass = $row->service_status === 'Active' ? 'label-success' : ($row->service_status === 'Pending' ? 'label-warning' : 'label-default');
            echo '<tr>';
            echo '<td><a href="clientssummary.php?userid=' . (int) $row->client_id . '">' . pum_h($customerLabel) . '</a><br><small class="text-muted">' . pum_h($row->email) . '</small></td>';
            echo '<td>' . pum_h($row->product_name) . '<br><small class="text-muted">Product #' . (int) $row->product_id . '</small></td>';
            echo '<td><a href="clientsservices.php?userid=' . (int) $row->client_id . '&id=' . (int) $row->service_id . '">' . pum_h($serviceLabel) . '</a><br><small class="text-muted">Service #' . (int) $row->service_id . ($row->server_name ? ' · ' . pum_h($row->server_name) : '') . '</small></td>';
            echo '<td><span class="label ' . $statusClass . '">' . pum_h($row->service_status) . '</span><br><small class="text-muted">Client: ' . pum_h($row->client_status) . '</small></td>';
            echo '<td>' . pum_h($row->billingcycle) . '</td>';
            echo '<td><small>Signup: ' . pum_h($row->regdate) . '</small><br><small>Due: ' . pum_h($row->nextduedate) . '</small></td>';
            echo '<td><a href="clientsservices.php?userid=' . (int) $row->client_id . '&id=' . (int) $row->service_id . '">View service</a></td>';
            echo '</tr>';
        }
    }
    echo '</tbody></table></div><div class="panel-footer clearfix">';
    echo '<span class="text-muted">Showing 20 services per page. ' . ($total > 0 ? 'Results ' . (($page - 1) * $perPage + 1) . '–' . min($page * $perPage, $total) . ' of ' . number_format($total) . '.' : 'No results.') . '</span>';
    echo '<ul class="pagination pagination-sm pull-right" style="margin:0">';
    if ($previousUrl !== '') {
        echo '<li><a href="' . pum_h($previousUrl) . '">&laquo; Previous</a></li>';
    } else {
        echo '<li class="disabled"><span>&laquo; Previous</span></li>';
    }
    if ($pages <= 7) {
        $pageItems = range(1, $pages);
    } elseif ($page <= 4) {
        $pageItems = array(1, 2, 3, 4, 5, '...', $pages);
    } elseif ($page >= $pages - 3) {
        $pageItems = array(1, '...', $pages - 4, $pages - 3, $pages - 2, $pages - 1, $pages);
    } else {
        $pageItems = array(1, '...', $page - 1, $page, $page + 1, '...', $pages);
    }
    foreach ($pageItems as $pageItem) {
        if ($pageItem === '...') {
            echo '<li class="disabled"><span>...</span></li>';
        } elseif ((int) $pageItem === (int) $page) {
            echo '<li class="active"><span>' . (int) $pageItem . '</span></li>';
        } else {
            echo '<li><a href="' . pum_h(pum_build_url($modulelink, $filters, array('page' => (int) $pageItem))) . '">' . (int) $pageItem . '</a></li>';
        }
    }
    if ($nextUrl !== '') {
        echo '<li><a href="' . pum_h($nextUrl) . '">Next &raquo;</a></li>';
    } else {
        echo '<li class="disabled"><span>Next &raquo;</span></li>';
    }
    echo '</ul></div></div>';
    echo '<p class="text-muted text-center"><small>WHMCS Package Usage Manager · Author: Riduan Chowdhury · Powered by <a href="https://bytesvibe.com" target="_blank" rel="noopener noreferrer">bytesvibe.com</a></small></p>';
    echo '</div>';
}
