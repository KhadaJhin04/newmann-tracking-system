<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
    <div>
        <a href="<?= BASE_PATH ?>/deliveries" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to List</a>
        <?php if (!empty($delivery)): ?>
        <a href="<?= BASE_PATH ?>/delivery/edit?id=<?= $delivery['delivery_id'] ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit</a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($delivery)): ?>
    <div class="alert alert-danger" style="margin-top: 1rem;">Delivery not found or an error occurred.</div>
<?php else: ?>
    <div class="details-main-grid">
        <div class="detail-card" style="grid-column: 1 / -1;">
            <h4><i class="fas fa-info-circle"></i> General Information</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
                <div class="detail-item"><span class="label">Delivery ID:</span> <span class="value"><?= htmlspecialchars($delivery['delivery_id']) ?></span></div>
                <div class="detail-item"><span class="label">Status:</span> <span class="value"><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $delivery['status'])) ?>"><?= htmlspecialchars($delivery['status']) ?></span></span></div>
                <div class="detail-item"><span class="label">Total Items:</span> <span class="value"><?= htmlspecialchars($delivery['motorcycle_count']) ?></span></div>
                <div class="detail-item"><span class="label">Origin:</span> <span class="value"><?= htmlspecialchars($delivery['warehouse_location'] ?? 'N/A') ?></span></div>
            </div>
            <div class="detail-item" style="margin-top:15px;"><span class="label">Destination:</span> <span class="value" style="white-space: pre-wrap;"><?= htmlspecialchars($delivery['destination_details']) ?></span></div>
        </div>

        <div class="details-top-section">
            <div class="detail-card">
                <h4><i class="fas fa-qrcode"></i> Delivery QR Code</h4>
                <div class="qr-code-container" style="padding:0; margin: auto; max-width: 280px; display:flex; align-items:center; flex-grow:1;">
                    <img src="<?= BASE_PATH ?>/delivery/qr?id=<?= $delivery['delivery_id'] ?>" alt="Delivery QR Code" style="width:100%; height:auto;">
                </div>
            </div>
            <div class="detail-card">
                <h4><i class="fas fa-map-marker-alt"></i> Truck Location</h4>
                <div id="deliveryTruckMap"></div>
                <p id="mapStatusMessage" style="text-align:center; font-style:italic; color:#666; margin-top:5px; height: 1em; font-size: 0.9em;"></p>
            </div>
        </div>

        <div class="details-middle-section">
            <div class="detail-card">
                <h4><i class="fas fa-clock"></i> Timeline</h4>
                <div class="detail-item"><span class="label">Departure:</span> <span class="value"><?= htmlspecialchars($delivery['departure_datetime'] ? date('M d, Y, g:i A', strtotime($delivery['departure_datetime'])) : 'N/A') ?></span></div>
                <div class="detail-item"><span class="label">Est. Arrival:</span> <span class="value"><?= htmlspecialchars($delivery['estimated_arrival'] ? date('M d, Y, g:i A', strtotime($delivery['estimated_arrival'])) : 'N/A') ?></span></div>
                <div class="detail-item"><span class="label">Actual Arrival:</span> <span class="value"><?= htmlspecialchars($delivery['actual_arrival'] ? date('M d, Y, g:i A', strtotime($delivery['actual_arrival'])) : 'N/A') ?></span></div>
            </div>
            <div class="detail-card">
                <h4><i class="fas fa-user-tie"></i> Assigned Driver</h4>
                <div class="detail-item"><span class="label">Name:</span> <span class="value"><?= htmlspecialchars($delivery['driver_name'] ?? 'N/A') ?></span></div>
            </div>
            <div class="detail-card">
                <h4><i class="fas fa-truck"></i> Assigned Truck</h4>
                <div class="detail-item"><span class="label">Plate #:</span> <span class="value"><?= htmlspecialchars($delivery['plate_number'] ?? 'N/A') ?></span></div>
            </div>
        </div>
        
        <div class="detail-card" style="grid-column: 1 / -1;">
            <h4><i class="fas fa-motorcycle"></i> Delivered Items</h4>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Brand</th><th>Model</th><th>Quantity</th></tr></thead>
                    <tbody>
                        <?php if(empty($delivery_items)): ?>
                            <tr><td colspan="3" style="text-align:center;">No specific models logged for this delivery.</td></tr>
                        <?php else: ?>
                            <?php foreach($delivery_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['brand'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($item['model_name']) ?></td>
                                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="detail-card" style="grid-column: 1 / -1;">
            <h4><i class="fas fa-exclamation-triangle"></i> Associated Road Anomalies</h4>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>ID</th><th>Type</th><th>Severity</th><th>Location</th><th>Description</th><th>Reported At</th></tr></thead>
                    <tbody>
                         <?php if(empty($delivery_anomalies)): ?>
                            <tr><td colspan="6" style="text-align:center;">No anomalies reported for this delivery.</td></tr>
                        <?php else: ?>
                            <?php foreach($delivery_anomalies as $anomaly): ?>
                                <tr>
                                    <td><?= htmlspecialchars($anomaly['anomaly_id']) ?></td>
                                    <td><?= htmlspecialchars($anomaly['type']) ?></td>
                                    <td><?= htmlspecialchars($anomaly['severity']) ?></td>
                                    <td><?= htmlspecialchars($anomaly['location']) ?></td>
                                    <td><?= htmlspecialchars($anomaly['description'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars(date('M d, Y, g:i A', strtotime($anomaly['reported_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deliveryTruckMapElement = document.getElementById('deliveryTruckMap');
    const mapStatusMessage = document.getElementById('mapStatusMessage');
    let deliveryMap;
    let truckMarker = null;

    <?php 
        $truck_id_for_map = $delivery['truck_id'] ?? null;
        $truck_plate_for_map = $delivery['plate_number'] ?? 'N/A';
    ?>

    const truckIdForThisDelivery = <?= json_encode($truck_id_for_map); ?>;
    
    if (deliveryTruckMapElement && truckIdForThisDelivery) {
        deliveryMap = L.map('deliveryTruckMap').setView([14.4479, 120.9932], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(deliveryMap);
        
        setTimeout(() => { if (deliveryMap) deliveryMap.invalidateSize() }, 100);

        function fetchSpecificTruckLocation() {
            fetch('<?= BASE_PATH ?>/api/truck_locations')
                .then(response => response.ok ? response.json() : Promise.reject('Network response was not ok.'))
                .then(result => {
                    if (result.status !== 'success') throw new Error(result.message || 'API Error');
                    
                    const truck = result.data.find(t => t.truck_id == truckIdForThisDelivery);
                    
                    if (truck && truck.latitude && truck.longitude) {
                        const latLng = [parseFloat(truck.latitude), parseFloat(truck.longitude)];
                        const popupContent = `<b>Plate:</b> <?= htmlspecialchars($truck_plate_for_map) ?><br><b>Status:</b> ${truck.delivery_status}`;
                        
                        if (truckMarker) {
                            truckMarker.setLatLng(latLng).setPopupContent(popupContent);
                        } else {
                            truckMarker = L.marker(latLng).addTo(deliveryMap).bindPopup(popupContent);
                        }
                        deliveryMap.setView(latLng, 15);
                        mapStatusMessage.textContent = `Location updated: ${new Date().toLocaleTimeString()}`;
                    } else {
                        mapStatusMessage.textContent = 'No recent GPS data available for this truck.';
                    }
                })
                .catch(error => {
                    console.error('Error fetching truck location:', error);
                    mapStatusMessage.textContent = 'Could not fetch location data.';
                });
        }
        
        fetchSpecificTruckLocation();
        setInterval(fetchSpecificTruckLocation, 30000);
    } else if (deliveryTruckMapElement) {
        mapStatusMessage.textContent = 'No truck assigned to this delivery to display location.';
    }
});
</script>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>