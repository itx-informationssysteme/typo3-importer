# Importer dashboard widgets (TYPO3 13)

Three widgets in a new "Importer" group of the Dashboard:

| Widget | Shows |
|---|---|
| Import status | Latest run per import type: status, start, duration, live job progress (done/total, failed) |
| Import statistics | Records added / updated / deleted / unchanged per record type of the latest run |
| Failed import jobs | Most recent failed jobs with failure reason (`limit` option, default 10) |

## Install into the v13 branch

1. Copy the new files (same paths) into the extension:
   - `Classes/Dashboard/ImportDashboardDataProvider.php`
   - `Classes/Widgets/*.php`
   - `Configuration/Services/Dashboard.yaml`
   - `Configuration/Backend/DashboardWidgetGroups.php`
   - `Configuration/Icons.php` (skip if the branch already has one; add the `importer-extension` entry instead)
   - `Resources/Private/Templates/Widget/*.html`
   - `Resources/Private/Language/locallang_dashboard.xlf`
2. `Configuration/Services.yaml`: add at the top
   ```yaml
   imports:
       -   { resource: 'Services/Dashboard.yaml' }
   ```
   and extend the resource exclude (the widgets and the provider are registered explicitly in `Dashboard.yaml`):
   ```yaml
   exclude:
       - '../Classes/Domain/Model/*'
       - '../Classes/Widgets/*'
       - '../Classes/Dashboard/*'
   ```
3. `composer.json`: add `"typo3/cms-dashboard": "^13.4"` to `require`;
   `ext_emconf.php`: add `'dashboard' => '13.4.0-13.4.99'` to `depends`.
4. Flush caches, then Dashboard > Add widget > "Importer".

The templates reuse the existing `Partials/Backend/ImportStatus.html`. The data is read straight from the
`tx_importer_domain_model_*` tables with DBAL, not through the Extbase repositories.
