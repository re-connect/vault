import {Controller} from '@hotwired/stimulus'
import PasswordHelper from "./helpers/PasswordHelper";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['input'];

  generate() {
    this.inputTarget.value = new PasswordHelper(9).generate();
  }
}
