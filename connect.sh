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
