const $ = require('jquery');
// import 'jquery-ui-dist';

$(document).ready(function () {
    // add a delete link to all of the existing tag form li elements
    $('#rappel-fields-list').find('li').each(function() {
        addTagFormDeleteLink($(this));
    });

    $('.add-another-collection-widget').click(function (e) {
        var list = $($(this).attr('data-list-selector'));
        // Try to find the counter of the list or use the length of the list
        var counter = list.data('widget-counter') || list.children().length;

        // grab the prototype template
        var newWidget = list.attr('data-prototype');

        newWidget = newWidget.replace(/__name__/g, counter);
        // Increase the counter
        counter++;
        // And store it, the length cannot be used if deleting widgets is allowed
        list.data('widget-counter', counter);

        var $newWidget = $(newWidget);

        var $newElem = $(list.attr('data-widget-tags')).html($newWidget);

        addTagFormDeleteLink($newElem);

        $newElem.appendTo(list);
    });

    function addTagFormDeleteLink($tagFormLi) {
        var $removeFormButton = $('<span class="pull-right"><button type="button" class="btn btn-danger"><span class="glyphicon glyphicon glyphicon-remove" aria-hidden="true"></span></button></span>');
        $tagFormLi.prepend($removeFormButton);

        $removeFormButton.on('click', function(e) {
            $tagFormLi.remove();
        });
    }
});
