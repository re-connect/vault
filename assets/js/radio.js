import $ from 'jquery';

$(function () {
    $('.reCheckbox').each(function () {
        const $this = $(this);
        if ($this.prop("checked")) {
            $(this).after('<span class="glyphicon glyphicon-check main re-checkbox-button" aria-hidden="true" data-id="' + $(this).attr('id') + '">&nbsp;</span>');
        } else {
            $(this).after('<span class="glyphicon glyphicon-unchecked main re-checkbox-button" aria-hidden="true" data-id="' + $(this).attr('id') + '">&nbsp;</span>');
        }

        const $reCheckboxButton = $(".re-checkbox-button");
        $reCheckboxButton.unbind('click');
        $reCheckboxButton.bind('click', function (event) {
            const element = $('#' + $(this).attr('data-id'));
			element.prop("checked", !element.prop("checked"));
            element.trigger('change');
        });

        $this.change(function () {
            const checkbox = $(this);
            $reCheckboxButton.each(function () {
                if ($(this).attr('data-id') == $(checkbox).attr('id')) {
                    if ($(this).hasClass("glyphicon-check")) {
                        $(this).switchClass("glyphicon-check", "glyphicon-unchecked");
                    } else {
                        $(this).switchClass("glyphicon-unchecked", "glyphicon-check");
                    }
                }
            })
        })
    });

    $('.reRadio').each(function () {
        var arOptions = $(this).find('option');
        var optionChecked = $(this).find(":checked").parent();

        var parentElement = $(this);

        $(parentElement).find('> li').prepend('<div class="reRadio-button"></div>');
        $(optionChecked).addClass('checked');

        $(parentElement).children("li").unbind('click');
        $(parentElement).children("li").bind('click', function (event) {
            $(this).parent().find('li').removeClass('checked');
            $(this).find('input[type=radio]').prop('checked', true);
            $(this).addClass('checked');
        });
    });
});
