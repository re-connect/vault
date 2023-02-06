import $ from 'jquery';

$(document).ready(function () {
    $(".hoverImage").each(function () {
        var actUrl = $(this).attr("data-actUrl");
        var width = $(this).attr("data-width");
        $(this).wrap("<div class=\"relative inbl\"></div>");
        if (!width) {
            $(this).parent().append("<img alt=\"\" class=\"hoverImageColored\" src=\"" + actUrl + "\"/>");
        } else {
            $(this).parent().append(`<img style="width: ${width}px" alt="" class="hoverImageColored" src="${actUrl}"/>`);
        }
    });
});