const $ = require('jquery');

$(document).ready(() => {
    const $reFormBeneficiaireStep2UserPlainPassword = $('#re_form_beneficiaire_step2_user_plainPassword');
    const $repeatPassword = $('#repeatPassword');

    $repeatPassword.val($reFormBeneficiaireStep2UserPlainPassword.val());

    $reFormBeneficiaireStep2UserPlainPassword.keyup(() => {
        $reFormBeneficiaireStep2UserPlainPassword.attr("type", "password");
        $('#repeatPasswordDiv').show();
        $repeatPassword.val("");
        $("#divPasswordShow").show();
        $reFormBeneficiaireStep2UserPlainPassword.unbind("keyup");
    });

    $('form[name="re_form_beneficiaire_step2"]').submit(() => {
        if ($repeatPassword.val() !== $reFormBeneficiaireStep2UserPlainPassword.val()) {
            $('#password-error').css('opacity', 1);
            return false;
        }
    });

    $("#buttonPasswordShow").click(() => {
        const  $plainPassword = $("#re_form_beneficiaire_step2_user_plainPassword");
        const  $secondPassword = $("#repeatPassword");
        const  $divSecondPassword = $("#repeatPasswordDiv");
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
})
