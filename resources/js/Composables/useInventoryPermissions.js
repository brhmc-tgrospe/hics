import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useInventoryPermissions() {
    const page = usePage();
    const authUser = computed(() => page.props.auth.user);
    const userRoles = computed(() => authUser.value?.roles || []);
    const userPermissions = computed(() => authUser.value?.permissions || []);

    const isSuperadmin = computed(() => userRoles.value.includes('Superadmin') || userRoles.value.includes('Developer'));
    const isSecretary = computed(() => userRoles.value.includes('Secretary'));
    const isAdmin = computed(() => userRoles.value.includes('Admin'));
    const isEncoder = computed(() => userRoles.value.includes('Encoder'));
    const isInGeneralArea = computed(() => {
        if (isSuperadmin.value || isAdmin.value) return false;
        return Boolean(authUser.value?.is_general_area || authUser.value?.area_name?.toLowerCase() === 'general area');
    });

    const canEditItem = (item, editPermission) => {
        if (isSuperadmin.value) return true;
        if (isSecretary.value) return false;
        if (!userPermissions.value.includes(editPermission)) return false;
        
        if (isAdmin.value) return item.division_id == authUser.value?.division_id;
        if (isEncoder.value) return item.division_id == authUser.value?.division_id && item.area_id == authUser.value?.area_id;
        
        return false;
    };

    const canDeleteItem = (item, deletePermission) => {
        if (isSuperadmin.value) return true;
        if (isSecretary.value) return false;
        if (!userPermissions.value.includes(deletePermission)) return false;

        if (isAdmin.value) return item.division_id == authUser.value?.division_id;
        if (isEncoder.value) return item.division_id == authUser.value?.division_id && item.area_id == authUser.value?.area_id;
        
        return false;
    };

    const canFilterDivisionArea = computed(() => isSuperadmin.value || isAdmin.value);

    return {
        authUser,
        userRoles,
        userPermissions,
        isSuperadmin,
        isSecretary,
        isAdmin,
        isEncoder,
        isInGeneralArea,
        canFilterDivisionArea,
        canEditItem,
        canDeleteItem,
    };
}
