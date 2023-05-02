import {Controller} from '@hotwired/stimulus'
import Swal from 'sweetalert2';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    cancelButtonCustomClass: String,
    cancelButtonText: String,
    confirmButtonCustomClass: String,
    confirmButtonText: String,
    customClass: Object,
    href: String,
    message: String,
  };

  swalDefaultOptions = {
    cancelButtonColor: '#cb3c53',
    confirmButtonColor: '#4db95f',
    icon: 'warning',
    reverseButtons: true,
    showCancelButton: true,
  }

  confirm(event) {
    event.preventDefault();

    const customClass = this.cancelButtonCustomClassValue && this.confirmButtonCustomClassValue ? {
      cancelButton: this.cancelButtonCustomClassValue,
      confirmButton: this.confirmButtonCustomClassValue,
      buttonsStyling: false,
    } : {};

    Swal.fire({
      text: this.messageValue,
      ...this.swalDefaultOptions,
      confirmButtonText: this.confirmButtonTextValue,
      cancelButtonText: this.cancelButtonTextValue,
      customClass,
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.replace(this.hrefValue);
      }
    })
  }
}
