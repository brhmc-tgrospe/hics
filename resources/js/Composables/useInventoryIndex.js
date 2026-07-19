import { ref, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import axios from 'axios';

export function useInventoryIndex({ 
    props, 
    indexRouteName, 
    importRouteName, 
    reportGenerateRouteName, 
    reportShowRouteName 
}) {
    const page = usePage();
    const authUser = computed(() => page.props.auth.user);
    const userRoles = computed(() => authUser.value?.roles || []);
    const isSuperadmin = computed(() => userRoles.value.includes('Superadmin') || userRoles.value.includes('Developer'));

    // Filters
    const search = ref(props.filters.search || '');
    const category = ref(props.filters.category || 'All');
    const myDivisionOnly = ref(props.filters.my_division_only === '1' || props.filters.my_division_only === true);
    const myAreaOnly = ref(props.filters.my_area_only === '1' || props.filters.my_area_only === true);

    const applyFilters = debounce(() => {
        router.get(route(indexRouteName), {
            search: search.value,
            category: category.value,
            my_division_only: myDivisionOnly.value ? '1' : '0',
            my_area_only: myAreaOnly.value ? '1' : '0',
        }, { preserveState: true, replace: true, preserveScroll: true });
    }, 300);

    watch([search, category], applyFilters);

    const toggleDivisionFilter = () => {
        myDivisionOnly.value = !myDivisionOnly.value;
        if (myDivisionOnly.value) myAreaOnly.value = false;
        applyFilters();
    };

    const toggleAreaFilter = () => {
        myAreaOnly.value = !myAreaOnly.value;
        if (myAreaOnly.value) myDivisionOnly.value = false;
        applyFilters();
    };

    // Forms & Viewing
    const isAdding = ref(false);
    const editingData = ref(null);
    const isViewing = ref(false);
    const viewingData = ref(null);

    const openEdit = (data) => {
        editingData.value = data;
        isAdding.value = true;
    };

    const openAdd = () => {
        editingData.value = null;
        isAdding.value = true;
    };

    const openView = (data) => {
        viewingData.value = data;
        isViewing.value = true;
    };

    const closeForm = () => {
        isAdding.value = false;
        editingData.value = null;
    };

    // File Import
    const fileInput = ref(null);
    const showErrorModal = ref(false);
    const errorMessageContent = ref('');

    const handleFileUpload = (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);

        router.post(route(importRouteName), formData, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                if (fileInput.value) fileInput.value.value = '';
            },
            onError: (errors) => {
                if (fileInput.value) fileInput.value.value = '';
                errorMessageContent.value = Object.values(errors)[0] || 'Failed to import CSV. Please ensure the format matches the template.';
                showErrorModal.value = true;
            }
        });
    };

    // Reporting
    const isReporting = ref(false);
    const reportData = ref({
        category: props.categories?.length ? props.categories[0].code : '',
        date_of_accountability: new Date('2021-05-28'),
        year_of_report: new Date().getFullYear(),
        fund_cluster: '',
        report_type: 'General',
        report_period: '1st Qtr',
        custom_month: null,
        scope_id: null,
    });

    const availableReportTypes = computed(() => {
        if (isSuperadmin.value || userRoles.value.includes('Admin')) {
            return ['General', 'Division', 'Area'];
        }
        return ['General', 'Area'];
    });

    const reportDivisions = computed(() => {
        if (isSuperadmin.value) return props.divisions || [];
        return (props.divisions || []).filter(d => d.id === authUser.value?.division_id);
    });

    const reportAreas = computed(() => {
        if (isSuperadmin.value) return props.areas || [];
        if (userRoles.value.includes('Admin')) {
            return (props.areas || []).filter(a => a.division_id === authUser.value?.division_id);
        }
        return (props.areas || []).filter(a => a.id === authUser.value?.area_id);
    });

    watch(() => reportData.value.report_type, (newType) => {
        if (newType === 'General') {
            reportData.value.scope_id = null;
        } else if (newType === 'Division' && reportDivisions.value.length === 1) {
            reportData.value.scope_id = reportDivisions.value[0].id;
        } else if (newType === 'Area' && reportAreas.value.length === 1) {
            reportData.value.scope_id = reportAreas.value[0].id;
        } else {
            reportData.value.scope_id = null;
        }
    });

    const generateReport = async () => {
        if (!reportData.value.category || !reportData.value.date_of_accountability || !reportData.value.year_of_report) {
            alert("Please fill in all required fields.");
            return;
        }
        if (reportData.value.report_type !== 'General' && !reportData.value.scope_id) {
            alert(`Please select a ${reportData.value.report_type}.`);
            return;
        }

        try {
            const payload = {
                ...reportData.value,
                date_of_accountability: reportData.value.date_of_accountability instanceof Date 
                    ? reportData.value.date_of_accountability.toLocaleDateString('en-CA') // YYYY-MM-DD
                    : reportData.value.date_of_accountability
            };
            const response = await axios.post(route(reportGenerateRouteName), payload);
            if (response.data && response.data.id) {
                window.open(route(reportShowRouteName, response.data.id), '_blank');
                isReporting.value = false;
            }
        } catch (error) {
            console.error("Error generating report", error);
            alert("Failed to generate report. Please check your inputs.");
        }
    };

    return {
        search,
        category,
        myDivisionOnly,
        myAreaOnly,
        toggleDivisionFilter,
        toggleAreaFilter,
        isAdding,
        editingData,
        isViewing,
        viewingData,
        openEdit,
        openAdd,
        openView,
        closeForm,
        fileInput,
        showErrorModal,
        errorMessageContent,
        handleFileUpload,
        isReporting,
        reportData,
        availableReportTypes,
        reportDivisions,
        reportAreas,
        generateReport
    };
}
