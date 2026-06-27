@echo off
REM Simple WoltLab Plugin Manager — Windows launcher (Git Bash required)
setlocal
set "HERE=%~dp0"
set "HERE=%HERE:~0,-1%"

where bash >nul 2>&1
if errorlevel 1 (
    echo [SWPM] Git Bash not found. Install Git for Windows: https://git-scm.com/download/win
    exit /b 1
)

bash "%HERE%/tools.sh" %*
