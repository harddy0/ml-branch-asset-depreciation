@echo off
setlocal EnableExtensions EnableDelayedExpansion

for %%I in ("%~dp0..") do set "PROJECT_ROOT=%%~fI"
set "ACTION=%~1"

set "PHP_BINARY="
set "TASK_NAME=ML Branch Daily Depreciation"
set "TASK_TIME=07:00"
set "TASK_WORKDIR=%PROJECT_ROOT%"

if exist "%PROJECT_ROOT%\.env" call :LoadEnv "%PROJECT_ROOT%\.env"

if defined PHP_BINARY_OVERRIDE set "PHP_BINARY=%PHP_BINARY_OVERRIDE%"
if not defined PHP_BINARY set "PHP_BINARY=C:\xampp\php\php.exe"
if not exist "%PHP_BINARY%" (
    echo PHP executable not found: %PHP_BINARY%
    exit /b 1
)

if not defined ACTION set "ACTION=MENU"
if /I "%ACTION%"=="/install" goto INSTALL
if /I "%ACTION%"=="--install" goto INSTALL
if /I "%ACTION%"=="/check" goto CHECK
if /I "%ACTION%"=="--check" goto CHECK
if /I "%ACTION%"=="/uninstall" goto UNINSTALL
if /I "%ACTION%"=="--uninstall" goto UNINSTALL
if /I "%ACTION%"=="/run" goto RUN
if /I "%ACTION%"=="--run" goto RUN
if /I "%ACTION%"=="MENU" goto MENU
goto RUN

:HEADER
cls
echo ========================================
echo Daily Depreciation Job
echo ========================================
echo Project root : %PROJECT_ROOT%
echo PHP binary   : %PHP_BINARY%
echo Script       : %PROJECT_ROOT%\scripts\run_daily_depreciation.php
echo Task name    : %TASK_NAME%
echo Task time    : %TASK_TIME%
echo Working dir  : %TASK_WORKDIR%
echo Mode         : %ACTION%
echo.
exit /b 0

:MENU
call :HEADER
echo Choose an action:
echo   [I] Install Windows scheduled task
echo   [C] Check config only
echo   [R] Run depreciation now
echo   [U] Uninstall Windows scheduled task
echo   [X] Exit
echo.
choice /C ICRUX /N /M "Select an option"
if errorlevel 5 goto EXIT_MENU
if errorlevel 4 goto UNINSTALL
if errorlevel 3 goto RUN
if errorlevel 2 goto CHECK
if errorlevel 1 goto INSTALL

:EXIT_MENU
echo.
echo No action selected. Exiting.
call :MaybePause
endlocal & exit /b 0

:CHECK
call :HEADER
echo Checking environment and scheduler inputs...
echo.
"%PHP_BINARY%" "%PROJECT_ROOT%\scripts\run_daily_depreciation.php" --check
set "EXIT_CODE=%ERRORLEVEL%"
echo.
echo Exit code: %EXIT_CODE%
call :MaybePause
endlocal & exit /b %EXIT_CODE%

:INSTALL
call :HEADER
echo Preparing Windows Task Scheduler registration...
echo This will create a Windows Scheduled Task that runs the PHP script every day.
echo Task Scheduler will run the PHP script directly without opening Command Prompt.
echo Task name: %TASK_NAME%
echo Task time: %TASK_TIME%
echo Working dir: %TASK_WORKDIR%
echo Run as    : SYSTEM
echo.
echo Verifying PHP and .env settings first...
"%PHP_BINARY%" "%PROJECT_ROOT%\scripts\run_daily_depreciation.php" --check
if errorlevel 1 (
    echo.
    echo Environment check failed. Task was not created.
    endlocal & exit /b 1
)

echo.
choice /C YN /M "Create the Windows scheduled task now"
if errorlevel 2 (
    echo Task creation cancelled.
    endlocal & exit /b 0
)

set "TASK_TR=\"%PHP_BINARY%\" \"%PROJECT_ROOT%\scripts\run_daily_depreciation.php\""

schtasks /Create /TN "%TASK_NAME%" /SC DAILY /ST %TASK_TIME% /RU SYSTEM /RL HIGHEST /TR "%TASK_TR%" /F >nul 2>&1
if errorlevel 1 (
    echo.
    echo SYSTEM task creation failed - likely no admin permission.
    echo Retrying as current user...
    schtasks /Create /TN "%TASK_NAME%" /SC DAILY /ST %TASK_TIME% /TR "%TASK_TR%" /F
    if errorlevel 1 (
        echo.
        echo Task creation failed.
        endlocal & exit /b 1
    )
    set "TASK_RUN_MODE=CURRENT_USER"
) else (
    set "TASK_RUN_MODE=SYSTEM"
)

echo.
echo Task created successfully.
if /I "%TASK_RUN_MODE%"=="SYSTEM" (
    echo Task Scheduler runs in background as SYSTEM.
) else (
    echo Task Scheduler runs as the current Windows user.
)
echo Action: %PHP_BINARY% %PROJECT_ROOT%\scripts\run_daily_depreciation.php
echo You can manage it later in Windows Task Scheduler under: %TASK_NAME%
call :MaybePause
endlocal & exit /b 0

:UNINSTALL
call :HEADER
echo Preparing to remove the Windows scheduled task...
echo Task name: %TASK_NAME%
echo.
choice /C YN /M "Remove the scheduled task now"
if errorlevel 2 (
    echo Task removal cancelled.
    call :MaybePause
    endlocal & exit /b 0
)

schtasks /Delete /TN "%TASK_NAME%" /F
if errorlevel 1 (
    echo.
    echo Task removal failed.
    call :MaybePause
    endlocal & exit /b 1
)

echo.
echo Task removed successfully.
call :MaybePause
endlocal & exit /b 0

:RUN
call :HEADER
echo Running the depreciation job now...
echo.
"%PHP_BINARY%" "%PROJECT_ROOT%\scripts\run_daily_depreciation.php" %*
set "EXIT_CODE=%ERRORLEVEL%"
echo.
echo Exit code: %EXIT_CODE%
if /I "%ACTION%"=="MENU" call :MaybePause
endlocal & exit /b %EXIT_CODE%

:LoadEnv
for /f "usebackq tokens=1,* delims==" %%A in (`findstr /r /v "^[ ]*#" "%~1"`) do (
    set "ENV_KEY=%%A"
    set "ENV_VALUE=%%B"

    for /f "tokens=* delims= " %%K in ("!ENV_KEY!") do set "ENV_KEY=%%K"
    for /f "tokens=* delims= " %%V in ("!ENV_VALUE!") do set "ENV_VALUE=%%V"

    if /I "!ENV_KEY!"=="PHP_BINARY" set "PHP_BINARY=!ENV_VALUE!"
    if /I "!ENV_KEY!"=="TASK_NAME" set "TASK_NAME=!ENV_VALUE!"
    if /I "!ENV_KEY!"=="TASK_SCHEDULER_RUN_TIME" set "TASK_TIME=!ENV_VALUE!"
    if /I "!ENV_KEY!"=="TASK_SCHEDULER_WORKING_DIR" set "TASK_WORKDIR=!ENV_VALUE!"
)
exit /b 0

:MaybePause
if defined NO_PAUSE exit /b 0
pause
exit /b 0