<?php

namespace Routina\Controllers;

use Routina\Models\Buzz;

class BuzzController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $tab = $_GET['tab'] ?? 'inbox';
        if (!in_array($tab, ['inbox', 'outbox'], true)) {
            $tab = 'inbox';
        }

        $userId = (int)$_SESSION['user_id'];
        $inbox = [];
        $outbox = [];
        $dbError = '';

        try {
            $inbox = Buzz::inbox($userId, 80);
            $outbox = Buzz::outbox($userId, 80);
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        view('buzz/index', [
            'tab' => $tab,
            'inbox' => $inbox,
            'outbox' => $outbox,
            'sent' => !empty($_GET['sent']),
            'dbError' => $dbError
        ]);
    }

    public function send() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $fromUserId = (int)$_SESSION['user_id'];
        $toUserId = (int)($_POST['to_user_id'] ?? 0);
        $familyMemberId = (int)($_POST['family_member_id'] ?? 0);
        $channel = trim((string)($_POST['channel'] ?? 'in_app'));
        $message = trim((string)($_POST['message'] ?? ''));

        if ($toUserId <= 0 || $toUserId === $fromUserId) {
            header('Location: /family?view=tree');
            exit;
        }

        Buzz::create($fromUserId, $toUserId, $familyMemberId, $channel, $message);

        $returnTo = $_POST['return_to'] ?? '';
        if (is_string($returnTo) && $returnTo !== '' && substr($returnTo, 0, 1) === '/') {
            $join = (strpos($returnTo, '?') !== false) ? '&' : '?';
            header('Location: ' . $returnTo . $join . 'buzz=sent');
            exit;
        }

        header('Location: /buzz?sent=1');
        exit;
    }

    public function mark() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $buzzId = (int)($_GET['id'] ?? 0);
        $status = (string)($_POST['status'] ?? 'acknowledged');

        if ($buzzId > 0) {
            Buzz::setStatusForRecipient($userId, $buzzId, $status);
        }

        header('Location: /buzz');
        exit;
    }

    public function markAll() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        Buzz::acknowledgeAllPending($userId);

        header('Location: /buzz?tab=inbox');
        exit;
    }
}
