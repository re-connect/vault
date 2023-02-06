const $ = require("jquery");

$(function () {
    const hasNeverClickedMesDocuments = $('#page-beneficiaireAccueilCont').data('hasNeverClickedMesDocuments');
    if(hasNeverClickedMesDocuments) {
        $('#page-beneficiaireAccueilCont a:nth-child(1)').click(function () {
            mixpanel.track("first_visit");
            return true;
        });
    }
})
