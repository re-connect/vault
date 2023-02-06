import $ from "jquery";

function refreshAutre() {
    if ($('#questionSecrete').val() == "Autre") {
        $('#lineAutreQuestion').show();
    } else {
        $('#lineAutreQuestion').hide();
    }
}

$(document).ready(function () {
    $('#questionSecrete').change(function () {
        refreshAutre();
    });
    refreshAutre();

    if($('#lineAutreQuestion').val() !== '') {
        $('#re_form_setQuestionSecrete_questionSecrete option:eq(5)').prop('selected', true);
        refreshAutre();
        $('#re_form_setQuestionSecrete_autreQuestionSecrete').val("{{ autreQuestion }}")
    }
});

$("#password_show").click(() => {
    const  $plainPassword = $("#user_plainPassword_first");
    const  $secondPassword = $("#user_plainPassword_second");
    const  $divSecondPassword = $("#user_plainPassword_second_tr");
    const type = $plainPassword.attr('type') === 'text' ? 'password' : 'text';
    $plainPassword.attr('type', type);
    if (type === 'text') {
        $secondPassword.val($plainPassword.val())
        $divSecondPassword.hide();
        $plainPassword.bind('change', function() {
            $secondPassword.val($(this).val());
        });
    } else {
        $plainPassword.unbind('change');
        $divSecondPassword.show();
    }
});
