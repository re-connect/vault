import {Controller} from '@hotwired/stimulus';
import $ from 'jquery';

export default class extends Controller {
  connect() {
    $('[data-toggle="tooltip"]').tooltip()
  }
}
