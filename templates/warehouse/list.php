<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center;">
    <a href="<?= BASE_PATH ?>/warehouse/add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Warehouse</a>
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
                <th>Location</th>
                <th>Manager</th>
                <th>Capacity</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($warehouses)): ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No warehouses found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($warehouses as $warehouse): ?>
                    <tr>
                        <td><?= htmlspecialchars($warehouse['warehouse_id']) ?></td>
                        <td><?= htmlspecialchars($warehouse['location']) ?></td>
                        <td><?= htmlspecialchars($warehouse['manager'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($warehouse['capacity'] ?? 'N/A') ?></td>
                        <td class="actions">
                            <a href="<?= BASE_PATH ?>/warehouse/edit?id=<?= $warehouse['warehouse_id'] ?>" class="btn btn-primary">Edit</a>
                            <a href="<?= BASE_PATH ?>/warehouse/delete?id=<?= $warehouse['warehouse_id'] ?>" 
                               class="btn btn-danger" 
                               onclick="return confirm('Are you sure you want to delete this warehouse?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>