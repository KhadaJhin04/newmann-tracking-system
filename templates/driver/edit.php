<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<form action="<?= BASE_PATH ?>/driver/edit?id=<?= $driver['driver_id'] ?>" method="POST">
    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($driver['name']) ?>" required>
    </div>

    <div class="form-group">
        <label for="contact">Contact Number</label>
        <input type="text" name="contact" id="contact" class="form-control" value="<?= htmlspecialchars($driver['contact']) ?>">
    </div>

    <div class="form-group">
        <label for="license_number">License Number</label>
        <input type="text" name="license_number" id="license_number" class="form-control" value="<?= htmlspecialchars($driver['license_number']) ?>" required>
    </div>

    <hr>
    <h4>App Login Credentials</h4>

    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($driver['username']) ?>">
    </div>

    <div class="form-group">
        <label for="password">New Password (leave blank to keep current)</label>
        <input type="password" name="password" id="password" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= BASE_PATH ?>/drivers" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>