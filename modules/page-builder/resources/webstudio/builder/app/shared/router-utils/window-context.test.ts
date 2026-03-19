import { describe, expect, it } from "vitest";
import { shouldUseCredentiallessCanvasIframe } from "./window-context";

describe("shouldUseCredentiallessCanvasIframe", () => {
  it("enables credentialless for top-level builder", () => {
    const topLevelWindow = {};
    expect(
      shouldUseCredentiallessCanvasIframe({
        self: topLevelWindow as Window,
        top: topLevelWindow as Window,
      })
    ).toBe(true);
  });

  it("disables credentialless when builder is embedded in another iframe", () => {
    expect(
      shouldUseCredentiallessCanvasIframe({
        self: {} as Window,
        top: {} as Window,
      })
    ).toBe(false);
  });
});
