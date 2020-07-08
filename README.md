# ESP32_project.

# wifi_ds_sleep_final2.ino:
ESP32 Arduino IDE code. Espressif SDK needed from https://github.com/espressif/arduino-esp32 or from Arduino Boards manager.

# esp_ui.php:
PHP script for receiving data from sensors.
# espdb.php:
Save data to a sqlite database. Create DB if it doesn't exist.
# esp_with_arg.php:
PHP-script for Munin-monitoring (work in progress).
# esp.sqlite:
Small sample SQLite database for you.

# notes:
On FEDORA systems, you need to install php-pdo package with dnf.
Also, if you enable public_html (userdir module) and have SELinux enabled, do:
chcon -R -t httpd_sys_rw_content_t /home/user/public_html/esp
That way, apache can have write access and create a database for you, per espdb.php.

# OneWire.h Arduino module:
https://github.com/PaulStoffregen/OneWire

