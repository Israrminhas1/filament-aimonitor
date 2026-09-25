# Changelog

## Unreleased

### Added
- Filament 5 support (Livewire 4, Laravel 13). Filament 4 is still supported.
- Dashboard period filter (7 / 30 / 90 / 365 days) and provider filter for all widgets.
- Pricing matches dated or tagged model snapshots (`gpt-4o-2024-08-06` uses the `gpt-4o` price).
- Cost recalculation: `ai-monitor:recalculate-costs` command, a *Recalculate missing costs* action, and a *Recalculate cost* bulk action.
- `AiRequestLogged` event, fired for every logged request.
- `navigationSort()` plugin option; `user_title_attribute` config option.
- `Tenancy::resolveUsing()` for a custom tenant resolver; Filament panel tenancy is detected automatically.
- Updated default pricing in `ai-monitor:setup-pricing` (current Claude, GPT, Gemini and Sonar models).
- Pest test suite and GitHub Actions CI on Filament 4 and 5.

### Fixed
- The request detail page crashed because it used `Filament\Infolists\Components\Section`, which does not exist in Filament 4 or 5.
- Links in the dashboard and setup alert used hard-coded `filament.admin.*` route names and broke on panels with a different ID.
- `navigationGroup()` and the `navigation_group` config option were ignored.
- The `tenant_support` config option was ignored.
- The Top Models and Usage by User widgets threw SQL errors when tenant scoping was active.
- Resources failed on Filament panels that use tenancy.
- The setup alert widget was rendered twice on the dashboard.
- The API key edit form sent the decrypted key to the browser. It is now write-only, and the table shows only the last four characters.
- Requests logged with status `error` showed as gray instead of red.

### Changed
- Dashboard widgets use grouped queries instead of one query per day (the cost chart went from 60 queries to 1).
- Replaced deprecated table APIs (`actions()`, `bulkActions()`, filter `form()`) with `recordActions()`, `toolbarActions()` and `schema()`.
- Removed the unused `ai-top-models-widget` and `ai-user-usage-widget` views, and the old dashboard view.
