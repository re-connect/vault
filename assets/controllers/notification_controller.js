import {Controller} from '@hotwired/stimulus'
import {Modal} from 'bootstrap-next';
import {axiosInstance} from '../js/helpers/axios';
import {visit} from '@hotwired/turbo';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['form']

  connect() {
    (new Modal(this.element)).show();
  }

  submitForm() {
    const form = this.formTarget;

    axiosInstance
      .post(form.getAttribute('action'), new FormData(form))
      .then(() => {
        visit(window.location);
      });
  }
}
