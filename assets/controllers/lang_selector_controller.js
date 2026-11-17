import { Controller } from '@hotwired/stimulus';
import {visit} from '@hotwired/turbo';

export default class extends Controller {
  static targets = ['list', 'button'];

  toggle() {
    this.listTarget.classList.contains('d-none') ? this.open() : this.close();
  }

  open() {
    this.listTarget.classList.remove('d-none');
    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute('aria-expanded', 'true');
    }
  }

  close() {
    this.listTarget.classList.add('d-none');
    if (this.hasButtonTarget) {
      this.buttonTarget.setAttribute('aria-expanded', 'false');
    }
  }

  closeAndFocus() {
    if (this.listTarget.classList.contains('d-none')) {
      return;
    }
    this.close();
    if (this.hasButtonTarget) {
      this.buttonTarget.focus();
    }
  }

  closeOnOutsideClick(event) {
    if (!this.element.contains(event.target)) {
      this.close();
    }
  }

  closeOnFocusOut(event) {
    if (event.relatedTarget && !this.element.contains(event.relatedTarget)) {
      this.close();
    }
  }

  select(event) {
    const origin = window.location.origin;
    const lang = event.currentTarget.dataset.lang;
    visit(`${origin}/public/changer-langue/${lang}`);
  }
}
