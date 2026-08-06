export function useSupplyCategoryRules() {
    /**
     * Determine if a given supply category requires an expiry date.
     * 
     * @param {string|object|null} category 
     * @param {Array} [categoriesList=[]]
     * @returns {boolean}
     */
    const isExpiryRequiredSupply = (category, categoriesList = []) => {
        if (!category) return false;

        // If category is an object with has_expiration_date
        if (typeof category === 'object' && category !== null) {
            if (category.has_expiration_date !== undefined && category.has_expiration_date !== null) {
                return Boolean(category.has_expiration_date);
            }
            category = category.name || category.code || '';
        }

        // If categoriesList is provided, try finding matching category
        if (Array.isArray(categoriesList) && categoriesList.length > 0) {
            const rawCategory = category.toString().trim().toLowerCase();
            const matched = categoriesList.find(c => {
                if (!c) return false;
                const cName = (c.name || '').toString().trim().toLowerCase();
                const cCode = (c.code || '').toString().trim().toLowerCase();
                return cName === rawCategory || cCode === rawCategory || String(c.id) === String(category);
            });
            if (matched && matched.has_expiration_date !== undefined && matched.has_expiration_date !== null) {
                return Boolean(matched.has_expiration_date);
            }
        }

        const normalized = category.toString().trim().toLowerCase().replace(/[- _]/g, '');

        // Explicit non-food, hardware or exempt categories are NOT required
        if (
            normalized.includes('nonfood') || 
            normalized.includes('hardware') ||
            ['ictsupply', 'office', 'officesup', 'hksupp', 'housekeeping', 'hardwaresupplies'].includes(normalized)
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
     * @param {string|object|null} category 
     * @param {Array} [categoriesList=[]]
     * @returns {boolean}
     */
    const isExpiryExemptSupply = (category, categoriesList = []) => {
        return !isExpiryRequiredSupply(category, categoriesList);
    };

    return {
        isExpiryRequiredSupply,
        isExpiryExemptSupply,
    };
}
