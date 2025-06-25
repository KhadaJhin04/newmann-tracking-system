<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<form action="<?= BASE_PATH ?>/model/add" method="POST">
    <div class="form-group">
        <label for="brand">Brand (Optional)</label>
        <input type="text" name="brand" id="brand" class="form-control">
    </div>
    
    <div class="form-group">
        <label for="model_name">Model Name</label>
        <input type="text" name="model_name" id="model_name" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-primary">Add Model</button>
    <a href="<?= BASE_PATH ?>/models" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>