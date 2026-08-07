import {Controller} from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
       static targets = ['loadingContent', 'content', 'button']

       load() {
              const hasForm = this.buttonTarget.tagName !== 'A';
              const form = hasForm ? this.contentTarget.closest('form') : null;
              if (!hasForm || form.checkValidity()) {
                     this.buttonTarget.classList.add('disabled');
                     this.buttonTarget.disabled = 'disabled';
                     this.loadingContentTarget.style.display = 'inline-block';
                     this.contentTarget.style.display = 'none';

                     const requestTarget = form ?? this.element.closest('turbo-frame') ?? document;
                     requestTarget.addEventListener('turbo:before-fetch-response', this.reset, { once: true });
                     requestTarget.addEventListener('turbo:fetch-request-error', this.reset, { once: true });

                     if (form) {
                            form.submit();
                     }
              }
       }

       reset = () => {
              this.buttonTarget.disabled = false;
              this.buttonTarget.classList.remove('disabled');
              this.buttonTarget.dataset.ariaDisabled = 'false';
              this.loadingContentTarget.style.display = 'none';
              this.contentTarget.style.display = 'inline-block';
       }
}
