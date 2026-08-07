import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Axios normally proves CSRF by reading the XSRF-TOKEN cookie and echoing it
// back. Firebase Hosting forwards only a cookie named "__session" and drops
// everything else, so that cookie never reaches the browser in production and
// every axios write would come back 419 — task status changes, inline edits.
//
// Take the token from the meta tag the layout already renders instead. It does
// not depend on a second cookie surviving the CDN, and it is the same source
// the fetch() calls in app.js use.
const csrf = document.querySelector('meta[name=csrf-token]')?.content;
if (csrf) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf;
}
