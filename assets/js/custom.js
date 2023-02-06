const $ = require('jquery');
require("../../node_modules/jquery-ui-dist/jquery-ui.js");
$.widget.bridge('uitooltip', $.ui.tooltip);

const refreshUploadWidget = () => {
    const $btnUploadFile = $(".btnUploadFile");
    $btnUploadFile.unbind("click");
    $btnUploadFile.bind("click", function () {
        $(this).parent().children("input[type=file]").trigger("click");
        return false;
    });
    const $vichFile = $(".vich-file input[type=file]");
    $vichFile.unbind("change");
    $vichFile.bind("change", function (e) {
        $(this).parent().children(".filenameLabel").html(e.target.files[0].name);
    });
};

export const scrollTo = (event) => {
    const scrollToId = $(event.currentTarget).data('scrollTo');
    $("body,html").animate({ scrollTop: $('#' + scrollToId).offset().top - 79 }, 1200, "easeOutCirc");

    return false;
};

function ajaxCallUrl(event) {
    const $target = $(event.currentTarget);
    const url = $target.data('url');
    if (url) {
        $.ajax({ type: "GET", url });
    }
}

function loadOnSubmit(event) {
    const $loaderContainer = $(event.currentTarget).find('.js-loading-container');
    if ($loaderContainer) {
        $loaderContainer.addClass('disabled').attr("disabled", true).prepend("<i class='fa fa-spinner fa-spin mr-2'></i>");
        setTimeout(() => {
            $loaderContainer.removeClass('disabled').attr("disabled", false);
            $loaderContainer.find('i.fa.fa-spinner').remove();
        }, 1000);
    }
}

const $document = $(document);
$document.ready(function () {
    $document.on('click', '.ajax-call-url', ajaxCallUrl);
    $document.on('submit', 'form', loadOnSubmit);
});

$(function () {
    $("#flashContainer").delay(5000).fadeTo(2000, 0, function () {
        $(this).hide();
    });
    $(".notification .close").click(function () {
        $(this).parent().fadeTo(500, 0, function () {
            $(this).hide();
        });
    });

    $("body").removeClass("preload");

    refreshUploadWidget();

    /**
     * Gestion des tooltips
     */
    $(document).uitooltip({
        classes: {
            "ui-tooltip": ""
        },
        position: { my: "bottom+55", at: "left" },
        open: function (event, target, content) {
            const $el = $(event.originalEvent.target);
            if ($el.is("[data-tooltip='grey']")) {
                target.tooltip.addClass("ui-tooltip-grey ui-widget-content-grey");
            }
            if ($el.is("[data-tooltip='greyLight']")) {
                target.tooltip.addClass("ui-tooltip-greyLight ui-widget-content-greyLight");
            }
        }
    });

    $(document).click(function () {
        $('.ui-tooltip').remove();
    });

    $("#header-handler, #header-handler-close").click(function () {
        $(this).parent().parent().parent().find("nav").toggleClass("opened");
        return false;
    });

    $("#userMenu-button").click(function (event) {
        $("#userMenu").toggleClass("opened");
        if ($("#userMenu").hasClass("opened")) {
            $("#userMenu-button .reDropDown").addClass("opened");
            $("#otherUserMenu-button .reDropDown").removeClass("opened");
            $("#otherUserMenu").removeClass("opened");
            $("#userMenu-button .reDropDown").html("<i class=\"fas fa-chevron-up font-size-1-3\"></i>");

        } else {
            $("#userMenu-button .reDropDown").removeClass("opened");
            $("#userMenu-button .reDropDown").html("<i class=\"fas fa-chevron-down font-size-1-3\"></i>");
        }
        event.stopPropagation();
    });

    $("input.uncorrect").change(function () {
        $(this).removeClass("uncorrect");
    });

    $(".changeLang").change(function () {
        window.location = Routing.generate("re_main_change_lang", { "lang": $(this).val() });
    });
});
