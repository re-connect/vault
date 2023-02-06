'use strict';

require("./base");
const $ = require('jquery');
const angular = require('angular');
require('angular-utils-pagination');
require('angular-dragdrop');
require('angular-loading-bar');
require('angular-animate');
require('../../node_modules/angular-i18n/angular-locale_fr-fr.js');

const app = angular.module("app", ["angularUtils.directives.dirPagination", "ngDragDrop", "angular-loading-bar", "ngAnimate"]);
global.angular = angular;
global.app = app;
require('./angular/re-table');

const beneficiaireId = $("#personalDataBody").data("beneficiaireId");
global.beneficiaireId = beneficiaireId;
