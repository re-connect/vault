import {Controller} from '@hotwired/stimulus'
import Swal from 'sweetalert2';
import {getSwalDefaultOptions} from "./helpers/swal_helper";

/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    url: String,
    message: String,
    confirmButtonText: String,
    cancelButtonText: String,
  };

  confirm(event) {
    event.preventDefault();

    Swal.fire({
      ...getSwalDefaultOptions(
        this.messageValue,
        this.confirmButtonTextValue,
        this.cancelButtonTextValue
      ),
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.replace(this.urlValue);
      }
    })
  }
}
