# uLam nightly database backup
# Dumps ulam_db to C:\wamp64\backups\uLam\ (date-stamped) and prunes dumps older than 14 days.
# Registered in Task Scheduler as "uLam DB Backup" (daily 21:00) — see admin/README.md.

$mysqldump = 'C:\wamp64\bin\mysql\mysql8.3.0\bin\mysqldump.exe'
$backupDir = 'C:\wamp64\backups\uLam'
$database  = 'ulam_db'
$user      = 'root'
$retentionDays = 14

if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Force $backupDir | Out-Null
}

$stamp = Get-Date -Format 'yyyy-MM-dd_HHmm'
$outFile = Join-Path $backupDir "ulam_db_$stamp.sql"

& $mysqldump --user=$user --host=127.0.0.1 --port=3306 --single-transaction --routines --triggers $database | Out-File -FilePath $outFile -Encoding utf8

if ((Get-Item $outFile).Length -lt 10KB) {
    Write-Warning "Backup looks suspiciously small: $outFile"
    exit 1
}

# Prune old dumps
Get-ChildItem $backupDir -Filter 'ulam_db_*.sql' |
    Where-Object { $_.LastWriteTime -lt (Get-Date).AddDays(-$retentionDays) } |
    Remove-Item -Force -Confirm:$false

Write-Host "Backup OK: $outFile ($([math]::Round((Get-Item $outFile).Length / 1KB)) KB)"
