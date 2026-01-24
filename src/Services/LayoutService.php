<?php

namespace Routina\Services;

use Routina\Models\Buzz;

class LayoutService {
    public static function getGlobalData(?int $userId, string $currentPath) {
        $isAuthenticated = ($userId !== null && $userId > 0);
        $module = self::moduleFromPath($currentPath, $isAuthenticated);
        
        $buzzUnread = 0;
        $buzzPreview = [];
        
        if ($isAuthenticated) {
            try {
                $buzzUnread = Buzz::unreadCount($userId);
                // Optionally fetch preview if we want to show a dropdown
                if ($buzzUnread > 0) {
                     $buzzPreview = Buzz::preview($userId, 5);
                }
            } catch (\Throwable $e) {
                // Fail silently for UI counters
                error_log("LayoutService Error: " . $e->getMessage());
            }
        }

        $buzzBadgeLabel = '';
        if ($buzzUnread > 0) {
            $buzzBadgeLabel = ($buzzUnread > 99) ? '99+' : (string)$buzzUnread;
        }

        return (object)[
            'Module' => $module,
            'BuzzUnread' => $buzzUnread,
            'BuzzBadgeLabel' => $buzzBadgeLabel,
            'BuzzPreview' => $buzzPreview
        ];
    }

    private static function moduleFromPath(string $path, bool $isAuthenticated): string {
        if (!$isAuthenticated) {
            return 'landing';
        }

        if ($path === '/' || $path === '/dashboard') return 'dashboard';
        
        $map = [
            '/journal' => 'journal',
            '/vacation' => 'vacation',
            '/finance' => 'finance',
            '/vehicle' => 'vehicle',
            '/home' => 'home',
            '/health' => 'health',
            '/calendar' => 'calendar',
            '/family' => 'family',
            '/buzz' => 'buzz',
            '/profile' => 'profile',
            '/account/profile' => 'profile',
        ];

        foreach ($map as $prefix => $mod) {
            if (strpos($path, $prefix) === 0) {
                return $mod;
            }
        }

        return 'dashboard';
    }
}
