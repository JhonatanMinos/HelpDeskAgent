import { Link } from '@inertiajs/react';
import { Building2, LifeBuoy } from 'lucide-react';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
	return (
		<div className="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
			<aside className="relative hidden flex-col justify-between overflow-hidden bg-slate-950 p-10 text-slate-100 lg:flex xl:p-14">
				<div
					aria-hidden="true"
					className="pointer-events-none absolute -left-32 top-1/3 size-96 rounded-full bg-emerald-500/15 blur-3xl"
				/>
				<Link
					href={home()}
					className="relative flex w-fit items-center gap-3 text-lg font-semibold"
				>
					<span className="grid size-10 place-items-center rounded-xl bg-emerald-400/15 text-emerald-300">
						<LifeBuoy className="size-6" />
					</span>
					HelpDeskAgent
				</Link>
				<div className="relative max-w-md py-16">
					<Building2 className="mb-6 size-9 text-emerald-300" />
					<h2 className="text-4xl font-semibold leading-tight tracking-tight">
						Your operations.
						<br />
						One focused workspace.
					</h2>
					<p className="mt-5 text-base leading-relaxed text-slate-400">
						Branch connectivity, support requests and team projects. Keep the work that matters
						within reach.
					</p>
				</div>
				<p className="relative text-xs text-slate-500">Branch operations & team support</p>
			</aside>
			<main className="flex min-w-0 items-center justify-center px-6 py-10 sm:px-10 lg:py-14">
				<div className="w-full max-w-sm">
					<Link
						href={home()}
						className="mb-10 flex w-fit items-center gap-3 font-semibold lg:hidden"
					>
						<span className="grid size-9 place-items-center rounded-xl bg-primary text-primary-foreground">
							<LifeBuoy className="size-5" />
						</span>
						HelpDeskAgent
					</Link>
					<div className="mb-8 space-y-3">
						<p className="text-xs font-semibold uppercase tracking-widest text-primary">
							Your account
						</p>
						<h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
						<p className="text-sm leading-relaxed text-muted-foreground">{description}</p>
					</div>
					{children}
				</div>
			</main>
		</div>
	);
}
