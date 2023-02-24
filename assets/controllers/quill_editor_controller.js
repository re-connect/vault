import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';

export default class extends Controller {

  connect() {
    const input = this.element;
    const editorDiv = this.createEditorDiv(input);

    this.quill = new Quill(editorDiv, {
      theme: 'snow',
      modules: {toolbar: this.getToolbarOptions()},
    });

    this.quill.container.firstChild.innerHTML = input.value;

    this.handleFocusOnEditor();

    this.quill.on('text-change', () => {
      input.value = this.quill.container.firstChild.innerHTML;
    });
  }

  getToolbarOptions() {
    return [
      ['bold', 'italic', 'underline'],
      ['link', {'color': []}],
      [{'list': 'ordered'}, {'list': 'bullet'}],
      ['clean']
    ];
  }

  createEditorDiv(input) {
    const editorDiv = document.createElement('div');
    editorDiv.style.minHeight = '200px'
    input.after(editorDiv);

    return editorDiv;
  }

  handleFocusOnEditor() {
    const qlContainer = document.getElementsByClassName('ql-container')[0];
    const qlEditor = document.getElementsByClassName('ql-editor')[0];
    qlContainer.addEventListener('click', () => {
      qlEditor.focus();
    });
  }
}
