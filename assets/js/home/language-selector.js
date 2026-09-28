const $document = $(document);

// Delegated on document so handlers survive Turbo Drive body swaps
$document.on('click', '.js-language-selector-click', function (event) {
    $(event.currentTarget).closest('.js-language-selector').find('.js-language-selector-show').toggleClass('d-none');
});

$document.on('click', '.js-language-selector-change-lang', function (event) {
    const newLanguage = $(event.currentTarget).data('language');
    window.location.replace(`${window.location.origin}/public/changer-langue/${newLanguage}`);
});
