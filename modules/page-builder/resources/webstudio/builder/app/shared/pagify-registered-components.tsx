import * as React from "react";
import {
  PlaceholderValue,
  renderTemplate,
  ws,
  type GeneratedTemplateMeta,
} from "@webstudio-is/template";
import { $registeredTemplates } from "~/shared/nano-states";

export type PagifyRegisteredComponent = {
  key: string;
  label: string;
  description?: string;
  icon?: string;
  owner?: string;
};

const PAGIFY_TEMPLATE_PREFIX = "pagify:registered:";

const normalizeToken = (value: string) =>
  value
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9:_-]+/g, "-")
    .replace(/-+/g, "-")
    .replace(/^-|-$/g, "");

const resolveOwner = (owner: string | undefined) => {
  const resolved = normalizeToken(owner ?? "");
  return resolved === "" ? "unknown" : resolved;
};

const resolveName = (key: string) => {
  const normalized = normalizeToken(key);
  return normalized === "" ? "component" : normalized;
};

const toTemplateMeta = (
  component: PagifyRegisteredComponent
): GeneratedTemplateMeta => ({
  category: "other",
  label: component.label,
  description: component.description,
  icon: component.icon,
  template: renderTemplate(
    <>
      <ws.element ws:tag="section" ws:label={component.label}>
        <ws.element ws:tag="p">
          {new PlaceholderValue(component.label)}
        </ws.element>
      </ws.element>
    </>
  ),
});

export const registerPagifyRegisteredComponents = (
  components: PagifyRegisteredComponent[]
) => {
  const prevTemplates = $registeredTemplates.get();
  const nextTemplates = new Map(
    [...prevTemplates.entries()].filter(
      ([key]) => key.startsWith(PAGIFY_TEMPLATE_PREFIX) === false
    )
  );

  for (const component of components) {
    const key = component.key.trim();
    if (key === "") {
      continue;
    }

    const owner = resolveOwner(component.owner);
    const fullKey = `${PAGIFY_TEMPLATE_PREFIX}${owner}:${resolveName(key)}`;
    nextTemplates.set(fullKey, toTemplateMeta(component));
  }

  $registeredTemplates.set(nextTemplates);
};
