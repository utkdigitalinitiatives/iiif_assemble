var pid = 'galston%3A700';
var queryString = window.location.search;
var urlParams = new URLSearchParams(queryString);
var populatePid = urlParams.get('pid');

if (populatePid != '') {
    pid = populatePid;
} else {
    pid = null;
    alert('No PID  is currently set.');
}

var baseUrl = 'https://digital.lib.utk.edu/iiif/2/collections~islandora~object~' + pid + '~datastream~OBJ';
var map = L.map('map', {
    center: [0, 0],
    crs: L.CRS.Simple,
    zoom: 0,
});

var iiifLayer = L.tileLayer.iiif(baseUrl + '/info.json', {
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
    $('#region').html(
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
