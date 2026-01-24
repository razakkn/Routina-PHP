<?php

namespace Routina\Controllers;

use Routina\Models\Family;

class FamilyController {
    private function validateParentRefs($userId, $memberId, $motherId, $fatherId) {
        $errors = [];

        $motherId = (int)$motherId;
        $fatherId = (int)$fatherId;

        if ($motherId > 0 && $memberId > 0 && $motherId === (int)$memberId) {
            $errors[] = 'Mother cannot be the same person.';
        }
        if ($fatherId > 0 && $memberId > 0 && $fatherId === (int)$memberId) {
            $errors[] = 'Father cannot be the same person.';
        }
        if ($motherId > 0 && $fatherId > 0 && $motherId === $fatherId) {
            $errors[] = 'Mother and Father cannot be the same person.';
        }

        if ($userId > 0) {
            if ($motherId > 0 && !Family::findByIdForUser($userId, $motherId)) {
                $errors[] = 'Selected mother is not valid.';
            }
            if ($fatherId > 0 && !Family::findByIdForUser($userId, $fatherId)) {
                $errors[] = 'Selected father is not valid.';
            }
        }

        return $errors;
    }

    private function validateMemberPayload($post, $userId = 0, $memberId = 0) {
        $name = trim((string)($post['name'] ?? ''));
        $relation = trim((string)($post['relation'] ?? ''));
        $gender = trim((string)($post['gender'] ?? ''));
        $birthdate = trim((string)($post['birthdate'] ?? ''));
        $deathdate = trim((string)($post['deathdate'] ?? ''));
        $sideOfFamily = trim((string)($post['side_of_family'] ?? ''));
        $phone = trim((string)($post['phone'] ?? ''));
        $noEmail = !empty($post['no_email']) ? 1 : 0;
        $email = isset($post['email']) ? trim((string)$post['email']) : '';
        $motherId = isset($post['mother_id']) ? (int)$post['mother_id'] : 0;
        $fatherId = isset($post['father_id']) ? (int)$post['father_id'] : 0;

        $errors = [];
        if ($name === '') {
            $errors[] = 'Please provide a name.';
        }
        if ($relation === '') {
            $errors[] = 'Please select a relationship.';
        }
        if ($phone === '') {
            $errors[] = 'Contact number is required.';
        }
        if (!$noEmail && $email === '') {
            $errors[] = 'Email address is required (or tick "No email available").';
        }

        $errors = array_merge($errors, $this->validateParentRefs((int)$userId, (int)$memberId, $motherId, $fatherId));

        $payload = [
            'name' => $name,
            'relation' => $relation,
            'gender' => $gender,
            'birthdate' => $birthdate,
            'deathdate' => $deathdate,
            'side_of_family' => $sideOfFamily,
            'email' => $email,
            'phone' => $phone,
            'no_email' => $noEmail,
            'mother_id' => $motherId,
            'father_id' => $fatherId
        ];

        return [$payload, $errors];
    }

    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$payload, $errors] = $this->validateMemberPayload($_POST, (int)$_SESSION['user_id'], 0);
            if (!empty($errors)) {
                $members = Family::getAll($_SESSION['user_id']);
                $matches = Family::getContactMatches((int)$_SESSION['user_id'], $members);
                view('family/index', [
                    'members' => $members,
                    'matches' => $matches,
                    'error' => implode(' ', $errors)
                ]);
                return;
            }

            Family::createExtended($_SESSION['user_id'], [
                'name' => $payload['name'],
                'relation' => $payload['relation'],
                'gender' => $payload['gender'],
                'birthdate' => $payload['birthdate'],
                'deathdate' => $payload['deathdate'],
                'side_of_family' => $payload['side_of_family'],
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'no_email' => $payload['no_email'],
                'mother_id' => $payload['mother_id'],
                'father_id' => $payload['father_id'],
                'created_at' => gmdate('Y-m-d H:i:s')
            ]);
            header('Location: /family');
            exit;
        }

        $members = Family::getAll($_SESSION['user_id']);
        $matches = Family::getContactMatches((int)$_SESSION['user_id'], $members);
        view('family/index', ['members' => $members, 'matches' => $matches]);
    }

    public function edit() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $memberId = (int)($_GET['id'] ?? 0);
        if ($memberId <= 0) {
            header('Location: /family');
            exit;
        }

        $member = Family::findByIdForUser($userId, $memberId);
        if (!$member) {
            header('Location: /family');
            exit;
        }

        $members = Family::getAll($userId);
        view('family/edit', ['member' => $member, 'members' => $members]);
    }

    public function update() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $memberId = (int)($_GET['id'] ?? 0);
        if ($memberId <= 0) {
            header('Location: /family');
            exit;
        }

        $member = Family::findByIdForUser($userId, $memberId);
        if (!$member) {
            header('Location: /family');
            exit;
        }

        [$payload, $errors] = $this->validateMemberPayload($_POST, $userId, $memberId);
        if (!empty($errors)) {
            $members = Family::getAll($userId);
            view('family/edit', [
                'member' => array_merge($member, $payload),
                'members' => $members,
                'error' => implode(' ', $errors)
            ]);
            return;
        }

        Family::updateByIdForUser($userId, $memberId, $payload);
        header('Location: /family');
        exit;
    }

    public function updateParents() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $memberId = (int)($_GET['id'] ?? 0);
        if ($memberId <= 0) {
            header('Location: /family');
            exit;
        }

        $member = Family::findByIdForUser($userId, $memberId);
        if (!$member) {
            header('Location: /family');
            exit;
        }

        $motherId = isset($_POST['mother_id']) ? (int)$_POST['mother_id'] : 0;
        $fatherId = isset($_POST['father_id']) ? (int)$_POST['father_id'] : 0;

        $errors = $this->validateParentRefs($userId, $memberId, $motherId, $fatherId);
        if (!empty($errors)) {
            $members = Family::getAll($userId);
            $matches = Family::getContactMatches($userId, $members);
            $_GET['view'] = 'list';
            view('family/index', [
                'members' => $members,
                'matches' => $matches,
                'error' => implode(' ', $errors)
            ]);
            return;
        }

        Family::updateParentsByIdForUser($userId, $memberId, $motherId, $fatherId);

        $returnTo = $_POST['return_to'] ?? '';
        if (is_string($returnTo) && $returnTo !== '' && substr($returnTo, 0, 1) === '/') {
            header('Location: ' . $returnTo);
            exit;
        }

        header('Location: /family?view=list');
        exit;
    }

    public function delete() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $memberId = (int)($_GET['id'] ?? 0);
        if ($memberId <= 0) {
            header('Location: /family');
            exit;
        }

        // If this member is currently linked as partner, unlink.
        try {
            $user = \Routina\Models\User::find($userId);
            if ($user && (int)($user->partner_member_id ?? 0) === $memberId) {
                $user->partner_member_id = null;
                $user->save();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Clear mother/father references pointing to this member.
        try {
            Family::clearParentRefsForUser($userId, $memberId);
        } catch (\Throwable $e) {
            // ignore
        }

        Family::deleteByIdForUser($userId, $memberId);
        header('Location: /family');
        exit;
    }
}
