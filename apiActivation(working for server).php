<?php 
require '../components/core.php';
$core = new Core();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file path
$logFile = '/home/u176398115/domains/kinge.shop/public_html/logs/apiActivation.log';

// Function to log messages
function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

// Log the start of the script
logMessage('Script started.');

// Fetch active servers from the database
function getActiveServers($core) {
    $activeServers = [];
    $query = "SELECT serverIP FROM server WHERE isActive = 1";
    $result = mysqli_query($core->getConnection(), $query);

    if ($result) {
        logMessage('Successfully fetched active servers from the database.');
        while ($row = mysqli_fetch_assoc($result)) {
            $activeServers[] = $row['serverIP'];
        }
    } else {
        logMessage('Error fetching active servers: ' . mysqli_error($core->getConnection()));
    }

    return $activeServers;
}

// Function to send requests to VPN servers
function sendRequestToServer($serverIP, $action, $username, $password = '') {
    $url = "http://$serverIP/vpn.php";
    $data = [
        'user' => $username,
        'password' => $password,
        'action' => $action
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data)
        ]
    ];
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    return json_decode($result, true);
}

// Check if the required parameters are set
if (isset($_POST['data']) && isset($_POST['status'])) {
    $status = $_POST['status']; // Get the status from POST
    $response = [];
    $activeServers = getActiveServers($core);

    if (empty($activeServers)) {
        logMessage('No active servers available.');
        echo json_encode(['state' => 0, 'message' => 'No active servers available.']);
        exit;
    }

    // Process each user in the data array
    foreach ($_POST['data'] as $userData) {
        $username = $userData['username']; // Get the username
        $password = isset($userData['password']) ? $userData['password'] : '';

        logMessage("Processing user: $username with status: $status");

        foreach ($activeServers as $serverIP) {
            if ($status == "ON") {
                $result = sendRequestToServer($serverIP, 'add', $username, $password);
                if ($result['state'] == 1) {
                    logMessage("User added to server $serverIP: $username");
                } else {
                    logMessage("Failed to add user to server $serverIP: $username. " . $result['message']);
                }
            } elseif ($status == "OFF") {
                $result = sendRequestToServer($serverIP, 'delete', $username);
                if ($result['state'] == 1) {
                    logMessage("User deleted from server $serverIP: $username");
                } else {
                    logMessage("Failed to delete user from server $serverIP: $username. " . $result['message']);
                }
            }
        }
    }

    // Log the response before sending it
    logMessage('Processing complete.');
    echo json_encode(['state' => 1, 'message' => 'Processing complete.']);
} else {
    logMessage('Invalid request: Missing required parameters.');
    echo json_encode(['state' => 0, 'message' => 'Invalid request.']);
}

// Log the end of the script
logMessage('Script ended.');
?>
