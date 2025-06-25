// PWA Service Worker Registration
if ('serviceWorker' in navigator && typeof BASE_PATH !== 'undefined') {
  window.addEventListener('load', () => {
    const swPath = `${BASE_PATH}/sw.js`;
    navigator.serviceWorker.register(swPath).then(reg => console.log('SW registration successful.', reg.scope), err => console.error('SW registration failed: ', err));
  });
}

document.addEventListener('DOMContentLoaded', () => {
    // Responsive Sidebar Toggle
    const menuToggle = document.getElementById('menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('content-overlay');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', (e) => { e.stopPropagation(); document.body.classList.toggle('sidebar-is-open'); });
    }
    if (overlay) {
        overlay.addEventListener('click', () => { document.body.classList.remove('sidebar-is-open'); });
    }

    // --- DYNAMIC MOTORCYCLE FORM LOGIC ---
    const container = document.getElementById('motorcycle-entries-container');
    const addButton = document.getElementById('add-motorcycle-btn');
    if (container && addButton) {
        const templateRow = container.querySelector('.motorcycle-entry')?.cloneNode(true);
        if (templateRow) {
            addButton.addEventListener('click', () => {
                const newEntry = templateRow.cloneNode(true);
                newEntry.querySelector('select').selectedIndex = 0;
                newEntry.querySelector('input').value = '1';
                container.appendChild(newEntry);
            });
            container.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-motorcycle-btn')) {
                    if (container.querySelectorAll('.motorcycle-entry').length > 1) {
                        e.target.closest('.motorcycle-entry').remove();
                    } else {
                        alert('At least one motorcycle model must be specified.');
                    }
                }
            });
        }
    }

    // --- Notification System Logic ---
    const notificationBell = document.getElementById('notificationBell');
    const notificationCountSpan = document.getElementById('notificationCount');
    const notificationsDropdown = document.getElementById('notificationsDropdown');
    const notificationListDiv = document.getElementById('notificationList');
    const markAllReadLink = document.getElementById('markAllReadLink');
    if (!notificationBell || typeof BASE_PATH === 'undefined') return;

    function timeAgo(dateString) {
        const date = new Date(dateString.replace(' ', 'T'));
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        let interval = seconds / 31536000; if (interval > 1) return Math.floor(interval) + " years ago";
        interval = seconds / 2592000; if (interval > 1) return Math.floor(interval) + " months ago";
        interval = seconds / 86400; if (interval > 1) return Math.floor(interval) + " days ago";
        interval = seconds / 3600; if (interval > 1) return Math.floor(interval) + " hours ago";
        interval = seconds / 60; if (interval > 1) return Math.floor(interval) + " minutes ago";
        return Math.floor(seconds) < 5 ? "just now" : Math.floor(seconds) + " seconds ago";
    }
    function updateNotificationDisplay(count, notifications) {
        notificationCountSpan.textContent = count;
        notificationCountSpan.style.display = count > 0 ? 'flex' : 'none';
        notificationListDiv.innerHTML = '';
        if (notifications.length > 0) {
            notifications.forEach(n => {
                const item = document.createElement('a'); item.href = n.link_url || '#'; item.classList.add('notification-item');
                if (parseInt(n.is_read, 10) === 0) item.style.fontWeight = 'bold';
                item.dataset.id = n.notification_id;
                item.innerHTML = `<span class="message">${n.message}</span><span class="timestamp">${timeAgo(n.created_at)}</span>`;
                notificationListDiv.appendChild(item);
            });
        } else {
            notificationListDiv.innerHTML = '<div class="no-notifications">No new notifications.</div>';
        }
    }
    function fetchNotifications() {
        fetch(`${BASE_PATH}/api/notifications`).then(res => res.ok ? res.json() : Promise.reject(res))
            .then(data => { if (data.status === 'success') updateNotificationDisplay(data.unread_count, data.notifications); })
            .catch(err => console.error("Error fetching notifications:", err));
    }
    notificationBell.addEventListener('click', (e) => {
        e.stopPropagation();
        notificationsDropdown.classList.toggle('is-visible');
        if (notificationsDropdown.classList.contains('is-visible')) fetchNotifications();
    });
    document.addEventListener('click', (e) => {
        if (notificationsDropdown && !notificationsDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
            notificationsDropdown.classList.remove('is-visible');
        }
    });
    notificationListDiv.addEventListener('click', (e) => {
        const item = e.target.closest('.notification-item');
        if (item && item.dataset.id) {
            e.preventDefault();
            const formData = new FormData(); formData.append('action', 'mark_one_read'); formData.append('notification_id', item.dataset.id);
            fetch(`${BASE_PATH}/api/notifications/mark_read`, { method: 'POST', body: formData })
                .then(() => { if (item.href && item.href !== window.location.href + '#') { window.location.href = item.href; } else { fetchNotifications(); } });
        }
    });
    if(markAllReadLink) {
        markAllReadLink.addEventListener('click', (e) => {
            e.preventDefault();
            const formData = new FormData(); formData.append('action', 'mark_all_read');
            fetch(`${BASE_PATH}/api/notifications/mark_read`, { method: 'POST', body: formData }).then(() => fetchNotifications());
        });
    }
    fetchNotifications();
    setInterval(fetchNotifications, 60000);
});