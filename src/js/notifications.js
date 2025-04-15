class NotificationSystem {
    constructor() {
        this.notificationCount = 0;
        this.notifications = [];
        this.notificationSound = new Audio('assets/alert.mp3');
        this.setupNotificationPermission();
        this.initializeNotifications();
    }

    async setupNotificationPermission() {
        if ('Notification' in window) {
            if (Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                await Notification.requestPermission();
            }
        }
    }

    initializeNotifications() {
        // Load existing notifications from localStorage
        this.notifications = JSON.parse(localStorage.getItem('notifications') || '[]');
        this.updateNotificationBadge();
        this.setupEventListeners();
        this.startEmergencyListener();
    }

    setupEventListeners() {
        const notificationBtn = document.querySelector('#notificationContainer');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', () => this.toggleNotificationPanel());
        }
    }

    startEmergencyListener() {
        // Check for new emergencies every 10 seconds
        setInterval(() => {
            const emergencies = JSON.parse(localStorage.getItem('emergencies') || '[]');
            const newEmergencies = emergencies.filter(emergency => 
                !emergency.notified && emergency.status === 'pending'
            );

            newEmergencies.forEach(emergency => {
                this.createNotification(emergency);
                emergency.notified = true;
            });
            localStorage.setItem('emergencies', JSON.stringify(emergencies));
        }, 10000);
    }

    createNotification(emergency) {
        const notification = {
            id: Date.now(),
            type: emergency.emergencyType,
            message: `New ${emergency.emergencyType} emergency reported at ${emergency.location.address}`,
            timestamp: new Date().toISOString(),
            priority: emergency.severity || 'medium',
            read: false,
            emergencyId: emergency.id
        };

        this.notifications.unshift(notification);
        localStorage.setItem('notifications', JSON.stringify(this.notifications));
        
        this.showNotification(notification);
        this.updateNotificationBadge();
        this.updateNotificationPanel();
    }

    showNotification(notification) {
        // Browser notification
        if (Notification.permission === 'granted') {
            const browserNotification = new Notification('QRCS Emergency Alert', {
                body: notification.message,
                icon: '/assets/emergency-icon.png',
                tag: notification.id
            });

            browserNotification.onclick = () => {
                window.focus();
                this.showEmergencyDetails(notification.emergencyId);
            };
        }

        // Play sound for high priority
        if (notification.priority === 'high') {
            this.notificationSound.play();
        }

        // Show in-app notification toast
        this.showToast(notification);
    }

    showToast(notification) {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 bg-gray-900 border border-gray-800 p-4 rounded-lg shadow-lg z-50 
            ${notification.priority === 'high' ? 'animate-bounce' : ''} transition-all duration-300`;
        
        toast.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-500 text-xl mr-2"></i>
                <div>
                    <h4 class="text-white font-semibold">${notification.type} Alert</h4>
                    <p class="text-gray-300 text-sm">${notification.message}</p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    toggleNotificationPanel() {
        const panel = document.getElementById('notificationPanel');
        if (!panel) {
            this.createNotificationPanel();
        } else {
            panel.classList.toggle('hidden');
        }
    }

    createNotificationPanel() {
        const panel = document.createElement('div');
        panel.id = 'notificationPanel';
        panel.className = 'fixed right-4 top-20 w-96 bg-gray-900 border border-gray-800 rounded-lg shadow-xl z-50';
        
        this.updateNotificationPanel(panel);
        document.body.appendChild(panel);
    }

    updateNotificationPanel(panel = document.getElementById('notificationPanel')) {
        if (!panel) return;

        panel.innerHTML = `
            <div class="p-4 border-b border-gray-800">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-white">Notifications</h3>
                    <button onclick="notificationSystem.markAllAsRead()" 
                        class="text-sm text-gray-400 hover:text-white">
                        Mark all as read
                    </button>
                </div>
            </div>
            <div class="max-h-96 overflow-y-auto">
                ${this.notifications.length ? this.notifications.map(notification => `
                    <div class="p-4 border-b border-gray-800 ${notification.read ? 'bg-gray-900' : 'bg-gray-800'} 
                        hover:bg-gray-700 transition-colors cursor-pointer"
                        onclick="notificationSystem.handleNotificationClick('${notification.id}')">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-${this.getNotificationIcon(notification.type)} text-red-500 mr-2"></i>
                                <div>
                                    <h4 class="text-white font-medium">${notification.type}</h4>
                                    <p class="text-sm text-gray-400">${notification.message}</p>
                                    <span class="text-xs text-gray-500">${this.formatTime(notification.timestamp)}</span>
                                </div>
                            </div>
                            ${!notification.read ? '<span class="w-2 h-2 bg-red-500 rounded-full"></span>' : ''}
                        </div>
                    </div>
                `).join('') : `
                    <div class="p-4 text-center text-gray-400">
                        No notifications
                    </div>
                `}
            </div>
        `;
    }

    handleNotificationClick(notificationId) {
        const notification = this.notifications.find(n => n.id === parseInt(notificationId));
        if (notification) {
            notification.read = true;
            localStorage.setItem('notifications', JSON.stringify(this.notifications));
            this.updateNotificationBadge();
            this.updateNotificationPanel();
            this.showEmergencyDetails(notification.emergencyId);
        }
    }

    markAllAsRead() {
        this.notifications.forEach(notification => notification.read = true);
        localStorage.setItem('notifications', JSON.stringify(this.notifications));
        this.updateNotificationBadge();
        this.updateNotificationPanel();
    }

    updateNotificationBadge() {
        const unreadCount = this.notifications.filter(n => !n.read).length;
        const badge = document.getElementById('notificationCount');
        if (badge) {
            badge.textContent = unreadCount;
            badge.classList.toggle('hidden', unreadCount === 0);
        }
    }

    showEmergencyDetails(emergencyId) {
        // Implement navigation to emergency details page or modal
        if (window.location.pathname.includes('dashboard.html')) {
            // If on dashboard, trigger emergency details view
            window.viewEmergency(emergencyId);
        } else {
            // Navigate to dashboard with emergency ID
            window.location.href = `dashboard.html#emergency=${emergencyId}`;
        }
    }

    getNotificationIcon(type) {
        const icons = {
            medical: 'ambulance',
            fire: 'fire',
            police: 'shield-alt',
            accident: 'car-crash',
            disaster: 'house-damage'
        };
        return icons[type] || 'exclamation-circle';
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
        return date.toLocaleDateString();
    }
}

// Initialize notification system
const notificationSystem = new NotificationSystem();
window.notificationSystem = notificationSystem; // Make it globally accessible

// Ensure the notification badge is updated after the page loads completely
window.addEventListener('load', function() {
    setTimeout(function() {
        notificationSystem.updateNotificationBadge();
    }, 500);
}); 