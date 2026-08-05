import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export function useInventoryTable({ items, canDeleteItem, destroyRouteName, bulkDeleteRouteName }) {
    const selectedItems = ref([]);

    // Clear selection when data changes (e.g., page change)
    watch(() => items.value, () => {
        selectedItems.value = [];
    }, { deep: true });

    const selectAll = computed({
        get: () => {
            const deletableItems = items.value.filter(canDeleteItem);
            return deletableItems.length > 0 && deletableItems.every(item => selectedItems.value.includes(item.id));
        },
        set: (val) => {
            if (val) {
                selectedItems.value = items.value.filter(canDeleteItem).map(item => item.id);
            } else {
                selectedItems.value = [];
            }
        }
    });

    const isConfirmDeleteOpen = ref(false);
    const itemToDelete = ref(null);
    const deleteRemarks = ref('');

    const handleDelete = (id) => {
        itemToDelete.value = id;
        deleteRemarks.value = '';
        isConfirmDeleteOpen.value = true;
    };

    const executeDelete = () => {
        if (itemToDelete.value) {
            router.delete(route(destroyRouteName, itemToDelete.value), {
                data: { remarks: deleteRemarks.value },
                onSuccess: () => {
                    isConfirmDeleteOpen.value = false;
                    itemToDelete.value = null;
                    deleteRemarks.value = '';
                }
            });
        }
    };

    const isConfirmBulkDeleteOpen = ref(false);
    const bulkDeleteRemarks = ref('');

    const handleBulkDelete = () => {
        if (selectedItems.value.length === 0) return;
        bulkDeleteRemarks.value = '';
        isConfirmBulkDeleteOpen.value = true;
    };

    const executeBulkDelete = () => {
        router.delete(route(bulkDeleteRouteName), {
            data: { 
                ids: selectedItems.value,
                remarks: bulkDeleteRemarks.value 
            },
            onSuccess: () => {
                selectedItems.value = [];
                isConfirmBulkDeleteOpen.value = false;
                bulkDeleteRemarks.value = '';
            }
        });
    };

    return {
        selectedItems,
        selectAll,
        isConfirmDeleteOpen,
        itemToDelete,
        deleteRemarks,
        handleDelete,
        executeDelete,
        isConfirmBulkDeleteOpen,
        bulkDeleteRemarks,
        handleBulkDelete,
        executeBulkDelete
    };
}
