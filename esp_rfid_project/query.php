<?php
// Database configuration
$host = 'localhost'; 
$db = 'rfid_db'; 
$user = 'root'; 
$pass = ''; 
$charset = 'utf8mb4';

// Set up the PDO connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Check if RFID data is received via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rfid'])) {
    // Get the RFID value from the POST request
    $rfid = htmlspecialchars($_POST['rfid']); // Sanitize the input for security

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
        
        // Prepare a response to send back to the ESP32
        $response = array('status' => 'success', 'rfid' => $rfid, 'new_status' => $new_status);
    } else {
        // RFID not found, respond without inserting
        $response = array('status' => 'RFID not found', 'rfid' => $rfid);
    }
    
    echo json_encode($response);
    exit;
}

// Handle GET request to fetch the latest RFID status
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Retrieve the latest RFID from the text file
    $latest_rfid = '';
    if (file_exists('rfid_data.txt')) {
        $lines = file('rfid_data.txt', FILE_IGNORE_NEW_LINES);
        $latest_rfid = end($lines); // Get the last line

        // Get status of the latest RFID from the database
        $stmt = $pdo->prepare("SELECT status FROM rfid_data WHERE rfid = ?");
        $stmt->execute([$latest_rfid]);
        $status_row = $stmt->fetch();

        if ($status_row) {
            // Return status as JSON if RFID is found
            echo json_encode([
                'rfid' => $latest_rfid,
                'status' => $status_row['status']
            ]);
        } else {
            // Return a response indicating RFID not found
            echo json_encode([
                'rfid' => $latest_rfid,
                'status' => 'RFID Not Found'
            ]);
        }
    } else {
        echo json_encode(['status' => 'No RFID data available']);
    }
    exit;
}
?>

