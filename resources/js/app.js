/**
 * Load required plugins.
 */
window.$ = window.jQuery = require('jquery');
window.Popper = require('@popperjs/core');
window.bootstrap = require('bootstrap');
window.Cookies = require('js-cookie');
window.moment = require('moment');

window.Swal = require('sweetalert2/dist/sweetalert2.js');
require('js-cookie');
require('metismenu');
window.Dropzone = require("dropzone");

require('./partials/request');
require('./partials/loader.bar');
require('./partials/loader.progress');
require('./partials/flashmessage');
require('./partials/toggler');
require('./partials/trigger');
require('./partials/selectlist');
require('./partials/utils');
