<?php require ROOT_PATH . '/templates/layout/header.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="filter-form-container">
    <form action="<?= BASE_PATH ?>/reports" method="GET" class="filter-form">
        <div class="filter-group">
            <label for="start_date">Start Date:</label>
            <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="filter-group">
            <label for="end_date">End Date:</label>
            <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filter</button>
            <a href="<?= BASE_PATH ?>/reports" class="btn btn-secondary"><i class="fas fa-times-circle"></i> Clear</a>
        </div>
    </form>
</div>


<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger" style="margin-top: 1rem;">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<div class="card-container">
    <div class="card deliveries">
        <h3>On-Time Deliveries</h3>
        <p class="count"><?= htmlspecialchars($reports_data['on_time_count'] ?? 0) ?></p>
    </div>
    <div class="card drivers">
        <h3>Delayed Deliveries</h3>
        <p class="count"><?= htmlspecialchars($reports_data['delayed_count'] ?? 0) ?></p>
    </div>
</div>

<div class="report-section" style="margin-top: 2rem;">
    <h3><i class="fas fa-motorcycle"></i> Most Delivered Motorcycle Models</h3>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Brand</th>
                    <th>Model Name</th>
                    <th>Total Units Delivered</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($reports_data['model_report'])): ?>
                    <tr><td colspan="3" style="text-align:center;">No model data available for the selected period.</td></tr>
                <?php else: ?>
                    <?php foreach($reports_data['model_report'] as $model): ?>
                    <tr>
                        <td><?= htmlspecialchars($model['brand'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($model['model_name']) ?></td>
                        <td><?= htmlspecialchars($model['total_delivered']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="report-section" style="margin-top: 2rem;">
    <h3><i class="fas fa-lightbulb"></i> Operational Insights & Recommendations</h3>
    <ul class="insights-list">
        <?php foreach ($prescriptive_insights as $insight): ?>
            <li><?= $insight ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<div style="display: flex; flex-wrap: wrap; gap: 2rem; margin-top: 2rem;">
    <div style="flex: 1; min-width: 300px;">
        <h3>Delivery Status Summary</h3>
        <canvas id="deliveryStatusChart"></canvas>
    </div>
    <div style="flex: 1; min-width: 400px;">
        <h3>Road Anomaly Frequency</h3>
        <canvas id="anomalyFrequencyChart"></canvas>
    </div>
</div>

<div class="dashboard-columns">
    <div class="dashboard-column">
        <h3><i class="fas fa-truck-moving"></i> Truck Utilization</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Plate Number</th><th>Status</th><th>Deliveries Assigned</th></tr></thead>
                <tbody>
                    <?php foreach($reports_data['truck_utilization'] ?? [] as $truck): ?>
                        <tr>
                            <td><?= htmlspecialchars($truck['plate_number']) ?></td>
                            <td><?= htmlspecialchars($truck['truck_status']) ?></td>
                            <td><?= htmlspecialchars($truck['delivery_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="dashboard-column">
        <h3><i class="fas fa-id-card"></i> Driver Activity</h3>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Driver Name</th><th>Deliveries Handled</th></tr></thead>
                <tbody>
                    <?php foreach($reports_data['driver_activity'] ?? [] as $driver): ?>
                        <tr>
                            <td><?= htmlspecialchars($driver['name']) ?></td>
                            <td><?= htmlspecialchars($driver['delivery_count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Charting data
    const deliverySummary = <?= json_encode($reports_data['delivery_summary'] ?? []) ?>;
    const anomalyFrequency = <?= json_encode($reports_data['anomaly_frequency'] ?? []) ?>;
    
    // Delivery Status Chart (Bar)
    if (Object.keys(deliverySummary).length > 0) {
        new Chart(document.getElementById('deliveryStatusChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: Object.keys(deliverySummary),
                datasets: [{
                    label: 'Total Deliveries',
                    data: Object.values(deliverySummary),
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(220, 53, 69, 0.7)',
                        'rgba(0, 123, 255, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(108, 117, 125, 0.7)'
                    ],
                    borderColor: 'rgba(0,0,0,0.1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // Anomaly Frequency Chart (Bar)
    if (anomalyFrequency.length > 0) {
        new Chart(document.getElementById('anomalyFrequencyChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: anomalyFrequency.map(item => item.type),
                datasets: [{
                    label: 'Frequency',
                    data: anomalyFrequency.map(item => item.count),
                    backgroundColor: '#e74c3c',
                }]
            },
            options: { 
                indexAxis: 'y', 
                scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>

<?php require ROOT_PATH . '/templates/layout/footer.php'; ?>