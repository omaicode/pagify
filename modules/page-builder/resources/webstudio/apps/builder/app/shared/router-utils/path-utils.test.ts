import { describe, expect, it, vi } from "vitest";
import { getCanvasUrl } from "./path-utils";

describe("getCanvasUrl", () => {
  it("does not include mode query param in canvas url", () => {
    vi.stubGlobal("window", {
      location: {
        href: "https://example.test/admin/page-builder/editor-spa?pageId=42&mode=preview&accessToken=abc",
        pathname: "/admin/page-builder/editor-spa",
        search: "?pageId=42&mode=preview&accessToken=abc",
      },
    });

    expect(getCanvasUrl()).toBe(
      "/admin/page-builder/editor-spa/canvas?pageId=42&accessToken=abc"
    );
  });

  it("keeps existing canvas path and removes mode", () => {
    vi.stubGlobal("window", {
      location: {
        href: "https://example.test/admin/page-builder/editor-spa/canvas?pageId=42&mode=content",
        pathname: "/admin/page-builder/editor-spa/canvas",
        search: "?pageId=42&mode=content",
      },
    });

    expect(getCanvasUrl()).toBe("/admin/page-builder/editor-spa/canvas?pageId=42");
  });
});
