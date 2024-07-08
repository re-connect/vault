import {Controller} from '@hotwired/stimulus'
import {Modal} from 'bootstrap-next';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['modal'];

  open() {
    (new Modal(this.modalTarget)).show();
  }
}
