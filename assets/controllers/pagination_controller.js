import {Controller} from '@hotwired/stimulus'
import {axiosInstance} from '../js/helpers/axios';

export default class extends Controller {

  paginate(event) {
    const button = event.currentTarget;
    const url = button.dataset.url;
    const input = document.getElementsByClassName('ajax-search-input')[0];

    axiosInstance
      .get(`${url}/?word=${input.value}`)
      .then((response) => {
        const listContainer = document.getElementsByClassName('list-container')[0];
        listContainer.innerHTML = response.data.html;
      })
  }
}
