import {Controller} from '@hotwired/stimulus'
import Swal from 'sweetalert2';
import {visit} from '@hotwired/turbo';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    confirmButtonText: String,
    cancelButtonText: String,
  };

  confirm(event) {
    event.preventDefault();
    const element = event.currentTarget;
    const message = element.dataset.message;
    const url = element.getAttribute('href');

    Swal.fire({
      text: message,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#4db95f',
      confirmButtonText: this.confirmButtonTextValue,
      cancelButtonColor: '#cb3c53',
      cancelButtonText: this.cancelButtonTextValue,
      reverseButtons: true,
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.replace(url);
      }
    })
  }
}
