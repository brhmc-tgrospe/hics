<?php

namespace App\Domain\Supplies\Services;

use App\Domain\Shared\Models\Category;

class SupplyCategoryExpirationPolicy
{
    /**
     * In-memory cache for category expiration status to prevent redundant DB queries during CSV bulk imports.
     *
     * @var array<string, bool>
     */
    private static array $cache = [];

    /**
     * Determine if a given supply category requires an expiry date.
     *
     * @param string|int|Category|null $category
     * @return bool
     */
    public static function isExpiryRequired(string|int|Category|null $category): bool
    {
        if (empty($category)) {
            return false;
        }

        if ($category instanceof Category) {
            return (bool) $category->has_expiration_date;
        }

        $lookupKey = is_int($category) ? (string) $category : trim((string) $category);
        $normalized = strtolower(str_replace(['-', ' ', '_'], '', $lookupKey));

        if (isset(self::$cache[$normalized])) {
            return self::$cache[$normalized];
        }

        // 1. Try to find the category in the database
        $dbCategory = is_numeric($lookupKey)
            ? Category::where('type', 'supply')->find((int) $lookupKey)
            : Category::where('type', 'supply')
                ->where(function ($query) use ($lookupKey, $normalized) {
                    $query->where('code', $lookupKey)
                          ->orWhere('name', $lookupKey)
                          ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(code, '-', ''), ' ', ''), '_', '')) = ?", [$normalized])
                          ->orWhereRaw("LOWER(REPLACE(REPLACE(REPLACE(name, '-', ''), ' ', ''), '_', '')) = ?", [$normalized]);
                })->first();

        if ($dbCategory !== null) {
            $isRequired = (bool) $dbCategory->has_expiration_date;
            self::$cache[$normalized] = $isRequired;
            return $isRequired;
        }

        // 2. Fallback heuristics for non-database or detached test scenarios
        if (
            str_contains($normalized, 'hardware') ||
            str_contains($normalized, 'nonfood') ||
            in_array($normalized, ['ictsupply', 'office', 'officesup', 'hksupp', 'housekeeping', 'hardwaresup', 'hardwaresupplies'])
        ) {
            self::$cache[$normalized] = false;
            return false;
        }

        if (
            str_contains($normalized, 'food') ||
            in_array($normalized, ['mssup', 'enteral', 'drmeds', 'medicalandsurgicalsupplies', 'enteralsupplies', 'drugsandmedicines'])
        ) {
            self::$cache[$normalized] = true;
            return true;
        }

        self::$cache[$normalized] = false;
        return false;
    }

    /**
     * Determine if a given supply category is exempt from requiring an expiry date.
     *
     * @param string|int|Category|null $category
     * @return bool
     */
    public static function isExpiryExempt(string|int|Category|null $category): bool
    {
        return !self::isExpiryRequired($category);
    }

    /**
     * Clear the in-memory cache (primarily for tests).
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
