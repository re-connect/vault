import {Controller} from '@hotwired/stimulus';
import {axiosInstance} from '../js/helpers/axios';

export default class extends Controller {
  static values = {url: String};
  static targets = ['icon']

  toggle(event) {
    const icons = this.iconTargets;

    axiosInstance.patch(this.urlValue)
      .then(function () {
        icons.forEach(
          (icon) => icon.dataset.toggleClasses.split(' ').forEach(
            (cssClass) => icon.classList.toggle(cssClass)
          )
        )
      })
      .catch(() => {
        event.target.classList.toggle('bg-red');
      })
  }
}
