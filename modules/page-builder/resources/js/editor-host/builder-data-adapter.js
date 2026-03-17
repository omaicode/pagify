import { normalizeLayout } from './layout-state-adapter';

export const loadBuilderData = async ({ token, endpoint }) => {
    const normalizedToken = String(token ?? '').trim();
    const normalizedEndpoint = String(endpoint ?? '').trim();

    if (normalizedToken === '' || normalizedEndpoint === '') {
        return {
            ok: false,
            message: 'Missing builder-data token or endpoint.',
        };
    }

    try {
        const response = await fetch(normalizedEndpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ token: normalizedToken }),
        });

        if (!response.ok) {
            return {
                ok: false,
                message: 'Unable to load builder data.',
            };
        }

        const payload = await response.json();

        if (payload?.success !== true) {
            return {
                ok: false,
                message: 'Unable to load builder data.',
            };
        }

        return {
            ok: true,
            data: payload?.data ?? {},
            layout: normalizeLayout(payload?.data?.layout ?? {}),
        };
    } catch (_) {
        return {
            ok: false,
            message: 'Unable to load builder data.',
        };
    }
};
