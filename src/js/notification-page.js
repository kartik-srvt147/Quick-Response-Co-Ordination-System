document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
    if (!user || !user.isLoggedIn) {
        window.location.href = 'login.html';
        return;
    }

    const notificationsList = document.getElementById('notificationsList');
    const emptyState = document.getElementById('emptyState');
    const filterSelect = document.getElementById('filterNotifications');
    const markAllReadBtn = document.getElementById('markAllReadBtn');

    // Initialize notification system
    const notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
    
    function displayNotifications(filter = 'all') {
        const filteredNotifications = filterNotifications(notifications, filter);
        
        if (filteredNotifications.length === 0) {
            notificationsList.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        notificationsList.innerHTML = filteredNotifications.map(notification => `
            <div class="bg-gray-900 rounded-lg border border-gray-800 p-4 ${notification.read ? 'opacity-75' : ''}"
                data-id="${notification.id}">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <i class="fas fa-${getNotificationIcon(notification.type)} text-red-500 text-xl"></i>
                            ${!notification.read ? '<span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>' : ''}
                        </div>
                        <div>
                            <div class="flex items-center space-x-2">
                                <h3 class="text-white font-medium">${notification.type}</h3>
                                <span class="text-xs px-2 py-1 rounded-full ${getPriorityColor(notification.priority)}">
                                    ${notification.priority}
                                </span>
                            </div>
                            <p class="text-gray-300 text-sm">${notification.message}</p>
                            <span class="text-gray-500 text-xs">${formatTime(notification.timestamp)}</span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="viewEmergencyDetails('${notification.emergencyId}')" 
                            class="text-blue-400 hover:text-blue-300">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="deleteNotification('${notification.id}')" 
                            class="text-red-400 hover:text-red-300">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function filterNotifications(notifications, filter) {
        switch (filter) {
            case 'unread':
                return notifications.filter(n => !n.read);
            case 'high':
                return notifications.filter(n => n.priority === 'high');
            case 'medium':
                return notifications.filter(n => n.priority === 'medium');
            case 'low':
                return notifications.filter(n => n.priority === 'low');
            default:
                return notifications;
        }
    }

    function getNotificationIcon(type) {
        const icons = {
            medical: 'ambulance',
            fire: 'fire',
            police: 'shield-alt',
            accident: 'car-crash',
            disaster: 'house-damage'
        };
        return icons[type] || 'exclamation-circle';
    }

    function getPriorityColor(priority) {
        const colors = {
            high: 'bg-red-500 bg-opacity-20 text-red-500',
            medium: 'bg-yellow-500 bg-opacity-20 text-yellow-500',
            low: 'bg-green-500 bg-opacity-20 text-green-500'
        };
        return colors[priority] || 'bg-gray-500 bg-opacity-20 text-gray-500';
    }

    function formatTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString();
    }

    // Event Listeners
    filterSelect.addEventListener('change', (e) => {
        displayNotifications(e.target.value);
    });

    markAllReadBtn.addEventListener('click', () => {
        notifications.forEach(notification => notification.read = true);
        localStorage.setItem('notifications', JSON.stringify(notifications));
        displayNotifications(filterSelect.value);
        updateNotificationBadge();
    });

    // Initialize display
    displayNotifications();

    // Expose functions to window for onclick handlers
    window.viewEmergencyDetails = function(emergencyId) {
        window.location.href = `dashboard.html#emergency=${emergencyId}`;
    };

    window.deleteNotification = function(notificationId) {
        const index = notifications.findIndex(n => n.id === parseInt(notificationId));
        if (index !== -1) {
            notifications.splice(index, 1);
            localStorage.setItem('notifications', JSON.stringify(notifications));
            displayNotifications(filterSelect.value);
            updateNotificationBadge();
        }
    };

    function updateNotificationBadge() {
        const unreadCount = notifications.filter(n => !n.read).length;
        const badge = document.getElementById('notificationCount');
        if (badge) {
            badge.textContent = unreadCount;
            badge.classList.toggle('hidden', unreadCount === 0);
        }
    }
}); 