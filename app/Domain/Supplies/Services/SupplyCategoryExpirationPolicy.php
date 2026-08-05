<?php

namespace App\Domain\Supplies\Services;

class SupplyCategoryExpirationPolicy
{
    /**
     * Determine if a given supply category requires an expiry date.
     *
     * @param string|null $category
     * @return bool
     */
    public static function isExpiryRequired(?string $category): bool
    {
        if (empty($category)) {
            return false;
        }

        $normalized = strtolower(str_replace(['-', ' ', '_'], '', trim($category)));

        // Explicit non-food or exempt categories are NOT required
        if (str_contains($normalized, 'nonfood') || in_array($normalized, ['ictsupply', 'office', 'officesup', 'hksupp', 'housekeeping'])) {
            return false;
        }

        // Explicit perishable / medical / food categories require expiry
        if (
            str_contains($normalized, 'food') || 
            in_array($normalized, ['mssup', 'enteral', 'drmeds', 'medicalandsurgicalsupplies', 'enteralsupplies', 'drugsandmedicines'])
        ) {
            return true;
        }

        // Default to not required if category is unknown or unhandled
        return false;
    }

    /**
     * Determine if a given supply category is exempt from requiring an expiry date.
     *
     * @param string|null $category
     * @return bool
     */
    public static function isExpiryExempt(?string $category): bool
    {
        return !self::isExpiryRequired($category);
    }
}
