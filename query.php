<?php 
// Database configuration
$host = 'localhost'; // Your database host
$db   = 'rfid_db'; // Your database name
$user = 'root'; // Your database username
$pass = ''; // Your database password
$charset = 'utf8mb4';

// Set up the PDO connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Check if RFID data is received via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rfid'])) {
    // Get the RFID value from the POST request
    $rfid = htmlspecialchars($_POST['rfid']);  // Sanitize the input for security

    // Save the RFID value to a text file
    file_put_contents('rfid_data.txt', $rfid . PHP_EOL, FILE_APPEND);

    // Check if the RFID already exists in the database
    $stmt = $pdo->prepare("SELECT status FROM rfid_data WHERE rfid = ?");
    $stmt->execute([$rfid]);
    $row = $stmt->fetch();

    if ($row) {
        // If RFID exists, toggle its status
        $new_status = $row['status'] == 1 ? 0 : 1; // Toggle between 0 and 1
        $update_stmt = $pdo->prepare("UPDATE rfid_data SET status = ? WHERE rfid = ?");
        $update_stmt->execute([$new_status, $rfid]);
    } else {
        // If RFID doesn't exist, insert it into the database
        $insert_stmt = $pdo->prepare("INSERT INTO rfid_data (rfid, status) VALUES (?, ?)");
        $insert_stmt->execute([$rfid, 1]); // Insert with a default status of 1
    }

    // Prepare a response to send back to the ESP32
    $response = array('status' => 'success', 'rfid' => $rfid);
    echo json_encode($response);
    
    // Exit to prevent further execution of the script
    exit;
}

// Read the latest RFID data from the text file
$latest_rfid = '';
$status_message = 'No RFID data available.';
if (file_exists('rfid_data.txt')) {
    // Get the last line of the text file
    $lines = file('rfid_data.txt', FILE_IGNORE_NEW_LINES);
    $latest_rfid = end($lines);  // Get the last line

    // Check the status of the latest RFID in the database
    $stmt = $pdo->prepare("SELECT status FROM rfid_data WHERE rfid = ?");
    $stmt->execute([$latest_rfid]);
    $status_row = $stmt->fetch();

    if ($status_row) {
        // Set the status message based on the retrieved status
        $status_message = ($status_row['status'] == 1) ? 'Status: 1' : 'Status: 0';
    } else {
        // If not found in the database
        $status_message = 'RFID is not in the database.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Data Display</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script>
        function fetchLatestRFID() {
            fetch('query.php')
                .then(response => response.text())
                .then(data => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data;
                    const latestRFID = tempDiv.querySelector('div#latest-rfid').innerText;
                    const statusMessage = tempDiv.querySelector('div#status-message').innerText;

                    document.getElementById('latest-rfid').innerText = latestRFID;
                    document.getElementById('status-message').innerText = statusMessage;
                })
                .catch(error => console.error('Error fetching RFID data:', error));
        }

        setInterval(fetchLatestRFID, 5000);
    </script>
</head>
<body class="bg-gradient-to-r from-blue-50 to-blue-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">RFID Data Display</h1>
        <div class="space-y-6">
            <div class="p-4 bg-blue-50 rounded-lg shadow-inner">
                <h2 class="text-lg font-semibold text-gray-700">Latest RFID Value:</h2>
                <div class="text-blue-600 text-xl font-medium mt-2" id="latest-rfid">
                    <?= htmlspecialchars($latest_rfid) ?: 'No RFID data available.' ?>
                </div>
            </div>
            <div class="p-4 bg-green-50 rounded-lg shadow-inner">
                <h2 class="text-lg font-semibold text-gray-700">Status:</h2>
                <div class="text-green-600 text-xl font-medium mt-2" id="status-message">
                    <?= $status_message ?: 'No status available.' ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


