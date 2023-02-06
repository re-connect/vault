import 'bootstrap-next';
import '../language-selector';
import './show-password';
import './openstreetmap-marker-generator';
import '../helpers/intl-tel-input';
import '../../bootstrap';

function ajaxCallUrl(event) {
    const $target = $(event.currentTarget);
    const url = $target.data('url');
    if (url) {
        $.ajax({ type: "GET", url });
    }
}

const $document = $(document);
$document.ready(function () {
    $document.on('click', '.ajax-call-url', ajaxCallUrl);
});
