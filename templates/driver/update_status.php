<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Update Status' ?> - Newmann</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/driver_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="driver-header">
        <h1>Update Status</h1>
        <a href="<?= BASE_PATH ?>/driver_dashboard">Back</a>
    </header>

    <main class="driver-content">
        <?php if(isset($page_error)): ?>
            <div class="card error-card"><?= htmlspecialchars($page_error) ?></div>
        <?php elseif($delivery): ?>
            <div class="card delivery-card">
                <div class="card-header">
                    <h3>Delivery #<?= htmlspecialchars($delivery['delivery_id']) ?></h3>
                </div>
                <div class="card-body">
                    <p><strong>Destination:</strong> <?= htmlspecialchars($delivery['destination_details']) ?></p>
                    <p><strong>Current Status:</strong> <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $delivery['status'])) ?>"><?= htmlspecialchars($delivery['status']) ?></span></p>
                </div>
            </div>
            
            <form id="updateStatusForm">
                <input type="hidden" name="delivery_id" value="<?= $delivery['delivery_id'] ?>">
                
                <div class="form-group">
                    <label for="new_status">New Status</label>
                    <select name="new_status" id="new_status" required>
                        <option value="">-- Select --</option>
                        <option value="In Transit">In Transit</option>
                        <option value="Delayed">Delayed</option>
                        <option value="Delivered">Delivered</option>
                    </select>
                </div>

                <div id="delay-options" style="display: none;">
                    <div class="form-group">
                        <label for="delay_reason_id">Reason for Delay</label>
                        <select name="delay_reason_id" id="delay_reason_id">
                            <option value="">-- Select a predefined reason --</option>
                            <?php foreach($delay_reasons as $reason): ?>
                                <option value="<?= $reason['reason_id'] ?>"><?= htmlspecialchars($reason['reason_description']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($delivery_anomalies)): ?>
                    <div class="form-group">
                        <label for="linked_anomaly_id">Link to a Reported Anomaly (Optional)</label>
                        <select name="linked_anomaly_id" id="linked_anomaly_id">
                            <option value="">-- Select a reported anomaly --</option>
                            <?php foreach($delivery_anomalies as $anomaly): ?>
                                <option value="<?= $anomaly['anomaly_id'] ?>"><?= htmlspecialchars($anomaly['type'] . ' at ' . $anomaly['location']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="notes">Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3" placeholder="e.g., Recipient name, reason for delay..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-save"></i> Submit Update</button>
            </form>
            <div id="form-message" style="margin-top: 1rem;"></div>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusSelect = document.getElementById('new_status');
            const delayOptions = document.getElementById('delay-options');
            const updateForm = document.getElementById('updateStatusForm');
            const messageDiv = document.getElementById('form-message');

            statusSelect.addEventListener('change', () => {
                delayOptions.style.display = (statusSelect.value === 'Delayed') ? 'block' : 'none';
            });

            updateForm.addEventListener('submit', (e) => {
                e.preventDefault();
                messageDiv.innerHTML = '';
                const submitButton = updateForm.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

                const formData = new FormData(updateForm);
                const payload = Object.fromEntries(formData.entries());

                const sendRequest = (payload) => {
                    fetch('<?= BASE_PATH ?>/api/delivery/update_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            messageDiv.innerHTML = `<div class="card info-card" style="border-left-color: #2ecc71;">${data.message}</div>`;
                            setTimeout(() => { window.location.href = '<?= BASE_PATH ?>/driver_dashboard'; }, 2000);
                        } else {
                            throw new Error(data.message || 'An unknown error occurred.');
                        }
                    })
                    .catch(error => {
                        messageDiv.innerHTML = `<div class="card error-card">${error.message}</div>`;
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-save"></i> Submit Update';
                    });
                };
                
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            payload.latitude = position.coords.latitude;
                            payload.longitude = position.coords.longitude;
                            sendRequest(payload);
                        },
                        (error) => {
                            console.warn(`Geolocation error: ${error.message}`);
                            sendRequest(payload);
                        },
                        { timeout: 10000, enableHighAccuracy: true }
                    );
                } else {
                    sendRequest(payload);
                }
            });
        });
    </script>
</body>
</html>