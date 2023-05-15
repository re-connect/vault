import {Controller} from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static targets = ['input'];
  characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

  generate() {
    this.inputTarget.value = this.getGeneratedPassword(8);
  }

  getGeneratedPassword(length) {
    return [...Array(length)]
      .reduce((acc) => acc + this.getRandomChar(), '');
  }

  getRandomChar = () => this.characters.charAt(Math.floor(Math.random() * this.characters.length));
}
