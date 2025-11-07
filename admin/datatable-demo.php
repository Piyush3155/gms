<?php
require_once '../includes/config.php';
require_permission('admin', 'view');

$page_title = "DataTable Demo";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-wrapper">
    <?php include '../includes/header.php'; ?>

    <div class="page-content">
        <div class="container-fluid">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-table me-2"></i>DataTable Component Demo
                </h1>
                <p class="text-muted">Demonstration of the enhanced DataTable component with search, sort, pagination, and export features.</p>
            </div>

            <!-- Feature Overview -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="card-icon bg-primary-light text-primary">
                            <i class="bi bi-search"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Search</h5>
                            <p class="card-text">Real-time filtering across all columns</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="card-icon bg-success-light text-success">
                            <i class="bi bi-sort-alpha-down"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Sorting</h5>
                            <p class="card-text">Click column headers to sort</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="card-icon bg-info-light text-info">
                            <i class="bi bi-layout-three-columns"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Pagination</h5>
                            <p class="card-text">Customizable rows per page</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="feature-card">
                        <div class="card-icon bg-warning-light text-warning">
                            <i class="bi bi-download"></i>
                        </div>
                        <div class="card-content">
                            <h5 class="card-title">Export</h5>
                            <p class="card-text">Download as CSV, Excel, or PDF</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sample Data Table -->
            <div class="card-modern">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sample Members Data</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="demo-table" class="table table-modern">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Plan</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                    <th data-sortable="false">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Generate sample data
                                $names = ['John Smith', 'Jane Doe', 'Mike Johnson', 'Sarah Williams', 'David Brown', 'Emily Davis', 'Chris Wilson', 'Lisa Anderson', 'Tom Martinez', 'Amy Taylor', 'Robert Garcia', 'Jennifer Lee', 'James White', 'Maria Lopez', 'Michael Clark'];
                                $plans = ['Basic', 'Premium', 'Elite', 'Annual'];
                                $statuses = ['active', 'inactive', 'pending'];
                                
                                for ($i = 1; $i <= 15; $i++) {
                                    $name = $names[$i - 1];
                                    $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';
                                    $phone = sprintf('+1 555-%03d-%04d', rand(100, 999), rand(1000, 9999));
                                    $plan = $plans[array_rand($plans)];
                                    $joinDate = date('M j, Y', strtotime('-' . rand(30, 365) . ' days'));
                                    $status = $statuses[array_rand($statuses)];
                                    $statusClass = 'status-' . $status;
                                    
                                    echo "<tr>";
                                    echo "<td>{$i}</td>";
                                    echo "<td>{$name}</td>";
                                    echo "<td>{$email}</td>";
                                    echo "<td>{$phone}</td>";
                                    echo "<td><span class='badge bg-primary'>{$plan}</span></td>";
                                    echo "<td>{$joinDate}</td>";
                                    echo "<td><span class='status-indicator {$statusClass}'>" . ucfirst($status) . "</span></td>";
                                    echo "<td class='actions'>";
                                    echo "<button class='btn-icon btn-sm btn-outline-primary' title='View'><i class='bi bi-eye'></i></button>";
                                    echo "<button class='btn-icon btn-sm btn-outline-success' title='Edit'><i class='bi bi-pencil'></i></button>";
                                    echo "<button class='btn-icon btn-sm btn-outline-danger' title='Delete'><i class='bi bi-trash'></i></button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Usage Code Example -->
            <div class="card-modern mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-code-slash me-2"></i>Usage Example
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="mb-3">HTML:</h6>
                    <pre class="bg-light p-3 rounded"><code>&lt;table id="demo-table" class="table table-modern"&gt;
  &lt;thead&gt;
    &lt;tr&gt;
      &lt;th&gt;Name&lt;/th&gt;
      &lt;th&gt;Email&lt;/th&gt;
      &lt;th data-sortable="false"&gt;Actions&lt;/th&gt;
    &lt;/tr&gt;
  &lt;/thead&gt;
  &lt;tbody&gt;
    &lt;tr&gt;
      &lt;td&gt;John Doe&lt;/td&gt;
      &lt;td&gt;john@example.com&lt;/td&gt;
      &lt;td&gt;&lt;button&gt;Edit&lt;/button&gt;&lt;/td&gt;
    &lt;/tr&gt;
  &lt;/tbody&gt;
&lt;/table&gt;</code></pre>

                    <h6 class="mb-3 mt-4">JavaScript:</h6>
                    <pre class="bg-light p-3 rounded"><code>const table = document.getElementById('demo-table');
new DataTable(table, {
    searchable: true,      // Enable search field
    pagination: true,      // Enable pagination
    sortable: true,        // Enable column sorting
    exportable: true,      // Enable export buttons (CSV, Excel, PDF)
    itemsPerPage: 10,      // Number of rows per page
    exportOptions: {
        fileName: 'data-export'  // Base filename for exports
    }
});</code></pre>

                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> To disable sorting on specific columns, add <code>data-sortable="false"</code> to the <code>&lt;th&gt;</code> element (typically used for action columns).
                    </div>
                </div>
            </div>

        </div>
    </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.getElementById('demo-table');
        if (table) {
            new DataTable(table, {
                searchable: true,
                pagination: true,
                sortable: true,
                exportable: true,
                itemsPerPage: 10,
                exportOptions: {
                    fileName: 'GMS_Demo_Data'
                }
            });
        }
    });
    </script>
</body>
</html>
