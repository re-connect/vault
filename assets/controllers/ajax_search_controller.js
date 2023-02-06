import {Controller} from '@hotwired/stimulus'
import {axiosInstance} from '../js/helpers/axios';

export default class extends Controller {
  static values = {url: String};

  search(event) {
    if (event.type === 'submit') {
      event.preventDefault();
    } else if (event.type === 'input') {
      const input = event.currentTarget;
      const url = this.urlValue;

      axiosInstance
        .get(`${url}/?word=${input.value}`)
        .then((response) => {
          const listContainer = document.getElementsByClassName('list-container')[0];
          listContainer.innerHTML = response.data.html;
        })
    }
  }
}
