#!/bin/bash

# Define variables
USER="bret"
GROUP="www-data"

echo "Setting ownership to $USER:$GROUP..."
# 1. Set the owner to you and the group to the web server
sudo chown -R $USER:$GROUP .

echo "Setting directory and file permissions..."
# 2. Standard files: 664 (Owner/Group can read/write, others read)
# 3. Standard dirs:  775 (Owner/Group can read/write/traverse)
find . -type f -exec chmod 664 {} \;
find . -type d -exec chmod 775 {} \;

echo "Granting special write access to storage, bootstrap/cache, and database..."
# 4. Ensure the web server can write to Laravel's specific folders
sudo chgrp -R $GROUP storage bootstrap/cache database
sudo chmod -R ug+rwx storage bootstrap/cache database

# 5. SQLite Specific: The 'database' directory itself must be writable 
# so SQLite can create .sqlite-journal files during transactions.
sudo chmod 775 database
if [ -f database/database.sqlite ]; then
    sudo chmod 664 database/database.sqlite
fi

echo "Setting the GID bit..."
# 6. The "Magic": Ensure new files created in these dirs inherit the 'www-data' group
sudo find storage bootstrap/cache database -type d -exec chmod g+s {} \;

echo "Permissions updated successfully!"
