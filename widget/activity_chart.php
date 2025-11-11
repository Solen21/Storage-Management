<?php
/*
 * Activity Chart Widget
 * This widget is designed to be included in a dashboard page.
 * It assumes a session has already been started and a PDO connection ($pdo) is available.
 */

// We must re-verify the user's role to ensure this widget is only displayed to authorized users.
$widget_user_role_name = '';
$widget_role_id = $_SESSION["role_id"] ?? null;

if ($widget_role_id) {
    try {
        $sql = "SELECT name FROM roles WHERE id = :role_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':role_id', $widget_role_id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() == 1) {
            $widget_user_role_name = strtolower($stmt->fetchColumn());
        }
    } catch (PDOException $e) {
        error_log("Error fetching role in activity_chart.php widget: " . $e->getMessage());
    }
}

// Only proceed if the user is an admin or manager
if (in_array($widget_user_role_name, ['admin', 'manager'])):

    // --- Prepare data for the last 7 days ---
    $labels = [];
    $imports_data = [];
    $exports_data = [];
    $broken_data = [];

    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M d', strtotime($date));
        $imports_data[$date] = 0;
        $exports_data[$date] = 0;
        $broken_data[$date] = 0;
    }

    // Fetch import data
    $import_query = "SELECT DATE(import_date) as day, SUM(quantity) as total FROM imports WHERE import_date >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(import_date)";
    $import_results = $pdo->query($import_query)->fetchAll();
    foreach ($import_results as $row) {
        $imports_data[$row['day']] = (int)$row['total'];
    }

    // Fetch export data
    $export_query = "SELECT DATE(export_date) as day, SUM(quantity) as total FROM exports WHERE export_date >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(export_date)";
    $export_results = $pdo->query($export_query)->fetchAll();
    foreach ($export_results as $row) {
        $exports_data[$row['day']] = (int)$row['total'];
    }

    // Fetch broken items data
    $broken_query = "SELECT DATE(created_at) as day, SUM(quantity) as total FROM broken_items WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(created_at)";
    $broken_results = $pdo->query($broken_query)->fetchAll();
    foreach ($broken_results as $row) {
        $broken_data[$row['day']] = (int)$row['total'];
    }

?>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h4 class="my-0 font-weight-normal"><i class="fas fa-chart-line mr-2"></i>Last 7 Days Activity</h4>
    </div>
    <div class="card-body">
        <canvas id="activityChart"></canvas>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('activityChart').getContext('2d');
    
    // Determine colors based on theme
    const isDarkMode = document.body.classList.contains('dark-mode');
    const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
    const fontColor = isDarkMode ? '#f8f9fa' : '#666';

    const activityChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_values($labels)); ?>,
            datasets: [{
                label: 'Imports',
                data: <?php echo json_encode(array_values($imports_data)); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(54, 162, 235, 1)'
            }, {
                label: 'Exports',
                data: <?php echo json_encode(array_values($exports_data)); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(255, 99, 132, 1)'
            }, {
                label: 'Broken Items',
                data: <?php echo json_encode(array_values($broken_data)); ?>,
                backgroundColor: 'rgba(255, 206, 86, 0.2)', // Yellowish color
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 2,
                pointBackgroundColor: 'rgba(255, 206, 86, 1)'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                labels: {
                    fontColor: fontColor
                }
            },
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true,
                        fontColor: fontColor,
                        // Only show whole numbers
                        callback: function(value) { if (Number.isInteger(value)) { return value; } },
                    },
                    gridLines: {
                        color: gridColor
                    }
                }],
                xAxes: [{
                    ticks: {
                        fontColor: fontColor
                    },
                    gridLines: {
                        color: gridColor
                    }
                }]
            }
        }
    });
});
</script>

<?php endif; // End of role check ?>