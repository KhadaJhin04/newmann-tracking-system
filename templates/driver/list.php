<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= BASE_PATH ?>/driver/add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Driver</a>
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
                <th>Name</th>
                <th>Contact</th>
                <th>License Number</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($drivers)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No drivers found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($drivers as $driver): ?>
                    <tr>
                        <td><?= htmlspecialchars($driver['driver_id']) ?></td>
                        <td><?= htmlspecialchars($driver['name']) ?></td>
                        <td><?= htmlspecialchars($driver['contact'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($driver['license_number']) ?></td>
                        <td class="actions">
                            <a href="<?= BASE_PATH ?>/driver/edit?id=<?= $driver['driver_id'] ?>" class="btn btn-primary">Edit</a>
                            <a href="<?= BASE_PATH ?>/driver/delete?id=<?= $driver['driver_id'] ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this driver?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>