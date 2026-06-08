param(
    [Parameter(Position = 0)]
    [string] $Command = "help",

    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $Rest = @()
)

$ErrorActionPreference = "Stop"

$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$ComposeFile = Join-Path $ProjectRoot "docker-compose.local.yml"

function Invoke-Compose {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]] $ComposeArgs
    )

    & docker compose -f $ComposeFile @ComposeArgs
    exit $LASTEXITCODE
}

function Invoke-App {
    param(
        [Parameter(ValueFromRemainingArguments = $true)]
        [string[]] $AppArgs
    )

    & docker compose -f $ComposeFile exec app @AppArgs
    exit $LASTEXITCODE
}

switch ($Command) {
    "up" {
        Invoke-Compose up -d --build
    }
    "start" {
        Invoke-Compose up -d
    }
    "stop" {
        Invoke-Compose stop
    }
    "down" {
        Invoke-Compose down
    }
    "restart" {
        Invoke-Compose restart app queue scheduler
    }
    "ps" {
        Invoke-Compose ps
    }
    "logs" {
        Invoke-Compose logs @Rest
    }
    "shell" {
        Invoke-App bash
    }
    "php" {
        Invoke-App php @Rest
    }
    "composer" {
        Invoke-App composer @Rest
    }
    "artisan" {
        Invoke-App php artisan @Rest
    }
    "test" {
        Invoke-App php artisan test @Rest
    }
    "migrate-pretend" {
        Invoke-App php artisan migrate --pretend @Rest
    }
    "pint" {
        Invoke-App vendor/bin/pint @Rest
    }
    "pint-test" {
        Invoke-App vendor/bin/pint --test @Rest
    }
    default {
        Write-Host "DYCRM local helper"
        Write-Host ""
        Write-Host "Usage:"
        Write-Host "  .\scripts\dev.ps1 up"
        Write-Host "  .\scripts\dev.ps1 ps"
        Write-Host "  .\scripts\dev.ps1 artisan about"
        Write-Host "  .\scripts\dev.ps1 test"
        Write-Host "  .\scripts\dev.ps1 migrate-pretend"
        Write-Host "  .\scripts\dev.ps1 pint-test"
        Write-Host "  .\scripts\dev.ps1 composer install"
        Write-Host "  .\scripts\dev.ps1 shell"
        exit 0
    }
}
