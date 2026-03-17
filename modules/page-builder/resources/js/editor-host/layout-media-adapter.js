const ensureWebstudioShape = (layout) => {
    const baseLayout = layout && typeof layout === 'object' && !Array.isArray(layout) ? { ...layout } : {};

    if (!baseLayout.webstudio || typeof baseLayout.webstudio !== 'object' || Array.isArray(baseLayout.webstudio)) {
        baseLayout.webstudio = {};
    }

    if (!Array.isArray(baseLayout.webstudio.assets)) {
        baseLayout.webstudio.assets = [];
    }

    return baseLayout;
};

export const attachAssetToLayout = (layout, asset) => {
    const normalizedLayout = ensureWebstudioShape(layout);
    const assets = Array.isArray(normalizedLayout.webstudio.assets) ? [...normalizedLayout.webstudio.assets] : [];

    const candidateValues = [
        asset?.uuid,
        asset?.path,
    ].filter((value) => typeof value === 'string' && value.trim() !== '');

    for (const value of candidateValues) {
        if (!assets.includes(value)) {
            assets.push(value);
        }
    }

    normalizedLayout.webstudio.assets = assets;

    return normalizedLayout;
};
