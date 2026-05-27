<script setup lang="ts">
import CookieBanner from '@/components/CookieBanner.vue';
import LoginModal from '@/components/LoginModal.vue';
import PublicNavbar from '@/components/PublicNavbar.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const isLoginModalOpen = ref(false);
</script>

<template>
    <Head>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    </Head>
    <div class="min-h-screen custom-scrollbar bg-slate-50 text-slate-800 selection:bg-yellow-200 selection:text-yellow-900">
        <PublicNavbar @open-login="isLoginModalOpen = true" />
        <slot />
        <LoginModal v-model="isLoginModalOpen" />
        <CookieBanner />
    </div>
</template>
