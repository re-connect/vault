const $ = require("jquery");

$("#password_show").click(() => {
    const  $plainPassword = $("#fos_user_resetting_form_plainPassword_first");
    const  $secondPassword = $("#fos_user_resetting_form_plainPassword_second");
    const  $divSecondPassword = $("#fos_user_resetting_form_plainPassword_second_div");
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
