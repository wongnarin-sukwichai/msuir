<script setup lang="ts">
import { useAlert } from '@/utils/alert';
import { useForm } from '@inertiajs/vue3';

const modelValue = defineModel<boolean>({ required: true });

const { toast } = useAlert();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const handleLogin = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onSuccess: () => {
            modelValue.value = false;
        },
        onError: () => {
            toast('อีเมลหรือรหัสผ่านไม่ถูกต้อง', 'error');
        },
    });
};

const loginWithGoogle = () => {
    window.location.href = route('google.redirect');
};
</script>

<template>
    <transition name="fade">
        <div v-if="modelValue" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="modelValue = false"></div>

            <div class="relative w-full max-w-md overflow-hidden transition-all bg-white shadow-2xl rounded-3xl">
                <div class="bg-[#1e3a8a] p-8 text-white">
                    <h2 class="text-2xl font-bold text-white">
                        เข้าสู่ระบบ <span class="text-yellow-500">MSU</span> <span class="text-yellow-500">IR</span>
                    </h2>
                    <p class="mt-1 text-sm text-slate-200">กรุณาลงชื่อเข้าใช้งาน</p>
                </div>

                <div class="p-8">
                    <form @submit.prevent="handleLogin" class="space-y-5">
                        <div>
                            <label class="block mb-2 text-sm font-bold text-slate-700">อีเมล</label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="name@example.com"
                                class="w-full px-4 py-3 text-sm transition-all border rounded-xl border-slate-200 bg-slate-50 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                            />
                        </div>

                        <div>
                            <label class="block mb-2 text-sm font-bold text-slate-700">รหัสผ่าน</label>
                            <input
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full px-4 py-3 text-sm transition-all border rounded-xl border-slate-200 bg-slate-50 focus:border-blue-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-blue-500/10"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-xl bg-[#1e3a8a] py-4 text-sm font-bold text-white shadow-lg transition-all hover:bg-blue-800 active:scale-[0.98] disabled:opacity-50"
                        >
                            <span v-if="form.processing">กำลังตรวจสอบ...</span>
                            <span v-else>เข้าสู่ระบบ</span>
                        </button>
                    </form>

                    <div class="relative flex items-center justify-center my-8">
                        <div class="h-[1px] w-full bg-slate-100"></div>
                        <span class="absolute px-4 text-xs font-bold tracking-widest uppercase bg-white text-slate-400">หรือ</span>
                    </div>

                    <button
                        @click="loginWithGoogle"
                        type="button"
                        class="flex w-full items-center justify-center space-x-3 rounded-xl border border-slate-200 py-3 text-sm font-bold text-slate-700 transition-all hover:bg-slate-50 active:scale-[0.98]"
                    >
                        <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" class="w-5 h-5" alt="Google" />
                        <span>เข้าสู่ระบบด้วย Google (@msu.ac.th)</span>
                    </button>
                </div>

                <div class="p-6 text-sm text-center bg-slate-50"></div>

                <button @click="modelValue = false" class="absolute right-6 top-6 text-white/60 hover:text-white">
                    <i class="text-xl fas fa-times"></i>
                </button>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
