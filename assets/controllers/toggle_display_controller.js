import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['target'];

  toggle() {
    this.targetTarget.classList.toggle('d-none')
  }
}
