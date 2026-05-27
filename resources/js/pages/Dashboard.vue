<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { type BreadcrumbItem } from '@/types';

// --- Interfaces & Types ---
interface Submission {
  id: string;
  title: string;
  author: string;
  type: string;
  date: string;
  status: 'approved' | 'pending' | 'action_required';
  downloads: number;
  views: number;
}

interface Member {
  id: string;
  name: string;
  email: string;
  role: 'admin' | 'staff' | 'student' | 'lecturer';
  department: string;
  status: 'active' | 'suspended';
}

interface NewDoc {
  title: string;
  author: string;
  type: string;
  status: 'pending';
}

interface Toast {
  show: boolean;
  message: string;
  type: 'success' | 'warning' | 'danger';
}

// --- Breadcrumbs Setup ---
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

// --- States ---
const activeTab = ref<string>('dashboard'); // dashboard, repository, approvals, analytics, members, settings
const isUploadModalOpen = ref<boolean>(false);
const isMobileMenuOpen = ref<boolean>(false);
const isProfileOpen = ref<boolean>(false);
const searchQuery = ref<string>('');
const categoryFilter = ref<string>('all');
const isSidebarCollapsed = ref<boolean>(false); // สำหรับการพับ-กาง Sidebar บน PC

// --- Form State (For uploading new item) ---
const newDoc = ref<NewDoc>({
  title: '',
  author: '',
  type: 'วิทยานิพนธ์',
  status: 'pending'
});

// --- Toast State ---
const toast = ref<Toast>({
  show: false,
  message: '',
  type: 'success'
});

// --- Mock Data: Submissions ---
const submissions = ref<Submission[]>([
  { id: 'THE-2026-001', title: 'โมเดลการพัฒนาสารสนเทศท้องถิ่นเพื่อการท่องเที่ยวเชิงวัฒนธรรมอย่างยั่งยืนในลุ่มน้ำชี', author: 'ดร.สมศักดิ์ รักเรียน', type: 'วิทยานิพนธ์', date: '2026-05-20', status: 'approved', downloads: 142, views: 520 },
  { id: 'RES-2026-004', title: 'การวิเคราะห์สารสกัดจากพืชสมุนไพรในป่าชุมชนเพื่อต้านอนุมูลอิสระ', author: 'รศ.ดร.มนตรี มีสุข', type: 'งานวิจัยอาจารย์', date: '2026-05-24', status: 'pending', downloads: 0, views: 12 },
  { id: 'ART-2026-012', title: 'แนวทางการใช้ปัญญาประดิษฐ์ (AI) ในการพัฒนาหลักสูตรท้องถิ่นมหาสารคาม', author: 'ดร.สมชาย ใจดี', type: 'วารสารวิชาการ', date: '2026-05-25', status: 'pending', downloads: 0, views: 4 },
  { id: 'THE-2026-003', title: 'ระบบคลาวด์คอมพิวติ้งเพื่อการจัดการฐานข้อมูลขนาดใหญ่ในสถาบันอุดมศึกษา', author: 'นายกิตติศักดิ์ มุ่งมั่น', type: 'วิทยานิพนธ์', date: '2026-05-18', status: 'approved', downloads: 98, views: 340 },
  { id: 'LOC-2026-007', title: 'คลังรวบรวมลายผ้าทอพื้นบ้านอีสานลุ่มน้ำชีและลุ่มน้ำมูลประยุกต์', author: 'อ.สุจิตรา งามยิ่ง', type: 'ฐานข้อมูลท้องถิ่น', date: '2026-05-22', status: 'action_required', downloads: 15, views: 88 }
]);

// --- Mock Data: Members ---
const members = ref<Member[]>([
  { id: 'MEM-001', name: 'ดร.สมศักดิ์ รักเรียน', email: 'somsak.r@msu.ac.th', role: 'admin', department: 'สำนักวิทยบริการ', status: 'active' },
  { id: 'MEM-002', name: 'รศ.ดร.มนตรี มีสุข', email: 'montri.m@msu.ac.th', role: 'lecturer', department: 'คณะวิทยาศาสตร์', status: 'active' },
  { id: 'MEM-003', name: 'นายกิตติศักดิ์ มุ่งมั่น', email: 'kittisak.m@msu.ac.th', role: 'student', department: 'คณะวิทยาการสารสนเทศ', status: 'active' },
  { id: 'MEM-004', name: 'อ.สุจิตรา งามยิ่ง', email: 'suchitra.n@msu.ac.th', role: 'staff', department: 'สถาบันวิจัยศิลปะและวัฒนธรรมอีสาน', status: 'active' },
  { id: 'MEM-005', name: 'นายวิชาการ ก้าวไกล', email: 'wichakan.k@msu.ac.th', role: 'student', department: 'คณะวิศวกรรมศาสตร์', status: 'suspended' }
]);

// --- Dynamic calculations ---
const totalItems = computed(() => submissions.value.length);
const approvedItems = computed(() => submissions.value.filter(s => s.status === 'approved').length);
const pendingItems = computed(() => submissions.value.filter(s => s.status === 'pending').length);
const actionRequiredItems = computed(() => submissions.value.filter(s => s.status === 'action_required').length);

const filteredSubmissions = computed(() => {
  return submissions.value.filter(item => {
    const matchesSearch = item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                          item.author.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                          item.id.toLowerCase().includes(searchQuery.value.toLowerCase());
    const matchesCategory = categoryFilter.value === 'all' || item.type === categoryFilter.value;
    return matchesSearch && matchesCategory;
  });
});

const filteredMembers = computed(() => {
  return members.value.filter(member => {
    return member.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
           member.email.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
           member.department.toLowerCase().includes(searchQuery.value.toLowerCase());
  });
});

// --- Trigger Toast Notification ---
const triggerToast = (message: string, type: 'success' | 'warning' | 'danger' = 'success') => {
  toast.value = { show: true, message, type };
  setTimeout(() => {
    toast.value.show = false;
  }, 4000);
};

// --- Action Handlers ---
const handleApprove = (id: string) => {
  submissions.value = submissions.value.map(sub => 
    sub.id === id ? { ...sub, status: 'approved' } : sub
  );
  triggerToast('อนุมัติการเผยแพร่เอกสารเรียบร้อยแล้ว', 'success');
};

const handleReject = (id: string) => {
  submissions.value = submissions.value.map(sub => 
    sub.id === id ? { ...sub, status: 'action_required' } : sub
  );
  triggerToast('ปฏิเสธการอนุมัติเอกสารแล้ว (ผู้ส่งต้องแก้ไขข้อมูล)', 'warning');
};

const handleDelete = (id: string) => {
  if (confirm('คุณแน่ใจหรือไม่ว่าต้องการลบเอกสารนี้ออกจากระบบคลังข้อมูลดิจิทัล?')) {
    submissions.value = submissions.value.filter(sub => sub.id !== id);
    triggerToast('ลบรายการเอกสารสำเร็จ', 'danger');
  }
};

const handleToggleUserStatus = (id: string) => {
  members.value = members.value.map(mem => {
    if (mem.id === id) {
      const newStatus = mem.status === 'active' ? 'suspended' : 'active';
      triggerToast(`เปลี่ยนสถานะผู้ใช้งานเป็น ${newStatus === 'active' ? 'ปกติ' : 'ระงับการใช้งาน'} สำเร็จ`, 'warning');
      return { ...mem, status: newStatus };
    }
    return mem;
  });
};

const handleCreateDocument = () => {
  if (!newDoc.value.title || !newDoc.value.author) {
    triggerToast('กรุณากรอกข้อมูลให้ครบถ้วน', 'danger');
    return;
  }
  
  const prefix = newDoc.value.type === 'วิทยานิพนธ์' ? 'THE' : newDoc.value.type === 'งานวิจัยอาจารย์' ? 'RES' : 'ART';
  const generatedId = `${prefix}-2026-${String(submissions.value.length + 1).padStart(3, '0')}`;
  
  const itemToAdd: Submission = {
    id: generatedId,
    title: newDoc.value.title,
    author: newDoc.value.author,
    type: newDoc.value.type,
    date: new Date().toISOString().split('T')[0],
    status: 'pending',
    downloads: 0,
    views: 1
  };

  submissions.value = [itemToAdd, ...submissions.value];
  isUploadModalOpen.value = false;
  newDoc.value = { title: '', author: '', type: 'วิทยานิพนธ์', status: 'pending' };
  triggerToast('ส่งข้อมูลคำขออัปโหลดเอกสารสำเร็จ เจ้าหน้าที่จะรีบตรวจสอบ', 'success');
};

const logout = async () => {
  const result = await Swal.fire({
    title: 'ออกจากระบบ?',
    text: 'คุณต้องการออกจากระบบหลังบ้านใช่หรือไม่',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#1e3a8a',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'ออกจากระบบ',
    cancelButtonText: 'ยกเลิก',
    reverseButtons: true,
  });
  if (result.isConfirmed) router.post(route('logout'));
};

// --- Watchers for Overflow handling & syncing ---
watch(isUploadModalOpen, (newVal) => {
  document.body.style.overflow = newVal ? 'hidden' : '';
});
</script>

<template>
  <Head title="แผงผู้ดูแลระบบหลังบ้าน | MSU IR">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
  </Head>

  <div class="flex min-h-screen bg-slate-50 text-slate-800 font-sarabun selection:bg-yellow-100 selection:text-slate-900 custom-scrollbar">
    
    <!-- --- SIDEBAR NAVIGATION (Desktop - LG ขึ้นไป - ปรับปรุงเป็นแบบพับเก็บได้ Collapsible) --- -->
    <aside 
      :class="[
        isSidebarCollapsed ? 'w-20' : 'w-64', 
        'bg-[#1e3a8a] text-white flex flex-col justify-between p-6 border-r border-blue-900/40 relative z-30 shrink-0 hidden lg:flex transition-all duration-300'
      ]"
    >
      <div class="space-y-10">
        <!-- Brand/Logo (จัดตัวอักษร M ให้อยู่กึ่งกลางเป๊ะ) -->
        <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'space-x-3'">
          <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-[#1e3a8a] font-black text-xl shadow-xl shadow-blue-950/40 shrink-0 select-none">
            M
          </div>
          <div v-if="!isSidebarCollapsed" class="min-w-0 transition-opacity duration-300">
            <span class="block text-base font-black leading-tight tracking-tight text-white uppercase truncate">MSU IR Portal</span>
            <span class="text-[9px] text-yellow-400 tracking-wider uppercase font-black block truncate">Management</span>
          </div>
        </div>

        <!-- Sidebar Collapse Toggle Button (PC Version) -->
        <button 
          @click="isSidebarCollapsed = !isSidebarCollapsed"
          class="w-full py-2.5 bg-blue-950/40 hover:bg-blue-950/70 rounded-xl transition-all flex items-center justify-center text-blue-200 hover:text-white outline-none"
        >
          <i v-if="!isSidebarCollapsed" class="text-xs fa-solid fa-chevron-left"></i>
          <i v-else class="text-xs fa-solid fa-chevron-right"></i>
          <span v-if="!isSidebarCollapsed" class="text-[10px] font-black uppercase tracking-wider ml-2">ย่อแถบข้าง</span>
        </button>

        <!-- Menu Tabs (เปลี่ยนมาใช้ไอคอนของ Font Awesome 6) -->
        <nav class="space-y-1.5">
          <button 
            @click="activeTab = 'dashboard'"
            :class="[
              'w-full flex items-center rounded-xl text-xs font-bold transition-all duration-300 outline-none',
              isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
              activeTab === 'dashboard' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white'
            ]"
            title="ภาพรวมแผงควบคุม"
          >
            <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-chart-line"></i>
            <span v-if="!isSidebarCollapsed">ภาพรวมแผงควบคุม</span>
          </button>

          <button 
            @click="activeTab = 'repository'"
            :class="[
              'w-full flex items-center rounded-xl text-xs font-bold transition-all duration-300 outline-none',
              isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
              activeTab === 'repository' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white'
            ]"
            title="จัดการคลังข้อมูล"
          >
            <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-folder-open"></i>
            <span v-if="!isSidebarCollapsed" class="truncate">จัดการคลังข้อมูล</span>
            <span v-if="totalItems > 0 && !isSidebarCollapsed" class="ml-auto bg-blue-950 text-white text-[9px] font-black px-2 py-0.5 rounded-full">
              {{ totalItems }}
            </span>
          </button>

          <button 
            @click="activeTab = 'approvals'"
            :class="[
              'w-full flex items-center rounded-xl text-xs font-bold transition-all duration-300 outline-none',
              isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
              activeTab === 'approvals' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white'
            ]"
            title="คิวตรวจสอบข้อมูล"
          >
            <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-clipboard-check"></i>
            <span v-if="!isSidebarCollapsed" class="truncate">คิวตรวจสอบข้อมูล</span>
            <span v-if="pendingItems > 0 && !isSidebarCollapsed" class="ml-auto bg-red-500 text-white text-[9px] font-black px-2 py-0.5 rounded-full animate-pulse">
              {{ pendingItems }}
            </span>
          </button>

          <button 
            @click="activeTab = 'analytics'"
            :class="[
              'w-full flex items-center rounded-xl text-xs font-bold transition-all duration-300 outline-none',
              isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
              activeTab === 'analytics' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white'
            ]"
            title="สถิติและการดาวน์โหลด"
          >
            <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-chart-bar"></i>
            <span v-if="!isSidebarCollapsed" class="truncate">สถิติและการดาวน์โหลด</span>
          </button>

          <!-- จัดการสมาชิก (Added Menu Item) -->
          <button 
            @click="activeTab = 'members'"
            :class="[
              'w-full flex items-center rounded-xl text-xs font-bold transition-all duration-300 outline-none',
              isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
              activeTab === 'members' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white'
            ]"
            title="จัดการสมาชิก"
          >
            <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-user-group"></i>
            <span v-if="!isSidebarCollapsed" class="truncate">จัดการสมาชิก</span>
          </button>

          <button 
            @click="activeTab = 'settings'"
            :class="[
              'w-full flex items-center rounded-xl text-xs font-bold transition-all duration-300 outline-none',
              isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
              activeTab === 'settings' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white'
            ]"
            title="การตั้งค่าระบบ"
          >
            <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-sliders"></i>
            <span v-if="!isSidebarCollapsed">การตั้งค่าระบบ</span>
          </button>
        </nav>
      </div>

      <!-- Sidebar Footer -->
      <div class="space-y-5">
        <div class="bg-blue-950/40 p-4 rounded-[1.5rem] border border-blue-800/50" v-if="!isSidebarCollapsed">
          <div class="flex items-center space-x-2">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-ping"></span>
            <span class="text-[10px] font-black text-green-300 uppercase tracking-widest">Server Online</span>
          </div>
          <p class="text-[10px] text-blue-200/80 leading-relaxed font-medium pl-4">เชื่อมต่อฐานข้อมูลหลัก</p>
        </div>
        <button
          @click="logout"
          class="w-full flex items-center justify-center bg-red-500/10 hover:bg-red-500 text-red-300 hover:text-white transition-all py-3 rounded-xl text-[11px] font-black uppercase tracking-widest outline-none"
          :class="isSidebarCollapsed ? 'px-0' : 'space-x-2'"
        >
          <i class="text-xs fa-solid fa-right-from-bracket"></i>
          <span v-if="!isSidebarCollapsed">ออกจากระบบ</span>
        </button>
      </div>
    </aside>

    <!-- --- MAIN AREA --- -->
    <div class="flex flex-col flex-1 h-screen min-w-0 overflow-y-auto custom-scrollbar">
      
      <!-- --- STICKY HEADER BAR --- -->
      <header class="sticky top-0 z-20 flex items-center justify-between h-20 px-8 border-b bg-white/80 backdrop-blur-md border-slate-200 shrink-0">
        <!-- Mobile Left section (จัด "M" สีขาวให้อยู่กึ่งกลางของวงกลม) -->
        <div class="flex items-center space-x-4 lg:hidden">
          <button @click="isMobileMenuOpen = !isMobileMenuOpen" class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100">
            <i class="text-lg fa-solid fa-bars"></i>
          </button>
          <div class="flex items-center space-x-2">
            <div class="w-9 h-9 bg-[#1e3a8a] rounded-lg flex items-center justify-center text-white font-black text-base shadow-sm select-none">M</div>
            <span class="text-sm font-black tracking-tight text-slate-900">MSU IR Admin</span>
          </div>
        </div>

        <!-- Desktop Title context -->
        <div class="hidden lg:block">
          <h1 class="text-xl font-black tracking-tight text-slate-900">
            <span v-if="activeTab === 'dashboard'">แดชบอร์ดภาพรวมคลังเอกสารและวิทยานิพนธ์</span>
            <span v-else-if="activeTab === 'repository'">จัดการรายการเอกสารวิชาการดิจิทัล</span>
            <span v-else-if="activeTab === 'approvals'">รายการเอกสารคิวตรวจสอบและพิจารณา</span>
            <span v-else-if="activeTab === 'analytics'">ข้อมูลเชิงลึกและการวิเคราะห์พฤติกรรมการสืบค้น</span>
            <span v-else-if="activeTab === 'members'">การจัดการข้อมูลสมาชิกและสิทธิ์ระบบ</span>
            <span v-else-if="activeTab === 'settings'">ปรับแต่งตั้งค่าโครงสร้างและสิทธิ์หลังบ้าน</span>
          </h1>
          <p class="text-xs text-slate-400 font-bold uppercase tracking-wider mt-0.5">Maha Sarakham University</p>
        </div>

        <!-- Right Side Controls -->
        <div class="flex items-center space-x-4 sm:space-x-6">
          <button 
            @click="isUploadModalOpen = true"
            class="bg-[#1e3a8a] hover:bg-blue-800 text-white px-5 py-2.5 rounded-full text-xs font-black shadow-lg shadow-blue-900/10 hover:shadow-xl transition-all flex items-center group active:scale-95 outline-none"
          >
            <i class="fa-solid fa-plus mr-1.5 transition-transform group-hover:rotate-90 text-[11px]"></i>
            <span>เพิ่มผลงานใหม่</span>
          </button>

          <!-- Notification Bell -->
          <div class="relative cursor-pointer hover:bg-slate-100 p-2.5 rounded-xl transition-colors hidden sm:block">
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            <i class="text-lg fa-regular fa-bell text-slate-600"></i>
          </div>

          <!-- Profile Control -->
          <div class="relative">
            <button 
              @click="isProfileOpen = !isProfileOpen"
              class="flex items-center space-x-3 hover:bg-slate-50 p-1.5 rounded-xl transition-colors outline-none"
            >
              <div class="w-9 h-9 bg-yellow-100 text-yellow-700 rounded-full font-black text-sm flex items-center justify-center border-2 border-[#facc15] shrink-0 select-none">
                AD
              </div>
              <div class="hidden text-left md:block">
                <span class="block text-xs font-black leading-tight text-slate-900">Admin-MSU-IR</span>
                <span class="text-[10px] text-slate-400 font-bold block mt-0.5">ฝ่ายตรวจสอบคลัง</span>
              </div>
              <i class="text-xs fa-solid fa-chevron-down text-slate-400"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <transition name="dropdown-fade">
              <div v-if="isProfileOpen" class="absolute right-0 mt-3 w-56 bg-white rounded-[1.5rem] border border-slate-100 shadow-2xl p-3 z-50 animate-fade-in">
                <div class="p-3 mb-2 border-b border-slate-100">
                  <p class="text-xs font-bold tracking-wider uppercase text-slate-400">บัญชีผู้ใช้</p>
                  <p class="mt-1 text-sm font-black truncate text-slate-800">somsak.m@msu.ac.th</p>
                </div>
                <button class="w-full text-left p-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-blue-900 transition-all flex items-center">
                  <i class="flex items-center justify-center w-5 h-5 mr-2 text-sm fa-regular fa-circle-user"></i> ข้อมูลส่วนตัว
                </button>
                <button class="w-full text-left p-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 hover:text-blue-900 transition-all flex items-center">
                  <i class="flex items-center justify-center w-5 h-5 mr-2 text-sm fa-solid fa-sliders"></i> การตั้งค่าระบบ
                </button>
                <button @click="logout" class="w-full text-left p-2.5 mt-2 rounded-xl text-xs font-black text-red-500 hover:bg-red-50 transition-all flex items-center">
                  <i class="flex items-center justify-center w-5 h-5 mr-2 text-sm fa-solid fa-right-from-bracket"></i> ออกจากระบบ
                </button>
              </div>
            </transition>
          </div>
        </div>
      </header>

      <!-- --- SCREEN TRANSITION BODY --- -->
      <main class="flex-1 p-6 space-y-8 sm:p-8">

        <!-- ========================================================== -->
        <!-- TAB 1: DASHBOARD OVERVIEW -->
        <!-- ========================================================== -->
        <div v-if="activeTab === 'dashboard'" class="space-y-8 animate-fade-in">
          
          <!-- Stats Grid -->
          <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            
            <!-- Total Items Card -->
            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-[5rem] -mr-8 -mt-8 transition-transform duration-500 group-hover:scale-110"></div>
              <div class="relative z-10 flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">เอกสารในคลังทั้งหมด</p>
                  <h3 class="text-4xl font-black text-slate-900">{{ totalItems }}</h3>
                  <p class="flex items-center mt-2 text-xs font-bold text-blue-600">
                    <i class="mr-1 text-xs fa-solid fa-arrow-trend-up"></i> เพิ่มขึ้น 12% สัปดาห์นี้
                  </p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 text-blue-900 bg-blue-50 rounded-2xl">
                  <i class="text-xl fa-solid fa-folder-tree"></i>
                </div>
              </div>
            </div>

            <!-- Approved Card -->
            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-[5rem] -mr-8 -mt-8 transition-transform duration-500 group-hover:scale-110"></div>
              <div class="relative z-10 flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">อนุมัติเสร็จสิ้น</p>
                  <h3 class="text-4xl font-black text-green-600">{{ approvedItems }}</h3>
                  <!-- Smooth Yellow Progress Bar -->
                  <div class="w-32 h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
                    <div 
                      class="h-full bg-[#facc15] rounded-full transition-all duration-1000"
                      :style="{ width: `${totalItems ? (approvedItems / totalItems) * 100 : 0}%` }"
                    ></div>
                  </div>
                </div>
                <div class="flex items-center justify-center w-12 h-12 text-green-600 bg-green-50 rounded-2xl">
                  <i class="text-xl fa-regular fa-circle-check"></i>
                </div>
              </div>
            </div>

            <!-- Pending Verification Card -->
            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-50 rounded-bl-[5rem] -mr-8 -mt-8 transition-transform duration-500 group-hover:scale-110"></div>
              <div class="relative z-10 flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">รอตรวจสอบ</p>
                  <h3 class="text-4xl font-black text-yellow-600">{{ pendingItems }}</h3>
                  <p class="flex items-center mt-2 text-xs font-bold text-yellow-600">
                    <i class="mr-1 text-xs fa-regular fa-clock"></i> ควรเร่งรีบอนุมัติเอกสาร
                  </p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 text-yellow-600 bg-yellow-50 rounded-2xl">
                  <i class="text-xl fa-regular fa-clock"></i>
                </div>
              </div>
            </div>

            <!-- Action Required Card -->
            <div class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm hover:shadow-md transition-all relative overflow-hidden group">
              <div class="absolute top-0 right-0 w-24 h-24 bg-red-50 rounded-bl-[5rem] -mr-8 -mt-8 transition-transform duration-500 group-hover:scale-110"></div>
              <div class="relative z-10 flex items-center justify-between">
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">ตีกลับให้แก้ไข</p>
                  <h3 class="text-4xl font-black text-red-500">{{ actionRequiredItems }}</h3>
                  <p class="flex items-center mt-2 text-xs font-bold text-red-500">
                    <i class="mr-1 text-xs fa-solid fa-triangle-exclamation"></i> ข้อมูลขัดข้องรอแก้ไข
                  </p>
                </div>
                <div class="flex items-center justify-center w-12 h-12 text-red-500 bg-red-50 rounded-2xl">
                  <i class="text-xl fa-solid fa-triangle-exclamation"></i>
                </div>
              </div>
            </div>

          </div>

          <!-- Charts & Stats Details -->
          <div class="grid grid-cols-1 gap-8 lg:grid-cols-12">
            
            <!-- Graphic Chart Visualized (SVG Line representation) -->
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm lg:col-span-8 flex flex-col justify-between">
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h3 class="text-xl font-black tracking-tight text-slate-900">สถิติปริมาณความนิยมดาวน์โหลดประจำปี</h3>
                  <p class="mt-1 text-xs font-bold uppercase text-slate-400">Resource Download Metrics 2026</p>
                </div>
                <span class="bg-[#1e3a8a]/5 text-[#1e3a8a] px-4 py-1.5 rounded-full text-xs font-black">อัปเดตเรียลไทม์</span>
              </div>

              <!-- Interactive Chart Section -->
              <div class="relative flex items-end w-full h-64 p-6 overflow-hidden border bg-slate-50 rounded-3xl border-slate-100">
                <div class="absolute top-4 left-4 flex flex-col justify-between h-[80%] text-[10px] font-bold text-slate-400">
                  <span>400</span>
                  <span>200</span>
                  <span>100</span>
                  <span>0</span>
                </div>

                <svg class="absolute inset-0 w-full h-full p-6 pt-12 pb-8 pl-12 pr-12" viewBox="0 0 500 100" preserveAspectRatio="none">
                  <line x1="0" y1="0" x2="500" y2="0" stroke="#f1f5f9" stroke-width="1" />
                  <line x1="0" y1="33" x2="500" y2="33" stroke="#f1f5f9" stroke-width="1" />
                  <line x1="0" y1="66" x2="500" y2="66" stroke="#f1f5f9" stroke-width="1" />
                  <line x1="0" y1="100" x2="500" y2="100" stroke="#f1f5f9" stroke-width="1" />

                  <!-- Area Line Path with Golden Theme Highlight -->
                  <path 
                    d="M0,80 Q50,40 100,70 T200,30 T300,50 T400,20 T500,10" 
                    fill="none" 
                    stroke="#1e3a8a" 
                    stroke-width="3.5" 
                    stroke-linecap="round"
                  />
                  <path 
                    d="M0,80 Q50,40 100,70 T200,30 T300,50 T400,20 T500,10 L500,100 L0,100 Z" 
                    fill="url(#gradient-blue-vue)" 
                    opacity="0.12" 
                  />

                  <defs>
                    <linearGradient id="gradient-blue-vue" x1="0%" y1="0%" x2="0%" y2="100%">
                      <stop offset="0%" stop-color="#1e3a8a" />
                      <stop offset="100%" stop-color="#ffffff" />
                    </linearGradient>
                  </defs>
                </svg>

                <div class="w-full pl-12 pr-6 flex justify-between text-[11px] font-bold text-slate-400">
                  <span>ม.ค.</span>
                  <span>ก.พ.</span>
                  <span>มี.ค.</span>
                  <span>เม.ย.</span>
                  <span>พ.ค.</span>
                </div>
              </div>
            </div>

            <!-- List Categories summary -->
            <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm lg:col-span-4 flex flex-col justify-between">
              <div>
                <h3 class="mb-1 text-xl font-black tracking-tight text-slate-900">คลังสถิติดิจิทัลเด่น</h3>
                <p class="mb-6 text-xs font-bold uppercase text-slate-400">Popular categories</p>
              </div>

              <div class="space-y-6">
                <div class="flex items-start justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center text-sm font-bold text-blue-800 w-9 h-9 bg-blue-50 rounded-xl">#1</div>
                    <div>
                      <span class="block text-xs font-black text-slate-800">MSU e-Theses</span>
                      <span class="text-[10px] text-slate-400 font-bold">ยอดดาวน์โหลดเฉลี่ยสะสม</span>
                    </div>
                  </div>
                  <span class="text-xs font-black text-[#1e3a8a]">4,230 ครั้ง</span>
                </div>

                <div class="flex items-start justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center text-sm font-bold text-yellow-800 w-9 h-9 bg-yellow-50 rounded-xl">#2</div>
                    <div>
                      <span class="block text-xs font-black text-slate-800">งานวิจัยอาจารย์</span>
                      <span class="text-[10px] text-slate-400 font-bold">บทความและวารสารตีพิมพ์</span>
                    </div>
                  </div>
                  <span class="text-xs font-black text-[#1e3a8a]">2,845 ครั้ง</span>
                </div>

                <div class="flex items-start justify-between">
                  <div class="flex items-center space-x-3">
                    <div class="flex items-center justify-center text-sm font-bold text-purple-800 w-9 h-9 bg-purple-50 rounded-xl">#3</div>
                    <div>
                      <span class="block text-xs font-black text-slate-800">คลังข้อมูลลุ่มน้ำชี</span>
                      <span class="text-[10px] text-slate-400 font-bold">ภูมิปัญญาและวิจัยท้องถิ่น</span>
                    </div>
                  </div>
                  <span class="text-xs font-black text-[#1e3a8a]">1,910 ครั้ง</span>
                </div>
              </div>

              <button class="w-full bg-slate-50 hover:bg-slate-100 transition-colors py-3.5 rounded-2xl text-xs font-black text-slate-600 tracking-wider uppercase mt-8 flex items-center justify-center">
                <span>ดูรายละเอียดทั้งหมด</span>
                <i class="fa-solid fa-arrow-up-right-from-square ml-1.5 text-[10px]"></i>
              </button>
            </div>

          </div>

          <!-- Recent Submissions list -->
          <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-xl font-black tracking-tight text-slate-900">รายการนำส่งล่าสุด</h3>
                <p class="mt-1 text-xs font-bold uppercase text-slate-400">Latest Submissions</p>
              </div>
              <button @click="activeTab = 'repository'" class="text-xs font-black text-[#1e3a8a] hover:text-yellow-600 transition-colors">
                ดูทั้งหมด
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50 text-slate-500 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4">ประเภททรัพยากร</th>
                    <th class="px-6 py-4">หัวข้อและผู้ส่งงาน</th>
                    <th class="px-6 py-4">วันที่ส่งตรวจ</th>
                    <th class="px-6 py-4">สถานะตรวจสอบ</th>
                    <th class="px-6 py-4 text-center">จัดการ</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <tr v-for="item in submissions.slice(0, 3)" :key="item.id" class="transition-colors hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">{{ item.id }}</td>
                    <td class="px-6 py-4">
                      <span class="bg-blue-50 text-[#1e3a8a] px-3 py-1 rounded-full text-[11px] font-black">{{ item.type }}</span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="block text-sm font-bold leading-snug text-slate-800 line-clamp-1">{{ item.title }}</span>
                      <span class="text-[11px] text-slate-400 font-bold block mt-0.5">{{ item.author }}</span>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-slate-500">{{ item.date }}</td>
                    <td class="px-6 py-4">
                      <span :class="[
                        'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center',
                        item.status === 'approved' ? 'bg-green-100 text-green-700' : 
                        item.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'
                      ]">
                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                        <span v-if="item.status === 'approved'">อนุมัติแล้ว</span>
                        <span v-else-if="item.status === 'pending'">รอตรวจสอบ</span>
                        <span v-else>รอการแก้ไข</span>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-center space-x-2">
                        <template v-if="item.status === 'pending'">
                          <button @click="handleApprove(item.id)" class="flex items-center justify-center w-8 h-8 text-green-600 transition-all rounded-full bg-green-50 hover:bg-green-500 hover:text-white" title="อนุมัติ"><i class="text-xs fa-solid fa-check"></i></button>
                          <button @click="handleReject(item.id)" class="flex items-center justify-center w-8 h-8 text-red-500 transition-all rounded-full bg-red-50 hover:bg-red-500 hover:text-white" title="ปฏิเสธ"><i class="text-xs fa-solid fa-xmark"></i></button>
                        </template>
                        <button @click="handleDelete(item.id)" class="flex items-center justify-center w-8 h-8 transition-all rounded-full bg-slate-50 text-slate-400 hover:bg-red-500 hover:text-white" title="ลบรายการ"><i class="text-xs fa-regular fa-trash-can"></i></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

        </div>

        <!-- ========================================================== -->
        <!-- TAB 2: REPOSITORY CRUD MANAGEMENT -->
        <!-- ========================================================== -->
        <div v-else-if="activeTab === 'repository'" class="bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-6 animate-fade-in">
          
          <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <!-- Search bar input -->
            <div class="relative w-full max-w-md group">
              <i class="absolute transition-colors -translate-y-1/2 fa-solid fa-magnifying-glass left-4 top-1/2 text-slate-400 group-focus-within:text-blue-900"></i>
              <input 
                type="text" 
                placeholder="ค้นหา ID, ชื่อผลงาน หรือนักวิจัย..." 
                v-model="searchQuery"
                class="w-full py-3.5 pl-12 pr-4 bg-slate-50 border border-slate-200 focus:border-blue-900 focus:bg-white rounded-2xl outline-none text-sm transition-all focus:ring-4 focus:ring-blue-900/5 font-medium"
              />
            </div>

            <!-- Filters options -->
            <div class="flex items-center gap-3">
              <span class="text-xs font-black tracking-widest uppercase text-slate-400">หมวดหมู่:</span>
              <select 
                v-model="categoryFilter"
                class="bg-slate-50 border border-slate-200 px-4 py-3.5 rounded-2xl text-xs font-bold text-slate-600 focus:border-blue-900 focus:bg-white outline-none"
              >
                <option value="all">ทั้งหมด ทุกทรัพยากร</option>
                <option value="วิทยานิพนธ์">วิทยานิพนธ์ (e-Theses)</option>
                <option value="งานวิจัยอาจารย์">งานวิจัยอาจารย์ (Researches)</option>
                <option value="วารสารวิชาการ">วารสารวิชาการ (Articles)</option>
                <option value="ฐานข้อมูลท้องถิ่น">ข้อมูลภูมิปัญญาท้องถิ่น</option>
              </select>
            </div>
          </div>

          <!-- Main Table view representation -->
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50 text-slate-500 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                  <th class="px-6 py-5">รหัสคลัง</th>
                  <th class="px-6 py-5">ประเภททรัพยากร</th>
                  <th class="px-6 py-5 w-[45%]">ชื่อเอกสารและเจ้าของลิขสิทธิ์</th>
                  <th class="px-6 py-5">ยอดวิว / ยอดโหลด</th>
                  <th class="px-6 py-5">สถานะ</th>
                  <th class="px-6 py-5 text-center">ปฏิบัติการ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <template v-if="filteredSubmissions.length > 0">
                  <tr v-for="item in filteredSubmissions" :key="item.id" class="transition-colors hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">{{ item.id }}</td>
                    <td class="px-6 py-4">
                      <span class="bg-blue-50 text-[#1e3a8a] px-3 py-1 rounded-full text-[11px] font-black">{{ item.type }}</span>
                    </td>
                    <td class="px-6 py-4">
                      <span class="block text-sm font-bold leading-snug text-slate-800">{{ item.title }}</span>
                      <span class="text-[11px] text-slate-400 font-bold block mt-1">{{ item.author }}</span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="text-xs font-bold text-slate-600">
                        <span>วิว: <b class="font-black text-slate-900">{{ item.views }}</b></span>
                        <span class="mx-2 text-slate-200">|</span>
                        <span>โหลด: <b class="text-[#1e3a8a] font-black">{{ item.downloads }}</b></span>
                      </div>
                    </td>
                    <td class="px-6 py-4">
                      <span :class="[
                        'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center',
                        item.status === 'approved' ? 'bg-green-100 text-green-700' : 
                        item.status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'
                      ]">
                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                        <span v-if="item.status === 'approved'">เปิดแพร่สาธารณะ</span>
                        <span v-else-if="item.status === 'pending'">รอตรวจสอบ</span>
                        <span v-else>ต้องแก้ไขข้อมูล</span>
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-center space-x-2">
                        <button v-if="item.status !== 'approved'" @click="handleApprove(item.id)" class="flex items-center justify-center w-8 h-8 text-green-600 transition-all rounded-full bg-green-50 hover:bg-green-500 hover:text-white"><i class="text-xs fa-solid fa-check"></i></button>
                        <button @click="handleDelete(item.id)" class="flex items-center justify-center w-8 h-8 text-red-500 transition-all rounded-full bg-red-50 hover:bg-red-500 hover:text-white"><i class="text-xs fa-regular fa-trash-can"></i></button>
                      </div>
                    </td>
                  </tr>
                </template>
                <template v-else>
                  <tr>
                    <td colSpan="6" class="py-12 text-sm font-bold text-center text-slate-400">
                      <i class="mb-3 text-4xl fa-regular fa-folder-open text-slate-300"></i>
                      <p>ไม่พบค้นข้อมูลที่ระบุในคลังขณะนี้</p>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>

        </div>

        <!-- ========================================================== -->
        <!-- TAB 3: APPROVAL QUEUE -->
        <!-- ========================================================== -->
        <div v-else-if="activeTab === 'approvals'" class="space-y-6 animate-fade-in">
          
          <div class="bg-yellow-50 border border-yellow-200 p-6 rounded-[2rem] text-yellow-800 flex items-start space-x-4">
            <i class="fa-solid fa-circle-info shrink-0 mt-0.5 text-lg"></i>
            <div>
              <h4 class="text-sm font-black tracking-wider uppercase">โปรดตรวจสอบข้อมูลเพื่อรักษามาตรฐาน</h4>
              <p class="mt-1 text-xs font-semibold leading-relaxed">กรุณาตรวจสอบความถูกต้องของชื่อผู้แต่ง แหล่งอ้างอิง และเอกสารไฟล์ PDF หลัก ว่าเป็นไปตามเกณฑ์มาตรฐานข้อมูลคลังวิทยบริการระดับมหาวิทยาลัย ก่อนกดยอมรับการเผยแพร่</p>
            </div>
          </div>

          <div class="bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
            <h3 class="mb-6 text-lg font-black tracking-tight text-slate-900">คิวตรวจสอบเอกสารรอการอนุมัติ ({{ pendingItems }} รายการ)</h3>
            
            <div class="space-y-4">
              <template v-if="pendingItems > 0">
                <div 
                  v-for="item in submissions.filter(s => s.status === 'pending')" 
                  :key="item.id" 
                  class="flex flex-col justify-between gap-4 p-6 transition-all duration-300 border border-slate-100 rounded-2xl hover:border-yellow-300 hover:shadow-xl bg-slate-50/30 md:flex-row md:items-center animate-fade-in"
                >
                  <div class="space-y-2">
                    <div class="flex items-center space-x-3">
                      <span class="font-mono text-xs font-black text-slate-400">{{ item.id }}</span>
                      <span class="bg-blue-50 text-blue-900 text-[10px] font-black px-2.5 py-0.5 rounded-full">{{ item.type }}</span>
                    </div>
                    <h4 class="text-base font-bold leading-snug text-slate-800">{{ item.title }}</h4>
                    <div class="flex items-center text-xs font-bold text-slate-500">
                      <i class="fa-solid fa-users mr-1.5 text-xs text-slate-400"></i> เสนอผลงานโดย: <span class="ml-1 font-bold text-slate-800">{{ item.author }}</span>
                      <span class="mx-2 text-slate-200">|</span>
                      <i class="fa-regular fa-clock mr-1.5 text-xs text-slate-400"></i> ยื่นส่งตรวจ: {{ item.date }}
                    </div>
                  </div>

                  <div class="flex items-center space-x-3 shrink-0">
                    <button 
                      @click="handleApprove(item.id)"
                      class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-xl text-xs font-black shadow-lg shadow-green-600/10 active:scale-95 transition-all flex items-center outline-none"
                    >
                      <i class="fa-solid fa-check mr-1.5"></i> อนุญาตให้เผยแพร่
                    </button>
                    <button 
                      @click="handleReject(item.id)"
                      class="bg-white hover:bg-red-50 text-red-600 border border-red-200 px-5 py-2.5 rounded-xl text-xs font-black active:scale-95 transition-all flex items-center outline-none"
                    >
                      <i class="fa-solid fa-xmark mr-1.5"></i> ส่งคืนแก้ไข
                    </button>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="py-16 font-bold text-center text-slate-400">
                  <i class="mb-3 text-5xl text-green-500 fa-regular fa-circle-check animate-bounce"></i>
                  <p>ไม่มีเอกสารวิจัยวิชาการรอการพิจารณาในขณะนี้</p>
                </div>
              </template>
            </div>
          </div>

        </div>

        <!-- ========================================================== -->
        <!-- TAB 4: ANALYTICS REPORT -->
        <!-- ========================================================== -->
        <div v-else-if="activeTab === 'analytics'" class="grid grid-cols-1 gap-8 md:grid-cols-2 animate-fade-in">
          
          <!-- Top downloads list representation -->
          <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm">
            <h3 class="mb-1 text-xl font-black tracking-tight text-slate-900">ผลงานยอดความนิยมการดาวน์โหลดสูงสุด</h3>
            <p class="mb-6 text-xs font-bold uppercase text-slate-400">Most Downloaded Items</p>

            <div class="space-y-4">
              <div 
                v-for="(item, idx) in [...submissions].sort((a,b) => b.downloads - a.downloads)" 
                :key="item.id" 
                class="flex items-center justify-between p-4 border border-slate-50 rounded-2xl bg-slate-50/20"
              >
                <div class="flex items-center min-w-0 space-x-3">
                  <span class="w-8 h-8 rounded-xl bg-blue-50 text-[#1e3a8a] flex items-center justify-center font-black text-xs select-none">{{ idx + 1 }}</span>
                  <div class="min-w-0">
                    <span class="block text-xs font-black leading-snug truncate text-slate-800">{{ item.title }}</span>
                    <span class="text-[10px] text-slate-400 font-bold block mt-0.5">{{ item.author }}</span>
                  </div>
                </div>
                <span class="text-xs font-black text-[#1e3a8a] shrink-0 ml-4">{{ item.downloads }} ดาวน์โหลด</span>
              </div>
            </div>
          </div>

          <!-- Demographics simulated values -->
          <div class="bg-white p-8 rounded-[2.5rem] border border-slate-100 shadow-sm flex flex-col justify-between">
            <div>
              <h3 class="mb-1 text-xl font-black tracking-tight text-slate-900">สัดส่วนผู้สืบค้นระบบจำแนก</h3>
              <p class="mb-8 text-xs font-bold uppercase text-slate-400">Access Demographics</p>
            </div>

            <div class="space-y-6">
              <div>
                <div class="flex justify-between mb-2 text-xs font-bold text-slate-600">
                  <span>นิสิตและนักศึกษา (MSU Students)</span>
                  <span>65%</span>
                </div>
                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                  <div class="w-[65%] h-full bg-[#1e3a8a] rounded-full"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between mb-2 text-xs font-bold text-slate-600">
                  <span>บุคลากร คณาจารย์ มหาวิทยาลัย</span>
                  <span>25%</span>
                </div>
                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                  <div class="w-[25%] h-full bg-[#facc15] rounded-full"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between mb-2 text-xs font-bold text-slate-600">
                  <span>บุคคลภายนอกทั่วไป</span>
                  <span>10%</span>
                </div>
                <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                  <div class="w-[10%] h-full bg-slate-300 rounded-full"></div>
                </div>
              </div>
            </div>

            <div class="pt-6 mt-8 border-t border-slate-100">
              <p class="text-xs font-semibold leading-relaxed text-center text-slate-400">ระบบประมวลผลหลังบ้านอัปเดตสม่ำเสมอในทุกช่วงเวลาอย่างสมบูรณ์แบบ</p>
            </div>
          </div>

        </div>

        <!-- ========================================================== -->
        <!-- TAB 5: MEMBERS MANAGEMENT -->
        <!-- ========================================================== -->
        <div v-else-if="activeTab === 'members'" class="bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-100 shadow-sm space-y-6 animate-fade-in">
          <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
            <!-- Search bar input -->
            <div class="relative w-full max-w-md group">
              <i class="absolute transition-colors -translate-y-1/2 fa-solid fa-magnifying-glass left-4 top-1/2 text-slate-400 group-focus-within:text-blue-900"></i>
              <input 
                type="text" 
                placeholder="ค้นหาสมาชิกด้วยชื่อ, อีเมล หรือคณะ..." 
                v-model="searchQuery"
                class="w-full py-3.5 pl-12 pr-4 bg-slate-50 border border-slate-200 focus:border-blue-900 focus:bg-white rounded-2xl outline-none text-sm transition-all focus:ring-4 focus:ring-blue-900/5 font-medium"
              />
            </div>
          </div>

          <!-- Members Table view representation -->
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50 text-slate-500 text-[10px] uppercase font-black tracking-widest border-b border-slate-100">
                  <th class="px-6 py-5">รหัสสมาชิก</th>
                  <th class="px-6 py-5">ชื่อ-นามสกุล</th>
                  <th class="px-6 py-5">อีเมลติดต่อ</th>
                  <th class="px-6 py-5">บทบาท</th>
                  <th class="px-6 py-5">หน่วยงาน/คณะ</th>
                  <th class="px-6 py-5">สถานะบัญชี</th>
                  <th class="px-6 py-5 text-center">จัดการ</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <template v-if="filteredMembers.length > 0">
                  <tr v-for="member in filteredMembers" :key="member.id" class="transition-colors hover:bg-slate-50/50">
                    <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">{{ member.id }}</td>
                    <td class="px-6 py-4 text-sm font-bold leading-snug text-slate-800">{{ member.name }}</td>
                    <td class="px-6 py-4 text-xs font-medium text-slate-600">{{ member.email }}</td>
                    <td class="px-6 py-4">
                      <span :class="[
                        'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider',
                        member.role === 'admin' ? 'bg-red-50 text-red-700' : 
                        member.role === 'lecturer' ? 'bg-blue-50 text-blue-700' : 
                        member.role === 'staff' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-700'
                      ]">
                        {{ member.role }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-xs font-bold text-slate-500">{{ member.department }}</td>
                    <td class="px-6 py-4">
                      <span :class="[
                        'px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest inline-flex items-center',
                        member.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                      ]">
                        <span class="w-1.5 h-1.5 rounded-full mr-1.5 bg-current"></span>
                        {{ member.status === 'active' ? 'ใช้งานปกติ' : 'ถูกระงับสิทธิ์' }}
                      </span>
                    </td>
                    <td class="px-6 py-4">
                      <div class="flex items-center justify-center space-x-2">
                        <button 
                          @click="handleToggleUserStatus(member.id)" 
                          :class="[
                            'w-8 h-8 rounded-full flex items-center justify-center transition-all',
                            member.status === 'active' ? 'bg-red-50 text-red-500 hover:bg-red-500 hover:text-white' : 'bg-green-50 text-green-600 hover:bg-green-600 hover:text-white'
                          ]" 
                          :title="member.status === 'active' ? 'ระงับการใช้งาน' : 'เปิดใช้งานปกติ'"
                        >
                          <i v-if="member.status === 'active'" class="text-xs fa-solid fa-user-slash"></i>
                          <i v-else class="text-xs fa-solid fa-user-check"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                </template>
                <template v-else>
                  <tr>
                    <td colSpan="7" class="py-12 text-sm font-bold text-center text-slate-400">
                      <i class="mb-3 text-4xl fa-solid fa-users text-slate-300"></i>
                      <p>ไม่พบค้นหาข้อมูลสมาชิกที่ระบุ</p>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- TAB 6: SETTINGS CONFIGURATION -->
        <!-- ========================================================== -->
        <div v-else-if="activeTab === 'settings'" class="bg-white p-6 sm:p-8 rounded-[2.5rem] border border-slate-100 shadow-sm max-w-3xl space-y-8 animate-fade-in">
          <div>
            <h3 class="mb-1 text-xl font-black tracking-tight text-slate-900">ตั้งค่าการทำงานแผงควบคุมหลัก</h3>
            <p class="text-xs font-bold uppercase text-slate-400">System Configuration</p>
          </div>

          <div class="pb-8 space-y-6 border-b border-slate-100">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-bold text-slate-800">ระบบตรวจสอบอัตโนมัติ (AI Validation)</h4>
                <p class="mt-1 text-xs text-slate-400">ช่วยคัดกรองเนื้อหาวิทยานิพนธ์ละเมิดลิขสิทธิ์เบื้องต้น</p>
              </div>
              <div class="flex items-center justify-end w-12 h-6 p-1 bg-green-500 rounded-full cursor-pointer">
                <div class="w-4 h-4 bg-white rounded-full shadow"></div>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-bold text-slate-800">อนุญาตดาวน์โหลดโดยไม่ลงชื่อใช้งาน</h4>
                <p class="mt-1 text-xs text-slate-400">บุคคลภายนอกสามารถดึงรายงาน PDF นามธรรมไปใช้ประโยชน์ต่อได้</p>
              </div>
              <div class="flex items-center justify-start w-12 h-6 p-1 rounded-full cursor-pointer bg-slate-200">
                <div class="w-4 h-4 bg-white rounded-full shadow"></div>
              </div>
            </div>
          </div>

          <div class="flex justify-end space-x-3">
            <button class="px-6 py-3 text-xs font-bold transition-colors rounded-2xl text-slate-500 hover:bg-slate-50">ล้างค่าเริ่มต้น</button>
            <button 
              @click="triggerToast('บันทึกการตั้งค่าระบบหลังบ้านสำเร็จ', 'success')"
              class="bg-[#1e3a8a] text-white px-8 py-3 rounded-2xl text-xs font-black shadow-lg shadow-blue-900/10 active:scale-95 transition-all outline-none"
            >
              บันทึกตั้งค่า
            </button>
          </div>
        </div>

      </main>
    </div>

    <!-- --- MOBILE SLIDE-OUT MENU OVERLAY --- -->
    <transition name="fade">
      <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 lg:hidden">
        <!-- Backdrop -->
        <div @click="isMobileMenuOpen = false" class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm"></div>
        
        <!-- Sidebar context on Mobile -->
        <div class="absolute inset-y-0 left-0 w-72 bg-[#1e3a8a] text-white p-6 flex flex-col justify-between animate-slide-right">
          <div class="space-y-8">
            <div class="flex items-center space-x-3">
              <!-- ปรับสัญลักษณ์ M บนมือถือให้อยู่ตรงกลางเช่นกัน -->
              <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-[#1e3a8a] font-black text-lg select-none">M</div>
              <div>
                <span class="block text-sm font-black leading-tight text-white uppercase">MSU IR Portal</span>
                <span class="text-[9px] text-yellow-400 tracking-wider uppercase font-black">Management</span>
              </div>
            </div>

            <!-- Navigation Links in mobile context (Clean & Standard Menu) -->
            <nav class="space-y-1">
              <!-- หน้าแรกภาพรวม -->
              <button 
                @click="activeTab = 'dashboard'; isMobileMenuOpen = false"
                :class="[
                  'w-full flex items-center space-x-4 px-4 py-3 rounded-xl text-xs font-bold transition-all outline-none',
                  activeTab === 'dashboard' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10'
                ]"
              >
                <i class="text-sm fa-solid fa-chart-line"></i>
                <span>ภาพรวมแผงควบคุม</span>
              </button>

              <!-- จัดการคลังข้อมูลหลัก -->
              <button 
                @click="activeTab = 'repository'; isMobileMenuOpen = false"
                :class="[
                  'w-full flex items-center space-x-4 px-4 py-3 rounded-xl text-xs font-bold transition-all outline-none',
                  activeTab === 'repository' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10'
                ]"
              >
                <i class="text-sm fa-solid fa-folder-open"></i>
                <span>จัดการคลังข้อมูล</span>
              </button>

              <!-- คิวตรวจสอบงานวิชาการ -->
              <button 
                @click="activeTab = 'approvals'; isMobileMenuOpen = false"
                :class="[
                  'w-full flex items-center space-x-4 px-4 py-3 rounded-xl text-xs font-bold transition-all outline-none',
                  activeTab === 'approvals' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10'
                ]"
              >
                <i class="text-sm fa-solid fa-clipboard-check"></i>
                <span>คิวตรวจสอบข้อมูล</span>
              </button>

              <!-- รายงานและการสืบค้นสถิติ -->
              <button 
                @click="activeTab = 'analytics'; isMobileMenuOpen = false"
                :class="[
                  'w-full flex items-center space-x-4 px-4 py-3 rounded-xl text-xs font-bold transition-all outline-none',
                  activeTab === 'analytics' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10'
                ]"
              >
                <i class="text-sm fa-solid fa-chart-bar"></i>
                <span>สถิติและการดาวน์โหลด</span>
              </button>

              <!-- จัดการสมาชิก -->
              <button 
                @click="activeTab = 'members'; isMobileMenuOpen = false"
                :class="[
                  'w-full flex items-center space-x-4 px-4 py-3 rounded-xl text-xs font-bold transition-all outline-none',
                  activeTab === 'members' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10'
                ]"
              >
                <i class="text-sm fa-solid fa-user-group"></i>
                <span>จัดการสมาชิก</span>
              </button>

              <!-- ตั้งค่าระบบส่วนกลาง -->
              <button 
                @click="activeTab = 'settings'; isMobileMenuOpen = false"
                :class="[
                  'w-full flex items-center space-x-4 px-4 py-3 rounded-xl text-xs font-bold transition-all outline-none',
                  activeTab === 'settings' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10'
                ]"
              >
                <i class="text-sm fa-solid fa-sliders"></i>
                <span>การตั้งค่าระบบ</span>
              </button>
            </nav>
          </div>

          <button @click="logout" class="flex items-center justify-center w-full py-3 space-x-2 text-xs font-black tracking-wider text-red-300 uppercase bg-red-500/20 rounded-xl">
            <i class="text-xs fa-solid fa-right-from-bracket"></i>
            <span>ออกจากระบบ</span>
          </button>
        </div>
      </div>
    </transition>

    <!-- --- SMOOTH UPLOAD MODAL (FADE & SCALE) --- -->
    <transition name="modal-fade">
      <div v-if="isUploadModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Blur Backdrop -->
        <div @click="isUploadModalOpen = false" class="absolute inset-0 bg-slate-950/40 backdrop-blur-md"></div>

        <!-- Content card -->
        <div class="relative w-full max-w-lg bg-white rounded-[2.5rem] shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)] border border-slate-100 overflow-hidden">
          <div class="bg-gradient-to-br from-[#1e3a8a] via-[#1e40af] to-blue-700 p-8 text-white relative">
            <div class="absolute w-32 h-32 rounded-full -right-8 -top-8 bg-white/5 blur-2xl"></div>
            <h3 class="text-2xl font-black tracking-tight">เพิ่มรายการใหม่ในคลังข้อมูล</h3>
            <p class="mt-1 text-xs text-blue-100/80">กรุณากรอกข้อมูลเพื่อความถูกต้องด้านลิขสิทธิ์และการจัดสืบค้น</p>
            
            <button @click="isUploadModalOpen = false" class="absolute flex items-center justify-center w-10 h-10 text-white transition-all rounded-full outline-none right-6 top-6 bg-white/10 hover:bg-white/20 hover:rotate-90">
              <i class="fa-solid fa-xmark"></i>
            </button>
          </div>

          <!-- Form inputs container -->
          <form @submit.prevent="handleCreateDocument" class="p-8 space-y-6 bg-white">
            <div>
              <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">ประเภททรัพยากร</label>
              <select 
                v-model="newDoc.type"
                class="w-full bg-slate-50 border border-slate-200 px-4 py-3.5 rounded-2xl text-sm font-bold text-slate-700 focus:border-blue-900 focus:bg-white outline-none animate-fade-in"
              >
                <option value="วิทยานิพนธ์">วิทยานิพนธ์ (e-Theses)</option>
                <option value="งานวิจัยอาจารย์">งานวิจัยอาจารย์ (Researches)</option>
                <option value="วารสารวิชาการ">วารสารวิชาการ (Articles)</option>
                <option value="ฐานข้อมูลท้องถิ่น">ฐานข้อมูลภูมิปัญญาท้องถิ่น</option>
              </select>
            </div>

            <div>
              <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">ชื่อผลงาน / หัวข้อทางวิชาการ</label>
              <input 
                type="text" 
                placeholder="กรอกชื่อวิทยานิพนธ์ หรือผลงานวิจัยฉบับสมบูรณ์..."
                v-model="newDoc.title"
                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none bg-slate-50 border-slate-200 focus:border-blue-900 focus:bg-white rounded-2xl focus:ring-4 focus:ring-blue-900/5 placeholder:text-slate-300"
              />
            </div>

            <div>
              <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">ผู้จัดทำ / เจ้าของลิขสิทธิ์</label>
              <input 
                type="text" 
                placeholder="เช่น รศ.ดร. สองเมือง ดีเลิศ หรือ นายขยัน เรียนดี"
                v-model="newDoc.author"
                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none bg-slate-50 border-slate-200 focus:border-blue-900 focus:bg-white rounded-2xl focus:ring-4 focus:ring-blue-900/5 placeholder:text-slate-300"
              />
            </div>

            <!-- Simulation section -->
            <div class="p-6 text-center transition-all border-2 border-dashed cursor-pointer border-slate-200 rounded-2xl bg-slate-50 hover:bg-slate-100/50">
              <i class="mb-2 text-3xl fa-regular fa-file-pdf text-slate-400"></i>
              <span class="block text-xs font-black text-slate-600">อัปโหลดไฟล์ PDF ฉบับเต็ม</span>
              <span class="text-[10px] text-slate-400 font-bold block mt-1">ขีดจำกัดขนาดสูงสุด 50MB ต่อหนึ่งไฟล์</span>
            </div>

            <!-- Footer actions -->
            <div class="flex pt-4 space-x-3 border-t border-slate-100">
              <button type="button" @click="isUploadModalOpen = false" class="flex-1 py-4 text-xs font-bold transition-colors text-slate-500 hover:bg-slate-50 rounded-2xl">
                ยกเลิก
              </button>
              <button type="submit" class="flex-1 bg-[#1e3a8a] hover:bg-blue-800 text-white py-4 rounded-2xl text-xs font-black shadow-lg shadow-blue-900/20 active:scale-95 transition-all">
                บันทึกนำส่งข้อมูล
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- --- TOAST NOTIFICATIONS --- -->
    <transition name="toast-slide">
      <div v-if="toast.show" class="fixed bottom-8 right-8 z-[120] bg-slate-900 text-white rounded-[1.5rem] p-5 shadow-2xl border border-slate-800 flex items-center space-x-4 max-w-sm">
        <div :class="[
          'w-9 h-9 rounded-xl flex items-center justify-center shrink-0',
          toast.type === 'success' ? 'bg-green-500 text-white' : 
          toast.type === 'warning' ? 'bg-yellow-500 text-black' : 'bg-red-500 text-white'
        ]">
          <i class="text-sm font-bold fa-solid fa-check"></i>
        </div>
        <div>
          <p class="text-xs font-bold tracking-wider uppercase text-slate-300">ระบบหลังบ้านแจ้งเตือน</p>
          <p class="text-xs font-black text-white mt-0.5 leading-relaxed">{{ toast.message }}</p>
        </div>
      </div>
    </transition>

  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Anuphan:wght@300;400;500;700&display=swap');

.font-sarabun {
  font-family: 'Anuphan', sans-serif;
}

/* Custom scrollbar layout styling */
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

/* --- ANIMATIONS & TRANSITIONS --- */

/* Tab Fade transition */
.animate-fade-in {
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Modal animation (Scale & Fade) */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: scale(0.96) translateY(12px);
}

/* Toast Notification Animation */
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-slide-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}
.toast-slide-leave-to {
  opacity: 0;
  transform: translateY(10px) scale(0.98);
}

/* Dropdown Animation */
.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: all 0.2s ease-out;
}
.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: translateY(8px);
}

/* Normal fade */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Slide Right Animation for Mobile Sidebar */
.animate-slide-right {
  animation: slideRight 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes slideRight {
  from {
    transform: translateX(-100%);
  }
  to {
    transform: translateX(0);
  }
}
</style>