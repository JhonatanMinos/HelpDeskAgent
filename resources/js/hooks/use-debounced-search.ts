import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

/**
 * Encapsula el patrón de búsqueda server-side con debounce que usamos en
 * cualquier tabla paginada por Inertia: mantiene el input controlado
 * localmente y solo dispara la visita GET después de que el usuario deja
 * de teclear, preservando el estado de la página actual.
 */
export function useDebouncedSearch(
	routeUrl: string,
	initialValue: string,
	only: string[],
	delayMs = 350
) {
	const [search, setSearch] = useState(initialValue);
	const isFirstRender = useRef(true);
	const onlyRef = useRef(only);

	useEffect(() => {
		onlyRef.current = only;
	}, [only]);

	useEffect(() => {
		if (isFirstRender.current) {
			isFirstRender.current = false;
			return;
		}

		const timeout = setTimeout(() => {
			router.get(
				routeUrl,
				{ search: search || undefined },
				{
					preserveState: true,
					preserveScroll: true,
					replace: true,
					only: onlyRef.current,
				}
			);
		}, delayMs);

		return () => clearTimeout(timeout);
	}, [search, routeUrl, delayMs]);

	return [search, setSearch] as const;
}
