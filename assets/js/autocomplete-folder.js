const $ = require('jquery');

$(function() {
  $('#addFolder').click(() => {
    $('#newFolderModal').show(() => {
      $('#re_form_dossiersimple_nom').autocomplete({
        source: $('#re_form_dossiersimple').data('autocomplete')
      });
    });
  });
});
