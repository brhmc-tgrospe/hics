<RULE[project_data_retention]>
<!-- BEGIN:data-retention-rule -->
# Do Not Truncate Data During Updates
When making updates to the application, modifying database schemas, or testing features, **NEVER** run commands that truncate or wipe out the existing database data (e.g., do not run `php artisan migrate:fresh`, `php artisan migrate:refresh`, or direct `TRUNCATE` SQL queries). 

Always prefer:
- Standard `php artisan migrate` 
- Writing new migrations that preserve existing data
- If data must be reset, you must ask for explicit user permission first.

### Cache & Database Isolation for CLI / Test Scripts
- **NEVER** run ad-hoc PHP/CLI verification scripts that switch `database.default` to `sqlite` (or another test connection) without also isolating `cache.default` and `permission.cache.store` to `'array'` and re-initializing `Spatie\Permission\PermissionRegistrar`; always prefer `php artisan test` for isolated verification.
- Never allow test or CLI runs to write to or flush `spatie.permission.cache` in MySQL's `cache` table.
<!-- END:data-retention-rule -->
</RULE[project_data_retention]>

<RULE[hics_core_development_skills]>
<!-- BEGIN:hics-core-development-skills-rule -->
# Mandatory Core Development Skills Protocol for HICS
Whenever developing, refactoring, modifying, testing, or debugging HICS (Hospital Inventory & Control System), you **MUST** strictly consult and actively enforce the following 25 core skills across their respective domains:

### 1. Backend Architecture & Domain Logic (Laravel 13 & PHP 8.3+)
- [`laravel-expert`](file:///C:/Users/Admin/.gemini/config/skills/laravel-expert/SKILL.md): Keep controllers thin (`EquipmentController`, `SupplyController`), extract domain operations into dedicated Action and Service classes, enforce validation in dedicated Form Requests, eager-load Eloquent relationships, and enforce Model Policies.
- [`php-pro`](file:///C:/Users/Admin/.gemini/config/skills/php-pro/SKILL.md): Leverage modern PHP 8.3+ idioms (typed properties, Enums, match expressions, and memory-efficient generators for large dataset exports and CSV/Excel batch imports).
- [`software-architecture`](file:///C:/Users/Admin/.gemini/config/skills/software-architecture/SKILL.md): Enforce Clean Architecture & Domain-Driven Design (DDD), strict separation of domain rules from infrastructure/framework concerns, early return guards, and decompose functions exceeding 80 lines.
- [`clean-code`](file:///C:/Users/Admin/.gemini/config/skills/clean-code/SKILL.md): Meaningful naming, small single-responsibility functions, eliminating unintended side effects across shared services and inventory transactions.
- [`cc-skill-backend-patterns`](file:///C:/Users/Admin/.gemini/config/skills/cc-skill-backend-patterns/SKILL.md): RESTful conventions, consistent query filtering/sorting/pagination parameters, database transaction wrappers, and secure service isolation.

### 2. Frontend UI, State & Interactive Tables (Vue 3, Inertia.js 2.0 & Tailwind CSS)
- [`cmis-table-patterns`](file:///C:/Users/Admin/.gemini/config/skills/cmis-table-patterns/SKILL.md): 300–350ms debounced global search via Inertia router with `{ preserveState: true, replace: true, preserveScroll: true }`, reusable sortable column headers (`SortableHeader`), dynamic pagination controls (`[10, 25, 50, 100]`), and exact record count summaries (`Showing {from} to {to} of {total} entries`) across Equipment, Supplies, Reports, and Users.
- [`cc-skill-frontend-patterns`](file:///C:/Users/Admin/.gemini/config/skills/cc-skill-frontend-patterns/SKILL.md): Component composition over inheritance, accessible form controls, clean Vue 3 reactive state, and resilient asset resolution in Inertia layouts.
- [`tailwind-patterns`](file:///C:/Users/Admin/.gemini/config/skills/tailwind-patterns/SKILL.md): Maintainable utility styling, accessible form controls, and consistent token usage across enterprise hospital dashboard views.
- [`frontend-design`](file:///C:/Users/Admin/.gemini/config/skills/frontend-design/SKILL.md): High-craft enterprise dashboard interfaces, legible typography, responsive layouts, and intuitive workflows for clinical and administrative staff.

### 3. Database Architecture, Performance & Data Safety
- [`database-design`](file:///C:/Users/Admin/.gemini/config/skills/database-design/SKILL.md): Clean 3NF schema modeling, foreign key cascades, proper indexing strategies, and soft delete handling (`RecycleBinController`).
- [`sql-optimization-patterns`](file:///C:/Users/Admin/.gemini/config/skills/sql-optimization-patterns/SKILL.md): Eager load relations to eliminate N+1 queries when loading items with divisions, areas, categories, and audit logs; add composite indexes on high-frequency filter columns.
- [`accidental-data-loss-prevention`](file:///C:/Users/Admin/.gemini/config/skills/accidental-data-loss-prevention/SKILL.md): Strict ban on catastrophic data wiping commands (`php artisan migrate:fresh`, `php artisan migrate:refresh`, `db:wipe`, direct `TRUNCATE`). Only forward-safe migrations allowed.

### 4. Security, RBAC & Audit Trails
- [`laravel-security-audit`](file:///C:/Users/Admin/.gemini/config/skills/laravel-security-audit/SKILL.md): Validate Spatie role & permission gates (`role:Developer|Superadmin`, etc.), prevent IDOR vulnerabilities, block mass-assignment flaws, and verify CSRF protection.
- [`security-auditor`](file:///C:/Users/Admin/.gemini/config/skills/security-auditor/SKILL.md): Comprehensive security audits, defense-in-depth reviews, and compliance checks across all application tiers.
- [`web-security-testing`](file:///C:/Users/Admin/.gemini/config/skills/web-security-testing/SKILL.md): OWASP-compliant validation, secure file upload validation for Excel/CSV imports, input sanitization, and output escaping to prevent XSS.

### 5. Testing, Systematic Debugging & Verification Gate
- [`tdd-workflow`](file:///C:/Users/Admin/.gemini/config/skills/tdd-workflow/SKILL.md): Red-Green-Refactor testing cycle with PHPUnit (`php artisan test`), validating domain logic and regression safety before shipping features.
- [`systematic-debugging`](file:///C:/Users/Admin/.gemini/config/skills/systematic-debugging/SKILL.md): Enforce disciplined root-cause investigation before proposing fixes; ban blind symptom patching and assumptions.
- [`verification-before-completion`](file:///C:/Users/Admin/.gemini/config/skills/verification-before-completion/SKILL.md): The Iron Law: NO COMPLETION CLAIMS WITHOUT FRESH VERIFICATION EVIDENCE. Always run and inspect verification commands (`php artisan test`, `npm run build`, `git diff`) before marking any task complete.

### 6. Workflow Planning & Execution
- [`concise-planning`](file:///C:/Users/Admin/.gemini/config/skills/concise-planning/SKILL.md): Produce atomic, actionable checklists before modifying code.
- [`writing-plans`](file:///C:/Users/Admin/.gemini/config/skills/writing-plans/SKILL.md): Detailed multi-step task specifications with clear checkpoints and risk mitigations.
- [`subagent-driven-development`](file:///C:/Users/Admin/.gemini/config/skills/subagent-driven-development/SKILL.md): Structure independent tasks across subagent checkpoints with strict review gates.

### 7. Domain Specifics & Host Environment Utilities
- [`it-manager-hospital`](file:///C:/Users/Admin/.gemini/config/skills/it-manager-hospital/SKILL.md): Healthcare/hospital IT governance, clinical equipment lifecycles, asset traceability, and regulatory compliance.
- [`xlsx-official`](file:///C:/Users/Admin/.gemini/config/skills/xlsx-official/SKILL.md): Excel template generation, zero formula errors (`#REF!`, `#DIV/0!`), column structure alignment, memory-efficient chunked imports, and safe data exports.
- [`windows-shell-reliability`](file:///C:/Users/Admin/.gemini/config/skills/windows-shell-reliability/SKILL.md): Reliable command execution, proper escaping, and binary path handling on Windows.
- [`powershell-windows`](file:///C:/Users/Admin/.gemini/config/skills/powershell-windows/SKILL.md): Safe PowerShell scripting, avoiding common pipeline, quoting, and encoding pitfalls.
<!-- END:hics-core-development-skills-rule -->
</RULE[hics_core_development_skills]>
