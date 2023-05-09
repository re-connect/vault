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
    disaffiliatedMessage: String,
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

  disaffiliate(event) {
    const button = event.currentTarget;

    Swal.fire({
      ...this.swalOptions,
      preConfirm: () => {
        return axiosInstance.get(this.urlValue)
          .then(() => {
            this.iconTarget.classList.replace('text-primary', 'text-grey');
            button.classList.replace('btn-red', 'btn-grey');
            button.innerHTML = this.disaffiliatedMessageValue
            button.removeAttribute('data-action');
          })
      },
    })
  }
}
