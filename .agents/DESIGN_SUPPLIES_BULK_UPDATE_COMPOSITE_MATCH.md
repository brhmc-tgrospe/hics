# Design Specification: Supplies Bulk Import Fallback Matching

## 1. Overview & Purpose
Enable bulk updating of supplies that lack a `stock_number` during CSV imports, while preventing duplicate entries and accidental overwrites.

---

## 2. Understanding Summary
* **Current State**: The CSV import matches existing supplies exclusively by `stock_number` scoped to `division_id` and `area_id`. Rows without a stock number are always created as new records.
* **Problem**: Many supplies in inventory do not have official stock numbers assigned yet. Re-importing updated data (e.g., counts or unit values) results in duplicate records.
* **Target Behavior**: When `stock_number` is absent, the system uses a composite fallback match (`category` + `article` + `description` within `division_id` + `area_id`).
* **Creation vs. Update Rules**:
  * If `category` differs: Created as new data.
  * If `article` differs: Created as new data.
  * If `description` differs: Created as new data.
  * If `category`, `article`, and `description` all match a single existing record: Updated.
  * If multiple records match (ambiguity): Aborted with a line-specific error.

---

## 3. Assumptions & Constraints
* **Data Retention**: Under no circumstances will database tables or rows be truncated, wiped, or dropped.
* **Case and Whitespace Insensitivity**: String comparisons for `article` and `description` use trimmed, case-insensitive logic (`LOWER(TRIM(...))`) to avoid false-negative mismatches.
* **Role Scoping**: Encoders can only match/update within their assigned division and area. Admins can match/update within their division.
* **Transactional Integrity**: All import operations execute within `DB::transaction`. Any ambiguous match or validation failure rolls back the entire batch.
* **Partial Field Updates**: Empty/null columns in the CSV do not overwrite existing database columns (`array_filter` non-null logic).

---

## 4. Decision Log

| Decision Point | Chosen Approach | Alternatives Considered | Rationale |
| :--- | :--- | :--- | :--- |
| **Matching Strategy** | Composite Key Fallback | Add DB `id` to CSV; Auto-generate stock numbers; Web UI bulk grid | Does not require changing CSV template structure or database schema. |
| **Composite Fields** | Category + Article + Description | Article + Description only; Category + Description | Maximizes specificity within division & area; adheres to exact user specifications. |
| **Collision Policy** | Strict Abort with Line-Specific Error | Overwrite first match; Silently skip | Prevents corrupting or overwriting the wrong inventory batch when duplicates already exist. |
| **Implementation Layer** | Action-Level (`ImportSupplyAction`) | Controller in-memory map; FormRequest hook | Keeps domain logic in DDD actions, high testability, and isolated transactions. |

---

## 5. Detailed Technical Design

### A. Action Layer (`ImportSupplyAction.php`)
```php
public function execute(SupplyDTO $dto): array
{
    $supply = null;
    $stockNumber = trim((string) ($dto->stock_number ?? ''));

    // Tier 1: Match by stock_number if provided
    if ($stockNumber !== '') {
        $query = Supply::where('stock_number', $stockNumber);
        if (!empty($dto->division_id)) {
            $query->where('division_id', $dto->division_id);
        }
        if (!empty($dto->area_id)) {
            $query->where('area_id', $dto->area_id);
        }
        $supply = $query->first();
    } else {
        // Tier 2: Composite Fallback (Category + Article + Description)
        $query = Supply::where('category', $dto->category);
        if (!empty($dto->division_id)) {
            $query->where('division_id', $dto->division_id);
        }
        if (!empty($dto->area_id)) {
            $query->where('area_id', $dto->area_id);
        }

        $cleanArticle = strtolower(trim((string) ($dto->article ?? '')));
        $cleanDesc = strtolower(trim((string) ($dto->description ?? '')));

        $query->whereRaw('LOWER(TRIM(COALESCE(article, ""))) = ?', [$cleanArticle])
              ->whereRaw('LOWER(TRIM(COALESCE(description, ""))) = ?', [$cleanDesc]);

        $matches = $query->get();

        if ($matches->count() > 1) {
            throw new \DomainException(
                "Multiple records found matching Category '{$dto->category}', Article '{$dto->article}', and Description in this area. Please assign a Stock Number to disambiguate."
            );
        }

        $supply = $matches->first();
    }

    if ($supply) {
        return ['record' => $this->updateAction->execute($supply, $dto), 'action' => 'updated'];
    }

    return ['record' => $this->createAction->execute($dto), 'action' => 'created'];
}
```

### B. Controller Error Handling (`SupplyController.php`)
Catch `\DomainException` or validation exceptions during the import loop and associate the error with the CSV row line number (`$data['_line']`), flashing a clear message back to the user interface.

### C. UI Guidance (`ImportCsvModal.vue`)
Update the guideline collapsible table to explain:
* When `stock_number` is provided, it updates by stock number.
* When `stock_number` is blank, it automatically matches and updates by Category + Article + Description if an exact match exists.
