import { Controller } from '@hotwired/stimulus'
import Sortable from 'sortablejs';
import { visit } from '@hotwired/turbo';
import { Routing } from '../components/Routing';

export default class extends Controller {
  static targets = ['documentList', 'folderList', 'folder']

  connect() {
    const draggableLists = [this.folderListTarget, this.documentListTarget];
    draggableLists.forEach((el) => {
      Sortable.create(el, {
        draggable: ".draggable",
        group: "default",
        sort: false,
        onMove: function (event) {
          toggleDroppableClasses();
          Sortable.utils.toggleClass(event.to, 'current-droppable', true);
        },
        onEnd: function () {
          toggleDroppableClasses();
        }
      })
    })

    this.folderTargets.forEach((folder) => {
      Sortable.create(folder, {
        draggable: ".draggable",
        group: "default",
        sort: false,
        ghostClass: "d-none",
        onAdd: function (event) {
          const itemId = event.item.dataset.id;
          const folderId = event.target.dataset.id;
          const route = event.item.dataset.type === 'document'
            ? 'document_move_to_folder'
            : 'folder_move_to_folder';
          Sortable.utils.css(event.item, 'display', 'none');

          visit(Routing.generate(route, {'id': itemId, 'folderId': folderId}));
        },
      });
    })

    const toggleDroppableClasses = () => {
      document.getElementsByClassName('current-droppable').forEach(
        el => el.classList.toggle('current-droppable')
      );
    }
  }
}
