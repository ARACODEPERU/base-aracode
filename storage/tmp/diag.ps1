Get-Process mysqld -ErrorAction SilentlyContinue | ForEach-Object { "PID=" + $_.Id + " PATH=" + $_.Path + " START=" + $_.StartTime }
