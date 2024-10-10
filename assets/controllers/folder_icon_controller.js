import {Controller} from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'img'];

    update() {
        const input = this.inputTarget;
        this.imgTarget.src = input.options[input.selectedIndex].dataset.iconFilePath;
    }
}
