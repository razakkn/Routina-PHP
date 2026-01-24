<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
        <div>
            <div class="routina-title">Edit family member</div>
            <div class="routina-sub">Update details for your family tree.</div>
        </div>
        <div>
            <a class="btn btn-outline-secondary" href="/family">Back</a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php
        $m = is_array($member ?? null) ? $member : [];
        $dob = trim((string)($m['birthdate'] ?? ''));
        $dod = trim((string)($m['deathdate'] ?? ''));
        $noEmail = !empty($m['no_email']);
        $memberId = (int)($m['id'] ?? 0);
        $curMotherId = (int)($m['mother_id'] ?? 0);
        $curFatherId = (int)($m['father_id'] ?? 0);
    ?>

    <div class="card" style="max-width: 860px;">
        <div class="card-kicker">Member details</div>
        <form method="post" action="/family/update?id=<?php echo (int)($m['id'] ?? 0); ?>" class="mt-3" data-family-form>
            <?= csrf_field() ?>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Name</label>
                    <input name="name" class="form-control" value="<?php echo htmlspecialchars((string)($m['name'] ?? '')); ?>" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Relationship</label>
                    <select name="relation" class="form-select" required>
                        <?php
                            $rels = ['Spouse','Boyfriend','Girlfriend','Child','Mother','Father','Parent','Sibling','Grandparent','Grandchild','Cousin','Uncle','Aunt','Other'];
                            $curRel = (string)($m['relation'] ?? '');
                        ?>
                        <?php foreach ($rels as $r): ?>
                            <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ($curRel === $r) ? 'selected' : ''; ?>><?php echo htmlspecialchars($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Side of family</label>
                    <?php $side = (string)($m['side_of_family'] ?? ''); ?>
                    <select name="side_of_family" class="form-select">
                        <option value="" <?php echo ($side === '') ? 'selected' : ''; ?>>—</option>
                        <option value="Father" <?php echo ($side === 'Father') ? 'selected' : ''; ?>>Father side</option>
                        <option value="Mother" <?php echo ($side === 'Mother') ? 'selected' : ''; ?>>Mother side</option>
                        <option value="Partner" <?php echo ($side === 'Partner') ? 'selected' : ''; ?>>Partner side</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender</label>
                    <input name="gender" class="form-control" value="<?php echo htmlspecialchars((string)($m['gender'] ?? '')); ?>" list="familyGenderOptions" />
                    <datalist id="familyGenderOptions">
                        <option value="Male"></option>
                        <option value="Female"></option>
                        <option value="Non-binary"></option>
                    </datalist>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">DOB</label>
                    <input name="birthdate" type="date" class="form-control" value="<?php echo htmlspecialchars($dob); ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">DOD</label>
                    <input name="deathdate" type="date" class="form-control" value="<?php echo htmlspecialchars($dod); ?>" />
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Mother (optional)</label>
                    <select name="mother_id" class="form-select">
                        <option value="">—</option>
                        <?php foreach (($members ?? []) as $opt): ?>
                            <?php $optId = (int)($opt['id'] ?? 0); ?>
                            <?php if ($optId > 0 && $optId !== $memberId): ?>
                                <option value="<?php echo $optId; ?>" <?php echo ($curMotherId === $optId) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($opt['name'] ?? '')); ?><?php echo !empty($opt['relation']) ? ' — ' . htmlspecialchars((string)$opt['relation']) : ''; ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Father (optional)</label>
                    <select name="father_id" class="form-select">
                        <option value="">—</option>
                        <?php foreach (($members ?? []) as $opt): ?>
                            <?php $optId = (int)($opt['id'] ?? 0); ?>
                            <?php if ($optId > 0 && $optId !== $memberId): ?>
                                <option value="<?php echo $optId; ?>" <?php echo ($curFatherId === $optId) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($opt['name'] ?? '')); ?><?php echo !empty($opt['relation']) ? ' — ' . htmlspecialchars((string)$opt['relation']) : ''; ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Contact number <span class="text-danger">*</span></label>
                    <input name="phone" class="form-control" value="<?php echo htmlspecialchars((string)($m['phone'] ?? '')); ?>" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input name="email" type="email" class="form-control" value="<?php echo htmlspecialchars((string)($m['email'] ?? '')); ?>" data-family-email <?php echo $noEmail ? 'disabled' : ''; ?> <?php echo $noEmail ? '' : 'required'; ?> />
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" value="1" name="no_email" data-family-no-email <?php echo $noEmail ? 'checked' : ''; ?>>
                        <label class="form-check-label">No email available</label>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn btn-outline-secondary" href="/family">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script src="/js/family.js" defer></script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
