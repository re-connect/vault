export const getSwalDefaultOptions = (message, confirmButtonText, cancelButtonText) => {
  return {
    text: message,
    confirmButtonText: confirmButtonText,
    cancelButtonText: cancelButtonText,
    icon: 'warning',
    reverseButtons: true,
    showCancelButton: true,
    customClass: {
      cancelButton: 'btn btn-outline-black me-1',
      confirmButton: 'btn btn-red text-white ms-1'
    }
  }
}
