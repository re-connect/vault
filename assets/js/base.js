'use strict';

const $ = require('jquery');
import 'jquery-ui-dist/jquery-ui.js';
import './tooltip-jquery';
import './favicons';
import './language-selector';
import '../bootstrap';
import './helpers/intl-tel-input';

require('bootstrap');
require('./custom');
require('./popup');
require('./select');
require('./radio');
require('./hoverImage');
require('./ies-alert');

$(function () {
    const $contactNotification = $('#contactNotification');
    if ($contactNotification.length) {
        $contactNotification.modal("show");
    }
    const $centerNotification = $('#centerNotification');
    if ($centerNotification.length) {
        $centerNotification.modal("show");
    }
});
