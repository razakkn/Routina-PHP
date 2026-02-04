<?php
// Mock User State for Layout
// In a real app, this would come from the Auth Service
$isAuthenticated = isset($_SESSION['user_id']);
$userData = isset($_SESSION['user_data']) ? $_SESSION['user_data'] : null;
$displayName = $userData['name'] ?? 'Guest';
$initials = substr($displayName, 0, 1);
$topbarAvatarUrl = null;
$topbarAvatarPreset = null;
if ($isAuthenticated && isset($_SESSION['user_id'])) {
    try {
        $u = \Routina\Models\User::find((int)$_SESSION['user_id']);
        if ($u) {
            if (!empty($u->avatar_image_url)) {
                $topbarAvatarUrl = $u->avatar_image_url;
            } elseif (!empty($u->avatar_preset_key)) {
                $topbarAvatarPreset = $u->avatar_preset_key;
            }
        }
    } catch (\Throwable $e) {
        $topbarAvatarUrl = null;
        $topbarAvatarPreset = null;
    }
}
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Helper for active class
function isActive($path, $current) {
    return strpos($current, $path) === 0 ? 'active' : '';
}

// Use LayoutService for consolidated logic
$layoutData = \Routina\Services\LayoutService::getGlobalData(
    isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
    $currentPath
);

// Backward compat locals if needed, though better to use $layoutData->Key
$module = (is_object($layoutData) && isset($layoutData->Module)) ? (string)$layoutData->Module : ($isAuthenticated ? 'dashboard' : 'landing');
$buzzUnread = $layoutData->BuzzUnread;
$buzzBadgeLabel = $layoutData->BuzzBadgeLabel;
$buzzPreview = $layoutData->BuzzPreview;
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
    <?php if ($isAuthenticated && $module === 'vacation'): ?>
        <link rel="stylesheet" href="/css/place_autocomplete.css" />
    <?php endif; ?>
    <?php if ($module === 'dashboard'): ?>
        <link rel="stylesheet" href="/css/dashboard_v2.css" />
    <?php endif; ?>
    <!-- Favicon -->
    <link rel="icon" href="/favicon.ico" />
    <?php if ($isAuthenticated && $module === 'profile'): ?>
        <link rel="stylesheet" href="/css/profile.css" />
    <?php endif; ?>
    <?php if ($isAuthenticated && $module === 'family'): ?>
        <link rel="stylesheet" href="/css/family_tree.css" />
    <?php endif; ?>
</head>
<body data-theme="light" data-sidebar-state="expanded" data-module="<?php echo htmlspecialchars($module); ?>">

<?php if ($isAuthenticated): ?><?php endif; ?>

<div class="app-shell" data-has-sidebar="<?php echo $isAuthenticated ? 'true' : 'false'; ?>">
    <?php if ($isAuthenticated): ?>
        <aside class="app-sidebar" aria-label="Primary navigation">
            <div class="sidebar-surface">
                <div class="app-brand">
                    <div class="brand-mark">⧉</div>
                    <div>
                        <div class="brand-title">Routina</div>
                        <div class="brand-sub brand-tagline">One place. Your whole life.</div>
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
                    <a class="nav-item nav-buzz <?php echo isActive('/buzz', $currentPath); ?>" href="/buzz" data-module="buzz"><span class="nav-icon">📣</span><span>Buzz</span><?php if ($buzzBadgeLabel !== ''): ?><span class="nav-badge" aria-label="Unread buzz requests"><?php echo htmlspecialchars($buzzBadgeLabel); ?></span><?php endif; ?></a>
                </nav>

                <div class="app-sidebar-foot">
                    <div class="muted-sm"><?php echo htmlspecialchars(quote_of_the_day()); ?></div>
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
                    <div class="app-header-title">Routina <span class="brand-tagline">One place. Your whole life.</span></div>
                </div>
            </div>

            <div class="app-header-actions">
                <button class="icon-button theme-toggle" type="button" aria-label="Toggle theme" aria-pressed="false">
                    <span class="icon icon-sun" aria-hidden="true">☀️</span>
                    <span class="icon icon-moon" aria-hidden="true">🌙</span>
                </button>
                <?php if ($isAuthenticated): ?>
                    <div class="topbar-buzz-wrapper dropdown">
                        <a class="icon-button topbar-buzz dropdown-toggle" href="#" id="buzzMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" role="button" aria-label="Buzz">
                            <span class="icon icon-buzz" aria-hidden="true"></span>
                            <?php if ($buzzBadgeLabel !== ''): ?>
                                <span class="topbar-badge" aria-label="Unread buzz requests"><?php echo htmlspecialchars($buzzBadgeLabel); ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end buzz-dropdown" aria-labelledby="buzzMenu">
                            <li><h6 class="dropdown-header">Buzz Requests</h6></li>
                            <?php if (empty($buzzPreview)): ?>
                                <li><div class="dropdown-item-text text-muted small">No new requests</div></li>
                            <?php else: ?>
                                <?php foreach ($buzzPreview as $bp): ?>
                                    <li>
                                        <a class="dropdown-item" href="/buzz">
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold small"><?php echo htmlspecialchars((string)($bp['from_display_name'] ?? 'User')); ?></span>
                                                <span class="text-truncate small text-muted" style="max-width: 180px;">
                                                    <?php echo htmlspecialchars((string)($bp['message'] ?? 'Buzz')); ?>
                                                </span>
                                            </div>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <?php if ($buzzUnread > count($buzzPreview)): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-center small text-primary" href="/buzz">See all <?php echo $buzzUnread; ?></a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center small" href="/buzz">Go to Inbox</a></li>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($isAuthenticated): ?>
                    <div class="topbar-profile dropdown">
                        <a class="d-flex align-items-center gap-2 text-decoration-none dropdown-toggle" href="#" id="accountMenu" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" role="button" tabindex="0">
                            <?php if (!empty($topbarAvatarUrl)): ?>
                                <img class="topbar-avatar-img" src="<?php echo htmlspecialchars($topbarAvatarUrl); ?>" alt="Avatar" />
                            <?php elseif (!empty($topbarAvatarPreset)): ?>
                                <img class="topbar-avatar-img" src="<?php echo htmlspecialchars(avatar_preset_url($topbarAvatarPreset)); ?>" alt="Avatar" />
                            <?php else: ?>
                                <div class="topbar-avatar initials"><?php echo $initials; ?></div>
                            <?php endif; ?>
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
                <a class="module-tab" href="/buzz" data-module="buzz">Buzz<?php if ($buzzBadgeLabel !== ''): ?><span class="module-badge" aria-label="Unread buzz requests"><?php echo htmlspecialchars($buzzBadgeLabel); ?></span><?php endif; ?></a>
            </nav>
        <?php endif; ?>

        <div class="app-content container-fluid">
            <?php echo $content; ?>
        </div>

        <footer class="app-footer">
            <span class="muted-sm"><?php echo htmlspecialchars(quote_of_the_day()); ?></span>
        </footer>
        <?php if ($isAuthenticated): ?>
            <button class="mobile-menu-fab" type="button" data-sidebar-toggle aria-label="Open menu">
                <span class="icon icon-menu"></span>
            </button>
        <?php endif; ?>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="/js/site.js"></script>
<?php if ($isAuthenticated): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js" defer></script><?php endif; ?>
<?php if ($isAuthenticated && $module === 'vehicle'): ?>
    <script src="/js/vehicle-make-model.js" defer></script>
<?php endif; ?>
<?php if ($isAuthenticated && $module === 'vacation'): ?>
    <script src="/js/place_autocomplete.js" defer></script>
<?php endif; ?>
<?php if ($module === 'dashboard'): ?>
    <script src="/js/dashboard_v2.js" defer></script>
<?php endif; ?>
</body>
</html>








