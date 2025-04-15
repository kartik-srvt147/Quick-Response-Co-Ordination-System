<?php
// Include auth and config files
require_once 'includes/config.php';
require_once 'includes/notification_helper.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Get current user info
$currentUser = getCurrentUser();

// Handle form submission
$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate form data
    if (empty($_POST['description']) || empty($_POST['severity'])) {
        $error_message = "Please fill all required fields";
    } else {
        // Prepare data for database insertion
        $title = !empty($_POST['emergencyType']) ? $conn->real_escape_string($_POST['emergencyType']) : 'Other';
        $description = $conn->real_escape_string($_POST['description']);
        $location = $conn->real_escape_string($_POST['address']);
        $latitude = !empty($_POST['latitude']) ? $conn->real_escape_string($_POST['latitude']) : 0;
        $longitude = !empty($_POST['longitude']) ? $conn->real_escape_string($_POST['longitude']) : 0;
        $severity = $conn->real_escape_string($_POST['severity']);
        $reported_by = $currentUser['id'];
        
        // Additional data
        $additional_info = [];
        if (!empty($_POST['peopleAffected'])) {
            $additional_info['peopleAffected'] = $_POST['peopleAffected'];
        }
        if (!empty($_POST['resources'])) {
            $additional_info['resources'] = $_POST['resources'];
        }
        if (!empty($_POST['landmark'])) {
            $additional_info['landmark'] = $_POST['landmark'];
        }
        if (!empty($_POST['contactPhone'])) {
            $additional_info['contactPhone'] = $_POST['contactPhone'];
        }
        
        $additional_data = !empty($additional_info) ? json_encode($additional_info) : null;
        
        // Insert into database
        $sql = "INSERT INTO incidents (title, description, location, latitude, longitude, severity, reported_by, status, additional_data) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'reported', ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssddsis", $title, $description, $location, $latitude, $longitude, $severity, $reported_by, $additional_data);
        
        if ($stmt->execute()) {
            // Get the inserted incident ID
            $incident_id = $stmt->insert_id;
            
            // Create notifications for admins using our helper function
            createEmergencyNotification($incident_id, $title, $severity, $location, 'admin');
            
            $success_message = "Emergency reported successfully! Help is on the way.";
        } else {
            $error_message = "Error reporting emergency: " . $conn->error;
        }
    }
}

// Set page title
$page_title = "Report Emergency - QRCS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="./output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            padding-top: 5rem; /* Add body padding for fixed navbar */
        }
        main {
            padding-top: 1rem; /* Additional spacing for content */
        }
        /* Styles for the emergency type buttons */
        .emergency-type-btn {
            transition: all 0.3s ease;
        }
        .emergency-type-btn.active {
            background-color: rgb(220, 38, 38); /* bg-red-600 */
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(220, 38, 38, 0.5);
        }
        /* Styles for severity radios */
        .severity-option input:checked + div {
            border: 2px solid white;
            transform: scale(1.05);
        }
        /* Animation for the submit button */
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .pulse-animation:hover {
            animation: pulse 2s infinite;
        }
        /* Map container */
        #locationMap {
            height: 300px;
            width: 100%;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body class="bg-black min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <main>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-600 bg-opacity-25 border border-green-500 text-green-100 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-check-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-bold">Success!</p>
                    <p><?php echo $success_message; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-600 bg-opacity-25 border border-red-500 text-red-100 px-4 py-3 rounded mb-6 flex items-center">
                <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                <div>
                    <p class="font-bold">Error</p>
                    <p><?php echo $error_message; ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="bg-gray-900 rounded-lg shadow-2xl p-6 space-y-8 border border-gray-800 transform transition-all hover:shadow-red-900/20">
                <div class="text-center">
                    <div class="inline-block p-3 rounded-full bg-red-600 bg-opacity-20 mb-4">
                        <i class="fas fa-heartbeat text-red-500 text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-white">Report Emergency</h1>
                    <p class="mt-2 text-gray-400">Please provide as much detail as possible</p>
                </div>

                <form id="emergencyForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                    <input type="hidden" id="latitude" name="latitude" value="">
                    <input type="hidden" id="longitude" name="longitude" value="">
                    <input type="hidden" id="emergencyType" name="emergencyType" value="other">
                    
                    <!-- Emergency Type -->
                    <div class="space-y-2">
                        <label class="text-white font-medium">Emergency Type</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <button type="button" class="emergency-type-btn bg-gray-800 hover:bg-red-600 text-white p-4 rounded-lg flex flex-col items-center transition-colors" data-type="medical">
                                <i class="fas fa-ambulance text-2xl mb-2"></i>
                                Medical
                            </button>
                            <button type="button" class="emergency-type-btn bg-gray-800 hover:bg-red-600 text-white p-4 rounded-lg flex flex-col items-center transition-colors" data-type="fire">
                                <i class="fas fa-fire text-2xl mb-2"></i>
                                Fire
                            </button>
                            <button type="button" class="emergency-type-btn bg-gray-800 hover:bg-red-600 text-white p-4 rounded-lg flex flex-col items-center transition-colors" data-type="police">
                                <i class="fas fa-shield-alt text-2xl mb-2"></i>
                                Police
                            </button>
                            <button type="button" class="emergency-type-btn bg-gray-800 hover:bg-red-600 text-white p-4 rounded-lg flex flex-col items-center transition-colors" data-type="accident">
                                <i class="fas fa-car-crash text-2xl mb-2"></i>
                                Accident
                            </button>
                            <button type="button" class="emergency-type-btn bg-gray-800 hover:bg-red-600 text-white p-4 rounded-lg flex flex-col items-center transition-colors" data-type="disaster">
                                <i class="fas fa-house-damage text-2xl mb-2"></i>
                                Disaster
                            </button>
                            <button type="button" class="emergency-type-btn bg-gray-800 hover:bg-red-600 text-white p-4 rounded-lg flex flex-col items-center transition-colors" data-type="other">
                                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                                Other
                            </button>
                        </div>
                    </div>

                    <!-- Severity Level -->
                    <div class="space-y-2">
                        <label class="text-white font-medium">Severity Level <span class="text-red-500">*</span></label>
                        <div class="flex space-x-4">
                            <label class="flex-1">
                                <input type="radio" name="severity" value="low" class="hidden" required>
                                <div class="text-center p-3 rounded-lg bg-green-800 hover:bg-green-700 cursor-pointer text-white">
                                    Low
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="severity" value="medium" class="hidden">
                                <div class="text-center p-3 rounded-lg bg-yellow-600 hover:bg-yellow-500 cursor-pointer text-white">
                                    Medium
                                </div>
                            </label>
                            <label class="flex-1">
                                <input type="radio" name="severity" value="high" class="hidden">
                                <div class="text-center p-3 rounded-lg bg-red-600 hover:bg-red-500 cursor-pointer text-white">
                                    High
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="space-y-2">
                        <label class="text-white font-medium">Location <span class="text-red-500">*</span></label>
                        <div class="space-y-4">
                            <div id="locationMap" class="shadow-lg"></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <input type="text" id="address" name="address" placeholder="Street Address" required
                                    class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-red-500">
                                <input type="text" id="landmark" name="landmark" placeholder="Nearest Landmark" 
                                    class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-red-500">
                            </div>
                            <button type="button" id="getCurrentLocationBtn" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition-colors flex items-center justify-center">
                                <i class="fas fa-location-arrow mr-2"></i>
                                Use My Current Location
                            </button>
                        </div>
                    </div>

                    <!-- Number of People Affected -->
                    <div class="space-y-2">
                        <label for="peopleAffected" class="text-white font-medium">Number of People Affected</label>
                        <input type="number" id="peopleAffected" name="peopleAffected" min="1" 
                            class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-red-500">
                    </div>

                    <!-- Description -->
                    <div class="space-y-2">
                        <label for="description" class="text-white font-medium">Emergency Description <span class="text-red-500">*</span></label>
                        <textarea id="description" name="description" rows="4" required
                            class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-red-500"
                            placeholder="Please describe the emergency situation in detail..."></textarea>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-4">
                        <h3 class="text-white font-medium">Contact Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" id="contactName" name="contactName" placeholder="Your Name" value="<?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>" 
                                class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-red-500">
                            <input type="tel" id="contactPhone" name="contactPhone" placeholder="Phone Number" 
                                class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white placeholder-gray-500 focus:outline-none focus:border-red-500">
                        </div>
                    </div>

                    <!-- Additional Resources Needed -->
                    <div class="space-y-2">
                        <label class="text-white font-medium">Additional Resources Needed</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="resources[]" value="ambulance" 
                                    class="form-checkbox h-4 w-4 text-red-600 bg-gray-800 border-gray-700 rounded">
                                <span class="text-gray-300">Ambulance</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="resources[]" value="firetruck" 
                                    class="form-checkbox h-4 w-4 text-red-600 bg-gray-800 border-gray-700 rounded">
                                <span class="text-gray-300">Fire Truck</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="resources[]" value="police" 
                                    class="form-checkbox h-4 w-4 text-red-600 bg-gray-800 border-gray-700 rounded">
                                <span class="text-gray-300">Police Unit</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="resources[]" value="medicalTeam" 
                                    class="form-checkbox h-4 w-4 text-red-600 bg-gray-800 border-gray-700 rounded">
                                <span class="text-gray-300">Medical Team</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="resources[]" value="rescueTeam" 
                                    class="form-checkbox h-4 w-4 text-red-600 bg-gray-800 border-gray-700 rounded">
                                <span class="text-gray-300">Rescue Team</span>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-center pt-4">
                        <button type="submit" 
                            class="bg-red-600 text-white px-8 py-3 rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-gray-900 transition-colors pulse-animation">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            Submit Emergency Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize map
            const map = L.map('locationMap').setView([0, 0], 2);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
            
            let marker = null;
            
            // Function to add/update marker
            function setMarker(lat, lng) {
                if (marker) {
                    map.removeLayer(marker);
                }
                marker = L.marker([lat, lng]).addTo(map);
                map.setView([lat, lng], 15);
                
                // Update hidden form fields
                document.getElementById('latitude').value = lat;
                document.getElementById('longitude').value = lng;
                
                // Reverse geocode to get address
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data && data.display_name) {
                            document.getElementById('address').value = data.display_name;
                        }
                    })
                    .catch(error => console.error('Error getting address:', error));
            }
            
            // Click on map to set location
            map.on('click', function(e) {
                setMarker(e.latlng.lat, e.latlng.lng);
            });
            
            // Get current location button
            document.getElementById('getCurrentLocationBtn').addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const { latitude, longitude } = position.coords;
                            setMarker(latitude, longitude);
                        },
                        (error) => {
                            console.error('Error getting location:', error);
                            alert('Unable to get your current location. Please click on the map or enter the address manually.');
                        }
                    );
                } else {
                    alert('Geolocation is not supported by your browser. Please click on the map or enter the address manually.');
                }
            });
            
            // Try to get user's location on page load
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const { latitude, longitude } = position.coords;
                        setMarker(latitude, longitude);
                    },
                    (error) => {
                        console.error('Error getting location:', error);
                        // Use default center if unable to get location
                        map.setView([0, 0], 2);
                    }
                );
            }
            
            // Emergency type button activation
            const emergencyTypeButtons = document.querySelectorAll('.emergency-type-btn');
            emergencyTypeButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    emergencyTypeButtons.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    // Update hidden input value
                    document.getElementById('emergencyType').value = this.getAttribute('data-type');
                });
            });
            
            // Style radio buttons on click
            const severityRadios = document.querySelectorAll('input[name="severity"]');
            severityRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    // Add styling to parent div
                    const divs = document.querySelectorAll('input[name="severity"] + div');
                    divs.forEach(div => {
                        div.style.border = "none";
                        div.style.transform = "scale(1)";
                    });
                    
                    if (this.checked) {
                        const div = this.nextElementSibling;
                        div.style.border = "2px solid white";
                        div.style.transform = "scale(1.05)";
                    }
                });
            });
        });
    </script>
</body>
</html> 