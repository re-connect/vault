import $ from 'jquery';
import 'select2';

$('select.has-select2').select2();

function resetWidth(element) {

    var maxWidth = 0;
    $(element).find(".reSelect-childs").addClass("opened");
    $(element).find(".reSelect-childs li").each(function () {
        if (parseInt($(this).width()) > maxWidth) {
            maxWidth = parseInt($(this).width());
        }
    });

    $(element).find(".reSelect-title").css("min-width", (maxWidth + 40) + "px");
    $(element).find(".reSelect-childs").removeClass("opened");
}

$(document).ready(function () {

    $("select.reSelect").each(function () {
        var arOptions = $(this).find("option");
        var optionSelected = $(this).find(":selected");

        var classes = "reSelectContainer";
        if ($(this).hasClass("reSelect-grey")) {
            classes = classes + ' reSelectContainer-grey';
        }
        var element = $(this).wrap("<div class='" + classes + "'></div>").parent();

        var strAppend = "";
        arOptions.each(function () {
            if ($(this).val() === optionSelected.val()) {
                strAppend = "<li class=\"selected\" data-choice=\"" + $(this).val() + "\">" + $(this).text() + "</li>" + strAppend;
            } else {
                strAppend = "<li data-choice=\"" + $(this).val() + "\">" + $(this).text() + "</li>" + strAppend;
            }
        })

        var strHandle = "";
        if (!$(this).hasClass("reSelect-grey")) {
            strHandle = "<div class=\"reSelect-handle\"></div>";
        }
        $(element).append("<div class=\"reSelect-title\">" + optionSelected.text() + "</div>" + strHandle + "<br/><ul class=\"reSelect-childs opened\">" + strAppend + "</ul></div><div class=\"reSelect-handle>aega</div>");


        $(".reSelect-title, .reSelect-handle").unbind("click");
        $(".reSelect-title, .reSelect-handle").bind("click", function (event) {
            $(this).parent().children(".reSelect-childs").toggleClass("opened");
            event.stopPropagation();
        });
        $(".reSelect-childs li").unbind("click");
        $(".reSelect-childs li").bind("click", function (event) {
            $(this).parent().removeClass("opened");
            $(this).parent().parent().children(".reSelect-title").html($(this).html());
            $(this).parent().children("li").removeClass("selected");
            $(this).addClass("selected");
            var element = $(this);

            $(this).parent().parent().find(".reSelect option").each(function () {
                if ($(this).val() === $(element).attr("data-choice")) {
                    $(this).attr("selected", "selected");
                    $(this).trigger("change");
                } else {
                    $(this).attr("selected", false);
                }
            });
            event.stopPropagation();
            resetWidth(element.parent().parent());
        });

        resetWidth(element);
    });

    $(document).click(function () {
        $(".reSelect-childs").removeClass("opened");
    });
});