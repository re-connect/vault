import {Controller} from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
       static targets = ['loadingContent', 'content', 'button']

       load() {
              const checkFormValidity = this.buttonTarget.tagName !== 'A';
              if (!checkFormValidity || this.contentTarget.closest('form').checkValidity()) {
                     this.buttonTarget.classList.add('disabled');
                     this.buttonTarget.disabled = 'disabled';
                     this.loadingContentTarget.style.display = 'inline-block';
                     this.contentTarget.style.display = 'none';
              }
              setTimeout(() => {
                     this.buttonTarget.disabled = false;
                     this.buttonTarget.classList.remove('disabled');
                     this.buttonTarget.dataset.ariaDisabled = 'false';
                     this.loadingContentTarget.style.display = 'none';
                     this.contentTarget.style.display = 'inline-block';
              }, 2000);
       }
}
