<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= BASE_PATH ?>/truck/add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Truck</a>
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
                <th>Plate Number</th>
                <th>Capacity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($trucks)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No trucks found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($trucks as $truck): ?>
                    <tr>
                        <td><?= htmlspecialchars($truck['truck_id']) ?></td>
                        <td><?= htmlspecialchars($truck['plate_number']) ?></td>
                        <td><?= htmlspecialchars($truck['capacity'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($truck['status']) ?></td>
                        <td class="actions">
                            <a href="<?= BASE_PATH ?>/truck/edit?id=<?= $truck['truck_id'] ?>" class="btn btn-primary">Edit</a>
                            <a href="<?= BASE_PATH ?>/truck/delete?id=<?= $truck['truck_id'] ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this truck?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>