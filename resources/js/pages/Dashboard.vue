<script setup lang="ts">
import ModernSelect from '@/components/ModernSelect.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { computed, ref, watch } from 'vue';

// --- Interfaces & Types ---
type ItemStatus = 'approved' | 'pending' | 'action_required';

interface Member {
    id: number;
    name: string;
    email: string;
    role_level: number; // 1 = สมาชิกทั่วไป, 3 = ผู้ดูแลระบบ
    is_msu_member: boolean;
    department_id: number | null;
    department_name: string | null;
    status: 'active' | 'suspended';
}

interface Department {
    id: number;
    name: string;
}

interface NewMember {
    is_msu_member: boolean;
    role_level: 1 | 3;
    department_id: number | null;
    name: string;
    email_local: string; // ใช้เมื่อเป็นสมาชิก มมส. (ต่อท้าย @msu.ac.th ตอน submit)
    email: string; // ใช้เมื่อไม่ใช่สมาชิก มมส. (กรอกเต็ม)
    password: string;
}

interface EditMember {
    id: number;
    is_msu_member: boolean;
    role_level: 1 | 3;
    department_id: number | null;
    name: string;
    email_local: string;
    email: string;
    password: string; // เว้นว่างไว้ = ไม่เปลี่ยนรหัสผ่าน (เฉพาะบุคคลภายนอก)
}

interface Toast {
    show: boolean;
    message: string;
    type: 'success' | 'warning' | 'danger';
}

interface RepoItem {
    id: number;
    collection: string | null;
    title: string;
    author: string | null;
    year: number | null;
    status: ItemStatus;
    canEdit: boolean;
}

interface DashboardStats {
    total: number;
    approved: number;
    pending: number;
    actionRequired: number;
    byCollection: { name: string; count: number }[];
    byLanguage: { tha: number; eng: number };
    byYear: { year: number; count: number }[];
    topFaculties: { name: string; count: number }[];
    recent: { id: number; title: string; collection: string | null; author: string | null; status: ItemStatus }[];
}

interface QueueItem {
    id: number;
    title: string;
    collection: string | null;
    author: string | null;
    year: number | null;
    status: ItemStatus;
    reviewNote: string | null;
    owner: string | null;
    submittedAt: string | null;
}

interface RepositoryPayload {
    items: RepoItem[];
    pagination: { total: number; currentPage: number; lastPage: number; perPage: number };
    filters: { q: string; collection: number | null; status: string | null };
    collections: { id: number; name: string }[];
}

interface ImportIssue {
    level: 'error' | 'warning';
    message: string;
}
interface ImportPreviewRow {
    line: number;
    title: string;
    collection_id: number;
    creator: string | null;
    department_name: string | null;
    year: number | null;
    status: 'ready' | 'duplicate' | 'error';
    issues: ImportIssue[];
}
interface ImportReport {
    ok: boolean;
    error?: string;
    uuid?: string;
    filename?: string;
    missingColumns?: string[];
    unexpectedColumns?: string[];
    summary?: { total: number; ready: number; duplicate: number; error: number };
    preview?: ImportPreviewRow[];
    done?: boolean;
    result?: { created: number; skippedError: number; skippedDuplicate: number };
}

// --- Props (real data from the backend) ---
const props = defineProps<{
    members: Member[];
    departments: Department[];
    repository: RepositoryPayload;
    stats: DashboardStats;
    queue: { items: QueueItem[] };
}>();

// --- Breadcrumbs Setup ---
const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

// --- Current logged-in admin ---
const page = usePage();
const currentUserId = computed(() => (page.props.auth as any)?.user?.id);
const roleLevel = computed(() => Number((page.props.auth as any)?.user?.role_level ?? 0));
const isAdmin = computed(() => roleLevel.value >= 3); // role 3 = ผู้ดูแลระบบ; role 2 = staff

// --- States ---
// activeTab is persisted per browser session so a server round-trip (e.g. the CSV
// import upload doing router.post → back()) does not knock the admin back to the
// dashboard tab when the component re-initialises.
const TAB_STORE_KEY = 'msuir.dashboard.activeTab';
const validTabs = ['dashboard', 'repository', 'contribute', 'import', 'approvals', 'analytics', 'members'];
const readStoredTab = (): string => {
    try {
        const t = sessionStorage.getItem(TAB_STORE_KEY);
        return t && validTabs.includes(t) ? t : 'dashboard';
    } catch {
        return 'dashboard';
    }
};
const activeTab = ref<string>(readStoredTab());

// Staff (role 2) must never land on an admin-only tab, even by tampering.
const adminOnlyTabs = ['members', 'import'];
watch(
    activeTab,
    (tab) => {
        if (!isAdmin.value && adminOnlyTabs.includes(tab)) {
            activeTab.value = 'dashboard';
            return;
        }
        try {
            sessionStorage.setItem(TAB_STORE_KEY, tab);
        } catch {
            /* sessionStorage unavailable — non-fatal */
        }
    },
    { immediate: true },
);

const isAddMemberModalOpen = ref<boolean>(false);
const isEditMemberModalOpen = ref<boolean>(false);
const isMobileMenuOpen = ref<boolean>(false);
const isProfileOpen = ref<boolean>(false);
const searchQuery = ref<string>('');
const isSidebarCollapsed = ref<boolean>(false); // สำหรับการพับ-กาง Sidebar บน PC

// --- Form State (For adding a new member) ---
const newMember = ref<NewMember>({
    is_msu_member: true,
    role_level: 1,
    department_id: null,
    name: '',
    email_local: '',
    email: '',
    password: '',
});

// --- Form State (For editing an existing member) ---
const editMember = ref<EditMember>({
    id: 0,
    is_msu_member: true,
    role_level: 1,
    department_id: null,
    name: '',
    email_local: '',
    email: '',
    password: '',
});

// --- Toast State ---
const toast = ref<Toast>({
    show: false,
    message: '',
    type: 'success',
});

// --- Status badge label/colour, shared by every repository/queue view ---
const statusMeta: Record<ItemStatus, { label: string; cls: string }> = {
    approved: { label: 'เผยแพร่แล้ว', cls: 'bg-green-100 text-green-700' },
    pending: { label: 'รอตรวจสอบ', cls: 'bg-yellow-100 text-yellow-700' },
    action_required: { label: 'ต้องแก้ไข', cls: 'bg-red-100 text-red-700' },
};

const filteredMembers = computed(() => {
    return props.members.filter((member) => {
        const q = searchQuery.value.toLowerCase();
        return (
            member.name.toLowerCase().includes(q) ||
            member.email.toLowerCase().includes(q) ||
            (member.department_name ?? '').toLowerCase().includes(q)
        );
    });
});

// Sidebar "คิวตรวจสอบข้อมูล" badge — admin: everything queued; staff: their items sent back.
const queueBadgeCount = computed(() =>
    isAdmin.value ? props.queue.items.length : props.queue.items.filter((i) => i.status === 'action_required').length,
);

const maxCollectionCount = computed(() => Math.max(1, ...props.stats.byCollection.map((r) => r.count)));
const maxYearCount = computed(() => Math.max(1, ...props.stats.byYear.map((r) => r.count)));
const maxFacultyCount = computed(() => Math.max(1, ...props.stats.topFaculties.map((r) => r.count)));

// --- Flow C: repository table (real data, role-aware, server-side filter/paginate) ---
const repoFilters = ref({
    q: props.repository.filters.q,
    collection: props.repository.filters.collection as number | null,
    status: props.repository.filters.status as string | null,
});

function applyRepoFilters(extra: Record<string, unknown> = {}) {
    router.get(
        route('dashboard'),
        {
            repo_q: repoFilters.value.q || undefined,
            repo_collection: repoFilters.value.collection || undefined,
            repo_status: repoFilters.value.status || undefined,
            ...extra,
        },
        { only: ['repository'], preserveState: true, preserveScroll: true, replace: true },
    );
}

let repoSearchTimer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => repoFilters.value.q,
    () => {
        clearTimeout(repoSearchTimer);
        repoSearchTimer = setTimeout(() => applyRepoFilters(), 350);
    },
);
watch([() => repoFilters.value.collection, () => repoFilters.value.status], () => applyRepoFilters());

const repoGoToPage = (page: number) => {
    const { currentPage, lastPage } = props.repository.pagination;
    if (page < 1 || page > lastPage || page === currentPage) return;
    applyRepoFilters({ page });
};

const handleDeleteItem = (item: RepoItem) => {
    Swal.fire({
        title: 'ลบรายการนี้?',
        html: `<p class="text-sm text-slate-500">"${item.title}"<br/>รายการจะถูกนำออกจากคลังข้อมูล</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ลบรายการ',
        cancelButtonText: 'ยกเลิก',
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.delete(route('admin.repository.items.destroy', item.id), {
            preserveScroll: true,
            only: ['repository'],
            onSuccess: () => triggerToast('ลบรายการออกจากคลังข้อมูลแล้ว', 'danger'),
            onError: () => triggerToast('ลบรายการไม่สำเร็จ', 'danger'),
        });
    });
};

// --- Flow A: CSV bulk import wizard (admin only) ---
const importStep = ref<1 | 2 | 3>(1); // 1 upload · 2 review · 3 done
const importBusy = ref(false);
const importFile = ref<File | null>(null);
const importReport = ref<ImportReport | null>(null);
const importResult = ref<ImportReport['result'] | null>(null);
const importFileInput = ref<HTMLInputElement | null>(null);

const importStatusMeta: Record<ImportPreviewRow['status'], { label: string; cls: string }> = {
    ready: { label: 'พร้อมนำเข้า', cls: 'bg-green-100 text-green-700' },
    duplicate: { label: 'ซ้ำ — ข้าม', cls: 'bg-yellow-100 text-yellow-700' },
    error: { label: 'ผิดพลาด — ข้าม', cls: 'bg-red-100 text-red-700' },
};

const resetImport = () => {
    importStep.value = 1;
    importBusy.value = false;
    importFile.value = null;
    importReport.value = null;
    importResult.value = null;
    if (importFileInput.value) importFileInput.value.value = '';
};

const onImportFilePicked = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    if (!file) return;
    importFile.value = file;
    importBusy.value = true;
    router.post(
        route('admin.repository.import.validate'),
        { file },
        {
            forceFormData: true,
            only: ['flash'],
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                importBusy.value = false;
                triggerToast(errors.file ?? 'อัปโหลดไฟล์ไม่สำเร็จ', 'danger');
            },
        },
    );
};

const submitImportCommit = () => {
    if (!importReport.value?.uuid) return;
    importBusy.value = true;
    router.post(
        route('admin.repository.import.commit'),
        { uuid: importReport.value.uuid },
        {
            only: ['flash', 'repository'],
            preserveState: true,
            preserveScroll: true,
            onError: () => {
                importBusy.value = false;
                triggerToast('นำเข้าข้อมูลไม่สำเร็จ', 'danger');
            },
        },
    );
};

// React to the `import` flash payload coming back from validate / commit.
// `immediate` so it also fires if the component re-mounts on the redirect-follow
// with the flash already present in props.
watch(
    () => (page.props.flash as { import?: ImportReport } | undefined)?.import,
    (report) => {
        if (!report) return;
        importBusy.value = false;
        if (report.done) {
            importResult.value = report.result ?? null;
            importStep.value = 3;
            return;
        }
        if (!report.ok) {
            triggerToast(report.error ?? 'ไฟล์ไม่ถูกต้อง', 'danger');
            resetImport();
            return;
        }
        importReport.value = report;
        importStep.value = 2;
    },
    { immediate: true },
);

// --- Flow B: "เพิ่มรายการ" wizard (staff + admin) ---
const wizTotalSteps = 5;
const wizStep = ref(1);
const wizBusy = ref(false);
const wizDone = ref(false);

const blankWizForm = () => ({
    collection_id: null as number | null,
    title: '',
    language: 'tha' as 'tha' | 'eng',
    alt_titles: [] as string[],
    creator: '',
    contributors: [] as string[],
    year_issued: null as number | null,
    department_id: null as number | null,
    rights: '',
    degree: '',
    description: '',
    subjects: [] as string[],
    fulltext_mode: 'url' as 'url' | 'file',
    fulltext_url: '',
    fulltext_file: null as File | null,
});
const wizForm = ref(blankWizForm());
const wizFileInput = ref<HTMLInputElement | null>(null);

const wizCollectionName = computed(() => props.repository.collections.find((c) => c.id === wizForm.value.collection_id)?.name ?? '—');
const wizDepartmentName = computed(() => props.departments.find((d) => d.id === wizForm.value.department_id)?.name ?? 'ไม่ระบุ');

const wizStepValid = (step: number): boolean => {
    const f = wizForm.value;
    if (step === 1) return !!f.collection_id && f.title.trim() !== '';
    if (step === 5) return f.fulltext_mode === 'url' ? f.fulltext_url.trim() !== '' : !!f.fulltext_file;
    return true;
};

const wizNext = () => {
    if (!wizStepValid(wizStep.value)) {
        triggerToast('กรุณากรอกข้อมูลที่จำเป็นให้ครบก่อน', 'warning');
        return;
    }
    if (wizStep.value < wizTotalSteps) wizStep.value++;
};
const wizBack = () => {
    if (wizStep.value > 1) wizStep.value--;
};

const wizAddRow = (key: 'alt_titles' | 'contributors' | 'subjects') => wizForm.value[key].push('');
const wizRemoveRow = (key: 'alt_titles' | 'contributors' | 'subjects', i: number) => wizForm.value[key].splice(i, 1);

const onWizFilePicked = (e: Event) => {
    wizForm.value.fulltext_file = (e.target as HTMLInputElement).files?.[0] ?? null;
};

const resetWizard = () => {
    wizForm.value = blankWizForm();
    wizStep.value = 1;
    wizBusy.value = false;
    wizDone.value = false;
    if (wizFileInput.value) wizFileInput.value.value = '';
};

const wizSubmit = () => {
    if (!wizStepValid(1) || !wizStepValid(5)) {
        triggerToast('ข้อมูลยังไม่ครบ', 'warning');
        return;
    }
    const f = wizForm.value;
    wizBusy.value = true;
    router.post(
        route('admin.repository.items.store'),
        {
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
            fulltext_url: f.fulltext_mode === 'url' ? f.fulltext_url || null : null,
            fulltext_file: f.fulltext_mode === 'file' ? f.fulltext_file : null,
        },
        {
            forceFormData: true,
            only: ['flash', 'repository'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                wizBusy.value = false;
                wizDone.value = true;
                triggerToast(isAdmin.value ? 'เพิ่มรายการเข้าคลังแล้ว' : 'ส่งรายการเข้าคิวตรวจสอบแล้ว', 'success');
            },
            onError: (errors) => {
                wizBusy.value = false;
                triggerToast(Object.values(errors)[0] ?? 'บันทึกไม่สำเร็จ', 'danger');
            },
        },
    );
};

// --- Trigger Toast Notification ---
const triggerToast = (message: string, type: 'success' | 'warning' | 'danger' = 'success') => {
    toast.value = { show: true, message, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 4000);
};

// --- Flow E: review queue actions (admin only) ---
const handleQueueApprove = (item: QueueItem) => {
    router.patch(
        route('admin.repository.items.approve', item.id),
        {},
        {
            preserveScroll: true,
            only: ['queue', 'stats', 'repository'],
            onSuccess: () => triggerToast('อนุมัติเผยแพร่รายการแล้ว', 'success'),
            onError: () => triggerToast('ดำเนินการไม่สำเร็จ', 'danger'),
        },
    );
};

const handleQueueReturn = (item: QueueItem) => {
    Swal.fire({
        title: 'ส่งกลับให้แก้ไข',
        input: 'textarea',
        inputLabel: 'ระบุสิ่งที่ต้องแก้ไข',
        inputPlaceholder: 'เช่น ชื่อผู้แต่งสะกดผิด, ไฟล์ PDF เปิดไม่ได้ …',
        inputAttributes: { 'aria-label': 'หมายเหตุการแก้ไข' },
        showCancelButton: true,
        confirmButtonColor: '#1e3a8a',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'ส่งกลับ',
        cancelButtonText: 'ยกเลิก',
        inputValidator: (v) => (!v.trim() ? 'กรุณาระบุหมายเหตุ' : undefined),
    }).then((result) => {
        if (!result.isConfirmed) return;
        router.patch(
            route('admin.repository.items.return', item.id),
            { note: result.value },
            {
                preserveScroll: true,
                only: ['queue', 'stats', 'repository'],
                onSuccess: () => triggerToast('ส่งรายการกลับให้เจ้าของแก้ไขแล้ว', 'warning'),
                onError: () => triggerToast('ดำเนินการไม่สำเร็จ', 'danger'),
            },
        );
    });
};

const handleToggleUserStatus = (member: Member) => {
    router.patch(
        route('admin.members.status', member.id),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                const newStatus = member.status === 'active' ? 'ระงับการใช้งาน' : 'ปกติ';
                triggerToast(`เปลี่ยนสถานะผู้ใช้งานเป็น ${newStatus} สำเร็จ`, 'warning');
            },
        },
    );
};

const handleDeleteMember = async (member: Member) => {
    const result = await Swal.fire({
        title: 'ลบสมาชิก?',
        text: `คุณต้องการลบสมาชิก "${member.name}" ออกจากระบบใช่หรือไม่`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'ลบสมาชิก',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true,
    });
    if (!result.isConfirmed) return;

    router.delete(route('admin.members.destroy', member.id), {
        preserveScroll: true,
        onSuccess: () => triggerToast('ลบสมาชิกสำเร็จ', 'danger'),
    });
};

const handleImpersonate = async (member: Member) => {
    const result = await Swal.fire({
        title: 'สวมสิทธิ์สมาชิก?',
        text: `คุณกำลังจะเข้าสู่ระบบในฐานะ "${member.name}"`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#1e3a8a',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: 'สวมสิทธิ์',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true,
    });
    if (!result.isConfirmed) return;

    router.post(route('admin.members.impersonate', member.id));
};

const handleCreateMember = () => {
    const isMsu = newMember.value.is_msu_member;
    const email = isMsu ? `${newMember.value.email_local}@msu.ac.th` : newMember.value.email;

    if (!newMember.value.name || (isMsu ? !newMember.value.email_local : !newMember.value.email)) {
        triggerToast('กรุณากรอกข้อมูลให้ครบถ้วน', 'danger');
        return;
    }
    if (!isMsu && !newMember.value.password) {
        triggerToast('กรุณากำหนดรหัสผ่านสำหรับสมาชิกภายนอก', 'danger');
        return;
    }

    router.post(
        route('admin.members.store'),
        {
            is_msu_member: isMsu,
            role_level: newMember.value.role_level,
            department_id: newMember.value.department_id,
            name: newMember.value.name,
            email,
            password: isMsu ? '' : newMember.value.password,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isAddMemberModalOpen.value = false;
                newMember.value = { is_msu_member: true, role_level: 1, department_id: null, name: '', email_local: '', email: '', password: '' };
                triggerToast('เพิ่มสมาชิกใหม่สำเร็จ', 'success');
            },
            onError: () => {
                triggerToast('เพิ่มสมาชิกไม่สำเร็จ กรุณาตรวจสอบข้อมูล', 'danger');
            },
        },
    );
};

const handleOpenEditMember = (member: Member) => {
    editMember.value = {
        id: member.id,
        is_msu_member: member.is_msu_member,
        role_level: member.role_level as 1 | 3,
        department_id: member.department_id,
        name: member.name,
        email_local: member.is_msu_member ? member.email.replace(/@msu\.ac\.th$/, '') : '',
        email: member.is_msu_member ? '' : member.email,
        password: '',
    };
    isEditMemberModalOpen.value = true;
};

const handleUpdateMember = () => {
    const isMsu = editMember.value.is_msu_member;
    const email = isMsu ? `${editMember.value.email_local}@msu.ac.th` : editMember.value.email;

    if (!editMember.value.name || (isMsu ? !editMember.value.email_local : !editMember.value.email)) {
        triggerToast('กรุณากรอกข้อมูลให้ครบถ้วน', 'danger');
        return;
    }

    router.put(
        route('admin.members.update', editMember.value.id),
        {
            is_msu_member: isMsu,
            role_level: editMember.value.role_level,
            department_id: editMember.value.department_id,
            name: editMember.value.name,
            email,
            password: isMsu ? '' : editMember.value.password,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isEditMemberModalOpen.value = false;
                triggerToast('บันทึกข้อมูลสมาชิกสำเร็จ', 'success');
            },
            onError: () => {
                triggerToast('บันทึกข้อมูลไม่สำเร็จ กรุณาตรวจสอบข้อมูล', 'danger');
            },
        },
    );
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
watch(isAddMemberModalOpen, (newVal) => {
    document.body.style.overflow = newVal ? 'hidden' : '';
});
watch(isEditMemberModalOpen, (newVal) => {
    document.body.style.overflow = newVal ? 'hidden' : '';
});
</script>

<template>
    <Head title="แผงผู้ดูแลระบบหลังบ้าน | MSU IR">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    </Head>

    <div class="flex min-h-screen font-sarabun custom-scrollbar bg-slate-50 text-slate-800 selection:bg-yellow-100 selection:text-slate-900">
        <!-- --- SIDEBAR NAVIGATION (Desktop - LG ขึ้นไป - ปรับปรุงเป็นแบบพับเก็บได้ Collapsible) --- -->
        <aside
            :class="[
                isSidebarCollapsed ? 'w-20' : 'w-64',
                'relative z-30 flex hidden shrink-0 flex-col justify-between border-r border-blue-900/40 bg-[#1e3a8a] p-6 text-white transition-all duration-300 lg:flex',
            ]"
        >
            <div class="space-y-10">
                <!-- Brand/Logo (จัดตัวอักษร M ให้อยู่กึ่งกลางเป๊ะ) -->
                <div class="flex items-center" :class="isSidebarCollapsed ? 'justify-center' : 'space-x-3'">
                    <div
                        class="flex h-10 w-10 shrink-0 select-none items-center justify-center rounded-xl bg-white text-xl font-black text-[#1e3a8a] shadow-xl shadow-blue-950/40"
                    >
                        M
                    </div>
                    <div v-if="!isSidebarCollapsed" class="min-w-0 transition-opacity duration-300">
                        <span class="block text-base font-black leading-tight tracking-tight text-white uppercase truncate">MSU IR Portal</span>
                        <span class="block truncate text-[9px] font-black uppercase tracking-wider text-yellow-400">Management</span>
                    </div>
                </div>

                <!-- Sidebar Collapse Toggle Button (PC Version) -->
                <button
                    @click="isSidebarCollapsed = !isSidebarCollapsed"
                    class="flex w-full items-center justify-center rounded-xl bg-blue-950/40 py-2.5 text-blue-200 outline-none transition-all hover:bg-blue-950/70 hover:text-white"
                >
                    <i v-if="!isSidebarCollapsed" class="text-xs fa-solid fa-chevron-left"></i>
                    <i v-else class="text-xs fa-solid fa-chevron-right"></i>
                    <span v-if="!isSidebarCollapsed" class="ml-2 text-[10px] font-black uppercase tracking-wider">ย่อแถบข้าง</span>
                </button>

                <!-- Menu Tabs (เปลี่ยนมาใช้ไอคอนของ Font Awesome 6) -->
                <nav class="space-y-1.5">
                    <button
                        @click="activeTab = 'dashboard'"
                        :class="[
                            'flex w-full items-center rounded-xl text-xs font-bold outline-none transition-all duration-300',
                            isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
                            activeTab === 'dashboard'
                                ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg'
                                : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                        ]"
                        title="แดชบอร์ด"
                    >
                        <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-chart-line"></i>
                        <span v-if="!isSidebarCollapsed">แดชบอร์ด</span>
                    </button>

                    <!-- จัดการสมาชิก (admin only) -->
                    <button
                        v-if="isAdmin"
                        @click="activeTab = 'members'"
                        :class="[
                            'flex w-full items-center rounded-xl text-xs font-bold outline-none transition-all duration-300',
                            isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
                            activeTab === 'members' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                        ]"
                        title="จัดการสมาชิก"
                    >
                        <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-user-group"></i>
                        <span v-if="!isSidebarCollapsed" class="truncate">จัดการสมาชิก</span>
                    </button>

                    <button
                        v-if="isAdmin"
                        @click="activeTab = 'import'"
                        :class="[
                            'flex w-full items-center rounded-xl text-xs font-bold outline-none transition-all duration-300',
                            isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
                            activeTab === 'import' ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg' : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                        ]"
                        title="นำเข้าข้อมูล .csv"
                    >
                        <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-file-csv"></i>
                        <span v-if="!isSidebarCollapsed" class="truncate">นำเข้าข้อมูล .csv</span>
                    </button>

                    <button
                        @click="activeTab = 'repository'"
                        :class="[
                            'flex w-full items-center rounded-xl text-xs font-bold outline-none transition-all duration-300',
                            isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
                            activeTab === 'repository'
                                ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg'
                                : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                        ]"
                        title="จัดการคลังข้อมูล"
                    >
                        <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-folder-open"></i>
                        <span v-if="!isSidebarCollapsed" class="truncate">จัดการคลังข้อมูล</span>
                        <span
                            v-if="props.repository.pagination.total > 0 && !isSidebarCollapsed"
                            class="ml-auto rounded-full bg-blue-950 px-2 py-0.5 text-[9px] font-black text-white"
                        >
                            {{ props.repository.pagination.total }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'contribute'"
                        :class="[
                            'flex w-full items-center rounded-xl text-xs font-bold outline-none transition-all duration-300',
                            isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
                            activeTab === 'contribute'
                                ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg'
                                : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                        ]"
                        title="เพิ่มรายการ"
                    >
                        <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-circle-plus"></i>
                        <span v-if="!isSidebarCollapsed" class="truncate">เพิ่มรายการ</span>
                    </button>

                    <button
                        @click="activeTab = 'approvals'"
                        :class="[
                            'flex w-full items-center rounded-xl text-xs font-bold outline-none transition-all duration-300',
                            isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
                            activeTab === 'approvals'
                                ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg'
                                : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                        ]"
                        title="คิวตรวจสอบข้อมูล"
                    >
                        <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-clipboard-check"></i>
                        <span v-if="!isSidebarCollapsed" class="truncate">คิวตรวจสอบข้อมูล</span>
                        <span
                            v-if="queueBadgeCount > 0 && !isSidebarCollapsed"
                            class="ml-auto animate-pulse rounded-full bg-red-500 px-2 py-0.5 text-[9px] font-black text-white"
                        >
                            {{ queueBadgeCount }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'analytics'"
                        :class="[
                            'flex w-full items-center rounded-xl text-xs font-bold outline-none transition-all duration-300',
                            isSidebarCollapsed ? 'justify-center p-3.5' : 'space-x-3 px-4 py-3.5',
                            activeTab === 'analytics'
                                ? 'bg-[#facc15] text-[#1e3a8a] shadow-lg'
                                : 'text-blue-100/70 hover:bg-white/10 hover:text-white',
                        ]"
                        title="สถิติและการดาวน์โหลด"
                    >
                        <i class="flex items-center justify-center w-5 h-5 text-sm fa-solid fa-chart-bar"></i>
                        <span v-if="!isSidebarCollapsed" class="truncate">สถิติและการดาวน์โหลด</span>
                    </button>
                </nav>
            </div>

            <!-- Sidebar Footer -->
            <div class="space-y-5">
                <div class="rounded-[1.5rem] border border-blue-800/50 bg-blue-950/40 p-4" v-if="!isSidebarCollapsed">
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-ping"></span>
                        <span class="text-[10px] font-black uppercase tracking-widest text-green-300">Server Online</span>
                    </div>
                    <p class="pl-4 text-[10px] font-medium leading-relaxed text-blue-200/80">เชื่อมต่อฐานข้อมูลหลัก</p>
                </div>
                <button
                    @click="logout"
                    class="flex w-full items-center justify-center rounded-xl bg-red-500/10 py-3 text-[11px] font-black uppercase tracking-widest text-red-300 outline-none transition-all hover:bg-red-500 hover:text-white"
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
            <header
                class="sticky top-0 z-20 flex items-center justify-between h-20 px-8 border-b shrink-0 border-slate-200 bg-white/80 backdrop-blur-md"
            >
                <!-- Mobile Left section (จัด "M" สีขาวให้อยู่กึ่งกลางของวงกลม) -->
                <div class="flex items-center space-x-4 lg:hidden">
                    <button
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="flex items-center justify-center w-10 h-10 rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100"
                    >
                        <i class="text-lg fa-solid fa-bars"></i>
                    </button>
                    <div class="flex items-center space-x-2">
                        <div
                            class="flex h-9 w-9 select-none items-center justify-center rounded-lg bg-[#1e3a8a] text-base font-black text-white shadow-sm"
                        >
                            M
                        </div>
                        <span class="text-sm font-black tracking-tight text-slate-900">MSU IR Admin</span>
                    </div>
                </div>

                <!-- Desktop Title context -->
                <div class="hidden lg:block">
                    <h1 class="text-xl font-black tracking-tight text-slate-900">
                        <span v-if="activeTab === 'dashboard'">แดชบอร์ดภาพรวมคลังเอกสารและวิทยานิพนธ์</span>
                        <span v-else-if="activeTab === 'repository'">จัดการรายการเอกสารวิชาการดิจิทัล</span>
                        <span v-else-if="activeTab === 'contribute'">เพิ่มรายการใหม่เข้าคลัง</span>
                        <span v-else-if="activeTab === 'import'">นำเข้าข้อมูลจากไฟล์ CSV</span>
                        <span v-else-if="activeTab === 'approvals'">รายการเอกสารคิวตรวจสอบและพิจารณา</span>
                        <span v-else-if="activeTab === 'analytics'">ข้อมูลเชิงลึกและการวิเคราะห์พฤติกรรมการสืบค้น</span>
                        <span v-else-if="activeTab === 'members'">การจัดการข้อมูลสมาชิกและสิทธิ์ระบบ</span>
                    </h1>
                    <p class="mt-0.5 text-xs font-bold uppercase tracking-wider text-slate-400">Maha Sarakham University</p>
                </div>

                <!-- Right Side Controls -->
                <div class="flex items-center space-x-4 sm:space-x-6">
                    <!-- Notification Bell -->
                    <div class="relative hidden cursor-pointer rounded-xl p-2.5 transition-colors hover:bg-slate-100 sm:block">
                        <span class="absolute right-1.5 top-1.5 h-2 w-2 animate-pulse rounded-full bg-red-500"></span>
                        <i class="text-lg fa-regular fa-bell text-slate-600"></i>
                    </div>

                    <!-- Profile Control -->
                    <div class="relative">
                        <button
                            @click="isProfileOpen = !isProfileOpen"
                            class="flex items-center space-x-3 rounded-xl p-1.5 outline-none transition-colors hover:bg-slate-50"
                        >
                            <div
                                class="flex h-9 w-9 shrink-0 select-none items-center justify-center rounded-full border-2 border-[#facc15] bg-yellow-100 text-sm font-black text-yellow-700"
                            >
                                AD
                            </div>
                            <div class="hidden text-left md:block">
                                <span class="block text-xs font-black leading-tight text-slate-900">Admin-MSU-IR</span>
                                <span class="mt-0.5 block text-[10px] font-bold text-slate-400">ฝ่ายตรวจสอบคลัง</span>
                            </div>
                            <i class="text-xs fa-solid fa-chevron-down text-slate-400"></i>
                        </button>

                        <!-- Profile Dropdown Menu -->
                        <transition name="dropdown-fade">
                            <div
                                v-if="isProfileOpen"
                                class="animate-fade-in absolute right-0 z-50 mt-3 w-56 rounded-[1.5rem] border border-slate-100 bg-white p-3 shadow-2xl"
                            >
                                <div class="p-3 mb-2 border-b border-slate-100">
                                    <p class="text-xs font-bold tracking-wider uppercase text-slate-400">บัญชีผู้ใช้</p>
                                    <p class="mt-1 text-sm font-black truncate text-slate-800">somsak.m@msu.ac.th</p>
                                </div>
                                <button
                                    class="flex w-full items-center rounded-xl p-2.5 text-left text-xs font-bold text-slate-600 transition-all hover:bg-slate-50 hover:text-blue-900"
                                >
                                    <i class="flex items-center justify-center w-5 h-5 mr-2 text-sm fa-regular fa-circle-user"></i> ข้อมูลส่วนตัว
                                </button>
                                <button
                                    @click="logout"
                                    class="mt-2 flex w-full items-center rounded-xl p-2.5 text-left text-xs font-black text-red-500 transition-all hover:bg-red-50"
                                >
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
                    <p class="text-xs font-bold text-slate-400">
                        {{ isAdmin ? 'ภาพรวมคลังข้อมูลทั้งหมด' : 'ภาพรวมเฉพาะรายการที่คุณนำเข้า' }}
                    </p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <p class="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">รายการทั้งหมด</p>
                            <h3 class="text-4xl font-black text-slate-900">{{ props.stats.total.toLocaleString() }}</h3>
                        </div>
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <p class="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">เผยแพร่แล้ว</p>
                            <h3 class="text-4xl font-black text-green-600">{{ props.stats.approved.toLocaleString() }}</h3>
                            <div class="mt-3 h-1.5 w-32 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full bg-[#facc15] transition-all duration-700"
                                    :style="{ width: `${props.stats.total ? (props.stats.approved / props.stats.total) * 100 : 0}%` }"
                                ></div>
                            </div>
                        </div>
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <p class="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">รอตรวจสอบ</p>
                            <h3 class="text-4xl font-black text-yellow-600">{{ props.stats.pending.toLocaleString() }}</h3>
                        </div>
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <p class="mb-1 text-[10px] font-black uppercase tracking-widest text-slate-400">ต้องแก้ไข</p>
                            <h3 class="text-4xl font-black text-red-500">{{ props.stats.actionRequired.toLocaleString() }}</h3>
                        </div>
                    </div>

                    <!-- Breakdown -->
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                        <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <h3 class="mb-6 text-lg font-black tracking-tight text-slate-900">แยกตามคอลเลกชัน</h3>
                            <div v-if="props.stats.byCollection.length" class="space-y-4">
                                <div v-for="row in props.stats.byCollection" :key="row.name">
                                    <div class="flex justify-between mb-1 text-xs font-bold text-slate-600">
                                        <span>{{ row.name }}</span
                                        ><span>{{ row.count.toLocaleString() }}</span>
                                    </div>
                                    <div class="w-full h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full bg-[#1e3a8a]"
                                            :style="{ width: `${(row.count / maxCollectionCount) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs font-bold text-slate-300">ยังไม่มีข้อมูล</p>
                        </div>

                        <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <h3 class="mb-6 text-lg font-black tracking-tight text-slate-900">แยกตามปี (ล่าสุด)</h3>
                            <div v-if="props.stats.byYear.length" class="space-y-4">
                                <div v-for="row in props.stats.byYear" :key="row.year">
                                    <div class="flex justify-between mb-1 text-xs font-bold text-slate-600">
                                        <span>{{ row.year }}</span
                                        ><span>{{ row.count.toLocaleString() }}</span>
                                    </div>
                                    <div class="w-full h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full bg-[#facc15]"
                                            :style="{ width: `${(row.count / maxYearCount) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs font-bold text-slate-300">ยังไม่มีข้อมูล</p>
                        </div>
                    </div>

                    <!-- Recent -->
                    <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-black tracking-tight text-slate-900">รายการล่าสุด</h3>
                            <button
                                @click="activeTab = 'repository'"
                                class="text-xs font-black text-[#1e3a8a] transition-colors hover:text-yellow-600"
                            >
                                ดูทั้งหมด
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        <th class="px-6 py-4">ID</th>
                                        <th class="px-6 py-4">คอลเลกชัน</th>
                                        <th class="w-[50%] px-6 py-4">ชื่อเรื่อง / ผู้แต่ง</th>
                                        <th class="px-6 py-4">สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="item in props.stats.recent" :key="item.id" class="transition-colors hover:bg-slate-50/50">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">{{ item.id }}</td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black text-[#1e3a8a]">{{
                                                item.collection ?? '—'
                                            }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="block text-sm font-bold leading-snug line-clamp-1 text-slate-800">{{ item.title }}</span>
                                            <span class="mt-0.5 block text-[11px] font-bold text-slate-400">{{
                                                item.author ?? 'ไม่ระบุผู้แต่ง'
                                            }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                :class="[
                                                    'inline-flex items-center rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest',
                                                    statusMeta[item.status].cls,
                                                ]"
                                            >
                                                <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-current"></span>{{ statusMeta[item.status].label }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr v-if="props.stats.recent.length === 0">
                                        <td colspan="4" class="py-10 text-sm font-bold text-center text-slate-400">ยังไม่มีรายการ</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- TAB 2: REPOSITORY MANAGEMENT (Flow C — real data, role-aware) -->
                <!-- ========================================================== -->
                <div
                    v-else-if="activeTab === 'repository'"
                    class="animate-fade-in space-y-6 rounded-[2.5rem] border border-slate-100 bg-white p-6 shadow-sm sm:p-8"
                >
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <!-- Search bar input -->
                        <div class="relative w-full max-w-md group">
                            <i
                                class="absolute transition-colors -translate-y-1/2 fa-solid fa-magnifying-glass left-4 top-1/2 text-slate-400 group-focus-within:text-blue-900"
                            ></i>
                            <input
                                type="text"
                                placeholder="ค้นหา ID, ชื่อเรื่อง หรือผู้แต่ง..."
                                v-model="repoFilters.q"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm font-medium outline-none transition-all focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>

                        <div class="flex flex-wrap items-center gap-2 shrink-0">
                            <button
                                v-if="isAdmin"
                                @click="activeTab = 'import'"
                                class="flex items-center gap-2 px-4 py-3 text-xs font-black transition bg-white border rounded-2xl border-slate-200 text-slate-600 hover:border-blue-300 hover:text-blue-900"
                            >
                                <i class="fa-solid fa-file-csv text-[#1e3a8a]"></i> นำเข้า CSV
                            </button>
                            <button
                                @click="activeTab = 'contribute'"
                                class="flex items-center gap-2 rounded-2xl bg-[#1e3a8a] px-4 py-3 text-xs font-black text-white shadow-lg shadow-blue-900/10 transition-all hover:bg-blue-800 active:scale-95"
                            >
                                <i class="fa-solid fa-plus text-[11px]"></i> เพิ่มรายการ
                            </button>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-xs font-black tracking-widest uppercase text-slate-400">กรอง:</span>
                        <div class="w-56">
                            <ModernSelect
                                v-model="repoFilters.collection"
                                :options="[
                                    { value: null, label: 'ทุกคอลเลกชัน' },
                                    ...props.repository.collections.map((c) => ({ value: c.id, label: c.name })),
                                ]"
                            />
                        </div>
                        <div class="w-44">
                            <ModernSelect
                                v-model="repoFilters.status"
                                :options="[
                                    { value: null, label: 'ทุกสถานะ' },
                                    { value: 'approved', label: 'เผยแพร่แล้ว' },
                                    { value: 'pending', label: 'รอตรวจสอบ' },
                                    { value: 'action_required', label: 'ต้องแก้ไข' },
                                ]"
                            />
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    <th class="px-6 py-5">ID</th>
                                    <th class="px-6 py-5">คอลเลกชัน</th>
                                    <th class="w-[45%] px-6 py-5">ชื่อเรื่อง / ผู้แต่ง</th>
                                    <th class="px-6 py-5">ปี</th>
                                    <th class="px-6 py-5">สถานะ</th>
                                    <th class="px-6 py-5 text-center">ปฏิบัติการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <template v-if="props.repository.items.length > 0">
                                    <tr v-for="item in props.repository.items" :key="item.id" class="transition-colors hover:bg-slate-50/50">
                                        <td class="px-6 py-4 font-mono text-xs font-bold text-slate-500">{{ item.id }}</td>
                                        <td class="px-6 py-4">
                                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black text-[#1e3a8a]">{{
                                                item.collection ?? '—'
                                            }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="block text-sm font-bold leading-snug text-slate-800">{{ item.title }}</span>
                                            <span class="mt-1 block text-[11px] font-bold text-slate-400">{{ item.author ?? 'ไม่ระบุผู้แต่ง' }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500">{{ item.year ?? '—' }}</td>
                                        <td class="px-6 py-4">
                                            <span
                                                :class="[
                                                    'inline-flex items-center rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest',
                                                    statusMeta[item.status].cls,
                                                ]"
                                            >
                                                <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ statusMeta[item.status].label }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center space-x-2">
                                                <Link
                                                    v-if="item.canEdit"
                                                    :href="route('admin.repository.items.edit', item.id)"
                                                    class="flex items-center justify-center w-8 h-8 transition-all rounded-full bg-slate-50 text-slate-400 hover:bg-blue-900 hover:text-white"
                                                    title="แก้ไขรายการ"
                                                >
                                                    <i class="text-xs fa-solid fa-pen"></i>
                                                </Link>
                                                <button
                                                    v-if="isAdmin"
                                                    @click="handleDeleteItem(item)"
                                                    class="flex items-center justify-center w-8 h-8 text-red-500 transition-all rounded-full bg-red-50 hover:bg-red-500 hover:text-white"
                                                    title="ลบรายการ"
                                                >
                                                    <i class="text-xs fa-regular fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <tr>
                                        <td colspan="6" class="py-12 text-sm font-bold text-center text-slate-400">
                                            <i class="mb-3 text-4xl fa-regular fa-folder-open text-slate-300"></i>
                                            <p>ไม่พบรายการในคลังตามเงื่อนไขนี้</p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="props.repository.pagination.lastPage > 1" class="flex flex-col items-center gap-3 pt-2 sm:flex-row sm:justify-between">
                        <p class="text-xs font-bold text-slate-500">
                            ทั้งหมด {{ props.repository.pagination.total.toLocaleString() }} รายการ · หน้า
                            {{ props.repository.pagination.currentPage }} / {{ props.repository.pagination.lastPage }}
                        </p>
                        <div class="flex items-center gap-2">
                            <button
                                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a] disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="props.repository.pagination.currentPage === 1"
                                @click="repoGoToPage(props.repository.pagination.currentPage - 1)"
                            >
                                <i class="text-xs fa-solid fa-chevron-left"></i>
                            </button>
                            <button
                                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 hover:border-blue-200 hover:bg-blue-50 hover:text-[#1e3a8a] disabled:cursor-not-allowed disabled:opacity-40"
                                :disabled="props.repository.pagination.currentPage === props.repository.pagination.lastPage"
                                @click="repoGoToPage(props.repository.pagination.currentPage + 1)"
                            >
                                <i class="text-xs fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- TAB: CSV IMPORT (Flow A) — admin only -->
                <!-- ========================================================== -->
                <div
                    v-else-if="activeTab === 'import'"
                    class="animate-fade-in space-y-8 rounded-[2.5rem] border border-slate-100 bg-white p-6 shadow-sm sm:p-8"
                >
                    <!-- Step indicator -->
                    <div class="flex items-center gap-3 text-xs font-black">
                        <div
                            v-for="s in [
                                { n: 1, t: 'อัปโหลดไฟล์' },
                                { n: 2, t: 'ตรวจสอบข้อมูล' },
                                { n: 3, t: 'นำเข้าสำเร็จ' },
                            ]"
                            :key="s.n"
                            class="flex items-center gap-3"
                        >
                            <span
                                :class="[
                                    'flex h-7 w-7 items-center justify-center rounded-full',
                                    importStep >= s.n ? 'bg-[#1e3a8a] text-white' : 'bg-slate-100 text-slate-400',
                                ]"
                                >{{ s.n }}</span
                            >
                            <span :class="importStep >= s.n ? 'text-slate-800' : 'text-slate-400'">{{ s.t }}</span>
                            <i v-if="s.n < 3" class="fa-solid fa-chevron-right text-[9px] text-slate-300"></i>
                        </div>
                    </div>

                    <!-- STEP 1: template + upload -->
                    <div v-if="importStep === 1" class="space-y-6">
                        <a
                            :href="route('admin.repository.import.template')"
                            class="flex items-center gap-4 p-5 transition border group rounded-2xl border-slate-200 bg-slate-50/60 hover:border-blue-300 hover:bg-blue-50/40"
                        >
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#1e3a8a] text-white">
                                <i class="fa-solid fa-file-arrow-down"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-800">ดาวน์โหลดไฟล์ตัวอย่าง (.csv)</p>
                                <p class="text-xs font-bold text-slate-400">
                                    หัวคอลัมน์ครบ 29 ช่อง + ตัวอย่าง 2 แถว — แก้ไขให้เสร็จก่อนแล้วค่อยกลับมาอัปโหลด
                                </p>
                            </div>
                            <i class="fa-solid fa-download ml-auto text-slate-300 group-hover:text-[#1e3a8a]"></i>
                        </a>

                        <label
                            :class="[
                                'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed p-10 text-center transition',
                                importBusy ? 'border-blue-300 bg-blue-50/40' : 'border-slate-200 hover:border-blue-300 hover:bg-slate-50',
                            ]"
                        >
                            <i
                                :class="[
                                    'text-3xl',
                                    importBusy ? 'fa-solid fa-spinner fa-spin text-[#1e3a8a]' : 'fa-solid fa-cloud-arrow-up text-slate-400',
                                ]"
                            ></i>
                            <span class="text-sm font-black text-slate-700">{{
                                importBusy ? 'กำลังตรวจสอบไฟล์…' : 'เลือกไฟล์ .csv เพื่ออัปโหลด'
                            }}</span>
                            <span class="text-[10px] font-bold text-slate-400">ขนาดไม่เกิน 5MB · รองรับเฉพาะ .csv</span>
                            <input
                                ref="importFileInput"
                                type="file"
                                accept=".csv"
                                class="hidden"
                                :disabled="importBusy"
                                @change="onImportFilePicked"
                            />
                        </label>
                    </div>

                    <!-- STEP 2: validation report -->
                    <div v-else-if="importStep === 2 && importReport" class="space-y-6">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-500">
                            <i class="fa-solid fa-file-csv text-[#1e3a8a]"></i>
                            <span class="font-black text-slate-800">{{ importReport.filename }}</span>
                        </div>

                        <div
                            v-if="importReport.missingColumns?.length || importReport.unexpectedColumns?.length"
                            class="p-4 space-y-1 text-xs font-bold border rounded-2xl border-amber-200 bg-amber-50 text-amber-800"
                        >
                            <p v-if="importReport.missingColumns?.length">คอลัมน์ที่ขาด: {{ importReport.missingColumns.join(', ') }}</p>
                            <p v-if="importReport.unexpectedColumns?.length">
                                คอลัมน์ที่ระบบไม่รู้จัก (จะถูกข้าม): {{ importReport.unexpectedColumns.join(', ') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div class="p-4 border rounded-2xl border-slate-100 bg-slate-50">
                                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">ทั้งหมด</p>
                                <p class="text-2xl font-black text-slate-800">{{ importReport.summary?.total }}</p>
                            </div>
                            <div class="p-4 border border-green-100 rounded-2xl bg-green-50">
                                <p class="text-[10px] font-black uppercase tracking-widest text-green-600">พร้อมนำเข้า</p>
                                <p class="text-2xl font-black text-green-700">{{ importReport.summary?.ready }}</p>
                            </div>
                            <div class="p-4 border border-yellow-100 rounded-2xl bg-yellow-50">
                                <p class="text-[10px] font-black uppercase tracking-widest text-yellow-600">ซ้ำ — ข้าม</p>
                                <p class="text-2xl font-black text-yellow-700">{{ importReport.summary?.duplicate }}</p>
                            </div>
                            <div class="p-4 border border-red-100 rounded-2xl bg-red-50">
                                <p class="text-[10px] font-black uppercase tracking-widest text-red-600">ผิดพลาด — ข้าม</p>
                                <p class="text-2xl font-black text-red-700">{{ importReport.summary?.error }}</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto border rounded-2xl border-slate-100">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                        <th class="px-4 py-3">บรรทัด</th>
                                        <th class="w-[40%] px-4 py-3">ชื่อเรื่อง</th>
                                        <th class="px-4 py-3">คอลเลกชัน</th>
                                        <th class="px-4 py-3">ปี</th>
                                        <th class="px-4 py-3">สถานะ</th>
                                        <th class="px-4 py-3">หมายเหตุ</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="row in importReport.preview" :key="row.line" class="align-top">
                                        <td class="px-4 py-3 font-mono text-xs font-bold text-slate-400">{{ row.line }}</td>
                                        <td class="px-4 py-3 text-xs font-bold text-slate-700">
                                            {{ row.title || '—' }}
                                            <span v-if="row.creator" class="mt-0.5 block text-[10px] font-bold text-slate-400">{{
                                                row.creator
                                            }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-xs font-bold text-slate-500">{{ row.collection_id }}</td>
                                        <td class="px-4 py-3 text-xs font-bold text-slate-500">{{ row.year ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                :class="[
                                                    'whitespace-nowrap rounded-full px-2.5 py-0.5 text-[10px] font-black',
                                                    importStatusMeta[row.status].cls,
                                                ]"
                                                >{{ importStatusMeta[row.status].label }}</span
                                            >
                                        </td>
                                        <td class="px-4 py-3">
                                            <ul class="space-y-0.5">
                                                <li
                                                    v-for="(iss, i) in row.issues"
                                                    :key="i"
                                                    :class="['text-[10px] font-bold', iss.level === 'error' ? 'text-red-600' : 'text-amber-600']"
                                                >
                                                    {{ iss.message }}
                                                </li>
                                            </ul>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p
                            v-if="(importReport.summary?.total ?? 0) > (importReport.preview?.length ?? 0)"
                            class="text-[11px] font-bold text-slate-400"
                        >
                            แสดง {{ importReport.preview?.length }} แถวแรกจาก {{ importReport.summary?.total }} แถว
                        </p>

                        <div class="flex flex-wrap gap-3">
                            <button
                                :disabled="importBusy || (importReport.summary?.ready ?? 0) === 0"
                                @click="submitImportCommit"
                                class="rounded-2xl bg-[#1e3a8a] px-6 py-3.5 text-xs font-black text-white shadow-lg shadow-blue-900/10 transition-all hover:bg-blue-800 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                <i v-if="importBusy" class="fa-solid fa-spinner fa-spin mr-1.5"></i>
                                นำเข้า {{ importReport.summary?.ready ?? 0 }} รายการ
                            </button>
                            <button
                                :disabled="importBusy"
                                @click="resetImport"
                                class="rounded-2xl px-6 py-3.5 text-xs font-bold text-slate-500 hover:bg-slate-50 disabled:opacity-40"
                            >
                                เลือกไฟล์ใหม่
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: done -->
                    <div v-else-if="importStep === 3" class="py-8 space-y-4 text-center">
                        <i class="text-5xl text-green-500 fa-regular fa-circle-check"></i>
                        <p class="text-lg font-black text-slate-800">นำเข้าข้อมูลสำเร็จ</p>
                        <p class="text-sm font-bold text-slate-500">
                            เพิ่มใหม่ {{ importResult?.created ?? 0 }} รายการ · ข้าม
                            {{ (importResult?.skippedError ?? 0) + (importResult?.skippedDuplicate ?? 0) }} รายการ (ผิดพลาด
                            {{ importResult?.skippedError ?? 0 }} / ซ้ำ {{ importResult?.skippedDuplicate ?? 0 }})
                        </p>
                        <div class="flex justify-center gap-3 pt-2">
                            <button
                                @click="activeTab = 'repository'"
                                class="rounded-2xl bg-[#1e3a8a] px-6 py-3 text-xs font-black text-white transition-all hover:bg-blue-800 active:scale-95"
                            >
                                ดูในตารางคลังข้อมูล
                            </button>
                            <button @click="resetImport" class="px-6 py-3 text-xs font-bold rounded-2xl text-slate-500 hover:bg-slate-50">
                                นำเข้าไฟล์อื่น
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- TAB: ADD ITEM WIZARD (Flow B) — staff + admin -->
                <!-- ========================================================== -->
                <div
                    v-else-if="activeTab === 'contribute'"
                    class="animate-fade-in space-y-8 rounded-[2.5rem] border border-slate-100 bg-white p-6 shadow-sm sm:p-8"
                >
                    <!-- DONE -->
                    <div v-if="wizDone" class="py-8 space-y-4 text-center">
                        <i class="text-5xl text-green-500 fa-regular fa-circle-check"></i>
                        <p class="text-lg font-black text-slate-800">
                            {{ isAdmin ? 'เพิ่มรายการเข้าคลังเรียบร้อย' : 'ส่งรายการเข้าคิวตรวจสอบแล้ว' }}
                        </p>
                        <p class="text-sm font-bold text-slate-500">
                            {{ isAdmin ? 'รายการถูกเผยแพร่ทันที' : 'รอผู้ดูแลระบบอนุมัติก่อนเผยแพร่' }}
                        </p>
                        <div class="flex justify-center gap-3 pt-2">
                            <button
                                @click="resetWizard"
                                class="rounded-2xl bg-[#1e3a8a] px-6 py-3 text-xs font-black text-white transition-all hover:bg-blue-800 active:scale-95"
                            >
                                เพิ่มรายการอีก
                            </button>
                            <button
                                @click="activeTab = 'repository'"
                                class="px-6 py-3 text-xs font-bold rounded-2xl text-slate-500 hover:bg-slate-50"
                            >
                                ไปที่ตารางคลังข้อมูล
                            </button>
                        </div>
                    </div>

                    <template v-else>
                        <!-- Step indicator -->
                        <div class="flex flex-wrap items-center gap-2 text-[11px] font-black">
                            <template v-for="(label, i) in ['ประเภท/ชื่อเรื่อง', 'ผู้แต่ง', 'บรรณานุกรม', 'หัวเรื่อง', 'ไฟล์/ตรวจทาน']" :key="i">
                                <span
                                    :class="[
                                        'flex h-6 w-6 shrink-0 items-center justify-center rounded-full',
                                        wizStep >= i + 1 ? 'bg-[#1e3a8a] text-white' : 'bg-slate-100 text-slate-400',
                                    ]"
                                    >{{ i + 1 }}</span
                                >
                                <span :class="wizStep === i + 1 ? 'text-slate-800' : 'text-slate-400'">{{ label }}</span>
                                <i v-if="i < 4" class="fa-solid fa-chevron-right mr-1 text-[8px] text-slate-300"></i>
                            </template>
                        </div>

                        <!-- STEP 1 -->
                        <div v-if="wizStep === 1" class="space-y-5">
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400"
                                    >คอลเลกชัน <span class="text-red-500">*</span></label
                                >
                                <ModernSelect
                                    v-model="wizForm.collection_id"
                                    placeholder="เลือกคอลเลกชัน"
                                    :options="props.repository.collections.map((c) => ({ value: c.id, label: c.name }))"
                                />
                            </div>
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400"
                                    >ชื่อเรื่อง <span class="text-red-500">*</span></label
                                >
                                <textarea
                                    v-model="wizForm.title"
                                    rows="2"
                                    placeholder="ชื่อเรื่องฉบับสมบูรณ์"
                                    class="w-full px-4 py-3 text-sm font-bold border outline-none resize-none rounded-2xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                ></textarea>
                            </div>
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400"
                                    >ภาษา <span class="text-red-500">*</span></label
                                >
                                <div class="flex gap-3">
                                    <button
                                        type="button"
                                        @click="wizForm.language = 'tha'"
                                        :class="[
                                            'flex-1 rounded-2xl border-2 py-3 text-xs font-black transition',
                                            wizForm.language === 'tha'
                                                ? 'border-blue-900 bg-blue-50 text-blue-900'
                                                : 'border-slate-200 bg-slate-50 text-slate-400',
                                        ]"
                                    >
                                        ไทย
                                    </button>
                                    <button
                                        type="button"
                                        @click="wizForm.language = 'eng'"
                                        :class="[
                                            'flex-1 rounded-2xl border-2 py-3 text-xs font-black transition',
                                            wizForm.language === 'eng'
                                                ? 'border-blue-900 bg-blue-50 text-blue-900'
                                                : 'border-slate-200 bg-slate-50 text-slate-400',
                                        ]"
                                    >
                                        อังกฤษ
                                    </button>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-black tracking-widest uppercase text-slate-400">ชื่อเรื่องรอง / คู่ขนาน</label>
                                    <button
                                        type="button"
                                        @click="wizAddRow('alt_titles')"
                                        class="text-[11px] font-black text-[#1e3a8a] hover:underline"
                                    >
                                        <i class="mr-1 fa-solid fa-plus"></i>เพิ่ม
                                    </button>
                                </div>
                                <div v-if="wizForm.alt_titles.length === 0" class="text-[11px] font-bold text-slate-300">— ไม่มี —</div>
                                <div v-for="(t, i) in wizForm.alt_titles" :key="i" class="flex gap-2 mb-2">
                                    <input
                                        v-model="wizForm.alt_titles[i]"
                                        type="text"
                                        placeholder="ชื่อเรื่องรอง"
                                        class="flex-1 px-4 py-3 text-sm font-bold border outline-none rounded-xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                    />
                                    <button
                                        type="button"
                                        @click="wizRemoveRow('alt_titles', i)"
                                        class="flex items-center justify-center text-red-500 w-11 shrink-0 rounded-xl bg-red-50 hover:bg-red-500 hover:text-white"
                                    >
                                        <i class="text-xs fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 2 -->
                        <div v-else-if="wizStep === 2" class="space-y-5">
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">ผู้แต่งหลัก (creator)</label>
                                <input
                                    v-model="wizForm.creator"
                                    type="text"
                                    placeholder="เช่น สมชาย ใจดี"
                                    class="w-full px-4 py-3 text-sm font-bold border outline-none rounded-2xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                />
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-black tracking-widest uppercase text-slate-400">ผู้แต่งร่วม (contributors)</label>
                                    <button
                                        type="button"
                                        @click="wizAddRow('contributors')"
                                        class="text-[11px] font-black text-[#1e3a8a] hover:underline"
                                    >
                                        <i class="mr-1 fa-solid fa-plus"></i>เพิ่ม
                                    </button>
                                </div>
                                <div v-if="wizForm.contributors.length === 0" class="text-[11px] font-bold text-slate-300">— ไม่มี —</div>
                                <div v-for="(c, i) in wizForm.contributors" :key="i" class="flex gap-2 mb-2">
                                    <span
                                        class="flex items-center justify-center text-xs font-black w-9 shrink-0 rounded-xl bg-slate-100 text-slate-400"
                                        >{{ i + 1 }}</span
                                    >
                                    <input
                                        v-model="wizForm.contributors[i]"
                                        type="text"
                                        placeholder="ชื่อผู้แต่งร่วม"
                                        class="flex-1 px-4 py-3 text-sm font-bold border outline-none rounded-xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                    />
                                    <button
                                        type="button"
                                        @click="wizRemoveRow('contributors', i)"
                                        class="flex items-center justify-center text-red-500 w-11 shrink-0 rounded-xl bg-red-50 hover:bg-red-500 hover:text-white"
                                    >
                                        <i class="text-xs fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- STEP 3 -->
                        <div v-else-if="wizStep === 3" class="space-y-5">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400"
                                        >ปี (พ.ศ./ค.ศ. ตามต้นฉบับ)</label
                                    >
                                    <input
                                        v-model.number="wizForm.year_issued"
                                        type="number"
                                        placeholder="เช่น 2566"
                                        class="w-full px-4 py-3 text-sm font-bold border outline-none rounded-2xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                    />
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">หน่วยงาน / คณะ</label>
                                    <ModernSelect
                                        v-model="wizForm.department_id"
                                        :options="[
                                            { value: null, label: 'ไม่ระบุ' },
                                            ...props.departments.map((d) => ({ value: d.id, label: d.name })),
                                        ]"
                                    />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">สิทธิ์ (rights)</label>
                                    <input
                                        v-model="wizForm.rights"
                                        type="text"
                                        placeholder="เช่น สงวนลิขสิทธิ์ มมส."
                                        class="w-full px-4 py-3 text-sm font-bold border outline-none rounded-2xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                    />
                                </div>
                                <div>
                                    <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">ระดับปริญญา (degree)</label>
                                    <input
                                        v-model="wizForm.degree"
                                        type="text"
                                        placeholder="ปล่อยว่างได้"
                                        class="w-full px-4 py-3 text-sm font-bold border outline-none rounded-2xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">บทคัดย่อ / คำอธิบาย</label>
                                <textarea
                                    v-model="wizForm.description"
                                    rows="4"
                                    placeholder="ปล่อยว่างได้"
                                    class="w-full px-4 py-3 text-sm font-bold border outline-none resize-none rounded-2xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                ></textarea>
                            </div>
                        </div>

                        <!-- STEP 4 -->
                        <div v-else-if="wizStep === 4" class="space-y-4">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-black tracking-widest uppercase text-slate-400">หัวเรื่อง (subjects)</label>
                                <button type="button" @click="wizAddRow('subjects')" class="text-[11px] font-black text-[#1e3a8a] hover:underline">
                                    <i class="mr-1 fa-solid fa-plus"></i>เพิ่ม
                                </button>
                            </div>
                            <div v-if="wizForm.subjects.length === 0" class="text-[11px] font-bold text-slate-300">— ไม่มี —</div>
                            <div v-for="(s, i) in wizForm.subjects" :key="i" class="flex gap-2">
                                <input
                                    v-model="wizForm.subjects[i]"
                                    type="text"
                                    placeholder="เช่น การพัฒนาระบบสารสนเทศ"
                                    class="flex-1 px-4 py-3 text-sm font-bold border outline-none rounded-xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                />
                                <button
                                    type="button"
                                    @click="wizRemoveRow('subjects', i)"
                                    class="flex items-center justify-center text-red-500 w-11 shrink-0 rounded-xl bg-red-50 hover:bg-red-500 hover:text-white"
                                >
                                    <i class="text-xs fa-solid fa-xmark"></i>
                                </button>
                            </div>
                        </div>

                        <!-- STEP 5 -->
                        <div v-else-if="wizStep === 5" class="space-y-5">
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400"
                                    >ไฟล์ฉบับเต็ม <span class="text-red-500">*</span></label
                                >
                                <div class="flex gap-3 mb-3">
                                    <button
                                        type="button"
                                        @click="wizForm.fulltext_mode = 'url'"
                                        :class="[
                                            'flex-1 rounded-2xl border-2 py-3 text-xs font-black transition',
                                            wizForm.fulltext_mode === 'url'
                                                ? 'border-blue-900 bg-blue-50 text-blue-900'
                                                : 'border-slate-200 bg-slate-50 text-slate-400',
                                        ]"
                                    >
                                        <i class="fa-solid fa-link mr-1.5"></i>แนบลิงก์
                                    </button>
                                    <button
                                        type="button"
                                        @click="wizForm.fulltext_mode = 'file'"
                                        :class="[
                                            'flex-1 rounded-2xl border-2 py-3 text-xs font-black transition',
                                            wizForm.fulltext_mode === 'file'
                                                ? 'border-blue-900 bg-blue-50 text-blue-900'
                                                : 'border-slate-200 bg-slate-50 text-slate-400',
                                        ]"
                                    >
                                        <i class="fa-solid fa-file-arrow-up mr-1.5"></i>อัปโหลด PDF
                                    </button>
                                </div>
                                <input
                                    v-if="wizForm.fulltext_mode === 'url'"
                                    v-model="wizForm.fulltext_url"
                                    type="url"
                                    placeholder="https://…/fulltext.pdf"
                                    class="w-full px-4 py-3 text-sm font-bold border outline-none rounded-2xl border-slate-200 bg-slate-50 focus:border-blue-900 focus:bg-white"
                                />
                                <label
                                    v-else
                                    class="flex flex-col items-center justify-center gap-1 p-6 text-center border-2 border-dashed cursor-pointer rounded-2xl border-slate-200 hover:border-blue-300 hover:bg-slate-50"
                                >
                                    <i class="text-2xl fa-solid fa-file-arrow-up text-slate-400"></i>
                                    <span class="text-xs font-black text-slate-700">{{
                                        wizForm.fulltext_file ? wizForm.fulltext_file.name : 'เลือกไฟล์ PDF'
                                    }}</span>
                                    <span class="text-[10px] font-bold text-slate-400">ไม่เกิน 50MB</span>
                                    <input ref="wizFileInput" type="file" accept="application/pdf,.pdf" class="hidden" @change="onWizFilePicked" />
                                </label>
                            </div>

                            <!-- Review -->
                            <div class="p-5 space-y-2 text-xs border rounded-2xl border-slate-100 bg-slate-50/60">
                                <p class="mb-2 text-[10px] font-black uppercase tracking-widest text-slate-400">ตรวจทานก่อนบันทึก</p>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">คอลเลกชัน</span
                                    ><span class="col-span-2 font-bold text-slate-700">{{ wizCollectionName }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">ชื่อเรื่อง</span
                                    ><span class="col-span-2 font-bold text-slate-700">{{ wizForm.title || '—' }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">ภาษา</span
                                    ><span class="col-span-2 font-bold text-slate-700">{{ wizForm.language === 'eng' ? 'อังกฤษ' : 'ไทย' }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">ผู้แต่ง</span
                                    ><span class="col-span-2 font-bold text-slate-700">{{
                                        [wizForm.creator, ...wizForm.contributors].filter(Boolean).join(', ') || '—'
                                    }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">ปี</span
                                    ><span class="col-span-2 font-bold text-slate-700">{{ wizForm.year_issued || '—' }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">หน่วยงาน</span
                                    ><span class="col-span-2 font-bold text-slate-700">{{ wizDepartmentName }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">หัวเรื่อง</span
                                    ><span class="col-span-2 font-bold text-slate-700">{{
                                        wizForm.subjects.filter(Boolean).join(' · ') || '—'
                                    }}</span>
                                </div>
                                <div class="grid grid-cols-3 gap-1">
                                    <span class="font-black text-slate-400">สถานะเมื่อบันทึก</span
                                    ><span class="col-span-2 font-black" :class="isAdmin ? 'text-green-600' : 'text-yellow-600'">{{
                                        isAdmin ? 'เผยแพร่ทันที (approved)' : 'รอตรวจสอบ (pending)'
                                    }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer nav -->
                        <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                            <button
                                type="button"
                                :disabled="wizStep === 1 || wizBusy"
                                @click="wizBack"
                                class="px-5 py-3 text-xs font-bold rounded-2xl text-slate-500 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-30"
                            >
                                <i class="fa-solid fa-chevron-left mr-1.5 text-[10px]"></i>ย้อนกลับ
                            </button>
                            <button
                                v-if="wizStep < wizTotalSteps"
                                type="button"
                                @click="wizNext"
                                class="rounded-2xl bg-[#1e3a8a] px-6 py-3 text-xs font-black text-white transition-all hover:bg-blue-800 active:scale-95"
                            >
                                ถัดไป<i class="fa-solid fa-chevron-right ml-1.5 text-[10px]"></i>
                            </button>
                            <button
                                v-else
                                type="button"
                                :disabled="wizBusy"
                                @click="wizSubmit"
                                class="rounded-2xl bg-[#1e3a8a] px-8 py-3 text-xs font-black text-white shadow-lg shadow-blue-900/10 transition-all hover:bg-blue-800 active:scale-95 disabled:opacity-40"
                            >
                                <i v-if="wizBusy" class="fa-solid fa-spinner fa-spin mr-1.5"></i>บันทึกรายการ
                            </button>
                        </div>
                    </template>
                </div>

                <!-- ========================================================== -->
                <!-- TAB 3: REVIEW QUEUE (Flow E — real data, role-aware) -->
                <!-- ========================================================== -->
                <div v-else-if="activeTab === 'approvals'" class="space-y-6 animate-fade-in">
                    <div class="rounded-[2.5rem] border border-slate-100 bg-white p-6 shadow-sm sm:p-8">
                        <h3 class="mb-1 text-lg font-black tracking-tight text-slate-900">
                            {{ isAdmin ? 'คิวตรวจสอบข้อมูล' : 'สถานะรายการของฉัน' }}
                        </h3>
                        <p class="mb-6 text-xs font-bold text-slate-400">
                            {{
                                isAdmin
                                    ? `รายการที่รอตรวจสอบหรือถูกส่งกลับให้แก้ไข (${props.queue.items.length} รายการ)`
                                    : 'รายการที่คุณนำเข้าทั้งหมด — ถ้าถูกส่งกลับ จะเห็นหมายเหตุให้แก้ไข'
                            }}
                        </p>

                        <div class="space-y-4">
                            <div
                                v-for="item in props.queue.items"
                                :key="item.id"
                                class="flex flex-col gap-4 p-6 border rounded-2xl border-slate-100 bg-slate-50/40 md:flex-row md:items-start md:justify-between"
                            >
                                <div class="min-w-0 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-black text-slate-400">#{{ item.id }}</span>
                                        <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-[10px] font-black text-blue-900">{{
                                            item.collection ?? '—'
                                        }}</span>
                                        <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-black', statusMeta[item.status].cls]">{{
                                            statusMeta[item.status].label
                                        }}</span>
                                    </div>
                                    <h4 class="text-sm font-bold leading-snug text-slate-800">{{ item.title }}</h4>
                                    <div class="flex flex-wrap items-center text-xs font-bold gap-x-3 gap-y-1 text-slate-500">
                                        <span><i class="fa-solid fa-user mr-1.5 text-slate-300"></i>{{ item.author ?? 'ไม่ระบุผู้แต่ง' }}</span>
                                        <span v-if="item.year"><i class="fa-regular fa-calendar mr-1.5 text-slate-300"></i>{{ item.year }}</span>
                                        <span v-if="isAdmin && item.owner"
                                            ><i class="fa-solid fa-paper-plane mr-1.5 text-slate-300"></i>{{ item.owner }}</span
                                        >
                                        <span v-if="item.submittedAt"
                                            ><i class="fa-regular fa-clock mr-1.5 text-slate-300"></i>{{ item.submittedAt }}</span
                                        >
                                    </div>
                                    <p
                                        v-if="item.status === 'action_required' && item.reviewNote"
                                        class="mt-1 rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-[11px] font-bold text-red-700"
                                    >
                                        <i class="mr-1 fa-solid fa-pen-to-square"></i>{{ item.reviewNote }}
                                    </p>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <template v-if="isAdmin">
                                        <button
                                            @click="handleQueueApprove(item)"
                                            class="flex items-center rounded-xl bg-green-600 px-4 py-2.5 text-xs font-black text-white transition-all hover:bg-green-700 active:scale-95"
                                        >
                                            <i class="fa-solid fa-check mr-1.5"></i>อนุมัติ
                                        </button>
                                        <button
                                            @click="handleQueueReturn(item)"
                                            class="flex items-center rounded-xl border border-red-200 bg-white px-4 py-2.5 text-xs font-black text-red-600 transition-all hover:bg-red-50 active:scale-95"
                                        >
                                            <i class="fa-solid fa-rotate-left mr-1.5"></i>ส่งกลับแก้ไข
                                        </button>
                                    </template>
                                    <Link
                                        v-else-if="item.status === 'action_required'"
                                        :href="route('admin.repository.items.edit', item.id)"
                                        class="flex items-center rounded-xl bg-[#1e3a8a] px-4 py-2.5 text-xs font-black text-white transition-all hover:bg-blue-800 active:scale-95"
                                    >
                                        <i class="fa-solid fa-pen mr-1.5"></i>แก้ไข
                                    </Link>
                                </div>
                            </div>

                            <div v-if="props.queue.items.length === 0" class="py-16 font-bold text-center text-slate-400">
                                <i class="mb-3 text-5xl text-green-500 fa-regular fa-circle-check"></i>
                                <p>{{ isAdmin ? 'ไม่มีรายการรอตรวจสอบในขณะนี้' : 'ยังไม่มีรายการที่คุณนำเข้า' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================== -->
                <!-- TAB 4: ANALYTICS (real aggregates — no view/download tracking in schema) -->
                <!-- ========================================================== -->
                <div v-else-if="activeTab === 'analytics'" class="space-y-8 animate-fade-in">
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">ทั้งหมด</p>
                            <p class="text-3xl font-black text-slate-900">{{ props.stats.total.toLocaleString() }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">ภาษาไทย</p>
                            <p class="text-3xl font-black text-[#1e3a8a]">{{ props.stats.byLanguage.tha.toLocaleString() }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">ภาษาอังกฤษ</p>
                            <p class="text-3xl font-black text-[#1e3a8a]">{{ props.stats.byLanguage.eng.toLocaleString() }}</p>
                        </div>
                        <div class="rounded-[2rem] border border-slate-100 bg-white p-6 shadow-sm">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">รอตรวจสอบ</p>
                            <p class="text-3xl font-black text-yellow-600">{{ props.stats.pending.toLocaleString() }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-8 md:grid-cols-2">
                        <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <h3 class="mb-6 text-lg font-black tracking-tight text-slate-900">จำนวนรายการแยกตามคอลเลกชัน</h3>
                            <div v-if="props.stats.byCollection.length" class="space-y-4">
                                <div v-for="row in props.stats.byCollection" :key="row.name">
                                    <div class="flex justify-between mb-1 text-xs font-bold text-slate-600">
                                        <span>{{ row.name }}</span
                                        ><span>{{ row.count.toLocaleString() }}</span>
                                    </div>
                                    <div class="w-full h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full bg-[#1e3a8a]"
                                            :style="{ width: `${(row.count / maxCollectionCount) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs font-bold text-slate-300">ยังไม่มีข้อมูล</p>
                        </div>

                        <div class="rounded-[2.5rem] border border-slate-100 bg-white p-8 shadow-sm">
                            <h3 class="mb-6 text-lg font-black tracking-tight text-slate-900">หน่วยงานที่มีรายการมากที่สุด</h3>
                            <div v-if="props.stats.topFaculties.length" class="space-y-4">
                                <div v-for="row in props.stats.topFaculties" :key="row.name">
                                    <div class="flex justify-between mb-1 text-xs font-bold text-slate-600">
                                        <span class="pr-2 truncate">{{ row.name }}</span
                                        ><span class="shrink-0">{{ row.count.toLocaleString() }}</span>
                                    </div>
                                    <div class="w-full h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full bg-[#facc15]"
                                            :style="{ width: `${(row.count / maxFacultyCount) * 100}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-xs font-bold text-slate-300">ยังไม่มีรายการที่ระบุหน่วยงาน</p>
                        </div>
                    </div>

                    <p class="text-[11px] font-bold text-slate-300">
                        * ยังไม่มีการเก็บสถิติยอดเข้าชม / ดาวน์โหลดในระบบ — ตัวเลขทั้งหมดคำนวณจากจำนวนรายการจริง
                    </p>
                </div>

                <!-- ========================================================== -->
                <!-- TAB 5: MEMBERS MANAGEMENT -->
                <!-- ========================================================== -->
                <div
                    v-else-if="activeTab === 'members'"
                    class="animate-fade-in space-y-6 rounded-[2.5rem] border border-slate-100 bg-white p-6 shadow-sm sm:p-8"
                >
                    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <!-- Search bar input -->
                        <div class="relative w-full max-w-md group">
                            <i
                                class="absolute transition-colors -translate-y-1/2 fa-solid fa-magnifying-glass left-4 top-1/2 text-slate-400 group-focus-within:text-blue-900"
                            ></i>
                            <input
                                type="text"
                                placeholder="ค้นหาสมาชิกด้วยชื่อ, อีเมล หรือคณะ..."
                                v-model="searchQuery"
                                class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3.5 pl-12 pr-4 text-sm font-medium outline-none transition-all focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>

                        <button
                            @click="isAddMemberModalOpen = true"
                            class="group flex shrink-0 items-center justify-center rounded-2xl bg-[#1e3a8a] px-5 py-3.5 text-xs font-black text-white shadow-lg shadow-blue-900/10 outline-none transition-all hover:bg-blue-800 hover:shadow-xl active:scale-95"
                        >
                            <i class="fa-solid fa-user-plus mr-1.5 text-[11px] transition-transform group-hover:rotate-6"></i>
                            <span>เพิ่มสมาชิก</span>
                        </button>
                    </div>

                    <!-- Members Table view representation -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-slate-100 bg-slate-50 text-[10px] font-black uppercase tracking-widest text-slate-500">
                                    <th class="px-6 py-5">รหัสสมาชิก</th>
                                    <th class="px-6 py-5">ชื่อ-นามสกุล</th>
                                    <th class="px-6 py-5">อีเมลติดต่อ</th>
                                    <th class="px-6 py-5">บทบาท</th>
                                    <th class="px-6 py-5">หน่วยงาน/คณะ</th>
                                    <th class="px-6 py-5">สถานะบัญชี</th>
                                    <th class="px-6 py-5 text-center">สวมสิทธิ์</th>
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
                                            <span
                                                :class="[
                                                    'rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider',
                                                    member.role_level === 3 ? 'bg-red-50 text-red-700' : 'bg-slate-100 text-slate-700',
                                                ]"
                                            >
                                                {{ member.role_level === 3 ? 'ผู้ดูแลระบบ' : 'สมาชิกทั่วไป' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs font-bold text-slate-500">{{ member.department_name || '-' }}</td>
                                        <td class="px-6 py-4">
                                            <button
                                                @click="handleToggleUserStatus(member)"
                                                :title="member.status === 'active' ? 'คลิกเพื่อระงับการใช้งาน' : 'คลิกเพื่อเปิดใช้งานปกติ'"
                                                :class="[
                                                    'inline-flex cursor-pointer items-center rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest outline-none transition-opacity hover:opacity-70',
                                                    member.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700',
                                                ]"
                                            >
                                                <span class="mr-1.5 h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ member.status === 'active' ? 'ใช้งานปกติ' : 'ถูกระงับสิทธิ์' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center">
                                                <button
                                                    @click="handleImpersonate(member)"
                                                    :disabled="member.id === currentUserId || member.role_level === 3 || member.status !== 'active'"
                                                    class="flex items-center justify-center w-8 h-8 text-blue-700 transition-all rounded-full bg-blue-50 hover:bg-blue-700 hover:text-white disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-blue-50 disabled:hover:text-blue-700"
                                                    title="สวมสิทธิ์เข้าสู่ระบบในฐานะสมาชิกคนนี้"
                                                >
                                                    <i class="text-xs fa-solid fa-user-secret"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button
                                                    @click="handleOpenEditMember(member)"
                                                    class="flex items-center justify-center w-8 h-8 transition-all rounded-full bg-slate-50 text-slate-400 hover:bg-blue-900 hover:text-white"
                                                    title="แก้ไขข้อมูลสมาชิก"
                                                >
                                                    <i class="text-xs fa-solid fa-pen"></i>
                                                </button>
                                                <button
                                                    @click="handleDeleteMember(member)"
                                                    :disabled="member.id === currentUserId"
                                                    class="flex items-center justify-center w-8 h-8 transition-all rounded-full bg-slate-50 text-slate-400 hover:bg-red-500 hover:text-white disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-slate-50 disabled:hover:text-slate-400"
                                                    title="ลบสมาชิก"
                                                >
                                                    <i class="text-xs fa-regular fa-trash-can"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <template v-else>
                                    <tr>
                                        <td colSpan="8" class="py-12 text-sm font-bold text-center text-slate-400">
                                            <i class="mb-3 text-4xl fa-solid fa-users text-slate-300"></i>
                                            <p>ไม่พบค้นหาข้อมูลสมาชิกที่ระบุ</p>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
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
                <div class="animate-slide-right absolute inset-y-0 left-0 flex w-72 flex-col justify-between bg-[#1e3a8a] p-6 text-white">
                    <div class="space-y-8">
                        <div class="flex items-center space-x-3">
                            <!-- ปรับสัญลักษณ์ M บนมือถือให้อยู่ตรงกลางเช่นกัน -->
                            <div class="flex h-10 w-10 select-none items-center justify-center rounded-xl bg-white text-lg font-black text-[#1e3a8a]">
                                M
                            </div>
                            <div>
                                <span class="block text-sm font-black leading-tight text-white uppercase">MSU IR Portal</span>
                                <span class="text-[9px] font-black uppercase tracking-wider text-yellow-400">Management</span>
                            </div>
                        </div>

                        <!-- Navigation Links in mobile context (Clean & Standard Menu) -->
                        <nav class="space-y-1">
                            <!-- หน้าแรกภาพรวม -->
                            <button
                                @click="
                                    activeTab = 'dashboard';
                                    isMobileMenuOpen = false;
                                "
                                :class="[
                                    'flex w-full items-center space-x-4 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all',
                                    activeTab === 'dashboard' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10',
                                ]"
                            >
                                <i class="text-sm fa-solid fa-chart-line"></i>
                                <span>แดชบอร์ด</span>
                            </button>

                            <!-- จัดการสมาชิก (admin only) -->
                            <button
                                v-if="isAdmin"
                                @click="
                                    activeTab = 'members';
                                    isMobileMenuOpen = false;
                                "
                                :class="[
                                    'flex w-full items-center space-x-4 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all',
                                    activeTab === 'members' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10',
                                ]"
                            >
                                <i class="text-sm fa-solid fa-user-group"></i>
                                <span>จัดการสมาชิก</span>
                            </button>

                            <!-- จัดการคลังข้อมูลหลัก -->
                            <button
                                @click="
                                    activeTab = 'repository';
                                    isMobileMenuOpen = false;
                                "
                                :class="[
                                    'flex w-full items-center space-x-4 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all',
                                    activeTab === 'repository' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10',
                                ]"
                            >
                                <i class="text-sm fa-solid fa-folder-open"></i>
                                <span>จัดการคลังข้อมูล</span>
                            </button>

                            <!-- เพิ่มรายการ -->
                            <button
                                @click="
                                    activeTab = 'contribute';
                                    isMobileMenuOpen = false;
                                "
                                :class="[
                                    'flex w-full items-center space-x-4 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all',
                                    activeTab === 'contribute' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10',
                                ]"
                            >
                                <i class="text-sm fa-solid fa-circle-plus"></i>
                                <span>เพิ่มรายการ</span>
                            </button>

                            <!-- นำเข้าข้อมูล .csv (admin only) -->
                            <button
                                v-if="isAdmin"
                                @click="
                                    activeTab = 'import';
                                    isMobileMenuOpen = false;
                                "
                                :class="[
                                    'flex w-full items-center space-x-4 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all',
                                    activeTab === 'import' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10',
                                ]"
                            >
                                <i class="text-sm fa-solid fa-file-csv"></i>
                                <span>นำเข้าข้อมูล .csv</span>
                            </button>

                            <!-- คิวตรวจสอบงานวิชาการ -->
                            <button
                                @click="
                                    activeTab = 'approvals';
                                    isMobileMenuOpen = false;
                                "
                                :class="[
                                    'flex w-full items-center space-x-4 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all',
                                    activeTab === 'approvals' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10',
                                ]"
                            >
                                <i class="text-sm fa-solid fa-clipboard-check"></i>
                                <span>คิวตรวจสอบข้อมูล</span>
                            </button>

                            <!-- รายงานและการสืบค้นสถิติ -->
                            <button
                                @click="
                                    activeTab = 'analytics';
                                    isMobileMenuOpen = false;
                                "
                                :class="[
                                    'flex w-full items-center space-x-4 rounded-xl px-4 py-3 text-xs font-bold outline-none transition-all',
                                    activeTab === 'analytics' ? 'bg-[#facc15] text-[#1e3a8a]' : 'text-blue-100 hover:bg-white/10',
                                ]"
                            >
                                <i class="text-sm fa-solid fa-chart-bar"></i>
                                <span>สถิติและการดาวน์โหลด</span>
                            </button>
                        </nav>
                    </div>

                    <button
                        @click="logout"
                        class="flex items-center justify-center w-full py-3 space-x-2 text-xs font-black tracking-wider text-red-300 uppercase rounded-xl bg-red-500/20"
                    >
                        <i class="text-xs fa-solid fa-right-from-bracket"></i>
                        <span>ออกจากระบบ</span>
                    </button>
                </div>
            </div>
        </transition>

        <!-- --- ADD MEMBER MODAL (FADE & SCALE) --- -->
        <transition name="modal-fade">
            <div v-if="isAddMemberModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Blur Backdrop -->
                <div @click="isAddMemberModalOpen = false" class="absolute inset-0 bg-slate-950/40 backdrop-blur-md"></div>

                <!-- Content card -->
                <div class="relative w-full max-w-lg rounded-[2.5rem] border border-slate-100 bg-white shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)]">
                    <div
                        class="relative overflow-hidden rounded-t-[2.5rem] bg-gradient-to-br from-[#1e3a8a] via-[#1e40af] to-blue-700 p-8 text-white"
                    >
                        <div class="absolute w-32 h-32 rounded-full -right-8 -top-8 bg-white/5 blur-2xl"></div>
                        <h3 class="text-2xl font-black tracking-tight">เพิ่มสมาชิกใหม่</h3>
                        <p class="mt-1 text-xs text-blue-100/80">กรอกข้อมูลเพื่อสร้างบัญชีสมาชิกใหม่เข้าสู่ระบบ</p>

                        <button
                            @click="isAddMemberModalOpen = false"
                            class="absolute flex items-center justify-center w-10 h-10 text-white transition-all rounded-full outline-none right-6 top-6 bg-white/10 hover:rotate-90 hover:bg-white/20"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Form inputs container -->
                    <form @submit.prevent="handleCreateMember" class="space-y-6 rounded-b-[2.5rem] bg-white p-8">
                        <div>
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">สถานะสมาชิก</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="newMember.is_msu_member = true"
                                    :class="[
                                        'rounded-2xl border-2 py-3.5 text-xs font-black transition-all',
                                        newMember.is_msu_member
                                            ? 'border-blue-900 bg-blue-50 text-blue-900'
                                            : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300',
                                    ]"
                                >
                                    สมาชิก มมส. (@msu.ac.th)
                                </button>
                                <button
                                    type="button"
                                    @click="newMember.is_msu_member = false"
                                    :class="[
                                        'rounded-2xl border-2 py-3.5 text-xs font-black transition-all',
                                        !newMember.is_msu_member
                                            ? 'border-blue-900 bg-blue-50 text-blue-900'
                                            : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300',
                                    ]"
                                >
                                    บุคคลภายนอก
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">บทบาท</label>
                                <ModernSelect
                                    v-model="newMember.role_level"
                                    :options="[
                                        { value: 1, label: 'สมาชิกทั่วไป' },
                                        { value: 3, label: 'ผู้ดูแลระบบ' },
                                    ]"
                                />
                            </div>
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">หน่วยงาน/คณะ</label>
                                <ModernSelect
                                    v-model="newMember.department_id"
                                    :options="[
                                        { value: null, label: 'ไม่ระบุ' },
                                        ...props.departments.map((dep) => ({ value: dep.id, label: dep.name })),
                                    ]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">ชื่อ-นามสกุล</label>
                            <input
                                type="text"
                                placeholder="เช่น ดร.สมศักดิ์ รักเรียน"
                                v-model="newMember.name"
                                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>

                        <div>
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">อีเมล</label>
                            <div v-if="newMember.is_msu_member" class="flex items-stretch">
                                <input
                                    type="text"
                                    placeholder="ชื่อผู้ใช้"
                                    v-model="newMember.email_local"
                                    class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-l-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                                />
                                <span
                                    class="flex items-center px-4 text-sm font-bold border border-l-0 whitespace-nowrap rounded-r-2xl border-slate-200 bg-slate-100 text-slate-500"
                                    >@msu.ac.th</span
                                >
                            </div>
                            <input
                                v-else
                                type="email"
                                placeholder="name@example.com"
                                v-model="newMember.email"
                                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>

                        <div v-if="!newMember.is_msu_member">
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">รหัสผ่านเริ่มต้น</label>
                            <input
                                type="password"
                                placeholder="กำหนดรหัสผ่านเริ่มต้นสำหรับสมาชิก"
                                v-model="newMember.password"
                                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>
                        <p v-else class="-mt-2 text-[11px] font-bold leading-relaxed text-slate-400">
                            สมาชิก มมส. เข้าสู่ระบบด้วยบัญชี Google (@msu.ac.th) จึงไม่ต้องตั้งรหัสผ่าน
                        </p>

                        <!-- Footer actions -->
                        <div class="flex pt-4 space-x-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isAddMemberModalOpen = false"
                                class="flex-1 py-4 text-xs font-bold transition-colors rounded-2xl text-slate-500 hover:bg-slate-50"
                            >
                                ยกเลิก
                            </button>
                            <button
                                type="submit"
                                class="flex-1 rounded-2xl bg-[#1e3a8a] py-4 text-xs font-black text-white shadow-lg shadow-blue-900/20 transition-all hover:bg-blue-800 active:scale-95"
                            >
                                บันทึกสมาชิกใหม่
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>

        <!-- --- EDIT MEMBER MODAL (FADE & SCALE) --- -->
        <transition name="modal-fade">
            <div v-if="isEditMemberModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <!-- Blur Backdrop -->
                <div @click="isEditMemberModalOpen = false" class="absolute inset-0 bg-slate-950/40 backdrop-blur-md"></div>

                <!-- Content card -->
                <div class="relative w-full max-w-lg rounded-[2.5rem] border border-slate-100 bg-white shadow-[0_25px_60px_-15px_rgba(0,0,0,0.3)]">
                    <div
                        class="relative overflow-hidden rounded-t-[2.5rem] bg-gradient-to-br from-[#1e3a8a] via-[#1e40af] to-blue-700 p-8 text-white"
                    >
                        <div class="absolute w-32 h-32 rounded-full -right-8 -top-8 bg-white/5 blur-2xl"></div>
                        <h3 class="text-2xl font-black tracking-tight">แก้ไขข้อมูลสมาชิก</h3>
                        <p class="mt-1 text-xs text-blue-100/80">ปรับปรุงข้อมูลบัญชีสมาชิก #{{ editMember.id }}</p>

                        <button
                            @click="isEditMemberModalOpen = false"
                            class="absolute flex items-center justify-center w-10 h-10 text-white transition-all rounded-full outline-none right-6 top-6 bg-white/10 hover:rotate-90 hover:bg-white/20"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Form inputs container -->
                    <form @submit.prevent="handleUpdateMember" class="space-y-6 rounded-b-[2.5rem] bg-white p-8">
                        <div>
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">สถานะสมาชิก</label>
                            <div class="grid grid-cols-2 gap-3">
                                <button
                                    type="button"
                                    @click="editMember.is_msu_member = true"
                                    :class="[
                                        'rounded-2xl border-2 py-3.5 text-xs font-black transition-all',
                                        editMember.is_msu_member
                                            ? 'border-blue-900 bg-blue-50 text-blue-900'
                                            : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300',
                                    ]"
                                >
                                    สมาชิก มมส. (@msu.ac.th)
                                </button>
                                <button
                                    type="button"
                                    @click="editMember.is_msu_member = false"
                                    :class="[
                                        'rounded-2xl border-2 py-3.5 text-xs font-black transition-all',
                                        !editMember.is_msu_member
                                            ? 'border-blue-900 bg-blue-50 text-blue-900'
                                            : 'border-slate-200 bg-slate-50 text-slate-400 hover:border-slate-300',
                                    ]"
                                >
                                    บุคคลภายนอก
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">บทบาท</label>
                                <ModernSelect
                                    v-model="editMember.role_level"
                                    :options="[
                                        { value: 1, label: 'สมาชิกทั่วไป' },
                                        { value: 3, label: 'ผู้ดูแลระบบ' },
                                    ]"
                                />
                            </div>
                            <div>
                                <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">หน่วยงาน/คณะ</label>
                                <ModernSelect
                                    v-model="editMember.department_id"
                                    :options="[
                                        { value: null, label: 'ไม่ระบุ' },
                                        ...props.departments.map((dep) => ({ value: dep.id, label: dep.name })),
                                    ]"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">ชื่อ-นามสกุล</label>
                            <input
                                type="text"
                                placeholder="เช่น ดร.สมศักดิ์ รักเรียน"
                                v-model="editMember.name"
                                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>

                        <div>
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">อีเมล</label>
                            <div v-if="editMember.is_msu_member" class="flex items-stretch">
                                <input
                                    type="text"
                                    placeholder="ชื่อผู้ใช้"
                                    v-model="editMember.email_local"
                                    class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-l-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                                />
                                <span
                                    class="flex items-center px-4 text-sm font-bold border border-l-0 whitespace-nowrap rounded-r-2xl border-slate-200 bg-slate-100 text-slate-500"
                                    >@msu.ac.th</span
                                >
                            </div>
                            <input
                                v-else
                                type="email"
                                placeholder="name@example.com"
                                v-model="editMember.email"
                                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>

                        <div v-if="!editMember.is_msu_member">
                            <label class="block mb-2 text-xs font-black tracking-widest uppercase text-slate-400">รหัสผ่านใหม่</label>
                            <input
                                type="password"
                                placeholder="เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน"
                                v-model="editMember.password"
                                class="w-full px-5 py-4 text-sm font-bold tracking-tight transition-all border outline-none rounded-2xl border-slate-200 bg-slate-50 placeholder:text-slate-300 focus:border-blue-900 focus:bg-white focus:ring-4 focus:ring-blue-900/5"
                            />
                        </div>

                        <!-- Footer actions -->
                        <div class="flex pt-4 space-x-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="isEditMemberModalOpen = false"
                                class="flex-1 py-4 text-xs font-bold transition-colors rounded-2xl text-slate-500 hover:bg-slate-50"
                            >
                                ยกเลิก
                            </button>
                            <button
                                type="submit"
                                class="flex-1 rounded-2xl bg-[#1e3a8a] py-4 text-xs font-black text-white shadow-lg shadow-blue-900/20 transition-all hover:bg-blue-800 active:scale-95"
                            >
                                บันทึกการแก้ไข
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>

        <!-- --- TOAST NOTIFICATIONS --- -->
        <transition name="toast-slide">
            <div
                v-if="toast.show"
                class="fixed bottom-8 right-8 z-[120] flex max-w-sm items-center space-x-4 rounded-[1.5rem] border border-slate-800 bg-slate-900 p-5 text-white shadow-2xl"
            >
                <div
                    :class="[
                        'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl',
                        toast.type === 'success'
                            ? 'bg-green-500 text-white'
                            : toast.type === 'warning'
                              ? 'bg-yellow-500 text-black'
                              : 'bg-red-500 text-white',
                    ]"
                >
                    <i class="text-sm font-bold fa-solid fa-check"></i>
                </div>
                <div>
                    <p class="text-xs font-bold tracking-wider uppercase text-slate-300">ระบบหลังบ้านแจ้งเตือน</p>
                    <p class="mt-0.5 text-xs font-black leading-relaxed text-white">{{ toast.message }}</p>
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
