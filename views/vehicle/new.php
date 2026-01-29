<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Add Vehicle</div>
           <div class="routina-sub">Create a new vehicle record.</div>
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
                    <?php $maxYear = (int)date('Y') + 1; $minYear = 1960; ?>
                    <select name="year" class="form-select" required data-vehicle-year>
                        <option value="" selected disabled>Select year</option>
                        <?php for ($y = $maxYear; $y >= $minYear; $y--): ?>
                            <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label">Make</label>
                    <input name="make" class="form-control" placeholder="Start typing (e.g., Toyota)" list="vehicleMakeList" autocomplete="off" required data-vehicle-make />
                    <datalist id="vehicleMakeList"></datalist>
                </div>
                <div class="col-6">
                    <label class="form-label">Model</label>
                    <input name="model" class="form-control" placeholder="Start typing (e.g., Corolla)" list="vehicleModelList" autocomplete="off" required data-vehicle-model />
                    <datalist id="vehicleModelList"></datalist>
                    <div class="form-text">Models load after you pick year + make.</div>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-8">
                    <label class="form-label">License Plate</label>
                    <input name="plate" class="form-control" placeholder="ABC-1234" required />
                </div>
                    <div class="col-4">
                        <label class="form-label">Trim/Submodel</label>
                        <input name="trim" class="form-control" placeholder="LE, Sport, Limited" />
                    </div>
            </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Engine</label>
                        <input name="engine" class="form-control" placeholder="2.0L I4, 3.5L V6" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Gear type</label>
                        <select name="transmission" class="form-select">
                            <option value="">Select</option>
                            <option value="Automatic">Automatic</option>
                            <option value="Manual">Manual</option>
                            <option value="CVT">CVT</option>
                            <option value="DCT">DCT</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Fuel type</label>
                        <select name="fuel_type" class="form-select">
                            <option value="">Select</option>
                            <option value="Petrol">Petrol</option>
                            <option value="Diesel">Diesel</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Electric">Electric</option>
                            <option value="Plug-in Hybrid">Plug-in Hybrid</option>
                            <option value="CNG">CNG</option>
                            <option value="LPG">LPG</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Drivetrain</label>
                        <select name="drivetrain" class="form-select">
                            <option value="">Select</option>
                            <option value="FWD">FWD (Front-Wheel Drive)</option>
                            <option value="RWD">RWD (Rear-Wheel Drive)</option>
                            <option value="AWD">AWD (All-Wheel Drive)</option>
                            <option value="4WD">4WD (Four-Wheel Drive)</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Color</label>
                        <input name="color" class="form-control" placeholder="Black" />
                    </div>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Owned date</label>
                        <input type="date" name="owned_date" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Registration date</label>
                        <input type="date" name="registration_date" class="form-control" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Registration expiry</label>
                        <input type="date" name="registration_expiry" class="form-control" />
                    </div>
                </div>
                <div class="card" style="padding: 14px;">
                    <div class="card-kicker">Insurance details (optional)</div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Provider</label>
                            <input name="insurance_provider" class="form-control" placeholder="AXA, Allianz" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Policy number</label>
                            <input name="insurance_policy_number" class="form-control" />
                        </div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Insurance start</label>
                            <input type="date" name="insurance_start_date" class="form-control" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Insurance expiry</label>
                            <input type="date" name="insurance_end_date" class="form-control" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Insurance notes</label>
                        <textarea name="insurance_notes" class="form-control" rows="2" placeholder="Coverage, add-ons, reminders"></textarea>
                    </div>
                </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Create Vehicle</button>
                <a class="btn btn-outline-secondary" href="/vehicle">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/main.php';
?>
