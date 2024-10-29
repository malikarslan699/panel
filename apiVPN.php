<?php 
require '../components/core.php'; // Include your Core class
$core = new Core(); // Create an instance of the Core class

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log file path
$logFile = '/home/u176398115/domains/kinge.shop/public_html/logs/apiVPN.log'; // Adjust this path as needed

// Function to log messages
function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

// Fetch active servers from the database
$activeServers = [];
$query = "SELECT serverIP FROM server WHERE isActive = 1";
$result = mysqli_query($core->getConnection(), $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $activeServers[] = $row['serverIP'];
    }
} else {
    logMessage('Error fetching active servers: ' . mysqli_error($core->getConnection()));
    echo json_encode(['state' => 0, 'message' => 'Error fetching active servers.']);
    exit;
}

// Check if there are active servers
if (empty($activeServers)) {
    logMessage('No active servers available.');
    echo json_encode(['state' => 0, 'message' => 'No active servers available.']);
    exit;
}

// Use the first active server for the request
$vpnServerUrl = 'http://' . $activeServers[0] . '/vpn.php'; // Use the first active server
logMessage('Using VPN server: ' . $vpnServerUrl);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted data
    $username = $_POST['user'];
    $password = $_POST['password'];
    $device = $_POST['device'];
    $days = $_POST['days'];
    $reseller = $_POST['reseller'];

    // Validate input
    if (empty($username) || empty($password)) {
        logMessage('Username and password are required.');
        echo json_encode(['state' => 0, 'message' => 'Username and password are required.']);
        exit;
    }

    // Check if the user already exists in the database
    if ($core->duplicateVPN($username) == 1) {
        logMessage('User already exists: ' . $username);
        echo json_encode(['state' => 0, 'message' => 'User already exists.']);
        exit;
    }

    // Check if the reseller has available limits
    if ($core->getResellerLimit($reseller) - ($days / 31) >= 0) {
        // Prepare data to send to the VPN server
        $data = [
            'user' => $username,
            'password' => $password,
            'action' => 'add' // Specify the action
        ];

        // Use cURL to send a request to the VPN server
        $ch = curl_init($vpnServerUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log the response from the VPN server
        logMessage('Response from VPN server: ' . $response);

        // Handle the response from the VPN server
        if ($httpCode == 200) {
            $res = json_decode($response, true);
            if ($res['state'] == 1) {
                // Add user to the database
                logMessage('Adding user to the database: ' . $username);
                echo $core->addVPNUser($username, $password, "VPN", $reseller, $days, $device);
            } else {
                logMessage('Failed to add user to VPN: ' . $res['message']);
                echo json_encode(['state' => 0, 'message' => 'Failed to add user to VPN.']);
            }
        } else {
            logMessage('Failed to communicate with VPN server. HTTP Code: ' . $httpCode);
            echo json_encode(['state' => 0, 'message' => 'Failed to communicate with VPN server.']);
        }
    } else {
        logMessage('Reseller limit exceeded for: ' . $reseller);
        echo json_encode(array("state" => 3));
    }
} else {
    logMessage('Invalid request method.');
    echo json_encode(['state' => 0, 'message' => 'Invalid request method.']);
}
?>
