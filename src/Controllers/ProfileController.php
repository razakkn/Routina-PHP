<?php

namespace Routina\Controllers;

use Routina\Models\User;

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
        
        $model = (object)[
            'ActiveSection' => $activeSection,
            'Input' => [
                'DisplayName' => $user->display_name,
                'JobTitle' => $user->job_title,
                'Phone' => $user->phone,
                'Address' => $user->address,
                'Bio' => $user->bio,
                'Dob' => $user->dob,
                'Gender' => $user->gender,
                'CountryOfOrigin' => $user->country_of_origin,
                'CurrentLocation' => $user->current_location,
                'PreferredCurrencyCode' => $user->currency,
                'SpouseCount' => $user->spouse_count
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
             'CurrencyOptions' => ['USD', 'EUR', 'GBP'],
             'StatusMessage' => $_SESSION['flash_message'] ?? null,
             'UserId' => $user->id,
             'LoggedSpouseCount' => $user->spouse_count,
             'ChildMembers' => []
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
            $user->phone = $_POST['Phone'] ?? $user->phone;
            $user->address = $_POST['Address'] ?? $user->address;
            $user->bio = $_POST['Bio'] ?? $user->bio;
            $user->dob = $_POST['Dob'] ?? $user->dob;
            $user->gender = $_POST['Gender'] ?? $user->gender;
            $user->country_of_origin = $_POST['CountryOfOrigin'] ?? $user->country_of_origin;
            $user->current_location = $_POST['CurrentLocation'] ?? $user->current_location;
            $user->currency = $_POST['PreferredCurrencyCode'] ?? $user->currency;
            $spouseCount = (int)($_POST['SpouseCount'] ?? $user->spouse_count);
            $user->spouse_count = max(0, min(10, $spouseCount));
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
                    header('Location: /profile?section=avatar');
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
                        header('Location: /profile?section=avatar');
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

        header('Location: /profile?section=' . $section);
        exit;
    }
}
