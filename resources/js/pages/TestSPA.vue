<script setup lang="ts">
import { Link, Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="หน้าทดสอบ SPA" />

    <div style="padding: 50px; font-family: sans-serif;">
        <h1 style="color: #ec4899;">นี่คือหน้า SPA หน้าที่สอง</h1>
        <p>สังเกตที่ Tab ของ Browser ดูครับ มันจะไม่มีการโหลดหน้าใหม่ (Refresh)</p>

        <Link href="/" style="color: #3b82f6; text-decoration: underline;">
            กลับไปหน้าหลัก
        </Link>
    </div>
</template>