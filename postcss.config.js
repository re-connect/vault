// Right-to-left layout for Arabic, Pashto and Dari (RGAA 8.10).
// postcss-rtlcss adds [dir="rtl"] overrides that mirror margins, paddings, offsets,
// floats, positions and text alignment, for Bootstrap and the site styles alike.
// "override" mode keeps the original rules untouched: left-to-right pages do not change.
const postcssRTLCSS = require('postcss-rtlcss');
const { Mode } = require('postcss-rtlcss/options');

module.exports = {
  plugins: [postcssRTLCSS({ mode: Mode.override })],
};
