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

<RULE[hics_core_development_skills]>
<!-- BEGIN:hics-core-development-skills-rule -->
# Mandatory Core Development Skills Protocol for HICS
Whenever developing, refactoring, modifying, testing, or debugging this system, you **MUST** strictly consult and actively enforce the following core skills based on the active task:

1. **Architecture & Backend Patterns**:
   - `laravel-expert`: Senior Laravel standard conventions, Form Requests for validation, Thin Controllers, Action/Service classes, Eloquent relationships with eager loading, and Model Policies.
   - `php-pro`: Modern PHP 8.3+ standards (Enums, match expressions, typed properties, and memory-efficient generators for large dataset exports and CSV/Excel batch imports).
   - `software-architecture`: Clean Architecture & DDD, strict separation of business domain from infrastructure/UI, library-first approach, early return guards, and decomposing functions exceeding 80 lines.
   - `clean-code`: Meaningful naming, small single-responsibility functions, eliminating side effects.
   - `cc-skill-backend-patterns`: Clean service/action architecture, RESTful conventions, database optimization, query scoping, transaction wrappers, and robust server-side security.

2. **Frontend UI & Data Tables (Vue 3, Inertia.js, Tailwind CSS)**:
   - `cmis-table-patterns`: Standard debounced search (300-350ms) using Inertia router with `preserveState` and `preserveScroll`, reusable `SortableHeader`, dynamic pagination, and exact record count summaries (`Showing {from} to {to} of {total} entries`) across Equipment, Supplies, Reports, and Users.
   - `cc-skill-frontend-patterns`: Component composition, accessible and responsive UI components, clean Vue 3 / Inertia reactive state management, and resilient asset resolution.
   - `tailwind-patterns`: Maintainable utility styling, accessible form controls, and consistent token usage.
   - `frontend-design`: Polished enterprise dashboard interfaces, legible typography, and intuitive workflows.

3. **Database Architecture, Performance & Data Retention**:
   - `database-design`: Proper 3NF schema design, foreign keys, index strategies, and soft delete handling (`RecycleBinController`).
   - `sql-optimization-patterns`: Eager loading relations, eliminating N+1 queries, indexing frequently filtered/joined columns.
   - `accidental-data-loss-prevention`: Strict prohibition against running commands or queries that wipe out data (`php artisan migrate:fresh`, `php artisan migrate:refresh`, `TRUNCATE`).

4. **Security, RBAC & Audit Trails**:
   - `laravel-security-audit` & `security-auditor`: Verify Role-Based Access Control (RBAC via Spatie permissions/roles), prevent mass assignment, protect routes/actions, and prevent IDOR.
   - `web-security-testing`: OWASP-compliant validation, CSRF verification, secure file uploads/imports, and input/output sanitization.

5. **Testing & Systematic Debugging**:
   - `tdd-workflow`: Red-Green-Refactor testing cycle with PHPUnit (`php artisan test`).
   - `systematic-debugging`: Mandate root-cause investigation before proposing any fixes; never apply blind symptom patches.

6. **Verification & Completion Gate**:
   - `verification-before-completion`: NO COMPLETION CLAIMS WITHOUT FRESH VERIFICATION EVIDENCE. Always execute the full verification command (`php artisan test`, `npm run build`, git diff) and inspect the actual output/exit code before claiming any task or phase is complete.

7. **Planning & Task Orchestration**:
   - `concise-planning` / `writing-plans`: Create an atomic, reviewable checklist before modifying code.
   - `subagent-driven-development`: Structure task execution with explicit checkpoints and review gates.

8. **Domain Specifics & Utilities**:
   - `it-manager-hospital`: Clinical/hospital IT standards, medical equipment and supply tracking, auditability standards, and departmental asset governance.
   - `xlsx-official`: Excel template generation, zero-formula errors, column structure alignment, and safe data imports/exports.
   - `windows-shell-reliability` & `powershell-windows`: Safe execution of PowerShell commands, path formatting, and avoiding shell escaping pitfalls on Windows.
<!-- END:hics-core-development-skills-rule -->
</RULE[hics_core_development_skills]>
