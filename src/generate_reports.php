<?php
// Include auth and config files
require_once 'includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

// Get current user info
$currentUser = getCurrentUser();
$notificationCount = getNotificationCount();

// Check if this is an admin user, redirect if not
if (!$currentUser || $currentUser['role'] !== 'admin') {
    header("Location: user_dashboard.php");
    exit;
}

// Handle report generation
$success_message = '';
$error_message = '';
$report_data = [];
$report_title = '';
$report_columns = [];
$chart_type = '';
$chart_data = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['report_type'])) {
    $report_type = $_POST['report_type'];
    $date_range = isset($_POST['date_range']) ? $_POST['date_range'] : '7_days';
    
    // Determine date range
    $end_date = date('Y-m-d H:i:s');
    switch ($date_range) {
        case '24_hours':
            $start_date = date('Y-m-d H:i:s', strtotime('-1 day'));
            break;
        case '7_days':
            $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
            break;
        case '30_days':
            $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
            break;
        case 'all_time':
            $start_date = '2000-01-01 00:00:00'; // Essentially all time
            break;
        default:
            $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
    }
    
    // Generate the appropriate report
    switch ($report_type) {
        case 'incidents_by_status':
            $report_title = 'Incidents by Status';
            $sql = "SELECT status, COUNT(*) as count 
                    FROM incidents 
                    WHERE reported_at BETWEEN ? AND ?
                    GROUP BY status 
                    ORDER BY count DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $report_columns = ['Status', 'Count'];
            $report_data = [];
            $chart_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $status = ucfirst($row['status']);
                $count = $row['count'];
                $report_data[] = [$status, $count];
                $chart_data[] = ['label' => $status, 'value' => $count];
            }
            
            $chart_type = 'pie';
            break;
            
        case 'incidents_by_severity':
            $report_title = 'Incidents by Severity';
            $sql = "SELECT severity, COUNT(*) as count 
                    FROM incidents 
                    WHERE reported_at BETWEEN ? AND ?
                    GROUP BY severity 
                    ORDER BY 
                        CASE severity
                            WHEN 'critical' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'medium' THEN 3
                            WHEN 'low' THEN 4
                            ELSE 5
                        END";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $report_columns = ['Severity', 'Count'];
            $report_data = [];
            $chart_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $severity = ucfirst($row['severity']);
                $count = $row['count'];
                $report_data[] = [$severity, $count];
                $chart_data[] = ['label' => $severity, 'value' => $count];
            }
            
            $chart_type = 'column';
            break;
            
        case 'incidents_by_type':
            $report_title = 'Incidents by Type';
            $sql = "SELECT title as type, COUNT(*) as count 
                    FROM incidents 
                    WHERE reported_at BETWEEN ? AND ?
                    GROUP BY title 
                    ORDER BY count DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $report_columns = ['Type', 'Count'];
            $report_data = [];
            $chart_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $type = ucfirst($row['type']);
                $count = $row['count'];
                $report_data[] = [$type, $count];
                $chart_data[] = ['label' => $type, 'value' => $count];
            }
            
            $chart_type = 'pie';
            break;
            
        case 'resources_by_status':
            $report_title = 'Resources by Status';
            $sql = "SELECT status, COUNT(*) as count 
                    FROM resources 
                    GROUP BY status 
                    ORDER BY count DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $report_columns = ['Status', 'Count'];
            $report_data = [];
            $chart_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $status = ucfirst(str_replace('_', ' ', $row['status']));
                $count = $row['count'];
                $report_data[] = [$status, $count];
                $chart_data[] = ['label' => $status, 'value' => $count];
            }
            
            $chart_type = 'pie';
            break;
            
        case 'resources_by_type':
            $report_title = 'Resources by Type';
            $sql = "SELECT type, COUNT(*) as count 
                    FROM resources 
                    GROUP BY type 
                    ORDER BY count DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $report_columns = ['Type', 'Count'];
            $report_data = [];
            $chart_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $type = ucfirst($row['type']);
                $count = $row['count'];
                $report_data[] = [$type, $count];
                $chart_data[] = ['label' => $type, 'value' => $count];
            }
            
            $chart_type = 'column';
            break;
            
        case 'response_times':
            $report_title = 'Average Response Times';
            $sql = "SELECT 
                        i.severity,
                        AVG(TIMESTAMPDIFF(MINUTE, i.reported_at, l.created_at)) as avg_response_time
                    FROM incidents i
                    JOIN incident_logs l ON i.id = l.incident_id
                    WHERE 
                        i.reported_at BETWEEN ? AND ?
                        AND l.action = 'status_change'
                        AND l.details LIKE '%responding%'
                    GROUP BY i.severity
                    ORDER BY 
                        CASE i.severity
                            WHEN 'critical' THEN 1
                            WHEN 'high' THEN 2
                            WHEN 'medium' THEN 3
                            WHEN 'low' THEN 4
                            ELSE 5
                        END";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $report_columns = ['Severity', 'Average Response Time (minutes)'];
            $report_data = [];
            $chart_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $severity = ucfirst($row['severity']);
                $avg_time = round($row['avg_response_time'], 1);
                $report_data[] = [$severity, $avg_time];
                $chart_data[] = ['label' => $severity, 'value' => $avg_time];
            }
            
            $chart_type = 'column';
            break;
            
        case 'users_by_role':
            $report_title = 'Users by Role';
            $sql = "SELECT role, COUNT(*) as count 
                    FROM users 
                    GROUP BY role 
                    ORDER BY count DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $report_columns = ['Role', 'Count'];
            $report_data = [];
            $chart_data = [];
            
            while ($row = $result->fetch_assoc()) {
                $role = ucfirst($row['role']);
                $count = $row['count'];
                $report_data[] = [$role, $count];
                $chart_data[] = ['label' => $role, 'value' => $count];
            }
            
            $chart_type = 'pie';
            break;
    }
    
    $success_message = "Report generated successfully.";
}

// Set page title
$page_title = "Generate Reports - QRCS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="./output.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            padding-top: 5rem; /* Add body padding for fixed navbar */
        }
        main {
            padding-top: 1rem; /* Additional spacing for content */
        }
    </style>
</head>
<body class="bg-black min-h-screen">
    <?php include 'includes/navbar.php'; ?>

    <main class="container mx-auto px-4 py-8">
        <!-- Back button -->
        <div class="mb-6">
            <a href="admin_dashboard.php" class="text-blue-500 hover:underline">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
        
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
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Report Selection -->
            <div>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <h2 class="text-xl font-bold text-white mb-6">Generate Report</h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                        <!-- Report Type -->
                        <div>
                            <label for="report_type" class="block text-gray-300 mb-2">Report Type</label>
                            <select id="report_type" name="report_type" required class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-red-500">
                                <option value="">Select Report Type</option>
                                <optgroup label="Incident Reports">
                                    <option value="incidents_by_status">Incidents by Status</option>
                                    <option value="incidents_by_severity">Incidents by Severity</option>
                                    <option value="incidents_by_type">Incidents by Type</option>
                                    <option value="response_times">Response Times</option>
                                </optgroup>
                                <optgroup label="Resource Reports">
                                    <option value="resources_by_status">Resources by Status</option>
                                    <option value="resources_by_type">Resources by Type</option>
                                </optgroup>
                                <optgroup label="User Reports">
                                    <option value="users_by_role">Users by Role</option>
                                </optgroup>
                            </select>
                        </div>
                        
                        <!-- Date Range -->
                        <div>
                            <label for="date_range" class="block text-gray-300 mb-2">Date Range</label>
                            <select id="date_range" name="date_range" class="w-full px-4 py-2 rounded-lg bg-gray-800 border border-gray-700 text-white focus:outline-none focus:border-red-500">
                                <option value="24_hours">Last 24 Hours</option>
                                <option value="7_days" selected>Last 7 Days</option>
                                <option value="30_days">Last 30 Days</option>
                                <option value="all_time">All Time</option>
                            </select>
                        </div>
                        
                        <!-- Submit Button -->
                        <div>
                            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                                <i class="fas fa-chart-bar mr-2"></i>Generate Report
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Report Options -->
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 mt-6">
                    <h2 class="text-xl font-bold text-white mb-4">Report Options</h2>
                    
                    <div class="space-y-4 text-gray-300">
                        <div>
                            <h3 class="text-lg font-medium text-white mb-2">Available Reports</h3>
                            <ul class="list-disc pl-5 space-y-2">
                                <li><span class="text-red-400 font-medium">Incidents by Status</span> - Distribution of incidents by their current status</li>
                                <li><span class="text-orange-400 font-medium">Incidents by Severity</span> - Breakdown of incidents by severity level</li>
                                <li><span class="text-yellow-400 font-medium">Incidents by Type</span> - Analysis of different types of incidents</li>
                                <li><span class="text-green-400 font-medium">Response Times</span> - Average time to respond to incidents by severity</li>
                                <li><span class="text-blue-400 font-medium">Resources by Status</span> - Overview of resource availability</li>
                                <li><span class="text-indigo-400 font-medium">Resources by Type</span> - Distribution of different resource types</li>
                                <li><span class="text-purple-400 font-medium">Users by Role</span> - User distribution by role</li>
                            </ul>
                        </div>
                        
                        <div class="bg-red-900 bg-opacity-30 border border-red-800 rounded-lg p-4 mt-6">
                            <div class="flex items-start">
                                <i class="fas fa-info-circle text-red-500 text-xl mt-0.5 mr-3"></i>
                                <p>Reports are generated in real-time based on the latest data in the system.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Report Results -->
            <div class="lg:col-span-2">
                <?php if (!empty($report_data)): ?>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-white"><?php echo $report_title; ?></h2>
                        <div>
                            <button onclick="window.print()" class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded-md text-sm flex items-center">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                    
                    <!-- Chart -->
                    <div class="mb-8">
                        <canvas id="reportChart" height="250"></canvas>
                    </div>
                    
                    <!-- Data Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-gray-800 rounded-lg overflow-hidden">
                            <thead class="bg-gray-700">
                                <tr>
                                    <?php foreach ($report_columns as $column): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider"><?php echo $column; ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($report_data as $row): ?>
                                <tr class="hover:bg-gray-700">
                                    <?php foreach ($row as $cell): ?>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300"><?php echo $cell; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-gray-900 rounded-lg border border-gray-800 p-6 flex flex-col items-center justify-center h-96">
                    <i class="fas fa-chart-pie text-gray-600 text-6xl mb-4"></i>
                    <h3 class="text-xl font-medium text-gray-400 mb-2">No Report Generated</h3>
                    <p class="text-gray-500 text-center">Select a report type and click Generate Report to see results.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php if (!empty($chart_data)): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('reportChart').getContext('2d');
            
            // Prepare data for chart
            const labels = <?php echo json_encode(array_column($chart_data, 'label')); ?>;
            const values = <?php echo json_encode(array_column($chart_data, 'value')); ?>;
            
            // Chart colors
            const colors = [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(199, 199, 199, 0.7)',
                'rgba(83, 102, 255, 0.7)',
                'rgba(40, 159, 64, 0.7)',
                'rgba(210, 199, 199, 0.7)'
            ];
            
            // Create chart
            const chartType = '<?php echo $chart_type; ?>';
            new Chart(ctx, {
                type: chartType === 'pie' ? 'pie' : 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: '<?php echo $report_title; ?>',
                        data: values,
                        backgroundColor: colors,
                        borderColor: chartType === 'pie' ? 'rgba(0, 0, 0, 0.1)' : colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: chartType === 'pie' ? 'right' : 'top',
                            labels: {
                                color: 'rgb(229, 231, 235)'
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(26, 32, 44, 0.9)',
                            titleColor: 'rgb(229, 231, 235)',
                            bodyColor: 'rgb(229, 231, 235)',
                            displayColors: true
                        }
                    },
                    scales: chartType === 'pie' ? {} : {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(75, 85, 99, 0.2)'
                            },
                            ticks: {
                                color: 'rgb(156, 163, 175)'
                            }
                        },
                        x: {
                            grid: {
                                color: 'rgba(75, 85, 99, 0.2)'
                            },
                            ticks: {
                                color: 'rgb(156, 163, 175)'
                            }
                        }
                    }
                }
            });
        });
    </script>
    <?php endif; ?>
</body>
</html> 