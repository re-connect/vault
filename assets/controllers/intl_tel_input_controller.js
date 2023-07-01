import { Controller } from '@hotwired/stimulus';
import intlTelInput from 'intl-tel-input';
import utilsScript from 'intl-tel-input/build/js/utils';

const preferredCountries = ['fr', 'gb', 'de', 'es', 'it', 'ru', 'af'];

export default class extends Controller {
  static targets = ['input'];

  connect() {
    intlTelInput(this.inputTarget, {
      autoPlaceholder: 'aggressive',
      hiddenInput: 'formatted-number',
      initialCountry: 'fr',
      preferredCountries,
      separateDialCode: true,
      utilsScript,
    });
  }
}
