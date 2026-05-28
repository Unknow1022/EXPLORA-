# =============================================================
# AuditoriaSeguridad.ps1
# Script de Auditoria Basica de Seguridad del Sistema (Windows)
# Uso: Ejecutar como Administrador en PowerShell
# =============================================================

# Requiere privilegios de administrador
if (-NOT ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")) {
    Write-Warning "Este script debe ejecutarse como Administrador. Reiniciando con privilegios elevados..."
    Start-Process powershell.exe "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`"" -Verb RunAs
    exit
}

# Configurar codificacion de salida a UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# --- Cabecera ---
Clear-Host
$fecha = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "        AUDITORIA DE SEGURIDAD DEL SISTEMA - WINDOWS            " -ForegroundColor Cyan
Write-Host "        Fecha: $fecha" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""

# Ruta para guardar el reporte
$reportPath = "$PSScriptRoot\reporte_seguridad_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
$resultados = @()
$resultados += "REPORTE DE AUDITORIA DE SEGURIDAD - $fecha"
$resultados += "================================================"

# ---------------------------------------------------------------
# 1. INFORMACION DEL SISTEMA
# ---------------------------------------------------------------
Write-Host "[1/6] Recopilando informacion del sistema..." -ForegroundColor Yellow
$resultados += "`n[1] INFORMACION DEL SISTEMA"
$resultados += "---------------------------------"

$os = Get-CimInstance -ClassName Win32_OperatingSystem
$cs = Get-CimInstance -ClassName Win32_ComputerSystem
$infoSistema = @(
    "  Nombre del equipo  : $($cs.Name)",
    "  Sistema Operativo  : $($os.Caption)",
    "  Version            : $($os.Version)",
    "  Arquitectura       : $($os.OSArchitecture)",
    "  Ultimo inicio      : $($os.LastBootUpTime)"
)
$infoSistema | ForEach-Object {
    Write-Host $_ -ForegroundColor Gray
    $resultados += $_
}

# ---------------------------------------------------------------
# 2. ESTADO DEL FIREWALL
# ---------------------------------------------------------------
Write-Host ""
Write-Host "[2/6] Comprobando el estado del Firewall de Windows..." -ForegroundColor Yellow
$resultados += "`n[2] ESTADO DEL FIREWALL"
$resultados += "---------------------------------"

$firewallProfiles = Get-NetFirewallProfile
foreach ($profile in $firewallProfiles) {
    if ($profile.Enabled -eq "True") {
        $msg = "  [OK] Perfil '$($profile.Name)': ACTIVO"
        Write-Host $msg -ForegroundColor Green
    } else {
        $msg = "  [ADVERTENCIA] Perfil '$($profile.Name)': INACTIVO"
        Write-Host $msg -ForegroundColor Red
    }
    $resultados += $msg
}

# ---------------------------------------------------------------
# 3. ESTADO DEL ANTIVIRUS (WINDOWS DEFENDER)
# ---------------------------------------------------------------
Write-Host ""
Write-Host "[3/6] Comprobando Windows Defender..." -ForegroundColor Yellow
$resultados += "`n[3] ESTADO DEL ANTIVIRUS (Windows Defender)"
$resultados += "---------------------------------"

try {
    $defender = Get-MpComputerStatus

    $checks = @{
        "Proteccion en tiempo real" = $defender.RealTimeProtectionEnabled
        "Proteccion en la nube"     = $defender.IsTamperProtected
        "Definiciones actualizadas" = ($defender.AntivirusSignatureAge -le 3)
    }

    foreach ($check in $checks.GetEnumerator()) {
        if ($check.Value -eq $true) {
            $msg = "  [OK] $($check.Key): ACTIVA"
            Write-Host $msg -ForegroundColor Green
        } else {
            $msg = "  [ADVERTENCIA] $($check.Key): INACTIVA o desactualizada"
            Write-Host $msg -ForegroundColor Red
        }
        $resultados += $msg
    }

    $sigAge = "  Antiguedad de firmas de virus: $($defender.AntivirusSignatureAge) dia(s)"
    Write-Host $sigAge -ForegroundColor Gray
    $resultados += $sigAge

} catch {
    $msg = "  [INFO] No se pudo obtener estado de Defender (puede usar otro antivirus)."
    Write-Host $msg -ForegroundColor DarkGray
    $resultados += $msg
}

# ---------------------------------------------------------------
# 4. PUERTOS TCP ABIERTOS EN ESCUCHA
# ---------------------------------------------------------------
Write-Host ""
Write-Host "[4/6] Listando puertos TCP en escucha..." -ForegroundColor Yellow
$resultados += "`n[4] PUERTOS TCP ABIERTOS (en escucha)"
$resultados += "---------------------------------"

$openPorts = Get-NetTCPConnection -State Listen |
    Select-Object LocalAddress, LocalPort, @{Name="Proceso";Expression={(Get-Process -Id $_.OwningProcess -ErrorAction SilentlyContinue).ProcessName}} |
    Sort-Object LocalPort -Unique

if ($openPorts) {
    foreach ($p in $openPorts) {
        $msg = "  [!] $($p.LocalAddress):$($p.LocalPort)  -> Proceso: $($p.Proceso)"
        Write-Host $msg -ForegroundColor DarkYellow
        $resultados += $msg
    }
    $nota = "  NOTA: Verifica que todos estos puertos correspondan a servicios necesarios."
    Write-Host $nota -ForegroundColor Cyan
    $resultados += $nota
} else {
    $msg = "  [OK] No se encontraron puertos TCP en escucha."
    Write-Host $msg -ForegroundColor Green
    $resultados += $msg
}

# ---------------------------------------------------------------
# 5. ACTUALIZACIONES RECIENTES DEL SISTEMA
# ---------------------------------------------------------------
Write-Host ""
Write-Host "[5/6] Verificando actualizaciones recientes..." -ForegroundColor Yellow
$resultados += "`n[5] ACTUALIZACIONES RECIENTES (ultimas 5)"
$resultados += "---------------------------------"

try {
    $updates = Get-HotFix | Sort-Object InstalledOn -Descending | Select-Object -First 5
    if ($updates) {
        foreach ($u in $updates) {
            $msg = "  [OK] $($u.HotFixID) - Instalado: $($u.InstalledOn) - $($u.Description)"
            Write-Host $msg -ForegroundColor Green
            $resultados += $msg
        }
    } else {
        $msg = "  [ADVERTENCIA] No se encontraron actualizaciones recientes instaladas."
        Write-Host $msg -ForegroundColor Red
        $resultados += $msg
    }
} catch {
    $msg = "  [ERROR] No se pudo obtener la lista de actualizaciones."
    Write-Host $msg -ForegroundColor DarkGray
    $resultados += $msg
}

# ---------------------------------------------------------------
# 6. USUARIOS CON PRIVILEGIOS DE ADMINISTRADOR
# ---------------------------------------------------------------
Write-Host ""
Write-Host "[6/6] Verificando usuarios con privilegios de Administrador..." -ForegroundColor Yellow
$resultados += "`n[6] USUARIOS EN EL GRUPO ADMINISTRADORES"
$resultados += "---------------------------------"

try {
    $admins = Get-LocalGroupMember -Group "Administrators" | Select-Object Name, PrincipalSource
    foreach ($admin in $admins) {
        $msg = "  [!] Usuario Admin: $($admin.Name)  (Origen: $($admin.PrincipalSource))"
        Write-Host $msg -ForegroundColor DarkYellow
        $resultados += $msg
    }
    $nota = "  NOTA: Asegúrate de que solo los usuarios necesarios tengan acceso de Administrador."
    Write-Host $nota -ForegroundColor Cyan
    $resultados += $nota
} catch {
    $msg = "  [ERROR] No se pudo obtener la lista de administradores."
    Write-Host $msg -ForegroundColor DarkGray
    $resultados += $msg
}

# ---------------------------------------------------------------
# GUARDAR REPORTE
# ---------------------------------------------------------------
Write-Host ""
Write-Host "================================================================" -ForegroundColor Cyan
try {
    $resultados | Out-File -FilePath $reportPath -Encoding UTF8
    Write-Host "  Reporte guardado en: $reportPath" -ForegroundColor Green
} catch {
    Write-Host "  [ERROR] No se pudo guardar el reporte: $_" -ForegroundColor Red
}

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host "                 AUDITORIA COMPLETADA                          " -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Presiona cualquier tecla para salir..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
