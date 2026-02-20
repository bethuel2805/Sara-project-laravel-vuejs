#!/bin/bash
chmod 664 /var/www/html/database/database.sqlite
chmod 775 /var/www/html/database
chown www-data:www-data /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/database
