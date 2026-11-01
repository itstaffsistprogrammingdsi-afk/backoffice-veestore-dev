@echo off
setlocal
cd /d "%~dp0services\ai-analytics"

if not exist ".venv\Scripts\python.exe" (
  echo Virtual environment belum tersedia.
  echo Jalankan: python -m venv .venv
  echo Lalu: .venv\Scripts\python.exe -m pip install -r requirements.txt
  exit /b 1
)

echo Starting Immostore AI Analytics at http://127.0.0.1:8001
".venv\Scripts\python.exe" -m uvicorn app.main:app --host 127.0.0.1 --port 8001

