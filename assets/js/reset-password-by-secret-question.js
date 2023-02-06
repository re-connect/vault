const $ = require("jquery");

$("#password_show").click(() => {
    const  $plainPassword = $("#re_form_reset_password_plainPassword_first");
    const  $secondPassword = $("#re_form_reset_password_plainPassword_second");
    const  $divSecondPassword = $("#re_form_reset_password_plainPassword_second_div");
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
