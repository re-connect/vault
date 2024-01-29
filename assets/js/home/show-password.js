$(document).ready(($) => {
    $('body').on('click', '.show-password', (event) => {
        const $target = $(event.currentTarget);
        const $input = $target.closest('form').find('input.login-form-password-input');
        $input
            .attr('type', $input.hasClass('plain-password') ? 'password' : 'text')
            .toggleClass('plain-password');
        $target.toggleClass('fa-eye').toggleClass('fa-eye-slash');
    })
});