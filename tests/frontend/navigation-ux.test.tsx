import { expect, test } from 'bun:test';
import {
	isWorkspaceSectionActive,
	workspaceNavigation,
} from '../../resources/js/lib/workspace-navigation';

test('workspace navigation and command search use real team-scoped destinations', () => {
	expect(workspaceNavigation('operations').map((item) => item.href)).toEqual([
		'/operations/dashboard',
		'/operations/branches',
		'/operations/tickets',
		'/operations/dev/projects',
	]);
	expect(workspaceNavigation(null).every((item) => item.href === '/')).toBe(true);
});

test('section highlighting follows nested pages without matching another section prefix', () => {
	expect(
		isWorkspaceSectionActive('/operations/branches/4?tab=network', '/operations/branches')
	).toBe(true);
	expect(
		isWorkspaceSectionActive('/operations/branches?search=central', '/operations/branches')
	).toBe(true);
	expect(isWorkspaceSectionActive('/operations/branches-other', '/operations/branches')).toBe(
		false
	);
	expect(isWorkspaceSectionActive('/operations/dashboard', '/')).toBe(false);
	expect(isWorkspaceSectionActive('/', '/')).toBe(true);
});
