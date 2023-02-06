const $ = require('jquery');
import './angular/document';
import './elevate-zoom';
import "blueimp-file-upload/js/vendor/jquery.ui.widget.js";
import "blueimp-file-upload/js/jquery.iframe-transport.js";
import "blueimp-file-upload/js/jquery.fileupload.js";
import "blueimp-file-upload/js/jquery.fileupload-process.js";

$(function () {
    const beneficiaryId = $("#personalDataBody").data("beneficiaireId");
    $('#fileupload').fileupload({
        url: Routing.generate("api_document_upload", {beneficiaryId: beneficiaryId}),
        singleFileUploads: false,
        maxNumberOfFiles: 10,
        success: function (response) {
            let errorMessage = "";
            let successMessage = "";
            response.files.forEach(function (file) {
                console.log(file.nom);
                if (file.error) {
                    errorMessage += file.nom + ":&nbsp;" + file.error + ".<br />";
                } else {
                    successMessage += file.nom + ":&nbsp;document&nbsp;bien&nbsp;téléchargé.<br />";
                }
            });
            if (errorMessage) {
                $("#alertDangerMessage").append(errorMessage);
                // $("#alertDanger").show();
                $("#alertDanger").fadeTo(2000, 500);
            }
            if (successMessage) {
                $("#alertSuccessMessage").append(successMessage);
                $("#alertSuccess").fadeTo(2000, 500);
                angular.element($("#DocumentsListCtrl")).scope().refresh();
                setTimeout(function () {
                    // $(".alert").alert('close');
                    $("#alertSuccess").slideUp(500, function () {
                        $("#alertSuccessMessage").html('');
                    });
                }, 5000);
            }
        },
        start: function () {
            $("#alertDanger").slideUp(500, function () {
                $("#alertDangerMessage").html('');
            });
        },
    });
});
