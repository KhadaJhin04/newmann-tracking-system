<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Dashboard - Newmann Tracking</title>
    <meta name="theme-color" content="#333333"/>
    <link rel="manifest" href="<?= BASE_PATH ?>/manifest.json">
    <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/driver_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <header class="driver-header">
        <h1>My Dashboard</h1>
        <div class="user-info">
            <span>Hi, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
            <a href="<?= BASE_PATH ?>/logout">Logout</a>
        </div>
    </header>

    <main class="driver-content">
        <div class="actions-bar">
            <a href="<?= BASE_PATH ?>/driver/scan" class="btn btn-primary"><i class="fas fa-qrcode"></i> Scan Delivery QR</a>
            <button id="refresh-button" class="btn btn-secondary"><i class="fas fa-sync-alt"></i> Refresh</button>
        </div>

        <div class="card info-card" id="gps-status-card" style="display: none;">
            </div>

        <div id="deliveries-list">
            </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listContainer = document.getElementById('deliveries-list');
            const refreshButton = document.getElementById('refresh-button');
            const gpsStatusCard = document.getElementById('gps-status-card');
            let gpsWatchId = null;
            let currentlyTrackedTruckId = null;

            function updateGpsStatus(message, isError = false) {
                if (!gpsStatusCard) return;
                gpsStatusCard.style.display = 'block';
                gpsStatusCard.textContent = message;
                gpsStatusCard.className = isError ? 'card error-card' : 'card info-card';
            }

            function stopGpsTracking() {
                if (gpsWatchId !== null) {
                    navigator.geolocation.clearWatch(gpsWatchId);
                    gpsWatchId = null;
                    currentlyTrackedTruckId = null;
                    console.log("Stopped GPS tracking because no deliveries are 'In Transit'.");
                    updateGpsStatus("GPS Tracking is now inactive.", false);
                }
            }

            function startGpsTracking(truckId) {
                if (gpsWatchId !== null) {
                    if (currentlyTrackedTruckId === truckId) {
                        console.log("GPS tracking is already active for this truck.");
                        return; // Already tracking the correct truck
                    } else {
                        // Switching to a new truck if a different one is now in transit
                        stopGpsTracking(); 
                    }
                }

                if (!navigator.geolocation) {
                    updateGpsStatus('Geolocation is not supported by your browser.', true);
                    return;
                }
                if (!truckId) {
                    console.error("Cannot start GPS tracking without a truck ID.");
                    return;
                }
                
                currentlyTrackedTruckId = truckId;
                updateGpsStatus('GPS Tracking is now active...', false);

                gpsWatchId = navigator.geolocation.watchPosition(
                    (position) => {
                        const payload = {
                            truck_id: currentlyTrackedTruckId,
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude
                        };
                        fetch('<?= BASE_PATH ?>/api/gps/log', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(payload)
                        }).then(response => response.json()).then(data => {
                            if(data.status === 'success') {
                                updateGpsStatus(`GPS Active. Last update: ${new Date().toLocaleTimeString()}`);
                            } else { throw new Error(data.message); }
                        }).catch(error => {
                            updateGpsStatus(`Could not send location: ${error.message}`, true);
                        });
                    },
                    (error) => {
                        updateGpsStatus(`GPS Error: ${error.message}. Please ensure location services are enabled.`, true);
                        stopGpsTracking();
                    },
                    { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 }
                );
            }
            
            function renderDeliveries(deliveries) {
                if (!listContainer) return;
                if (deliveries.length === 0) {
                    listContainer.innerHTML = '<div class="card info-card"><p>You have no active deliveries assigned.</p></div>';
                    return;
                }

                let html = '';
                deliveries.forEach(delivery => {
                    const statusClass = delivery.status.toLowerCase().replace(' ', '-');
                    html += `
                        <div class="card delivery-card status-${statusClass}">
                            <div class="card-header">
                                <h3>Delivery #${delivery.delivery_id}</h3>
                                <span class="status-badge">${delivery.status}</span>
                            </div>
                            <div class="card-body">
                                <p><strong>Truck ID:</strong> ${delivery.truck_id || 'N/A'}</p>
                                <p><strong>Destination:</strong> ${delivery.destination_details}</p>
                                <p><strong>Departure:</strong> ${delivery.departure_datetime_formatted || 'N/A'}</p>
                                <p><strong>ETA:</strong> ${delivery.estimated_arrival_formatted || 'N/A'}</p>
                            </div>
                            <div class="card-actions">
                                <a href="<?= BASE_PATH ?>/driver/update_status?id=${delivery.delivery_id}" class="btn btn-success"><i class="fas fa-check"></i> Update Status</a>
                                <a href="<?= BASE_PATH ?>/driver/report_anomaly?id=${delivery.delivery_id}" class="btn btn-warning"><i class="fas fa-exclamation-triangle"></i> Report Anomaly</a>
                            </div>
                        </div>`;
                });
                listContainer.innerHTML = html;
            }

            function fetchDeliveries() {
                if (!listContainer) return;
                listContainer.innerHTML = '<div class="card info-card"><p><i class="fas fa-spinner fa-spin"></i> Loading deliveries...</p></div>';
                
                fetch('<?= BASE_PATH ?>/api/driver/deliveries')
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok.');
                        return response.json();
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            const inTransitDelivery = data.deliveries.find(d => d.status === 'In Transit');
                            if (inTransitDelivery) {
                                startGpsTracking(inTransitDelivery.truck_id);
                            } else {
                                stopGpsTracking();
                            }
                            renderDeliveries(data.deliveries);
                        } else {
                            throw new Error(data.message || 'Failed to load deliveries.');
                        }
                    })
                    .catch(error => {
                        listContainer.innerHTML = `<div class="card error-card"><p>${error.message}</p></div>`;
                    });
            }

            if (refreshButton) {
                refreshButton.addEventListener('click', fetchDeliveries);
            }

            // Load deliveries on initial page load
            fetchDeliveries();
        });
    </script>
</body>
</html>