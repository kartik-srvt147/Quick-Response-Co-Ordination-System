document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
    if (!user || !user.isLoggedIn) {
        // Redirect to login page if not logged in
        window.location.href = 'login.html';
        return;
    }

    // Update user menu
    const userMenuBtn = document.getElementById('userMenuBtn');
    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', function() {
            // Handle logout
            if (confirm('Are you sure you want to logout?')) {
                localStorage.removeItem('user');
                sessionStorage.removeItem('user');
                window.location.href = 'index.html';
            }
        });
    }

    // Initialize map
    const map = L.map('dashboardMap').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Get user's location and center map
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                map.setView([latitude, longitude], 13);
            },
            (error) => {
                console.error('Error getting location:', error);
            }
        );
    }

    // Initialize statistics
    let statistics = {
        active: 0,
        resolved: 0,
        pending: 0,
        availableUnits: 10
    };

    // Update statistics display
    function updateStatistics() {
        document.getElementById('activeEmergencies').textContent = statistics.active;
        document.getElementById('resolvedCases').textContent = statistics.resolved;
        document.getElementById('pendingCases').textContent = statistics.pending;
        document.getElementById('availableUnits').textContent = statistics.availableUnits;
    }

    // Load and display emergencies
    function loadEmergencies() {
        const emergencies = JSON.parse(localStorage.getItem('emergencies') || '[]');
        const emergencyList = document.getElementById('emergencyList');
        emergencyList.innerHTML = '';

        // Reset statistics
        statistics.active = 0;
        statistics.resolved = 0;
        statistics.pending = 0;

        // Get selected filters
        const statusFilter = document.getElementById('statusFilter').value;
        const typeFilter = document.getElementById('typeFilter').value;

        // Filter emergencies
        const filteredEmergencies = emergencies.filter(emergency => {
            const statusMatch = statusFilter === 'all' || emergency.status === statusFilter;
            const typeMatch = typeFilter === 'all' || emergency.emergencyType === typeFilter;
            return statusMatch && typeMatch;
        });

        // Add markers to map
        map.eachLayer((layer) => {
            if (layer instanceof L.Marker) {
                map.removeLayer(layer);
            }
        });

        filteredEmergencies.forEach(emergency => {
            // Update statistics
            if (emergency.status === 'active') statistics.active++;
            else if (emergency.status === 'resolved') statistics.resolved++;
            else if (emergency.status === 'pending') statistics.pending++;

            // Add marker to map
            if (emergency.location.coordinates) {
                const marker = L.marker([
                    emergency.location.coordinates.lat,
                    emergency.location.coordinates.lng
                ]).addTo(map);
                
                marker.bindPopup(`
                    <b>${emergency.emergencyType.toUpperCase()}</b><br>
                    Status: ${emergency.status}<br>
                    ${emergency.description}
                `);
            }

            // Create table row
            const row = document.createElement('tr');
            row.className = 'border-t border-gray-800';
            row.innerHTML = `
                <td class="py-3 px-4">
                    <div class="flex items-center">
                        <i class="fas fa-${getEmergencyIcon(emergency.emergencyType)} text-red-500 mr-2"></i>
                        ${emergency.emergencyType}
                    </div>
                </td>
                <td class="py-3 px-4">${emergency.location.address || 'Location not specified'}</td>
                <td class="py-3 px-4">
                    <span class="px-2 py-1 rounded-full text-xs ${getStatusColor(emergency.status)}">
                        ${emergency.status}
                    </span>
                </td>
                <td class="py-3 px-4">${formatTime(emergency.timestamp)}</td>
                <td class="py-3 px-4">
                    <div class="flex space-x-4">
                        <button onclick="resolveEmergency('${emergency.id}')" 
                            class="text-green-400 hover:text-green-300" title="Mark as Resolved">
                            <i class="fas fa-check-circle"></i>
                        </button>
                        <button onclick="deleteEmergency('${emergency.id}')"
                            class="text-red-400 hover:text-red-300" title="Delete Record">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;
            emergencyList.appendChild(row);
        });

        updateStatistics();
    }

    // Helper functions
    function getEmergencyIcon(type) {
        const icons = {
            medical: 'ambulance',
            fire: 'fire',
            police: 'shield-alt',
            accident: 'car-crash',
            disaster: 'house-damage',
            other: 'exclamation-triangle'
        };
        return icons[type] || 'exclamation-circle';
    }

    function getStatusColor(status) {
        const colors = {
            pending: 'bg-yellow-500 bg-opacity-20 text-yellow-500',
            active: 'bg-blue-500 bg-opacity-20 text-blue-500',
            resolved: 'bg-green-500 bg-opacity-20 text-green-500'
        };
        return colors[status] || 'bg-gray-500 bg-opacity-20 text-gray-500';
    }

    function formatTime(timestamp) {
        return new Date(timestamp).toLocaleString();
    }

    // Add event listeners for filters
    document.getElementById('statusFilter').addEventListener('change', loadEmergencies);
    document.getElementById('typeFilter').addEventListener('change', loadEmergencies);

    // Initial load
    loadEmergencies();

    // Refresh data periodically
    setInterval(loadEmergencies, 30000);

    // Update the action functions
    window.resolveEmergency = function(emergencyId) {
        if (confirm('Mark this emergency as resolved and archive it?')) {
            const emergencies = JSON.parse(localStorage.getItem('emergencies') || '[]');
            const filteredEmergencies = emergencies.filter(e => e.id !== emergencyId);
            
            // Get the emergency being resolved
            const emergency = emergencies.find(e => e.id === emergencyId);
            if (emergency) {
                // Add to resolved emergencies archive
                const resolvedEmergencies = JSON.parse(localStorage.getItem('resolvedEmergencies') || '[]');
                emergency.status = 'resolved';
                emergency.resolvedAt = new Date().toISOString();
                resolvedEmergencies.push(emergency);
                localStorage.setItem('resolvedEmergencies', JSON.stringify(resolvedEmergencies));
            }

            // Remove from active emergencies
            localStorage.setItem('emergencies', JSON.stringify(filteredEmergencies));
            
            // Free up assigned resources
            const resources = JSON.parse(localStorage.getItem('resources') || '[]');
            resources.forEach(resource => {
                if (resource.assignedTo === emergencyId) {
                    resource.status = 'available';
                    delete resource.assignedTo;
                }
            });
            localStorage.setItem('resources', JSON.stringify(resources));
            
            // Show success message
            showNotification('Emergency marked as resolved and archived', 'success');
            
            // Refresh the view
            loadEmergencies();
        }
    };

    window.deleteEmergency = function(emergencyId) {
        if (confirm('Permanently delete this emergency record? This action cannot be undone.')) {
            const emergencies = JSON.parse(localStorage.getItem('emergencies') || '[]');
            const filteredEmergencies = emergencies.filter(e => e.id !== emergencyId);
            localStorage.setItem('emergencies', JSON.stringify(filteredEmergencies));
            
            // Free up assigned resources
            const resources = JSON.parse(localStorage.getItem('resources') || '[]');
            resources.forEach(resource => {
                if (resource.assignedTo === emergencyId) {
                    resource.status = 'available';
                    delete resource.assignedTo;
                }
            });
            localStorage.setItem('resources', JSON.stringify(resources));
            
            // Show success message
            showNotification('Emergency record permanently deleted', 'warning');
            
            // Refresh the view
            loadEmergencies();
        }
    };

    // Add notification function
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-600' : 
            type === 'warning' ? 'bg-red-600' : 
            'bg-blue-600'
        } text-white`;
        notification.innerHTML = message;
        document.body.appendChild(notification);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}); 