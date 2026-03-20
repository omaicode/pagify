// Our root outlet doesn't contain a layout because we have 2 types of documents: canvas and builder and we need to decide down the line which one to render, thre is no single root document.
import {
  Outlet,
  type ShouldRevalidateFunction,
} from "@remix-run/react";
import { useSetFeatures } from "./shared/use-set-features";

export default function App() {
  useSetFeatures();

  return <Outlet />;
}

export const shouldRevalidate: ShouldRevalidateFunction = () => {
  return false;
};
