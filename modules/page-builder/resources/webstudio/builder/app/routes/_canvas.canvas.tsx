import { lazy } from "react";
import { Scripts, ScrollRestoration } from "@remix-run/react";
import { ClientOnly } from "~/shared/client-only";

export { ErrorBoundary } from "~/shared/error/error-boundary";

const Canvas = lazy(async () => {
  const { Canvas } = await import("~/canvas/index.client");
  return { default: Canvas };
});

const CanvasRoute = () => {
  return (
    // this setup remix scripts on canvas and after rendering a website
    // scripts will continue to work even though removed from dom
    <ClientOnly
      fallback={
        <body>
          <Scripts />
          <ScrollRestoration />
        </body>
      }
    >
      <Canvas />
    </ClientOnly>
  );
};

export default CanvasRoute;
