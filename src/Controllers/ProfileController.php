<?php

namespace Routina\Controllers;

use Routina\Models\Family;
use Routina\Models\User;
use Routina\Services\CurrencyService;
use Routina\Services\HolidayService;

class ProfileController {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];
        $user = User::find($userId);

        if (!$user) {
            // Session may be stale (user deleted). Force re-auth.
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            header('Location: /login');
            exit;
        }

        $activeSection = $_GET['section'] ?? 'profile';
        if (!in_array($activeSection, ['profile', 'avatar'], true)) {
            $activeSection = 'profile';
        }

        $mode = $_GET['mode'] ?? 'view';
        if (!in_array($mode, ['view', 'edit'], true)) {
            $mode = 'view';
        }
        
        $partner = null;
        if (!empty($user->partner_member_id)) {
            $partner = Family::findByIdForUser((int)$user->id, (int)$user->partner_member_id);
        }

        $familyMembers = Family::getAll((int)$user->id);
        $linkedFamily = [];
        if (!empty($user->email)) {
            $rawLinked = Family::findLinkedToEmail($user->email);
            foreach ($rawLinked as $lf) {
                $ownerUser = User::find((int)($lf['owner_user_id'] ?? 0));
                if ($ownerUser) {
                    $linkedFamily[] = [
                        'owner_name' => $ownerUser->display_name,
                        'owner_email' => $ownerUser->email,
                        'owner_dob' => $ownerUser->dob,
                        'relation' => $lf['relation'],
                        'birthdate' => $ownerUser->dob,
                    ];
                }
            }
        }

        $currencyOptions = CurrencyService::all();
        $currentCurrencyCode = CurrencyService::normalizeCode($user->currency ?? 'USD');
        if ($currentCurrencyCode !== '' && !isset($currencyOptions[$currentCurrencyCode])) {
            // Preserve previously saved codes even if not in the curated list.
            $currencyOptions[$currentCurrencyCode] = $currentCurrencyCode;
            ksort($currencyOptions);
        }

        // Sort for display: spouse/partner first, then children, then others.
        $rank = function ($rel) {
            $r = strtolower(trim((string)$rel));
            if (in_array($r, ['spouse', 'wife', 'husband', 'boyfriend', 'girlfriend'], true)) return 0;
            if (in_array($r, ['child', 'son', 'daughter'], true)) return 1;
            if (in_array($r, ['mother', 'father', 'parent'], true)) return 2;
            return 3;
        };
        usort($familyMembers, function ($a, $b) use ($rank) {
            $ra = $rank($a['relation'] ?? '');
            $rb = $rank($b['relation'] ?? '');
            if ($ra !== $rb) return $ra <=> $rb;
            return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });

        $model = (object)[
            'ActiveSection' => $activeSection,
            'Mode' => $mode,
            'Input' => [
                'Email' => $user->email,
                'DisplayName' => $user->display_name,
                'JobTitle' => $user->job_title,
                'Headline' => $user->headline,
                'Phone' => $user->phone,
                'Address' => $user->address,
                'Bio' => $user->bio,
                'LinkedIn' => $user->linkedin,
                'Instagram' => $user->instagram,
                'Twitter' => $user->twitter,
                'Website' => $user->website,
                'Dob' => $user->dob,
                'Gender' => $user->gender,
                'CountryOfOrigin' => $user->country_of_origin,
                'CurrentLocation' => $user->current_location,
                'HolidayCountry' => $user->holiday_country,
                'PreferredCurrencyCode' => $user->currency,
                'PreferredCurrencyLabel' => CurrencyService::labelFor($user->currency ?? 'USD'),
                'SpouseCount' => $user->spouse_count,

                'RelationshipStatus' => $user->relationship_status ?? 'single',
                'FamilyRelation' => $user->family_relation,
                'PartnerType' => (is_array($partner) ? ($partner['relation'] ?? '') : ''),
                'PartnerName' => (is_array($partner) ? ($partner['name'] ?? '') : ''),
                'PartnerDob' => (is_array($partner) ? ($partner['birthdate'] ?? '') : ''),
                'PartnerGender' => (is_array($partner) ? ($partner['gender'] ?? '') : ''),
                'PartnerEmail' => (is_array($partner) ? ($partner['email'] ?? '') : ''),
                'PartnerPhone' => (is_array($partner) ? ($partner['phone'] ?? '') : ''),
                'PartnerNoEmail' => (is_array($partner) ? (!empty($partner['no_email']) ? 1 : 0) : 0)
                ,'ShareProfilePublicly' => !empty($user->share_profile_publicly) ? 1 : 0
            ],
            'Avatar' => (object)[
                'HasImage' => !empty($user->avatar_image_url) || !empty($user->avatar_preset_key),
                'ImageUrl' => $user->avatar_image_url, 
                'UpdatedUtc' => null,
                'HasCustomAsset' => !empty($user->avatar_image_url), 
                'PresetKey' => $user->avatar_preset_key
            ],
             'AvatarPresets' => [
                 'lavender' => 'Lavender',
                 'sage' => 'Sage',
                 'teal' => 'Teal',
                 'coral' => 'Coral'
             ],
             'RelationshipOptions' => [
                 'single' => 'Single',
                 'in_relationship' => 'In a relationship',
                 'married' => 'Married'
             ],
            'CurrencyOptions' => $currencyOptions,
            'HolidayCountryOptions' => HolidayService::commonCountries(),
             'StatusMessage' => $_SESSION['flash_message'] ?? null,
             'UserId' => $user->id,
             'LoggedSpouseCount' => $user->spouse_count,
            'ChildMembers' => [],
            'FamilyMembers' => $familyMembers,
            'LinkedFamily' => $linkedFamily
        ];
        
        unset($_SESSION['flash_message']);

        view('account/profile', ['Model' => $model]);
    }

    public function update() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $user = User::find($userId);
        
        if (!$user) {
             header('Location: /');
             exit;
        }

        $section = $_GET['section'] ?? 'profile';
        if (!in_array($section, ['profile', 'avatar'], true)) {
            $section = 'profile';
        }

        if ($section === 'profile') {
            $user->display_name = $_POST['DisplayName'] ?? $user->display_name;
            $user->job_title = $_POST['JobTitle'] ?? $user->job_title;
            $user->headline = $_POST['Headline'] ?? $user->headline;
            $user->phone = $_POST['Phone'] ?? $user->phone;
            $user->address = $_POST['Address'] ?? $user->address;
            $user->bio = $_POST['Bio'] ?? $user->bio;
            $user->linkedin = $_POST['LinkedIn'] ?? $user->linkedin;
            $user->instagram = $_POST['Instagram'] ?? $user->instagram;
            $user->twitter = $_POST['Twitter'] ?? $user->twitter;
            $user->website = $_POST['Website'] ?? $user->website;
            $user->dob = $_POST['Dob'] ?? $user->dob;
            $user->gender = $_POST['Gender'] ?? $user->gender;
            $user->country_of_origin = $_POST['CountryOfOrigin'] ?? $user->country_of_origin;
            $user->current_location = $_POST['CurrentLocation'] ?? $user->current_location;
            $holidayCountryCustom = strtoupper(trim((string)($_POST['HolidayCountryCustom'] ?? '')));
            $holidayCountrySelect = strtoupper(trim((string)($_POST['HolidayCountry'] ?? '')));
            $holidayCountry = $holidayCountryCustom !== '' ? $holidayCountryCustom : $holidayCountrySelect;
            if ($holidayCountry !== '' && preg_match('/^[A-Z]{2}$/', $holidayCountry)) {
                $user->holiday_country = $holidayCountry;
            } elseif ($holidayCountry === '') {
                $user->holiday_country = null;
            }

            $postedCurrencyCustom = trim((string)($_POST['PreferredCurrencyCodeCustom'] ?? ''));
            $postedCurrencySelect = trim((string)($_POST['PreferredCurrencyCode'] ?? ''));
            $postedCurrency = ($postedCurrencyCustom !== '') ? $postedCurrencyCustom : $postedCurrencySelect;

            if ($postedCurrency !== '') {
                $normalized = CurrencyService::normalizeCode($postedCurrency);
                // Accept any 3-letter code (covers edge/legacy/special currencies).
                if (CurrencyService::isValidCode($normalized)) {
                    $user->currency = $normalized;
                }
            }
            $spouseCount = (int)($_POST['SpouseCount'] ?? $user->spouse_count);
            $user->spouse_count = max(0, min(10, $spouseCount));

            $relationshipStatus = trim((string)($_POST['RelationshipStatus'] ?? ($user->relationship_status ?? 'single')));
            if (!in_array($relationshipStatus, ['single', 'in_relationship', 'married'], true)) {
                $relationshipStatus = 'single';
            }

            $partnerType = trim((string)($_POST['PartnerType'] ?? ''));
            $partnerName = trim((string)($_POST['PartnerName'] ?? ''));
            $partnerDob = trim((string)($_POST['PartnerDob'] ?? ''));
            $partnerGender = trim((string)($_POST['PartnerGender'] ?? ''));
            $partnerPhone = trim((string)($_POST['PartnerPhone'] ?? ''));
            $partnerNoEmail = !empty($_POST['PartnerNoEmail']) ? 1 : 0;
            $partnerEmail = isset($_POST['PartnerEmail']) ? trim((string)$_POST['PartnerEmail']) : '';

            $user->relationship_status = $relationshipStatus;
            $user->share_profile_publicly = !empty($_POST['ShareProfilePublicly']) ? true : false;

            $profileErrors = [];
            if (in_array($relationshipStatus, ['in_relationship', 'married'], true)) {
                if ($relationshipStatus === 'married') {
                    $partnerType = 'Spouse';
                } else {
                    if (!in_array($partnerType, ['Boyfriend', 'Girlfriend'], true)) {
                        $partnerType = '';
                    }
                }

                if ($partnerName === '') {
                    $profileErrors[] = 'Partner name is required.';
                }
                if ($partnerPhone === '') {
                    $profileErrors[] = 'Partner contact number is required.';
                }
                if (!$partnerNoEmail && $partnerEmail === '') {
                    $profileErrors[] = 'Partner email is required (or tick "No email available").';
                }

                if ($relationshipStatus === 'in_relationship' && $partnerType === '') {
                    $profileErrors[] = 'Please select girlfriend/boyfriend.';
                }
            }

            if (!empty($profileErrors)) {
                $_SESSION['flash_message'] = implode(' ', $profileErrors);
                $mode = $_GET['mode'] ?? 'view';
                if (!in_array($mode, ['view', 'edit'], true)) {
                    $mode = 'view';
                }
                header('Location: /profile?section=profile&mode=' . $mode);
                exit;
            }

            // Auto-populate partner into family tree.
            if (in_array($relationshipStatus, ['in_relationship', 'married'], true)) {
                $partnerPayload = [
                    'name' => $partnerName,
                    'relation' => $partnerType,
                    'birthdate' => $partnerDob,
                    'gender' => $partnerGender,
                    'side_of_family' => 'Partner',
                    'email' => $partnerEmail,
                    'phone' => $partnerPhone,
                    'no_email' => $partnerNoEmail
                ];

                $existingId = (int)($user->partner_member_id ?? 0);
                if ($existingId > 0 && Family::findByIdForUser($userId, $existingId)) {
                    Family::updateByIdForUser($userId, $existingId, $partnerPayload);
                } else {
                    $newId = Family::createExtended($userId, $partnerPayload);
                    if ($newId) {
                        $user->partner_member_id = (int)$newId;
                    }
                }
            } else {
                // Keep old member (if any) but stop auto-syncing.
                $user->partner_member_id = null;
            }

            $user->save();

            // Sync session for layout
            if (isset($_SESSION['user_data'])) {
                $_SESSION['user_data']['name'] = $user->display_name;
            }

            // Propagate profile fields to any linked family member entries in other users' trees
            try {
                \Routina\Models\Family::updateLinkedRecordsForUser((int)$user->id, [
                    'display_name' => $user->display_name,
                    'dob' => $user->dob,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'gender' => $user->gender
                ]);
            } catch (\Throwable $e) {
                // ignore propagation errors
            }

            $_SESSION['flash_message'] = "Profile updated successfully!";
        } elseif ($section === 'avatar') {
            
            // 1. Preset Selection
            if (!empty($_POST['AvatarPresetKey'])) {
                 $user->avatar_preset_key = $_POST['AvatarPresetKey'];
                 // Clear custom if preset selected (and remove previous custom file if any)
                 if (is_string($user->avatar_image_url) && str_starts_with($user->avatar_image_url, '/uploads/avatars/')) {
                     $oldPath = __DIR__ . '/../../public' . $user->avatar_image_url;
                     if (is_file($oldPath)) {
                         @unlink($oldPath);
                     }
                 }
                 $user->avatar_image_url = null;
            }

            // 2. File Upload
            if (isset($_FILES['AvatarFile']) && $_FILES['AvatarFile']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

                // Basic size limit (2MB)
                $maxBytes = 2 * 1024 * 1024;
                $size = (int)($_FILES['AvatarFile']['size'] ?? 0);
                if ($size <= 0 || $size > $maxBytes) {
                    $_SESSION['flash_message'] = 'Avatar file must be under 2MB.';
                    $mode = $_GET['mode'] ?? 'view';
                    if (!in_array($mode, ['view', 'edit'], true)) {
                        $mode = 'view';
                    }
                    header('Location: /profile?section=avatar&mode=' . $mode);
                    exit;
                }
                
                $ext = pathinfo($_FILES['AvatarFile']['name'], PATHINFO_EXTENSION);
                // Basic validation
                if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    // Validate that the uploaded file is actually an image
                    $tmp = $_FILES['AvatarFile']['tmp_name'] ?? '';
                    $isImage = false;
                    if (is_string($tmp) && $tmp !== '' && is_file($tmp)) {
                        if (function_exists('exif_imagetype')) {
                            $isImage = exif_imagetype($tmp) !== false;
                        } else {
                            $info = @getimagesize($tmp);
                            $isImage = is_array($info);
                        }
                    }

                    if (!$isImage) {
                        $_SESSION['flash_message'] = 'Invalid image file.';
                        $mode = $_GET['mode'] ?? 'view';
                        if (!in_array($mode, ['view', 'edit'], true)) {
                            $mode = 'view';
                        }
                        header('Location: /profile?section=avatar&mode=' . $mode);
                        exit;
                    }

                    $fileName = 'user_' . $userId . '_' . time() . '.' . $ext;
                    $destPath = $uploadDir . $fileName;
                    
                    if (move_uploaded_file($_FILES['AvatarFile']['tmp_name'], $destPath)) {
                        // Remove previous custom avatar file if any
                        if (is_string($user->avatar_image_url) && str_starts_with($user->avatar_image_url, '/uploads/avatars/')) {
                            $oldPath = __DIR__ . '/../../public' . $user->avatar_image_url;
                            if (is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                        $user->avatar_image_url = '/uploads/avatars/' . $fileName;
                        $user->avatar_preset_key = null; 
                    }
                }
            }
            
            // 3. Delete/Remove
            if (!empty($_POST['DeleteAvatar'])) {
                if (is_string($user->avatar_image_url) && str_starts_with($user->avatar_image_url, '/uploads/avatars/')) {
                    $oldPath = __DIR__ . '/../../public' . $user->avatar_image_url;
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $user->avatar_image_url = null;
                $user->avatar_preset_key = null;
            }

            $user->save();
            $_SESSION['flash_message'] = "Avatar updated successfully!";
        }

        $mode = $_GET['mode'] ?? 'view';
        if (!in_array($mode, ['view', 'edit'], true)) {
            $mode = 'view';
        }
        header('Location: /profile?section=' . $section . '&mode=' . $mode);
        exit;
    }

    /**
     * MFA Settings - Setup or disable two-factor authentication
     */
    public function mfaSettings() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $action = $_GET['action'] ?? 'view';
        
        $mfaStatus = \Routina\Services\AuthService::getMfaStatus($userId);

        // Handle disable action
        if ($action === 'disable') {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $password = $_POST['password'] ?? '';
                
                // Verify current password
                $db = \Routina\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    \Routina\Services\AuthService::disableMfa($userId);
                    $_SESSION['flash_message'] = 'Two-factor authentication has been disabled.';
                    header('Location: /profile');
                    exit;
                }
                
                view('account/mfa_disable', ['error' => 'Incorrect password']);
                return;
            }
            
            view('account/mfa_disable');
            return;
        }

        // Handle setup action
        if ($action === 'setup') {
            // Generate new secret if not in session
            if (empty($_SESSION['mfa_setup_secret'])) {
                $_SESSION['mfa_setup_secret'] = \Routina\Services\AuthService::generateMfaSecret();
            }
            $secret = $_SESSION['mfa_setup_secret'];

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $code = $_POST['code'] ?? '';
                
                if (\Routina\Services\AuthService::verifyTotpCode($secret, $code)) {
                    \Routina\Services\AuthService::enableMfa($userId, $secret);
                    unset($_SESSION['mfa_setup_secret']);
                    $_SESSION['flash_message'] = 'Two-factor authentication is now enabled!';
                    header('Location: /profile');
                    exit;
                }
                
                view('account/mfa_setup', [
                    'secret' => $secret,
                    'error' => 'Invalid verification code. Please try again.'
                ]);
                return;
            }

            view('account/mfa_setup', ['secret' => $secret]);
            return;
        }

        // Default: redirect to profile
        header('Location: /profile');
        exit;
    }

    /**
     * Delete user account - shows confirmation page and handles deletion
     */
    public function deleteAccount() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = (int)$_SESSION['user_id'];
        $db = \Routina\Config\Database::getConnection();
        
        // Get user info for display (backward compatible with older DB schemas)
        try {
            $stmt = $db->prepare("SELECT routina_id, email, display_name FROM users WHERE id = :id");
            $stmt->execute(['id' => $userId]);
            $user = $stmt->fetch();
        } catch (\PDOException $e) {
            $msg = strtolower($e->getMessage());
            if (strpos($msg, 'unknown column') !== false || strpos($msg, 'no such column') !== false || strpos($msg, 'does not exist') !== false) {
                $stmt = $db->prepare("SELECT email, display_name FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $user = $stmt->fetch();
                if (is_array($user)) {
                    $user['routina_id'] = null;
                }
            } else {
                throw $e;
            }
        }
        
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $confirmText = trim($_POST['confirm_text'] ?? '');
            $password = $_POST['password'] ?? '';
            
            $expectedConfirm = 'DELETE MY ACCOUNT';
            
            // Validate confirmation text
            if (strtoupper($confirmText) !== $expectedConfirm) {
                view('account/delete_account', [
                    'user' => $user,
                    'error' => 'Please type "DELETE MY ACCOUNT" exactly to confirm.'
                ]);
                return;
            }
            
            // Check if user has a password (not just Google OAuth)
            try {
                $stmt = $db->prepare("SELECT password, google_id FROM users WHERE id = :id");
                $stmt->execute(['id' => $userId]);
                $userData = $stmt->fetch();
            } catch (\PDOException $e) {
                $msg = strtolower($e->getMessage());
                if (strpos($msg, 'unknown column') !== false || strpos($msg, 'no such column') !== false || strpos($msg, 'does not exist') !== false) {
                    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
                    $stmt->execute(['id' => $userId]);
                    $userData = $stmt->fetch();
                    if (is_array($userData)) {
                        $userData['google_id'] = null;
                    }
                } else {
                    throw $e;
                }
            }
            
            // If user has a password, verify it
            if (!empty($userData['password'])) {
                if (!password_verify($password, $userData['password'])) {
                    view('account/delete_account', [
                        'user' => $user,
                        'error' => 'Incorrect password. Please try again.'
                    ]);
                    return;
                }
            }
            
            // Perform the deletion
            $deleteError = null;
            $deleted = User::deleteAccount($userId, $deleteError);
            
            if ($deleted) {
                // Clear session
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                
                // Redirect to goodbye page
                header('Location: /account-deleted');
                exit;
            } else {
                $ref = bin2hex(random_bytes(4));
                $safeDetail = is_string($deleteError) && $deleteError !== '' ? $deleteError : 'unknown';
                error_log("Account deletion failed for user {$userId} [ref={$ref}]: {$safeDetail}");

                view('account/delete_account', [
                    'user' => $user,
                    'error' => 'Failed to delete account. Please try again. If this keeps happening, contact support with reference: ' . $ref
                ]);
                return;
            }
        }

        view('account/delete_account', ['user' => $user]);
    }
}
