@echo off
REM Backup CLI - respalda la base completa local
REM Uso: backup.bat

set "MYSQL_CONFIG=C:\Users\Usuario\Desktop\PROPIAS\mi_catalogo\.my.cnf"
set "OUTPUT_DIR=C:\Users\Usuario\Desktop\PROPIAS\mi_catalogo\backups"

if not exist "%OUTPUT_DIR%" mkdir "%OUTPUT_DIR%"

set "TIMESTAMP=%DATE:/=-%_%TIME::=-%"
set "TIMESTAMP=%TIMESTAMP: =0%"
set "FILENAME=%OUTPUT_DIR%\backup_%TIMESTAMP%.sql"

echo Creando backup en %FILENAME% ...
"C:\xampp\mysql\bin\mysqldump" --defaults-extra-file="%MYSQL_CONFIG%" --single-transaction --routines --triggers catalogo_whatsapp > "%FILENAME%"

if %ERRORLEVEL% equ 0 (
    echo Backup completado: %FILENAME%
) else (
    echo Error al crear backup
    exit /b 1
)
