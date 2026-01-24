<?php ob_start(); ?>

<div class="container mt-4">
  <div class="profile-section-nav mb-4">
    <a href="/profile?section=profile" class="profile-section-link <?php echo ($Model->ActiveSection == "profile" ? "is-active" : ""); ?>">Profile</a>
    <a href="/profile?section=avatar" class="profile-section-link <?php echo ($Model->ActiveSection == "avatar" ? "is-active" : ""); ?>">Avatar</a>
  </div>

  <div class="row gy-4">
    <?php if ($Model->ActiveSection == "avatar"): ?>
        <div class="col-lg-6 col-md-8 mx-auto">
             <div class="card shadow-sm" id="avatar-card">
                <div class="card-body profile-avatar-card">
                  <h3 class="h4 mb-1">Avatar &amp; appearance</h3>
                  <p class="text-muted">Upload a personal photo or pick one of our presets.</p>
                  
                                    <form method="post" action="/profile?section=avatar" enctype="multipart/form-data">
                                        <?= csrf_field() ?>
                    <?php if ($Model->StatusMessage): ?>
                        <div class="alert alert-success py-2 mb-3 small"><?php echo $Model->StatusMessage; ?></div>
                    <?php endif; ?>

                    <div class="profile-avatar-preview mt-3 mb-4 d-flex align-items-center gap-3">
                        <?php if ($Model->Avatar->HasImage && $Model->Avatar->ImageUrl): ?>
                            <img src="<?php echo $Model->Avatar->ImageUrl; ?>" class="rounded-circle border" style="width: 64px; height: 64px; object-fit: cover;" alt="Avatar">
                        <?php elseif ($Model->Avatar->PresetKey): ?>
                            <!-- Map keys to colors roughly using bootstrap utility classes or inline styles --> 
                            <?php 
                                $colors = ['lavender' => '#E6E6FA', 'sage' => '#9DC183', 'teal' => '#008080', 'coral' => '#FF7F50'];
                                $bg = $colors[$Model->Avatar->PresetKey] ?? '#ccc';
                            ?>
                             <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                                  style="width: 64px; height: 64px; font-size: 1.5rem; background-color: <?php echo $bg; ?>; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
                                <?php echo strtoupper(substr($Model->Input['DisplayName'], 0, 1)); ?>
                             </div>
                        <?php else: ?>
                            <div class="profile-avatar-placeholder rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; font-size: 1.5rem;">
                                <?php echo strtoupper(substr($Model->Input['DisplayName'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="profile-avatar-meta">
                            <div class="fw-semibold"><?php echo htmlspecialchars($Model->Input['DisplayName']); ?></div>
                            <div class="text-muted small">
                                <?php echo ($Model->Avatar->HasImage || $Model->Avatar->PresetKey) ? "Looking good!" : "No avatar set"; ?>
                            </div>
                             <?php if ($Model->Avatar->HasImage || $Model->Avatar->PresetKey): ?>
                                  <button type="submit" name="DeleteAvatar" value="1" class="btn btn-link btn-sm text-danger p-0 text-decoration-none" style="font-size: 0.8rem;">Remove Avatar</button>
                             <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small text-uppercase text-muted fw-bold">Presets</label>
                        <div class="d-flex gap-3">
                            <?php 
                                $colors = ['lavender' => '#E6E6FA', 'sage' => '#9DC183', 'teal' => '#008080', 'coral' => '#FF7F50'];
                            ?>
                            <?php foreach ($Model->AvatarPresets as $key => $label): ?>
                                 <button type="submit" name="AvatarPresetKey" value="<?php echo $key; ?>" 
                                    class="btn btn-outline-light border rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm"
                                                style="width: 42px; height: 42px; <?php echo $Model->Avatar->PresetKey == $key ? 'outline: 2px solid #7c6dff; outline-offset: 2px;' : ''; ?>"
                                    title="<?php echo $label; ?>">
                                    <div style="width: 100%; height: 100%; border-radius: 50%; background-color: <?php echo $colors[$key]; ?>;"></div>
                                 </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                         <label class="form-label small text-uppercase text-muted fw-bold">Custom Upload</label>
                        <div class="input-group input-group-sm">
                            <input type="file" name="AvatarFile" class="form-control" accept="image/*">
                            <button class="btn btn-primary" type="submit">Upload</button>
                        </div>
                        <div class="form-text">Max size 2MB.</div>
                    </div>

                </form>

                </div>
             </div>
        </div>
    <?php else: ?>
        <!-- Profile Tab -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h2 class="mb-1">Your Profile</h2>
                    <p class="text-muted">Update core profile information.</p>

                    <form method="post" action="/profile?section=profile" class="mt-3">
                        <?= csrf_field() ?>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Display Name 🏷️</label>
                                <input name="DisplayName" class="form-control" value="<?php echo htmlspecialchars($Model->Input['DisplayName']); ?>" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Job Title 💼</label>
                                <input name="JobTitle" class="form-control" value="<?php echo htmlspecialchars($Model->Input['JobTitle'] ?? ''); ?>" placeholder="Software Engineer" />
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                             <div class="col-md-6">
                                <label class="form-label">Date of Birth 🎂</label>
                                <input name="Dob" type="date" class="form-control" value="<?php echo htmlspecialchars($Model->Input['Dob'] ?? ''); ?>" />
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Gender ⚧️</label>
                                <input name="Gender" class="form-control" value="<?php echo htmlspecialchars($Model->Input['Gender'] ?? ''); ?>" placeholder="Select or type..." list="genderOptions" />
                                <datalist id="genderOptions">
                                    <option value="Male"></option>
                                    <option value="Female"></option>
                                    <option value="Non-binary"></option>
                                    <option value="Agender"></option>
                                    <option value="Genderfluid"></option>
                                </datalist>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                             <div class="col-md-6">
                                <label class="form-label">Phone 📱</label>
                                <input name="Phone" class="form-control" value="<?php echo htmlspecialchars($Model->Input['Phone'] ?? ''); ?>" />
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Address 🏠</label>
                                <input name="Address" class="form-control" value="<?php echo htmlspecialchars($Model->Input['Address'] ?? ''); ?>" />
                            </div>
                        </div>

                         <div class="mb-3">
                            <label class="form-label">Bio 📝</label>
                            <textarea name="Bio" class="form-control" rows="3"><?php echo htmlspecialchars($Model->Input['Bio'] ?? ''); ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Country of Origin 🚩</label>
                                <input name="CountryOfOrigin" class="form-control" value="<?php echo htmlspecialchars($Model->Input['CountryOfOrigin'] ?? ''); ?>" />
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Current Location 📍</label>
                                <input name="CurrentLocation" class="form-control" value="<?php echo htmlspecialchars($Model->Input['CurrentLocation'] ?? ''); ?>" />
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Preferred Currency 💱</label>
                                <select name="PreferredCurrencyCode" class="form-select">
                                    <option value="USD">USD</option>
                                    <option value="EUR">EUR</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>
                             <div class="col-md-6">
                                <label class="form-label">Spouses Recorded 💍</label>
                                <input name="SpouseCount" type="number" class="form-control" value="<?php echo $Model->Input['SpouseCount']; ?>" />
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
             <div class="card shadow-sm h-100">
                <div class="card-body">
                  <h3 class="mb-1">Relatives</h3>
                  <p class="text-muted">Capture family members so finance and family dashboards stay in sync.</p>
                  
                  <div class="bg-light rounded-3 border p-3 mt-3">
                      <div class="small text-uppercase text-muted fw-semibold">Household summary</div>
                      <div class="small mt-2">
                          <span class="fw-semibold">Spouses noted:</span> 
                          <?php echo $Model->Input['SpouseCount']; ?>
                      </div>
                  </div>
                </div>
             </div>
        </div>
    <?php endif; ?>
  </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
