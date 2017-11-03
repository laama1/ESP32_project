# ESP32_project
# ESP32 Arduino IDE code (not yet released).
# esp_ui.php: PHP script for receiving data.
# espdb.php: Save data to a sqlite database. Create DB if it doesn't exist.
# esp_with_arg.php: PHP-script for Munin.
# esp.sqlite: Small sample SQLite database for you.

# On FEDORA Systems (SELinux), you need to install php-pdo package with dnf.
# Also, if you enable public_html (userdir module), do:
# chcon -R -t httpd_sys_rw_content_t /home/user/public_html/esp
# That way, apache can create a database for you, per espdb.php.
