import {Controller} from '@hotwired/stimulus';
import {axiosInstance} from '../js/helpers/axios';
import Swal from "sweetalert2";

export default class extends Controller {
  static targets = ['icon']
  static values = {
    url: String,
    confirmMessage: String,
    confirmButtonText: String,
    cancelButtonText: String,
    disableFiredButton: Boolean,
    updatedButtonMessage: String,
  };
  swalOptions = {
    text: this.confirmMessageValue,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: this.confirmButtonTextValue,
    cancelButtonText: this.cancelButtonTextValue,
    reverseButtons: true,
    customClass: {
      cancelButton: 'btn btn-outline-black me-1',
      confirmButton: 'btn btn-red text-white ms-1'
    },
    buttonsStyling: false,
    showLoaderOnConfirm: true,
  }

  alert(event) {
    const button = event.currentTarget;
    const updatedButtonMessage = this.updatedButtonMessageValue;

    Swal.fire({
      ...this.swalOptions,
      preConfirm: () => {
        return axiosInstance.get(this.urlValue)
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
      },
    })
  }
}
