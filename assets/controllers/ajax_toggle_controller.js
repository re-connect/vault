import {Controller} from '@hotwired/stimulus';
import {axiosInstance} from '../js/helpers/axios';
import {Tooltip} from "bootstrap-next";

export default class extends Controller {
  static values = {url: String};
  static targets = ['icon', 'switch', 'card'];

  toggle(event) {
    const icons = this.iconTargets;
    const toggleSwitch = this.switchTarget;
    const card = this.cardTarget;

    axiosInstance.patch(this.urlValue)
      .then(function () {
        icons.forEach(
          (icon) => {
            updateTooltipTitle(icon);
            icon.dataset.toggleClasses.split(' ').forEach(
              (cssClass) => icon.classList.toggle(cssClass)
            );
          }
        );

        updateTooltipTitle(toggleSwitch);
          card.dataset.toggleClasses.split(' ').forEach(
          (cssClass) => card.classList.toggle(cssClass)
        );
      })
      .catch(() => {
        event.target.classList.toggle('bg-red');
      });
  }
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
