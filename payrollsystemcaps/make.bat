@echo off
:: Check if a name was provided
if "%~1"=="" (
    echo Usage: make migration_name
    exit /b
)

:: Run a tiny PHP script to call the make function from tinker.php
php -r "require 'tinker.php'; make('%~1');"