import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap-next';

export default class extends Controller {
  static values = { title: String };
  tooltip = null;
  focusTarget = null;
  describesFocusTarget = false;
  forceHide = false;

  connect () {
    this.tooltip = Tooltip.getOrCreateInstance(this.element, {
      trigger: 'hover focus',
      container: this.element.parentNode,
      title: this.titleValue,
      delay: { show: 0, hide: 200 },
    });

    // Tooltip set on an icon inside a button or a link: show it when that control gets the focus.
    const focusTarget = this.element.parentElement?.closest('button, a[href]');
    if (focusTarget) {
      this.focusTarget = focusTarget;
      this.focusTarget.addEventListener('focusin', this.showOnFocus);
      this.focusTarget.addEventListener('focusout', this.hideOnBlur);
    }

    this.element.addEventListener('hide.bs.tooltip', this.keepWhileHoveredOrFocused);
    this.element.addEventListener('shown.bs.tooltip', this.describeFocusTarget);
    this.element.addEventListener('hidden.bs.tooltip', this.undescribeFocusTarget);
    document.addEventListener('keydown', this.hideOnEscape);
  }

  disconnect () {
    document.removeEventListener('keydown', this.hideOnEscape);
    if (this.focusTarget) {
      this.focusTarget.removeEventListener('focusin', this.showOnFocus);
      this.focusTarget.removeEventListener('focusout', this.hideOnBlur);
      this.undescribeFocusTarget();
    }
  }

  showOnFocus = () => {
    this.tooltip.show();
  };

  hideOnBlur = (event) => {
    if (!this.focusTarget.contains(event.relatedTarget)) {
      this.tooltip.hide();
    }
  };

  hideOnEscape = (event) => {
    if (event.key !== 'Escape' || !this.isShown()) {
      return;
    }
    this.forceHide = true;
    this.tooltip.hide();
    this.forceHide = false;
  };

  // The tooltip stays visible while the pointer is over it or while the control keeps the focus.
  keepWhileHoveredOrFocused = (event) => {
    if (event.target !== this.element || this.forceHide) {
      return;
    }
    const tip = this.tooltip.tip;
    if (tip && tip.matches(':hover')) {
      event.preventDefault();
      tip.addEventListener('mouseleave', this.hideAfterLeavingTip, { once: true });

      return;
    }
    if (this.focusTarget && this.focusTarget.contains(document.activeElement)) {
      event.preventDefault();
    }
  };

  hideAfterLeavingTip = () => {
    const control = this.focusTarget ?? this.element;
    if (!this.element.matches(':hover') && !control.contains(document.activeElement)) {
      this.tooltip.hide();
    }
  };

  describeFocusTarget = (event) => {
    if (event.target !== this.element || !this.focusTarget) {
      return;
    }
    const tipId = this.element.getAttribute('aria-describedby');
    const tipText = this.tooltip.tip?.textContent.trim();
    // No description when the control is already named with the same text.
    if (tipId && this.focusTarget.getAttribute('aria-label') !== tipText) {
      this.focusTarget.setAttribute('aria-describedby', tipId);
      this.describesFocusTarget = true;
    }
  };

  undescribeFocusTarget = (event) => {
    if ((event && event.target !== this.element) || !this.describesFocusTarget) {
      return;
    }
    this.focusTarget.removeAttribute('aria-describedby');
    this.describesFocusTarget = false;
  };

  isShown () {
    const tip = this.tooltip?.tip;

    return Boolean(tip && tip.isConnected && tip.classList.contains('show'));
  }
}
