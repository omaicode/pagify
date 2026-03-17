export const PAGE_BUILDER_IFRAME_PROTOCOL_VERSION = 1;
export const PAGE_BUILDER_IFRAME_NAMESPACE = 'pagify:editor';

export const PAGE_BUILDER_IFRAME_PARENT_TO_CHILD = {
    INIT: 'pagify:editor:init',
    SET_LAYOUT: 'pagify:editor:set-layout',
    FLUSH: 'pagify:editor:flush',
    SEARCH: 'pagify:editor:search',
    TOKEN_REFRESH_RESULT: 'pagify:editor:token-refresh-result',
};

export const PAGE_BUILDER_IFRAME_CHILD_TO_PARENT = {
    READY: 'pagify:editor:ready',
    ERROR: 'pagify:editor:error',
    LAYOUT_CHANGE: 'pagify:editor:layout-change',
    TOKEN_REFRESH_REQUEST: 'pagify:editor:token-refresh-request',
};

export const PAGE_BUILDER_HOST_EVENTS = {
    FLUSH_REQUEST: 'pbx-editor-flush',
    SEARCH_REQUEST: 'pbx-editor-search:set',
};
