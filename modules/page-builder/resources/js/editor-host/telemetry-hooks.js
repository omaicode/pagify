export const createTelemetryCollector = (postToParent) => {
    const events = [];

    const flush = () => {
        if (events.length === 0) {
            return;
        }

        postToParent('pagify:editor:telemetry-batch', {
            events: [...events],
        });

        events.length = 0;
    };

    const track = (name, payload = {}) => {
        events.push({
            name,
            payload,
            at: new Date().toISOString(),
        });

        if (events.length >= 20) {
            flush();
        }
    };

    return {
        track,
        flush,
    };
};
