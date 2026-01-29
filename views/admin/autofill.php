<?php ob_start(); ?>

<?php
    $email = (string)($email ?? '');
    $phone = (string)($phone ?? '');

    $familyName = (string)($family_name ?? '');
    $familyBirthdate = (string)($family_birthdate ?? '');
    $familyGender = (string)($family_gender ?? '');
    $familyRelation = (string)($family_relation ?? '');

    $targetUser = is_array($target_user ?? null) ? $target_user : null;
    $targetUserRow = is_array($target_user_row ?? null) ? $target_user_row : null;
    $familyMatches = is_array($family_matches ?? null) ? $family_matches : [];
    $dryRun = is_array($dry_run_updates ?? null) ? $dry_run_updates : [];
    $notes = is_array($notes ?? null) ? $notes : [];

    $mask = function ($s) {
        $t = trim((string)$s);
        if ($t === '') return '';
        return substr(hash('sha256', $t), 0, 10);
    };
?>

<div class="routina-wrap">
    <div class="routina-header">
        <div>
            <div class="routina-title">Auto-Populate Diagnostics</div>
            <div class="routina-sub">Admin-only: verify whether email/phone matching is working and what fields would be updated.</div>
        </div>
    </div>

    <div class="card" style="grid-column: span 2; max-width: 900px;">
        <div class="card-kicker">Run Check</div>
        <form method="get" class="mt-3" style="display: grid; gap: 12px;">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">User Email (to match)</label>
                    <input class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="user@example.com" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">User Phone (to match)</label>
                    <input class="form-control" name="phone" value="<?php echo htmlspecialchars($phone); ?>" placeholder="+1 555 123 4567" />
                </div>
            </div>

            <div class="mt-2" style="font-weight: 600;">Optional: Family-member fields (simulate existing-user autofill)</div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Family Name</label>
                    <input class="form-control" name="name" value="<?php echo htmlspecialchars($familyName); ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Family DOB</label>
                    <input class="form-control" type="date" name="birthdate" value="<?php echo htmlspecialchars($familyBirthdate); ?>" />
                </div>
                <div class="col-md-3">
                    <label class="form-label">Family Gender</label>
                    <input class="form-control" name="gender" value="<?php echo htmlspecialchars($familyGender); ?>" placeholder="Male/Female" />
                </div>
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Family Relation</label>
                    <input class="form-control" name="relation" value="<?php echo htmlspecialchars($familyRelation); ?>" placeholder="Spouse / Wife / Husband / Boyfriend / Girlfriend" />
                    <div class="form-text">Only these relations affect relationship_status.</div>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button class="btn btn-primary" type="submit">Run diagnostics</button>
                    <a class="btn btn-outline-secondary ms-2" href="/admin/autofill">Reset</a>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($notes)): ?>
        <div class="card" style="grid-column: span 2; max-width: 900px;">
            <div class="card-kicker">Notes</div>
            <ul class="mt-2">
                <?php foreach ($notes as $n): ?>
                    <li><?php echo htmlspecialchars((string)$n); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($email !== '' || $phone !== ''): ?>
        <div class="card" style="grid-column: span 2; max-width: 900px;">
            <div class="card-kicker">Matched User</div>
            <?php if (!$targetUser): ?>
                <div class="mt-2">No match found in <code>users</code> for the provided email/phone.</div>
            <?php else: ?>
                <div class="mt-2">
                    Matched user id: <strong><?php echo (int)$targetUser['id']; ?></strong>
                    <div class="text-muted">emailHash=<?php echo htmlspecialchars($mask($targetUser['email'] ?? '')); ?></div>
                </div>

                <?php if ($targetUserRow): ?>
                    <div class="mt-3">
                        <div style="font-weight:600;">Current user fields (subset)</div>
                        <pre style="background:#0b1020;color:#dfe7ff;padding:1rem;border-radius:8px;overflow-x:auto;"><?php
                            $subsetKeys = ['id','email','phone','display_name','dob','gender','relationship_status','routina_id','google_id'];
                            $subset = [];
                            foreach ($subsetKeys as $k) {
                                if (array_key_exists($k, $targetUserRow)) {
                                    $subset[$k] = $targetUserRow[$k];
                                }
                            }
                            echo htmlspecialchars(json_encode($subset, JSON_PRETTY_PRINT));
                        ?></pre>
                    </div>
                <?php endif; ?>

                <div class="mt-3">
                    <div style="font-weight:600;">Dry-run updates (existing-user flow)</div>
                    <?php if (empty($dryRun)): ?>
                        <div class="text-muted">No updates would be applied based on current user values and the provided family-member fields.</div>
                    <?php else: ?>
                        <pre style="background:#0b1020;color:#dfe7ff;padding:1rem;border-radius:8px;overflow-x:auto;"><?php echo htmlspecialchars(json_encode($dryRun, JSON_PRETTY_PRINT)); ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card" style="grid-column: span 2; max-width: 900px;">
            <div class="card-kicker">Family Matches (new-user flow)</div>
            <?php if (empty($familyMatches)): ?>
                <div class="mt-2">No matching records in <code>family_members</code> for the provided email/phone.</div>
            <?php else: ?>
                <div class="mt-2">Found <?php echo count($familyMatches); ?> match(es). Showing most recent first.</div>
                <pre class="mt-3" style="background:#0b1020;color:#dfe7ff;padding:1rem;border-radius:8px;overflow-x:auto;"><?php
                    $trimmed = [];
                    foreach (array_slice($familyMatches, 0, 5) as $m) {
                        if (!is_array($m)) continue;
                        $trimmed[] = [
                            'id' => $m['id'] ?? null,
                            'owner_user_id' => $m['owner_user_id'] ?? null,
                            'name' => $m['name'] ?? null,
                            'relation' => $m['relation'] ?? null,
                            'birthdate' => $m['birthdate'] ?? null,
                            'gender' => $m['gender'] ?? null,
                            'phone' => $m['phone'] ?? null,
                            'email_hash' => $mask($m['email'] ?? ''),
                            'created_at' => $m['created_at'] ?? null,
                        ];
                    }
                    echo htmlspecialchars(json_encode($trimmed, JSON_PRETTY_PRINT));
                ?></pre>
            <?php endif; ?>

            <div class="mt-3 text-muted">
                Reminder: family trees are per-account; this app currently uses family tree data to pre-fill profiles, but it does not automatically copy another user’s family tree into a matched user’s account.
            </div>
        </div>
    <?php endif; ?>
    <?php
        $allFamilyWithEmail = is_array($all_family_with_email ?? null) ? $all_family_with_email : [];
    ?>
    <?php if (!empty($allFamilyWithEmail)): ?>
        <div class="card" style="grid-column: span 2; max-width: 900px;">
            <div class="card-kicker">📋 All Family Members with Emails (sample)</div>
            <div class="mt-2 text-muted">Last 20 family_members records that have an email set. Check if the target email is here.</div>
            <table class="table table-sm mt-3" style="font-size: 0.85rem;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Owner</th>
                        <th>Name</th>
                        <th>Email Hash</th>
                        <th>Phone</th>
                        <th>Relation</th>
                        <th>no_email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allFamilyWithEmail as $fm): ?>
                        <tr>
                            <td><?php echo (int)($fm['id'] ?? 0); ?></td>
                            <td><?php echo htmlspecialchars($fm['owner_name'] ?? ''); ?> (uid=<?php echo (int)($fm['user_id'] ?? 0); ?>)</td>
                            <td><?php echo htmlspecialchars($fm['name'] ?? ''); ?></td>
                            <td><code><?php echo htmlspecialchars($mask($fm['email'] ?? '')); ?></code></td>
                            <td><?php echo htmlspecialchars($fm['phone'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($fm['relation'] ?? ''); ?></td>
                            <td><?php echo is_null($fm['no_email'] ?? null) ? '<span class="text-muted">NULL</span>' : (int)$fm['no_email']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?></div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
