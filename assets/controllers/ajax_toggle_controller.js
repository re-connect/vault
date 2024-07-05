import {Controller} from '@hotwired/stimulus';
import {axiosInstance} from '../js/helpers/axios';
import {Tooltip} from "bootstrap-next";

export default class extends Controller {
  static values = {url: String};
  static targets = ['icon', 'switch']

  toggle(event) {
    const icons = this.iconTargets;
    const toggleSwitch = this.switchTarget;

    axiosInstance.patch(this.urlValue)
      .then(function () {
        icons.forEach(
          (icon) => icon.dataset.toggleClasses.split(' ').forEach(
            (cssClass) => icon.classList.toggle(cssClass)
          )
        )

        const tooltip = Tooltip.getInstance(toggleSwitch)
        const currentTooltip = tooltip._config.title;
        tooltip._config.title = toggleSwitch.dataset.nextToggleTooltip;
        toggleSwitch.dataset.nextToggleTooltip = currentTooltip
        tooltip.update();
      })
      .catch(() => {
        event.target.classList.toggle('bg-red');
      })
  }
}
