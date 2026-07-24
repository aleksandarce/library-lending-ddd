import { error } from '@sveltejs/kit';
import { request } from '$lib/api.js';

export async function load({ params, fetch }) {
	try {
		const member = await request(`/members/${params.id}`, { fetch });
		return { member };
	} catch (e) {
		// In SvelteKit 2, error() throws internally — nothing runs after it.
		error(e.status === 404 ? 404 : 502, e.message ?? 'Could not load member');
	}
}
