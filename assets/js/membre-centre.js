"use strict";

require('./app.js');
const angular = require('angular');
require( 'angular-utils-pagination');
require( 'angular-dragdrop');
require( 'angular-loading-bar');
require( 'angular-animate');

const app = angular.module("app", ["angularUtils.directives.dirPagination", "ngDragDrop", "angular-loading-bar", "ngAnimate"]);
global.angular = angular;
global.app = app;
require('./angular/re-table');
require("./angular/membre");

// $('[rel=tooltip]').tooltip('disable')
// $(function () {
//     $( document ).tooltip();
//     // $('[rel=tooltip]').tooltip();
// })

// $(document).ready(function() {
//     $( document ).tooltip();
    // console.log('ici');
    // $('[data-toggle="tooltip"]').tooltip();
// });