/* Leaflet-Karte mit Cluster + Suche ueber die KuLaDig API. */
document.addEventListener('DOMContentLoaded', function () {
  var mapContainer = document.getElementById('kuladig-map');
  if (!mapContainer || typeof L === 'undefined') {
    return;
  }

  // Karte anlegen (Mitte Deutschland).
  var map = L.map('kuladig-map').setView([51.2, 10.5], 6);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: 'c OpenStreetMap-Mitwirkende'
  }).addTo(map);

  // Marker-Cluster-Gruppe anlegen.
  var clusterGroup = L.markerClusterGroup();
  map.addLayer(clusterGroup);

  // Basis-URL: alle Kuladig-Objekte (ohne Projekt-Filter).
  // Nur dein Projekt? z.B.: + '&Projekt=2085'
  var baseUrl = 'https://www.kuladig.de/api/public/Objekt?ObjektTyp=KuladigObjekt';

  var bounds = [];
  var currentSearch = '';   // aktuell verwendeter Suchbegriff.

  // Meldungs-Element fuer Feedback.
  var msgEl = document.getElementById('kuladig-map-search-message');

  function setMessage(text) {
    if (msgEl) msgEl.textContent = text || '';
  }

  // Eine Seite laden (mit optionalem Suchbegriff).
  function loadPage(page, searchTerm) {
    var url = baseUrl + '&Seite=' + page;

    if (searchTerm && searchTerm.trim() !== '') {
      // PARAMETERNAME ggf. in deiner API-Doku checken.
      url += '&Suchbegriff=' + encodeURIComponent(searchTerm.trim());
    }

    fetch(url)
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (!json || !json.Ergebnis || !json.Ergebnis.length) {
          return;
        }

        json.Ergebnis.forEach(function (objekt) {
          // neue API-Struktur: Punktkoordinate -> GeoJSON-aehnlich.
          if (!objekt.Punktkoordinate || !objekt.Punktkoordinate.coordinates) return;

          var lon = objekt.Punktkoordinate.coordinates[0];
          var lat = objekt.Punktkoordinate.coordinates[1];
          if (typeof lat !== 'number' || typeof lon !== 'number') return;

          var name = objekt.Name || '';
          var id   = objekt.Id   || '';

          // Marker erzeugen.
          var marker = L.marker([lat, lon]);

          // Popup mit Titel + Link zur Detailseite.
          var popupHtml =
            '<strong>' + name + '</strong><br>' +
            '<a href="/ort/?id=' + encodeURIComponent(id) + '">Mehr anzeigen</a>';

          marker.bindPopup(popupHtml);

          // Marker in die Cluster-Gruppe.
          clusterGroup.addLayer(marker);

          bounds.push([lat, lon]);
        });

        // weitere Seiten laden?
        var aktuelleSeite = json.Seite || 0;
        var anzahlSeiten  = json.AnzahlSeiten || 1;
        var naechsteSeite = aktuelleSeite + 1;

        if (naechsteSeite < anzahlSeiten) {
          loadPage(naechsteSeite, searchTerm);
        } else {
          // alle Seiten geladen -> auf alle Marker zoomen.
          if (bounds.length) {
            map.fitBounds(bounds, { padding: [30, 30] });
          }
          setMessage(json.Ergebnis.length + ' Treffer auf dieser Seite geladen.');
        }
      })
      .catch(function (err) {
        console.error('Fehler beim Laden der KuLaDig-Daten:', err);
        setMessage('Fehler beim Laden der Daten.');
      });
  }

  // Neue Suche starten: Cluster leeren, Bounds resetten, Seite 0 laden.
  function startSearch(term) {
    currentSearch = term || '';
    bounds = [];
    clusterGroup.clearLayers();
    setMessage('Lade Daten ...');
    loadPage(0, currentSearch);
  }

  // Suchformular anbinden.
  var searchForm = document.getElementById('kuladig-map-search-form');
  if (searchForm) {
    searchForm.addEventListener('submit', function (e) {
      e.preventDefault(); // verhindert Seiten-Reload.
      var input = document.getElementById('kuladig-map-search-input');
      var term = input ? input.value : '';
      startSearch(term);
    });
  }

  // Initial: alle Objekte laden (ohne Suchbegriff).
  startSearch('');
});
