import { TooltipProvider } from "@radix-ui/react-tooltip";
import * as React from "react";
import {
  Flex,
  globalCss,
  rawTheme,
  Text,
  theme,
} from "@webstudio-is/design-system";
import { PagifyLogoIcon } from "@webstudio-is/icons";

const globalStyles = globalCss({
  body: {
    margin: 0,
    overflow: "hidden",
  },
});

export type LoginProps = {
  errorMessage?: string;
};

export const Login = ({
  errorMessage,
}: LoginProps) => {
  globalStyles();
  return (
    <Flex
      align="center"
      justify="center"
      css={{
        height: "100vh",
        background: theme.colors.brandBackgroundDashboard,
      }}
    >
      <Flex
        direction="column"
        align="center"
        gap="6"
        css={{
          width: theme.spacing[35],
          minWidth: theme.spacing[20],
          padding: theme.spacing[17],
          borderRadius: theme.spacing[5],
          [`@media (min-width: ${rawTheme.spacing[35]})`]: {
            backgroundColor: `rgba(255, 255, 255, 0.5)`,
          },
        }}
      >
        <PagifyLogoIcon size={48} />
        <Text variant="brandSectionTitle" as="h1" align="center">
          Welcome to Webstudio
        </Text>

        <TooltipProvider>
          <Flex direction="column" gap="3" css={{ width: "100%" }}>
            <Text align="center" color="moreSubtle">
              Authentication is disabled.
            </Text>
            <Text align="center" color="moreSubtle">
              Open editor with a valid accessToken in URL.
            </Text>
          </Flex>
        </TooltipProvider>
        {errorMessage ? (
          <Text align="center" color="destructive">
            {errorMessage}
          </Text>
        ) : null}
      </Flex>
    </Flex>
  );
};
