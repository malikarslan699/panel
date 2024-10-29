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

// Temporary function to fetch user status from the database
function getUserStatus($core, $username) {
    $query = "SELECT status FROM account WHERE username = '$username'";
    $result = mysqli_query($core->getConnection(), $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['status'];
    } else {
        logMessage("Error fetching user status for $username: " . mysqli_error($core->getConnection()));
        return null;
    }
}

// Temporary function to update user status in the database
function updateUserStatus($core, $username, $newStatus) {
    $query = "UPDATE account SET status = '$newStatus' WHERE username = '$username'";
    if (mysqli_query($core->getConnection(), $query)) {
        logMessage("Successfully updated status for $username to $newStatus.");
    } else {
        logMessage("Error updating status for $username: " . mysqli_error($core->getConnection()));
    }
}

// Log the start of the script
logMessage('apiActivation.php script started for SQL testing and VPN server requests.');

// Check if the required parameters are set
if (isset($_POST['data']) && isset($_POST['status'])) {
    $status = $_POST['status']; // Get the status from POST
    $activeServers = getActiveServers($core); // Fetch active VPN servers

    if (empty($activeServers)) {
        logMessage('No active servers available.');
        echo json_encode(['state' => 0, 'message' => 'No active servers available.']);
        exit;
    }

    // Process each user in the data array
    foreach ($_POST['data'] as $userData) {
        $username = $userData['username']; // Get the username
        $password = isset($userData['password']) ? $userData['password'] : '';

        // Temporary test: Log initial status, update status, and log again
        $initialStatus = getUserStatus($core, $username);
        logMessage("Initial status for $username: " . ($initialStatus !== null ? $initialStatus : "Status not found"));

        if ($initialStatus === "ON" && $status == "OFF") {
            // Change status to OFF for testing and send delete request
            updateUserStatus($core, $username, "OFF");
            logMessage("Operation: Changed status from ON to OFF for $username.");

            // Send delete request to all active servers
            foreach ($activeServers as $serverIP) {
                $result = sendRequestToServer($serverIP, 'delete', $username);
                if ($result && $result['state'] == 1) {
                    logMessage("User deleted from server $serverIP: $username");
                } else {
                    logMessage("Failed to delete user from server $serverIP: $username. " . ($result['message'] ?? 'No response'));
                }
            }
        } elseif ($initialStatus === "OFF" && $status == "ON") {
            // Change status to ON for testing and send add request
            updateUserStatus($core, $username, "ON");
            logMessage("Operation: Changed status from OFF to ON for $username.");

            // Send add request to all active servers
            foreach ($activeServers as $serverIP) {
                $result = sendRequestToServer($serverIP, 'add', $username, $password);
                if ($result && $result['state'] == 1) {
                    logMessage("User added to server $serverIP: $username");
                } else {
                    logMessage("Failed to add user to server $serverIP: $username. " . ($result['message'] ?? 'No response'));
                }
            }
        } else {
            logMessage("No status change required for $username.");
        }

        // Log the updated status
        $updatedStatus = getUserStatus($core, $username);
        logMessage("Updated status for $username: " . ($updatedStatus !== null ? $updatedStatus : "Status not found"));
    }

    // Final response
    logMessage('SQL and VPN server processing in apiActivation.php complete.');
    echo json_encode(['state' => 1, 'message' => 'SQL and VPN server processing complete.']);
} else {
    logMessage('Invalid request: Missing required parameters.');
    echo json_encode(['state' => 0, 'message' => 'Invalid request.']);
}

// Log the end of the script
logMessage('apiActivation.php script ended.');
?>
