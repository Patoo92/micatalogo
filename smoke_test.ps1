param(
    [string]$BaseUrl = "http://localhost/micatalogo"
)

$ErrorActionPreference = "Stop"
$passed = 0
$failed = 0

function Test-Endpoint {
    param($Name, $Url, $ExpectedStatus = 200, $ExpectedContent = $null)
    try {
        $req = Invoke-WebRequest -Uri "$BaseUrl$Url" -UseBasicParsing -TimeoutSec 10
        if ($req.StatusCode -ne $ExpectedStatus) {
            Write-Host "FAIL $Name — esperaba $ExpectedStatus, obtuvo $($req.StatusCode)" -ForegroundColor Red
            $script:failed++
            return
        }
        if ($ExpectedContent -and $req.Content -notmatch $ExpectedContent) {
            Write-Host "FAIL $Name — no contiene '$ExpectedContent'" -ForegroundColor Red
            $script:failed++
            return
        }
        Write-Host "OK   $Name" -ForegroundColor Green
        $script:passed++
    } catch {
        Write-Host "FAIL $Name — $_" -ForegroundColor Red
        $script:failed++
    }
}

# Página pública de catálogo
Test-Endpoint "Catalogo" "/index.php?tienda=tienda-ejemplo" -ExpectedContent "tienda"

# 404 tienda inexistente
Test-Endpoint "404 tienda" "/index.php?tienda=no-existe" -ExpectedStatus 404

# Super admin login (debe devolver 200 con formulario)
Test-Endpoint "Login admin" "/login-admin.php" -ExpectedContent "Acceso Master"

# Login público
Test-Endpoint "Login publico" "/login.php" -ExpectedContent "Iniciar"

# Recuperar admin
Test-Endpoint "Recuperar admin" "/recuperar-admin.php" -ExpectedContent "Recuperar"

# Recuperar pass
Test-Endpoint "Recuperar pass" "/recuperar.php" -ExpectedContent "Recuperar"

# Headers de seguridad
try {
    $req = Invoke-WebRequest -Uri "$BaseUrl/index.php?tienda=tienda-ejemplo" -UseBasicParsing
    if ($req.Headers["Content-Security-Policy"]) {
        Write-Host "OK   CSP header presente" -ForegroundColor Green
        $passed++
    } else {
        Write-Host "WARN No hay CSP header" -ForegroundColor Yellow
        $failed++
    }
} catch { Write-Host "FAIL Header test — $_" -ForegroundColor Red; $failed++ }

Write-Host ""
Write-Host "=== Resumen: $passed pasaron, $failed fallaron ===" -ForegroundColor Cyan
if ($failed -gt 0) { exit 1 }
