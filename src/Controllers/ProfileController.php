<?php

namespace Routina\Controllers;

use Routina\Models\Family;
use Routina\Models\User;
use Routina\Services\CurrencyService;

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
                'PreferredCurrencyCode' => $user->currency,
                'PreferredCurrencyLabel' => CurrencyService::labelFor($user->currency ?? 'USD'),
                'SpouseCount' => $user->spouse_count,

                'RelationshipStatus' => $user->relationship_status ?? 'single',
                'PartnerType' => (is_array($partner) ? ($partner['relation'] ?? '') : ''),
                'PartnerName' => (is_array($partner) ? ($partner['name'] ?? '') : ''),
                'PartnerDob' => (is_array($partner) ? ($partner['birthdate'] ?? '') : ''),
                'PartnerGender' => (is_array($partner) ? ($partner['gender'] ?? '') : ''),
                'PartnerEmail' => (is_array($partner) ? ($partner['email'] ?? '') : ''),
                'PartnerPhone' => (is_array($partner) ? ($partner['phone'] ?? '') : ''),
                'PartnerNoEmail' => (is_array($partner) ? (!empty($partner['no_email']) ? 1 : 0) : 0)
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
             'StatusMessage' => $_SESSION['flash_message'] ?? null,
             'UserId' => $user->id,
             'LoggedSpouseCount' => $user->spouse_count,
             'ChildMembers' => [],
             'FamilyMembers' => $familyMembers
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
}
