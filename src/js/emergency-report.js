document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in
    const user = JSON.parse(localStorage.getItem('user') || sessionStorage.getItem('user') || 'null');
    if (!user || !user.isLoggedIn) {
        window.location.href = 'login.html';
        return;
    }

    // Initialize map
    const map = L.map('locationMap').setView([0, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let marker;
    let selectedLocation = null;

    // Get user's location
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords;
                map.setView([latitude, longitude], 13);
                
                // Add marker for user's location
                marker = L.marker([latitude, longitude], { draggable: true }).addTo(map);
                selectedLocation = { lat: latitude, lng: longitude };

                // Update location when marker is dragged
                marker.on('dragend', function(e) {
                    selectedLocation = e.target.getLatLng();
                });
            },
            (error) => {
                console.error('Error getting location:', error);
            }
        );
    }

    // Handle map clicks to update marker
    map.on('click', function(e) {
        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { draggable: true }).addTo(map);
        }
        selectedLocation = e.latlng;
    });

    // Handle emergency type selection
    const emergencyTypeBtns = document.querySelectorAll('.emergency-type-btn');
    let selectedEmergencyType = null;

    emergencyTypeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            emergencyTypeBtns.forEach(b => b.classList.remove('bg-red-600'));
            // Add active class to clicked button
            this.classList.add('bg-red-600');
            selectedEmergencyType = this.dataset.type;
        });
    });

    // Handle severity selection
    const severityInputs = document.querySelectorAll('input[name="severity"]');
    severityInputs.forEach(input => {
        input.addEventListener('change', function() {
            // Update visual feedback
            severityInputs.forEach(inp => {
                const div = inp.nextElementSibling;
                if (inp.checked) {
                    div.classList.add('ring-2', 'ring-white');
                } else {
                    div.classList.remove('ring-2', 'ring-white');
                }
            });
        });
    });

    // Handle form submission
    const emergencyForm = document.getElementById('emergencyForm');
    emergencyForm.addEventListener('submit', async function(e) {
        e.preventDefault();

        if (!selectedEmergencyType) {
            alert('Please select an emergency type');
            return;
        }

        if (!selectedLocation) {
            alert('Please select a location on the map');
            return;
        }

        // Gather form data
        const formData = {
            emergencyType: selectedEmergencyType,
            severity: document.querySelector('input[name="severity"]:checked')?.value,
            location: {
                coordinates: selectedLocation,
                address: document.getElementById('address').value,
                landmark: document.getElementById('landmark').value
            },
            peopleAffected: document.getElementById('peopleAffected').value,
            description: document.getElementById('description').value,
            contact: {
                name: document.getElementById('contactName').value,
                phone: document.getElementById('contactPhone').value
            },
            resources: Array.from(document.querySelectorAll('input[name="resources"]:checked'))
                .map(input => input.value),
            timestamp: new Date().toISOString(),
            status: 'pending'
        };

        try {
            // Here you would typically send the data to your backend
            // For now, we'll store it in localStorage
            const emergencies = JSON.parse(localStorage.getItem('emergencies') || '[]');
            emergencies.push(formData);
            localStorage.setItem('emergencies', JSON.stringify(emergencies));

            // Show success message
            alert('Emergency report submitted successfully!');
            
            // Redirect to dashboard or status page
            window.location.href = 'index.html#dashboard';
        } catch (error) {
            console.error('Error submitting report:', error);
            alert('Failed to submit emergency report. Please try again.');
        }
    });
}); 