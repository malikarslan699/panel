<?php
// vpn.php

// Log file path
$logFile = '/path/to/your/logfile.log'; // Adjust this path as needed

// Function to log messages
function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the posted data
    $username = isset($_POST['user']) ? $_POST['user'] : null;
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $action = isset($_POST['action']) ? $_POST['action'] : null;

    // Validate input based on action
    if ($action === 'add') {
        // For 'add' action, username, password, and action are required
        if (empty($username) || empty($password) || empty($action)) {
            echo json_encode(['state' => 0, 'message' => 'Username, password, and action are required for adding a user.']);
            exit;
        }
    } elseif (in_array($action, ['ban', 'unban', 'delete'])) {
        // For 'ban', 'unban', and 'delete', only username and action are required
        if (empty($username) || empty($action)) {
            echo json_encode(['state' => 0, 'message' => 'Username and action are required for this operation.']);
            exit;
        }
        // Ignore password if it is provided
        $password = null;
    } else {
        echo json_encode(['state' => 0, 'message' => 'Invalid action specified.']);
        logMessage("Invalid action specified: $action");
        exit;
    }

    // Path to the ocpasswd file
    $ocpasswdFile = '/etc/ocserv/ocpasswd';

    // Log the action being performed
    logMessage("Action: $action, User: $username");

    // Determine the action to perform
    switch ($action) {
        case 'add':
            if (addUserToOcpasswd($username, $password, $ocpasswdFile)) {
                echo json_encode(['state' => 1, 'message' => 'User added successfully.']);
                logMessage("User added: $username");
            } else {
                echo json_encode(['state' => 0, 'message' => 'Failed to add user.']);
                logMessage("Failed to add user: $username");
            }
            break;

        case 'ban':
            if (banUserInOcpasswd($username, $ocpasswdFile)) {
                echo json_encode(['state' => 1, 'message' => 'User banned successfully.']);
                logMessage("User banned: $username");
            } else {
                echo json_encode(['state' => 0, 'message' => 'Failed to ban user.']);
                logMessage("Failed to ban user: $username");
            }
            break;

        case 'unban':
            if (unbanUserInOcpasswd($username, $ocpasswdFile)) {
                echo json_encode(['state' => 1, 'message' => 'User unbanned successfully.']);
                logMessage("User unbanned: $username");
            } else {
                echo json_encode(['state' => 0, 'message' => 'Failed to unban user.']);
                logMessage("Failed to unban user: $username");
            }
            break;

        case 'delete':
            if (deleteUserInOcpasswd($username, $ocpasswdFile)) {
                echo json_encode(['state' => 1, 'message' => 'User deleted successfully.']);
                logMessage("User deleted: $username");
            } else {
                echo json_encode(['state' => 0, 'message' => 'Failed to delete user.']);
                logMessage("Failed to delete user: $username");
            }
            break;
    }
} else {
    echo json_encode(['state' => 0, 'message' => 'Invalid request method.']);
    logMessage('Invalid request method.');
}

// Function to add user to ocpasswd
function addUserToOcpasswd($username, $password, $filePath) {
    $command = "echo " . escapeshellarg($password) . " | sudo ocpasswd -c " . escapeshellarg($filePath) . " " . escapeshellarg($username);
    exec($command, $output, $return_var);
    return $return_var === 0; // Return true if the command was successful
}

// Function to ban user in ocpasswd
function banUserInOcpasswd($username, $filePath) {
    $command = "sudo ocpasswd -c " . escapeshellarg($filePath) . " -l " . escapeshellarg($username);
    exec($command, $output, $return_var);
    return $return_var === 0;
}

// Function to unban user in ocpasswd
function unbanUserInOcpasswd($username, $filePath) {
    $command = "sudo ocpasswd -c " . escapeshellarg($filePath) . " -u " . escapeshellarg($username);
    exec($command, $output, $return_var);
    return $return_var === 0;
}

// Function to delete user in ocpasswd
function deleteUserInOcpasswd($username, $filePath) {
    $command = "sudo ocpasswd -c " . escapeshellarg($filePath) . " -d " . escapeshellarg($username);
    exec($command, $output, $return_var);
    return $return_var === 0;
}
?>
