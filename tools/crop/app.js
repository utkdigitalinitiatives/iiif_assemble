var pid = 'galston%3A700';
var queryString = window.location.search;
var urlParams = new URLSearchParams(queryString);
var pid = urlParams.get('pid');
var external = urlParams.get('external');

var baseUrl = '';

if (pid != '' && pid != null) {
    baseUrl = 'https://digital.lib.utk.edu/iiif/2/collections~islandora~object~' + populatePid + '~datastream~OBJ/info.json';
} else if (external != ''&& external != null) {
    baseUrl = external + '/info.json';
} else {
    baseUrl = 'https://digital.lib.utk.edu/iiif/2/collections~islandora~object~collections:mugwump~datastream~FEATURED/info.json';
    alert('Usage Guide \n\nFor internal Islandora items, input URL as "/assemble/tools/crop/?pid=namespace:id" \nex: https://digital.lib.utk.edu/assemble/tools/crop/?pid=collections:mugwump \n\nFor external items, input URL as "/assemble/tools/crop/?external=URI" \nex: https://digital.lib.utk.edu/assemble/tools/crop/?external=https://cdm16281.contentdm.oclc.org/digital/iiif/p16281coll20/34/info.json \n\n');
}

var map = L.map('map', {
    center: [0, 0],
    crs: L.CRS.Simple,
    zoom: 0,
});

var iiifLayer = L.tileLayer.iiif(baseUrl, {
    tileSize: 512
}).addTo(map);

var areaSelect = L.areaSelect({
    width:300, height:300
});

areaSelect.addTo(map);

$('#urlArea').html(baseUrl)

areaSelect.on('change', function() {
    var bounds = this.getBounds();
    var zoom = map.getZoom();
    var min = map.project(bounds.getSouthWest(), zoom);
    var max = map.project(bounds.getNorthEast(), zoom);
    var imageSize = iiifLayer._imageSizes[zoom];
    var xRatio = iiifLayer.x / imageSize.x;
    var yRatio = iiifLayer.y / imageSize.y;
    var region = [
        Math.floor(min.x * xRatio),
        Math.floor(max.y * yRatio),
        Math.floor((max.x - min.x) * xRatio),
        Math.floor((min.y - max.y) * yRatio)
    ];
    var url = baseUrl + '/' + region.join(',') + '/!1000,1000/0/default.jpg';
    $('#urlArea').html(
        '<a href="' + url + '" target=_blank>' + url + '</a>'
    ),
    $('#region').val(
        region.join(',')
    )
});

function getParameterByName(name, url = window.location.href) {
    name = name.replace(/[\[\]]/g, '\\$&');
    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
        results = regex.exec(url);
    if (!results) return null;
    if (!results[2]) return '';
    return decodeURIComponent(results[2].replace(/\+/g, ' '));
}
