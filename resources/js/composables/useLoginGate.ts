import { ref } from 'vue';
import { useAlert } from '@/utils/alert';

/**
 * Shared login-gate state. `provide/inject` can't be used here because the public
 * pages wrap `<PublicLayout>` inside their own template (the layout is a child of
 * the page, not an ancestor), so a module-level singleton is the reliable bridge.
 *
 * `PublicLayout` binds its LoginModal to `modalOpen`; any page can call
 * `requireLogin()` to show a message and pop the modal.
 */
const modalOpen = ref(false);

export function useLoginGate() {
    const { toast } = useAlert();

    const requireLogin = (message = 'ขออภัย คุณจำเป็นต้องเข้าสู่ระบบก่อน') => {
        toast(message, 'warning');
        modalOpen.value = true;
    };

    return { modalOpen, requireLogin };
}
