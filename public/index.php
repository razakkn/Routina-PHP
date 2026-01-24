<?php

// Simple Autoloader
spl_autoload_register(function ($class) {
    // Project-specific namespace prefix
    $prefix = 'Routina\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../src/';

    // Does the class use the namespace prefix?
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        // no, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr($class, $len);

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Session hardening (must run before session_start)
ini_set('session.use_strict_mode', '1');
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start Session
session_start();

// Basic security headers (parity with ASP.NET CSP middleware)
$csp = "default-src 'self'; "
    . "script-src 'self' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
    . "img-src 'self' data: blob:; "
    . "font-src 'self' data: https://cdn.jsdelivr.net; "
    . "connect-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'";
header('Content-Security-Policy: ' . $csp);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

function app_config($key = null, $default = null) {
    static $config = null;
    if ($config === null) {
        $configFile = __DIR__ . '/../config/config.php';
        if (!is_file($configFile)) {
            $configFile = __DIR__ . '/../config/config.example.php';
        }
        $config = require $configFile;
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        redirect('/login');
    }
}

function is_admin() {
    $email = $_SESSION['user_data']['email'] ?? null;
    if (!is_string($email) || $email === '') {
        return false;
    }

    $admins = app_config('admin_emails', []);
    if (!is_array($admins)) {
        $admins = [];
    }

    return in_array(strtolower($email), array_map('strtolower', $admins), true);
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function current_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response($data, $statusCode = 200) {
    http_response_code((int)$statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function csrf_token() {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify() {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $sent = $_POST['_csrf'] ?? '';
    $expected = $_SESSION['_csrf'] ?? '';

    if (!is_string($sent) || !is_string($expected) || $expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(400);
        echo 'Bad Request';
        exit;
    }
}

// Routing Logic (Basic)
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Record request timings for basic metrics (mirrors ASP.NET metrics middleware)
$__reqStart = hrtime(true);
register_shutdown_function(function () use ($__reqStart) {
    try {
        $elapsedMs = (int)((hrtime(true) - $__reqStart) / 1_000_000);
        $recent = $_SESSION['metrics_recent'] ?? [];
        if (!is_array($recent)) {
            $recent = [];
        }
        $recent[] = $elapsedMs;
        if (count($recent) > 50) {
            $recent = array_slice($recent, -50);
        }
        $_SESSION['metrics_recent'] = $recent;
    } catch (Throwable $e) {
        // ignore
    }
});

// Enforce CSRF on all POST requests
csrf_verify();

// helper for views
function view($path, $data = []) {
    extract($data, EXTR_SKIP);
    
    // Make Model available as $Model (capitalized to match C# convention used in templates)
    if (isset($Model)) {
        // it's already set
    } elseif (isset($model)) {
        $Model = $model;
    } else {
        $Model = (object)[]; // Empty object to prevent errors
    }

    $viewPath = __DIR__ . '/../views/' . $path . '.php';
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo "View not found: " . $path;
    }
}

// Router

function cache_dir() {
    return __DIR__ . '/../storage/cache';
}

function cache_get_json($key, $ttlSeconds) {
    $dir = cache_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $key) . '.json';
    if (!is_file($file)) {
        return null;
    }

    if ($ttlSeconds > 0 && (time() - filemtime($file)) > (int)$ttlSeconds) {
        return null;
    }

    $raw = @file_get_contents($file);
    if ($raw === false) {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function cache_set_json($key, $data) {
    $dir = cache_dir();
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $key) . '.json';
    @file_put_contents($file, json_encode($data));
}

function http_get_json($url, $timeoutSeconds = 4) {
    // Prefer cURL when available (more reliable than allow_url_fopen)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$timeoutSeconds);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeoutSeconds);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: RoutinaApp/1.0 (vehicle)'
        ]);

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (is_string($raw) && $raw !== '' && $status >= 200 && $status < 300) {
            $doc = json_decode($raw, true);
            return is_array($doc) ? $doc : null;
        }

        // Retry with relaxed SSL verification (common on Windows without CA bundle)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int)$timeoutSeconds);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeoutSeconds);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: RoutinaApp/1.0 (vehicle)'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($raw) || $raw === '' || $status < 200 || $status >= 300) {
            return null;
        }

        $doc = json_decode($raw, true);
        return is_array($doc) ? $doc : null;
    }

    $baseHttp = [
        'method' => 'GET',
        'timeout' => (int)$timeoutSeconds,
        'header' => "User-Agent: RoutinaApp/1.0 (vehicle)\r\nAccept: application/json\r\n"
    ];

    $contexts = [
        stream_context_create([
            'http' => $baseHttp,
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]
        ]),
        // Retry with relaxed SSL checks (common on Windows without CA bundle)
        stream_context_create([
            'http' => $baseHttp,
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ])
    ];

    foreach ($contexts as $ctx) {
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            continue;
        }

        $doc = json_decode($raw, true);
        if (is_array($doc)) {
            return $doc;
        }
    }

    return null;
}

// API: Place autocomplete (parity with ASP.NET PlaceLookupService)
if ($requestUri === '/api/places') {
    $q = trim((string)($_GET['q'] ?? ''));
    $limit = (int)($_GET['limit'] ?? 6);
    $limit = max(1, min(10, $limit));
    $lang = trim((string)($_GET['lang'] ?? 'en'));
    if ($q === '' || strlen($q) < 2) {
        json_response([]);
    }

    $cacheKey = strtolower($lang) . ':' . $limit . ':' . strtolower($q);
    $cache = $_SESSION['_places_cache'] ?? [];
    if (is_array($cache) && isset($cache[$cacheKey])) {
        $hit = $cache[$cacheKey];
        if (is_array($hit) && isset($hit['exp'], $hit['data']) && time() < (int)$hit['exp'] && is_array($hit['data'])) {
            json_response($hit['data']);
        }
    }

    $url = 'https://photon.komoot.io/api/?q=' . rawurlencode($q) . '&limit=' . $limit . '&lang=' . rawurlencode($lang);
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 4,
            'header' => "User-Agent: RoutinaApp/1.0 (places)\r\nAccept: application/json\r\n"
        ]
    ]);

    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        json_response([]);
    }

    $doc = json_decode($raw, true);
    if (!is_array($doc) || !isset($doc['features']) || !is_array($doc['features'])) {
        json_response([]);
    }

    $results = [];
    foreach ($doc['features'] as $f) {
        $p = $f['properties'] ?? null;
        if (!is_array($p)) continue;

        $name = $p['name'] ?? null;
        $city = $p['city'] ?? ($p['locality'] ?? null);
        $state = $p['state'] ?? ($p['region'] ?? null);
        $country = $p['country'] ?? null;

        $place = $city ?: $name;
        if (!is_string($place) || trim($place) === '') continue;
        $place = trim($place);

        $parts = [$place];
        if (is_string($state) && trim($state) !== '') $parts[] = trim($state);
        if (is_string($country) && trim($country) !== '') $parts[] = trim($country);
        $value = implode(', ', $parts);

        $results[] = ['display' => $value, 'value' => $value];
        if (count($results) >= $limit) break;
    }

    $_SESSION['_places_cache'] = is_array($cache) ? $cache : [];
    $_SESSION['_places_cache'][$cacheKey] = ['exp' => time() + 1800, 'data' => $results];

    json_response($results);
}

// API: Vehicle make lookup (vPIC)
if ($requestUri === '/api/vehicle/makes') {
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q !== '' && strlen($q) < 2) {
        json_response([]);
    }

    // Cache the full makes list for 30 days
    $ttl = 30 * 24 * 60 * 60;
    $all = cache_get_json('vpic_all_makes', $ttl);

    if (!is_array($all) || empty($all)) {
        $doc = http_get_json('https://vpic.nhtsa.dot.gov/api/vehicles/getallmakes?format=json', 6);
        $makes = [];
        if (is_array($doc) && isset($doc['Results']) && is_array($doc['Results'])) {
            foreach ($doc['Results'] as $row) {
                $name = $row['Make_Name'] ?? null;
                if (is_string($name)) {
                    $name = trim($name);
                    if ($name !== '') {
                        $makes[$name] = true;
                    }
                }
            }
        }

        $all = array_keys($makes);
        sort($all, SORT_NATURAL | SORT_FLAG_CASE);
        cache_set_json('vpic_all_makes', $all);
    }

    $limit = 30;
    if ($q === '') {
        json_response(array_slice($all, 0, $limit));
    }

    $qLower = strtolower($q);
    $out = [];
    foreach ($all as $m) {
        if (strpos(strtolower($m), $qLower) !== false) {
            $out[] = $m;
            if (count($out) >= $limit) break;
        }
    }

    json_response($out);
}

// API: Vehicle model lookup for year+make (vPIC)
if ($requestUri === '/api/vehicle/models') {
    $year = (int)($_GET['year'] ?? 0);
    $make = trim((string)($_GET['make'] ?? ''));
    $minYear = 1960;
    $maxYear = (int)date('Y') + 1;
    if ($year < $minYear || $year > $maxYear || $make === '' || strlen($make) < 2) {
        json_response([]);
    }

    $ttl = 30 * 24 * 60 * 60;
    $key = 'vpic_models_' . $year . '_' . sha1(strtolower($make));
    $cached = cache_get_json($key, $ttl);
    if (is_array($cached)) {
        json_response($cached);
    }

    $url = 'https://vpic.nhtsa.dot.gov/api/vehicles/GetModelsForMakeYear/make/' . rawurlencode($make) . '/modelyear/' . $year . '?format=json';
    $doc = http_get_json($url, 6);

    $models = [];
    if (is_array($doc) && isset($doc['Results']) && is_array($doc['Results'])) {
        foreach ($doc['Results'] as $row) {
            $name = $row['Model_Name'] ?? null;
            if (is_string($name)) {
                $name = trim($name);
                if ($name !== '') {
                    $models[$name] = true;
                }
            }
        }
    }

    $out = array_keys($models);
    sort($out, SORT_NATURAL | SORT_FLAG_CASE);
    cache_set_json($key, $out);
    json_response($out);
}

// 1. Root / Landing
if ($requestUri === '/' || $requestUri === '/index.php') {
    if (isset($_SESSION['user_id'])) {
        redirect('/dashboard');
    }
    view('home/index');
    exit;
}

// 2. Auth Routes
if ($requestUri === '/login') {
    $controller = new \Routina\Controllers\AuthController();
    if ($method === 'POST') {
        $controller->login();
    } else {
        view('account/login');
    }
    exit;
}

if ($requestUri === '/logout') {
    if ($method !== 'POST') {
        http_response_code(405);
        echo 'Method Not Allowed';
        exit;
    }
    $controller = new \Routina\Controllers\AuthController();
    $controller->logout();
    exit;
}

if ($requestUri === '/register') {
    $controller = new \Routina\Controllers\AuthController();
    if ($method === 'POST') {
        $controller->register();
    } else {
        view('account/register');
    }
    exit;
}

if ($requestUri === '/forgot-password') {
    $controller = new \Routina\Controllers\AuthController();
    $controller->forgotPassword();
    exit;
}

if ($requestUri === '/logged-out') {
    view('account/logged_out');
    exit;
}

if ($requestUri === '/journal/today') {
    $controller = new \Routina\Controllers\JournalController();
    $controller->today();
    exit;
}

if ($requestUri === '/journal/history') {
    $controller = new \Routina\Controllers\JournalController();
    $controller->history();
    exit;
}

if ($requestUri === '/journal/view') {
    $controller = new \Routina\Controllers\JournalController();
    $controller->viewEntry();
    exit;
}

if ($requestUri === '/vacation/new') {
    $controller = new \Routina\Controllers\VacationController();
    $controller->newTrip();
    exit;
}

if ($requestUri === '/vacation/edit') {
    $controller = new \Routina\Controllers\VacationController();
    $controller->edit();
    exit;
}

if ($requestUri === '/vacation/trip') {
    $controller = new \Routina\Controllers\VacationController();
    $controller->trip();
    exit;
}

if ($requestUri === '/finance/assets') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->assets();
    exit;
}

if ($requestUri === '/finance/bills') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->bills();
    exit;
}

if ($requestUri === '/finance/budgets') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->budgets();
    exit;
}

if ($requestUri === '/finance/income') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->income();
    exit;
}

if ($requestUri === '/finance/savings') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->savings();
    exit;
}

if ($requestUri === '/finance/reflection') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->reflection();
    exit;
}

if ($requestUri === '/finance/diary') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->diary();
    exit;
}

if ($requestUri === '/vehicle/dashboard') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->dashboard();
    exit;
}

if ($requestUri === '/vehicle/new') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->newVehicle();
    exit;
}

if ($requestUri === '/vehicle/edit') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->edit();
    exit;
}

if ($requestUri === '/vehicle/vendors') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->vendors();
    exit;
}

if ($requestUri === '/vehicle/parts') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->parts();
    exit;
}

if ($requestUri === '/vehicle/maintenance') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->maintenance();
    exit;
}

if ($requestUri === '/vehicle/documents') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->documents();
    exit;
}

if ($requestUri === '/vehicle/events') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->events();
    exit;
}

if ($requestUri === '/vehicle/plans') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->plans();
    exit;
}

if ($requestUri === '/admin/metrics') {
    require_admin();
    $controller = new \Routina\Controllers\AdminController();
    $controller->metrics();
    exit;
}

if ($requestUri === '/metrics/json') {
    require_admin();
    $controller = new \Routina\Controllers\AdminController();
    $controller->metricsJson();
    exit;
}

if ($requestUri === '/metrics/export/csv') {
    require_admin();
    $controller = new \Routina\Controllers\AdminController();
    $controller->metricsCsv();
    exit;
}

// Family member actions
if ($requestUri === '/family/edit') {
    require_login();
    $controller = new \Routina\Controllers\FamilyController();
    $controller->edit();
    exit;
}

if ($requestUri === '/family/update' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\FamilyController();
    $controller->update();
    exit;
}

if ($requestUri === '/family/update-parents' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\FamilyController();
    $controller->updateParents();
    exit;
}

if ($requestUri === '/family/delete' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\FamilyController();
    $controller->delete();
    exit;
}

// Buzz (in-app reach out)
if ($requestUri === '/buzz/send' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\BuzzController();
    $controller->send();
    exit;
}

if ($requestUri === '/buzz/mark' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\BuzzController();
    $controller->mark();
    exit;
}

if ($requestUri === '/buzz/mark-all' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\BuzzController();
    $controller->markAll();
    exit;
}

// 3. Dual-Purpose Routes (Public Landing vs Private App)
if ($requestUri === '/home') {
    if (isset($_SESSION['user_id'])) {
        // Private: Home Management Module
        $controller = new \Routina\Controllers\HomeTaskController();
        $controller->index();
    } else {
        // Public: Landing Page (same as root)
        view('home/index');
    }
    exit;
}

// 4. App Modules (Protected)
$appRoutes = [
    '/dashboard' => 'DashboardController',
    '/finance'   => 'FinanceController',
    '/vehicle'   => 'VehicleController',
    '/journal'   => 'JournalController',
    '/vacation'  => 'VacationController',
    '/health'    => 'HealthController',
    '/calendar'  => 'CalendarController',
    '/family'    => 'FamilyController',
    '/buzz'      => 'BuzzController',
    '/profile'   => 'ProfileController',
    '/account/profile' => 'ProfileController'
];

if (isset($appRoutes[$requestUri])) {
    require_login();
    $controllerName = "\\Routina\\Controllers\\" . $appRoutes[$requestUri];
    
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        
        // POST handling for specific controllers
        if (($requestUri === '/profile' || $requestUri === '/account/profile') && $method === 'POST') {
            $controller->update();
        } else {
            $controller->index();
        }
    } else {
        http_response_code(500);
        echo "Controller not found: " . $controllerName;
    }
    exit;
}

// 5. Fallback
http_response_code(404);
echo "404 Not Found";
