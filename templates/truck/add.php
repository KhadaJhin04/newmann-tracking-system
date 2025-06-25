<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<form action="<?= BASE_PATH ?>/truck/add" method="POST">
    <div class="form-group">
        <label for="plate_number">Plate Number</label>
        <input type="text" name="plate_number" id="plate_number" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="capacity">Capacity (Motorcycles)</label>
        <input type="number" name="capacity" id="capacity" class="form-control" min="0">
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status" class="form-control" required>
            <option value="Active" selected>Active</option>
            <option value="Under Maintenance">Under Maintenance</option>
            <option value="Inactive">Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Add Truck</button>
    <a href="<?= BASE_PATH ?>/trucks" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>