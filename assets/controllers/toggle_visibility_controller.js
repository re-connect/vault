import { Controller } from "@hotwired/stimulus";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['leftBar'];

  toggle () {
    this.leftBarTarget.classList.toggle('new-color-class');
  }
}
