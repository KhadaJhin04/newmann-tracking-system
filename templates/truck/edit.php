<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<form action="<?= BASE_PATH ?>/truck/edit?id=<?= $truck['truck_id'] ?>" method="POST">
    <div class="form-group">
        <label for="plate_number">Plate Number</label>
        <input type="text" name="plate_number" id="plate_number" class="form-control" value="<?= htmlspecialchars($truck['plate_number']) ?>" required>
    </div>

    <div class="form-group">
        <label for="capacity">Capacity (Motorcycles)</label>
        <input type="number" name="capacity" id="capacity" class="form-control" min="0" value="<?= htmlspecialchars($truck['capacity']) ?>">
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select name="status" id="status" class="form-control" required>
            <option value="Active" <?= ($truck['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
            <option value="Under Maintenance" <?= ($truck['status'] == 'Under Maintenance') ? 'selected' : '' ?>>Under Maintenance</option>
            <option value="Inactive" <?= ($truck['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= BASE_PATH ?>/trucks" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>