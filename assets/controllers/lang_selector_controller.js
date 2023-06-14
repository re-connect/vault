import { Controller } from '@hotwired/stimulus';
import {visit} from '@hotwired/turbo';

export default class extends Controller {
  static targets = ['list'];

  toggle() {
    this.listTarget.classList.toggle('d-none')
  }

  select(event) {
    const origin = window.location.origin;
    const lang = event.currentTarget.dataset.lang;
    visit(`${origin}/public/changer-langue/${lang}`);
  }
}
