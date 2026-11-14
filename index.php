<?php
// index.php
require 'auth.php';

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'vital_records_db';
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// 1. SYSTEM DASHBOARD METRICS
$sys_sql = "SELECT COUNT(*) as total_parishes, SUM(total_population) as total_pop, SUM(registered_count) as total_reg FROM parishes";
$sys_data = $conn->query($sys_sql)->fetch_assoc();

$national_pop = $sys_data['total_pop'] ?? 0;
$national_reg = $sys_data['total_reg'] ?? 0;
$national_gap = ($national_pop > 0) ? (($national_pop - $national_reg) / $national_pop) * 100 : 0;

// 2. SEARCH LOGIC
$search = "";
$sql = "SELECT * FROM parishes";
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $sql .= " WHERE parish_name LIKE '%$search%' OR district LIKE '%$search%'";
}
$sql .= " ORDER BY parish_name ASC";
$result = $conn->query($sql);

// 3. DISTRICT AGGREGATION LOGIC
$district_sql = "SELECT district, SUM(total_population) as total_pop, SUM(registered_count) as total_reg FROM parishes GROUP BY district ORDER BY district ASC";
$district_result = $conn->query($district_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vital Records Gap Mapper</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <div class="top-bar">
        <h1>Vital Records Gap Mapper</h1>
        <div class="user-info">
            <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="change_password.php" style="color: #3498db; font-weight:bold; text-decoration:none;">Change Password</a>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div style="background:#d4edda; color:#155724; padding:15px; text-align:center; border-radius:6px; margin-bottom:20px; border: 1px solid #c3e6cb;">
            <?php echo isset($_GET['msg']) ? htmlspecialchars($_GET['msg']) : 'Action completed successfully!'; ?>
        </div>
    <?php elseif(isset($_GET['status']) && $_GET['status'] == 'error'): ?>
        <div style="background:#f8d7da; color:#721c24; padding:15px; text-align:center; border-radius:6px; margin-bottom:20px; border: 1px solid #f5c6cb;">
            Error: <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <!-- NATIONAL SYSTEM DASHBOARD -->
    <h2>National Overview Dashboard</h2>
    <div class="sys-dashboard">
        <div class="sys-card">
            <h3>Total Parishes</h3>
            <p><?php echo number_format($sys_data['total_parishes']); ?></p>
        </div>
        <div class="sys-card">
            <h3>Total Population</h3>
            <p><?php echo number_format($national_pop); ?></p>
        </div>
        <div class="sys-card">
            <h3>Total Registered</h3>
            <p><?php echo number_format($national_reg); ?></p>
        </div>
        <div class="sys-card" style="background-color: <?php echo ($national_gap > 50) ? '#e74c3c' : '#27ae60'; ?>;">
            <h3>National Gap</h3>
            <p><?php echo number_format($national_gap, 1); ?>%</p>
        </div>
    </div>

    <!-- DISTRICT SUMMARY DASHBOARD -->
    <h2>District Summary (Targeted Areas)</h2>
    <div class="dashboard-grid">
        <?php
        if ($district_result->num_rows > 0) {
            while($d_row = $district_result->fetch_assoc()) {
                $d_total = $d_row['total_pop']; $d_reg = $d_row['total_reg'];
                $d_gap_pct = ($d_total > 0) ? (($d_total - $d_reg) / $d_total) * 100 : 0;
                $card_class = ($d_gap_pct > 60) ? 'severity-high' : (($d_gap_pct > 30) ? 'severity-medium' : 'severity-low');

                echo "<div class='district-card $card_class'>";
                echo "<h3>" . htmlspecialchars($d_row['district']) . "</h3>";
                echo "<p>Pop: " . number_format($d_total) . " | Reg: " . number_format($d_reg) . "</p>";
                echo "<span class='gap-value'>" . number_format($d_gap_pct, 2) . "% Gap</span>";
                echo "</div>";
            }
        } else { echo "<p>No district data available.</p>"; }
        ?>
    </div>

    <!-- Add New Parish Form -->
    <h2>Add New Parish Data</h2>
    <form action="add_parish.php" method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Parish Name:</label>
                <input type="text" name="parish_name" required placeholder="e.g. St. Mary's">
            </div>
            <div class="form-group">
                <label>District:</label>
                <input type="text" name="district" required placeholder="e.g. Kampala">
            </div>
            <div class="form-group">
                <label>Total Population:</label>
                <input type="number" name="total_population" required min="1" placeholder="e.g. 5000">
            </div>
            <div class="form-group">
                <label>Registered Count:</label>
                <input type="number" name="registered_count" required min="0" placeholder="e.g. 1500">
            </div>
        </div>
        
        <div class="action-buttons">
            <button type="submit" class="btn btn-primary">Add Parish Record</button>
            <a href="export.php" class="btn btn-success">📥 Export to CSV</a>
        </div>
    </form>

    <!-- Search and View Individual Parishes -->
    <h2>Search Individual Parishes</h2>
    <form method="GET" action="" class="search-box">
        <input type="text" name="search" placeholder="Search by Parish Name or District..." value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit">Search</button>
        <button type="button" onclick="document.getElementById('parishTable').style.display='none'; document.querySelector('input[name=search]').value='';">Reset</button>
    </form>

    <table id="parishTable">
        <thead>
            <tr>
                <th>Parish Name</th>
                <th>District</th>
                <th>Total Pop.</th>
                <th>Registered</th>
                <th>Gap %</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $total = $row['total_population']; $registered = $row['registered_count'];
                    $gap_percentage = ($total > 0) ? (($total - $registered) / $total) * 100 : 0;
                    $formatted_gap = number_format($gap_percentage, 2) . "%";
                    $gap_class = ($gap_percentage > 50) ? 'gap-high' : 'gap-low';

                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['parish_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['district']) . "</td>";
                    echo "<td>" . number_format($total) . "</td>";
                    echo "<td>" . number_format($registered) . "</td>";
                    echo "<td class='$gap_class'>" . $formatted_gap . "</td>";
                    
                    echo "<td>
                            <a href='delete_parish.php?id=" . $row['id'] . "' 
                               onclick=\"return confirm('Delete this record?');\" 
                               class='btn-danger'>Delete</a>
                          </td>";
                    echo "</tr>";
                }
            } else { echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>No parishes found.</td></tr>"; }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>