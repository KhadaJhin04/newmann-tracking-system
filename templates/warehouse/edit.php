<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<form action="<?= BASE_PATH ?>/warehouse/edit?id=<?= $warehouse['warehouse_id'] ?>" method="POST">
    <div class="form-group">
        <label for="location">Location</label>
        <input type="text" name="location" id="location" class="form-control" value="<?= htmlspecialchars($warehouse['location']) ?>" required>
    </div>

    <div class="form-group">
        <label for="manager">Manager Name (Optional)</label>
        <input type="text" name="manager" id="manager" class="form-control" value="<?= htmlspecialchars($warehouse['manager']) ?>">
    </div>

    <div class="form-group">
        <label for="capacity">Capacity (Motorcycles)</label>
        <input type="number" name="capacity" id="capacity" class="form-control" min="0" value="<?= htmlspecialchars($warehouse['capacity']) ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= BASE_PATH ?>/warehouses" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>