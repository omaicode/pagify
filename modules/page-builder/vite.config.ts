import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
  plugins: [
    // Cast to local Vite plugin type to avoid monorepo cross-package type conflicts.
    laravel({
      input: ["resources/js/webstudio-vite-entry.js"],
      publicDirectory: "../../public",
      buildDirectory: "build/page-builder",
      refresh: false,
    }) as any,
  ],
  build: {
    // Keep copied Webstudio assets from sync step.
    emptyOutDir: false,
  },
});
