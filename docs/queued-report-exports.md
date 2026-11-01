# Queued report exports

Implemented for Ecommerce Payment, Promo Usage and Monitoring Data Customer.
Other exports still use their existing download flow and need separate migration.

## Deploy

Run the targeted migration before deploying traffic to the new export endpoints:

```sh
php artisan migrate --path=database/migrations/2026_09_15_100000_create_report_exports.php --force
php artisan config:clear
php artisan view:clear
```

Start a dedicated worker under Supervisor/systemd (one worker initially):

```sh
php artisan queue:work report_exports --queue=exports --timeout=900 --tries=1 --memory=512 --max-jobs=20
```

This explicitly uses the `report_exports` database connection/queue even if the
application default is `sync`. Queue retry_after is 960 seconds, above the worker
timeout. The worker and web process must share `storage/app` and database access.
Use `php artisan queue:restart` on subsequent deployments.

Without the worker, exports remain queued. A worker terminated by the operating
system can leave a processing record until the job is picked up again. Monitor
queue age and failed statuses. Configure Supervisor autorestart and stopwaitsecs
greater than 900 seconds.

## Limits and behavior

- Maximum inclusive date range: 31 days; configurable in config/report_exports.php.
- Customer dates mean registration dates (start_date/end_date).
- Defaults: current month through today, except Ecommerce Payment retains today.
- Database rows are read in batches of 500 with stable tie-break ordering.
- XLSX retains formatting; PhpSpreadsheet still retains cells in memory. A hard
  limit of 20,000 data rows prevents unlimited workbook growth. Exceeding it fails
  the export rather than serving a truncated report.
- Only one queued/processing request per user. Completed files are private and
  download endpoints check ownership.
- Data reflects database state while the worker runs, not an immutable request-time
  snapshot. Avoid concurrent bulk modifications during reconciliation exports.
- Files currently require an operational retention policy; monitor storage usage.

## Verification

`php artisan test --filter=QueuedReportExportTest` uses the configured database
schema, rolls back test metadata and deletes only generated test files. Run in a
development/test environment after the migration.
