<?php ob_start(); ?>

<?php
    $isAvatar = ($Model->ActiveSection ?? 'profile') === 'avatar';
    $mode = (string)($Model->Mode ?? 'view');
    $input = is_array($Model->Input ?? null) ? $Model->Input : [];
    $email = trim((string)($input['Email'] ?? ''));
    $name = (string)($input['DisplayName'] ?? '');
    $job = trim((string)($input['JobTitle'] ?? ''));
    $headline = trim((string)($input['Headline'] ?? ''));
    $dob = trim((string)($input['Dob'] ?? ''));
    $location = trim((string)($input['CurrentLocation'] ?? ''));
    $bio = trim((string)($input['Bio'] ?? ''));


    $relationshipStatus = (string)($input['RelationshipStatus'] ?? 'single');
    $relationshipLabel = $relationshipStatus;
    if ($relationshipStatus === 'in_relationship') $relationshipLabel = 'In a relationship';
    if ($relationshipStatus === 'married') $relationshipLabel = 'Married';
    if ($relationshipStatus === 'single') $relationshipLabel = 'Single';

    $familyRelation = trim((string)($input['FamilyRelation'] ?? ''));

    $avatarInitial = strtoupper(substr($name !== '' ? $name : 'U', 0, 1));
    $avatarUrl = null;
    if (!empty($Model->Avatar) && !empty($Model->Avatar->ImageUrl)) {
        $avatarUrl = (string)$Model->Avatar->ImageUrl;
    }

    $familyMembers = is_array($Model->FamilyMembers ?? null) ? $Model->FamilyMembers : [];
?>

<div class="profile-shell">
    <div class="profile-hero">
        <div class="profile-hero__left">
            <div class="profile-avatar">
                <?php if ($avatarUrl): ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" />
                <?php else: ?>
                    <div class="profile-avatar__initial"><?php echo htmlspecialchars($avatarInitial); ?></div>
                <?php endif; ?>
            </div>

            <div class="profile-hero__meta">
                <div class="profile-hero__name"><?php echo htmlspecialchars($name !== '' ? $name : 'Your profile'); ?></div>
                <?php if ($email !== ''): ?><div class="profile-hero__email text-muted" style="font-size: 0.9rem;"><?php echo htmlspecialchars($email); ?></div><?php endif; ?>
                <div class="profile-hero__sub">
                    <?php if ($job !== ''): ?><span class="profile-badge"><?php echo htmlspecialchars($job); ?></span><?php endif; ?>
                    <span class="profile-badge"><?php echo htmlspecialchars($relationshipLabel); ?></span>
                    <?php if ($familyRelation !== ''): ?><span class="profile-badge bg-info text-dark">Relation: <?php echo htmlspecialchars($familyRelation); ?></span><?php endif; ?>
                    <?php if ($headline !== ''): ?><span class="profile-badge"><?php echo htmlspecialchars($headline); ?></span><?php endif; ?>
                </div>
            </div>
        </div>

        <div class="profile-hero__right">
            <?php if (!$isAvatar): ?>
                <?php if ($mode === 'edit'): ?>
                    <a class="btn btn-outline-secondary" href="/profile?section=profile&mode=view">Done</a>
                <?php else: ?>
                    <a class="btn btn-primary" href="/profile?section=profile&mode=edit">Edit</a>
                <?php endif; ?>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="/profile?section=avatar&mode=<?php echo htmlspecialchars($mode); ?>">Avatar</a>
            <button class="btn btn-outline-secondary" type="button" disabled>Private</button>
        </div>
    </div>

    <?php if ($Model->StatusMessage): ?>
        <div class="alert alert-info mt-3 mb-0"><?php echo htmlspecialchars((string)$Model->StatusMessage); ?></div>
    <?php endif; ?>

    <?php if ($isAvatar): ?>
        <div class="profile-grid">
            <div class="profile-card">
                <div class="profile-card__head">
                    <div class="profile-card__title">Avatar &amp; appearance</div>
                    <div class="profile-card__privacy">Visible to me</div>
                </div>

                <form method="post" action="/profile?section=avatar&mode=<?php echo htmlspecialchars($mode); ?>" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="d-flex align-items-center gap-3" style="margin-bottom: 14px;">
                        <div class="profile-avatar" style="width:72px;height:72px;">
                            <?php if (!empty($Model->Avatar->HasImage) && !empty($Model->Avatar->ImageUrl)): ?>
                                <img src="<?php echo htmlspecialchars((string)$Model->Avatar->ImageUrl); ?>" alt="Avatar" />
                            <?php else: ?>
                                <div class="profile-avatar__initial"><?php echo htmlspecialchars($avatarInitial); ?></div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-weight:900;"><?php echo htmlspecialchars($name); ?></div>
                            <div class="text-muted">Upload a photo or choose a preset.</div>
                            <?php if (!empty($Model->Avatar->HasImage) || !empty($Model->Avatar->PresetKey)): ?>
                                <button type="submit" name="DeleteAvatar" value="1" class="btn btn-link text-danger p-0" style="text-decoration:none;">Remove avatar</button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="small text-uppercase text-muted fw-semibold">Presets</div>
                        <div class="d-flex gap-2" style="margin-top:10px; flex-wrap: wrap;">
                            <?php $colors = ['lavender' => '#E6E6FA', 'sage' => '#9DC183', 'teal' => '#008080', 'coral' => '#FF7F50']; ?>
                            <?php foreach (($Model->AvatarPresets ?? []) as $key => $label): ?>
                                <button type="submit" name="AvatarPresetKey" value="<?php echo htmlspecialchars((string)$key); ?>" class="btn btn-outline-secondary" style="padding: 8px 12px;">
                                    <span style="display:inline-block;width:12px;height:12px;border-radius:99px;background:<?php echo htmlspecialchars($colors[$key] ?? '#ccc'); ?>;margin-right:8px;"></span>
                                    <?php echo htmlspecialchars((string)$label); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="small text-uppercase text-muted fw-semibold">Custom upload</div>
                    </div>
                    <div class="input-group">
                        <input type="file" name="AvatarFile" class="form-control" accept="image/*" />
                        <button class="btn btn-primary" type="submit">Upload</button>
                    </div>
                    <div class="form-text">Max size 2MB.</div>
                </form>
            </div>

            <div class="profile-card">
                <div class="profile-card__head">
                    <div class="profile-card__title">Quick links</div>
                    <div class="profile-card__privacy">Private</div>
                </div>
                <div class="profile-list">
                    <a class="profile-item" href="/profile?section=profile&mode=<?php echo htmlspecialchars($mode); ?>" style="text-decoration:none;">
                        <div class="profile-item__label">Back to profile</div>
                        <div class="profile-item__value">Open</div>
                    </a>
                    <a class="profile-item" href="/family" style="text-decoration:none;">
                        <div class="profile-item__label">Family tree</div>
                        <div class="profile-item__value">Open</div>
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="profile-grid">
            <div>
                <div class="profile-card">
                    <div class="profile-card__head">
                        <div class="profile-card__title">About me</div>
                        <div class="profile-card__privacy">Visible to me</div>
                    </div>

                    <?php if ($mode === 'edit'): ?>
                        <form method="post" action="/profile?section=profile&mode=edit" class="profile-rows">
                            <?= csrf_field() ?>

                            <div class="profile-row">
                                <div class="profile-row__k">Display name</div>
                                <div class="profile-row__v"><input class="form-control" name="DisplayName" value="<?php echo htmlspecialchars((string)($input['DisplayName'] ?? '')); ?>" /></div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">Headline</div>
                                <div class="profile-row__v"><input class="form-control" name="Headline" value="<?php echo htmlspecialchars((string)($input['Headline'] ?? '')); ?>" placeholder="Builder. Father. Night thinker." /></div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">Occupation</div>
                                <div class="profile-row__v"><input class="form-control" name="JobTitle" value="<?php echo htmlspecialchars((string)($input['JobTitle'] ?? '')); ?>" /></div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">Phone</div>
                                <div class="profile-row__v"><input class="form-control" name="Phone" value="<?php echo htmlspecialchars((string)($input['Phone'] ?? '')); ?>" placeholder="+1 555 123 4567" /></div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">Address</div>
                                <div class="profile-row__v"><input class="form-control" name="Address" value="<?php echo htmlspecialchars((string)($input['Address'] ?? '')); ?>" placeholder="Street, city" /></div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">Date of birth</div>
                                <div class="profile-row__v"><input class="form-control" type="date" name="Dob" value="<?php echo htmlspecialchars((string)($input['Dob'] ?? '')); ?>" /></div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">Location</div>
                                <div class="profile-row__v"><input class="form-control" name="CurrentLocation" value="<?php echo htmlspecialchars((string)($input['CurrentLocation'] ?? '')); ?>" placeholder="Dubai, UAE" /></div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">Gender</div>
                                <div class="profile-row__v">
                                    <select class="form-select" name="Gender">
                                        <option value="">Select gender...</option>
                                        <option value="Male" <?php echo ((string)($input['Gender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo ((string)($input['Gender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Non-binary" <?php echo ((string)($input['Gender'] ?? '') === 'Non-binary') ? 'selected' : ''; ?>>Non-binary</option>
                                        <option value="Prefer not to say" <?php echo ((string)($input['Gender'] ?? '') === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                                    </select>
                                </div>
                            </div>

                            <div class="profile-row">
                                <div class="profile-row__k">What defines you these days?</div>
                                <div class="profile-row__v"><textarea class="form-control" name="Bio" rows="3"><?php echo htmlspecialchars((string)($input['Bio'] ?? '')); ?></textarea></div>
                            </div>

                            <div class="profile-card" style="padding: 14px; margin-top: 10px;">
                                <div class="profile-card__head" style="margin-bottom: 8px;">
                                    <div class="profile-card__title">Relationship</div>
                                    <div class="profile-card__privacy">Family only</div>
                                </div>

                                <?php if ($mode === 'edit'): ?>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="ShareProfilePublicly" name="ShareProfilePublicly" value="1" <?php echo !empty($Model->Input['ShareProfilePublicly']) ? 'checked' : ''; ?> />
                                            <label class="form-check-label" for="ShareProfilePublicly">Share my profile details (DOB, email, phone) in other users' family trees</label>
                                        </div>
                                        <div class="form-text">If unchecked, your email/phone/DOB will be masked when shown in other users' family trees.</div>
                                    </div>
                                <?php endif; ?>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <select id="RelationshipStatus" name="RelationshipStatus" class="form-select">
                                            <?php foreach (($Model->RelationshipOptions ?? []) as $k => $label): ?>
                                                <option value="<?php echo htmlspecialchars((string)$k); ?>" <?php echo ((string)($input['RelationshipStatus'] ?? 'single') === (string)$k) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="PartnerTypeWrap">
                                        <label class="form-label">Partner type</label>
                                        <select id="PartnerType" name="PartnerType" class="form-select">
                                            <option value="">Select…</option>
                                            <option value="Girlfriend" <?php echo ((string)($input['PartnerType'] ?? '') === 'Girlfriend') ? 'selected' : ''; ?>>Girlfriend</option>
                                            <option value="Boyfriend" <?php echo ((string)($input['PartnerType'] ?? '') === 'Boyfriend') ? 'selected' : ''; ?>>Boyfriend</option>
                                        </select>
                                        <div class="form-text">For married, this will be saved as Spouse.</div>
                                    </div>
                                </div>

                                <div id="PartnerFields" style="margin-top: 12px;">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Partner name <span class="text-danger">*</span></label>
                                            <input id="PartnerName" name="PartnerName" class="form-control" value="<?php echo htmlspecialchars((string)($input['PartnerName'] ?? '')); ?>" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Partner contact number <span class="text-danger">*</span></label>
                                            <input id="PartnerPhone" name="PartnerPhone" class="form-control" value="<?php echo htmlspecialchars((string)($input['PartnerPhone'] ?? '')); ?>" />
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label class="form-label">Partner email <span class="text-danger">*</span></label>
                                        <input id="PartnerEmail" name="PartnerEmail" type="email" class="form-control" value="<?php echo htmlspecialchars((string)($input['PartnerEmail'] ?? '')); ?>" />
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" value="1" id="PartnerNoEmail" name="PartnerNoEmail" <?php echo !empty($input['PartnerNoEmail']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="PartnerNoEmail">No email available</label>
                                        </div>
                                        <div class="form-text">Email + contact number will be reused later for address book/search.</div>
                                    </div>

                                    <div class="row g-3 mt-1">
                                        <div class="col-md-6">
                                            <label class="form-label">Partner DOB</label>
                                            <input name="PartnerDob" type="date" class="form-control" value="<?php echo htmlspecialchars((string)($input['PartnerDob'] ?? '')); ?>" />
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Partner gender</label>
                                            <select name="PartnerGender" class="form-select">
                                                <option value="">Select gender...</option>
                                                <option value="Male" <?php echo ((string)($input['PartnerGender'] ?? '') === 'Male') ? 'selected' : ''; ?>>Male</option>
                                                <option value="Female" <?php echo ((string)($input['PartnerGender'] ?? '') === 'Female') ? 'selected' : ''; ?>>Female</option>
                                                <option value="Non-binary" <?php echo ((string)($input['PartnerGender'] ?? '') === 'Non-binary') ? 'selected' : ''; ?>>Non-binary</option>
                                                <option value="Prefer not to say" <?php echo ((string)($input['PartnerGender'] ?? '') === 'Prefer not to say') ? 'selected' : ''; ?>>Prefer not to say</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="profile-card" style="padding: 14px; margin-top: 10px;">
                                <div class="profile-card__head" style="margin-bottom: 8px;">
                                    <div class="profile-card__title">Social &amp; presence</div>
                                    <div class="profile-card__privacy">Private</div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">LinkedIn</label>
                                        <input name="LinkedIn" class="form-control" value="<?php echo htmlspecialchars((string)($input['LinkedIn'] ?? '')); ?>" placeholder="https://linkedin.com/in/…" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Instagram</label>
                                        <input name="Instagram" class="form-control" value="<?php echo htmlspecialchars((string)($input['Instagram'] ?? '')); ?>" placeholder="@handle" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter / X</label>
                                        <input name="Twitter" class="form-control" value="<?php echo htmlspecialchars((string)($input['Twitter'] ?? '')); ?>" placeholder="@handle" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Website</label>
                                        <input name="Website" class="form-control" value="<?php echo htmlspecialchars((string)($input['Website'] ?? '')); ?>" placeholder="https://…" />
                                    </div>
                                </div>
                            </div>

                            <div class="profile-card" style="padding: 14px; margin-top: 10px;">
                                <div class="profile-card__head" style="margin-bottom: 8px;">
                                    <div class="profile-card__title">Preferences</div>
                                    <div class="profile-card__privacy">Private</div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Preferred currency</label>
                                        <select name="PreferredCurrencyCode" class="form-select">
                                            <?php $ccyOptions = ($Model->CurrencyOptions ?? ['USD','EUR','GBP']); ?>
                                            <?php foreach ($ccyOptions as $code => $label): ?>
                                                <?php
                                                    if (is_int($code)) {
                                                        $code = (string)$label;
                                                        $label = (string)$label;
                                                    } else {
                                                        $code = (string)$code;
                                                        $label = (string)$label;
                                                    }
                                                    $optText = ($label !== '' && $label !== $code) ? ($code . ' — ' . $label) : $code;
                                                ?>
                                                <option value="<?php echo htmlspecialchars($code); ?>" <?php echo ((string)($input['PreferredCurrencyCode'] ?? 'USD') === $code) ? 'selected' : ''; ?>><?php echo htmlspecialchars($optText); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input name="PreferredCurrencyCodeCustom" class="form-control mt-2" value="" placeholder="Can’t find it? Type 3-letter code (e.g., USD)" maxlength="3" />
                                        <div class="form-text">If you type a code here, it will override the dropdown.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Spouses recorded</label>
                                        <input name="SpouseCount" type="number" class="form-control" value="<?php echo htmlspecialchars((string)($input['SpouseCount'] ?? '0')); ?>" />
                                    </div>
                                </div>
                            </div>

                            <div class="profile-actions">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <a class="btn btn-outline-secondary" href="/profile?section=profile&mode=view">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="profile-rows">
                            <div class="profile-row">
                                <div class="profile-row__k">Date of birth</div>
                                <div class="profile-row__v"><?php echo htmlspecialchars($dob !== '' ? $dob : '—'); ?></div>
                            </div>
                            <div class="profile-row">
                                <div class="profile-row__k">Location</div>
                                <div class="profile-row__v"><?php echo htmlspecialchars($location !== '' ? $location : '—'); ?></div>
                            </div>
                            <div class="profile-row">
                                <div class="profile-row__k">Occupation</div>
                                <div class="profile-row__v"><?php echo htmlspecialchars($job !== '' ? $job : '—'); ?></div>
                            </div>
                            <div class="profile-row">
                                <div class="profile-row__k">Phone</div>
                                <div class="profile-row__v"><?php echo htmlspecialchars(trim((string)($input['Phone'] ?? '')) !== '' ? (string)$input['Phone'] : '—'); ?></div>
                            </div>
                            <div class="profile-row">
                                <div class="profile-row__k">What defines you these days?</div>
                                <div class="profile-row__v"><?php echo htmlspecialchars($bio !== '' ? $bio : '—'); ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="profile-card" style="margin-top: 14px;">
                    <div class="profile-card__head">
                        <div class="profile-card__title">Family</div>
                        <div class="profile-card__privacy">Family only</div>
                    </div>

                    <div class="profile-family-grid">
                        <?php
                            $cards = array_slice($familyMembers, 0, 3);
                            $cards = $familyMembers;
                            $linked = $Model->LinkedFamily ?? [];
                        ?>

                        <?php if (empty($cards) && empty($linked)): ?>
                            <div class="profile-family-card" style="grid-column: 1 / -1; min-height: 90px; display:flex; align-items:center; justify-content: space-between;">
                                <div>
                                    <div class="profile-family-card__name">No family added yet</div>
                                    <div class="profile-family-card__rel">Add your first family member</div>
                                </div>
                                <a class="btn btn-primary" href="/family">Add family member</a>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($cards as $m): ?>
                            <?php
                                $mName = (string)($m['name'] ?? '');
                                $mRel = (string)($m['relation'] ?? '');
                                $mDob = trim((string)($m['birthdate'] ?? ''));
                            ?>
                            <div class="profile-family-card">
                                <div class="profile-family-card__name"><?php echo htmlspecialchars($mName); ?></div>
                                <div class="profile-family-card__rel"><?php echo htmlspecialchars($mRel); ?></div>
                                <div class="profile-family-card__date"><?php echo htmlspecialchars($mDob !== '' ? $mDob : ''); ?></div>
                            </div>
                        <?php endforeach; ?>

                        <?php foreach ($linked as $m): ?>
                            <?php
                                $mName = (string)($m['owner_name'] ?? '');
                                $mRel = (string)($m['relation'] ?? '');
                                $mDob = trim((string)($m['owner_dob'] ?? ''));
                                $mEmail = (string)($m['owner_email'] ?? '');
                            ?>
                            <div class="profile-family-card bg-info-subtle">
                                <div class="profile-family-card__name"><?php echo htmlspecialchars($mName); ?></div>
                                <div class="profile-family-card__rel">Linked as: <?php echo htmlspecialchars($mRel); ?></div>
                                <div class="profile-family-card__date">DOB: <?php echo htmlspecialchars($mDob !== '' ? $mDob : ''); ?></div>
                                <?php if ($mEmail !== ''): ?><div class="profile-family-card__email">Email: <?php echo htmlspecialchars($mEmail); ?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="profile-actions">
                        <a class="btn btn-outline-secondary" href="/family">Manage family tree</a>
                    </div>
                </div>

                <div class="profile-card" style="margin-top: 14px;">
                    <div class="profile-card__head">
                        <div class="profile-card__title">Preferences</div>
                        <div class="profile-card__privacy">Private</div>
                    </div>

                    <div class="profile-list">
                        <div class="profile-item">
                            <div class="profile-item__label">Preferred currency</div>
                            <?php
                                $ccyCode = (string)($input['PreferredCurrencyCode'] ?? 'USD');
                                $ccyLabel = (string)($input['PreferredCurrencyLabel'] ?? '');
                                $ccyText = ($ccyLabel !== '' && $ccyLabel !== $ccyCode) ? ($ccyCode . ' — ' . $ccyLabel) : $ccyCode;
                            ?>
                            <div class="profile-item__value"><?php echo htmlspecialchars($ccyText); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="profile-card" style="margin-top: 14px;">
                    <div class="profile-card__head">
                        <div class="profile-card__title">Security</div>
                        <div class="profile-card__privacy">Private</div>
                    </div>

                    <?php
                        $mfaStatus = \Routina\Services\AuthService::getMfaStatus((int)($Model->UserId ?? 0));
                        $mfaEnabled = $mfaStatus['enabled'] ?? false;
                    ?>

                    <div class="profile-list">
                        <div class="profile-item">
                            <div class="profile-item__label">Two-Factor Auth</div>
                            <div class="profile-item__value">
                                <?php if ($mfaEnabled): ?>
                                    <span class="badge bg-success">Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Disabled</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="profile-actions">
                        <?php if ($mfaEnabled): ?>
                            <a class="btn btn-outline-danger btn-sm" href="/profile/security/mfa?action=disable">Disable MFA</a>
                        <?php else: ?>
                            <a class="btn btn-outline-primary btn-sm" href="/profile/security/mfa?action=setup">Setup MFA</a>
                        <?php endif; ?>
                        <a class="btn btn-outline-secondary btn-sm" href="/reset-password">Change Password</a>
                    </div>
                </div>
            </div>

            <div>
                <div class="profile-card">
                    <div class="profile-card__head">
                        <div class="profile-card__title">Family</div>
                        <div class="profile-card__privacy">Private</div>
                    </div>

                    <div class="profile-list">
                        <div class="profile-item">
                            <div class="profile-item__label">You</div>
                            <div class="profile-item__value"><?php echo htmlspecialchars($name); ?></div>
                        </div>
                        <div class="profile-item">
                            <div class="profile-item__label">Relationship</div>
                            <div class="profile-item__value"><?php echo htmlspecialchars($relationshipLabel); ?></div>
                        </div>
                        <?php if (!empty($input['PartnerName'])): ?>
                            <div class="profile-item">
                                <div class="profile-item__label">Partner</div>
                                <div class="profile-item__value"><?php echo htmlspecialchars((string)$input['PartnerName']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="profile-card" style="margin-top: 14px;">
                    <div class="profile-card__head">
                        <div class="profile-card__title">Social &amp; presence</div>
                        <div class="profile-card__privacy">Private</div>
                    </div>

                    <?php
                        $social = [
                            'LinkedIn' => (string)($input['LinkedIn'] ?? ''),
                            'Instagram' => (string)($input['Instagram'] ?? ''),
                            'Twitter / X' => (string)($input['Twitter'] ?? ''),
                            'Website' => (string)($input['Website'] ?? ''),
                            'Phone' => (string)($input['Phone'] ?? ''),
                        ];
                    ?>

                    <div class="profile-list">
                        <?php foreach ($social as $k => $v): ?>
                            <div class="profile-item">
                                <div class="profile-item__label"><?php echo htmlspecialchars((string)$k); ?></div>
                                <div class="profile-item__value"><?php echo htmlspecialchars(trim($v) !== '' ? $v : '—'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Danger Zone - Delete Account -->
                <div class="profile-card" style="margin-top: 14px; border: 1px solid rgba(220, 53, 69, 0.3); background: linear-gradient(135deg, #fff5f5 0%, #fff 100%);">
                    <div class="profile-card__head">
                        <div class="profile-card__title" style="color: #dc3545;">⚠️ Danger Zone</div>
                        <div class="profile-card__privacy">Permanent</div>
                    </div>

                    <div style="padding: 12px 0;">
                        <p style="margin: 0 0 12px 0; color: #666; font-size: 0.9rem;">
                            Permanently delete your account and all associated data. This action cannot be undone.
                        </p>
                        <a href="/profile/delete" class="btn btn-outline-danger btn-sm">
                            🗑️ Delete My Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!$isAvatar && $mode === 'edit'): ?>
    <script src="/js/profile.js" defer></script>
<?php endif; ?>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
