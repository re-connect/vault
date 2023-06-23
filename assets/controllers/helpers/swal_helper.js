export const getSwalDefaultOptions = (message, confirmButtonText, cancelButtonText) => {
  return {
    text: message,
    confirmButtonText: confirmButtonText,
    cancelButtonText: cancelButtonText,
    iconHtml: '<i class="fas fa-exclamation-triangle text-primary"></i>',
    reverseButtons: true,
    showCancelButton: true,
    buttonsStyling: false,
    showLoaderOnConfirm: true,
    customClass: {
      cancelButton: 'btn btn-light-black text-decoration-underline me-1',
      confirmButton: 'btn btn-red text-white ms-1',
      icon: 'border-0',
      htmlContainer: 'text-primary'
    }
  }
}
