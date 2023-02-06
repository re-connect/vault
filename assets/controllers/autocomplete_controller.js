import {Controller} from '@hotwired/stimulus'
const $ = require('jquery');
import 'jquery-ui-dist/jquery-ui.js';

export default class extends Controller {
  static values = {source: Array};

  autocomplete(event) {
    $(event.target).autocomplete({
      source: this.sourceValue
    })
  }
}
