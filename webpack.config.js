const Encore = require('@symfony/webpack-encore');
const path = require('path');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
  Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
  // directory where compiled assets will be stored
  .setOutputPath('public/build/')
  // public path used by the web server to access the output path
  .setPublicPath('/build')
  // only needed for CDN's or subdirectory deploy
  // .setManifestKeyPrefix('build/')

  // New Home
  .addEntry('home', './assets/js/home/app.js')
  .addStyleEntry('homeStyle', './assets/css/home/main.scss')

  // New app
  .addEntry('appV2', './assets/js/appV2/app.js')
  .addStyleEntry('appV2style', './assets/css/appV2/main.scss')

  // Admin
  .addEntry('custom', './assets/js/custom.js')
  .addStyleEntry('adminStyle', './assets/css/appV2/admin/main.scss')

  // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
  .splitEntryChunks()

  .enableStimulusBridge('./assets/controllers.json')

  .splitEntryChunks()
  .disableSingleRuntimeChunk()
  .cleanupOutputBeforeBuild()
  .enableBuildNotifications()
  .enableSourceMaps(Encore.isDevServer())
  .enableVersioning(!Encore.isDevServer())

  // configure Babel
  // .configureBabel((config) => {
  //     config.plugins.push('@babel/a-babel-plugin');
  // })

  // enables and configure @babel/preset-env polyfills
  .configureBabelPresetEnv((config) => {
    config.useBuiltIns = 'usage';
    config.corejs = '3.23';
  })

  .copyFiles([
    {from: './assets/images', to: 'images/[path][name].[hash:8].[ext]'},
  ])

  .enableSassLoader()
  .enableLessLoader()
  .autoProvidejQuery()
  .configureDevServerOptions(options => {
    options.server = {
      type: 'https',
      options: {
        pfx: path.join(process.env.HOME, '.symfony5/certs/default.p12'),
      }
    }
  })
;

const config = Encore.getWebpackConfig();
config.resolve.symlinks = false;

module.exports = config;
