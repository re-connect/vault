const $ = require('jquery');
require("../../node_modules/jquery-ui-dist/jquery-ui.js");
import('./tooltip-jquery');
import 'bootstrap';
import './slideshow';
import {scrollTo} from './custom';

require('../../node_modules/waypoints/lib/jquery.waypoints.js');

$(function () {
    /**
     * Show/hide login password
     */
    $("#showPassword").click(() => {
        const $plainPassword = $("#_password");
        const type = $plainPassword.attr('type') === 'text' ? 'password' : 'text';
        $plainPassword.attr('type', type);
    });

    /**
     * Waypoint
     */
    $('#home-quiSommesNous').waypoint(function (direction) {
        if (direction === "down") {
            var element = $('#' + this.element.id);
            $(element).addClass('scrolled');
        }
    }, {
        offset: '70%'
    });

    $('#home-commentCaMarche').waypoint(function () {
            $('#home-commentCaMarche .step:nth-child(1) img').toggleClass('animated swing');
            setTimeout(function () {
                $('#home-commentCaMarche .step:nth-child(2) img').toggleClass('animated swing');
            }, 400);
            setTimeout(function () {
                $('#home-commentCaMarche .step:nth-child(3) img').toggleClass('animated swing');
            }, 800);
            setTimeout(function () {
                $('#home-commentCaMarche .step:nth-child(4) img').toggleClass('animated swing');
            }, 1200);
            setTimeout(function () {
                $('#home-commentCaMarche .step:nth-child(5) img').toggleClass('animated swing');
            }, 1600);
        },
        {
            offset: '80%',
            triggerOnce: true
        });

    $(window).scroll(function () {
        if ($(this).scrollTop() >= $('#home-commentCaMarche').offset().top - 77) {
            $('#header').addClass('scrollPassed');
        } else {
            $('#header').removeClass('scrollPassed');
        }
    });
    $(window).scroll();

    $('body').on('click', '.scrollTo', scrollTo);

    $('#home-connexion button').click(function () {
        mixpanel.track("Connexion clic");
    });
});
