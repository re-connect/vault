import $ from "jquery";

function refreshAutre(){
    if($('#re_form_beneficiaire_step3_questionSecrete').val() == "Autre"){
        $('#lineAutreQuestion').show();
        $('#re_form_beneficiaire_step3_autreQuestionSecrete').prop('required',true);
    }
    else{
        $('#lineAutreQuestion').hide();
        $('#re_form_beneficiaire_step3_autreQuestionSecrete').prop('required',false);
    }
}

$(function(){
    $('#re_form_beneficiaire_step3_questionSecrete').change(function(){
        refreshAutre();
    });
    refreshAutre();
});
