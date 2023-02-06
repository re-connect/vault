/* ies alert managament (app/Resources/views/ies_alert.html.twig) */
    function GetIEVersion() {
        const sAgent = window.navigator.userAgent;
        const Idx = sAgent.indexOf("MSIE");

        // If IE, return version number.
        if (Idx > 0)
            return parseInt(sAgent.substring(Idx + 5, sAgent.indexOf(".", Idx)));

        // If IE 11 then look for Updated user agent string.
        else if (!!navigator.userAgent.match(/Trident\/7\./))
            return 11;
        else
            return 0; //It is not IE
    }

document.addEventListener("DOMContentLoaded", function (event) {
    const ieVersion = GetIEVersion();
    if (ieVersion > 0) {
        const browserWarning = document.getElementById('browserWarning');
        browserWarning.style.display = 'flex';
        const ie11Warning = document.getElementById('ie11Warning');
        const oldIeWarning = document.getElementById('oldIeWarning');
        if (ieVersion === 11) {
            ie11Warning.style.display = 'inline';
        } else {
            oldIeWarning.style.display = 'inline';
        }
    }
});
