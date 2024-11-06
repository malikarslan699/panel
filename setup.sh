#!/bin/bash
# Step 1: Clone the GitHub repository
git clone https://github.com/malikarslan699/panel.git

# Step 2: Change directory to the cloned folder
cd panel || exit

# Step 3: Make all files executable
chmod +x *

# Step 4: Move vpn.php to the web server directory
mv vpn.php /var/www/html/
chmod +x /var/www/html/vpn.php

# Step 5: Create scripts directory for ocserv and move script files
mkdir -p /etc/ocserv/scripts
mv connect.sh db_config.sh /etc/ocserv/scripts/

# Step 6: Create log directory and file for ocserv
mkdir -p /var/log/ocserv/
touch /var/log/ocserv/connection.log

# Step 7: Edit sudoers file for www-data permissions
SUDO_FILE="/etc/sudoers"
if ! grep -q "www-data ALL=(ALL) NOPASSWD: /usr/bin/ocpasswd" "$SUDO_FILE"; then
    echo "www-data ALL=(ALL) NOPASSWD: /usr/bin/ocpasswd" >> "$SUDO_FILE"
fi
if ! grep -q "www-data ALL=(ALL) NOPASSWD: /etc/ocserv/ocpasswd" "$SUDO_FILE"; then
    echo "www-data ALL=(ALL) NOPASSWD: /etc/ocserv/ocpasswd" >> "$SUDO_FILE"
fi

# Step 8: Check and edit ocserv configuration for connect-script
OCSERV_CONF="/etc/ocserv/ocserv.conf"
CONNECT_SCRIPT_LINE='connect-script = "/etc/ocserv/scripts/connect.sh"'
if ! grep -q "$CONNECT_SCRIPT_LINE" "$OCSERV_CONF"; then
    echo "$CONNECT_SCRIPT_LINE" >> "$OCSERV_CONF"
fi

echo "Setup completed successfully."
