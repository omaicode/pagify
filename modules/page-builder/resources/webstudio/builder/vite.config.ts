import { resolve } from "node:path";
import { defineConfig } from "vite";
import { vitePlugin as remix } from "@remix-run/dev";
import pc from "picocolors";

export default defineConfig(({ mode }) => {
  const isPagifyBuild = process.env.WEBSTUDIO_PAGIFY_BUILD === "1";

  if (mode === "development") {
    // Enable self-signed certificates for development service 2 service fetch calls.
    // This is particularly important for secure communication with the oauth.ws.token endpoint.
    process.env.NODE_TLS_REJECT_UNAUTHORIZED = "0";
  }

  return {
    base: isPagifyBuild ? "/build/page-builder/" : "/",
    plugins: [
      remix({
        ssr: false,
        ignoredRouteFiles: [
          "**/auth.*",
          "**/oauth.*",
          "**/rest.*",
          "**/trpc.$.ts",
          "**/cgi.*",
          "**/builder-logout.ts",
          "**/dashboard-logout.ts",
          "**/n8n.$.tsx",
          "**/_ui.dashboard*.tsx",
          "**/_ui.login._index.tsx",
          "**/_ui.logout.tsx",
          "**/_ui.error.tsx",
        ],
        future: {
          v3_lazyRouteDiscovery: false,
          v3_relativeSplatPath: false,
          v3_singleFetch: false,
          v3_fetcherPersist: false,
          v3_throwAbortReason: false,
        },
      }),
      {
        name: "request-timing-logger",
        configureServer(server) {
          server.middlewares.use((req, res, next) => {
            const start = Date.now();
            res.on("finish", () => {
              const duration = Date.now() - start;
              if (
                !(
                  req.url?.startsWith("/@") ||
                  req.url?.startsWith("/app") ||
                  req.url?.includes("/node_modules")
                )
              ) {
                console.info(
                  `[${req.method}] ${req.url} - ${duration}ms : ${pc.dim(req.headers.host)}`
                );
              }
            });
            next();
          });
        },
      },
    ],
    resolve: {
      conditions: ["webstudio", "browser", "development|production"],
      alias: [
        {
          find: "~",
          replacement: resolve("app"),
        },

        // before 2,899.74 kB, after 2,145.98 kB
        {
          find: "@supabase/node-fetch",
          replacement: resolve("./app/shared/empty.ts"),
        },
      ],
    },
    define: {
      "process.env.NODE_ENV": JSON.stringify(mode),
    },
    envPrefix: "GITHUB_",
  };
});
