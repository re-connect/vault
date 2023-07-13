import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['conditionalField', 'conditionedField'];

  connect () {
    this.toggleFieldDisplay();
  }

  update () {
    this.toggleFieldDisplay();
  }

  toggleFieldDisplay () {
    if (!this.targets.has('conditionalField')) {
      return;
    }
    const conditionalField = this.conditionalFieldTarget;
    const conditionedField = this.conditionedFieldTarget;
    const conditionalValue = conditionalField.dataset.conditionalValue;

    conditionedField.classList.toggle('d-none', conditionalValue !== conditionalField.value);
  }
}
