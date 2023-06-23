import {Controller} from '@hotwired/stimulus'
import {axiosInstance} from '../js/helpers/axios';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['results', 'form', 'input']
  static values = {url: String}

  search() {
    axiosInstance.get(`${this.urlValue}?q=${this.inputTarget.value}`)
      .then(response => this.resultsTarget.innerHTML = response.data);
  }
}
