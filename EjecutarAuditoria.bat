@echo off
:: ============================================================
:: EjecutarAuditoria.bat
:: Lanzador para AuditoriaSeguridad.ps1
:: Haz doble clic en este archivo para iniciar la auditoria.
:: ============================================================

echo Iniciando Auditoria de Seguridad del Sistema...
echo Este proceso puede tardar unos segundos. Por favor espere.
echo.

:: Ejecutar el script de PowerShell con privilegios de Administrador
:: y permitir la ejecucion de scripts sin cambiar la politica global del sistema
PowerShell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process PowerShell -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File ""%~dp0AuditoriaSeguridad.ps1""' -Verb RunAs"
