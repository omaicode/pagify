<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    activeHref: {
        type: String,
        default: '/',
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const containerRef = ref(null);
const moreMeasureRef = ref(null);
const measureItemRefs = ref([]);
const visibleCount = ref(0);
const overflowOpened = ref(false);

let resizeObserver = null;

const navItems = computed(() => (Array.isArray(props.items) ? props.items : []));
const visibleItems = computed(() => navItems.value.slice(0, visibleCount.value));
const overflowItems = computed(() => navItems.value.slice(visibleCount.value));

const normalizePath = (value) => {
    if (!value) {
        return '/';
    }

    try {
        const base = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
        const parsedUrl = new URL(String(value), base);
        const normalized = parsedUrl.pathname.replace(/\/+$/, '');

        return normalized || '/';
    } catch {
        const fallbackValue = String(value).split('?')[0].split('#')[0] || '/';
        const normalized = fallbackValue.replace(/\/+$/, '');

        return normalized || '/';
    }
};

const itemPatterns = (item) => {
    if (!item || typeof item !== 'object') {
        return [];
    }

    const rawPatterns = [item.href];

    if (Array.isArray(item.active_patterns)) {
        rawPatterns.push(...item.active_patterns);
    }

    return rawPatterns
        .map((pattern) => normalizePath(pattern))
        .filter((pattern, index, array) => pattern && array.indexOf(pattern) === index);
};

const matchScore = (item, currentPath) => {
    const patterns = itemPatterns(item);
    let score = -1;

    for (const pattern of patterns) {
        const matched = pattern === '/'
            ? currentPath === '/'
            : currentPath === pattern || currentPath.startsWith(`${pattern}/`);

        if (!matched) {
            continue;
        }

        score = Math.max(score, pattern.length);
    }

    return score;
};

const activeItemIndex = computed(() => {
    const currentPath = normalizePath(props.activeHref);

    let bestIndex = -1;
    let bestScore = -1;

    navItems.value.forEach((item, index) => {
        const score = matchScore(item, currentPath);

        if (score > bestScore) {
            bestScore = score;
            bestIndex = index;
        }
    });

    return bestIndex;
});

const isItemActive = (item) => {
    if (activeItemIndex.value < 0) {
        return false;
    }

    return navItems.value[activeItemIndex.value] === item;
};

const setMeasureItemRef = (el, index) => {
    if (!el) {
        return;
    }

    measureItemRefs.value[index] = el;
};

const closeOverflow = () => {
    overflowOpened.value = false;
};

const onWindowKeydown = (event) => {
    if (event.key === 'Escape') {
        closeOverflow();
    }
};

const onDocumentClick = (event) => {
    const target = event.target;

    if (!(target instanceof HTMLElement)) {
        closeOverflow();
        return;
    }

    if (target.closest('[data-top-nav]')) {
        return;
    }

    closeOverflow();
};

const recalculateVisibleItems = async () => {
    await nextTick();

    const items = navItems.value;
    if (!items.length) {
        visibleCount.value = 0;
        closeOverflow();
        return;
    }

    const containerWidth = containerRef.value?.clientWidth ?? 0;
    if (!containerWidth) {
        visibleCount.value = items.length;
        return;
    }

    const itemWidths = items.map((_, index) => {
        const itemEl = measureItemRefs.value[index];
        if (!itemEl) {
            return 0;
        }

        const styles = window.getComputedStyle(itemEl);
        const marginLeft = Number.parseFloat(styles.marginLeft || '0') || 0;
        const marginRight = Number.parseFloat(styles.marginRight || '0') || 0;

        return itemEl.getBoundingClientRect().width + marginLeft + marginRight;
    });

    const fallbackMoreWidth = 52;
    const measuredMoreWidth = moreMeasureRef.value?.getBoundingClientRect().width ?? fallbackMoreWidth;

    let countWithoutMore = 0;
    let usedWidth = 0;
    for (let index = 0; index < itemWidths.length; index += 1) {
        if (usedWidth + itemWidths[index] <= containerWidth) {
            usedWidth += itemWidths[index];
            countWithoutMore += 1;
            continue;
        }

        break;
    }

    if (countWithoutMore >= items.length) {
        visibleCount.value = items.length;
        closeOverflow();
        return;
    }

    let countWithMore = 0;
    usedWidth = 0;
    const availableWidth = Math.max(containerWidth - measuredMoreWidth, 0);

    for (let index = 0; index < itemWidths.length; index += 1) {
        if (usedWidth + itemWidths[index] <= availableWidth) {
            usedWidth += itemWidths[index];
            countWithMore += 1;
            continue;
        }

        break;
    }

    visibleCount.value = countWithMore;
};

onMounted(async () => {
    await recalculateVisibleItems();

    if (containerRef.value) {
        resizeObserver = new ResizeObserver(() => {
            recalculateVisibleItems();
        });
        resizeObserver.observe(containerRef.value);
    }

    window.addEventListener('resize', recalculateVisibleItems);
    window.addEventListener('keydown', onWindowKeydown);
    document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
    resizeObserver?.disconnect();
    window.removeEventListener('resize', recalculateVisibleItems);
    window.removeEventListener('keydown', onWindowKeydown);
    document.removeEventListener('click', onDocumentClick);
});

watch(
    () => props.items,
    async () => {
        measureItemRefs.value = [];
        await recalculateVisibleItems();
    },
    { deep: true },
);

watch(
    () => props.activeHref,
    async () => {
        await recalculateVisibleItems();
    },
);
</script>

<template>
    <div class="relative" data-top-nav>
        <div ref="containerRef" class="flex min-w-0 items-center justify-center gap-2">
            <a
                v-for="item in visibleItems"
                :key="item.route ?? item.href"
                :href="item.href"
                :class="isItemActive(item) ? 'pf-nav-pill-active' : 'pf-nav-pill hover:bg-[#f3f0ff]'"
                class="shrink-0"
            >
                {{ item.label }}
            </a>

            <div v-if="overflowItems.length" class="relative shrink-0">
                <button
                    type="button"
                    class="pf-nav-pill h-9 w-9 p-0 text-base text-[#4b5563] hover:bg-[#f3f0ff]"
                    :aria-label="t.more_menu ?? 'More menu'"
                    @click="overflowOpened = !overflowOpened"
                >
                    ⋮
                </button>

                <div
                    v-if="overflowOpened"
                    class="absolute right-0 z-50 mt-2 min-w-[180px] rounded-xl border border-[#e5deff] bg-white p-1.5 shadow-lg"
                >
                    <a
                        v-for="item in overflowItems"
                        :key="`overflow-${item.route ?? item.href}`"
                        :href="item.href"
                        :class="isItemActive(item) ? 'pf-nav-pill-active' : 'pf-nav-pill hover:bg-[#f3f0ff]'"
                        class="mb-1 block w-full last:mb-0"
                        @click="closeOverflow"
                    >
                        {{ item.label }}
                    </a>
                </div>
            </div>
        </div>

        <div class="invisible pointer-events-none absolute -z-10 h-0 overflow-hidden whitespace-nowrap">
            <a
                v-for="(item, index) in navItems"
                :key="`measure-${item.route ?? item.href}`"
                :ref="(el) => setMeasureItemRef(el, index)"
                :class="isItemActive(item) ? 'pf-nav-pill-active' : 'pf-nav-pill'"
                class="inline-flex"
            >
                {{ item.label }}
            </a>

            <button
                ref="moreMeasureRef"
                type="button"
                class="pf-nav-pill h-9 w-9 p-0 text-base"
            >
                ⋮
            </button>
        </div>
    </div>
</template>
