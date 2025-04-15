document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
    if (!user || !user.isLoggedIn) {
        window.location.href = 'login.html';
        return;
    }

    // Initialize resources if not exists
    if (!localStorage.getItem('resources')) {
        localStorage.setItem('resources', JSON.stringify([]));
    }

    // Load and display resources
    loadResources();

    // Add event listeners for filters
    document.getElementById('resourceTypeFilter').addEventListener('change', loadResources);
    document.getElementById('statusFilter').addEventListener('change', loadResources);

    // Add event listener for add resource form
    document.getElementById('addResourceForm').addEventListener('submit', handleAddResource);

    // Load active incidents
    loadActiveIncidents();

    // Update statistics periodically
    setInterval(updateStatistics, 30000);
});

function loadResources() {
    const resources = JSON.parse(localStorage.getItem('resources') || '[]');
    const resourceList = document.getElementById('resourceList');
    const typeFilter = document.getElementById('resourceTypeFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;

    // Filter resources
    const filteredResources = resources.filter(resource => {
        const typeMatch = typeFilter === 'all' || resource.type === typeFilter;
        const statusMatch = statusFilter === 'all' || resource.status === statusFilter;
        return typeMatch && statusMatch;
    });

    // Clear existing list
    resourceList.innerHTML = '';

    // Add resources to table
    filteredResources.forEach(resource => {
        const row = document.createElement('tr');
        row.className = 'border-t border-gray-800';
        row.innerHTML = `
            <td class="py-3 px-4">
                <div class="flex items-center">
                    <i class="fas fa-${getResourceIcon(resource.type)} text-red-500 mr-2"></i>
                    ${capitalizeFirstLetter(resource.type)}
                </div>
            </td>
            <td class="py-3 px-4">${resource.id}</td>
            <td class="py-3 px-4">
                <span class="px-2 py-1 rounded-full text-xs ${getStatusColor(resource.status)}">
                    ${resource.status}
                </span>
            </td>
            <td class="py-3 px-4">${resource.location}</td>
            <td class="py-3 px-4">
                <button onclick="assignResource('${resource.id}')" class="text-blue-400 hover:text-blue-500 mr-2" 
                    ${resource.status !== 'available' ? 'disabled' : ''}>
                    <i class="fas fa-link"></i>
                </button>
                <button onclick="toggleResourceStatus('${resource.id}')" class="text-yellow-400 hover:text-yellow-500 mr-2">
                    <i class="fas fa-sync-alt"></i>
                </button>
                <button onclick="deleteResource('${resource.id}')" class="text-red-400 hover:text-red-500">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        resourceList.appendChild(row);
    });

    updateStatistics();
}

function loadActiveIncidents() {
    const incidents = JSON.parse(localStorage.getItem('emergencies') || '[]');
    const activeIncidents = incidents.filter(incident => incident.status === 'active');
    const incidentsList = document.getElementById('activeIncidentsList');

    incidentsList.innerHTML = '';

    activeIncidents.forEach(incident => {
        const div = document.createElement('div');
        div.className = 'bg-gray-800 p-4 rounded-lg';
        div.innerHTML = `
            <div class="flex justify-between items-center mb-2">
                <h3 class="text-white font-medium">${incident.emergencyType} Emergency</h3>
                <span class="text-xs text-gray-400">${formatTime(incident.timestamp)}</span>
            </div>
            <p class="text-gray-300 text-sm mb-2">${incident.location.address}</p>
            <div class="flex justify-between items-center">
                <span class="text-xs text-gray-400">Assigned: ${incident.resources?.length || 0} units</span>
                <button onclick="showAssignResourceModal('${incident.id}')" 
                    class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">
                    Assign Resources
                </button>
            </div>
        `;
        incidentsList.appendChild(div);
    });
}

function updateStatistics() {
    const resources = JSON.parse(localStorage.getItem('resources') || '[]');
    const stats = {
        available: 0,
        assigned: 0,
        maintenance: 0,
        critical: 0
    };

    resources.forEach(resource => {
        stats[resource.status]++;
        if (resource.maintenanceNeeded) stats.critical++;
    });

    document.getElementById('availableCount').textContent = stats.available;
    document.getElementById('assignedCount').textContent = stats.assigned;
    document.getElementById('maintenanceCount').textContent = stats.maintenance;
    document.getElementById('criticalCount').textContent = stats.critical;
}

// Helper functions
function getResourceIcon(type) {
    const icons = {
        ambulance: 'ambulance',
        firetruck: 'truck',
        police: 'car',
        medical: 'user-md'
    };
    return icons[type] || 'question';
}

function getStatusColor(status) {
    const colors = {
        available: 'bg-green-500 bg-opacity-20 text-green-500',
        assigned: 'bg-blue-500 bg-opacity-20 text-blue-500',
        maintenance: 'bg-yellow-500 bg-opacity-20 text-yellow-500'
    };
    return colors[status] || 'bg-gray-500 bg-opacity-20 text-gray-500';
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function formatTime(timestamp) {
    return new Date(timestamp).toLocaleString();
}

// Modal functions
function openAddResourceModal() {
    document.getElementById('addResourceModal').classList.remove('hidden');
}

function closeAddResourceModal() {
    document.getElementById('addResourceModal').classList.add('hidden');
}

// Resource management functions
function handleAddResource(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const resource = {
        type: formData.get('type'),
        id: formData.get('id'),
        location: formData.get('location'),
        status: 'available',
        maintenanceNeeded: false,
        lastMaintenance: new Date().toISOString()
    };

    const resources = JSON.parse(localStorage.getItem('resources') || '[]');
    resources.push(resource);
    localStorage.setItem('resources', JSON.stringify(resources));

    closeAddResourceModal();
    loadResources();
}

function assignResource(resourceId) {
    // Implementation for assigning resources to incidents
    console.log('Assigning resource:', resourceId);
}

function toggleResourceStatus(resourceId) {
    const resources = JSON.parse(localStorage.getItem('resources') || '[]');
    const resource = resources.find(r => r.id === resourceId);
    
    if (resource) {
        const statusCycle = ['available', 'assigned', 'maintenance'];
        const currentIndex = statusCycle.indexOf(resource.status);
        resource.status = statusCycle[(currentIndex + 1) % statusCycle.length];
        
        localStorage.setItem('resources', JSON.stringify(resources));
        loadResources();
    }
}

function deleteResource(resourceId) {
    if (confirm('Are you sure you want to delete this resource?')) {
        const resources = JSON.parse(localStorage.getItem('resources') || '[]');
        const updatedResources = resources.filter(r => r.id !== resourceId);
        localStorage.setItem('resources', JSON.stringify(updatedResources));
        loadResources();
    }
}

function startMaintenance() {
    // Implementation for scheduling maintenance
    alert('Maintenance scheduling functionality will be implemented here');
}

function generateResourceReport() {
    // Implementation for generating resource reports
    alert('Report generation functionality will be implemented here');
} 