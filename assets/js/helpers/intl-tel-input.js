import intlTelInput from 'intl-tel-input';
import intlTelInputUtils from 'intl-tel-input/build/js/utils';

const $ = require("jquery");

const preferredCountries = ['fr', 'gb', 'de', 'es', 'it', 'ru', 'af'];
$(document).ready(function () {
    const $inputs = $('input.intl-tel-input');
    $inputs.each(function (index, element) {
        intlTelInput(element, {
            utilsScript: intlTelInputUtils,
            initialCountry: 'fr',
            separateDialCode: true,
            autoPlaceholder: 'aggressive',
            preferredCountries,
            hiddenInput: 'formatted-number'
        });
    });
});
