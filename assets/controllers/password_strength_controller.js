import { Controller } from '@hotwired/stimulus';
import { getValidCriteria, isPasswordStrongEnough } from './helpers/password_strength_helper';

export default class extends Controller {
  static targets = ['input', 'badge', 'widget', 'validText', 'invalidText'];

  check() {
    const value = this.inputTarget.value;
    const validCriteria = getValidCriteria(value);
    const isPasswordStrong = isPasswordStrongEnough(validCriteria);

    this.toggleContainerVisibility(value);
    this.toggleIsValidText(isPasswordStrong);
    this.enableDisableForm(isPasswordStrong);
    this.badgeTargets.forEach(this.toggleBadgeColor(validCriteria));
  }

  toggleBadgeColor = (validCriteria) => (target) => {
    const isValid = validCriteria.includes(target.dataset.criterionName)
    target.classList.toggle('bg-success', isValid);
    target.classList.toggle('bg-danger', !isValid);
  }

  toggleContainerVisibility(value) {
    this.widgetTarget.classList.toggle('hidden', !value || value.length === 0)
  }

  toggleIsValidText(isPasswordStrong) {
    this.validTextTarget.classList.toggle('hidden', !isPasswordStrong);
    this.invalidTextTarget.classList.toggle('hidden', isPasswordStrong);
  }

  enableDisableForm(isPasswordStrong) {
    const button = this.inputTarget.form.querySelector('button[type="submit"]');
    if (button) {
      button.disabled = !isPasswordStrong;
    }
  }
}
