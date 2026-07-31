module.exports = ({ config }) => {
  const downloadToken = process.env.RNMAPBOX_MAPS_DOWNLOAD_TOKEN?.trim();
  const plugins = (config.plugins || []).map((plugin) => {
    if (plugin !== '@rnmapbox/maps' || !downloadToken) return plugin;
    return ['@rnmapbox/maps', { RNMAPBOX_MAPS_DOWNLOAD_TOKEN: downloadToken }];
  });

  return { ...config, plugins };
};
