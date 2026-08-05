<script setup>
import { computed } from 'vue';

const props = defineProps({
    divisionId: {
        type: [String, Number],
        default: ''
    },
    areaId: {
        type: [String, Number],
        default: ''
    },
    divisions: {
        type: Array,
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
    selectClass: {
        type: String,
        default: 'bg-white/50 backdrop-blur border border-white/80 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700 shadow-sm'
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:divisionId', 'update:areaId', 'change']);

const getDivisionName = (division) => {
    return division.name || division.div_name || `Division #${division.id}`;
};

const getAreaName = (area) => {
    return area.name || area.area_name || `Area #${area.id}`;
};

const filteredAreas = computed(() => {
    if (!props.divisionId) return [];
    return props.areas.filter(area => String(area.division_id) === String(props.divisionId));
});

const onDivisionChange = (event) => {
    const val = event.target.value;
    emit('update:divisionId', val);
    
    // Check if current area belongs to the new division
    const stillValid = val && filteredAreas.value.some(a => String(a.id) === String(props.areaId));
    if (!stillValid) {
        emit('update:areaId', '');
    }
    
    emit('change', {
        divisionId: val,
        areaId: stillValid ? props.areaId : ''
    });
};

const onAreaChange = (event) => {
    const val = event.target.value;
    emit('update:areaId', val);
    emit('change', {
        divisionId: props.divisionId,
        areaId: val
    });
};
</script>

<template>
    <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
        <!-- Division Dropdown -->
        <div class="w-full sm:w-auto">
            <select
                :value="divisionId"
                @change="onDivisionChange"
                :disabled="disabled"
                :class="[
                    selectClass,
                    'w-full sm:w-44 transition-all duration-200 cursor-pointer',
                    disabled ? 'opacity-60 cursor-not-allowed bg-slate-100' : ''
                ]"
            >
                <option value="">All Divisions</option>
                <option v-for="division in divisions" :key="division.id" :value="division.id">
                    {{ getDivisionName(division) }}
                </option>
            </select>
        </div>

        <!-- Area Dropdown -->
        <div class="w-full sm:w-auto">
            <select
                :value="areaId"
                @change="onAreaChange"
                :disabled="disabled || !divisionId"
                :class="[
                    selectClass,
                    'w-full sm:w-44 transition-all duration-200',
                    (!divisionId || disabled) ? 'opacity-50 cursor-not-allowed bg-slate-100/70 text-slate-400' : 'cursor-pointer'
                ]"
            >
                <option value="">
                    {{ divisionId ? 'All Areas' : 'Select Division first' }}
                </option>
                <option v-for="area in filteredAreas" :key="area.id" :value="area.id">
                    {{ getAreaName(area) }}
                </option>
            </select>
        </div>
    </div>
</template>
