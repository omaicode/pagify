import {
  Links,
  Meta,
  Outlet,
  Scripts,
  ScrollRestoration,
  type ClientLoaderFunctionArgs,
  type ShouldRevalidateFunction,
} from "@remix-run/react";
import interFont from "@fontsource-variable/inter/index.css?url";
import manropeVariableFont from "@fontsource-variable/manrope/index.css?url";
import robotoMonoFont from "@fontsource/roboto-mono/index.css?url";
import appCss from "../shared/app.css?url";
import { type LinksFunction } from "@remix-run/server-runtime";
import { ErrorBoundary as ErrorBoundaryComponent } from "~/shared/error/error-boundary";
import {
  csrfToken as clientCsrfToken,
  updateCsrfToken,
} from "~/shared/csrf.client";

export const links: LinksFunction = () => {
  // `links` returns an array of objects whose
  // properties map to the `<link />` component props
  return [
    { rel: "stylesheet", href: interFont },
    { rel: "stylesheet", href: manropeVariableFont },
    { rel: "stylesheet", href: robotoMonoFont },
    { rel: "stylesheet", href: appCss },
  ];
};

const Document = (props: { children: React.ReactNode }) => {
  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <Meta />
        <Links />
      </head>
      <body>
        {props.children}
        <ScrollRestoration />
        <Scripts />
      </body>
    </html>
  );
};

export const clientLoader = async ({
  request,
}: ClientLoaderFunctionArgs) => {
  const serverData = {
    csrfToken:
      request.headers.get("sec-fetch-mode") === "navigate"
        ? "pagify-spa-csrf"
        : "",
  };

  if (clientCsrfToken === undefined && serverData.csrfToken !== "") {
    const { csrfToken } = serverData;
    updateCsrfToken(csrfToken);
  }

  // Hide real CSRF token from window.__remixContext
  serverData.csrfToken = "";
  return serverData;
};

clientLoader.hydrate = true;

export const ErrorBoundary = () => {
  return (
    <Document>
      <ErrorBoundaryComponent />
    </Document>
  );
};

export default function Layout() {
  return (
    <Document>
      <Outlet />
    </Document>
  );
}

export const shouldRevalidate: ShouldRevalidateFunction = () => {
  return false;
};
