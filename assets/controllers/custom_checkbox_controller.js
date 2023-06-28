import { Controller } from '@hotwired/stimulus';
import axios from 'axios';

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    errorText: String,
    togglableClasses: Array
  };

  toggleClasses (target) {
    this.togglableClassesValue.forEach(className => target.classList.toggle(className));
  }

  click (event) {
    const target = event.currentTarget;
    this.toggleClasses(target);
    if (!event.params.path) {
      return;
    }

    axios.get(event.params.path)
      .catch(error => {
        this.toggleClasses(target);
        if (error.response.status >= 500) {
          alert(this.errorTextValue);
        }
      });
  }
}
