<?php ob_start(); ?>

<?php
    $viewMode = $_GET['view'] ?? 'tree';
    if (!in_array($viewMode, ['tree', 'list'], true)) {
        $viewMode = 'tree';
    }

    $displayName = (string)($_SESSION['user_data']['name'] ?? 'You');
    $meInitial = strtoupper(substr($displayName !== '' ? $displayName : 'Y', 0, 1));

    $normalize = function ($s) {
        return strtolower(trim((string)$s));
    };

    $groupOf = function ($relation) use ($normalize) {
        $r = $normalize($relation);

        $partner = ['spouse','wife','husband','boyfriend','girlfriend','partner'];
        $children = ['child','son','daughter','kid'];
        $mother = ['mother','mom'];
        $father = ['father','dad'];
        $parents = ['parent'];
        $siblings = ['sibling','brother','sister','half-sibling','step-sibling'];
        $grandparents = ['grandparent','grandfather','grandmother'];
        $grandchildren = ['grandchild','grandson','granddaughter'];
        $inlaws = ['father-in-law','mother-in-law','brother-in-law','sister-in-law','son-in-law','daughter-in-law','in-law'];
        $extended = ['cousin','uncle','aunt','nephew','niece','step-parent','step-child','godparent','godchild','guardian'];

        if (in_array($r, $partner, true)) return 'partner';
        if (in_array($r, $children, true)) return 'children';
        if (in_array($r, $mother, true)) return 'mother';
        if (in_array($r, $father, true)) return 'father';
        if (in_array($r, $parents, true)) return 'parents';
        if (in_array($r, $siblings, true)) return 'siblings';
        if (in_array($r, $grandparents, true)) return 'grandparents';
        if (in_array($r, $grandchildren, true)) return 'grandchildren';
        if (in_array($r, $inlaws, true)) return 'inlaws';
        if (in_array($r, $extended, true)) return 'extended';
        return 'others';
    };

    $groups = [
        'partner' => [],
        'children' => [],
        'mother' => [],
        'father' => [],
        'parents' => [],
        'siblings' => [],
        'grandparents' => [],
        'grandchildren' => [],
        'inlaws' => [],
        'extended' => [],
        'others' => []
    ];

    foreach (($members ?? []) as $m) {
        $g = $groupOf($m['relation'] ?? '');
        $groups[$g][] = $m;
    }

    $laneTitles = [
        'partner' => 'Partner / Spouse',
        'children' => 'Children',
        'mother' => 'Mother',
        'father' => 'Father',
        'parents' => 'Parents',
        'siblings' => 'Siblings',
        'grandparents' => 'Grandparents',
        'grandchildren' => 'Grandchildren',
        'inlaws' => 'In-Laws',
        'extended' => 'Extended Family',
        'others' => 'Others'
    ];

    $laneOrder = ['partner', 'mother', 'father', 'parents', 'children', 'siblings', 'inlaws', 'grandparents', 'grandchildren', 'extended', 'others'];

    $byId = [];
    foreach (($members ?? []) as $m) {
        $mid = (int)($m['id'] ?? 0);
        if ($mid > 0) {
            $byId[$mid] = (string)($m['name'] ?? '');
        }
    }

    $userMother = '';
    $userFather = '';
    if (!empty($groups['mother'])) {
        $userMother = (string)($groups['mother'][0]['name'] ?? '');
    }
    if (!empty($groups['father'])) {
        $userFather = (string)($groups['father'][0]['name'] ?? '');
    }

    $chip = function ($label) {
        $t = trim((string)$label);
        if ($t === '') return '';
        return '<span class="family-chip">' . htmlspecialchars($t) . '</span>';
    };

    $memberInitial = function ($name) {
        $n = trim((string)$name);
        if ($n === '') return '?';
        return strtoupper(substr($n, 0, 1));
    };

    $matches = is_array($matches ?? null) ? $matches : [];

    $phoneDigits = function ($phone) {
        $digits = preg_replace('/\D+/', '', (string)$phone);
        return is_string($digits) ? $digits : '';
    };

    $waLink = function ($phone, $message) use ($phoneDigits) {
        $digits = $phoneDigits($phone);
        if ($digits === '') return '';
        // wa.me expects country code (no plus). If user stored a local number without country code,
        // WhatsApp may fail; tel: still works.
        $q = rawurlencode((string)$message);
        return 'https://wa.me/' . $digits . '?text=' . $q;
    };
?>

<div class="routina-wrap family-shell">
    <div class="routina-header">
       <div>
           <div class="routina-title">Family Tree</div>
           <div class="routina-sub">Manage family members and relations.</div>
       </div>
       <div class="family-toolbar">
           <a class="btn btn-sm <?php echo $viewMode === 'tree' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="/family?view=tree">Tree view</a>
           <a class="btn btn-sm <?php echo $viewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary'; ?>" href="/family?view=list">List view</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="family-layout">
        <aside class="family-slice">
            <div class="card">
                <div class="card-kicker">Add Member</div>
                <form method="post" class="mt-3" data-family-form>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" required />
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Relationship</label>
                            <select name="relation" class="form-select" required>
                                <optgroup label="Immediate Family">
                                    <option value="Spouse">Spouse</option>
                                    <option value="Boyfriend">Boyfriend</option>
                                    <option value="Girlfriend">Girlfriend</option>
                                    <option value="Child">Child</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Father">Father</option>
                                    <option value="Parent">Parent</option>
                                    <option value="Brother">Brother</option>
                                    <option value="Sister">Sister</option>
                                    <option value="Sibling">Sibling</option>
                                </optgroup>
                                <optgroup label="Extended Family">
                                    <option value="Grandparent">Grandparent</option>
                                    <option value="Grandfather">Grandfather</option>
                                    <option value="Grandmother">Grandmother</option>
                                    <option value="Grandchild">Grandchild</option>
                                    <option value="Grandson">Grandson</option>
                                    <option value="Granddaughter">Granddaughter</option>
                                    <option value="Cousin">Cousin</option>
                                    <option value="Uncle">Uncle</option>
                                    <option value="Aunt">Aunt</option>
                                    <option value="Nephew">Nephew</option>
                                    <option value="Niece">Niece</option>
                                </optgroup>
                                <optgroup label="In-Laws">
                                    <option value="Father-in-law">Father-in-law</option>
                                    <option value="Mother-in-law">Mother-in-law</option>
                                    <option value="Brother-in-law">Brother-in-law</option>
                                    <option value="Sister-in-law">Sister-in-law</option>
                                    <option value="Son-in-law">Son-in-law</option>
                                    <option value="Daughter-in-law">Daughter-in-law</option>
                                </optgroup>
                                <optgroup label="Other">
                                    <option value="Step-parent">Step-parent</option>
                                    <option value="Step-child">Step-child</option>
                                    <option value="Step-sibling">Step-sibling</option>
                                    <option value="Half-sibling">Half-sibling</option>
                                    <option value="Godparent">Godparent</option>
                                    <option value="Godchild">Godchild</option>
                                    <option value="Guardian">Guardian</option>
                                    <option value="Other">Other</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Side of family</label>
                            <select name="side_of_family" class="form-select">
                                <option value="">—</option>
                                <option value="Father">Father side</option>
                                <option value="Mother">Mother side</option>
                                <option value="Partner">Partner side</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <input name="gender" class="form-control" placeholder="Male / Female / ..." list="familyGenderOptions" />
                            <datalist id="familyGenderOptions">
                                <option value="Male"></option>
                                <option value="Female"></option>
                                <option value="Non-binary"></option>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact number <span class="text-danger">*</span></label>
                            <input name="phone" class="form-control" required />
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">DOB</label>
                            <input name="birthdate" type="date" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DOD</label>
                            <input name="deathdate" type="date" class="form-control" />
                        </div>
                    </div>

                    <!-- Family Connections Matrix -->
                    <div class="card-kicker mt-3 mb-2" style="font-size: 0.85rem;">Family Connections</div>
                    <div class="form-text mb-2">Link this person to other family members to build the family tree matrix.</div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Mother</label>
                            <select name="mother_id" class="form-select">
                                <option value="">— Select mother —</option>
                                <?php foreach (($members ?? []) as $opt): ?>
                                    <?php $optId = (int)($opt['id'] ?? 0); ?>
                                    <?php if ($optId > 0): ?>
                                        <option value="<?php echo $optId; ?>"><?php echo htmlspecialchars((string)($opt['name'] ?? '')); ?><?php echo !empty($opt['relation']) ? ' — ' . htmlspecialchars((string)$opt['relation']) : ''; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Father</label>
                            <select name="father_id" class="form-select">
                                <option value="">— Select father —</option>
                                <?php foreach (($members ?? []) as $opt): ?>
                                    <?php $optId = (int)($opt['id'] ?? 0); ?>
                                    <?php if ($optId > 0): ?>
                                        <option value="<?php echo $optId; ?>"><?php echo htmlspecialchars((string)($opt['name'] ?? '')); ?><?php echo !empty($opt['relation']) ? ' — ' . htmlspecialchars((string)$opt['relation']) : ''; ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Spouse / Partner</label>
                        <select name="spouse_member_id" class="form-select">
                            <option value="">— Select spouse/partner —</option>
                            <?php foreach (($members ?? []) as $opt): ?>
                                <?php $optId = (int)($opt['id'] ?? 0); ?>
                                <?php if ($optId > 0): ?>
                                    <option value="<?php echo $optId; ?>"><?php echo htmlspecialchars((string)($opt['name'] ?? '')); ?><?php echo !empty($opt['relation']) ? ' — ' . htmlspecialchars((string)$opt['relation']) : ''; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">If this person is married, select their spouse from your family tree.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input name="email" type="email" class="form-control" required data-family-email />
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" name="no_email" data-family-no-email>
                            <label class="form-check-label">No email available</label>
                        </div>
                        <div class="form-text">Email + contact number will be reused later for address book/search.</div>
                    </div>
                    <button class="btn btn-primary w-100">Add Member</button>
                </form>
            </div>
        </aside>

        <section>
            <?php if ($viewMode === 'tree'): ?>
                <div class="family-tree">
                    <div class="family-tree__canvas">
                        <div class="family-tree__root">
                            <div class="family-node family-node--me">
                                <div class="family-node__top">
                                    <div class="family-node__avatar"><?php echo htmlspecialchars($meInitial); ?></div>
                                    <div style="min-width:0;">
                                        <div class="family-node__name"><?php echo htmlspecialchars($displayName); ?></div>
                                        <div class="family-node__meta">You (root)</div>
                                    </div>
                                </div>
                                <div class="family-node__chips">
                                    <?php echo $chip('Head of tree'); ?>
                                    <?php echo $chip('Single plane'); ?>
                                    <?php if ($userMother !== '') echo $chip('Mother: ' . $userMother); ?>
                                    <?php if ($userFather !== '') echo $chip('Father: ' . $userFather); ?>
                                </div>
                            </div>
                        </div>

                        <div class="family-lanes">
                            <?php foreach ($laneOrder as $laneKey): ?>
                                <?php $laneMembers = $groups[$laneKey] ?? []; ?>
                                <div class="family-lane">
                                    <div class="family-lane__head">
                                        <div class="family-lane__title"><?php echo htmlspecialchars((string)($laneTitles[$laneKey] ?? $laneKey)); ?></div>
                                        <div class="family-lane__count"><?php echo (int)count($laneMembers); ?></div>
                                    </div>

                                    <?php if (empty($laneMembers)): ?>
                                        <div class="text-muted" style="text-align:center; padding: 6px 0;">No entries yet.</div>
                                    <?php else: ?>
                                        <div class="family-lane__nodes">
                                            <?php foreach ($laneMembers as $m): ?>
                                                <?php
                                                    $mName = (string)($m['name'] ?? '');
                                                    $mRel = (string)($m['relation'] ?? '');
                                                    $mSide = trim((string)($m['side_of_family'] ?? ''));
                                                    $mDob = trim((string)($m['birthdate'] ?? ''));
                                                    $mDod = trim((string)($m['deathdate'] ?? ''));
                                                    $mPhone = trim((string)($m['phone'] ?? ''));
                                                    $mEmail = trim((string)($m['email'] ?? ''));
                                                    $mNoEmail = !empty($m['no_email']);
                                                    $mMotherId = (int)($m['mother_id'] ?? 0);
                                                    $mFatherId = (int)($m['father_id'] ?? 0);
                                                    $mSpouseId = (int)($m['spouse_member_id'] ?? 0);
                                                    $mMotherName = ($mMotherId > 0 && isset($byId[$mMotherId])) ? (string)$byId[$mMotherId] : '';
                                                    $mFatherName = ($mFatherId > 0 && isset($byId[$mFatherId])) ? (string)$byId[$mFatherId] : '';
                                                    $mSpouseName = ($mSpouseId > 0 && isset($byId[$mSpouseId])) ? (string)$byId[$mSpouseId] : '';
                                                    $mId = (int)($m['id'] ?? 0);
                                                    $match = ($mId > 0 && isset($matches[$mId])) ? $matches[$mId] : null;
                                                    $isMatch = is_array($match);
                                                    $buzzMessage = 'Hi ' . ($mName !== '' ? $mName : 'there') . ', this is a quick buzz from Routina. Can we connect?';
                                                    $whatsApp = $waLink($mPhone, $buzzMessage);
                                                ?>
                                                <div class="family-node family-node--member <?php echo $isMatch ? 'family-node--match' : ''; ?>">
                                                    <div class="family-node__top">
                                                        <div class="family-node__avatar"><?php echo htmlspecialchars($memberInitial($mName)); ?></div>
                                                        <div style="min-width:0;">
                                                            <div class="family-node__name"><?php echo htmlspecialchars($mName !== '' ? $mName : 'Person'); ?></div>
                                                            <div class="family-node__meta"><?php echo htmlspecialchars($mRel !== '' ? $mRel : '—'); ?></div>
                                                        </div>
                                                    </div>

                                                    <div class="family-node__chips">
                                                        <?php if ($mSide !== '') echo $chip($mSide . ' side'); ?>
                                                        <?php if ($mDob !== '') echo $chip('DOB ' . $mDob); ?>
                                                        <?php if ($mDod !== '') echo $chip('DOD ' . $mDod); ?>
                                                        <?php if (!empty($m['is_foreign'])): ?>
                                                            <?php echo $chip('Linked from: ' . ($m['linked_owner_name'] ?? '')); ?>
                                                        <?php endif; ?>
                                                        <?php if ($isMatch): ?>
                                                            <span class="family-badge" title="This contact matches a Routina user">On Routina</span>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="family-node__secondary">
                                                        <div><?php echo htmlspecialchars($mPhone !== '' ? $mPhone : ''); ?></div>
                                                        <div><?php echo htmlspecialchars($mNoEmail ? 'No email' : $mEmail); ?></div>
                                                        <?php if ($mSpouseName !== ''): ?>
                                                            <div class="text-primary"><?php echo htmlspecialchars('Spouse: ' . $mSpouseName); ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($mMotherName !== ''): ?>
                                                            <div><?php echo htmlspecialchars('Mother: ' . $mMotherName); ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($mFatherName !== ''): ?>
                                                            <div><?php echo htmlspecialchars('Father: ' . $mFatherName); ?></div>
                                                        <?php endif; ?>
                                                    </div>

                                                    <?php if ($mPhone !== '' || (!$mNoEmail && $mEmail !== '')): ?>
                                                        <div class="family-node__actions">
                                                            <?php if ($mPhone !== ''): ?>
                                                                <a class="btn btn-sm btn-outline-secondary" href="tel:<?php echo htmlspecialchars($mPhone); ?>">Call</a>
                                                            <?php endif; ?>
                                                            <?php if (!$mNoEmail && $mEmail !== ''): ?>
                                                                <a class="btn btn-sm btn-outline-secondary" href="mailto:<?php echo htmlspecialchars($mEmail); ?>">Email</a>
                                                            <?php endif; ?>
                                                            <?php if ($whatsApp !== ''): ?>
                                                                <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($whatsApp); ?>" target="_blank" rel="noopener">WhatsApp</a>
                                                                <a class="btn btn-sm btn-primary" href="<?php echo htmlspecialchars($whatsApp); ?>" target="_blank" rel="noopener">Buzz</a>
                                                            <?php else: ?>
                                                                <?php if (!$mNoEmail && $mEmail !== ''): ?>
                                                                    <a class="btn btn-sm btn-primary" href="mailto:<?php echo htmlspecialchars($mEmail); ?>?subject=<?php echo rawurlencode('Routina buzz'); ?>&body=<?php echo rawurlencode($buzzMessage); ?>">Buzz</a>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if ($isMatch): ?>
                                                        <div class="family-node__actions">
                                                            <form method="post" action="/buzz/send" style="display:inline;">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="to_user_id" value="<?php echo (int)($match['user_id'] ?? 0); ?>" />
                                                                <input type="hidden" name="family_member_id" value="<?php echo (int)$mId; ?>" />
                                                                <input type="hidden" name="channel" value="in_app" />
                                                                <input type="hidden" name="message" value="<?php echo htmlspecialchars($buzzMessage); ?>" />
                                                                <input type="hidden" name="return_to" value="/family?view=tree" />
                                                                <button class="btn btn-sm btn-primary" type="submit">Buzz (in-app)</button>
                                                            </form>
                                                            <a class="btn btn-sm btn-outline-secondary" href="/buzz">Inbox</a>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="text-muted" style="margin-top: 10px;">
                        Tip: use “List view” for inline edit and delete.
                    </div>
                </div>
            <?php else: ?>
                <?php
                    $cousins = [];
                    foreach (($members ?? []) as $m) {
                        if ($normalize($m['relation'] ?? '') === 'cousin') {
                            $cousins[] = $m;
                        }
                    }
                ?>

                <div class="family-main-card">
                    <?php if (!empty($cousins)): ?>
                        <div class="card-kicker">Cousin parent mapping</div>
                        <div class="text-muted" style="margin-top: 8px;">Map each cousin’s Mother/Father using existing family members (aunt/uncle etc.).</div>

                        <div class="table-responsive" style="margin-top: 10px;">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="min-width: 220px;">Cousin</th>
                                        <th style="min-width: 220px;">Mother</th>
                                        <th style="min-width: 220px;">Father</th>
                                        <th style="width: 120px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cousins as $c): ?>
                                        <?php
                                            $cid = (int)($c['id'] ?? 0);
                                            $cm = (int)($c['mother_id'] ?? 0);
                                            $cf = (int)($c['father_id'] ?? 0);
                                            $formId = 'cousin-parent-' . $cid;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)($c['name'] ?? '')); ?></td>
                                            <td>
                                                    <select name="mother_id" form="<?php echo htmlspecialchars($formId); ?>" class="form-select form-select-sm">
                                                        <option value="">—</option>
                                                        <?php foreach (($members ?? []) as $opt): ?>
                                                            <?php $optId = (int)($opt['id'] ?? 0); ?>
                                                            <?php if ($optId > 0 && $optId !== $cid): ?>
                                                                <option value="<?php echo $optId; ?>" <?php echo ($cm === $optId) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($opt['name'] ?? '')); ?><?php echo !empty($opt['relation']) ? ' — ' . htmlspecialchars((string)$opt['relation']) : ''; ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                            </td>
                                            <td>
                                                    <select name="father_id" form="<?php echo htmlspecialchars($formId); ?>" class="form-select form-select-sm">
                                                        <option value="">—</option>
                                                        <?php foreach (($members ?? []) as $opt): ?>
                                                            <?php $optId = (int)($opt['id'] ?? 0); ?>
                                                            <?php if ($optId > 0 && $optId !== $cid): ?>
                                                                <option value="<?php echo $optId; ?>" <?php echo ($cf === $optId) ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($opt['name'] ?? '')); ?><?php echo !empty($opt['relation']) ? ' — ' . htmlspecialchars((string)$opt['relation']) : ''; ?></option>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </select>
                                            </td>
                                            <td class="text-end">
                                                <form id="<?php echo htmlspecialchars($formId); ?>" method="post" action="/family/update-parents?id=<?php echo $cid; ?>" style="display:inline;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="return_to" value="/family?view=list">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr style="margin: 14px 0;" />
                    <?php endif; ?>

                    <div class="card-kicker">Members</div>
                    <?php if (empty($members)): ?>
                        <div class="text-muted" style="padding-top: 10px;">No family members added.</div>
                    <?php else: ?>
                        <div class="table-responsive" style="margin-top: 10px;">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Relationship</th>
                                        <th>Gender</th>
                                        <th>DOB</th>
                                        <th>DOD</th>
                                        <th>Side</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Match</th>
                                        <th style="width: 160px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($members as $m): ?>
                                        <?php
                                            $dob = trim((string)($m['birthdate'] ?? ''));
                                            $dod = trim((string)($m['deathdate'] ?? ''));
                                            $email = $m['email'] ?? '';
                                            $noEmail = !empty($m['no_email']);
                                            $memberId = (int)($m['id'] ?? 0);
                                            $match = ($memberId > 0 && isset($matches[$memberId])) ? $matches[$memberId] : null;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars((string)($m['name'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars((string)($m['relation'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars((string)($m['gender'] ?? '')); ?></td>
                                            <td><?php echo htmlspecialchars($dob); ?></td>
                                            <td><?php echo htmlspecialchars($dod); ?></td>
                                            <td><?php echo htmlspecialchars((string)($m['side_of_family'] ?? '')); ?></td>
                                            <td>
                                                <?php if ($noEmail): ?>
                                                    <span class="text-muted">No email</span>
                                                <?php else: ?>
                                                    <?php echo htmlspecialchars((string)$email); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars((string)($m['phone'] ?? '')); ?></td>
                                            <td>
                                                <?php if (is_array($match)): ?>
                                                    <span class="family-badge" title="Matches by <?php echo htmlspecialchars((string)($match['type'] ?? 'contact')); ?>">On Routina</span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ((int)($m['user_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-family-edit-toggle="<?php echo $memberId; ?>">Edit</button>
                                                    <form method="post" action="/family/delete?id=<?php echo $memberId; ?>" style="display:inline;" onsubmit="return confirm('Delete this family member?');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                <?php else: ?>
                                                    <div class="text-muted">Linked from <?php echo htmlspecialchars((string)($m['linked_owner_name'] ?? 'another user')); ?></div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <?php if ((int)($m['user_id'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)): ?>
                                        <tr data-family-edit-row="<?php echo $memberId; ?>" style="display: none;">
                                            <td colspan="10">
                                                <div class="border rounded-3 p-3" style="background: rgba(0,0,0,0.02);">
                                                    <div class="d-flex align-items-center justify-content-between" style="gap: 12px;">
                                                        <div class="fw-semibold">Edit: <?php echo htmlspecialchars((string)($m['name'] ?? '')); ?></div>
                                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-family-edit-cancel="<?php echo $memberId; ?>">Cancel</button>
                                                    </div>

                                                    <?php
                                                        $rels = ['Spouse','Boyfriend','Girlfriend','Child','Mother','Father','Parent','Sibling','Grandparent','Grandchild','Cousin','Uncle','Aunt','Other'];
                                                        $curRel = (string)($m['relation'] ?? '');
                                                        $side = (string)($m['side_of_family'] ?? '');
                                                        $curMotherId = (int)($m['mother_id'] ?? 0);
                                                        $curFatherId = (int)($m['father_id'] ?? 0);
                                                    ?>

                                                    <form method="post" action="/family/update?id=<?php echo $memberId; ?>" class="mt-3" data-family-form>
                                                        <?= csrf_field() ?>

                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Name</label>
                                                                <input name="name" class="form-control" value="<?php echo htmlspecialchars((string)($m['name'] ?? '')); ?>" required />
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Relationship</label>
                                                                <select name="relation" class="form-select" required>
                                                                    <?php foreach ($rels as $r): ?>
                                                                        <option value="<?php echo htmlspecialchars($r); ?>" <?php echo ($curRel === $r) ? 'selected' : ''; ?>><?php echo htmlspecialchars($r); ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Side of family</label>
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
                                                            <button class="btn btn-outline-secondary" type="button" data-family-edit-cancel="<?php echo $memberId; ?>">Cancel</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php else: ?>
                                            <!-- foreign/linked member: no edit row -->
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<script src="/js/family.js" defer></script>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
