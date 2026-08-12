import { Toaster } from '@/components/ui/sonner';
import { useFlashToast } from '@/hooks/use-flash-toast';

export default function FlashToaster() {
	useFlashToast();

	return <Toaster position="bottom-right" richColors closeButton />;
}
