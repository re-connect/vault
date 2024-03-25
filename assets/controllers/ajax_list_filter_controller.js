import {Controller} from '@hotwired/stimulus'
import {axiosInstance} from '../js/helpers/axios';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['form', 'input'];

  connect () {
    this.formTarget.addEventListener('submit', event => event.preventDefault());
  }

  filter () {
    const form = this.formTarget;
    const inputs = this.inputTargets;
    const formAction = form.getAttribute('action');

    axiosInstance
      .get(`${formAction}${this.getQuerySeparator(formAction)}${this.getQueryParams(inputs)}`)
      .then((response) => {
        const listContainer = document.getElementsByClassName('list-container')[0];
        listContainer.innerHTML = response.data;
      });
  }

  getQueryParams (inputs) {
    return inputs.map(
      (input) => input.value ? `${input.dataset.inputName}=${input.value}&` : ''
    ).join('');
  }

  getQuerySeparator (formAction) {
    return formAction.substring(formAction.lastIndexOf('/') + 1).includes('?')
      ? '&'
      : '?';
  }
}
