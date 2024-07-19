import {Controller} from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['input', 'img'];

  update() {
    this.imgTarget.src = require('../images/appV2/folderIcons/' + this.inputTarget.value + '.png');
  }
}
