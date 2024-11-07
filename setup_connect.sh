#!/bin/bash

# Step 0: Check if MySQL is installed and install it if not
if ! mysql --version > /dev/null 2>&1; then
    echo "MySQL is not installed. Installing MySQL..."
    sudo apt update
    sudo apt install mysql-server -y
    echo "MySQL has been installed successfully."
else
    echo "MySQL is already installed."
fi

# Step 1: Ensure directories and files exist with appropriate permissions

# Create the necessary directories and files if they do not exist
sudo mkdir -p /etc/ocserv/scripts
sudo mkdir -p /var/log/ocserv
sudo touch /etc/ocserv/scripts/connect.sh
sudo touch /etc/ocserv/scripts/db_config.sh
sudo touch /var/log/ocserv/connection.log

# Set permissions
sudo chmod +x /etc/ocserv/scripts
sudo chmod +x /etc/ocserv/scripts/connect.sh
sudo chmod +x /etc/ocserv/scripts/db_config.sh
sudo chmod +x /var/www/html/vpn.php

echo "Initial setup of directories, files, and permissions completed."

# Step 2: Input for MySQL credentials
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

# Step 3: Downloading vpn.php
VPN_PHP_PATH="/var/www/html/vpn.php"
if [ -f "$VPN_PHP_PATH" ]; then
    sudo rm "$VPN_PHP_PATH"  # Remove existing file if it exists
fi
wget -O "$VPN_PHP_PATH" https://raw.githubusercontent.com/malikarslan699/panel/main/vpn.php
mv vpn.php /var/www/html/
sudo chmod +x "$VPN_PHP_PATH"

# Step 4: Downloading connect.sh
CONNECT_SH_PATH="/etc/ocserv/scripts/connect.sh"
if [ -f "$CONNECT_SH_PATH" ]; then
    sudo rm "$CONNECT_SH_PATH"  # Remove existing file if it exists
fi
wget -O "$CONNECT_SH_PATH" https://raw.githubusercontent.com/malikarslan699/panel/main/connect.sh
mv connect.sh /etc/ocserv/scripts/
sudo chmod +x "$CONNECT_SH_PATH"

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
systemctl restart ocserv
echo "Setup completed successfully."
