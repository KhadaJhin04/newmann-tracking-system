<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Report Anomaly' ?> - Newmann</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/driver_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="driver-header">
        <h1>Report Anomaly</h1>
        <a href="<?= BASE_PATH ?>/driver_dashboard">Back</a>
    </header>

    <main class="driver-content">
        <div class="card info-card" style="text-align:left;">
            Reporting anomaly for Delivery #<?= htmlspecialchars($delivery_id) ?>
        </div>
        
        <form id="reportAnomalyForm">
            <input type="hidden" name="delivery_id" value="<?= htmlspecialchars($delivery_id) ?>">
            
            <div class="form-group">
                <label for="anomaly_type">Anomaly Type</label>
                <select name="anomaly_type" id="anomaly_type" required>
                    <option value="">-- Select Type --</option>
                    <option value="Accident">Accident</option>
                    <option value="Road Closure">Road Closure</option>
                    <option value="Weather Event">Weather Event</option>
                    <option value="Road Construction">Road Construction</option>
                    <option value="Checkpoint">Checkpoint</option>
                    <option value="Mechanical Issue">Mechanical Issue</option>
                    <option value="Traffic Jam">Traffic Jam</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="severity">Severity</label>
                <select name="severity" id="severity" required>
                    <option value="">-- Select Severity --</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div class="form-group">
                <label for="location">Location of Anomaly</label>
                <input type="text" name="location" id="location" placeholder="e.g., SLEX Km 45 Southbound" required>
            </div>

            <div class="form-group">
                <label for="description">Description (Optional)</label>
                <textarea name="description" id="description" rows="4" placeholder="Provide more details..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fas fa-paper-plane"></i> Submit Report</button>
        </form>
        <div id="form-message" style="margin-top: 1rem;"></div>
    </main>

    <script>
        document.getElementById('reportAnomalyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const messageDiv = document.getElementById('form-message');
            messageDiv.innerHTML = '';
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            const formData = new FormData(this);
            const payload = Object.fromEntries(formData.entries());

            fetch('<?= BASE_PATH ?>/api/anomaly/report', {
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
                submitButton.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Report';
            });
        });
    </script>
</body>
</html>