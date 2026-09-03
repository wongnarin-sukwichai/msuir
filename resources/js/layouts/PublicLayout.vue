<script setup lang="ts">
import CookieBanner from '@/components/CookieBanner.vue';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';
import LoginModal from '@/components/LoginModal.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { useLoginGate } from '@/composables/useLoginGate';
import { Head } from '@inertiajs/vue3';

// Shared with the pages via a module singleton (see useLoginGate).
const { modalOpen: isLoginModalOpen } = useLoginGate();
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    </Head>
    <div class="min-h-screen custom-scrollbar bg-slate-50 text-slate-800 selection:bg-yellow-200 selection:text-yellow-900">
        <ImpersonationBanner />
        <PublicNavbar @open-login="isLoginModalOpen = true" />
        <slot />
        <LoginModal v-model="isLoginModalOpen" />
        <CookieBanner />
    </div>
</template>
