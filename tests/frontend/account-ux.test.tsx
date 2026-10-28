import { expect, test } from 'bun:test';
import { readFileSync } from 'node:fs';
import { renderToStaticMarkup } from 'react-dom/server';
import AuthSimpleLayout from '../../resources/js/layouts/auth/auth-simple-layout';

test('auth layout has a branded home link without altering the supplied form', () => {
	const html = renderToStaticMarkup(
		<AuthSimpleLayout title="Sign in" description="Access your account">
			<form>
				<input name="email" />
				<button type="submit">Log in</button>
			</form>
		</AuthSimpleLayout>
	);
	for (const value of [
		'HelpDeskAgent',
		'href="/"',
		'<h1',
		'Sign in',
		'Access your account',
		'name="email"',
		'type="submit"',
	]) {
		expect(html).toContain(value);
	}
});

test('account navigation declares active location and preserves existing destinations', () => {
	const source = readFileSync(
		new URL('../../resources/js/layouts/settings/layout.tsx', import.meta.url),
		'utf8'
	);
	expect(source).toContain('aria-current=');
	for (const value of [
		'@/routes/profile',
		'@/routes/security',
		'@/routes/teams',
		'@/routes/appearance',
		'isCurrentOrParentUrl',
	])
		expect(source).toContain(value);
});
