# DYCRM AI Project Context

## Stack

- Laravel 12 + Filament 5 CRM for Douyin creators.
- PHP target is 8.3. The admin panel lives at `/admin`.
- Local Docker admin URL: http://127.0.0.1:3100/admin
- Production deploy uses GitHub Actions-built Docker image and VPS pulls from GHCR.

## Filament Conventions

- Put admin resources under `app/Filament/Resources` and page classes under each resource's `Pages` namespace.
- Follow Filament 5 resource signatures: `form(Schema $schema): Schema`, `table(Table $table): Table`, and `getPages(): array`.
- Use `Filament\Forms` for form fields, `Filament\Schemas` for schema/layout components, and `Filament\Tables` for tables.
- Prefer Filament built-ins for forms, tables, actions, filters, widgets, notifications before adding custom UI.
- Use Heroicon names such as `heroicon-o-*` for navigation and action icons.
- Keep user-facing admin labels translatable with `__()`. Existing translations live in `lang/zh_CN.json`.
- Preserve the current Chinese business terminology for creator CRM concepts.
- For CSV import/export, keep UTF-8 BOM handling and streaming responses where the existing code uses them.

## Laravel Conventions

- Keep Eloquent models in `app/Models` with explicit `$fillable`, `casts(): array`, and typed relationship methods.
- Put migrations in `database/migrations` and make defaults match both migrations and model creation hooks where applicable.
- Reuse existing support services in `app/Support` for business logic that would otherwise make Filament resources too large.
- Use Laravel helpers and facades already present in the project rather than introducing new dependencies for small tasks.

## Verification

- Before finishing code changes, run the narrowest useful check available, such as:
  - `php artisan test`
  - `php artisan migrate --pretend`
  - `vendor/bin/pint --test`
- If a command cannot run in the current environment, report that clearly.

## Notes

- Read the nearby resource/model/migration before editing. The project has established CRM-specific field names and status values.
- Before giving VPS deployment/update commands, read `docs/PRODUCTION_DEPLOYMENT.md`; production normally pulls GitHub Actions-built Docker images from GHCR instead of building on the VPS.

## Current Decisions

- Use Filament Shield roles as the permission system.
- Installed the Filamen Import Wizard plug-in to enhance CSV and Excel import capabilities
- The Nitik-Error Tracker plug-in has been installed to monitor website errors.
- Default development is local testing. Commit locally when useful, but do not push unless explicitly requested.

## UX Preferences
- Prefer clean Filament demo-style layouts.
- Avoid overly custom UI that breaks native Filament behavior.
- Keep mobile table layout usable.
- Theme colors should be consistent.
