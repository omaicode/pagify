import { type GeneratedTemplateMeta } from "@webstudio-is/template";
import { $registeredTemplates } from "~/shared/nano-states";
import { generateFragmentFromHtml } from "~/shared/html";
import { elementComponent, type Instance, type Prop, type WebstudioFragment } from "@webstudio-is/sdk";

type PagifyRegisteredComponentChild =
  | string
  | {
      key?: string;
      component?: string;
      element?: string;
      tag?: string;
      class?: string;
      style?: string;
      attributes?: Record<string, string>;
      text?: string;
      inner_html?: string;
      children?: PagifyRegisteredComponentChild[];
    };

export type PagifyRegisteredComponent = {
  key: string;
  label: string;
  description?: string;
  icon?: string;
  category?: string;
  owner?: string;
  owner_type?: string;
  source?: string;
  html_template?: string;
  props_schema?: Record<string, unknown>;
  element?: string;
  tag?: string;
  class?: string;
  style?: string;
  attributes?: Record<string, string>;
  text?: string;
  inner_html?: string;
  children?: PagifyRegisteredComponentChild[];
};

const PAGIFY_TEMPLATE_PREFIX = "pagify:registered:";
const PLACEHOLDER_TAG = "pagify-component";
const PLACEHOLDER_REF_ATTR = "data-pagify-component";

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

const resolveTemplateKey = (component: PagifyRegisteredComponent) => {
  const owner = resolveOwner(component.owner);
  return `${PAGIFY_TEMPLATE_PREFIX}${owner}:${resolveName(component.key)}`;
};

const escapeHtml = (value: string) =>
  value
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");

const buildAttributeString = (component: PagifyRegisteredComponent) => {
  const attributes: Record<string, string> = {
    ...(component.attributes ?? {}),
  };

  const className = (component.class ?? "").trim();
  if (className !== "") {
    attributes.class = className;
  }

  const style = (component.style ?? "").trim();
  if (style !== "") {
    attributes.style = style.endsWith(";") ? style : `${style};`;
  }

  return Object.entries(attributes)
    .filter(([name, value]) => name.trim() !== "" && String(value).trim() !== "")
    .map(([name, value]) => `${name}="${escapeHtml(String(value))}"`)
    .join(" ");
};

const buildNodeAttributeString = (node: {
  attributes?: Record<string, string>;
  class?: string;
  style?: string;
}) => {
  const attributes: Record<string, string> = {
    ...(node.attributes ?? {}),
  };

  const className = (node.class ?? "").trim();
  if (className !== "") {
    attributes.class = className;
  }

  const style = (node.style ?? "").trim();
  if (style !== "") {
    attributes.style = style.endsWith(";") ? style : `${style};`;
  }

  return Object.entries(attributes)
    .filter(([name, value]) => name.trim() !== "" && String(value).trim() !== "")
    .map(([name, value]) => `${name}="${escapeHtml(String(value))}"`)
    .join(" ");
};

const buildPlaceholderMarkup = (reference: string) => {
  const safeReference = escapeHtml(reference.trim());
  if (safeReference === "") {
    return "";
  }

  return `<${PLACEHOLDER_TAG} ${PLACEHOLDER_REF_ATTR}="${safeReference}"></${PLACEHOLDER_TAG}>`;
};

const buildChildMarkup = (child: PagifyRegisteredComponentChild): string => {
  if (typeof child === "string") {
    return buildPlaceholderMarkup(child);
  }

  const keyReference = (child.key ?? "").trim();
  if (keyReference !== "") {
    return buildPlaceholderMarkup(keyReference);
  }

  const componentReference = (child.component ?? "").trim();
  if (componentReference !== "") {
    return buildPlaceholderMarkup(componentReference);
  }

  const tag = (child.element ?? child.tag ?? "").trim().toLowerCase() || "section";
  const attrs = buildNodeAttributeString(child);
  const attrsChunk = attrs === "" ? "" : ` ${attrs}`;
  const innerHtml = (child.inner_html ?? "").trim();
  const text = (child.text ?? "").trim();
  const nestedChildren = Array.isArray(child.children)
    ? child.children.map((node) => buildChildMarkup(node)).join("")
    : "";
  const content = `${innerHtml}${text === "" ? "" : escapeHtml(text)}${nestedChildren}`;

  return `<${tag}${attrsChunk}>${content}</${tag}>`;
};

const buildChildrenMarkup = (component: PagifyRegisteredComponent) => {
  if (!Array.isArray(component.children) || component.children.length === 0) {
    return "";
  }

  return component.children.map((child) => buildChildMarkup(child)).join("");
};

const buildTemplateWithChildren = (component: PagifyRegisteredComponent) => {
  const childrenMarkup = buildChildrenMarkup(component);
  if (childrenMarkup === "") {
    return undefined;
  }

  const htmlTemplate = (component.html_template ?? "").trim();
  if (htmlTemplate.includes("{{children}}")) {
    return htmlTemplate.replaceAll("{{children}}", childrenMarkup);
  }

  const tag =
    (component.element ?? component.tag ?? "").trim().toLowerCase() || "section";
  const attrs = buildAttributeString(component);
  const attrsChunk = attrs === "" ? "" : ` ${attrs}`;
  const innerHtml = (component.inner_html ?? "").trim();
  const text = (component.text ?? "").trim();
  const content = `${innerHtml}${text === "" ? "" : escapeHtml(text)}${childrenMarkup}`;

  return `<${tag}${attrsChunk}>${content}</${tag}>`;
};

const buildFallbackHtmlTemplate = (component: PagifyRegisteredComponent) => {
  const tag =
    (component.element ?? component.tag ?? "").trim().toLowerCase() || "section";
  const attrs = buildAttributeString(component);
  const attrsChunk = attrs === "" ? "" : ` ${attrs}`;
  const innerHtml = (component.inner_html ?? "").trim();

  if (innerHtml !== "") {
    return `<${tag}${attrsChunk}>${innerHtml}</${tag}>`;
  }

  const text = (component.text ?? component.label ?? component.key).trim();

  return `<${tag}${attrsChunk}>${escapeHtml(text)}</${tag}>`;
};

const resolveTemplateHtml = (component: PagifyRegisteredComponent) => {
  const templateWithChildren = buildTemplateWithChildren(component);
  if (templateWithChildren !== undefined) {
    return templateWithChildren;
  }

  const htmlTemplate = (component.html_template ?? "").trim();

  return htmlTemplate !== "" ? htmlTemplate : buildFallbackHtmlTemplate(component);
};

const buildComponentReferenceMap = (
  components: Array<{ definition: PagifyRegisteredComponent; templateKey: string }>
) => {
  const aliases = new Map<string, string>();

  const addAlias = (alias: string, templateKey: string) => {
    const trimmed = alias.trim();
    if (trimmed === "") {
      return;
    }

    aliases.set(trimmed, templateKey);
    aliases.set(normalizeToken(trimmed), templateKey);
  };

  for (const { definition, templateKey } of components) {
    const owner = resolveOwner(definition.owner);
    const rawKey = definition.key.trim();
    const normalizedRawKey = resolveName(rawKey);

    addAlias(templateKey, templateKey);
    addAlias(rawKey, templateKey);
    addAlias(normalizedRawKey, templateKey);

    if (rawKey.includes(":")) {
      const shortKey = rawKey.slice(rawKey.lastIndexOf(":") + 1);
      addAlias(shortKey, templateKey);
      addAlias(resolveName(shortKey), templateKey);
    }

    addAlias(`${owner}:${rawKey}`, templateKey);
    addAlias(`${owner}:${normalizedRawKey}`, templateKey);
  }

  return aliases;
};

const resolveComponentReference = (
  reference: string,
  aliases: Map<string, string>
) => {
  const trimmed = reference.trim();
  if (trimmed === "") {
    return undefined;
  }

  if (trimmed.startsWith(PAGIFY_TEMPLATE_PREFIX)) {
    return trimmed;
  }

  return aliases.get(trimmed) ?? aliases.get(normalizeToken(trimmed));
};

const applyComponentPlaceholders = (
  fragment: WebstudioFragment,
  aliases: Map<string, string>
): WebstudioFragment => {
  if (fragment.instances.length === 0) {
    return fragment;
  }

  const nextInstances: Instance[] = [];
  const replacedInstanceIds = new Set<string>();

  for (const instance of fragment.instances) {
    if (instance.component !== elementComponent || instance.tag !== PLACEHOLDER_TAG) {
      nextInstances.push(instance);
      continue;
    }

    const referenceProp = fragment.props.find(
      (prop) =>
        prop.instanceId === instance.id &&
        prop.name === PLACEHOLDER_REF_ATTR &&
        prop.type === "string" &&
        typeof prop.value === "string"
    );

    const resolvedComponent =
      referenceProp !== undefined
        ? resolveComponentReference(referenceProp.value, aliases)
        : undefined;

    if (resolvedComponent === undefined) {
      nextInstances.push(instance);
      continue;
    }

    const nextInstance: Instance = {
      ...instance,
      component: resolvedComponent,
    };
    delete nextInstance.tag;
    nextInstances.push(nextInstance);
    replacedInstanceIds.add(instance.id);
  }

  if (replacedInstanceIds.size === 0) {
    return fragment;
  }

  const nextProps: Prop[] = fragment.props.filter((prop) => {
    if (replacedInstanceIds.has(prop.instanceId) === false) {
      return true;
    }

    return prop.name !== PLACEHOLDER_REF_ATTR;
  });

  return {
    ...fragment,
    instances: nextInstances,
    props: nextProps,
  };
};

const applyNavigatorLabels = (
  fragment: WebstudioFragment,
  componentLabel: string
): WebstudioFragment => {
  if (fragment.instances.length === 0 || fragment.children.length === 0) {
    return fragment;
  }

  const instancesById = new Map<string, Instance>();
  for (const instance of fragment.instances) {
    instancesById.set(instance.id, { ...instance });
  }

  let labeledRoots = 0;
  for (const child of fragment.children) {
    if (child.type !== "id") {
      continue;
    }

    const instance = instancesById.get(child.value);
    if (instance === undefined) {
      continue;
    }

    instance.label =
      labeledRoots === 0 ? componentLabel : `${componentLabel} ${labeledRoots + 1}`;
    labeledRoots += 1;
  }

  return {
    ...fragment,
    instances: Array.from(instancesById.values()),
  };
};

const toTemplateMeta = (
  component: PagifyRegisteredComponent,
  aliases: Map<string, string>
): GeneratedTemplateMeta => ({
  category: "other",
  label: component.label,
  description: component.description,
  icon: component.icon,
  template: applyNavigatorLabels(
    applyComponentPlaceholders(
      generateFragmentFromHtml(resolveTemplateHtml(component)),
      aliases
    ),
    component.label
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

  const resolvedComponents = components
    .map((definition) => ({
      definition,
      key: definition.key.trim(),
      templateKey: resolveTemplateKey(definition),
    }))
    .filter(({ key }) => key !== "");

  const aliases = buildComponentReferenceMap(
    resolvedComponents.map(({ definition, templateKey }) => ({
      definition,
      templateKey,
    }))
  );

  for (const { definition: component, templateKey } of resolvedComponents) {
    const key = component.key.trim();
    if (key === "") {
      continue;
    }

    nextTemplates.set(templateKey, toTemplateMeta(component, aliases));
  }

  $registeredTemplates.set(nextTemplates);
};
