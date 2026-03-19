import { useStore } from "@nanostores/react";
import {
  theme,
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuItemRightSlot,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuCheckboxItem,
  DropdownMenuSeparator,
  Kbd,
} from "@webstudio-is/design-system";
import { $remoteDialog, setActiveSidebarPanel } from "~/builder/shared/nano-states";
import { $isDesignMode, $userPlanFeatures, toggleBuilderMode } from "~/shared/nano-states";
import { emitCommand } from "~/builder/shared/commands";
import { MenuButton } from "./menu-button";
import { UpgradeIcon } from "@webstudio-is/icons";
import { getSetting, setSetting } from "~/builder/shared/client-settings";
import { help } from "~/shared/help";

const ViewMenuItem = () => {
  const navigatorLayout = getSetting("navigatorLayout");

  return (
    <DropdownMenuSub>
      <DropdownMenuSubTrigger>View</DropdownMenuSubTrigger>
      <DropdownMenuSubContent width="regular">
        <DropdownMenuCheckboxItem
          checked={navigatorLayout === "undocked"}
          onSelect={() => {
            const setting =
              navigatorLayout === "undocked" ? "docked" : "undocked";
            setSetting("navigatorLayout", setting);
          }}
        >
          Undock navigator
        </DropdownMenuCheckboxItem>
      </DropdownMenuSubContent>
    </DropdownMenuSub>
  );
};

export const Menu = ({ defaultOpen }: { defaultOpen?: boolean } = {}) => {
  const userPlanFeatures = useStore($userPlanFeatures);
  const hasPaidPlan = userPlanFeatures.purchases.length > 0;
  const isDesignMode = useStore($isDesignMode);

  return (
    <DropdownMenu modal={false} defaultOpen={defaultOpen}>
      <MenuButton />
      <DropdownMenuContent sideOffset={4} collisionPadding={4} width="regular">
        <DropdownMenuItem onSelect={() => emitCommand("openBreakpointsMenu")}>
          Breakpoints
        </DropdownMenuItem>
        <ViewMenuItem />
        <DropdownMenuSeparator />
        <DropdownMenuItem onSelect={() => emitCommand("undo")}>
          Undo
          <DropdownMenuItemRightSlot>
            <Kbd value={["meta", "z"]} />
          </DropdownMenuItemRightSlot>
        </DropdownMenuItem>
        <DropdownMenuItem onSelect={() => emitCommand("redo")}>
          Redo
          <DropdownMenuItemRightSlot>
            <Kbd value={["meta", "shift", "z"]} />
          </DropdownMenuItemRightSlot>
        </DropdownMenuItem>
        {/* https://github.com/webstudio-is/webstudio/issues/499

          <DropdownMenuItem
            onSelect={() => {
              // TODO
            }}
          >
            Copy
            <DropdownMenuItemRightSlot><Kbd value={["meta", "c"]} /></DropdownMenuItemRightSlot>
          </DropdownMenuItem>
          <DropdownMenuItem
            onSelect={() => {
              // TODO
            }}
          >
            Paste
            <DropdownMenuItemRightSlot><Kbd value={["meta", "v"]} /></DropdownMenuItemRightSlot>
          </DropdownMenuItem>

          */}
        <DropdownMenuItem onSelect={() => emitCommand("deleteInstanceBuilder")}>
          Delete
          <DropdownMenuItemRightSlot>
            <Kbd value={["backspace"]} />
          </DropdownMenuItemRightSlot>
        </DropdownMenuItem>
        <DropdownMenuItem onSelect={() => emitCommand("save")}>
          Save
          <DropdownMenuItemRightSlot>
            <Kbd value={["meta", "s"]} />
          </DropdownMenuItemRightSlot>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          onSelect={() => {
            setActiveSidebarPanel("auto");
            toggleBuilderMode("preview");
          }}
        >
          Preview
          <DropdownMenuItemRightSlot>
            <Kbd value={["meta", "shift", "p"]} />
          </DropdownMenuItemRightSlot>
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        {isDesignMode && (
          <DropdownMenuItem onSelect={() => emitCommand("openCommandPanel")}>
            Search & commands
            <DropdownMenuItemRightSlot>
              <Kbd value={["meta", "k"]} />
            </DropdownMenuItemRightSlot>
          </DropdownMenuItem>
        )}

        <DropdownMenuItem onSelect={() => emitCommand("openKeyboardShortcuts")}>
          Keyboard shortcuts
          <DropdownMenuItemRightSlot>
            <Kbd value={["shift", "?"]} />
          </DropdownMenuItemRightSlot>
        </DropdownMenuItem>

        <DropdownMenuSub>
          <DropdownMenuSubTrigger>Help</DropdownMenuSubTrigger>
          <DropdownMenuSubContent width="regular">
            {help.map((item) => (
              <DropdownMenuItem
                key={item.url}
                onSelect={(event) => {
                  if ("target" in item && item.target === "embed") {
                    event.preventDefault();
                    $remoteDialog.set({
                      title: item.label,
                      url: item.url,
                    });
                    return;
                  }
                  window.open(item.url);
                }}
              >
                {item.label}
              </DropdownMenuItem>
            ))}
          </DropdownMenuSubContent>
        </DropdownMenuSub>

        {hasPaidPlan === false && (
          <>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              onSelect={() => {
                window.open("https://webstudio.is/pricing");
              }}
              css={{ gap: theme.spacing[3] }}
            >
              <UpgradeIcon />
              <div>Upgrade to Pro</div>
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
};
