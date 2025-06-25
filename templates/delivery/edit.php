<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<style>.motorcycle-entry { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; } .motorcycle-entry select { flex-grow: 1; } .motorcycle-entry input { width: 80px; }</style>

<form action="<?= BASE_PATH ?>/delivery/edit?id=<?= $delivery['delivery_id'] ?>" method="POST">
    <div class="form-group">
        <label for="driver_id">Assign Driver:</label>
        <select name="driver_id" id="driver_id" required>
            <option value="">-- Select a Driver --</option>
            <?php foreach ($drivers as $driver): ?>
                <option value="<?= $driver['driver_id'] ?>" <?= ($driver['driver_id'] == $delivery['driver_id']) ? 'selected' : '' ?>><?= htmlspecialchars($driver['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="truck_id">Assign Truck:</label>
        <select name="truck_id" id="truck_id" required>
            <option value="">-- Select a Truck --</option>
            <?php foreach ($trucks as $truck): ?>
                <option value="<?= $truck['truck_id'] ?>" <?= ($truck['truck_id'] == $delivery['truck_id']) ? 'selected' : '' ?>><?= htmlspecialchars($truck['plate_number']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="warehouse_id">Origin Warehouse:</label>
        <select name="warehouse_id" id="warehouse_id" required>
            <option value="">-- Select a Warehouse --</option>
            <?php foreach ($warehouses as $warehouse): ?>
                <option value="<?= $warehouse['warehouse_id'] ?>" <?= ($warehouse['warehouse_id'] == $delivery['warehouse_id']) ? 'selected' : '' ?>><?= htmlspecialchars($warehouse['location']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="destination_details">Destination Details:</label>
        <textarea name="destination_details" id="destination_details" rows="3" required><?= htmlspecialchars($delivery['destination_details']) ?></textarea>
    </div>
    
    <div class="form-group">
        <label>Motorcycles Delivered</label>
        <div id="motorcycle-entries-container">
            <?php if (empty($delivery_items)): ?>
                <div class="motorcycle-entry">
                    <select name="motorcycle_model_id[]" required><option value="">-- Select Model --</option><?php foreach ($motorcycle_models_list as $model): ?><option value="<?= $model['model_id'] ?>"><?= htmlspecialchars(($model['brand'] ? $model['brand'] . ' - ' : '') . $model['model_name']) ?></option><?php endforeach; ?></select>
                    <input type="number" name="motorcycle_quantity[]" placeholder="Qty" min="1" value="1" required>
                    <button type="button" class="btn btn-danger remove-motorcycle-btn">X</button>
                </div>
            <?php else: ?>
                <?php foreach ($delivery_items as $item): ?>
                    <div class="motorcycle-entry">
                        <select name="motorcycle_model_id[]" required>
                            <option value="">-- Select Model --</option>
                            <?php foreach ($motorcycle_models_list as $model): ?>
                                <option value="<?= $model['model_id'] ?>" <?= ($model['model_id'] == $item['model_id']) ? 'selected' : '' ?>><?= htmlspecialchars(($model['brand'] ? $model['brand'] . ' - ' : '') . $model['model_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" name="motorcycle_quantity[]" placeholder="Qty" min="1" value="<?= htmlspecialchars($item['quantity']) ?>" required>
                        <button type="button" class="btn btn-danger remove-motorcycle-btn">X</button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" id="add-motorcycle-btn" class="btn btn-success" style="margin-top: 10px;">+ Add Another Model</button>
    </div>

    <div class="form-group">
        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <?php $statuses = ['Pending', 'Scheduled', 'In Transit', 'Delivered', 'Delayed', 'Cancelled']; ?>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $status ?>" <?= ($status == $delivery['status']) ? 'selected' : '' ?>><?= $status ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="departure_datetime">Departure Time:</label>
        <input type="datetime-local" name="departure_datetime" id="departure_datetime" value="<?= $delivery['departure_datetime'] ? date('Y-m-d\TH:i', strtotime($delivery['departure_datetime'])) : '' ?>">
    </div>
    <div class="form-group">
        <label for="estimated_arrival">Estimated Arrival:</label>
        <input type="datetime-local" name="estimated_arrival" id="estimated_arrival" value="<?= $delivery['estimated_arrival'] ? date('Y-m-d\TH:i', strtotime($delivery['estimated_arrival'])) : '' ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="<?= BASE_PATH ?>/deliveries" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>