import { describe, expect, it } from "vitest";
import { isBuilder, isCanvas, isDashboard } from "./is-canvas";

describe("isCanvas", () => {
  it("detects Pagify editor-spa canvas path without projectId", () => {
    const request = new Request(
      "https://example.test/admin/page-builder/editor-spa/canvas?accessToken=abc"
    );

    expect(isCanvas(request)).toBe(true);
  });

  it("detects upstream canvas path", () => {
    const request = new Request("https://example.test/canvas?projectId=123");

    expect(isCanvas(request)).toBe(true);
  });

  it("returns false for non-canvas paths", () => {
    const request = new Request(
      "https://example.test/admin/page-builder/editor-spa?pageId=12"
    );

    expect(isCanvas(request)).toBe(false);
  });

  it("detects Pagify editor-spa root as builder", () => {
    const request = new Request(
      "https://example.test/admin/page-builder/editor-spa?pageId=12&accessToken=abc"
    );

    expect(isBuilder(request)).toBe(true);
    expect(isDashboard(request)).toBe(false);
  });

  it("does not treat Pagify canvas route as builder root", () => {
    const request = new Request(
      "https://example.test/admin/page-builder/editor-spa/canvas?pageId=12&accessToken=abc"
    );

    expect(isCanvas(request)).toBe(true);
    expect(isBuilder(request)).toBe(false);
    expect(isDashboard(request)).toBe(false);
  });
});
