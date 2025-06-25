<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['error_message'] /* Use raw output to render <br> tags */ ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<form action="<?= BASE_PATH ?>/user/edit?id=<?= $user['manager_id'] ?>" method="POST">
    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
    </div>
    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
    </div>
    <div class="form-group">
        <label for="role">Role</label>
        <select name="role" id="role" class="form-control" required onchange="toggleWarehouseAssignment(this.value)">
            <option value="">-- Select Role --</option>
            <option value="System Admin" <?= ($user['role'] == 'System Admin') ? 'selected' : '' ?>>System Admin</option>
            <option value="Logistics Manager" <?= ($user['role'] == 'Logistics Manager') ? 'selected' : '' ?>>Logistics Manager</option>
            <option value="Warehouse Manager" <?= ($user['role'] == 'Warehouse Manager') ? 'selected' : '' ?>>Warehouse Manager</option>
        </select>
    </div>
     <div class="form-group" id="warehouse-assignment" style="display: <?= ($user['role'] === 'Warehouse Manager') ? 'block' : 'none'; ?>;">
        <label for="warehouse_id">Assign to Warehouse</label>
        <select name="warehouse_id" id="warehouse_id" class="form-control">
            <option value="">-- Select Warehouse --</option>
            <?php foreach($warehouses as $warehouse): ?>
                <option value="<?= $warehouse['warehouse_id'] ?>" <?= ($user['warehouse_id'] == $warehouse['warehouse_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($warehouse['location']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="password">New Password (leave blank to keep current)</label>
        <input type="password" name="password" id="password" class="form-control">
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm New Password</label>
        <input type="password" name="confirm_password" id="confirm_password" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= BASE_PATH ?>/users" class="btn btn-secondary">Cancel</a>
</form>

<script>
    function toggleWarehouseAssignment(role) {
        const warehouseSection = document.getElementById('warehouse-assignment');
        if (role === 'Warehouse Manager') {
            warehouseSection.style.display = 'block';
        } else {
            warehouseSection.style.display = 'none';
        }
    }
</script>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>