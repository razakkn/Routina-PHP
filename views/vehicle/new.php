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
                    <?php $maxYear = (int)date('Y') + 1; ?>
                    <select name="year" class="form-select" required data-vehicle-year>
                        <option value="" selected disabled>Select year</option>
                        <?php for ($y = $maxYear; $y >= 1968; $y--): ?>
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
