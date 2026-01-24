<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Edit Vehicle</div>
           <div class="routina-sub">Update vehicle details and status.</div>
       </div>
       <div>
           <a class="btn btn-outline-secondary btn-sm" href="/vehicle">Back</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="max-width: 720px;">
        <div class="card-kicker">Vehicle details</div>
        <form method="post" class="mt-3" data-vehicle-picker>
            <?= csrf_field() ?>
            <div class="row g-3 mb-3">
                <div class="col-4">
                    <label class="form-label">Year</label>
                    <?php $maxYear = (int)date('Y') + 1; $minYear = 1960; $currentYear = (int)$vehicle['year']; ?>
                    <select name="year" class="form-select" required data-vehicle-year>
                        <option value="" disabled>Select year</option>
                        <?php for ($y = $maxYear; $y >= $minYear; $y--): ?>
                            <option value="<?php echo $y; ?>" <?php echo $y === $currentYear ? 'selected' : ''; ?>><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Make</label>
                    <input name="make" class="form-control" value="<?php echo htmlspecialchars($vehicle['make']); ?>" list="vehicleMakeList" autocomplete="off" required data-vehicle-make />
                    <datalist id="vehicleMakeList"></datalist>
                </div>
                <div class="col-6">
                    <label class="form-label">Model</label>
                    <input name="model" class="form-control" value="<?php echo htmlspecialchars($vehicle['model']); ?>" list="vehicleModelList" autocomplete="off" required data-vehicle-model />
                    <datalist id="vehicleModelList"></datalist>
                    <div class="form-text">Models load after you pick year + make.</div>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-8">
                    <label class="form-label">License Plate</label>
                    <input name="plate" class="form-control" value="<?php echo htmlspecialchars($vehicle['license_plate']); ?>" required />
                </div>
                <div class="col-4">
                    <label class="form-label">Trim/Submodel</label>
                    <input name="trim" class="form-control" value="<?php echo htmlspecialchars($vehicle['trim'] ?? ''); ?>" />
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Engine</label>
                    <input name="engine" class="form-control" value="<?php echo htmlspecialchars($vehicle['engine'] ?? ''); ?>" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gear type</label>
                    <?php $tx = (string)($vehicle['transmission'] ?? ''); ?>
                    <select name="transmission" class="form-select">
                        <option value="">Select</option>
                        <option value="Automatic" <?php echo $tx === 'Automatic' ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Manual" <?php echo $tx === 'Manual' ? 'selected' : ''; ?>>Manual</option>
                        <option value="CVT" <?php echo $tx === 'CVT' ? 'selected' : ''; ?>>CVT</option>
                        <option value="DCT" <?php echo $tx === 'DCT' ? 'selected' : ''; ?>>DCT</option>
                        <option value="Other" <?php echo $tx === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Fuel type</label>
                    <input name="fuel_type" class="form-control" value="<?php echo htmlspecialchars($vehicle['fuel_type'] ?? ''); ?>" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Drivetrain</label>
                    <input name="drivetrain" class="form-control" value="<?php echo htmlspecialchars($vehicle['drivetrain'] ?? ''); ?>" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Color</label>
                    <input name="color" class="form-control" value="<?php echo htmlspecialchars($vehicle['color'] ?? ''); ?>" />
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Owned date</label>
                    <input type="date" name="owned_date" class="form-control" value="<?php echo htmlspecialchars($vehicle['owned_date'] ?? ''); ?>" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Registration date</label>
                    <input type="date" name="registration_date" class="form-control" value="<?php echo htmlspecialchars($vehicle['registration_date'] ?? ''); ?>" />
                </div>
                <div class="col-md-4">
                    <label class="form-label">Registration expiry</label>
                    <input type="date" name="registration_expiry" class="form-control" value="<?php echo htmlspecialchars($vehicle['registration_expiry'] ?? ''); ?>" />
                </div>
            </div>
            <div class="card" style="padding: 14px; margin-bottom: 14px;">
                <div class="card-kicker">Insurance details (optional)</div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Provider</label>
                        <input name="insurance_provider" class="form-control" value="<?php echo htmlspecialchars($vehicle['insurance_provider'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Policy number</label>
                        <input name="insurance_policy_number" class="form-control" value="<?php echo htmlspecialchars($vehicle['insurance_policy_number'] ?? ''); ?>" />
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <label class="form-label">Insurance start</label>
                        <input type="date" name="insurance_start_date" class="form-control" value="<?php echo htmlspecialchars($vehicle['insurance_start_date'] ?? ''); ?>" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Insurance expiry</label>
                        <input type="date" name="insurance_end_date" class="form-control" value="<?php echo htmlspecialchars($vehicle['insurance_end_date'] ?? ''); ?>" />
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">Insurance notes</label>
                    <textarea name="insurance_notes" class="form-control" rows="2"><?php echo htmlspecialchars($vehicle['insurance_notes'] ?? ''); ?></textarea>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo $vehicle['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $vehicle['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="maintenance" <?php echo $vehicle['status'] === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Save Changes</button>
                <a class="btn btn-outline-secondary" href="/vehicle">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
