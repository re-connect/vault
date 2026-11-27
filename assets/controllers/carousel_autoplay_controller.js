import { Controller } from '@hotwired/stimulus';
import { Carousel } from 'bootstrap-next';

/*
 * Pause / resume control for an auto-rotating Bootstrap carousel (RGAA 13.8).
 * Automatic rotation is disabled when the user asks for reduced motion.
 */
export default class extends Controller {
  static targets = ['carousel', 'button', 'icon', 'label'];
  static values = { pauseLabel: String, playLabel: String };

  connect () {
    this.playing = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    this.apply();
    this.buttonTarget.hidden = false;
  }

  disconnect () {
    const carousel = Carousel.getInstance(this.carouselTarget);
    if (carousel) {
      carousel.pause();
      carousel.dispose();
    }
  }

  toggle () {
    this.playing = !this.playing;
    this.apply();
  }

  apply () {
    // Bootstrap keeps its configuration for the lifetime of the instance: the
    // carousel is rebuilt so that hovering or swiping cannot restart a paused rotation.
    this.disconnect();
    Carousel.getOrCreateInstance(this.carouselTarget, { ride: this.playing ? 'carousel' : false });

    this.labelTarget.textContent = this.playing ? this.pauseLabelValue : this.playLabelValue;
    this.iconTarget.classList.toggle('fa-pause', this.playing);
    this.iconTarget.classList.toggle('fa-play', !this.playing);
  }
}
