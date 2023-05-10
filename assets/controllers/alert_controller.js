import {Controller} from '@hotwired/stimulus'
import Swal from 'sweetalert2';
import {getSwalDefaultOptions} from "./helpers/swal_helper";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    url: String,
    message: String,
    confirmButtonText: String,
    confirmButtonCustomClass: String,
    cancelButtonText: String,
    cancelButtonCustomClass: String,
    customClass: Object,
  };

  confirm(event) {
    event.preventDefault();

    const customClass = this.cancelButtonCustomClassValue && this.confirmButtonCustomClassValue ? {
      cancelButton: this.cancelButtonCustomClassValue,
      confirmButton: this.confirmButtonCustomClassValue,
      buttonsStyling: false,
    } : {};

    Swal.fire({
      ...getSwalDefaultOptions(
        this.messageValue,
        this.confirmButtonTextValue,
        this.cancelButtonTextValue
      ),
      cancelButtonColor: '#cb3c53',
      confirmButtonColor: '#4db95f',
      customClass,
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.replace(this.urlValue);
      }
    })
  }
}
