import {Controller} from '@hotwired/stimulus'
import {axiosInstance} from '../js/helpers/axios';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

  connect() {
    const form = this.element;
    const inputs = form.querySelectorAll('input, select');

    form.addEventListener('submit', (event) => event.preventDefault());
    inputs.forEach(input => input.addEventListener('input', () => this.submitForm(form), false))
  }

  submitForm(form) {
    axiosInstance
      .post(form.getAttribute('action'), new FormData(form))
      .then((response) => {
        const listContainer = document.getElementsByClassName('list-container')[0];
        listContainer.innerHTML = response.data.html;
      })
  }
}
