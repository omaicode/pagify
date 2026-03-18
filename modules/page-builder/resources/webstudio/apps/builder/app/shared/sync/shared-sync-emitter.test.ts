import { describe, expect, it } from "vitest";
import { getSharedSyncEmitter } from "./shared-sync-emitter";

describe("getSharedSyncEmitter", () => {
  it("returns emitter from current window when present", () => {
    const emitter = {} as never;

    expect(
      getSharedSyncEmitter({
        __webstudioSharedSyncEmitter__: emitter,
        parent: {} as Window,
      })
    ).toBe(emitter);
  });

  it("falls back to parent window emitter", () => {
    const emitter = {} as never;

    expect(
      getSharedSyncEmitter({
        parent: {
          __webstudioSharedSyncEmitter__: emitter,
        } as Window,
      })
    ).toBe(emitter);
  });
});
