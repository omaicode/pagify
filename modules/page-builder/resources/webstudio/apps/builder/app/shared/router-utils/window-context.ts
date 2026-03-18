export const shouldUseCredentiallessCanvasIframe = (
  currentWindow:
    | Pick<Window, "self" | "top">
    | undefined = typeof window === "undefined" ? undefined : window
) => {
  return currentWindow !== undefined && currentWindow.self === currentWindow.top;
};
