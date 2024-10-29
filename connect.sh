#!/bin/bash
#/etc/ocserv/scripts/connect.sh
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
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -se "SELECT password, isActive, start_date, end_date, days FROM account WHERE username='$username';"
}

# Get user data
user_data=$(fetch_user_data "$USERNAME")

# Check if user data is fetched successfully
if [ -z "$user_data" ]; then
    log_message "Invalid User $USERNAME not available in database."
    exit 1
fi

# Read fetched data
read -r password isActive start_date end_date days <<< "$user_data"

# Log fetched data
log_message "Fetched data - password: [REDACTED], isActive: $isActive, start_date: '$start_date', end_date: '$end_date', days: '$days'"

# Handle user based on isActive status
CURRENT_DATE=$(date '+%d/%m/%Y')  # Current date in DD/MM/YYYY format

if [ "$isActive" -eq 0 ]; then
    log_message "User $USERNAME is inactive. Activating user now."

    # Ensure the days value is a valid positive integer
    if [[ "$days" =~ ^[0-9]+$ ]] && [ "$days" -gt 0 ]; then
        # Update isActive to 1
        isActive=1
        
        # Set start_date to the current date in a format that can be used for calculations
        start_date=$(date '+%Y-%m-%d')  # Use YYYY-MM-DD format for calculations

        # Calculate end_date by adding the days to the start_date
        end_date=$(date -d "$start_date + $days days" '+%d/%m/%Y')  # Convert back to DD/MM/YYYY for logging and DB

        # Log the action before updating the database
        log_message "Activating user $USERNAME: Setting start_date to $start_date and end_date to $end_date."

        # Prepare the update SQL command
        update_command="UPDATE account SET isActive=1, start_date='$start_date', end_date='$end_date' WHERE username='$USERNAME';"
        
        # Log the SQL command
        log_message "Executing SQL: $update_command"

        # Update the user in the database
        if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "$update_command"; then
            log_message "User $USERNAME activated with start_date $start_date and end_date $end_date."
            echo "User $USERNAME is allowed to connect."
        else
            log_message "Failed to update user $USERNAME in the database."
            exit 1
        fi
    else
        log_message "Invalid days value for user $USERNAME. Terminating connection."
        exit 1
    fi
else
    # Existing user logic
    if [ -n "$end_date" ]; then
        # Convert end_date and CURRENT_DATE to YYYY-MM-DD format for comparison
        end_date_formatted=$(date -d "$end_date" '+%Y-%m-%d')
        current_date_formatted=$(date -d "$CURRENT_DATE" '+%Y-%m-%d')

        if [[ "$end_date_formatted" > "$current_date_formatted" ]]; then
            log_message "User $USERNAME is valid having expiry date $end_date, allowed to connect."
            echo "User $USERNAME is allowed to connect."
        else
            log_message "User $USERNAME has expired having expiry date $end_date. Terminated."
            exit 1
        fi
    else
        log_message "User $USERNAME has no end date set. Terminating connection."
        exit 1
    fi
fi

exit 0
