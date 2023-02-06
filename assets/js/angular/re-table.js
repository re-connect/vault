'use strict';

// import Routing from '../../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
//
// const routes = require('../../../public/js/fos_js_routes.json');
// Routing.setRoutingData(routes);
require("../../components/Routing");

//Êtes vous sûrs
angular.module("app").directive("ngReallyClick", [function () {
    return {
        restrict: "A",
        link: function (scope, element, attrs) {
            element.bind("click", function () {
                const message = attrs.ngReallyMessage;
                if (message && confirm(message)) {
                    scope.$apply(attrs.ngReallyClick);
                }
            });
        }
    };
}]);

app.run(function ($rootScope) {
    $rootScope.getRoute = function (route, param) {
        let paramObject;
        if (param instanceof Object) {
            paramObject = param;
        } else {
            paramObject = {id: param};
        }
        return Routing.generate(route, paramObject);
    };

    $rootScope.isLoadedAndEmpty = function (ar) {
        if (typeof ar === "undefined") {
            return false;
        }
        return ar.length === 0;
    };
    $rootScope.isLoadedAndNotEmpty = function (ar) {
        if (typeof ar === "undefined") {
            return false;
        }
        return ar.length !== 0;
    };
});

app.filter("num", function () {
    return function (input) {
        return parseInt(input, 10);
    };
});
