import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
  static targets = ['alert'];

  alertTargetConnected(alert) {
    setTimeout(() => {
      alert.classList.remove('show');

      setTimeout(() => {
        alert.classList.add('d-none');
      }, 400)
    }, 5000)
  }
}
