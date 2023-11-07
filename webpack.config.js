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

  .addEntry('app', './assets/js/app.js')
  .addEntry('app_pro', './assets/js/app-pro.js')
  .addEntry('accueil', './assets/js/accueil.js')
  .addEntry('base_personal_data', './assets/js/base-personal-data.js')
  .addEntry('beneficiaire_accueil', './assets/js/beneficiaire-accueil.js')
  .addEntry('contact', './assets/js/angular/contact.js')
  .addEntry('creation_beneficiaire_step_2', './assets/js/creation-beneficiaire-step-2.js')
  .addEntry('custom', './assets/js/custom.js')
  .addEntry('document', './assets/js/document_v2.js')
  .addEntry('evenement', './assets/js/angular/evenement.js')
  .addEntry('evenement_form', './assets/js/evenement_form.js')
  .addEntry('login', './assets/js/login.js')
  .addEntry('membre_beneficiaire', './assets/js/membre-beneficiaire.js')
  .addEntry('membre_centre', './assets/js/membre-centre.js')
  .addEntry('membre_centres', './assets/js/membre-centres.js')
  .addEntry('note', './assets/js/angular/note.js')
  .addEntry('parameter', './assets/js/parameter.js')
  .addEntry('reset_password', './assets/js/reset-password.js')
  .addEntry('set_question_secrete', './assets/js/set-question-secrete.js')
  .addEntry('set_question_secrete_etape_3', './assets/js/set-question-secrete-etape-3.js')

  // New Home
  .addEntry('homeV2', './assets/js/homeV2/app.js')
  .addStyleEntry('homeV2style', './assets/css/homeV2/main.scss')

  // New app
  .addEntry('appV2', './assets/js/appV2/app.js')
  .addStyleEntry('appV2style', './assets/css/appV2/main.scss')

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
    {
      from: './node_modules/angular-utils-pagination',
      to: 'angular-utils-pagination/[path][name].[hash:8].[ext]',
      // only copy files matching this pattern
      pattern: /\.(html)$/
    },
    {
      from: './node_modules/ckeditor/',
      to: './ckeditor/[path][name].[ext]',
      pattern: /\.(js|css)$/,
      includeSubdirectories: true
    },
    {from: './node_modules/ckeditor/adapters', to: './ckeditor/adapters/[path][name].[ext]'},
    {from: './node_modules/ckeditor/lang', to: './ckeditor/lang/[path][name].[ext]'},
    {from: './node_modules/ckeditor/plugins', to: './ckeditor/plugins/[path][name].[ext]'},
    {from: './node_modules/ckeditor/skins', to: './ckeditor/skins/[path][name].[ext]'},
    {from: './node_modules/ckeditor/vendor', to: './ckeditor/vendor/[path][name].[ext]'}
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
