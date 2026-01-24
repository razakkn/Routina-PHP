<?php
/**
 * Error page template.
 * 
 * Variables:
 * - $code: HTTP status code
 * - $title: Error title
 * - $message: Error message
 * - $showBackLink: Whether to show back link (default true)
 */
$code = $code ?? 500;
$title = $title ?? 'Error';
$message = $message ?? 'An unexpected error occurred.';
$showBackLink = $showBackLink ?? true;

$titles = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Access Denied',
    404 => 'Page Not Found',
    405 => 'Method Not Allowed',
    419 => 'Session Expired',
    422 => 'Validation Error',
    429 => 'Too Many Requests',
    500 => 'Server Error',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
];

$title = $title !== 'Error' ? $title : ($titles[$code] ?? 'Error');

$icons = [
    400 => '⚠️',
    401 => '🔒',
    403 => '🚫',
    404 => '🔍',
    405 => '❌',
    419 => '⏰',
    422 => '📝',
    429 => '🚦',
    500 => '💥',
    502 => '🔌',
    503 => '🔧',
];

$icon = $icons[$code] ?? '❗';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Routina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            padding: 3rem;
            max-width: 500px;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 700;
            color: #6c757d;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .error-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #6c757d;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        .btn-back {
            padding: 0.75rem 2rem;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon"><?= $icon ?></div>
        <div class="error-code"><?= (int)$code ?></div>
        <h1 class="error-title"><?= htmlspecialchars($title) ?></h1>
        <p class="error-message"><?= htmlspecialchars($message) ?></p>
        <?php if ($showBackLink): ?>
            <div class="d-flex gap-2 justify-content-center flex-wrap">
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-back">
                    ← Go Back
                </a>
                <a href="/dashboard" class="btn btn-primary btn-back">
                    Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
