import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';

export default class extends Controller {

  connect() {
    const input = this.element;
    const editorDiv = this.createEditorDiv(input);

    this.quill = new Quill(editorDiv, {
      theme: 'snow',
      modules: {
        toolbar: this.getToolbarOptions(),
        keyboard: {bindings: this.getKeyboardBindings()},
      },
    });

    this.quill.clipboard.dangerouslyPasteHTML(input.value)

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

  // Let Tab and Shift+Tab move the focus out of the editor instead of inserting
  // a tab or changing the list indentation, so keyboard users are not trapped.
  getKeyboardBindings() {
    return {
      'tab': null,
      'remove tab': null,
      'indent': null,
      'outdent': null,
      'indent code-block': null,
      'outdent code-block': null,
      'table tab': null,
    };
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
