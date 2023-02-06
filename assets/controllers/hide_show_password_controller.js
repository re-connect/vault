import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'icon'];

    toggle() {
        const input = this.inputTarget;
        const icon = this.iconTarget;

        input.setAttribute('type', input.getAttribute('type') === 'password' ? 'text' : 'password');
        icon.classList.toggle('fa-eye-slash')
        icon.classList.toggle('fa-eye')
    }
}
