<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= BASE_PATH ?>/model/add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Model</a>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success" style="margin-top: 1rem;">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger" style="margin-top: 1rem;">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Brand</th>
                <th>Model Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($models)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">No motorcycle models found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($models as $model): ?>
                    <tr>
                        <td><?= htmlspecialchars($model['model_id']) ?></td>
                        <td><?= htmlspecialchars($model['brand'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($model['model_name']) ?></td>
                        <td class="actions">
                            <a href="<?= BASE_PATH ?>/model/edit?id=<?= $model['model_id'] ?>" class="btn btn-primary">Edit</a>
                            <a href="<?= BASE_PATH ?>/model/delete?id=<?= $model['model_id'] ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this model? This will fail if the model is in use.');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>