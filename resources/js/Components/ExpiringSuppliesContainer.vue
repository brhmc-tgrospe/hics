<template>
  <div v-if="expiringSupplies && expiringSupplies.total_items_count > 0">
    <!-- Compact Summary Banner -->
    <div
      class="bg-gradient-to-r from-rose-50/90 via-amber-50/80 to-white/80 backdrop-blur-xl rounded-3xl border border-amber-200/80 shadow-lg p-4 sm:p-5 space-y-3"
    >
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <!-- Left Info -->
        <div class="flex items-start gap-3">
          <div class="relative shrink-0">
            <span
              class="absolute inset-0 rounded-2xl bg-rose-400/60 animate-ping"
              aria-hidden="true"
            ></span>
            <div class="relative p-2.5 rounded-2xl bg-rose-100 text-rose-600 border border-rose-200/80 shadow-sm">
              <AlertTriangle class="w-5 h-5 animate-pulse" />
            </div>
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="text-sm sm:text-base font-bold text-slate-900 tracking-tight">
                Supplies Expiring Within 6 Months
              </h3>
              <span
                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/85 text-amber-800 border border-amber-200 shadow-sm"
              >
                <Clock class="w-3 h-3 text-amber-600" />
                {{ expiringSupplies.current_date_utc8 }} to {{ expiringSupplies.cutoff_date_utc8 }}
              </span>
            </div>
            <div class="flex flex-wrap items-center gap-2 mt-1.5 text-xs">
              <span
                v-if="expiredCount > 0"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-bold bg-rose-100 text-rose-700 border border-rose-200/70"
              >
                {{ expiredCount }} Expired
              </span>
              <span
                v-if="expiringSoonCount > 0"
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-bold bg-orange-100 text-orange-700 border border-orange-200/70"
              >
                {{ expiringSoonCount }} Close to Expiring
              </span>
              <span class="text-slate-500 font-medium">
                Click a category or View Items to inspect details
              </span>
            </div>
          </div>
        </div>

        <!-- Right KPIs & Action Button -->
        <div class="flex flex-wrap items-center gap-2.5">
          <div class="px-3 py-1.5 rounded-xl bg-white/85 border border-amber-200/80 shadow-sm text-xs">
            <span class="text-slate-500 font-medium">Categories:</span>
            <span class="ml-1 font-bold text-slate-800">{{ expiringSupplies.categories.length }}</span>
          </div>
          <div class="px-3 py-1.5 rounded-xl bg-white/85 border border-amber-200/80 shadow-sm text-xs">
            <span class="text-slate-500 font-medium">Total Qty:</span>
            <span class="ml-1 font-bold text-slate-900">{{ Number(expiringSupplies.total_quantity || 0).toLocaleString() }}</span>
          </div>
          <div class="px-3 py-1.5 rounded-xl bg-white/85 border border-rose-200/80 shadow-sm text-xs">
            <span class="text-slate-500 font-medium">Total Amount:</span>
            <span class="ml-1 font-bold text-rose-700">{{ formatCurrency(expiringSupplies.total_amount) }}</span>
          </div>
          <button
            type="button"
            @click="openModal()"
            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-md shadow-rose-200/70 flex items-center gap-1.5 transition-colors whitespace-nowrap"
          >
            <Eye class="w-4 h-4" />
            View Items ({{ expiringSupplies.total_items_count }})
          </button>
        </div>
      </div>

      <!-- Compact Category Summary Chips -->
      <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-amber-200/50">
        <button
          v-for="cat in expiringSupplies.categories"
          :key="cat.category_code"
          type="button"
          @click="openModal(cat.category_code)"
          class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white/85 hover:bg-white border border-amber-200/70 hover:border-amber-400 text-xs transition-all shadow-sm group"
        >
          <span class="font-bold text-slate-800 group-hover:text-rose-700 transition-colors">
            {{ cat.category_name }}
          </span>
          <span class="px-1.5 py-0.5 rounded-md bg-amber-100/80 text-amber-800 font-semibold text-[11px]">
            {{ Number(cat.total_quantity || 0).toLocaleString() }} qty
          </span>
          <span class="font-bold text-rose-700">
            {{ formatCurrency(cat.total_amount) }}
          </span>
        </button>
      </div>
    </div>

    <!-- Paginated, Filterable & Sortable Modal -->
    <Modal :show="isModalOpen" maxWidth="5xl" @close="closeModal">
      <div class="flex flex-col max-h-[85vh] bg-white rounded-lg overflow-hidden">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-200 bg-gradient-to-r from-rose-50/80 via-amber-50/70 to-white flex items-start justify-between gap-4 shrink-0">
          <div class="flex items-start gap-3">
            <div class="relative shrink-0">
              <span
                class="absolute inset-0 rounded-xl bg-rose-400/60 animate-ping"
                aria-hidden="true"
              ></span>
              <div class="relative p-2 rounded-xl bg-rose-100 text-rose-600 border border-rose-200/80">
                <AlertTriangle class="w-5 h-5 animate-pulse" />
              </div>
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-lg font-bold text-slate-900">
                  Supplies Expiring Within 6 Months
                </h3>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white text-amber-800 border border-amber-200">
                  <Clock class="w-3 h-3 text-amber-600" />
                  {{ expiringSupplies.current_date_utc8 }} to {{ expiringSupplies.cutoff_date_utc8 }}
                </span>
              </div>
              <p class="text-xs text-slate-500 font-medium mt-0.5">
                Filter by category, status, or search term; click column headers to sort by article name or expiry date.
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 shrink-0">
            <button
              type="button"
              @click="openReportModal(expiryStatusFilter)"
              class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-sm flex items-center gap-1.5 transition-colors whitespace-nowrap"
            >
              <FileText class="w-4 h-4" />
              Generate PDF Report
            </button>
            <button
              type="button"
              @click="closeModal"
              class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors shrink-0"
              title="Close"
            >
              <X class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Category Breakdown Summary Cards -->
        <div class="px-6 py-3 border-b border-slate-200/80 bg-slate-50/80 shrink-0">
          <div class="flex items-center gap-2 overflow-x-auto pb-1">
            <button
              type="button"
              @click="selectedCategory = 'All'"
              :class="[
                'shrink-0 px-3 py-2 rounded-xl border text-left transition-all',
                selectedCategory === 'All'
                  ? 'bg-slate-900 text-white border-slate-900 shadow-sm'
                  : 'bg-white text-slate-700 border-slate-200 hover:border-slate-300'
              ]"
            >
              <div class="text-[11px] font-bold uppercase tracking-wider opacity-80">All Categories</div>
              <div class="text-xs font-bold mt-0.5">
                {{ Number(viewScopedTotals.total_quantity || 0).toLocaleString() }} qty · {{ formatCurrency(viewScopedTotals.total_amount) }}
              </div>
            </button>

            <button
              v-for="cat in viewScopedCategories"
              :key="cat.category_code"
              type="button"
              @click="selectedCategory = cat.category_code"
              :class="[
                'shrink-0 px-3 py-2 rounded-xl border text-left transition-all',
                selectedCategory === cat.category_code
                  ? 'bg-rose-600 text-white border-rose-600 shadow-sm'
                  : 'bg-white text-slate-700 border-slate-200 hover:border-amber-300'
              ]"
            >
              <div class="text-[11px] font-bold truncate max-w-[180px]" :title="cat.category_name">
                {{ cat.category_name }} ({{ cat.items_count }})
              </div>
              <div class="text-xs font-semibold mt-0.5">
                {{ Number(cat.total_quantity || 0).toLocaleString() }} qty · {{ formatCurrency(cat.total_amount) }}
              </div>
            </button>
          </div>
        </div>

        <!-- Filters & Search Toolbar -->
        <div class="px-6 py-3.5 border-b border-slate-200 bg-white flex flex-col xl:flex-row xl:items-center justify-between gap-3 shrink-0">
          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-1 flex-wrap">
            <!-- Search Input -->
            <div class="relative flex-1 min-w-[200px] sm:max-w-xs">
              <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
              <input
                type="text"
                v-model="searchQuery"
                placeholder="Search article, description, stock no..."
                class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 focus:bg-white font-medium text-slate-700"
              />
              <button
                v-if="searchQuery"
                type="button"
                @click="searchQuery = ''"
                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                title="Clear search"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>

            <!-- Category Dropdown -->
            <select
              v-model="selectedCategory"
              class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
            >
              <option value="All">All Categories</option>
              <option
                v-for="cat in viewScopedCategories"
                :key="cat.category_code"
                :value="cat.category_code"
              >
                {{ cat.category_name }}
              </option>
            </select>

            <!-- Division & Area Filter -->
            <DivisionAreaFilter
              v-model:divisionId="selectedDivisionId"
              v-model:areaId="selectedAreaId"
              :divisions="availableDivisions"
              :areas="availableAreas"
              select-class="bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 font-medium text-slate-700"
            />
          </div>

          <!-- Status Quick Filter Pills (All / Close to Expiring / Expired) -->
          <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl self-start lg:self-auto">
            <button
              type="button"
              @click="expiryStatusFilter = 'all'"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap',
                expiryStatusFilter === 'all'
                  ? 'bg-white text-slate-900 shadow-sm'
                  : 'text-slate-600 hover:text-slate-900'
              ]"
            >
              All ({{ viewScopedItems.length }})
            </button>
            <button
              type="button"
              @click="expiryStatusFilter = 'expiring_soon'"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap',
                expiryStatusFilter === 'expiring_soon'
                  ? 'bg-orange-600 text-white shadow-sm'
                  : 'text-slate-600 hover:text-orange-700'
              ]"
            >
              Close to Expiring ({{ viewExpiringSoonCount }})
            </button>
            <button
              type="button"
              @click="expiryStatusFilter = 'expired'"
              :class="[
                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap',
                expiryStatusFilter === 'expired'
                  ? 'bg-rose-600 text-white shadow-sm'
                  : 'text-slate-600 hover:text-rose-700'
              ]"
            >
              Expired ({{ viewExpiredCount }})
            </button>
          </div>
        </div>

        <!-- Paginated Table Body -->
        <div class="flex-1 overflow-auto">
          <table class="w-full text-left text-sm">
            <thead class="sticky top-0 z-10 bg-slate-100 border-b border-slate-200">
              <tr>
                <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  <button
                    type="button"
                    @click="toggleSort('article')"
                    class="flex items-center gap-1 hover:text-slate-800 focus:outline-none uppercase"
                  >
                    Article / Product Name
                    <span class="flex flex-col">
                      <svg
                        class="w-2.5 h-2.5 -mb-1"
                        :class="{ 'text-blue-600': sortField === 'article' && sortDirection === 'asc', 'text-slate-400': !(sortField === 'article' && sortDirection === 'asc') }"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                      </svg>
                      <svg
                        class="w-2.5 h-2.5"
                        :class="{ 'text-blue-600': sortField === 'article' && sortDirection === 'desc', 'text-slate-400': !(sortField === 'article' && sortDirection === 'desc') }"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </button>
                </th>
                <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  Category
                </th>
                <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  Division &amp; Area
                </th>
                <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest">
                  <button
                    type="button"
                    @click="toggleSort('expiry_date')"
                    class="flex items-center gap-1 hover:text-slate-800 focus:outline-none uppercase"
                  >
                    Expiry Date
                    <span class="flex flex-col">
                      <svg
                        class="w-2.5 h-2.5 -mb-1"
                        :class="{ 'text-blue-600': sortField === 'expiry_date' && sortDirection === 'asc', 'text-slate-400': !(sortField === 'expiry_date' && sortDirection === 'asc') }"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                      </svg>
                      <svg
                        class="w-2.5 h-2.5"
                        :class="{ 'text-blue-600': sortField === 'expiry_date' && sortDirection === 'desc', 'text-slate-400': !(sortField === 'expiry_date' && sortDirection === 'desc') }"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                      >
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </button>
                </th>
                <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">
                  Quantity
                </th>
                <th class="px-5 py-3 text-[10px] font-bold text-slate-500 uppercase tracking-widest text-right">
                  Total Amount
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70">
              <tr v-if="paginatedItems.length === 0">
                <td colspan="6" class="px-6 py-10 text-center text-sm text-slate-500 font-medium">
                  No expiring supplies match the current filters.
                </td>
              </tr>
              <tr
                v-for="item in paginatedItems"
                :key="item.id"
                class="hover:bg-amber-50/40 transition-colors"
              >
                <td class="px-5 py-3 align-top">
                  <div class="font-bold text-slate-800">{{ item.article || '—' }}</div>
                  <div v-if="item.description" class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                    {{ item.description }}
                  </div>
                  <div v-if="item.stock_number" class="text-[11px] font-mono text-blue-700 font-semibold mt-0.5">
                    Stock No: {{ item.stock_number }}
                  </div>
                </td>
                <td class="px-5 py-3 align-top text-xs font-semibold text-slate-700">
                  {{ item.category_name }}
                </td>
                <td class="px-5 py-3 align-top text-xs">
                  <div class="font-semibold text-slate-800">{{ item.division_name || '—' }}</div>
                  <div v-if="item.area_name" class="text-slate-500 mt-0.5">{{ item.area_name }}</div>
                </td>
                <td class="px-5 py-3 align-top whitespace-nowrap">
                  <div class="flex flex-col items-start gap-1">
                    <span class="text-xs font-semibold text-slate-800">
                      {{ formatExpiryDate(item.expiry_date) }}
                    </span>
                    <span
                      v-if="item.is_expired"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-700"
                    >
                      Expired
                    </span>
                    <span
                      v-else-if="item.days_until_expiry === 0"
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-800"
                    >
                      Today
                    </span>
                    <span
                      v-else
                      class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-orange-100 text-orange-700"
                    >
                      In {{ item.days_until_expiry }}d
                    </span>
                  </div>
                </td>
                <td class="px-5 py-3 align-top text-right whitespace-nowrap font-semibold text-slate-800">
                  {{ Number(item.on_hand_per_count || 0).toLocaleString() }}
                  <span v-if="item.unit_of_measure" class="text-xs font-normal text-slate-500">
                    {{ item.unit_of_measure }}
                  </span>
                </td>
                <td class="px-5 py-3 align-top text-right whitespace-nowrap font-bold text-slate-900">
                  {{ formatCurrency(item.total_amount) }}
                </td>
              </tr>
            </tbody>
            <tfoot v-if="filteredAndSortedItems.length > 0" class="bg-amber-50/80 border-t border-amber-200/80">
              <tr class="font-bold text-xs">
                <td colspan="4" class="px-5 py-2.5 text-slate-700 uppercase tracking-wider">
                  Filtered Total ({{ filteredAndSortedItems.length }} {{ filteredAndSortedItems.length === 1 ? 'item' : 'items' }})
                </td>
                <td class="px-5 py-2.5 text-right text-slate-900 whitespace-nowrap">
                  {{ filteredTotalQuantity.toLocaleString() }}
                </td>
                <td class="px-5 py-2.5 text-right text-rose-700 whitespace-nowrap">
                  {{ formatCurrency(filteredTotalAmount) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
              <span class="text-xs font-medium text-slate-500">Rows per page:</span>
              <select
                v-model.number="perPage"
                class="bg-white border border-slate-300 rounded-lg pl-2 pr-7 py-1 text-xs font-medium text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-400"
              >
                <option v-for="size in [5, 10, 25, 50]" :key="size" :value="size">{{ size }}</option>
              </select>
            </div>
            <span class="text-xs font-medium text-slate-500">
              {{ paginationRangeText }}
            </span>
          </div>

          <div class="flex items-center gap-1">
            <button
              type="button"
              :disabled="currentPage <= 1"
              @click="currentPage--"
              class="px-2.5 py-1 rounded-lg text-xs font-medium border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
            >
              <ChevronLeft class="w-3.5 h-3.5" />
              Prev
            </button>

            <template v-for="(pageItem, idx) in visiblePageNumbers" :key="idx">
              <span
                v-if="pageItem === '...'"
                class="px-2 py-1 text-xs font-medium text-slate-400"
              >
                ...
              </span>
              <button
                v-else
                type="button"
                @click="currentPage = pageItem"
                :class="[
                  'px-3 py-1 rounded-lg text-xs font-semibold transition-colors',
                  currentPage === pageItem
                    ? 'bg-blue-600 text-white shadow-sm'
                    : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-100'
                ]"
              >
                {{ pageItem }}
              </button>
            </template>

            <button
              type="button"
              :disabled="currentPage >= totalPages"
              @click="currentPage++"
              class="px-2.5 py-1 rounded-lg text-xs font-medium border border-slate-300 bg-white text-slate-700 hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
            >
              Next
              <ChevronRight class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>
    </Modal>

    <!-- Generate Expiring Supplies Report Category Selection Modal -->
    <Modal :show="isReportModalOpen" maxWidth="md" @close="closeReportModal">
      <div class="p-6 bg-white rounded-lg">
        <div class="flex items-start justify-between gap-3 mb-4">
          <div>
            <h3 class="text-lg font-bold text-slate-800">Generate Expiring Supplies Report</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Select which categories to include in the printable / PDF report.
            </p>
          </div>
          <button
            type="button"
            @click="closeReportModal"
            class="p-1 rounded-lg text-slate-400 hover:text-slate-600"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="space-y-4">
          <!-- Division & Area Scope Selection -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                Division
              </label>
              <select
                v-model="reportDivisionId"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
              >
                <option value="">All Divisions (Whole Hospital)</option>
                <option v-for="div in availableDivisions" :key="div.id" :value="String(div.id)">
                  {{ div.name || div.div_name }}
                </option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                Area
              </label>
              <select
                v-model="reportAreaId"
                :disabled="!reportDivisionId"
                class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-3 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <option value="">{{ reportDivisionId ? 'All Areas' : 'Select Division first' }}</option>
                <option v-for="area in reportFilteredAreas" :key="area.id" :value="String(area.id)">
                  {{ area.name || area.area_name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Status Filter Selection -->
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
              Expiration Status Scope
            </label>
            <select
              v-model="reportStatusFilter"
              class="w-full rounded-xl border border-slate-300 bg-slate-50 text-slate-700 px-3.5 py-2 text-sm focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all"
            >
              <option value="all">All (Expired &amp; Close to Expiring)</option>
              <option value="expiring_soon">Close to Expiring Only</option>
              <option value="expired">Expired Items Only</option>
            </select>
          </div>

          <!-- Category Checkboxes -->
          <div>
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
              Categories
            </label>

            <!-- All Categories Toggle -->
            <label
              class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 cursor-pointer transition-colors mb-2"
              :class="{ 'opacity-60 cursor-not-allowed': reportScopedCategories.length === 0 }"
            >
              <div class="flex items-center gap-2.5">
                <input
                  type="checkbox"
                  :disabled="reportScopedCategories.length === 0"
                  :checked="isAllCategoriesSelected"
                  @change="toggleAllReportCategories"
                  class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-bold text-slate-800">All Categories</span>
              </div>
              <span class="text-xs font-semibold text-slate-500">
                {{ reportScopedCategories.length }} total
              </span>
            </label>

            <!-- Individual Categories -->
            <div v-if="reportScopedCategories.length > 0" class="max-h-56 overflow-y-auto space-y-1.5 pr-1">
              <label
                v-for="cat in reportScopedCategories"
                :key="cat.category_code"
                class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:border-blue-300 hover:bg-blue-50/30 cursor-pointer transition-colors"
              >
                <div class="flex items-center gap-2.5 min-w-0">
                  <input
                    type="checkbox"
                    :value="cat.category_code"
                    v-model="selectedReportCategories"
                    @change="onIndividualReportCategoryChange"
                    class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 shrink-0"
                  />
                  <span class="text-sm font-semibold text-slate-700 truncate">
                    {{ cat.category_name }}
                  </span>
                </div>
                <div class="flex items-center gap-2 shrink-0 text-xs">
                  <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 font-semibold">
                    {{ Number(cat.total_quantity || 0).toLocaleString() }} qty
                  </span>
                  <span class="font-bold text-rose-700">
                    {{ formatCurrency(cat.total_amount) }}
                  </span>
                </div>
              </label>
            </div>

            <p v-if="reportScopedCategories.length === 0" class="text-xs text-slate-500 font-medium py-3 text-center bg-slate-50 rounded-xl border border-slate-200">
              No expiring supplies match the selected division, area, and status scope.
            </p>
            <p v-else-if="selectedReportCategories.length === 0" class="text-xs text-rose-600 font-medium mt-2">
              Please select at least one category to generate the report.
            </p>
          </div>
        </div>

        <div class="pt-4 mt-5 border-t border-slate-200 flex justify-end gap-3">
          <button
            type="button"
            @click="closeReportModal"
            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50"
          >
            Cancel
          </button>
          <button
            type="button"
            :disabled="selectedReportCategories.length === 0 || reportScopedCategories.length === 0"
            @click="generateExpiringReport"
            class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-md flex items-center gap-1.5"
          >
            <FileText class="w-4 h-4" />
            Generate Report
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { AlertTriangle, Clock, Search, X, Eye, ChevronLeft, ChevronRight, FileText } from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import DivisionAreaFilter from '@/Components/DivisionAreaFilter.vue';
import { formatCurrency } from '@/utils/formatters.js';
import { useInventoryPermissions } from '@/Composables/useInventoryPermissions';

const props = defineProps({
  expiringSupplies: {
    type: Object,
    default: () => ({
      total_items_count: 0,
      total_quantity: 0,
      total_amount: 0,
      current_date_utc8: '',
      cutoff_date_utc8: '',
      categories: [],
    }),
  },
  divisions: {
    type: Array,
    default: () => [],
  },
  areas: {
    type: Array,
    default: () => [],
  },
  reportFilters: {
    type: Object,
    default: () => ({}),
  },
});

const { authUser, isSuperadmin, isSecretary } = useInventoryPermissions();
const isGlobalViewer = computed(() => isSuperadmin.value || isSecretary.value);

const isModalOpen = ref(false);
const isReportModalOpen = ref(false);
const selectedReportCategories = ref([]);
const reportStatusFilter = ref('all');
const reportDivisionId = ref('');
const reportAreaId = ref('');
const searchQuery = ref('');
const selectedCategory = ref('All');
const selectedDivisionId = ref('');
const selectedAreaId = ref('');
const expiryStatusFilter = ref('all'); // 'all' | 'expiring_soon' | 'expired'
const sortField = ref('expiry_date'); // 'article' | 'expiry_date'
const sortDirection = ref('asc'); // 'asc' | 'desc'
const currentPage = ref(1);
const perPage = ref(10);

const allItems = computed(() => {
  const categories = props.expiringSupplies?.categories || [];
  return categories.flatMap((cat) => cat.items || []);
});

const availableDivisions = computed(() => {
  let divs = props.divisions && props.divisions.length > 0
    ? props.divisions
    : Array.from(
        new Map(
          allItems.value
            .filter((item) => item.division_id && item.division_name)
            .map((item) => [String(item.division_id), { id: item.division_id, name: item.division_name }])
        ).values()
      );

  if (!props.reportFilters?.from_supplies_index && !isGlobalViewer.value && authUser.value?.division_id) {
    divs = divs.filter((d) => String(d.id) === String(authUser.value.division_id));
  }

  return divs;
});

const availableAreas = computed(() => {
  const rawAreas = props.areas && props.areas.length > 0
    ? props.areas
    : Array.from(
        new Map(
          allItems.value
            .filter((item) => item.area_id && item.area_name)
            .map((item) => [
              String(item.area_id),
              { id: item.area_id, name: item.area_name, division_id: item.division_id },
            ])
        ).values()
      );

  return rawAreas.filter((area) => {
    const name = (area.name || area.area_name || '').toLowerCase().trim();
    return name !== 'general area';
  });
});

const reportFilteredAreas = computed(() => {
  if (!reportDivisionId.value) return [];
  return availableAreas.value.filter((area) => String(area.division_id) === String(reportDivisionId.value));
});

watch(reportDivisionId, (newDivId) => {
  if (!newDivId) {
    reportAreaId.value = '';
    return;
  }
  const stillValid = reportFilteredAreas.value.some((a) => String(a.id) === String(reportAreaId.value));
  if (!stillValid) {
    reportAreaId.value = '';
  }
});

const isAllReportCategoriesMode = ref(true);

const filterItemsByScope = (items, divisionId, areaId, statusFilter = 'all') => {
  return (items || []).filter((item) => {
    if (divisionId && String(item.division_id) !== String(divisionId)) {
      return false;
    }
    if (areaId && String(item.area_id) !== String(areaId)) {
      return false;
    }
    if (statusFilter === 'expired' && !item.is_expired) {
      return false;
    }
    if (statusFilter === 'expiring_soon' && item.is_expired) {
      return false;
    }
    return true;
  });
};

const computeScopedCategories = (divisionId, areaId, statusFilter = 'all') => {
  const rawCategories = props.expiringSupplies?.categories || [];
  return rawCategories
    .map((cat) => {
      const items = filterItemsByScope(cat.items, divisionId, areaId, statusFilter);
      if (items.length === 0) return null;

      const totalQty = items.reduce((sum, item) => sum + Number(item.on_hand_per_count || 0), 0);
      const totalAmt = items.reduce((sum, item) => sum + Number(item.total_amount || 0), 0);

      return {
        ...cat,
        items_count: items.length,
        total_quantity: totalQty,
        total_amount: Math.round(totalAmt * 100) / 100,
        items,
      };
    })
    .filter(Boolean);
};

const viewScopedItems = computed(() => {
  return filterItemsByScope(allItems.value, selectedDivisionId.value, selectedAreaId.value, 'all');
});

const viewExpiredCount = computed(() => {
  return viewScopedItems.value.filter((item) => Boolean(item.is_expired)).length;
});

const viewExpiringSoonCount = computed(() => {
  return viewScopedItems.value.filter((item) => !item.is_expired).length;
});

const viewScopedCategories = computed(() => {
  return computeScopedCategories(selectedDivisionId.value, selectedAreaId.value, 'all');
});

const viewScopedTotals = computed(() => {
  return viewScopedCategories.value.reduce(
    (acc, cat) => {
      acc.total_quantity += Number(cat.total_quantity || 0);
      acc.total_amount = Math.round((acc.total_amount + Number(cat.total_amount || 0)) * 100) / 100;
      return acc;
    },
    { total_quantity: 0, total_amount: 0 }
  );
});

watch(viewScopedCategories, (cats) => {
  if (selectedCategory.value !== 'All' && !cats.some((c) => c.category_code === selectedCategory.value)) {
    selectedCategory.value = 'All';
  }
});

const reportScopedCategories = computed(() => {
  return computeScopedCategories(reportDivisionId.value, reportAreaId.value, reportStatusFilter.value);
});

const allReportCategoryCodes = computed(() => {
  return reportScopedCategories.value.map((c) => c.category_code);
});

const isAllCategoriesSelected = computed(() => {
  return (
    allReportCategoryCodes.value.length > 0 &&
    allReportCategoryCodes.value.every((code) => selectedReportCategories.value.includes(code))
  );
});

const toggleAllReportCategories = (event) => {
  isAllReportCategoriesMode.value = event.target.checked;
  if (event.target.checked) {
    selectedReportCategories.value = [...allReportCategoryCodes.value];
  } else {
    selectedReportCategories.value = [];
  }
};

const onIndividualReportCategoryChange = () => {
  isAllReportCategoriesMode.value = isAllCategoriesSelected.value;
};

watch(allReportCategoryCodes, (newCodes) => {
  if (isAllReportCategoriesMode.value) {
    selectedReportCategories.value = [...newCodes];
  } else {
    selectedReportCategories.value = selectedReportCategories.value.filter((code) => newCodes.includes(code));
  }
});

const openReportModal = (initialStatus = 'all') => {
  reportStatusFilter.value = ['all', 'expiring_soon', 'expired'].includes(initialStatus)
    ? initialStatus
    : 'all';

  reportDivisionId.value = String(selectedDivisionId.value || props.reportFilters?.division_id || '');
  reportAreaId.value = String(selectedAreaId.value || props.reportFilters?.area_id || '');

  if (
    isModalOpen.value &&
    selectedCategory.value !== 'All' &&
    allReportCategoryCodes.value.includes(selectedCategory.value)
  ) {
    selectedReportCategories.value = [selectedCategory.value];
    isAllReportCategoriesMode.value = allReportCategoryCodes.value.length === 1;
  } else {
    selectedReportCategories.value = [...allReportCategoryCodes.value];
    isAllReportCategoriesMode.value = true;
  }

  isReportModalOpen.value = true;
};

const closeReportModal = () => {
  isReportModalOpen.value = false;
};

const generateExpiringReport = () => {
  if (selectedReportCategories.value.length === 0 || reportScopedCategories.value.length === 0) return;

  const params = {
    categories: isAllCategoriesSelected.value ? ['All'] : selectedReportCategories.value,
    status_filter: reportStatusFilter.value,
  };

  if (props.reportFilters?.from_supplies_index) {
    params.from_supplies_index = 1;
  }
  if (reportDivisionId.value) {
    params.division_id = reportDivisionId.value;
  }
  if (reportAreaId.value) {
    params.area_id = reportAreaId.value;
  }

  const url = route('supplies.expiring_report.preview', params);

  window.open(url, '_blank');
  isReportModalOpen.value = false;
};

const expiredCount = computed(() => {
  return allItems.value.filter((item) => Boolean(item.is_expired)).length;
});

const expiringSoonCount = computed(() => {
  return allItems.value.filter((item) => !item.is_expired).length;
});

const openModal = (categoryCode = 'All') => {
  selectedCategory.value = categoryCode || 'All';
  currentPage.value = 1;
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const toggleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
};

const filteredAndSortedItems = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();

  const scoped = filterItemsByScope(
    allItems.value,
    selectedDivisionId.value,
    selectedAreaId.value,
    expiryStatusFilter.value
  );

  const filtered = scoped.filter((item) => {
    if (selectedCategory.value !== 'All' && item.category !== selectedCategory.value) {
      return false;
    }

    if (query) {
      const haystack = [
        item.article,
        item.description,
        item.stock_number,
        item.division_name,
        item.area_name,
        item.category_name,
      ]
        .filter(Boolean)
        .join(' ')
        .toLowerCase();

      if (!haystack.includes(query)) {
        return false;
      }
    }

    return true;
  });

  const dir = sortDirection.value === 'asc' ? 1 : -1;

  return [...filtered].sort((a, b) => {
    if (sortField.value === 'article') {
      const cmp = String(a.article || '').localeCompare(String(b.article || ''), undefined, {
        sensitivity: 'base',
      });
      if (cmp !== 0) return cmp * dir;
      return String(a.expiry_date || '').localeCompare(String(b.expiry_date || '')) * dir;
    }

    const cmp = String(a.expiry_date || '').localeCompare(String(b.expiry_date || ''));
    if (cmp !== 0) return cmp * dir;
    return String(a.article || '').localeCompare(String(b.article || ''), undefined, {
      sensitivity: 'base',
    });
  });
});

const filteredTotalQuantity = computed(() => {
  return filteredAndSortedItems.value.reduce(
    (sum, item) => sum + Number(item.on_hand_per_count || 0),
    0
  );
});

const filteredTotalAmount = computed(() => {
  return filteredAndSortedItems.value.reduce(
    (sum, item) => sum + Number(item.total_amount || 0),
    0
  );
});

const totalPages = computed(() => {
  return Math.max(1, Math.ceil(filteredAndSortedItems.value.length / perPage.value));
});

const paginatedItems = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredAndSortedItems.value.slice(start, start + perPage.value);
});

const paginationRangeText = computed(() => {
  const total = filteredAndSortedItems.value.length;
  if (total === 0) {
    return 'Showing 0 of 0 entries';
  }
  const from = (currentPage.value - 1) * perPage.value + 1;
  const to = Math.min(currentPage.value * perPage.value, total);
  return `Showing ${from} to ${to} of ${total} entries`;
});

const visiblePageNumbers = computed(() => {
  const total = totalPages.value;
  const current = currentPage.value;
  if (total <= 7) {
    return Array.from({ length: total }, (_, i) => i + 1);
  }
  const pages = [1];
  if (current > 3) pages.push('...');
  const start = Math.max(2, current - 1);
  const end = Math.min(total - 1, current + 1);
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  if (current < total - 2) pages.push('...');
  pages.push(total);
  return pages;
});

watch([searchQuery, selectedCategory, selectedDivisionId, selectedAreaId, expiryStatusFilter, sortField, sortDirection, perPage], () => {
  currentPage.value = 1;
});

watch(totalPages, (newTotal) => {
  if (currentPage.value > newTotal) {
    currentPage.value = newTotal;
  }
});

const formatExpiryDate = (dateStr) => {
  if (!dateStr) return '—';
  const parts = String(dateStr).slice(0, 10).split('-').map(Number);
  if (parts.length !== 3 || parts.some(Number.isNaN)) return dateStr;
  const [year, month, day] = parts;
  const d = new Date(Date.UTC(year, month - 1, day));
  return d.toLocaleDateString('en-US', {
    timeZone: 'UTC',
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  });
};
</script>
