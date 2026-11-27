import _ from 'lodash';
import Popper from 'popper.js';
import jQuery from 'jquery';
import axios from 'axios';

window._ = _;

/**
 * jQuery and the Bootstrap jQuery plugins provide the navbar toggler and the
 * user dropdown. Bootstrap 4 looks for window.jQuery and window.Popper, so
 * they are set before its plugins load (see app.js).
 */
window.Popper = Popper;
window.$ = window.jQuery = jQuery;

/**
 * Axios sends the CSRF token and the X-Requested-With header with every request.
 */
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}
