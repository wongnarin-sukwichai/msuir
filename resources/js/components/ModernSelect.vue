<script setup lang="ts">
import { onClickOutside } from '@vueuse/core';
import { computed, ref } from 'vue';

interface Option {
  value: string | number | null;
  label: string;
}

const props = defineProps<{
  modelValue: string | number | null;
  options: Option[];
  placeholder?: string;
}>();

const emit = defineEmits<{ 'update:modelValue': [value: string | number | null] }>();

const isOpen = ref(false);
const rootRef = ref<HTMLElement | null>(null);

const selectedLabel = computed(() => props.options.find((o) => o.value === props.modelValue)?.label ?? props.placeholder ?? '');

const select = (value: string | number | null) => {
  emit('update:modelValue', value);
  isOpen.value = false;
};

onClickOutside(rootRef, () => {
  isOpen.value = false;
});
</script>

<template>
  <div ref="rootRef" class="relative">
    <button
      type="button"
      @click="isOpen = !isOpen"
      :class="[
        'w-full flex items-center justify-between gap-2 bg-slate-50 border px-4 py-3.5 rounded-2xl text-sm font-bold text-slate-700 outline-none transition-all',
        isOpen ? 'border-blue-900 bg-white ring-4 ring-blue-900/5' : 'border-slate-200 hover:border-slate-300'
      ]"
    >
      <span class="truncate">{{ selectedLabel }}</span>
      <i class="text-[10px] transition-transform fa-solid fa-chevron-down shrink-0 text-slate-400" :class="isOpen ? 'rotate-180' : ''"></i>
    </button>

    <transition name="select-fade">
      <div
        v-if="isOpen"
        class="absolute z-20 w-full p-2 mt-2 overflow-y-auto bg-white border shadow-2xl custom-scrollbar max-h-64 rounded-2xl border-slate-100"
      >
        <button
          v-for="opt in options"
          :key="String(opt.value)"
          type="button"
          @click="select(opt.value)"
          :class="[
            'w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-bold text-left transition-colors',
            opt.value === modelValue ? 'bg-blue-50 text-blue-900' : 'text-slate-600 hover:bg-slate-50'
          ]"
        >
          <span class="truncate">{{ opt.label }}</span>
          <i v-if="opt.value === modelValue" class="text-[10px] fa-solid fa-check shrink-0"></i>
        </button>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.select-fade-enter-active,
.select-fade-leave-active {
  transition: all 0.15s ease-out;
}
.select-fade-enter-from,
.select-fade-leave-to {
  opacity: 0;
  transform: translateY(6px);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
</style>
