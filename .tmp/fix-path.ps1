# Reordena el PATH de la maquina para que PHP 8.5 de Laragon gane sobre 8.2.
# Requiere elevacion (HKLM). Guarda copia del valor original antes de escribir.
$ErrorActionPreference = 'Stop'

$rutaProyecto = 'C:\laragon\www\base-aracode'
$clave = 'SYSTEM\CurrentControlSet\Control\Session Manager\Environment'
$key = [Microsoft.Win32.Registry]::LocalMachine.OpenSubKey($clave, $true)
if (-not $key) { throw 'No se pudo abrir la clave de entorno de la maquina.' }

$original = $key.GetValue('Path', $null, [Microsoft.Win32.RegistryValueOptions]::DoNotExpandEnvironmentNames)
$respaldo = Join-Path $rutaProyecto '.tmp\path-machine-backup.txt'
Set-Content -LiteralPath $respaldo -Value $original -Encoding UTF8

$php85 = 'C:\laragon\bin\php\php-8.5_win'
$partes = @($original.Split(';') | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' })
# Deja 8.5 al inicio y descarta duplicados de esa misma ruta; el resto no se toca.
$partes = @($partes | Where-Object { $_ -ne $php85 })
$nuevo = (@($php85) + $partes) -join ';'

$key.SetValue('Path', $nuevo, [Microsoft.Win32.RegistryValueKind]::ExpandString)
$key.Close()

# Avisa a Explorer para que las terminales nuevas tomen el cambio.
Add-Type -Namespace Win32 -Name Env -MemberDefinition '[DllImport("user32.dll", SetLastError = true, CharSet = CharSet.Auto)] public static extern IntPtr SendMessageTimeout(IntPtr hWnd, uint Msg, UIntPtr wParam, string lParam, uint fuFlags, uint uTimeout, out UIntPtr lpdwResult);'
$resultado = [UIntPtr]::Zero
[void][Win32.Env]::SendMessageTimeout([IntPtr]0xffff, 0x1A, [UIntPtr]::Zero, 'Environment', 2, 5000, [ref]$resultado)

Write-Output ('RESPALDO=' + $respaldo)
Write-Output ('LONGITUD_ORIGINAL=' + $original.Length)
Write-Output ('LONGITUD_NUEVA=' + $nuevo.Length)
Write-Output ('PRIMERA_ENTRADA=' + $nuevo.Split(';')[0])
Write-Output ('PHP_LARAGON_EN_PATH=' + (($nuevo.Split(';') | Where-Object { $_ -match 'laragon\\bin\\php\\php-8\.' }) -join ' | '))
