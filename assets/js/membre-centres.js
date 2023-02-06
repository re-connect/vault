const $ = require('jquery');
$(document).ready(function () {
    var centres = $("#centres").data('centres');
    for (var centre in centres) {
        if (centre.adresse && centre.adresse.lat !== null && centre.adresse.lng !== null) {
            var center = new google.maps.LatLng(centre.adresse.lat, centre.adresse.lng);
            var mapOptions = {
                zoom: 11,
                center: center,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                streetViewControl: false,
                scrollwheel: true,
                mapTypeControl: false,
                zoomControl: false
            };
            window["map_" + centre.id] = new google.maps.Map(document.getElementById('map_{{centre.id}}'), mapOptions);


            window["image_" + centre.id] = {
                url: require('../images/home/mapMarker.png'),
                size: new google.maps.Size(23, 31),
                origin: new google.maps.Point(0, 0),
                anchor: new google.maps.Point(11, 31)
            };

            window["marker_" + centre.id] = new google.maps.Marker({
                position: center,
                map: window["map_" + centre.id],
                title: 'Carte',
                icon: window["image_" + centre.id]
            });

            window["contentString_" + centre.id] = '<div id="content">' +
                '<h1 class="uppercase mid italic bold main smallMargin">' + centre.nom + '</h1>' +
                '<div class="standard">' +
                '<p><i class="fa fa-fw fa-home main"></i>' + centre.adresse + '</p>';

            if (centre.telephone !== null && centre.telephone !== "") {
                window["contentString_" + centre.id] += '<p><i class="fa fa-fw fa-phone main">&nbsp;</i>' + centre.telephone + '</p>';
            }
            window["contentString_" + centre.id] += '</div></div>';

            window["infowindow_" + centre.id] = new google.maps.InfoWindow({
                content: window["contentString_" + centre.id]
            });

            google.maps.event.addListener(window["marker_" + centre.id], 'click', function () {
                window["infowindow_" + centre.id].open(window["map_" + centre.id], window["marker_" + centre.id]);
            });
        }
    }
});