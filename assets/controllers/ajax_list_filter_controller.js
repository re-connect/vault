import {Controller} from '@hotwired/stimulus'
import {axiosInstance} from '../js/helpers/axios';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['form', 'input']

  filter() {
    const form = this.formTarget;
    const inputs = this.inputTargets;
    const queryParams = inputs.map(
      (input) => input.value ? `${input.dataset.inputName}=${input.value}&` : ''
    ).join('')

    axiosInstance
      .get(`${form.getAttribute('action')}?${queryParams}`)
      .then((response) => {
        const listContainer = document.getElementsByClassName('list-container')[0];
        listContainer.innerHTML = response.data;
      })
  }
}
