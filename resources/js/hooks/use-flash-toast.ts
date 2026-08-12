import { usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';

export function useFlashToast(): void {
    const { props } = usePage<{
        flash?: {
            success?: string;
            error?: string;
            warning?: string;
            info?: string;
        };
    }>();

    const sharedFlash = props.flash;

    useEffect(() => {
        if (sharedFlash?.success) {
            toast.success(sharedFlash.success);
        }

        if (sharedFlash?.info) {
            toast.info(sharedFlash.info);
        }

        if (sharedFlash?.warning) {
            toast.warning(sharedFlash.warning);
        }

        if (sharedFlash?.error) {
            toast.error(sharedFlash.error);
        }
    }, [sharedFlash]);
}
