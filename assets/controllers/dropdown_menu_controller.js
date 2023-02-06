import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
  static targets = ['button', 'menu'];
  static values = {
    toggleClasses: Array,
  };

  toggle() {
    const button = this.buttonTarget;
    const menu = this.menuTarget;

    this.toggleElementsClasses(menu, button);

    if (!menu.classList.contains('d-none')) {
      window.addEventListener('mouseup', (closeEvent) => {
        if (!this.element.contains(closeEvent.target) || menu.contains(closeEvent.target)) {
          this.toggleElementsClasses(menu, button)
        }
      }, {once: true})
    }
  }

  toggleElementsClasses(menu, button) {
    const toggleClasses = this.toggleClassesValue;

    toggleClasses.forEach(toggleClass => button.classList.toggle(toggleClass));
    menu.classList.toggle('d-none');
  }
}
