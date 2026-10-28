export default function Heading({
	title,
	description,
	variant = 'default',
}: {
	title: string;
	description?: string;
	variant?: 'default' | 'small';
}) {
	return (
		<header className={variant === 'small' ? 'space-y-1' : 'mb-8 space-y-2'}>
			<h2
				className={
					variant === 'small'
						? 'text-base font-semibold tracking-tight'
						: 'text-2xl font-semibold tracking-tight md:text-3xl'
				}
			>
				{title}
			</h2>
			{description && (
				<p className="max-w-2xl text-sm leading-relaxed text-muted-foreground">{description}</p>
			)}
		</header>
	);
}
