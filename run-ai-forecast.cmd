@echo off
setlocal
cd /d "%~dp0services\ai-analytics"
".venv\Scripts\python.exe" -m jobs.run_forecast

