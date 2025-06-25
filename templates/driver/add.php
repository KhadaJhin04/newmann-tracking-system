<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<form action="<?= BASE_PATH ?>/driver/add" method="POST">
    <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" required>
    </div>

    <div class="form-group">
        <label for="contact">Contact Number</label>
        <input type="text" name="contact" id="contact" class="form-control">
    </div>

    <div class="form-group">
        <label for="license_number">License Number</label>
        <input type="text" name="license_number" id="license_number" class="form-control" required>
    </div>
    
    <hr>
    <h4>App Login Credentials (Optional)</h4>

    <div class="form-group">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" class="form-control">
    </div>

    <div class="form-group">
        <label for="password">Password (min. 6 characters)</label>
        <input type="password" name="password" id="password" class="form-control">
    </div>

    <button type="submit" class="btn btn-primary">Add Driver</button>
    <a href="<?= BASE_PATH ?>/drivers" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>