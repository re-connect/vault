import {Controller} from '@hotwired/stimulus';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['loadingContent', 'content']

    connect() {
        this.loadingContentTarget.style.display = 'none';
    }

    load() {
        if (this.contentTarget.closest('form').checkValidity()) {
            this.loadingContentTarget.style.display = 'block ';
            this.contentTarget.style.display = 'none';
        }
        setTimeout(() => {
            this.loadingContentTarget.style.display = 'none ';
            this.contentTarget.style.display = 'block';
        }, 2000);
    }
}
