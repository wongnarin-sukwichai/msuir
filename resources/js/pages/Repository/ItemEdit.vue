<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ModernSelect from '@/components/ModernSelect.vue';

type ItemStatus = 'approved' | 'pending' | 'action_required';

interface EditItem {
  id: number;
  collection_id: number;
  title: string;
  language: 'tha' | 'eng';
  year_issued: number | null;
  department_id: number | null;
  rights: string | null;
  degree: string | null;
  description: string | null;
  fulltext_url: string | null;
  fulltext_path: string | null;
  status: ItemStatus;
  review_note: string | null;
  alt_titles: string[];
  creator: string | null;
  contributors: string[];
  subjects: string[];
}

const props = defineProps<{
  item: EditItem;
  collections: { id: number; name: string }[];
  departments: { id: number; name: string }[];
}>();

const statusMeta: Record<ItemStatus, { label: string; cls: string }> = {
  approved: { label: 'เผยแพร่แล้ว', cls: 'bg-green-100 text-green-700' },
  pending: { label: 'รอตรวจสอบ', cls: 'bg-yellow-100 text-yellow-700' },
  action_required: { label: 'ต้องแก้ไข', cls: 'bg-red-100 text-red-700' },
};

const form = ref({
  collection_id: props.item.collection_id as number | null,
  title: props.item.title,
  language: props.item.language,
  alt_titles: [...props.item.alt_titles],
  creator: props.item.creator ?? '',
  contributors: [...props.item.contributors],
  year_issued: props.item.year_issued,
  department_id: props.item.department_id,
  rights: props.item.rights ?? '',
  degree: props.item.degree ?? '',
  description: props.item.description ?? '',
  subjects: [...props.item.subjects],
  fulltext_mode: (props.item.fulltext_path && !props.item.fulltext_url ? 'file' : 'url') as 'url' | 'file',
  fulltext_url: props.item.fulltext_url ?? '',
  fulltext_file: null as File | null,
});

const busy = ref(false);
const errors = ref<Record<string, string>>({});
const fileInput = ref<HTMLInputElement | null>(null);

const addRow = (key: 'alt_titles' | 'contributors' | 'subjects') => form.value[key].push('');
const removeRow = (key: 'alt_titles' | 'contributors' | 'subjects', i: number) => form.value[key].splice(i, 1);
const onFilePicked = (e: Event) => { form.value.fulltext_file = (e.target as HTMLInputElement).files?.[0] ?? null; };

const hasFulltext = () =>
  form.value.fulltext_mode === 'url'
    ? form.value.fulltext_url.trim() !== ''
    : !!form.value.fulltext_file || !!props.item.fulltext_path;

const submit = () => {
  if (!form.value.collection_id || form.value.title.trim() === '') {
    errors.value = { title: 'ต้องเลือกคอลเลกชันและกรอกชื่อเรื่อง' };
    return;
  }
  if (!hasFulltext()) {
    errors.value = { fulltext_url: 'ต้องมีลิงก์หรือไฟล์ฉบับเต็ม' };
    return;
  }
  const f = form.value;
  busy.value = true;
  errors.value = {};
  router.post(route('admin.repository.items.update', props.item.id), {
    _method: 'put',
    collection_id: f.collection_id,
    title: f.title,
    language: f.language,
    alt_titles: f.alt_titles.filter((t) => t.trim() !== ''),
    creator: f.creator || null,
    contributors: f.contributors.filter((t) => t.trim() !== ''),
    year_issued: f.year_issued,
    department_id: f.department_id,
    rights: f.rights || null,
    degree: f.degree || null,
    description: f.description || null,
    subjects: f.subjects.filter((t) => t.trim() !== ''),
    fulltext_url: f.fulltext_mode === 'url' ? (f.fulltext_url || null) : null,
    fulltext_file: f.fulltext_mode === 'file' ? f.fulltext_file : null,
  }, {
    forceFormData: true,
    onError: (e) => { busy.value = false; errors.value = e as Record<string, string>; },
    onFinish: () => { busy.value = false; },
  });
};

const inputCls =
  'w-full px-4 py-3 text-sm font-bold border outline-none bg-slate-50 border-slate-200 focus:border-blue-900 focus:bg-white rounded-2xl';
</script>

<template>
  <Head :title="`แก้ไขรายการ #${props.item.id} | MSU IR`">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  </Head>

  <div class="min-h-screen bg-slate-50 font-sarabun">
    <div class="max-w-3xl px-4 py-8 mx-auto sm:px-6">

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <Link :href="route('dashboard')" class="flex items-center gap-2 text-xs font-black text-slate-500 hover:text-[#1e3a8a]">
          <i class="fa-solid fa-chevron-left text-[10px]"></i> กลับไปแดชบอร์ด
        </Link>
        <span :class="['px-3 py-1 rounded-full text-[10px] font-black', statusMeta[props.item.status].cls]">
          {{ statusMeta[props.item.status].label }}
        </span>
      </div>

      <h1 class="text-2xl font-black tracking-tight text-slate-900">แก้ไขรายการ #{{ props.item.id }}</h1>
      <p class="mt-1 text-xs font-bold text-slate-400">ปรับปรุงข้อมูลบรรณานุกรมของรายการในคลัง</p>

      <div v-if="props.item.status === 'action_required' && props.item.review_note"
           class="mt-4 rounded-2xl bg-red-50 border border-red-100 px-4 py-3 text-xs font-bold text-red-700">
        <i class="fa-solid fa-pen-to-square mr-1"></i> หมายเหตุจากผู้ตรวจ: {{ props.item.review_note }}
      </div>

      <form @submit.prevent="submit" class="mt-6 space-y-6">

        <!-- Section: ประเภท / ชื่อเรื่อง -->
        <section class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm space-y-5">
          <h2 class="text-sm font-black tracking-widest uppercase text-slate-400">ประเภท / ชื่อเรื่อง</h2>
          <div>
            <label class="block mb-2 text-xs font-black text-slate-500">คอลเลกชัน <span class="text-red-500">*</span></label>
            <ModernSelect v-model="form.collection_id" placeholder="เลือกคอลเลกชัน"
              :options="props.collections.map(c => ({ value: c.id, label: c.name }))" />
          </div>
          <div>
            <label class="block mb-2 text-xs font-black text-slate-500">ชื่อเรื่อง <span class="text-red-500">*</span></label>
            <textarea v-model="form.title" rows="2" :class="[inputCls, 'resize-none']"></textarea>
            <p v-if="errors.title" class="mt-1 text-[11px] font-bold text-red-500">{{ errors.title }}</p>
          </div>
          <div>
            <label class="block mb-2 text-xs font-black text-slate-500">ภาษา</label>
            <div class="flex gap-3">
              <button type="button" @click="form.language = 'tha'" :class="['flex-1 py-3 rounded-2xl text-xs font-black border-2 transition', form.language === 'tha' ? 'bg-blue-50 border-blue-900 text-blue-900' : 'bg-slate-50 border-slate-200 text-slate-400']">ไทย</button>
              <button type="button" @click="form.language = 'eng'" :class="['flex-1 py-3 rounded-2xl text-xs font-black border-2 transition', form.language === 'eng' ? 'bg-blue-50 border-blue-900 text-blue-900' : 'bg-slate-50 border-slate-200 text-slate-400']">อังกฤษ</button>
            </div>
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-black text-slate-500">ชื่อเรื่องรอง / คู่ขนาน</label>
              <button type="button" @click="addRow('alt_titles')" class="text-[11px] font-black text-[#1e3a8a] hover:underline"><i class="fa-solid fa-plus mr-1"></i>เพิ่ม</button>
            </div>
            <div v-if="form.alt_titles.length === 0" class="text-[11px] font-bold text-slate-300">— ไม่มี —</div>
            <div v-for="(t, i) in form.alt_titles" :key="i" class="flex gap-2 mb-2">
              <input v-model="form.alt_titles[i]" type="text" :class="[inputCls, 'rounded-xl']" />
              <button type="button" @click="removeRow('alt_titles', i)" class="flex items-center justify-center text-red-500 rounded-xl w-11 bg-red-50 hover:bg-red-500 hover:text-white shrink-0"><i class="text-xs fa-solid fa-xmark"></i></button>
            </div>
          </div>
        </section>

        <!-- Section: ผู้แต่ง -->
        <section class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm space-y-5">
          <h2 class="text-sm font-black tracking-widest uppercase text-slate-400">ผู้แต่ง</h2>
          <div>
            <label class="block mb-2 text-xs font-black text-slate-500">ผู้แต่งหลัก</label>
            <input v-model="form.creator" type="text" :class="inputCls" />
          </div>
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="text-xs font-black text-slate-500">ผู้แต่งร่วม</label>
              <button type="button" @click="addRow('contributors')" class="text-[11px] font-black text-[#1e3a8a] hover:underline"><i class="fa-solid fa-plus mr-1"></i>เพิ่ม</button>
            </div>
            <div v-if="form.contributors.length === 0" class="text-[11px] font-bold text-slate-300">— ไม่มี —</div>
            <div v-for="(c, i) in form.contributors" :key="i" class="flex gap-2 mb-2">
              <span class="flex items-center justify-center text-xs font-black rounded-xl w-9 bg-slate-100 text-slate-400 shrink-0">{{ i + 1 }}</span>
              <input v-model="form.contributors[i]" type="text" :class="[inputCls, 'rounded-xl']" />
              <button type="button" @click="removeRow('contributors', i)" class="flex items-center justify-center text-red-500 rounded-xl w-11 bg-red-50 hover:bg-red-500 hover:text-white shrink-0"><i class="text-xs fa-solid fa-xmark"></i></button>
            </div>
          </div>
        </section>

        <!-- Section: บรรณานุกรม -->
        <section class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm space-y-5">
          <h2 class="text-sm font-black tracking-widest uppercase text-slate-400">บรรณานุกรม</h2>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-2 text-xs font-black text-slate-500">ปี (พ.ศ./ค.ศ. ตามต้นฉบับ)</label>
              <input v-model.number="form.year_issued" type="number" :class="inputCls" />
            </div>
            <div>
              <label class="block mb-2 text-xs font-black text-slate-500">หน่วยงาน / คณะ</label>
              <ModernSelect v-model="form.department_id"
                :options="[{ value: null, label: 'ไม่ระบุ' }, ...props.departments.map(d => ({ value: d.id, label: d.name }))]" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block mb-2 text-xs font-black text-slate-500">สิทธิ์ (rights)</label>
              <input v-model="form.rights" type="text" :class="inputCls" />
            </div>
            <div>
              <label class="block mb-2 text-xs font-black text-slate-500">ระดับปริญญา (degree)</label>
              <input v-model="form.degree" type="text" :class="inputCls" />
            </div>
          </div>
          <div>
            <label class="block mb-2 text-xs font-black text-slate-500">บทคัดย่อ / คำอธิบาย</label>
            <textarea v-model="form.description" rows="4" :class="[inputCls, 'resize-none']"></textarea>
          </div>
        </section>

        <!-- Section: หัวเรื่อง -->
        <section class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-sm font-black tracking-widest uppercase text-slate-400">หัวเรื่อง</h2>
            <button type="button" @click="addRow('subjects')" class="text-[11px] font-black text-[#1e3a8a] hover:underline"><i class="fa-solid fa-plus mr-1"></i>เพิ่ม</button>
          </div>
          <div v-if="form.subjects.length === 0" class="text-[11px] font-bold text-slate-300">— ไม่มี —</div>
          <div v-for="(s, i) in form.subjects" :key="i" class="flex gap-2">
            <input v-model="form.subjects[i]" type="text" :class="[inputCls, 'rounded-xl']" />
            <button type="button" @click="removeRow('subjects', i)" class="flex items-center justify-center text-red-500 rounded-xl w-11 bg-red-50 hover:bg-red-500 hover:text-white shrink-0"><i class="text-xs fa-solid fa-xmark"></i></button>
          </div>
        </section>

        <!-- Section: ไฟล์ฉบับเต็ม -->
        <section class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm space-y-4">
          <h2 class="text-sm font-black tracking-widest uppercase text-slate-400">ไฟล์ฉบับเต็ม</h2>
          <div class="flex gap-3">
            <button type="button" @click="form.fulltext_mode = 'url'" :class="['flex-1 py-3 rounded-2xl text-xs font-black border-2 transition', form.fulltext_mode === 'url' ? 'bg-blue-50 border-blue-900 text-blue-900' : 'bg-slate-50 border-slate-200 text-slate-400']"><i class="fa-solid fa-link mr-1.5"></i>ลิงก์</button>
            <button type="button" @click="form.fulltext_mode = 'file'" :class="['flex-1 py-3 rounded-2xl text-xs font-black border-2 transition', form.fulltext_mode === 'file' ? 'bg-blue-50 border-blue-900 text-blue-900' : 'bg-slate-50 border-slate-200 text-slate-400']"><i class="fa-solid fa-file-arrow-up mr-1.5"></i>อัปโหลด PDF</button>
          </div>
          <input v-if="form.fulltext_mode === 'url'" v-model="form.fulltext_url" type="url" placeholder="https://…/fulltext.pdf" :class="inputCls" />
          <div v-else class="space-y-2">
            <p v-if="props.item.fulltext_path" class="text-[11px] font-bold text-slate-400">
              ไฟล์ปัจจุบัน: <span class="text-slate-600">{{ props.item.fulltext_path.split('/').pop() }}</span> — เลือกไฟล์ใหม่เพื่อแทนที่
            </p>
            <label class="flex flex-col items-center justify-center gap-1 p-6 text-center border-2 border-dashed cursor-pointer rounded-2xl border-slate-200 hover:border-blue-300 hover:bg-slate-50">
              <i class="text-2xl fa-solid fa-file-arrow-up text-slate-400"></i>
              <span class="text-xs font-black text-slate-700">{{ form.fulltext_file ? form.fulltext_file.name : 'เลือกไฟล์ PDF' }}</span>
              <span class="text-[10px] font-bold text-slate-400">ไม่เกิน 50MB</span>
              <input ref="fileInput" type="file" accept="application/pdf,.pdf" class="hidden" @change="onFilePicked" />
            </label>
          </div>
          <p v-if="errors.fulltext_url || errors.fulltext_file" class="text-[11px] font-bold text-red-500">
            {{ errors.fulltext_url || errors.fulltext_file }}
          </p>
        </section>

        <div class="flex items-center gap-3">
          <button type="submit" :disabled="busy"
            class="bg-[#1e3a8a] hover:bg-blue-800 text-white px-8 py-3.5 rounded-2xl text-xs font-black shadow-lg shadow-blue-900/10 active:scale-95 transition-all disabled:opacity-40">
            <i v-if="busy" class="mr-1.5 fa-solid fa-spinner fa-spin"></i>บันทึกการแก้ไข
          </button>
          <Link :href="route('dashboard')" class="px-6 py-3.5 text-xs font-bold rounded-2xl text-slate-500 hover:bg-white">ยกเลิก</Link>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;700&display=swap');
.font-sarabun { font-family: 'Anuphan', sans-serif; }
</style>
