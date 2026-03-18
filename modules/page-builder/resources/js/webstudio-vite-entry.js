const bootstrap = window.__pagifyWebstudioBootstrap;

const resolveBasename = () => {
	const normalizedPath = window.location.pathname.replace(/\/+$/, "") || "/";
	const editorSpaMarker = "/page-builder/editor-spa";
	const markerIndex = normalizedPath.indexOf(editorSpaMarker);

	if (markerIndex >= 0) {
		return normalizedPath.slice(0, markerIndex + editorSpaMarker.length) || "/";
	}

	return normalizedPath;
};

const basename = resolveBasename();
window.__remixRouteModules = window.__remixRouteModules || {};
window.__remixContext = {
	basename,
	future: {
		v3_fetcherPersist: true,
		v3_relativeSplatPath: true,
		v3_singleFetch: false,
	},
	state: {
		loaderData: {},
		actionData: null,
		errors: null,
	},
	isSpaMode: true,
};

if (!bootstrap?.manifest || !bootstrap?.entry) {
	console.error("Missing Webstudio bootstrap urls from Laravel view context");
} else {
	import(/* @vite-ignore */ bootstrap.manifest)
		.then(() => import(/* @vite-ignore */ bootstrap.entry))
		.catch((error) => {
			console.error("Failed to boot Webstudio SPA", error);
		});
}
