import Swal from 'sweetalert2';

export const useAlert = () => {
    const toast = (title: string, icon: 'success' | 'error' | 'warning' | 'info' = 'success') => {
        Swal.fire({
            title,
            icon,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    };

    const confirm = async (title: string, text: string) => {
        return Swal.fire({
            title,
            text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#1e3a8a', // สีน้ำเงิน MSU ของคุณ
            cancelButtonColor: '#64748b',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true
        });
    };

    return { toast, confirm };
};