import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['target'];

  toggle() {
    this.targetTargets.forEach(target => target.classList.toggle('d-none'));
  }
}
