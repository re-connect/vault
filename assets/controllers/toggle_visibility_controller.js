import {Controller} from '@hotwired/stimulus';
import {axiosInstance} from '../js/helpers/axios';

export default class extends Controller {
  static values = {url: String};
  static targets = ['lockIcon', 'shareIcon']

  toggle(event) {
    const lockIcon = this.lockIconTarget;
    const shareIcon = this.shareIconTarget;
    const switchButton = event.currentTarget;

    axiosInstance.patch(this.urlValue)
      .then(function () {
        shareIcon.classList.toggle('text-light-grey');
        shareIcon.classList.toggle('text-green');
        lockIcon.classList.toggle('text-light-grey');
        lockIcon.classList.toggle('fa-lock-open');
        lockIcon.classList.toggle('fa-lock');
      })
      .catch(() => {
        switchButton.classList.toggle(
          lockIcon.classList.contains('fa-lock')
            ? 'bg-primary'
            : 'bg-green'
        );
      })
  }
}
