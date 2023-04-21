import {Controller} from '@hotwired/stimulus'
import Swal from 'sweetalert2';
import {visit} from '@hotwired/turbo';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    confirmButtonText: String,
    cancelButtonText: String,
    customClass: Object,
  };

  swalOptionsBase = {
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#4db95f',
    confirmButtonText: this.confirmButtonTextValue,
    cancelButtonColor: '#cb3c53',
    cancelButtonText: this.cancelButtonTextValue,
    reverseButtons: true,
  }

  confirm(event) {
    event.preventDefault();
    const element = event.currentTarget;
    const message = element.dataset.message;
    const url = element.getAttribute('href');

    Swal.fire({
      text: message,
      ...this.swalOptionsBase,
      ...this.getCustomClasses(this.customClassValue)
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.replace(url);
      }
    })
  }

  getCustomClasses(customClass) {
    return customClass && Object.keys(customClass).length > 0
      ? {customClass, buttonsStyling: false}
      : {}
  }
}
