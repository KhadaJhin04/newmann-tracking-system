<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<style>
    #truckMap {
        height: 500px;
        width: 100%;
        border: 1px solid #ccc;
        border-radius: 8px;
        margin-top: 1rem;
    }
</style>

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

<div class="card-container">
    <div class="card deliveries">
        <h3>In Transit Deliveries</h3>
        <p class="count"><?= htmlspecialchars($stats['in_transit'] ?? 0) ?></p>
    </div>
    <div class="card">
        <h3>Pending Deliveries</h3>
        <p class="count"><?= htmlspecialchars($stats['pending'] ?? 0) ?></p>
    </div>
    <div class="card trucks">
        <h3>Total Trucks</h3>
        <p class="count"><?= htmlspecialchars($stats['total_trucks'] ?? 0) ?></p>
    </div>
    <div class="card drivers">
        <h3>Total Drivers</h3>
        <p class="count"><?= htmlspecialchars($stats['total_drivers'] ?? 0) ?></p>
    </div>
</div>

<div style="margin-top: 3rem;">
    <div id="truckMap"></div>
</div>

<div class="dashboard-columns">
    <div class="dashboard-column">
        <h2>Recent Deliveries</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Driver</th>
                        <th>Plate #</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_deliveries)): ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">No recent deliveries found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_deliveries as $delivery): ?>
                            <tr>
                                <td><?= htmlspecialchars($delivery['delivery_id']) ?></td>
                                <td><?= htmlspecialchars($delivery['driver_name'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($delivery['plate_number'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($delivery['status']) ?></td>
                                <td class="actions">
                                    <a href="<?= BASE_PATH ?>/delivery/details?id=<?= $delivery['delivery_id'] ?>" class="btn btn-secondary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dashboard-column">
        <h2>Recent Road Anomalies</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Delivery ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_anomalies)): ?>
                        <tr>
                            <td colspan="3" style="text-align:center;">No recent anomalies reported.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recent_anomalies as $anomaly): ?>
                            <tr>
                                <td><?= htmlspecialchars($anomaly['type']) ?></td>
                                <td><?= htmlspecialchars($anomaly['location']) ?></td>
                                <td>
                                    <?php if ($anomaly['delivery_id']): ?>
                                        <a href="<?= BASE_PATH ?>/delivery/details?id=<?= $anomaly['delivery_id'] ?>"><?= htmlspecialchars($anomaly['delivery_id']) ?></a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- MAP INITIALIZATION ---
    const map = L.map('truckMap').setView([14.4479, 120.9932], 9); // Centered on Las Piñas/Metro Manila area
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    let truckMarkersLayer = L.layerGroup().addTo(map);
    let firstLoad = true;

    function fetchAndUpdateMap() {
        fetch('<?= BASE_PATH ?>/api/truck_locations')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.status !== 'success') {
                console.error("Failed to fetch truck locations:", data.message);
                return;
            }
            
            truckMarkersLayer.clearLayers(); 
            
            const bounds = [];
            data.data.forEach(truck => {
                if (truck.latitude && truck.longitude) {
                    const latLng = [parseFloat(truck.latitude), parseFloat(truck.longitude)];
                    const popupContent = `
                        <b>Plate:</b> ${truck.plate_number}<br>
                        <b>Status:</b> ${truck.delivery_status}<br>
                        <b>Last Update:</b> ${new Date(truck.log_timestamp.replace(' ', 'T')).toLocaleTimeString()}
                    `;
                    L.marker(latLng).addTo(truckMarkersLayer)
                        .bindPopup(popupContent);
                    bounds.push(latLng);
                }
            });

            // On first load with data, fit map to markers
            if (firstLoad && bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                firstLoad = false;
            }
        })
        .catch(error => console.error('Error fetching truck locations:', error));
    }
    
    // Fetch locations immediately and then every 30 seconds
    fetchAndUpdateMap();
    setInterval(fetchAndUpdateMap, 30000);
});
</script>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>