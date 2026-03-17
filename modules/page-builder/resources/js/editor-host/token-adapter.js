export const verifyEditorToken = async ({ token, verifyUrl }) => {
    const normalizedToken = String(token ?? '').trim();
    const normalizedVerifyUrl = String(verifyUrl ?? '').trim();

    if (normalizedToken === '' || normalizedVerifyUrl === '') {
        return {
            ok: false,
            message: 'Missing access token or verify endpoint for editor host.',
        };
    }

    try {
        const response = await fetch(normalizedVerifyUrl, {
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
                message: 'Editor access token is invalid or expired.',
            };
        }

        const payload = await response.json();

        if (payload?.success !== true || payload?.data?.valid !== true) {
            return {
                ok: false,
                message: 'Editor access token is invalid or expired.',
            };
        }

        return {
            ok: true,
            claims: payload?.data?.claims ?? {},
        };
    } catch (_) {
        return {
            ok: false,
            message: 'Editor access token is invalid or expired.',
        };
    }
};
