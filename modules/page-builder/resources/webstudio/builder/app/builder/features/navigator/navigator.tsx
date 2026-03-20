import {
  Box,
  Flex,
  PanelTitle,
  Separator,
  keyframes,
  rawTheme,
  styled,
} from "@webstudio-is/design-system";
import { CssPreview } from "./css-preview";
import { NavigatorTree } from "./navigator-tree";
import { $isDesignMode, $isPageDataLoading } from "~/shared/nano-states";
import { useStore } from "@nanostores/react";
import { InstanceContextMenu } from "~/builder/shared/instance-context-menu";

const shimmer = keyframes({
  "0%": { transform: "translateX(-120%)" },
  "100%": { transform: "translateX(120%)" },
});

const SkeletonLine = styled("div", {
  position: "relative",
  height: rawTheme.spacing[5],
  borderRadius: rawTheme.borderRadius[2],
  backgroundColor: rawTheme.colors.backgroundInputHighlight,
  overflow: "hidden",
  "&::after": {
    content: '""',
    position: "absolute",
    top: 0,
    bottom: 0,
    left: 0,
    width: "45%",
    background:
      "linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent)",
    animation: `${shimmer} 1.2s ease-in-out infinite`,
  },
});

const NavigatorSkeleton = () => {
  const widths = ["88%", "74%", "81%", "63%", "70%", "54%"];

  return (
    <Box
      css={{
        padding: `${rawTheme.spacing[5]} ${rawTheme.spacing[7]}`,
        borderBottom: `1px solid ${rawTheme.colors.borderMain}`,
        display: "grid",
        gap: rawTheme.spacing[4],
      }}
    >
      {widths.map((width, index) => (
        <SkeletonLine
          key={`navigator-skeleton-${index}`}
          css={{
            width,
            marginLeft: index % 2 === 0 ? 0 : rawTheme.spacing[6],
          }}
        />
      ))}
    </Box>
  );
};

export const NavigatorPanel = (_props: { onClose: () => void }) => {
  const isDesignMode = useStore($isDesignMode);
  const isPageDataLoading = useStore($isPageDataLoading);
  return (
    <>
      <PanelTitle>Navigator</PanelTitle>
      <Separator />
      <InstanceContextMenu>
        <Flex grow direction="column" justify="end">
          {isPageDataLoading && <NavigatorSkeleton />}
          <NavigatorTree />
        </Flex>
      </InstanceContextMenu>
      <Separator />
      {isDesignMode && <CssPreview />}
    </>
  );
};
