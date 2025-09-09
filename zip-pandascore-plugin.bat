@echo off
REM PandaScore Tracker Plugin Packaging Script (Batch Version)
REM This script creates a distributable ZIP file of the PandaScore Tracker WordPress plugin

echo.
echo ===================================================
echo  PandaScore Tracker Plugin Packager - by Deejay Dev
echo ===================================================
echo.

REM Set variables
set PLUGIN_DIR=wp-content\plugins\pandascore-tracker
set OUTPUT_FILE=pandascore-tracker-plugin.zip
set TEMP_DIR=temp-plugin-build

REM Check if plugin directory exists
if not exist "%PLUGIN_DIR%" (
    echo ERROR: Plugin directory not found: %PLUGIN_DIR%
    echo Please make sure you're running this script from the WordPress root directory.
    pause
    exit /b 1
)

echo [1/4] Checking plugin files...

REM Check main plugin file
if not exist "%PLUGIN_DIR%\pandascore-tracker.php" (
    echo ERROR: Main plugin file not found: %PLUGIN_DIR%\pandascore-tracker.php
    pause
    exit /b 1
)

echo   - Main plugin file found
if exist "%PLUGIN_DIR%\css" (
    echo   - CSS directory found
) else (
    echo   - WARNING: CSS directory not found
)
if exist "%PLUGIN_DIR%\js" (
    echo   - JS directory found
) else (
    echo   - WARNING: JS directory not found
)
if exist "%PLUGIN_DIR%\templates" (
    echo   - Templates directory found
) else (
    echo   - WARNING: Templates directory not found
)
if exist "%PLUGIN_DIR%\includes" (
    echo   - Includes directory found
) else (
    echo   - WARNING: Includes directory not found
)
if exist "%PLUGIN_DIR%\docs" (
    echo   - Docs directory found
) else (
    echo   - WARNING: Docs directory not found
)
if exist "%PLUGIN_DIR%\README.md" (
    echo   - README.md found
) else (
    echo   - WARNING: README.md not found
)
if exist "%PLUGIN_DIR%\uninstall.php" (
    echo   - uninstall.php found
) else (
    echo   - WARNING: uninstall.php not found
)

echo.
echo [2/4] Preparing build directory...

REM Clean up any existing temp directory
if exist "%TEMP_DIR%" (
    rmdir /s /q "%TEMP_DIR%"
)

REM Create temp directory structure (files go directly in temp root for WordPress compatibility)
mkdir "%TEMP_DIR%"

echo   - Created temporary build directory

echo.
echo [3/4] Copying plugin files...

REM Copy main plugin file directly to temp root
copy "%PLUGIN_DIR%\pandascore-tracker.php" "%TEMP_DIR%\" >nul
echo   - Copied pandascore-tracker.php

REM Copy uninstall.php if it exists
if exist "%PLUGIN_DIR%\uninstall.php" (
    copy "%PLUGIN_DIR%\uninstall.php" "%TEMP_DIR%\" >nul
    echo   - Copied uninstall.php
)

REM Copy CSS directory if it exists
if exist "%PLUGIN_DIR%\css" (
    xcopy "%PLUGIN_DIR%\css" "%TEMP_DIR%\css" /e /i /q >nul
    echo   - Copied CSS directory
)

REM Copy JS directory if it exists
if exist "%PLUGIN_DIR%\js" (
    xcopy "%PLUGIN_DIR%\js" "%TEMP_DIR%\js" /e /i /q >nul
    echo   - Copied JS directory
)

REM Copy Templates directory if it exists
if exist "%PLUGIN_DIR%\templates" (
    xcopy "%PLUGIN_DIR%\templates" "%TEMP_DIR%\templates" /e /i /q >nul
    echo   - Copied Templates directory
)

REM Copy Includes directory if it exists
if exist "%PLUGIN_DIR%\includes" (
    xcopy "%PLUGIN_DIR%\includes" "%TEMP_DIR%\includes" /e /i /q >nul
    echo   - Copied Includes directory
)

REM Copy Docs directory if it exists
if exist "%PLUGIN_DIR%\docs" (
    xcopy "%PLUGIN_DIR%\docs" "%TEMP_DIR%\docs" /e /i /q >nul
    echo   - Copied Docs directory
)

REM Copy README.md if it exists
if exist "%PLUGIN_DIR%\README.md" (
    copy "%PLUGIN_DIR%\README.md" "%TEMP_DIR%\" >nul
    echo   - Copied README.md
) else (
    echo   - WARNING: README.md not found, creating basic README.txt
    REM Create a simple README.txt as fallback
    (
    echo PandaScore Tracker WordPress Plugin
    echo ===================================
    echo.
    echo Installation Instructions:
    echo 1. Upload via WordPress Admin ^> Plugins ^> Add New ^> Upload Plugin
    echo 2. Or extract to your /wp-content/plugins/pandascore-tracker/ directory
    echo 3. Activate the plugin through the 'Plugins' menu in WordPress
    echo 4. Go to Settings ^> PandaScore Tracker to configure your API key
    echo 5. Use the shortcode [pandascore_tracker] in your posts or pages
    echo.
    echo Version: 1.0
    echo Author: Deejay Dev
    echo.
    echo For support, contact the developer.
    ) > "%TEMP_DIR%\README.txt"
    echo   - Created fallback README.txt
)

echo.
echo [4/4] Creating ZIP archive...

REM Remove existing ZIP if it exists
if exist "%OUTPUT_FILE%" (
    del "%OUTPUT_FILE%"
    echo   - Removed existing ZIP file
)

REM Create ZIP using PowerShell (available on Windows 10+)
powershell -command "Compress-Archive -Path '%TEMP_DIR%\*' -DestinationPath '%OUTPUT_FILE%' -CompressionLevel Optimal" 2>nul

if exist "%OUTPUT_FILE%" (
    echo   - Created ZIP archive: %OUTPUT_FILE%
    
    REM Get file size
    for %%A in ("%OUTPUT_FILE%") do set FILE_SIZE=%%~zA
    set /a FILE_SIZE_KB=%FILE_SIZE%/1024
    echo   - File size: %FILE_SIZE_KB% KB
    
    echo.
    echo ========================================
    echo  SUCCESS! Plugin packaged successfully
    echo ========================================
    echo.
    echo Output file: %OUTPUT_FILE%
    echo.
    echo Ready for distribution! Users can:
    echo 1. Upload via WordPress Admin ^> Plugins ^> Add New ^> Upload Plugin
    echo 2. Extract to wp-content/plugins/ directory manually
    echo.
) else (
    echo ERROR: Failed to create ZIP archive
    echo This might be due to PowerShell not being available or permissions issues.
    echo Please try using the PowerShell script instead: zip-pandascore-plugin.ps1
    pause
    exit /b 1
)

REM Clean up
echo Cleaning up temporary files...
rmdir /s /q "%TEMP_DIR%"

echo.
echo Packaging completed!
pause
