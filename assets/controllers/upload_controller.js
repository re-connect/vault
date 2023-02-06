import {Controller} from '@hotwired/stimulus';
import Uppy from '@uppy/core'
import XHRUpload from '@uppy/xhr-upload'
import Dashboard from '@uppy/dashboard'

import UppyFrench from "@uppy/locales/lib/fr_FR";
import UppyGerman from "@uppy/locales/lib/de_DE";
import UppyEnglish from "@uppy/locales/lib/en_US";
import UppyRussian from "@uppy/locales/lib/ru_RU";
import UppyItalian from "@uppy/locales/lib/it_IT";
import UppySpanish from "@uppy/locales/lib/es_ES";
import UppyArabic from "@uppy/locales/lib/ar_SA";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    locale: String,
    url: String,
  };

  connect() {
    const sessionLocale = this.localeValue;

    const availableLocales = {
      'fr': UppyFrench,
      'de': UppyGerman,
      'en': UppyEnglish,
      'ru': UppyRussian,
      'it': UppyItalian,
      'es': UppySpanish,
      'ar': UppyArabic,
    }

    Uppy({locale: availableLocales[sessionLocale] ?? availableLocales['fr']})
      .use(Dashboard, {
        trigger: this.element,
        closeModalOnClickOutside: true,
        proudlyDisplayPoweredByUppy: false,
        closeAfterFinish: true,
        theme: 'auto',
      })
      .use(XHRUpload, {
        endpoint: this.urlValue
      })
      .on('complete', () => {
        window.location.reload()
      });
  }
}
