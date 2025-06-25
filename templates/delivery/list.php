<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<div class="page-header">
    <a href="<?= BASE_PATH ?>/delivery/add" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Delivery</a>
</div>

<div class="filter-form-container">
    <form action="<?= BASE_PATH ?>/deliveries" method="GET" class="filter-form">
        <div class="filter-group search-group">
            <input type="search" name="search" id="search" placeholder="Search by ID, Destination, Driver, or Plate #" value="<?= htmlspecialchars($search_term ?? '') ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
            <?php if (!empty($search_term)): ?>
                <a href="<?= BASE_PATH ?>/deliveries" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Driver</th>
                <th>Truck Plate #</th>
                <th>Destination</th>
                <th>Status</th>
                <th>Total Items</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($deliveries)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">
                        <?php if (!empty($search_term)): ?>
                            No deliveries found matching your search.
                        <?php else: ?>
                            No deliveries found.
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($deliveries as $delivery): ?>
                    <tr>
                        <td><?= htmlspecialchars($delivery['delivery_id']) ?></td>
                        <td><?= htmlspecialchars($delivery['driver_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($delivery['plate_number'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars(substr($delivery['destination_details'], 0, 50)) . (strlen($delivery['destination_details']) > 50 ? '...' : '') ?></td>
                        <td><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $delivery['status'])) ?>"><?= htmlspecialchars($delivery['status']) ?></span></td>
                        <td><?= htmlspecialchars($delivery['motorcycle_count']) ?></td>
                        <td class="actions">
                            <a href="<?= BASE_PATH ?>/delivery/details?id=<?= $delivery['delivery_id'] ?>" class="btn btn-secondary">View</a>
                            <a href="<?= BASE_PATH ?>/delivery/edit?id=<?= $delivery['delivery_id'] ?>" class="btn btn-primary">Edit</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>