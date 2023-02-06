const $ = require("jquery");

$("#password_show").click(() => {
    const $plainPassword = $("#_password");
    const type = $plainPassword.attr('type') === 'text' ? 'password' : 'text';
    $plainPassword.attr('type', type);
});
