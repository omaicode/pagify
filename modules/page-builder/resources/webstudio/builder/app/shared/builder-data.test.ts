import { describe, expect, it } from "vitest";
import { serializeBuilderDataForPatch, type BuilderData } from "./builder-data";

describe("serializeBuilderDataForPatch", () => {
  it("converts map-based builder data to JSON-safe arrays", () => {
    const data = {
      project: { id: "p1", title: "Demo" },
      pages: { homePage: { id: "1" }, pages: [], folders: [] },
      assets: new Map([["a1", { id: "a1" }]]),
      instances: new Map([["i1", { id: "i1" }]]),
      props: new Map([["p1", { id: "p1" }]]),
      dataSources: new Map([["d1", { id: "d1" }]]),
      resources: new Map([["r1", { id: "r1" }]]),
      breakpoints: new Map([["b1", { id: "b1" }]]),
      styleSources: new Map([["s1", { id: "s1" }]]),
      styleSourceSelections: new Map([["i1", { instanceId: "i1" }]]),
      styles: new Map([["st1", { styleSourceId: "s1", property: "color" }]]),
      marketplaceProduct: undefined,
    } as unknown as BuilderData;

    expect(serializeBuilderDataForPatch(data)).toEqual({
      instances: [{ id: "i1" }],
      props: [{ id: "p1" }],
      dataSources: [{ id: "d1" }],
      resources: [{ id: "r1" }],
      breakpoints: [{ id: "b1" }],
      styleSources: [{ id: "s1" }],
      styleSourceSelections: [{ instanceId: "i1" }],
      styles: [{ styleSourceId: "s1", property: "color" }],
    });
  });
});
