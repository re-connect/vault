import {Controller} from '@hotwired/stimulus';
import {axiosInstance} from '../js/helpers/axios';
import Swal from "sweetalert2";
import {getSwalDefaultOptions} from "./helpers/swal_helper";

export default class extends Controller {
  static targets = ['icon']
  static values = {
    url: String,
    message: String,
    confirmButtonText: String,
    cancelButtonText: String,
    disableFiredButton: Boolean,
    updatedButtonMessage: String,
  };

  alert(event) {
    const button = event.currentTarget;
    const updatedButtonMessage = this.updatedButtonMessageValue;

    Swal.fire({
      ...getSwalDefaultOptions(
        this.messageValue,
        this.confirmButtonTextValue,
        this.cancelButtonTextValue
      ),
      preConfirm: () => axiosInstance.get(this.urlValue)
        .then(() => {
          if (updatedButtonMessage) {
            button.innerHTML = updatedButtonMessage;
          }
          if (this.disableFiredButtonValue) {
            button.removeAttribute('data-action');
            button.classList.replace('btn-red', 'btn-grey');
            this.iconTarget.classList.replace('text-primary', 'text-grey');
          }
        })
    })
  }
}
