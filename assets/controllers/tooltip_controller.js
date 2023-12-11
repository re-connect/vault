import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap-next';

export default class extends Controller {
  static values = { title: String };
  tooltip = null;

  connect () {
    this.tooltip = new Tooltip(this.element, {
      trigger: 'hover',
      container: this.element.parentNode,
      title: this.titleValue,
    });
  }
}
