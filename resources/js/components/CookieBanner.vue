<script setup lang="ts">
import { onMounted, ref } from 'vue';

const showCookie = ref(false);

const acceptCookies = () => {
    showCookie.value = false;
};

onMounted(() => {
    setTimeout(() => {
        showCookie.value = true;
    }, 1500);
});
</script>

<template>
    <transition name="cookie-fade">
        <div
            v-if="showCookie"
            class="fixed bottom-8 left-1/2 z-[100] flex w-[90%] max-w-3xl -translate-x-1/2 flex-col items-center justify-between rounded-3xl border border-slate-100 bg-white/95 p-6 shadow-2xl backdrop-blur md:flex-row"
        >
            <div class="flex items-center mb-4 md:mb-0">
                <div class="flex items-center justify-center w-12 h-12 mr-4 text-yellow-600 shrink-0 rounded-2xl bg-yellow-50">
                    <i class="text-xl fas fa-cookie-bite"></i>
                </div>
                <p class="text-sm leading-snug text-slate-600">เราใช้คุกกี้เพื่อเพิ่มประสิทธิภาพในการใช้งานและมอบประสบการณ์ที่ดีที่สุดแก่คุณ</p>
            </div>
            <button
                @click="acceptCookies"
                type="button"
                class="flex-1 px-8 py-3 mb-4 text-sm font-bold text-white transition-all shadow-lg rounded-2xl bg-slate-900 hover:bg-blue-900 active:scale-95 md:mb-0 md:flex-none"
            >
                ยินยอม
            </button>
        </div>
    </transition>
</template>

<style scoped>
.cookie-fade-enter-active,
.cookie-fade-leave-active {
    transition: all 0.4s ease;
}
.cookie-fade-enter-from,
.cookie-fade-leave-to {
    opacity: 0;
    transform: translate(-50%, 2rem);
}
</style>
