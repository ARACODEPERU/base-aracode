# Procesos de mis sondas de revisión (Chrome headless y node) que hayan quedado vivos.
$chrome = Get-CimInstance Win32_Process -Filter "name='chrome.exe'" |
    Where-Object { $_.CommandLine -like '*revisar-branding-empresa*' }

Write-Output ("chrome de la sonda: " + $chrome.Count)

foreach ($p in $chrome) {
    Write-Output ("  pid " + $p.ProcessId + " -> " + $p.CommandLine.Substring(0, [Math]::Min(110, $p.CommandLine.Length)))
}

$node = Get-CimInstance Win32_Process -Filter "name='node.exe'" |
    Where-Object { $_.CommandLine -like '*revisar-branding-empresa*' }

Write-Output ("node de la sonda: " + $node.Count)

foreach ($p in $node) {
    Write-Output ("  pid " + $p.ProcessId + " -> " + $p.CommandLine.Substring(0, [Math]::Min(110, $p.CommandLine.Length)))
}
