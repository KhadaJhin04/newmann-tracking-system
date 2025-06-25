<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<style>.motorcycle-entry { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; } .motorcycle-entry select { flex-grow: 1; } .motorcycle-entry input { width: 80px; }</style>

<form action="<?= BASE_PATH ?>/delivery/add" method="POST">
    <div class="form-group">
        <label for="driver_id">Assign Driver:</label>
        <select name="driver_id" id="driver_id" required>
            <option value="">-- Select a Driver --</option>
            <?php foreach ($drivers as $driver): ?>
                <option value="<?= $driver['driver_id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="truck_id">Assign Truck (Active Only):</label>
        <select name="truck_id" id="truck_id" required>
            <option value="">-- Select a Truck --</option>
            <?php foreach ($trucks as $truck): ?>
                <option value="<?= $truck['truck_id'] ?>"><?= htmlspecialchars($truck['plate_number']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="warehouse_id">Origin Warehouse:</label>
        <select name="warehouse_id" id="warehouse_id" required>
            <option value="">-- Select a Warehouse --</option>
            <?php foreach ($warehouses as $warehouse): ?>
                <option value="<?= $warehouse['warehouse_id'] ?>"><?= htmlspecialchars($warehouse['location']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="destination_details">Destination Details:</label>
        <textarea name="destination_details" id="destination_details" rows="3" required></textarea>
    </div>
    
    <div class="form-group">
        <label>Motorcycles to Deliver</label>
        <div id="motorcycle-entries-container">
            <div class="motorcycle-entry">
                <select name="motorcycle_model_id[]" required>
                    <option value="">-- Select Model --</option>
                    <?php foreach ($motorcycle_models_list as $model): ?>
                        <option value="<?= $model['model_id'] ?>"><?= htmlspecialchars(($model['brand'] ? $model['brand'] . ' - ' : '') . $model['model_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="motorcycle_quantity[]" placeholder="Qty" min="1" value="1" required>
                <button type="button" class="btn btn-danger remove-motorcycle-btn">X</button>
            </div>
        </div>
        <button type="button" id="add-motorcycle-btn" class="btn btn-success" style="margin-top: 10px;">+ Add Another Model</button>
    </div>
    
    <div class="form-group">
        <label for="status">Status:</label>
        <select name="status" id="status" required>
            <option value="Pending" selected>Pending</option>
            <option value="Scheduled">Scheduled</option>
        </select>
    </div>
    <div class="form-group">
        <label for="departure_datetime">Departure Time:</label>
        <input type="datetime-local" name="departure_datetime" id="departure_datetime">
    </div>
    <div class="form-group">
        <label for="estimated_arrival">Estimated Arrival:</label>
        <input type="datetime-local" name="estimated_arrival" id="estimated_arrival">
    </div>

    <button type="submit" class="btn btn-primary">Add Delivery</button>
    <a href="<?= BASE_PATH ?>/deliveries" class="btn btn-secondary">Cancel</a>
</form>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>