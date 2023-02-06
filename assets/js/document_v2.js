const $ = require('jquery');
import './angular/document';
import './elevate-zoom';
import '../components/Routing';
import UppyFrench from '@uppy/locales/lib/fr_FR';
import UppyGerman from '@uppy/locales/lib/de_DE';
import UppyEnglish from '@uppy/locales/lib/en_US';
import UppySpanish from '@uppy/locales/lib/es_ES';
import UppyRussian from '@uppy/locales/lib/ru_RU';
import UppyItalian from '@uppy/locales/lib/it_IT';
import UppyArabic from '@uppy/locales/lib/ar_SA';

// Autocomplete folder feature with jquery-ui style
import './autocomplete-folder.js';
import '../css/jquery-ui.structure.css';

/**
 * Uppy
 */
// Import the plugins
const Uppy = require('@uppy/core')
const XHRUpload = require('@uppy/xhr-upload')
const Dashboard = require('@uppy/dashboard')

UppyFrench.strings.browseFiles = 'Rechercher des documents';
UppyFrench.strings.uploadXFiles = {
    '0': 'Valider %{smart_count} fichier',
    '1': 'Valider %{smart_count} fichiers',
    '2': 'Valider %{smart_count} fichiers'
};

UppyGerman.strings.browse = 'suchen';
UppyGerman.strings.dropPasteFiles = 'Zieh die Dateien in dieses Fenster oder klick auf %{browse}';
UppyGerman.strings.addMore = 'Weitere hinzufügen';
UppyGerman.strings.uploadXFiles = {
  '0': '%{smart_count} Dokumente hochladen',
  '1': '%{smart_count} Dokumente hochladen',
  '2': '%{smart_count} Dokumente hochladen'
};

// And their styles (for UI plugins)
require('@uppy/core/dist/style.css')
require('@uppy/dashboard/dist/style.css')

$(function () {
    const sessionLocale = $('#select-files').data('locale');
    const availableLocales = {
      'fr': UppyFrench,
      'de': UppyGerman,
      'en': UppyEnglish,
      'ru': UppyRussian,
      'it': UppyItalian,
      'es': UppySpanish,
      'ar': UppyArabic,
    }
    const beneficiaryId = $("#personalDataBody").data("beneficiaireId");
    const uppy = Uppy({locale: availableLocales[sessionLocale] ?? availableLocales['fr']})
        .use(Dashboard, {
            trigger: '#select-files',
            closeModalOnClickOutside: true,
            proudlyDisplayPoweredByUppy: false,
            closeAfterFinish: true,
            theme: 'auto',
            onRequestCloseModal: () => {
                $('.ui-tooltip').remove();
                uppy.reset();
                uppy.getPlugin('Dashboard').closeModal();
            },
        })
        .use(XHRUpload, {endpoint: Routing.generate('api_document_upload', {beneficiaryId: beneficiaryId})})

    uppy.on('complete', (result) => {
        angular.element($("#DocumentsListCtrl")).scope().refresh();
    })

    uppy.on('upload', (data) => {
        const {xhrUpload} = uppy.getState()
        uppy.setState({
            xhrUpload: {
                ...xhrUpload,
                endpoint: Routing.generate('api_document_upload', {
                    beneficiaryId: beneficiaryId,
                    folder: $("#personalDataBody").data("folderId")
                })
            }
        })
    });
});
