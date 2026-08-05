export function useSupplyCategoryRules() {
    /**
     * Determine if a given supply category requires an expiry date.
     * 
     * @param {string|null} category 
     * @returns {boolean}
     */
    const isExpiryRequiredSupply = (category) => {
        if (!category) return false;

        const normalized = category.toString().trim().toLowerCase().replace(/[- _]/g, '');

        // Explicit non-food or exempt categories are NOT required
        if (
            normalized.includes('nonfood') || 
            ['ictsupply', 'office', 'officesup', 'hksupp', 'housekeeping'].includes(normalized)
        ) {
            return false;
        }

        // Explicit perishable / medical / food categories require expiry
        if (
            normalized.includes('food') || 
            ['mssup', 'enteral', 'drmeds', 'medicalandsurgicalsupplies', 'enteralsupplies', 'drugsandmedicines'].includes(normalized)
        ) {
            return true;
        }

        // Default to not required
        return false;
    };

    /**
     * Determine if a given supply category is exempt from requiring an expiry date.
     * 
     * @param {string|null} category 
     * @returns {boolean}
     */
    const isExpiryExemptSupply = (category) => {
        return !isExpiryRequiredSupply(category);
    };

    return {
        isExpiryRequiredSupply,
        isExpiryExemptSupply,
    };
}
