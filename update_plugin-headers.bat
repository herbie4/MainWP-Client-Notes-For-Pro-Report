@echo off
setlocal enabledelayedexpansion

:: Define variables
set "pluginFile=mainwp-work-notes-proreports-extention/mainwp-work-notes-proreports-extention.php"   :: Path to main plugin file

:: Run PHP script to update plugin headers
php -f update_plugin_headers.php "%pluginFile%"

echo Plugin headers updated successfully!
pause
