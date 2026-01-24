<?php
// Mock User State for Layout
// In a real app, this would come from the Auth Service
$isAuthenticated = isset($_SESSION['user_id']);
$userData = isset($_SESSION['user_data']) ? $_SESSION['user_data'] : null;
$displayName = $userData['name'] ?? 'Guest';
$initials = substr($displayName, 0, 1);
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Helper for active class
function isActive($path, $current) {
    return strpos($current, $path) === 0 ? 'active' : '';
}

function moduleFromPath($path, $isAuthenticated) {
    $path = is_string($path) ? $path : '/';
    if (!$isAuthenticated) {
        return 'landing';
    }

    if ($path === '/' || $path === '/dashboard') return 'dashboard';
    if (strpos($path, '/journal') === 0) return 'journal';
    if (strpos($path, '/vacation') === 0) return 'vacation';
    if (strpos($path, '/finance') === 0) return 'finance';
    if (strpos($path, '/vehicle') === 0) return 'vehicle';
    if (strpos($path, '/home') === 0) return 'home';
    if (strpos($path, '/health') === 0) return 'health';
    if (strpos($path, '/calendar') === 0) return 'calendar';
    if (strpos($path, '/family') === 0) return 'family';
    if (strpos($path, '/profile') === 0 || strpos($path, '/account/profile') === 0) return 'profile';

    return 'dashboard';
}

$module = moduleFromPath($currentPath, $isAuthenticated);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Routina - PHP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="/css/site.css" />
    <link rel="stylesheet" href="/css/sidebar_slices.css" />
    <link rel="stylesheet" href="/css/canvas.css" />
    <link rel="stylesheet" href="/css/app-3d.css" />
    <?php if ($isAuthenticated && $module === 'dashboard'): ?>
        <link rel="stylesheet" href="/css/dashboard.css" />
    <?php endif; ?>
</head>
<body data-theme="light" data-sidebar-state="expanded" data-has-3d="<?php echo $isAuthenticated ? 'true' : 'false'; ?>" data-module="<?php echo htmlspecialchars($module); ?>">

<?php if ($isAuthenticated): ?>
    <canvas id="app-3d" aria-hidden="true"></canvas>
<?php endif; ?>

<div class="app-shell" data-has-sidebar="<?php echo $isAuthenticated ? 'true' : 'false'; ?>">
    <?php if ($isAuthenticated): ?>
        <aside class="app-sidebar" aria-label="Primary navigation">
            <div class="sidebar-surface">
                <div class="app-brand">
                    <div class="brand-mark">⧉</div>
                    <div>
                        <div class="brand-title">Routina</div>
                        <div class="brand-sub">Personal Timeline</div>
                    </div>
                    <button class="icon-button sidebar-close" type="button" data-sidebar-toggle aria-label="Collapse menu">
                        <span class="icon icon-close"></span>
                    </button>
                </div>

                <nav class="app-nav" role="navigation">
                    <a class="nav-item nav-dashboard <?php echo isActive('/dashboard', $currentPath); ?>" href="/dashboard" data-module="dashboard"><span class="nav-icon">✨</span><span>Dashboard</span></a>
                    <a class="nav-item nav-journal <?php echo isActive('/journal', $currentPath); ?>" href="/journal" data-module="journal"><span class="nav-icon">📓</span><span>Daily Journal</span></a>
                    <a class="nav-item nav-vacation <?php echo isActive('/vacation', $currentPath); ?>" href="/vacation" data-module="vacation"><span class="nav-icon">🏖️</span><span>Vacation Planner</span></a>
                    <a class="nav-item nav-finance <?php echo isActive('/finance', $currentPath); ?>" href="/finance" data-module="finance"><span class="nav-icon">💰</span><span>Finance</span></a>
                    <a class="nav-item nav-vehicle <?php echo isActive('/vehicle', $currentPath); ?>" href="/vehicle" data-module="vehicle"><span class="nav-icon">🚗</span><span>Vehicle Manager</span></a>
                    <a class="nav-item nav-home <?php echo isActive('/home', $currentPath); ?>" href="/home" data-module="home"><span class="nav-icon">🏠</span><span>Home Management</span></a>
                    <a class="nav-item nav-health <?php echo isActive('/health', $currentPath); ?>" href="/health" data-module="health"><span class="nav-icon">❤️</span><span>Health Tracker</span></a>
                    <a class="nav-item nav-calendar <?php echo isActive('/calendar', $currentPath); ?>" href="/calendar" data-module="calendar"><span class="nav-icon">🗓️</span><span>Calendar</span></a>
                    <a class="nav-item nav-family <?php echo isActive('/family', $currentPath); ?>" href="/family" data-module="family"><span class="nav-icon">🌳</span><span>Family Tree</span></a>
                </nav>

                <div class="app-sidebar-foot">
                    <div class="muted-sm">v0.5 • php-port</div>
                </div>
            </div>
        </aside>
        <button type="button" class="sidebar-backdrop" data-sidebar-dismiss aria-label="Close navigation"></button>
    <?php endif; ?>

    <main class="app-main">
        <header class="app-header">
            <div class="app-header-left">
                <?php if ($isAuthenticated): ?>
                    <button class="icon-button sidebar-toggle" type="button" data-sidebar-toggle aria-label="Toggle navigation">
                        <span class="icon icon-menu"></span>
                    </button>
                <?php endif; ?>
                <div class="app-header-meta">
                    <div class="app-header-title">Routina</div>
                    <nav class="app-breadcrumbs" aria-label="Breadcrumb">
                        <ol class="breadcrumb-list">
                            <li class="breadcrumb-item"><a href="/">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars(ltrim($currentPath, '/')); ?></li>
                        </ol>
                    </nav>
                </div>

                <?php if ($isAuthenticated && $module === 'dashboard'): ?>
                    <form class="top-search" role="search" method="get" action="/dashboard" aria-label="Search">
                        <input class="top-search__input" type="search" name="q" placeholder="Search…" autocomplete="off" value="<?php echo htmlspecialchars(trim((string)($_GET['q'] ?? ''))); ?>" />
                        <button class="top-search__btn" type="submit" aria-label="Search">
                            <span class="icon icon-search" aria-hidden="true"></span>
                        </button>
                        <?php if (trim((string)($_GET['q'] ?? '')) !== ''): ?>
                            <a class="top-search__clear" href="/dashboard">Clear</a>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>

            <div class="app-header-actions">
                <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle theme">
                    <span class="icon icon-sun"></span>
                    <span class="icon icon-moon"></span>
                </button>

                <?php if ($isAuthenticated): ?>
                    <div class="topbar-profile dropdown">
                        <a class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" href="#" id="accountMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" role="button" tabindex="0">
                            <div class="topbar-avatar initials"><?php echo $initials; ?></div>
                            <span class="topbar-identity"><?php echo htmlspecialchars($displayName); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountMenu">
                            <li><a class="dropdown-item" href="/profile">Profile</a></li>
                            <li><hr class="dropdown-divider" /></li>
                            <li>
                                <form method="post" action="/logout" class="logout-form">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item logout-btn">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn-soft" href="/login">Sign in</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if ($isAuthenticated): ?>
            <nav class="module-tabs" aria-label="Modules" data-current-path="<?php echo htmlspecialchars($currentPath); ?>">
                <a class="module-tab" href="/dashboard" data-module="dashboard">Dashboard</a>
                <a class="module-tab" href="/journal" data-module="journal">Journal</a>
                <a class="module-tab" href="/vacation" data-module="vacation">Travel</a>
                <a class="module-tab" href="/finance" data-module="finance">Finance</a>
                <a class="module-tab" href="/vehicle" data-module="vehicle">Vehicle</a>
                <a class="module-tab" href="/home" data-module="home">Home</a>
                <a class="module-tab" href="/health" data-module="health">Health</a>
                <a class="module-tab" href="/calendar" data-module="calendar">Calendar</a>
                <a class="module-tab" href="/family" data-module="family">Family</a>
            </nav>
        <?php endif; ?>

        <div class="app-content container-fluid">
            <?php echo $content; ?>
        </div>

        <footer class="app-footer">
            <span class="muted-sm">Routina • your quiet timeline</span>
        </footer>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/site.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script>
<script src="/js/app-3d.js" defer></script>
<script src="/js/vehicle-make-model.js" defer></script>
</body>
</html>
