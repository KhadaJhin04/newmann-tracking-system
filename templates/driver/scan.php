<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Scan QR' ?> - Newmann</title>
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/driver_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>
<body>
    <header class="driver-header">
        <h1>Scan Delivery QR</h1>
        <a href="<?= BASE_PATH ?>/driver_dashboard">Dashboard</a>
    </header>

    <main class="driver-content">
        <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
        <div id="scan-result" class="card info-card" style="display: none; margin-top: 1rem;"></div>

        <div class="actions-bar" style="margin-top: 1rem;">
            <button id="start-scan-btn" class="btn btn-success"><i class="fas fa-camera"></i> Start Camera</button>
            <button id="stop-scan-btn" class="btn btn-danger" style="display:none;"><i class="fas fa-stop"></i> Stop Camera</button>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const resultContainer = document.getElementById('scan-result');
            const startBtn = document.getElementById('start-scan-btn');
            const stopBtn = document.getElementById('stop-scan-btn');
            
            const html5QrCode = new Html5Qrcode("reader");

            const onScanSuccess = (decodedText, decodedResult) => {
                // Stop scanning once a valid code is found.
                html5QrCode.stop().then(() => {
                    console.log("QR Code scanning stopped.");
                    startBtn.style.display = 'block';
                    stopBtn.style.display = 'none';
                }).catch(err => console.error("Error stopping scanner:", err));

                if (decodedText.startsWith("NEWMANN_DELIVERY_ID::")) {
                    const parts = decodedText.split("::");
                    const deliveryId = parseInt(parts[1], 10);
                    
                    if (!isNaN(deliveryId)) {
                        resultContainer.className = 'card info-card';
                        resultContainer.textContent = `Valid QR found for Delivery #${deliveryId}. Updating status to 'In Transit'...`;
                        resultContainer.style.display = 'block';
                        
                        // Automatically update the status
                        updateDeliveryStatus(deliveryId);
                    } else {
                        showError("Invalid Delivery ID in QR code.");
                    }
                } else {
                    showError("This is not a valid Newmann Delivery QR code.");
                }
            };
            
            const onScanFailure = (error) => {
                // This callback is called frequently, so we typically ignore it.
                // console.warn(`QR error = ${error}`);
            };

            function showError(message) {
                resultContainer.className = 'card error-card';
                resultContainer.textContent = message;
                resultContainer.style.display = 'block';
            }

            function updateDeliveryStatus(deliveryId) {
                const payload = {
                    delivery_id: deliveryId,
                    new_status: 'In Transit',
                    notes: 'Trip started via QR scan.'
                };

                const sendRequest = (payload) => {
                    fetch('<?= BASE_PATH ?>/api/delivery/update_status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            resultContainer.className = 'card info-card';
                            resultContainer.innerHTML = `Status for Delivery #${deliveryId} updated successfully! Redirecting...`;
                            setTimeout(() => { window.location.href = '<?= BASE_PATH ?>/driver_dashboard'; }, 2500);
                        } else {
                            throw new Error(data.message || 'An unknown API error occurred.');
                        }
                    })
                    .catch(error => showError(`Error: ${error.message}`));
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
                            sendRequest(payload); // Send anyway without GPS
                        }
                    );
                } else {
                    sendRequest(payload); // Geolocation not supported
                }
            }

            startBtn.addEventListener('click', () => {
                resultContainer.style.display = 'none';
                startBtn.style.display = 'none';
                stopBtn.style.display = 'block';
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    onScanSuccess,
                    onScanFailure
                ).catch(err => {
                    showError("Could not start camera. Please grant permission and try again.");
                    startBtn.style.display = 'block';
                    stopBtn.style.display = 'none';
                });
            });

            stopBtn.addEventListener('click', () => {
                html5QrCode.stop().then(() => {
                    console.log("QR Code scanning stopped.");
                    startBtn.style.display = 'block';
                    stopBtn.style.display = 'none';
                }).catch(err => console.error("Error stopping scanner:", err));
            });
        });
    </script>
</body>
</html>