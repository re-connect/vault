$(document).ready(($) => {
    const origin = window.location.origin;
    $('.js-language-selector-click').click(function (event) {
        $(event.currentTarget).closest('.js-language-selector').find('.js-language-selector-show').toggleClass('d-none');
    })
    $('.js-language-selector-change-lang').click(function (event) {
        const newLanguage = $(event.currentTarget).data('language');
        window.location.replace(`${origin}/changer-langue/${newLanguage}`)
    })
});
