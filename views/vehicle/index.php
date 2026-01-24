<?php ob_start(); ?>

<div class="routina-wrap">
    <div class="routina-header">
       <div>
           <div class="routina-title">Vehicle Manager</div>
           <div class="routina-sub">Keep track of your fleet.</div>
       </div>
       <div class="d-flex gap-2">
           <a class="btn btn-outline-secondary btn-sm" href="/vehicle/dashboard">Dashboard</a>
           <a class="btn btn-outline-secondary btn-sm" href="/vehicle/new">New</a>
       </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger mt-3">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <div class="card-kicker">Add Vehicle</div>
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
                <button class="btn btn-primary w-100">Add Vehicle</button>
            </form>
        </div>

        <?php if (!empty($vehicles)): ?>
            <?php foreach($vehicles as $v): ?>
                <div class="card">
                    <div class="card-kicker"><?php echo htmlspecialchars($v['status']); ?></div>
                    <div class="card-title"><?php echo htmlspecialchars($v['year'] . ' ' . $v['make'] . ' ' . $v['model']); ?></div>
                    <div class="muted">
                        Plate: <?php echo htmlspecialchars($v['license_plate']); ?>
                    </div>
                    <div class="card-buttons">
                        <a href="/vehicle/edit?id=<?php echo $v['id']; ?>" class="btn-soft">Edit</a>
                        <a href="/vehicle/dashboard" class="btn-soft">Dashboard</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
             <div class="card d-flex align-items-center justify-content-center text-muted p-5">
                 No vehicles added.
             </div>
        <?php endif; ?>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
