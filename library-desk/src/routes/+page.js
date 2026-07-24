import { request } from '$lib/api.js';

export async function load({ fetch }) {
	const { members } = await request('/members', { fetch });
	return { members };
}
