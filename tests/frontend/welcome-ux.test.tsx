import { expect, test } from 'bun:test';
import { renderToStaticMarkup } from 'react-dom/server';
import { WelcomeContent } from '../../resources/js/pages/welcome';

test('welcome presents the real product and preserves guest authentication routes', () => {
	const html = renderToStaticMarkup(<WelcomeContent authenticated={false} dashboardUrl="/" />);
	for (const value of [
		'HelpDeskAgent',
		'Branches',
		'Tickets',
		'Projects',
		'href="/login"',
		'href="/register"',
	]) {
		expect(html).toContain(value);
	}
	expect(html).not.toContain('laravel.com');
	expect(html).not.toContain('laracasts.com');
});

test('welcome retains authenticated team dashboard destination without guest actions', () => {
	const html = renderToStaticMarkup(
		<WelcomeContent authenticated dashboardUrl="/example-team/dashboard" />
	);
	expect(html).toContain('href="/example-team/dashboard"');
	expect(html).not.toContain('href="/login"');
	expect(html).not.toContain('href="/register"');
});
