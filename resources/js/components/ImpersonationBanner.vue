<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isImpersonating = computed(() => (page.props.auth as any)?.impersonating === true);

const leaveImpersonation = () => {
    router.post(route('impersonate.leave'));
};
</script>

<template>
    <div v-if="isImpersonating" class="sticky top-0 z-[110] flex items-center justify-center gap-3 bg-yellow-400 px-4 py-2.5 text-xs font-black text-slate-900">
        <i class="fa-solid fa-user-secret"></i>
        <span>คุณกำลังสวมสิทธิ์เป็นสมาชิกท่านนี้อยู่</span>
        <button
            @click="leaveImpersonation"
            class="ml-2 rounded-full bg-slate-900 px-4 py-1.5 text-[11px] font-black text-white transition-colors hover:bg-slate-800"
        >
            กลับสู่บัญชีแอดมิน
        </button>
    </div>
</template>
