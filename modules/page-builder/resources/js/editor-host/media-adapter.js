export const loadMediaAssets = async ({ token, endpoint, query = '' }) => {
    const normalizedToken = String(token ?? '').trim();
    const normalizedEndpoint = String(endpoint ?? '').trim();

    if (normalizedToken === '' || normalizedEndpoint === '') {
        return {
            ok: false,
            message: 'Missing media token or endpoint.',
            assets: [],
        };
    }

    try {
        const response = await fetch(normalizedEndpoint, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                token: normalizedToken,
                q: String(query ?? '').trim(),
                per_page: 24,
            }),
        });

        if (!response.ok) {
            return {
                ok: false,
                message: 'Unable to load media assets.',
                assets: [],
            };
        }

        const payload = await response.json();

        if (payload?.success !== true) {
            return {
                ok: false,
                message: 'Unable to load media assets.',
                assets: [],
            };
        }

        return {
            ok: true,
            assets: Array.isArray(payload?.data) ? payload.data : [],
            meta: payload?.meta ?? {},
        };
    } catch (_) {
        return {
            ok: false,
            message: 'Unable to load media assets.',
            assets: [],
        };
    }
};

export const uploadMediaAsset = async ({ token, endpoint, file }) => {
    const normalizedToken = String(token ?? '').trim();
    const normalizedEndpoint = String(endpoint ?? '').trim();

    if (normalizedToken === '' || normalizedEndpoint === '' || !(file instanceof File)) {
        return {
            ok: false,
            message: 'Missing upload token, endpoint, or file.',
        };
    }

    const formData = new FormData();
    formData.append('token', normalizedToken);
    formData.append('file', file);

    try {
        const response = await fetch(normalizedEndpoint, {
            method: 'POST',
            body: formData,
        });

        if (!response.ok) {
            return {
                ok: false,
                message: 'Upload failed.',
            };
        }

        const payload = await response.json();

        if (payload?.success !== true) {
            return {
                ok: false,
                message: 'Upload failed.',
            };
        }

        return {
            ok: true,
            asset: payload?.data ?? null,
        };
    } catch (_) {
        return {
            ok: false,
            message: 'Upload failed.',
        };
    }
};
