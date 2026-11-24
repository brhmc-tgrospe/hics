<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue';

defineProps({
    maxHeightClass: {
        type: String,
        default: 'max-h-[68vh]',
    },
});

const topScrollRef = ref(null);
const tableScrollRef = ref(null);
const tableScrollWidth = ref(0);
const verticalScrollbarWidth = ref(0);
const hasHorizontalOverflow = ref(false);

let isSyncingFromTop = false;
let isSyncingFromTable = false;
let resizeObserver = null;
let mutationObserver = null;
let observedTableEl = null;

const updateMetrics = () => {
    const container = tableScrollRef.value;
    if (!container) return;

    const tableEl = container.querySelector('table');
    const contentScrollWidth = Math.max(
        container.scrollWidth,
        tableEl ? tableEl.scrollWidth : 0
    );

    tableScrollWidth.value = contentScrollWidth;
    verticalScrollbarWidth.value = Math.max(0, container.offsetWidth - container.clientWidth);
    hasHorizontalOverflow.value = contentScrollWidth > container.clientWidth + 1;

    if (tableEl && tableEl !== observedTableEl && resizeObserver) {
        if (observedTableEl) {
            resizeObserver.unobserve(observedTableEl);
        }
        observedTableEl = tableEl;
        resizeObserver.observe(tableEl);
    }

    if (topScrollRef.value && topScrollRef.value.scrollLeft !== container.scrollLeft) {
        topScrollRef.value.scrollLeft = container.scrollLeft;
    }
};

const onTopScroll = () => {
    if (isSyncingFromTable) {
        isSyncingFromTable = false;
        return;
    }
    if (!topScrollRef.value || !tableScrollRef.value) return;

    isSyncingFromTop = true;
    tableScrollRef.value.scrollLeft = topScrollRef.value.scrollLeft;
    requestAnimationFrame(() => {
        isSyncingFromTop = false;
    });
};

const onTableScroll = () => {
    if (isSyncingFromTop) {
        isSyncingFromTop = false;
        return;
    }
    if (!topScrollRef.value || !tableScrollRef.value) return;

    isSyncingFromTable = true;
    topScrollRef.value.scrollLeft = tableScrollRef.value.scrollLeft;
    requestAnimationFrame(() => {
        isSyncingFromTable = false;
    });
};

onMounted(async () => {
    await nextTick();
    updateMetrics();

    if (typeof ResizeObserver !== 'undefined' && tableScrollRef.value) {
        resizeObserver = new ResizeObserver(() => {
            updateMetrics();
        });
        resizeObserver.observe(tableScrollRef.value);

        const tableEl = tableScrollRef.value.querySelector('table');
        if (tableEl) {
            observedTableEl = tableEl;
            resizeObserver.observe(tableEl);
        }
    }

    if (typeof MutationObserver !== 'undefined' && tableScrollRef.value) {
        mutationObserver = new MutationObserver(() => {
            updateMetrics();
        });
        mutationObserver.observe(tableScrollRef.value, {
            childList: true,
            subtree: true,
        });
    }

    window.addEventListener('resize', updateMetrics);
});

onBeforeUnmount(() => {
    if (resizeObserver) {
        resizeObserver.disconnect();
    }
    if (mutationObserver) {
        mutationObserver.disconnect();
    }
    window.removeEventListener('resize', updateMetrics);
});
</script>

<template>
    <div class="flex-1 flex flex-col min-w-0">
        <!-- Top synced horizontal scrollbar matching bottom scrollbar dimensions -->
        <div
            v-show="hasHorizontalOverflow"
            class="border-b border-slate-200/70 bg-slate-100/80"
        >
            <div
                ref="topScrollRef"
                class="overflow-x-auto overflow-y-hidden"
                :style="{ marginRight: verticalScrollbarWidth + 'px' }"
                @scroll="onTopScroll"
            >
                <div :style="{ width: tableScrollWidth + 'px', height: '1px' }"></div>
            </div>
        </div>

        <!-- Vertically & horizontally scrollable table viewport with sticky thead -->
        <div
            ref="tableScrollRef"
            :class="['overflow-auto flex-1 relative', maxHeightClass]"
            @scroll="onTableScroll"
        >
            <slot />
        </div>
    </div>
</template>
