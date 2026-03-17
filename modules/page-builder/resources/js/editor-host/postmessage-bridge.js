export const createPostMessageBridge = ({
    originGuard,
    namespace,
    parentToChild,
    childToParent,
    telemetry,
    getLayout,
    setLayout,
    rerender,
}) => {
    const handleMessage = (event) => {
        if (!originGuard.isValidEvent(event)) {
            return;
        }

        const data = event.data;
        const type = String(data.type ?? '');
        const payload = data.payload ?? {};

        if (type === parentToChild.INIT) {
            setLayout(payload?.layout ?? {});
            rerender();
            telemetry.track('bridge.init');
            return;
        }

        if (type === parentToChild.SET_LAYOUT) {
            setLayout(payload?.layout ?? getLayout());
            rerender();
            telemetry.track('bridge.set-layout');
            return;
        }

        if (type === parentToChild.FLUSH) {
            originGuard.postToParent(childToParent.LAYOUT_CHANGE, {
                layout: getLayout(),
                synced: true,
            });
            telemetry.track('bridge.flush');
        }
    };

    return {
        attach() {
            window.addEventListener('message', handleMessage);
        },
        detach() {
            window.removeEventListener('message', handleMessage);
        },
        ready(protocolVersion) {
            originGuard.postToParent(childToParent.READY, {
                protocolVersion,
                namespace,
            });
            telemetry.track('bridge.ready');
        },
        error(message) {
            originGuard.postToParent(childToParent.ERROR, {
                message,
            });
            telemetry.track('bridge.error', { message });
        },
        layoutChange(synced = false) {
            originGuard.postToParent(childToParent.LAYOUT_CHANGE, {
                layout: getLayout(),
                synced,
            });
            telemetry.track('bridge.layout-change', { synced });
        },
    };
};
