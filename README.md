# DYCRM

## Local Development

This workspace uses Docker for PHP 8.3, Composer, and Artisan commands. On Windows,
prefer the helper script instead of relying on PHP being installed in the host PATH:

```powershell
.\scripts\dev.ps1 up
.\scripts\dev.ps1 ps
.\scripts\dev.ps1 artisan about
.\scripts\dev.ps1 test
.\scripts\dev.ps1 migrate-pretend
.\scripts\dev.ps1 pint-test
```

The local app is exposed on the port configured by `APP_PORT` in `.env.local`
and defaults to `http://localhost:3100`.


