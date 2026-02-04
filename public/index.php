<?php

// Error handling for production
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't show errors to users
ini_set('log_errors', '1');
// ini_set('error_log', __DIR__ . '/../storage/error.log'); // Use default PHP error log

// Global exception handler
set_exception_handler(function ($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    if (file_exists(__DIR__ . '/../views/errors/500.php')) {
        include __DIR__ . '/../views/errors/500.php';
    } else {
        echo '<!DOCTYPE html><html><head><title>Error</title></head><body>';
        echo '<h1>Something went wrong</h1>';
        echo '<p>We\'re sorry, but something went wrong. Please try again later.</p>';
        echo '<p><a href="/">Go back to home</a></p>';
        echo '</body></html>';
    }
    exit;
});

// Global error handler (convert errors to exceptions)
set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

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

// Enforce HTTPS when app_url is HTTPS to keep secure cookies consistent
$cfgFile = __DIR__ . '/../config/config.php';
if (is_file($cfgFile)) {
    $cfg = require $cfgFile;
    $appUrl = is_array($cfg) ? (string)($cfg['app_url'] ?? '') : '';
    if (stripos($appUrl, 'https://') === 0) {
        $isHttps = (
            (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
            (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
            (!empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
        if (!$isHttps) {
            $appHost = (string)(parse_url($appUrl, PHP_URL_HOST) ?? '');
            $appPort = parse_url($appUrl, PHP_URL_PORT);
            $host = '';
            if ($appHost !== '' && preg_match('/^(?:[A-Za-z0-9-]+\.)*[A-Za-z0-9-]+$/', $appHost)) {
                $host = $appHost;
                if ($appPort) {
                    $host .= ':' . $appPort;
                }
            }
            if ($host !== '') {
                header('Location: https://' . $host . '/', true, 301);
                exit;
            }
        }
    }
}

// Session hardening (must run before session_start)
ini_set('session.use_strict_mode', '1');
$sessionPath = __DIR__ . '/../storage/sessions';
if (!is_dir($sessionPath)) {
    @mkdir($sessionPath, 0777, true);
}
if (is_dir($sessionPath) && is_writable($sessionPath)) {
    ini_set('session.save_path', $sessionPath);
} else {
    $tmp = sys_get_temp_dir();
    if (is_string($tmp) && $tmp !== '') {
        ini_set('session.save_path', $tmp);
        error_log('Session path fallback to temp: ' . $tmp);
    } else {
        error_log('Session path not writable: ' . $sessionPath);
    }
}
$secureCookie = (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') ||
    (!empty($_SERVER['REQUEST_SCHEME']) && strtolower($_SERVER['REQUEST_SCHEME']) === 'https') ||
    (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
);
if (!$secureCookie) {
    $appUrl = isset($appUrl) ? $appUrl : '';
    if (stripos($appUrl, 'https://') === 0) {
        $secureCookie = true;
    }
}
$cookieDomain = '';
if (!empty($appUrl)) {
    $host = parse_url($appUrl, PHP_URL_HOST);
    if (is_string($host) && $host !== '') {
        $cookieDomain = $host;
    }
}
if ($cookieDomain === '') {
    $cookieDomain = $_SERVER['HTTP_HOST'] ?? '';
}
if ($cookieDomain !== '') {
    $cookieDomain = preg_replace('/:\d+$/', '', $cookieDomain);
    if ($cookieDomain === 'localhost' || filter_var($cookieDomain, FILTER_VALIDATE_IP)) {
        $cookieDomain = '';
    }
}
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $cookieDomain ?: null,
    'secure' => $secureCookie,
    'httponly' => true,
    'samesite' => $secureCookie ? 'None' : 'Lax'
]);

// Start Session
session_start();

// Basic security headers (parity with ASP.NET CSP middleware)
// NOTE: Some third-party libs (or builds) may use `eval`/`new Function` for dynamic code.
// If you see "Evaluating a string as JavaScript violates the CSP" in the browser console,
// consider the security tradeoffs before enabling 'unsafe-eval'. For development or when
// using a library that requires it, we add 'unsafe-eval' here.
$csp = "default-src 'self'; "
    . "script-src 'self' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
    . "img-src 'self' data: blob:; "
    . "font-src 'self' data: https://cdn.jsdelivr.net https://fonts.gstatic.com https://fonts.googleapis.com; "
    . "connect-src 'self' https://api.bigdatacloud.net https://ipapi.co; object-src 'none'; base-uri 'self'; frame-ancestors 'self'";
header('Content-Security-Policy: ' . $csp);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Permissions-Policy: geolocation=(self), microphone=(), camera=()');

// Simple static helper: serve /galaxy or /galaxy.html directly from public folder for demo preview
$reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($reqPath === '/galaxy' || $reqPath === '/galaxy.html') {
    $file = __DIR__ . '/galaxy.html';
    if (file_exists($file)) {
        http_response_code(200);
        header('Content-Type: text/html; charset=utf-8');
        echo file_get_contents($file);
    } else {
        http_response_code(404);
        echo 'Galaxy demo file not found on server.';
    }
    exit;
}

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

function quote_of_the_day(): string {
    $quotes = [
        'Small steps, steady progress.',
        'Make today simple, make it count.',
        'Your pace is still progress.',
        'Clarity first. Then momentum.',
        'One routine at a time.',
        'Be gentle, be consistent.',
        'Do the next right thing.',
        'Tiny wins build big change.'
    ];
    $idx = (int)floor(time() / 86400) % count($quotes);
    return $quotes[$idx];
}

function avatar_preset_url(?string $key): ?string {
    $k = strtolower(trim((string)$key));
    if ($k === '') return null;
    $icons = [
        'lavender' => [
            'bg' => '#E6E6FA',
            'fg' => '#6D5BD0',
            'path' => 'M24 6l4 10 10 1-8 6 3 10-9-6-9 6 3-10-8-6 10-1z'
        ],
        'sage' => [
            'bg' => '#9DC183',
            'fg' => '#1F6F43',
            'path' => 'M22 34c10-4 16-14 16-24-10 0-20 6-24 16-4-4-6-10-6-16-8 4-12 12-10 20 4 12 16 18 24 12z'
        ],
        'teal' => [
            'bg' => '#008080',
            'fg' => '#E8FFFA',
            'path' => 'M8 24c10-10 22-12 32-6-6 10-20 20-32 20-4 0-6-4-4-14z'
        ],
        'coral' => [
            'bg' => '#FF7F50',
            'fg' => '#FFF5F0',
            'path' => 'M24 40s-14-8-14-18c0-6 4-10 10-10 4 0 7 2 8 5 1-3 4-5 8-5 6 0 10 4 10 10 0 10-22 18-22 18z'
        ],
    ];
    if (!isset($icons[$k])) return null;
    $icon = $icons[$k];
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 48 48'>"
         . "<rect width='48' height='48' rx='14' fill='{$icon['bg']}'/>"
         . "<path d='{$icon['path']}' fill='{$icon['fg']}'/>"
         . "</svg>";
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function redirect($path) {
    header('Location: ' . $path);
    exit;
}

function require_login() {
    if (!isset($_SESSION['user_id'])) {
        error_log('Auth required: missing session user_id, sid=' . session_id() . ' path=' . ($_SERVER['REQUEST_URI'] ?? ''));
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
        abort(403, 'Access denied', 'You do not have permission to access this resource.');
    }
}

/**
 * Abort the request with an error page.
 *
 * @param int $code HTTP status code
 * @param string $title Error title
 * @param string $message Error message
 * @return never
 */
function abort(int $code = 500, string $title = 'Error', string $message = 'An unexpected error occurred.'): never
{
    http_response_code($code);
    
    // If it's an API request, return JSON
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($accept, 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]);
        exit;
    }
    
    // Render error view
    $viewPath = __DIR__ . '/../views/errors/error.php';
    if (file_exists($viewPath)) {
        include $viewPath;
    } else {
        echo "<h1>{$code} - " . htmlspecialchars($title) . "</h1>";
        echo "<p>" . htmlspecialchars($message) . "</p>";
    }
    exit;
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

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (in_array($path, ['/login', '/register', '/forgot-password'], true)) {
        // Allow auth endpoints without CSRF to avoid blocking first-time users when session cookies are not yet stable.
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
    $key = 'vpic_models_' . $year . '_' . hash('sha256', strtolower($make));
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

// API: Check Routina ID availability
if ($requestUri === '/api/check-routina-id') {
    $id = trim((string)($_GET['id'] ?? ''));
    if ($id === '' || strlen($id) < 3) {
        json_response(['available' => false, 'error' => 'ID too short']);
    }
    
    $authService = new \Routina\Services\AuthService();
    $available = $authService->isRoutinaIdAvailable($id);
    json_response(['available' => $available]);
}

// API: Debug family members with emails (temporary diagnostic)
if ($requestUri === '/api/debug-family') {
    require_login();
    $db = \Routina\Config\Database::getConnection();
    
    // Get all family members with emails
    $stmt = $db->query("SELECT fm.id, fm.user_id, fm.name, fm.email, fm.phone, fm.relation, fm.no_email, u.display_name as owner_name 
                        FROM family_members fm 
                        JOIN users u ON fm.user_id = u.id 
                        WHERE fm.email IS NOT NULL AND fm.email != '' 
                        ORDER BY fm.id DESC LIMIT 20");
    $familyWithEmail = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    // Mask emails for security
    foreach ($familyWithEmail as &$row) {
        if (!empty($row['email'])) {
            $row['email_hash'] = substr(hash('sha256', strtolower(trim($row['email']))), 0, 12);
            $row['email'] = '***@***';
        }
    }
    
    // Get count of all family members
    $countStmt = $db->query("SELECT COUNT(*) as total FROM family_members");
    $total = (int)($countStmt->fetch()['total'] ?? 0);
    
    json_response([
        'total_family_members' => $total,
        'family_with_email' => $familyWithEmail,
        'note' => 'Emails are masked. Use email_hash to compare.'
    ]);
}

// API: Manually trigger auto-populate for current user (temporary diagnostic)
if ($requestUri === '/api/trigger-autofill') {
    require_login();
    $userId = (int)$_SESSION['user_id'];
    $db = \Routina\Config\Database::getConnection();
    
    // Get current user's email
    $stmt = $db->prepare("SELECT email, phone FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['email'])) {
        json_response(['success' => false, 'error' => 'No email found for current user']);
    }
    
    $email = $user['email'];
    $phone = $user['phone'] ?? null;
    
    // Call the auto-populate function
    $result = \Routina\Services\AuthService::autoPopulateFromFamilyTree($userId, $email, $phone);
    
    // Get updated user data
    $stmt = $db->prepare("SELECT display_name, dob, gender, phone, relationship_status FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    $updated = $stmt->fetch();
    
    json_response([
        'success' => $result,
        'user_id' => $userId,
        'email_hash' => substr(hash('sha256', strtolower(trim($email))), 0, 12),
        'updated_fields' => $updated,
        'note' => $result ? 'Profile was updated from family tree data' : 'No updates applied (either no match found or fields already filled)'
    ]);
}

// 1. Root / Landing
if ($requestUri === '/' || $requestUri === '/index.php') {
    if (isset($_SESSION['user_id'])) {
        redirect('/dashboard');
    }
    view('landing/index');
    exit;
}

// 2. Auth Routes
if ($requestUri === '/login') {
    // If already logged in, redirect appropriately
    if (isset($_SESSION['user_id'])) {
        $db = \Routina\Config\Database::getConnection();
        $stmt = $db->prepare("SELECT routina_id, email FROM users WHERE id = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && empty($user['routina_id'])) {
            redirect('/setup-routina-id');
        } elseif ($user && empty($user['email'])) {
            redirect('/setup-recovery-email');
        } else {
            redirect('/dashboard');
        }
    }
    
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

if ($requestUri === '/reset-password') {
    $controller = new \Routina\Controllers\AuthController();
    $controller->resetPassword();
    exit;
}

if ($requestUri === '/setup-routina-id') {
    require_login();
    $controller = new \Routina\Controllers\AuthController();
    $controller->setupRoutinaId();
    exit;
}

if ($requestUri === '/setup-recovery-email') {
    require_login();
    $controller = new \Routina\Controllers\AuthController();
    $controller->setupRecoveryEmail();
    exit;
}

if ($requestUri === '/login/mfa') {
    $controller = new \Routina\Controllers\AuthController();
    $controller->mfaVerify();
    exit;
}

if ($requestUri === '/auth/google') {
    $controller = new \Routina\Controllers\AuthController();
    $controller->googleAuth();
    exit;
}

if ($requestUri === '/auth/google/callback') {
    $controller = new \Routina\Controllers\AuthController();
    $controller->googleCallback();
    exit;
}

// MFA Setup page
if ($requestUri === '/profile/security/mfa') {
    require_login();
    $controller = new \Routina\Controllers\ProfileController();
    $controller->mfaSettings();
    exit;
}

// Delete account page
if ($requestUri === '/profile/delete') {
    require_login();
    $controller = new \Routina\Controllers\ProfileController();
    $controller->deleteAccount();
    exit;
}

// Account deleted confirmation page
if ($requestUri === '/account-deleted') {
    view('account/account_deleted');
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

if ($requestUri === '/vacation/detail') {
    $controller = new \Routina\Controllers\VacationController();
    $controller->detail();
    exit;
}

if ($requestUri === '/vacation/delete' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\VacationController();
    $controller->delete();
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

if ($requestUri === '/finance/diary/detail') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->diaryDetail();
    exit;
}

if ($requestUri === '/finance/debts') {
    $controller = new \Routina\Controllers\FinanceController();
    $controller->debts();
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

if ($requestUri === '/vehicle/delete') {
    $controller = new \Routina\Controllers\VehicleController();
    $controller->delete();
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

if ($requestUri === '/admin/autofill') {
    require_admin();
    $controller = new \Routina\Controllers\AdminController();
    $controller->autofillDiagnostics();
    exit;
}

if ($requestUri === '/admin/diagnostics') {
    require_admin();
    $controller = new \Routina\Controllers\AdminController();
    $controller->diagnostics();
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

// Calendar routes
if ($requestUri === '/calendar/delete' && $method === 'POST') {
    require_login();
    $controller = new \Routina\Controllers\CalendarController();
    $controller->delete();
    exit;
}

if ($requestUri === '/calendar/api/events') {
    require_login();
    $controller = new \Routina\Controllers\CalendarController();
    $controller->apiEvents();
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

