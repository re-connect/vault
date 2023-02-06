import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    connect() {
        this.element.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
    }
}
