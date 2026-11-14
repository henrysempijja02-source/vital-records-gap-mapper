<?php
// export.php
require 'auth.php'; // ADD THIS LINE
// ... rest of your code ...
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'vital_records_db';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set the filename with the current date
$filename = "Vital_Records_Gap_Report_" . date('Y-m-d') . ".csv";

// Set headers to force the browser to download the file
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add a UTF-8 BOM so Excel opens it correctly with special characters
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// 1. Output the Column Headers
fputcsv($output, array('Parish Name', 'District', 'Total Population', 'Registered Count', 'Gap Percentage (%)'));

// 2. Fetch the data from the database
$sql = "SELECT * FROM parishes ORDER BY district ASC, parish_name ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $total = $row['total_population'];
        $registered = $row['registered_count'];
        
        // Calculate Gap Percentage
        $gap_percentage = 0;
        if ($total > 0) {
            $gap_percentage = (($total - $registered) / $total) * 100;
        }
        
        $formatted_gap = number_format($gap_percentage, 2);

        // Output the data row
        fputcsv($output, array(
            $row['parish_name'],
            $row['district'],
            $total,
            $registered,
            $formatted_gap . '%'
        ));
    }
} else {
    fputcsv($output, array('No data available'));
}

fclose($output);
$conn->close();
exit();
?>