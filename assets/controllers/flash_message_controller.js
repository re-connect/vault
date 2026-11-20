import { Controller } from "@hotwired/stimulus";

// Flash messages stay on screen until the user closes them,
// so that everyone has time to read them.
export default class extends Controller {
  static targets = ['alert'];
}
