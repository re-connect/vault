import { Controller } from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['iframe']

  connect() {
    setTimeout(() => {
      const iframeDocument = this.iframeTarget.contentDocument || this.iframeTarget.contentWindow.document;
      const cols = $(iframeDocument).find('div.pas-column');
      cols.css('flex', '40%')
      cols.find('.pas-dropdown-input').css('min-width', '200px')
    }, 2000);
  }
}
