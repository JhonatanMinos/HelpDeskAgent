import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
	return (
		<svg
			{...props}
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="1.8"
			strokeLinecap="round"
			strokeLinejoin="round"
			aria-hidden="true"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path d="M4 13v-2a8 8 0 0 1 16 0v5a4 4 0 0 1-4 4h-3" />
			<rect x="3" y="10" width="4" height="7" rx="2" />
			<rect x="17" y="10" width="4" height="7" rx="2" />
			<path d="M10 20h3" />
		</svg>
	);
}
