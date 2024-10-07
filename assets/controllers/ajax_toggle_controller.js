import {Controller} from '@hotwired/stimulus';
import {axiosInstance} from '../js/helpers/axios';
import {Tooltip} from "bootstrap-next";
import Swal from 'sweetalert2';
import {getSwalDefaultOptions} from "./helpers/swal_helper";

export default class extends Controller {
  static values = {url: String};
  static targets = ['icon', 'switch']

  connect() {
    const toggleSwitch = this.switchTarget;
    if (toggleSwitch.dataset.confirmToggleMessage) {
      toggleSwitch.addEventListener('click', event => {
        event.preventDefault();
        this.toggle(event)
      })
    }
  }

  toggle(event) {
    const icons = this.iconTargets;
    const toggleSwitch = this.switchTarget;
    const url = this.urlValue;
    const confirmMessage = this.switchTarget.dataset.confirmToggleMessage;

    confirmMessage ? askToggleVisibilty(event, icons, toggleSwitch, url, confirmMessage) : toggleVisibility(event, icons, toggleSwitch, url)
  }
}

function askToggleVisibilty(event, icons, toggleSwitch, url, confirmMessage) {
  Swal.fire({
    ...getSwalDefaultOptions(
      confirmMessage,
      toggleSwitch.dataset.confirmText,
      toggleSwitch.dataset.confirmButtonColor,
      toggleSwitch.dataset.cancelText,
    ),
  }).then((result) => {
    if (result.isConfirmed && toggleVisibility(event, icons, toggleSwitch, url)) {
      const nextConfirmMessage = toggleSwitch.dataset.nextConfirmToggleMessage

      toggleSwitch.dataset.nextConfirmToggleMessage = confirmMessage;
      toggleSwitch.dataset.confirmToggleMessage = nextConfirmMessage;
      event.target.checked = !event.target.checked
    }
  })
}

function toggleVisibility(event, icons, toggleSwitch, url) {
  return axiosInstance.patch(url)
    .then(() => {
      icons.forEach(
        (icon) => {
          updateTooltipTitle(icon);
          icon.dataset.toggleClasses.split(' ').forEach(
            (cssClass) => icon.classList.toggle(cssClass)
          );
        }
      );

      updateTooltipTitle(toggleSwitch);

      return true;
    })
    .catch(() => {
      event.target.classList.toggle('bg-red');

      return false;
    })
}

function updateTooltipTitle(element) {
  const tooltip = Tooltip.getInstance(element);
  const nextTooltipTitle = element.dataset.nextToggleTooltip;

  if (tooltip) {
    const currentTooltip = tooltip._config.title;
    tooltip._config.title = nextTooltipTitle;
    element.dataset.nextToggleTooltip = currentTooltip
    tooltip.update();
  } else {
    new Tooltip(element, {
      trigger: 'hover',
      container: element.parentNode,
      title: nextTooltipTitle,
    });
  }
}
