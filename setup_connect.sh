#!/bin/bash

# Step 0: Ensure directories and files exist with appropriate permissions

# Create the necessary directories and files if they do not exist
sudo mkdir -p /etc/ocserv/scripts
sudo touch /etc/ocserv/scripts/connect.sh
sudo touch /etc/ocserv/scripts/db_config.sh
sudo touch /var/log/ocserv/connection.log

# Set permissions
sudo chmod +x /etc/ocserv/scripts
sudo chmod +x /etc/ocserv/scripts/connect.sh
sudo chmod +x /var/www/html/vpn.php

echo "Initial setup of directories, files, and permissions completed."

# Step 1: Input for MySQL credentials
echo "Enter MySQL Host:"
read DB_HOST

echo "Enter MySQL Username:"
read DB_USER

echo "Enter MySQL Password:"
read -s DB_PASS  # This hides the password input for security reasons

echo "Enter MySQL Database Name:"
read DB_NAME

# Append credentials to the file
echo -e "# MySQL credentials\nDB_HOST=\"$DB_HOST\"\nDB_USER=\"$DB_USER\"\nDB_PASS=\"$DB_PASS\"\nDB_NAME=\"$DB_NAME\"" | sudo tee -a /etc/ocserv/scripts/db_config.sh > /dev/null

echo "MySQL credentials have been successfully added to the file."

# Step 3: Create vpn.php file and move it to the web server directory
if [ -f /var/www/html/vpn.php ]; then
    echo "/var/www/html/vpn.php already exists, replacing it..."
    sudo rm /var/www/html/vpn.php
fi

# Create vpn.php file
cat <<EOF | sudo tee /var/www/html/vpn.php > /dev/null
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
EOF

chmod +x /var/www/html/vpn.php

# Step 4: Create connect.sh file and move it to the correct directory
if [ -f /etc/ocserv/scripts/connect.sh ]; then
    echo "/etc/ocserv/scripts/connect.sh already exists, replacing it..."
    sudo rm /etc/ocserv/scripts/connect.sh
fi

# Create connect.sh file
cat <<EOF | sudo tee /etc/ocserv/scripts/connect.sh > /dev/null
#!/bin/bash
#/etc/ocserv/scripts/connect.sh
##Update in ocserv
#auth = "plain[passwd=/etc/ocserv/ocpasswd]"
#connect-script = "/etc/ocserv/scripts/connect.sh"

# Set the timezone to Asia/Dubai
export TZ=Asia/Dubai

# Function to log messages
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') | $1" >> /var/log/ocserv/connection.log
}

# Load database credentials
source /etc/ocserv/scripts/db_config.sh

# Fetch username from environment variable
USERNAME="${USERNAME}"

# Check if USERNAME is set
if [ -z "$USERNAME" ]; then
    log_message "Username is not set, terminating."
    exit 1
fi

# Fetch user data from the database
fetch_user_data() {
    local username="$1"
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -se "SELECT isActive, end_date FROM account WHERE username='$username';"
}

# Get user data
user_data=$(fetch_user_data "$USERNAME")

# Check if user data is fetched successfully
if [ -z "$user_data" ]; then
    log_message "Invalid User $USERNAME not available in database."
    exit 1
fi

# Read fetched data
read -r isActive end_date <<< "$user_data"

# Log fetched data
log_message "Fetched data for user $USERNAME - isActive: $isActive, end_date: '$end_date'"

# Get the current date in YYYY-MM-DD format
CURRENT_DATE=$(date '+%Y-%m-%d')

# Check if isActive is 0
if [ "$isActive" -eq 0 ]; then
    log_message "User $USERNAME is inactive. Terminating connection."
    exit 1
fi

# Check if end_date is valid
if [ -n "$end_date" ]; then
    # Convert end_date from DD/MM/YYYY to YYYY-MM-DD for comparison
    end_date_formatted=$(date -d "$(echo $end_date | awk -F'/' '{print $3"-"$2"-"$1}')" '+%Y-%m-%d')

    # Log the formatted dates for debugging
    log_message "Current Date: $CURRENT_DATE, End Date: $end_date_formatted"

    # Compare end_date with CURRENT_DATE
    if [[ "$end_date_formatted" > "$CURRENT_DATE" ]]; then
        log_message "User $USERNAME is valid with expiry date $end_date, allowed to connect."
        echo "User $USERNAME is allowed to connect."
    else
        log_message "User $USERNAME has expired with expiry date $end_date. Terminated."
        exit 1
    fi
else
    log_message "User $USERNAME has no end date set. Terminating connection."
    exit 1
fi

exit 0
EOF

chmod +x /etc/ocserv/scripts/connect.sh

# Step 5: Edit sudoers file for www-data permissions
SUDO_FILE="/etc/sudoers"
if ! grep -q "www-data ALL=(ALL) NOPASSWD: /usr/bin/ocpasswd" "$SUDO_FILE"; then
    echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/ocpasswd" | sudo tee -a "$SUDO_FILE"
fi
if ! grep -q "www-data ALL=(ALL) NOPASSWD: /etc/ocserv/ocpasswd" "$SUDO_FILE"; then
    echo "www-data ALL=(ALL) NOPASSWD: /etc/ocserv/ocpasswd" | sudo tee -a "$SUDO_FILE"
fi

# Step 6: Check and edit ocserv configuration for connect-script
OCSERV_CONF="/etc/ocserv/ocserv.conf"
CONNECT_SCRIPT_LINE='connect-script = "/etc/ocserv/scripts/connect.sh"'
if ! grep -q "$CONNECT_SCRIPT_LINE" "$OCSERV_CONF"; then
    echo "$CONNECT_SCRIPT_LINE" | sudo tee -a "$OCSERV_CONF"
fi

echo "Setup completed successfully."
