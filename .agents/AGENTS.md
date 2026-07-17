<RULE[project_data_retention]>
<!-- BEGIN:data-retention-rule -->
# Do Not Truncate Data During Updates
When making updates to the application, modifying database schemas, or testing features, **NEVER** run commands that truncate or wipe out the existing database data (e.g., do not run `php artisan migrate:fresh`, `php artisan migrate:refresh`, or direct `TRUNCATE` SQL queries). 

Always prefer:
- Standard `php artisan migrate` 
- Writing new migrations that preserve existing data
- If data must be reset, you must ask for explicit user permission first.
<!-- END:data-retention-rule -->
</RULE[project_data_retention]>
