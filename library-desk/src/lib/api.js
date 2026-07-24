const BASE = 'http://library-api.test/api';

export class ApiError extends Error {
	constructor(status, code, message) {
		super(message);
		this.name = 'ApiError';
		this.status = status;
		this.code = code;
	}
}

export async function request(path, options = {}) {
	const { method = 'GET', body, fetch: fetcher = globalThis.fetch } = options;

	const response = await fetcher(BASE + path, {
		method,
		headers: {
			'Content-Type': 'application/json',
			Accept: 'application/json'
		},
		body: body === undefined ? undefined : JSON.stringify(body)
	});

	const text = await response.text();
	const payload = text === '' ? null : JSON.parse(text);

	if (!response.ok) {
		throw new ApiError(
			response.status,
			payload?.error ?? 'unexpected_error',
			payload?.message ?? `Request failed with status ${response.status}`
		);
	}

	return payload;
}
