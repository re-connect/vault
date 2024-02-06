import { Controller } from '@hotwired/stimulus';
import PasswordHelper from "./helpers/PasswordHelper";

export default class extends Controller {
  static targets = ['input', 'badge', 'widget', 'validText', 'invalidText'];
  static values = {
    passwordLength: String,
    checkSpecialChar: Boolean,
  };

  connect() {
    this.helper = new PasswordHelper(this.passwordLengthValue, this.checkSpecialCharValue);
  }

  check () {
    const value = this.inputTarget.value;
    const validCriteria = this.helper.getValidCriteria(value);
    const isPasswordStrong = this.helper.isPasswordStrongEnough(validCriteria);

    this.toggleContainerVisibility(value);
    this.toggleIsValidText(isPasswordStrong);
    this.enableDisableForm(isPasswordStrong);
    this.badgeTargets.forEach(this.toggleBadgeColor(validCriteria));
  }

  toggleBadgeColor = (validCriteria) => (target) => {
    const isValid = validCriteria.includes(target.dataset.criterionName);
    target.classList.toggle('bg-green', isValid);
    target.classList.toggle('bg-red', !isValid);
  };

  toggleContainerVisibility (value) {
    this.widgetTarget.classList.toggle('d-none', !value || value.length === 0);
  }

  toggleIsValidText (isPasswordStrong) {
    this.validTextTarget.classList.toggle('d-none', !isPasswordStrong);
    this.invalidTextTarget.classList.toggle('d-none', isPasswordStrong);
  }

  enableDisableForm (isPasswordStrong) {
    const button = this.inputTarget.form.querySelector('button[type="submit"]');
    if (button) {
      button.disabled = !isPasswordStrong;
    }
  }
}
