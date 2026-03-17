export const createOriginGuard = (namespace, allowedParentOrigin) => {
    const normalizedConfiguredOrigin = String(allowedParentOrigin ?? '').trim();

    let referrerOrigin = '';

    try {
        const parsed = new URL(String(document.referrer ?? ''));
        referrerOrigin = parsed.origin;
    } catch (_) {
        referrerOrigin = '';
    }

    const currentOrigin = String(window.location?.origin ?? '').trim();

    const knownOrigins = Array.from(new Set([
        normalizedConfiguredOrigin,
        referrerOrigin,
        currentOrigin,
    ].filter((origin) => origin !== '')));

    const targetOrigin = knownOrigins[0] ?? '*';

    const isAllowedOrigin = (origin) => {
        if (knownOrigins.length === 0) {
            return true;
        }

        return knownOrigins.includes(String(origin ?? ''));
    };

    const isValidEvent = (event) => {
        if (!isAllowedOrigin(event?.origin ?? '')) {
            return false;
        }

        const data = event?.data;

        if (!data || typeof data !== 'object') {
            return false;
        }

        return String(data.namespace ?? '') === namespace;
    };

    const postToParent = (type, payload = {}) => {
        if (!window.parent || window.parent === window) {
            return false;
        }

        window.parent.postMessage({
            namespace,
            type,
            payload,
        }, targetOrigin);

        return true;
    };

    return {
        isAllowedOrigin,
        isValidEvent,
        postToParent,
        targetOrigin,
        knownOrigins,
    };
};
