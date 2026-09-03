@echo off
SET BACKUP_DIR=C:\Users\%USERNAME%\Google Drive\My Drive\KARISMA Backup
SET DATE_STR=%date:~10,4%-%date:~4,2%-%date:~7,2%
SET TIME_STR=%time:~0,2%-%time:~3,2%
SET FILENAME=backup_%DATE_STR%.sql
SET MYSQL_PATH=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin
SET DB_NAME=terminal_db
SET DB_USER=root
SET DB_PASS=

:: Buat folder backup kalau belum ada
if not exist "%BACKUP_DIR%" mkdir "%BACKUP_DIR%"

:: Jalankan backup
"%MYSQL_PATH%\mysqldump.exe" -u %DB_USER% -p%DB_PASS% %DB_NAME% > "%BACKUP_DIR%\%FILENAME%"

:: Hapus backup lebih dari 30 hari
forfiles /p "%BACKUP_DIR%" /s /m *.sql /d -30 /c "cmd /c del @path" 2>nul

echo Backup selesai: %FILENAME%
