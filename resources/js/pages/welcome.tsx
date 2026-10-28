import type { InertiaLinkProps } from '@inertiajs/react';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, Building2, FolderKanban, LifeBuoy, Network, Ticket } from 'lucide-react';
import { dashboard, login, register } from '@/routes';

const capabilities = [
	{
		title: 'Branches',
		description: 'Review connection states and inspect your branch network.',
		icon: Building2,
	},
	{
		title: 'Tickets',
		description: 'Organize support requests and keep their follow-up in one place.',
		icon: Ticket,
	},
	{
		title: 'Projects',
		description: 'Coordinate work with your team through project boards.',
		icon: FolderKanban,
	},
];

export function WelcomeContent({
	authenticated,
	dashboardUrl,
}: {
	authenticated: boolean;
	dashboardUrl: NonNullable<InertiaLinkProps['href']>;
}) {
	return (
		<div className="flex min-h-svh flex-col bg-background text-foreground">
			<header className="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-6 lg:px-10">
				<a
					href="/"
					className="flex items-center gap-3 font-semibold tracking-tight"
					aria-label="HelpDeskAgent home"
				>
					<span className="grid size-9 place-items-center rounded-xl bg-primary text-primary-foreground">
						<LifeBuoy className="size-5" />
					</span>
					HelpDeskAgent
				</a>
				<nav aria-label="Account" className="flex items-center gap-3 text-sm font-medium">
					{authenticated ? (
						<Link
							href={dashboardUrl}
							className="rounded-lg bg-primary px-4 py-2.5 text-primary-foreground transition hover:bg-primary/90"
						>
							Dashboard
							<ArrowRight className="ml-2 inline size-4" />
						</Link>
					) : (
						<>
							<Link href={login()} className="rounded-lg px-3 py-2.5 transition hover:bg-muted">
								Log in
							</Link>
							<Link
								href={register()}
								className="rounded-lg bg-primary px-4 py-2.5 text-primary-foreground transition hover:bg-primary/90"
							>
								Register
							</Link>
						</>
					)}
				</nav>
			</header>

			<main className="mx-auto flex w-full max-w-7xl flex-1 flex-col justify-center gap-12 px-6 py-10 lg:px-10 lg:py-16">
				<section className="grid items-center gap-10 lg:grid-cols-[1.15fr_1fr]">
					<div className="max-w-2xl">
						<p className="mb-5 inline-flex items-center gap-2 rounded-full border bg-card px-3 py-1.5 text-xs font-medium text-muted-foreground">
							<Network className="size-3.5 text-primary" />
							Your operational workspace
						</p>
						<h1 className="text-4xl font-semibold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
							Your branches.
							<br />
							<span className="text-primary">A clearer picture.</span>
						</h1>
						<p className="mt-6 max-w-lg text-base leading-relaxed text-muted-foreground">
							Bring branch connectivity, support tickets and team projects into one focused
							workspace.
						</p>
						<div className="mt-8 flex flex-wrap items-center gap-4">
							<Link
								href={authenticated ? dashboardUrl : login()}
								className="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-semibold text-primary-foreground shadow-sm transition hover:bg-primary/90"
							>
								{authenticated ? 'Open your dashboard' : 'Access your workspace'}
								<ArrowRight className="size-4" />
							</Link>
							<p className="text-xs text-muted-foreground">Built around your team’s daily work.</p>
						</div>
					</div>

					<div className="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-950 p-7 text-slate-100 sm:p-9">
						<div
							aria-hidden="true"
							className="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-emerald-500/15 blur-3xl"
						/>
						<p className="relative mb-6 text-xs font-medium uppercase tracking-widest text-slate-400">
							From overview to action
						</p>
						<div className="relative space-y-5">
							{capabilities.map(({ title, description, icon: Icon }, index) => (
								<div
									key={title}
									className="flex items-start gap-4 rounded-2xl border border-white/10 bg-white/5 p-4"
								>
									<div className="grid size-10 shrink-0 place-items-center rounded-xl bg-emerald-400/10 text-emerald-300">
										<Icon className="size-5" />
									</div>
									<div>
										<p className="mb-1 flex items-center gap-2 text-sm font-semibold">
											<span className="text-xs font-normal text-slate-500">0{index + 1}</span>
											{title}
										</p>
										<p className="text-sm leading-relaxed text-slate-400">{description}</p>
									</div>
								</div>
							))}
						</div>
					</div>
				</section>
				<div className="flex flex-wrap items-center justify-between gap-3 border-t pt-6 text-xs text-muted-foreground">
					<p>HelpDeskAgent · Branch operations & team support</p>
					<p>One workspace. Clearer priorities.</p>
				</div>
			</main>
		</div>
	);
}

export default function Welcome() {
	const { auth, currentTeam } = usePage().props;
	const dashboardUrl = currentTeam ? dashboard(currentTeam.slug) : '/';
	return (
		<>
			<Head title="HelpDeskAgent" />
			<WelcomeContent authenticated={Boolean(auth.user)} dashboardUrl={dashboardUrl} />
		</>
	);
}
