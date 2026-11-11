$ErrorActionPreference = 'Stop'
$ruta = 'C:\laragon\www\base-aracode\.env'
$original = [System.IO.File]::ReadAllText($ruta)
$respaldo = 'C:\laragon\www\base-aracode\.env.backup'
$utf8 = New-Object System.Text.UTF8Encoding($false)
if (-not (Test-Path $respaldo)) {
    [System.IO.File]::WriteAllText($respaldo, $original, $utf8)
    Write-Output 'respaldo=.env.backup'
} else {
    Write-Output 'respaldo=ya existia (no se sobrescribe)'
}
$nuevo = [regex]::Replace($original, '(?m)^APP_URL=[^\r\n]*', 'APP_URL=http://base-aracode.test')
if ($nuevo -eq $original) { throw 'No se encontro una linea APP_URL activa.' }
[System.IO.File]::WriteAllText($ruta, $nuevo, $utf8)
$limpioA = [regex]::Replace($original, '(?m)^APP_URL=[^\r\n]*', '')
$limpioB = [regex]::Replace($nuevo, '(?m)^APP_URL=[^\r\n]*', '')
$sha = [System.Security.Cryptography.SHA256]::Create()
$hA = [BitConverter]::ToString($sha.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($limpioA)))
$hB = [BitConverter]::ToString($sha.ComputeHash([System.Text.Encoding]::UTF8.GetBytes($limpioB)))
Write-Output ('resto_identico=' + ($hA -eq $hB))
Write-Output ('app_url_activas=' + ([regex]::Matches($nuevo, '(?m)^APP_URL=')).Count)
Write-Output ('lineas=' + ($nuevo -split "`n").Count)
