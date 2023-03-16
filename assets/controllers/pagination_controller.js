import {Controller} from '@hotwired/stimulus'
import {axiosInstance} from '../js/helpers/axios';

export default class extends Controller {

  paginate(event) {
    const button = event.currentTarget;
    const url = button.dataset.url;
    const ajaxForm = document.querySelectorAll('[data-controller="ajax-list-filter"]')[0];

    axiosInstance
      .post(url, new FormData(ajaxForm))
      .then((response) => {
        const listContainer = document.getElementsByClassName('list-container')[0];
        listContainer.innerHTML = response.data.html;
      })
  }
}
