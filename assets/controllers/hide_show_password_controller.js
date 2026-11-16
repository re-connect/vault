import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input', 'icon', 'button'];

    toggle() {
        const input = this.inputTarget;
        const icon = this.iconTarget;

        const isHidden = input.getAttribute('type') === 'password';

        input.setAttribute('type', isHidden ? 'text' : 'password');
        icon.classList.toggle('fa-eye-slash')
        icon.classList.toggle('fa-eye')

        if (this.hasButtonTarget) {
            this.buttonTarget.setAttribute('aria-pressed', isHidden ? 'true' : 'false');
        }
    }
}
