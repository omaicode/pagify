import * as React from "react";

export { ErrorBoundary } from "~/shared/error/error-boundary";

export default function NotFound() {
  // Placeholder component to prevent Remix warning:
  // "Matched leaf route at location '/{SOME_LOCATION}' does not have an element or Component."
  // Without this, an <Outlet /> with a null value would render an empty page.
  return (
    <div>
      <h1>Not Found</h1>
      <p>The page you requested could not be found.</p>
    </div>
  );
}
