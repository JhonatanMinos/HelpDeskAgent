import { expect, test } from 'bun:test';
import { Children, isValidElement, type ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import AlertError from '../../resources/js/components/alert-error';
import { FieldError } from '../../resources/js/components/ui/field';

function listItems(node: ReactNode): Array<{ key: string | null; text: ReactNode }> {
	if (!isValidElement<{ children?: ReactNode }>(node)) return [];
	if (node.type === 'li') return [{ key: node.key, text: node.props.children }];
	return Children.toArray(node.props.children).flatMap(listItems);
}

test('deduplicated alert errors keep their identity when reordered', () => {
	const before = listItems(AlertError({ errors: ['First', 'Second', 'First'] }));
	const after = listItems(AlertError({ errors: ['Second', 'First'] }));
	expect(before.map((item) => item.text)).toEqual(['First', 'Second']);
	expect(Object.fromEntries(before.map(({ text, key }) => [text, key]))).toEqual(
		Object.fromEntries(after.map(({ text, key }) => [text, key]))
	);
});

test('field errors retain deduplicated messages and their order', () => {
	const html = renderToStaticMarkup(
		<FieldError errors={[{ message: 'First' }, { message: 'Second' }, { message: 'First' }]} />
	);
	expect(html).toContain('<li>First</li><li>Second</li>');
});
