import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
	plugins: [
		{
			name: 'strip-odometer-ie-hacks',
			enforce: 'pre',
			transform(source, id) {
				const normalizedId = id.split('?')[0].replaceAll('\\', '/');

				if (!normalizedId.endsWith('/node_modules/odometer/themes/odometer-theme-default.css')) {
					return null;
				}

				return source.replace(/^\s*\*(?:vertical-align|zoom|display)\s*:[^;]+;\s*$/gm, '');
			},
		},
		laravel({
			input: ['resources/css/app.css', 'resources/js/app.tsx'],
			refresh: true,
			fonts: [
				bunny('Instrument Sans', {
					weights: [400, 500, 600],
				}),
			],
		}),
		inertia(),
		react({
			babel: {
				plugins: ['babel-plugin-react-compiler'],
			},
		}),
		tailwindcss(),
		wayfinder({
			formVariants: true,
		}),
	],
});
