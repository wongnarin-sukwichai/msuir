<script setup lang="ts">
import { watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useAlert } from '@/utils/alert';

const page = usePage();
const { toast } = useAlert();

// แก้ไข Watch ให้ตรวจสอบความปลอดภัย (Optional Chaining)
watch(
    () => page.props.flash, 
    (flash: any) => {
        if (!flash) return; // ถ้าไม่มีข้อมูล flash ไม่ต้องทำต่อ

        if (flash.message) {
            toast(flash.message, 'success');
        }
        if (flash.error) {
            toast(flash.error, 'error');
        }
    }, 
    { deep: true } // เอา immediate: true ออกเพื่อรอให้ App พร้อมก่อนค่อยตรวจ
);
</script>

<template>
    <Transition name="fade" mode="out-in">
        <div :key="$page.url || 'default'">
            <slot />
        </div>
    </Transition>
</template>
