<?php

/**
 * KuLaDig Ort Details
 */
/**
 * Shortcode: [kuladig_ort_design]
 * Seite /ort/ enthält NUR diesen Shortcode
 * Aufruf: /ort/?id=KLD-xxxx
 */

/** --- BBCode/KuLaDig-Text grob säubern -> HTML --- */
function kuladig_bbcode_to_html($text) {
  $text = (string)$text;

  // 1) Author-Tags entfernen (nicht abschneiden!)
  $text = preg_replace('/\[(\/)?author[^\]]*\]/i', '', $text);

  // 2) Internet-Block nur entfernen, wenn er typisch am Ende steht
  $len = mb_strlen($text);
  $posInternet = mb_stripos($text, '[b]Internet[/b]');
  if ($posInternet !== false && $posInternet > ($len * 0.60)) {
    $text = mb_substr($text, 0, $posInternet);
  }

  // Basic BBCode
  $text = str_replace(['[b]','[/b]','[i]','[/i]'], ['<strong>','</strong>','<em>','</em>'], $text);

  // [url=LINK]Text[/url]
  $text = preg_replace_callback('/\[url=([^\]]+)\](.*?)\[\/url\]/i', function($m){
    $href = esc_url($m[1]);
    $t    = esc_html($m[2]);
    return '<a href="'.$href.'" target="_blank" rel="noopener">'.$t.'</a>';
  }, $text);

  // Restliche BBCode-Tags entfernen (aber keine normalen eckigen Klammern wie [1942])
  $text = preg_replace('/\[(\/)?[a-z][a-z0-9]*[^\]]*\]/i', '', $text);

  // Zeilen -> Absätze
  $parts = preg_split("/\R{2,}/u", trim($text));
  $html  = '';
  foreach ($parts as $p) {
    $p = trim($p);
    if ($p === '') continue;
    $html .= '<p>' . wp_kses_post($p) . '</p>';
  }
  return $html;
}

/** --- Adresse möglichst robust aus API-Daten ziehen --- */
function kuladig_extract_address($data) {
  $get = function($path) use ($data) {
    $v = $data;
    foreach ($path as $k) {
      if (!is_array($v) || !array_key_exists($k, $v)) return '';
      $v = $v[$k];
    }
    return is_scalar($v) ? (string)$v : '';
  };

  $street = $data['Strasse'] ?? $data['Straße'] ?? $get(['Adresse','Strasse']) ?? $get(['Adresse','Straße']) ?? $get(['Anschrift','Strasse']) ?? $get(['Anschrift','Straße']) ?? '';
  $hnr    = $data['Hausnummer'] ?? $get(['Adresse','Hausnummer']) ?? $get(['Anschrift','Hausnummer']) ?? '';
  $plz    = $data['PLZ'] ?? $data['Postleitzahl'] ?? $get(['Adresse','PLZ']) ?? $get(['Adresse','Postleitzahl']) ?? $get(['Anschrift','PLZ']) ?? '';
  $city   = $data['Ort'] ?? $data['Stadt'] ?? $data['Gemeinde'] ?? $get(['Adresse','Ort']) ?? $get(['Adresse','Stadt']) ?? $get(['Anschrift','Ort']) ?? '';
  $extra  = $data['Ortsteil'] ?? $data['Stadtteil'] ?? $get(['Adresse','Ortsteil']) ?? '';

  $street = trim($street);
  $hnr    = trim($hnr);
  $plz    = trim($plz);
  $city   = trim($city);
  $extra  = trim($extra);

  if ($street === '' && $plz === '' && $city === '' && $extra === '') return '';

  $line1 = trim(($street ? $street : '') . ($hnr ? ' ' . $hnr : ''));
  $line2 = trim(($plz ? $plz : '') . ($city ? ' ' . $city : ''));
  $line  = trim($line1 . ($line1 && $line2 ? ', ' : '') . $line2);

  if ($extra) $line = $line ? ($line . ' (' . $extra . ')') : $extra;

  return $line;
}

/** Leaflet sauber enqueue */
function kuladig_enqueue_leaflet() {
  wp_enqueue_style('kuladig-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
  wp_enqueue_script('kuladig-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
}

/** --- Shortcode --- */
function kuladig_ort_design_shortcode() {
  if (empty($_GET['id'])) return '<p>Kein Ort ausgewählt.</p>';
  $id = sanitize_text_field($_GET['id']);

  $cache_key = 'kuladig_obj_' . md5($id);
  $data = get_transient($cache_key);

  if ($data === false) {
    $url  = 'https://www.kuladig.de/api/public/Objekt/' . rawurlencode($id);
    $resp = wp_remote_get($url, ['timeout' => 12]);
    if (is_wp_error($resp)) return '<p>Fehler beim Laden der Daten.</p>';

    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($data) || !is_array($data)) return '<p>Ort nicht gefunden.</p>';

    set_transient($cache_key, $data, 7 * DAY_IN_SECONDS);
  }

  kuladig_enqueue_leaflet();

  $name = !empty($data['Name']) ? (string)$data['Name'] : '';
  $name_esc = esc_html($name);

  // Koordinaten
  $lat = $lon = null;
  if (!empty($data['Punktkoordinate']['coordinates']) && is_array($data['Punktkoordinate']['coordinates'])) {
    $lon = $data['Punktkoordinate']['coordinates'][0] ?? null;
    $lat = $data['Punktkoordinate']['coordinates'][1] ?? null;
    if (!is_numeric($lat) || !is_numeric($lon)) { $lat = $lon = null; }
  }

  // Bestes Bildtoken
  $token = '';
  if (!empty($data['Dokumente']) && is_array($data['Dokumente'])) {
    $first = reset($data['Dokumente']);
    if (!empty($first['Thumbnail3Token'])) $token = $first['Thumbnail3Token'];
    elseif (!empty($first['Thumbnail2Token'])) $token = $first['Thumbnail2Token'];
    elseif (!empty($first['Thumbnail1Token'])) $token = $first['Thumbnail1Token'];
    elseif (!empty($first['Token'])) $token = $first['Token'];
  }
  if (empty($token) && !empty($data['ThumbnailToken'])) $token = $data['ThumbnailToken'];
  $token = esc_attr($token);

  // Beschreibung -> HTML
  $desc_raw  = $data['Beschreibung'] ?? '';
  $desc_html = kuladig_bbcode_to_html($desc_raw);

  // Intro (kurz)
  $intro_plain = wp_strip_all_tags($desc_html);
  $intro_plain = preg_replace('/\s+/', ' ', trim($intro_plain));
  $intro = esc_html(mb_substr($intro_plain, 0, 220)) . (mb_strlen($intro_plain) > 220 ? '…' : '');

  // Wenn keine echten Überschriften -> Standardsektionen
  $has_headings = (stripos($desc_html, '<h2') !== false) || (stripos($desc_html, '<h3') !== false);
  if (!$has_headings) {
    preg_match_all('/<p\b[^>]*>.*?<\/p>/is', $desc_html, $m);
    $paras = $m[0] ?? [];
    if (empty($paras)) $paras = ['<p>' . wp_kses_post(wp_strip_all_tags($desc_html)) . '</p>'];

    $intro_block = implode('', array_slice($paras, 0, 2));
    $rest_block  = implode('', array_slice($paras, 2));

    $desc_html =
      '<h2>Einführung</h2>' . $intro_block .
      ($rest_block ? '<h2>Geschichte und Beschreibung</h2>' . $rest_block : '');
  }

  // Adresse als Punkt 3 einfügen
  $address = kuladig_extract_address($data);
  if ($address) {
    $address_block = '<h2>Adresse</h2><p>' . esc_html($address) . '</p>';

    $h2_close = '</h2>';
    $offset = 0; $count = 0; $inserted = false;

    while (($p = stripos($desc_html, $h2_close, $offset)) !== false) {
      $count++;
      $offset = $p + strlen($h2_close);
      if ($count === 2) {
        $desc_html = substr($desc_html, 0, $offset) . $address_block . substr($desc_html, $offset);
        $inserted = true;
        break;
      }
    }
    if (!$inserted) $desc_html .= $address_block;
  }

  $uid = 'kldp_' . wp_generate_password(6, false, false);

  // Daten für JS
  $lat_f = ($lat !== null) ? (float)$lat : 0.0;
  $lon_f = ($lon !== null) ? (float)$lon : 0.0;
  $has_coord = ($lat !== null && $lon !== null);

  ob_start(); ?>

  <style>
    /* Buttons unter der Karte */
    #<?php echo esc_attr($uid); ?> .kldp-map-actions{
      margin-top: 12px;
      display:flex;
      gap:10px;
      flex-wrap:wrap;
      justify-content:flex-start;
    }
    #<?php echo esc_attr($uid); ?> .kldp-btn{
      border: 1px solid rgba(15,23,42,.10);
      background: rgba(15,23,42,.04);
      color:#0f172a;
      padding: 10px 12px;
      border-radius: 12px;
      font-weight: 900;
      cursor:pointer;
      font-size: 13px;
      display:inline-flex;
      align-items:center;
      gap:10px;
      text-decoration:none;
      user-select:none;
    }
    #<?php echo esc_attr($uid); ?> .kldp-btn-primary{
      background:#0f172a;
      border-color:#0f172a;
      color:#fff;
    }
    #<?php echo esc_attr($uid); ?> .kldp-btn[disabled]{
      opacity:.55;
      cursor:not-allowed;
      pointer-events:none;
    }
    #<?php echo esc_attr($uid); ?> .kldp-toast{
      margin-top:10px;
      display:none;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(15,23,42,.08);
      background:#fff;
      box-shadow: 0 18px 45px rgba(15,23,42,.10);
      font-size: 12px;
      font-weight: 800;
      color:#0f172a;
    }
    #<?php echo esc_attr($uid); ?> .kldp-toast.is-on{ display:block; }
  </style>

  <div class="kldp" id="<?php echo esc_attr($uid); ?>"
       data-kld-id="<?php echo esc_attr($id); ?>"
       data-kld-name="<?php echo esc_attr($name); ?>"
       data-kld-hascoord="<?php echo $has_coord ? '1' : '0'; ?>"
       data-kld-lat="<?php echo $has_coord ? esc_attr((string)$lat_f) : ''; ?>"
       data-kld-lon="<?php echo $has_coord ? esc_attr((string)$lon_f) : ''; ?>">

    <div class="kldp-top">
      <div class="kldp-media">
        <?php if ($token): ?>
          <img class="kldp-image" src="https://www.kuladig.de/api/public/Dokument?token=<?php echo $token; ?>" alt="<?php echo esc_attr($name); ?>">
        <?php else: ?>
          <div class="kldp-image kldp-image--empty"></div>
        <?php endif; ?>
      </div>

      <div class="kldp-mapcard">
        <div class="kldp-map" id="<?php echo esc_attr($uid); ?>_map"></div>

        <div class="kldp-map-actions">
          <button class="kldp-btn" type="button" id="<?php echo esc_attr($uid); ?>_gmaps">
            In Google Maps öffnen
          </button>

          <button class="kldp-btn kldp-btn-primary" type="button" id="<?php echo esc_attr($uid); ?>_addroute">
            Zur Route hinzufügen
          </button>
        </div>

        <div class="kldp-toast" id="<?php echo esc_attr($uid); ?>_toast"></div>
      </div>
    </div>

    <div class="kldp-main">
      <aside class="kldp-aside">
        <div class="kldp-toc">
          <div class="kldp-toc-title">INHALT</div>
          <ul class="kldp-toc-list"></ul>
        </div>
      </aside>

      <article class="kldp-article">
        <h1 class="kldp-title"><?php echo $name_esc; ?></h1>

        <?php if ($intro_plain): ?>
          <div class="kldp-intro"><?php echo $intro; ?></div>
        <?php endif; ?>

        <div class="kldp-content">
          <?php echo wp_kses_post($desc_html); ?>
        </div>
      </article>
    </div>
  </div>

  <script>
  (function(){
    var root = document.getElementById(<?php echo wp_json_encode($uid); ?>);
    if(!root) return;

    var mapEl = document.getElementById(<?php echo wp_json_encode($uid . '_map'); ?>);
    if(!mapEl) return;

    var btnG = document.getElementById(<?php echo wp_json_encode($uid . '_gmaps'); ?>);
    var btnR = document.getElementById(<?php echo wp_json_encode($uid . '_addroute'); ?>);
    var toastEl = document.getElementById(<?php echo wp_json_encode($uid . '_toast'); ?>);

    var kldId = root.getAttribute('data-kld-id') || '';
    var kldName = root.getAttribute('data-kld-name') || '';
    var hasCoord = (root.getAttribute('data-kld-hascoord') === '1');
    var latStr = root.getAttribute('data-kld-lat') || '';
    var lonStr = root.getAttribute('data-kld-lon') || '';

    var lat = hasCoord ? parseFloat(latStr) : 0;
    var lon = hasCoord ? parseFloat(lonStr) : 0;

    if(!hasCoord){
      if(btnG) btnG.disabled = true;
      if(btnR) btnR.disabled = true;
    }

    function toast(msg){
      if(!toastEl) return;
      toastEl.textContent = msg;
      toastEl.classList.add('is-on');
      clearTimeout(toastEl._t);
      toastEl._t = setTimeout(function(){ toastEl.classList.remove('is-on'); }, 2200);
    }

    // Google Maps öffnen (kein Redirect)
    if(btnG){
      btnG.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        if(!hasCoord) return;

        var url = "https://www.google.com/maps/search/?api=1&query=" + encodeURIComponent(lat + "," + lon);
        window.open(url, "_blank", "noopener");
      });
    }

    // ===== Route speichern (genau gleicher Key wie Karten-Seite) =====
    var ROUTE_KEY = "kld_route_v1";

    function loadRoute(){
      try{
        var raw = localStorage.getItem(ROUTE_KEY);
        if(!raw) return [];
        var arr = JSON.parse(raw);
        return Array.isArray(arr) ? arr : [];
      }catch(e){ return []; }
    }
    function saveRoute(arr){
      try{ localStorage.setItem(ROUTE_KEY, JSON.stringify(arr)); }catch(e){}
    }

    if(btnR){
      btnR.addEventListener('click', function(e){
        e.preventDefault();
        e.stopPropagation();
        if(!hasCoord || !kldId) return;

        var route = loadRoute();

        // robust: auch alte Einträge akzeptieren (lat/lon evtl. strings)
        route = route.filter(function(x){
          if(!x || !x.id) return false;
          var la = (typeof x.lat === "string") ? parseFloat(x.lat) : x.lat;
          var lo = (typeof x.lon === "string") ? parseFloat(x.lon) : x.lon;
          return (typeof la === "number" && !isNaN(la) && typeof lo === "number" && !isNaN(lo));
        }).map(function(x){
          x.lat = (typeof x.lat === "string") ? parseFloat(x.lat) : x.lat;
          x.lon = (typeof x.lon === "string") ? parseFloat(x.lon) : x.lon;
          return x;
        });

        var exists = route.some(function(x){ return String(x.id) === String(kldId); });
        if(exists){
          toast("Ist schon in der Route.");
          return;
        }

        route.push({
          id: String(kldId),
          name: String(kldName || "Ort"),
          lat: lat,
          lon: lon
        });

        saveRoute(route);

        // Event für gleiche Seite (falls Map-Script auf gleicher Seite wäre)
        try{
          window.dispatchEvent(new CustomEvent('kld:route-updated', { detail: { route: route } }));
        }catch(e){}

        toast("Zur Route hinzugefügt.");
        // WICHTIG: KEIN Redirect zur Karte
      });
    }

    // ===== Leaflet Map init (wie bisher) =====
    function ensureLeaflet(cb){
      if (window.L) return cb();

      if(!document.querySelector('link[data-kuladig-leaflet]')){
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        link.setAttribute('data-kuladig-leaflet','1');
        document.head.appendChild(link);
      }

      if(!document.querySelector('script[data-kuladig-leaflet]')){
        var s = document.createElement('script');
        s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        s.defer = true;
        s.setAttribute('data-kuladig-leaflet','1');
        s.onload = cb;
        s.onerror = function(){ console.error('Leaflet konnte nicht geladen werden'); };
        document.head.appendChild(s);
      } else {
        var tries = 0;
        var t = setInterval(function(){
          tries++;
          if(window.L){ clearInterval(t); cb(); }
          if(tries > 50) clearInterval(t);
        }, 100);
      }
    }

    function initMap(){
      if(!window.L) return;
      if(mapEl.dataset.kldInited === '1') return;
      mapEl.dataset.kldInited = '1';

      var map = L.map(mapEl, { scrollWheelZoom: false, zoomControl: true });
      map.setView(hasCoord ? [lat, lon] : [51.2, 10.5], hasCoord ? 14 : 6);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap-Mitwirkende'
      }).addTo(map);

      if(hasCoord){
        L.marker([lat, lon]).addTo(map).bindPopup(String(kldName||""));
      }

      var fix = function(){ try { map.invalidateSize(true); } catch(e){} };
      setTimeout(fix, 100);
      setTimeout(fix, 400);
      setTimeout(fix, 1200);
      window.addEventListener('resize', fix);
    }

    ensureLeaflet(initMap);
  })();
  </script>

  <?php
  return ob_get_clean();
}
add_shortcode('kuladig_ort_design', 'kuladig_ort_design_shortcode');

/**
 * Search Results Page
 */
/**
 * 1) Front-Search (Autocomplete)
 *    Shortcode: [kuladig_search limit="8" results_page="/suche/"]
 *
 * (UNVERÄNDERT)
 */

/** =======================
 * AJAX: Autocomplete (Prefix-Cache + Query-Cache)
 * ======================= */
add_action('wp_ajax_kuladig_ajax_search', 'kuladig_ajax_search_handler');
add_action('wp_ajax_nopriv_kuladig_ajax_search', 'kuladig_ajax_search_handler');

/** AJAX-Endpoint fuer Autocomplete inkl. Prefix/Query-Cache. */
function kuladig_ajax_search_handler() {
  $q = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';
  $q = trim($q);

  $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
  if (!wp_verify_nonce($nonce, 'kuladig_search_nonce')) {
    wp_send_json_error(['message' => 'Nonce ungültig.']);
  }

  if (mb_strlen($q) < 2) {
    wp_send_json_error(['message' => 'Bitte mindestens 2 Zeichen eingeben.']);
  }

  $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 8;
  $limit = max(1, min(20, $limit));

  $norm = mb_strtolower($q);
  $norm = preg_replace('/\s+/', ' ', $norm);

  // Query Cache (sehr schnell)
  $q_cache_key = 'kuladig_q_' . md5($norm) . '_l' . $limit;
  $cached_q = get_transient($q_cache_key);
  if ($cached_q !== false) {
    wp_send_json_success(['cached' => true, 'items' => $cached_q]);
  }

  // Prefix Cache (API nur für Prefix)
  $prefix_len = 3;
  $prefix = mb_substr($norm, 0, min($prefix_len, mb_strlen($norm)));

  $p_cache_key = 'kuladig_p_' . md5($prefix);
  $prefix_items = get_transient($p_cache_key);

  if ($prefix_items === false) {
    $url = 'https://www.kuladig.de/api/public/Objekt?ObjektTyp=KuladigObjekt&Seite=0&suchText=' . rawurlencode($prefix);
    $resp = wp_remote_get($url, ['timeout' => 12]);

    if (is_wp_error($resp)) {
      wp_send_json_error(['message' => 'Fehler beim Laden der Daten.']);
    }

    $json = json_decode(wp_remote_retrieve_body($resp), true);

    if (!is_array($json) || empty($json['Ergebnis'])) {
      set_transient($p_cache_key, [], 30 * MINUTE_IN_SECONDS);
      set_transient($q_cache_key, [], 2 * HOUR_IN_SECONDS);
      wp_send_json_success(['cached' => false, 'items' => []]);
    }

    $prefix_items = [];
    foreach ($json['Ergebnis'] as $obj) {
      $desc = isset($obj['Beschreibung']) ? (string)$obj['Beschreibung'] : '';
      $desc = preg_replace('/\[[^\]]*\]/', '', $desc);
      $desc = trim(preg_replace('/\s+/', ' ', $desc));
      if (mb_strlen($desc) > 220) $desc = mb_substr($desc, 0, 217) . '...';

      $prefix_items[] = [
        'id'    => $obj['Id'] ?? '',
        'name'  => $obj['Name'] ?? '',
        'desc'  => $desc,
        'token' => $obj['ThumbnailToken'] ?? '',
      ];

      if (count($prefix_items) >= 120) break;
    }

    set_transient($p_cache_key, $prefix_items, 7 * DAY_IN_SECONDS);
  }

  // Serverseitiges Filtern
  $hits = [];
  foreach ((array)$prefix_items as $it) {
    $name = mb_strtolower((string)($it['name'] ?? ''));
    $desc = mb_strtolower((string)($it['desc'] ?? ''));

    if ($name !== '' && mb_strpos($name, $norm) !== false) {
      $hits[] = $it;
    } elseif ($desc !== '' && mb_strpos($desc, $norm) !== false) {
      $hits[] = $it;
    }

    if (count($hits) >= $limit) break;
  }

  set_transient($q_cache_key, $hits, 24 * HOUR_IN_SECONDS);

  wp_send_json_success(['cached' => false, 'items' => $hits]);
}


/** =======================
 * Shortcode: Front Search
 * ======================= */
add_shortcode('kuladig_search', 'kuladig_search_shortcode');

/** Shortcode fuer die Front-Suche inkl. Dropdown und JS. */
function kuladig_search_shortcode($atts) {
  $atts = shortcode_atts([
    'limit'        => '8',
    'results_page' => '/suche/',   // <- Seite für Ergebnisse
  ], $atts);

  $limit = max(1, min(20, (int)$atts['limit']));
  $results_page = trim((string)$atts['results_page']);
  if ($results_page === '') $results_page = '/suche/';

  $uid   = 'kld_' . wp_generate_password(6, false, false);
  $ajax_url = admin_url('admin-ajax.php');
  $nonce    = wp_create_nonce('kuladig_search_nonce');

  ob_start(); ?>
  <div class="kuladig-search" id="<?php echo esc_attr($uid); ?>">
    <form class="kuladig-search-form" autocomplete="off">
      <input type="text" class="kuladig-search-input" placeholder="Ort eingeben" />
      <button type="submit">Suchen</button>
    </form>

    <div class="kuladig-autocomplete" hidden></div>
    <div class="kuladig-search-message" aria-live="polite"></div>
  </div>

  <script>
  (function(){
    var root = document.getElementById(<?php echo wp_json_encode($uid); ?>);
    if(!root) return;

    var form = root.querySelector('.kuladig-search-form');
    var input = root.querySelector('.kuladig-search-input');
    var box = root.querySelector('.kuladig-autocomplete');
    var msg = root.querySelector('.kuladig-search-message');

    var AJAX_URL = <?php echo wp_json_encode($ajax_url); ?>;
    var NONCE = <?php echo wp_json_encode($nonce); ?>;
    var LIMIT = <?php echo (int)$limit; ?>;
    var RESULTS_PAGE = <?php echo wp_json_encode($results_page); ?>;

    var timer = null;
    var lastQuery = '';
    var lastItems = [];
    var controller = null;
    var sessionCache = Object.create(null);

    function esc(s){
      return (s||'').toString()
        .replaceAll('&','&amp;')
        .replaceAll('<','&lt;')
        .replaceAll('>','&gt;')
        .replaceAll('"','&quot;')
        .replaceAll("'","&#039;");
    }

    function hide(){
      box.hidden = true;
      box.innerHTML = '';
    }

    function show(items){
      lastItems = items || [];
      if(!lastItems.length){
        hide();
        return;
      }

      var html = '<div class="kuladig-ac-list">';
      for(var i=0;i<lastItems.length;i++){
        var it = lastItems[i] || {};
        var img = it.token
          ? '<img class="kuladig-ac-thumb" src="https://www.kuladig.de/api/public/Dokument?token='+encodeURIComponent(it.token)+'" alt="">'
          : '<div class="kuladig-ac-thumb kuladig-ac-thumb--empty"></div>';

        var desc = (it.desc || '');
        if(desc.length > 110) desc = desc.substring(0,107) + '...';

        html += (
          '<button type="button" class="kuladig-ac-item" data-id="'+esc(it.id||'')+'">' +
            img +
            '<div class="kuladig-ac-text">' +
              '<div class="kuladig-ac-title">'+esc(it.name||'')+'</div>' +
              '<div class="kuladig-ac-desc">'+esc(desc)+'</div>' +
            '</div>' +
          '</button>'
        );
      }
      html += '</div>';

      box.innerHTML = html;
      box.hidden = false;
    }

    function fetchHits(q){
      if(sessionCache[q]){
        show(sessionCache[q]);
        return Promise.resolve();
      }

      if(controller) controller.abort();
      controller = new AbortController();

      var fd = new FormData();
      fd.append('action', 'kuladig_ajax_search');
      fd.append('nonce', NONCE);
      fd.append('q', q);
      fd.append('limit', String(LIMIT));

      return fetch(AJAX_URL, { method: 'POST', body: fd, signal: controller.signal })
        .then(function(r){ return r.json(); })
        .then(function(data){
          if(!data || !data.success) { hide(); return; }
          var items = (data.data && data.data.items) ? data.data.items : [];
          sessionCache[q] = items;
          show(items);
        })
        .catch(function(){ hide(); });
    }

    input.addEventListener('input', function(){
      var q = (input.value || '').trim();
      if(q.length < 2){ hide(); msg.textContent=''; return; }
      if(q === lastQuery) return;
      lastQuery = q;

      clearTimeout(timer);
      timer = setTimeout(function(){ fetchHits(q); }, 180);
    });

    // ENTER / Button => auf Ergebnis-Seite (nicht mehr erstes Item öffnen)
    form.addEventListener('submit', function(e){
      e.preventDefault();
      var q = (input.value || '').trim();
      if(q.length < 2) return;

      var url = RESULTS_PAGE;
      if(!url.endsWith('/')) url += '/';
      window.location.href = url + '?q=' + encodeURIComponent(q);
    });

    // Klick im Dropdown => Detailseite
    box.addEventListener('click', function(e){
      var btn = e.target.closest('.kuladig-ac-item');
      if(!btn) return;
      var id = btn.getAttribute('data-id') || '';
      if(!id) return;
      window.location.href = '/ort/?id=' + encodeURIComponent(id);
    });

    document.addEventListener('click', function(e){
      if(!root.contains(e.target)) hide();
    });

    document.addEventListener('keydown', function(e){
      if(e.key === 'Escape') hide();
    });
  })();
  </script>
  <?php
  return ob_get_clean();
}

/**
 * Carousell Front
 */
/**
 * Shortcodes:
 * 1) [kuladig_home_hero apiaccesset="API2" badge="ANKERPUNKT"]
 * 2) [kuladig_cards apiaccesset="API2" badge="ANKERPUNKT" limit="12"]
 */

/** Bereinigt apiaccesset-IDs fuer den JSON-Importer. */
function kuladig_safe_apiaccesset($v) {
  $v = (string) $v;
  return preg_replace('/[^A-Za-z0-9_\-]/', '', $v);
}

/** Bindet das kleine UI-JS nur einmal ein (Hero + Cards). */
function kuladig_enqueue_ui_assets_once() {
  static $done = false;
  if ($done) return;
  $done = true;

  // Inline-JS als "echtes" WP-Script einhängen (nur 1x)
  wp_register_script('kuladig-ui', '', [], '1.0.0', true);
  wp_enqueue_script('kuladig-ui');

  $js = <<<JS
(function(){
  function initHero(root){
    var slides = root.querySelectorAll('.kuladig-hero-slide');
    if(!slides || !slides.length) return;

    var i = 0;
    function show(n){
      slides[i].classList.remove('is-active');
      i = (n + slides.length) % slides.length;
      slides[i].classList.add('is-active');
    }

    slides.forEach(function(s){ s.classList.remove('is-active'); });
    slides[0].classList.add('is-active');

    var prev = root.querySelector('.kuladig-hero-prev');
    var next = root.querySelector('.kuladig-hero-next');

    if(prev) prev.addEventListener('click', function(){ show(i-1); });
    if(next) next.addEventListener('click', function(){ show(i+1); });
  }

  function applyCardLimits(){
    document.querySelectorAll('.kuladig-card-grid[data-limit]').forEach(function(grid){
      var limit = parseInt(grid.getAttribute('data-limit') || '0', 10);
      if(!limit || limit < 1) return;

      var cards = grid.querySelectorAll('.kuladig-card');
      for(var k=limit; k<cards.length; k++){
        cards[k].style.display = 'none';
      }
    });
  }

  function initAll(){
    document.querySelectorAll('.kuladig-hero-wrapper').forEach(initHero);
    applyCardLimits();
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
JS;

  wp_add_inline_script('kuladig-ui', $js);
}

/** HERO Slider */
function kuladig_home_hero_shortcode($atts) {
  $atts = shortcode_atts([
    'apiaccesset' => 'API2',
    'badge'       => 'ANKERPUNKT',
  ], $atts, 'kuladig_home_hero');

  kuladig_enqueue_ui_assets_once();

  $apiaccesset = kuladig_safe_apiaccesset($atts['apiaccesset']);
  $badge = esc_html((string)$atts['badge']);
  $uid = 'kldhero_' . wp_generate_password(6, false, false);

  $tpl = <<<HTML
[jsoncontentimporter apiaccesset="$apiaccesset" basenode=""]
<div class="kuladig-hero-wrapper" id="$uid">
  <div class="kuladig-hero-slider">
    {subloop-array:Ergebnis:-1}
      <div class="kuladig-hero-slide">
        <div class="kuladig-hero-media" style="background-image:url('https://www.kuladig.de/api/public/Dokument?token={Ergebnis.ThumbnailToken}')"></div>

        <div class="kuladig-hero-panel">
          <div class="kuladig-hero-badge">$badge</div>
          <h2 class="kuladig-hero-title">{Ergebnis.Name}</h2>
          <p class="kuladig-hero-text">{Ergebnis.Beschreibung}</p>
          <a class="kuladig-hero-button" href="/ort/?id={Ergebnis.Id}">Besuchen <span aria-hidden="true">→</span></a>
        </div>
      </div>
    {/subloop-array:Ergebnis}
  </div>

  <button class="kuladig-hero-arrow kuladig-hero-prev" type="button" aria-label="Vorheriges">‹</button>
  <button class="kuladig-hero-arrow kuladig-hero-next" type="button" aria-label="Nächstes">›</button>
</div>
[/jsoncontentimporter]
HTML;

  return do_shortcode($tpl);
}
add_shortcode('kuladig_home_hero', 'kuladig_home_hero_shortcode');


/** Cards Grid */
function kuladig_cards_shortcode($atts) {
  $atts = shortcode_atts([
    'apiaccesset' => 'API2',
    'limit'       => '12',
    'badge'       => 'ANKERPUNKT',
  ], $atts, 'kuladig_cards');

  kuladig_enqueue_ui_assets_once();

  $apiaccesset = kuladig_safe_apiaccesset($atts['apiaccesset']);
  $limit = max(1, (int)$atts['limit']);
  $badge = esc_html((string)$atts['badge']);

  $tpl = <<<HTML
[jsoncontentimporter apiaccesset="$apiaccesset" basenode=""]
<div class="kuladig-card-grid" data-limit="$limit">
  {subloop-array:Ergebnis:-1}
    <a class="kuladig-card" href="/ort/?id={Ergebnis.Id}">
      <div class="kuladig-card-media" style="background-image:url('https://www.kuladig.de/api/public/Dokument?token={Ergebnis.ThumbnailToken}')"></div>
      <div class="kuladig-card-body">
        <div class="kuladig-hero-badge">$badge</div>
        <div class="kuladig-card-title">{Ergebnis.Name}</div>
        <div class="kuladig-card-text">{Ergebnis.Beschreibung}</div>
      </div>
    </a>
  {/subloop-array:Ergebnis}
</div>
[/jsoncontentimporter]
HTML;

  return do_shortcode($tpl);
}
add_shortcode('kuladig_cards', 'kuladig_cards_shortcode');

/**
 * News Feed Did You Know
 */
/**
 * 1 Ort pro Tag (mit Bild) + 1 Fact pro Tag
 * Shortcode: [kuladig_daily_didyouknow]
 *
 * Optional:
 *   define('KULADIG_PROJECT_ID', 2085);
 *   define('KULADIG_GEMINI_API_KEY', '...');
 *   define('KULADIG_GEMINI_MODEL', 'gemini-2.5-flash-lite'); // default unten
 */

if (!defined('KULADIG_DYK_OPTION')) define('KULADIG_DYK_OPTION', 'kuladig_didyouknow_state');
if (!defined('KULADIG_DYK_EVENT'))  define('KULADIG_DYK_EVENT',  'kuladig_didyouknow_daily_event');

/* Cron einmalig planen (03:05 Uhr Website-Zeitzone) */
add_action('init', function () {
  if (!wp_next_scheduled(KULADIG_DYK_EVENT)) {
    $tz  = wp_timezone();
    $now = new DateTime('now', $tz);
    $run = (clone $now)->setTime(3, 5, 0);
    if ($run <= $now) $run->modify('+1 day');
    wp_schedule_event($run->getTimestamp(), 'daily', KULADIG_DYK_EVENT);
  }
});

add_action(KULADIG_DYK_EVENT, 'kld_dyk_generate_daily');

/** Falls wenig Traffic und Cron nicht läuft: beim Seitenaufruf sicherstellen */
function kld_dyk_ensure_today() {
  $state = get_option(KULADIG_DYK_OPTION, []);
  $today = (new DateTime('now', wp_timezone()))->format('Y-m-d');
  if (($state['date'] ?? '') !== $today) {
    kld_dyk_generate_daily();
  }
}

/** Generiert genau 1x pro Tag (wenn noch nicht vorhanden) */
function kld_dyk_generate_daily() {
  $today = (new DateTime('now', wp_timezone()))->format('Y-m-d');

  $state = get_option(KULADIG_DYK_OPTION, []);
  if (($state['date'] ?? '') === $today && !empty($state['id']) && !empty($state['fact']) && !empty($state['token'])) {
    return;
  }

  $projectId = defined('KULADIG_PROJECT_ID') ? (int) KULADIG_PROJECT_ID : 0;

  for ($try = 0; $try < 12; $try++) {

    $pick = kld_dyk_pick_random_object_with_image($projectId);
    if (!$pick) break;

    [$id, $preview] = $pick;

    $details = kld_dyk_fetch_object_details($id);
    if (!$details) continue;

    $name = (string)($details['Name'] ?? $preview['Name'] ?? '');
    $desc = (string)($details['Beschreibung'] ?? $preview['Beschreibung'] ?? '');
    if ($name === '' || $desc === '') continue;

    $token = kld_dyk_best_thumb_token($details);
    if (!$token && !empty($preview['ThumbnailToken'])) $token = (string)$preview['ThumbnailToken'];
    $token = trim((string)$token);
    if ($token === '') continue;

    $prompt = kld_dyk_build_prompt($name, $desc);

    $fact_raw = kld_dyk_gemini_generate($prompt);
    $fact = $fact_raw ?: kld_dyk_fallback_fact($desc);
    $fact = kld_dyk_normalize_fact($fact);

    if ($fact === '') {
      $fact = kld_dyk_normalize_fact(kld_dyk_fallback_fact($desc));
    }

    update_option(KULADIG_DYK_OPTION, [
      'date'  => $today,
      'id'    => $id,
      'name'  => $name,
      'token' => $token,
      'fact'  => $fact,
    ], false);

    return;
  }
}

/** ===== KuLaDig Picker (nur mit Bild) ===== */
function kld_dyk_pick_random_object_with_image(int $projectId = 0) {
  $base = 'https://www.kuladig.de/api/public/Objekt';

  $params = ['ObjektTyp' => 'KuladigObjekt', 'Seite' => 0];
  if ($projectId > 0) $params['Projekt'] = $projectId;

  $first = kld_dyk_http_get_json(add_query_arg($params, $base));
  if (!$first) return null;

  $pages = (int)($first['AnzahlSeiten'] ?? $first['Seitenanzahl'] ?? 0);
  if ($pages < 1) $pages = 1;

  for ($try = 0; $try < 8; $try++) {
    $page = random_int(0, $pages - 1);
    $params['Seite'] = $page;

    $pageJson = kld_dyk_http_get_json(add_query_arg($params, $base));
    if (!$pageJson || empty($pageJson['Ergebnis']) || !is_array($pageJson['Ergebnis'])) continue;

    $results = $pageJson['Ergebnis'];

    $candidates = array_values(array_filter($results, function($r){
      return !empty($r['Id']) && !empty($r['Name']) && !empty($r['Beschreibung']) && !empty($r['ThumbnailToken']);
    }));

    if (empty($candidates)) continue;

    $r = $candidates[array_rand($candidates)];
    return [(string)$r['Id'], $r];
  }

  return null;
}

/** Holt Detaildaten fuer ein Objekt aus der KuLaDig API. */
function kld_dyk_fetch_object_details(string $id) {
  $url = 'https://www.kuladig.de/api/public/Objekt/' . rawurlencode($id);
  return kld_dyk_http_get_json($url);
}

/** Waehlt den besten verfuegbaren Thumbnail-Token. */
function kld_dyk_best_thumb_token(array $data): string {
  if (!empty($data['Dokumente']) && is_array($data['Dokumente'])) {
    $first = reset($data['Dokumente']);
    if (!empty($first['Thumbnail3Token'])) return (string)$first['Thumbnail3Token'];
    if (!empty($first['Thumbnail2Token'])) return (string)$first['Thumbnail2Token'];
    if (!empty($first['Thumbnail1Token'])) return (string)$first['Thumbnail1Token'];
    if (!empty($first['Token'])) return (string)$first['Token'];
  }
  return !empty($data['ThumbnailToken']) ? (string)$data['ThumbnailToken'] : '';
}

/** Baut den Prompt fuer den Fakten-Generator. */
function kld_dyk_build_prompt(string $name, string $desc_raw): string {
  $plain = wp_strip_all_tags($desc_raw);
  $plain = preg_replace('/\s+/', ' ', trim($plain));
  $plain = mb_substr($plain, 0, 1400);

  return
    "Schreibe EINEN sehr kurzen Fakt über den Ort.\n".
    "Regeln:\n".
    "- 1 bis 2 Sätze\n".
    "- maximal 240 Zeichen\n".
    "- NUR Fakten aus dem Text unten, nichts erfinden\n".
    "- BEGINNE NICHT mit „Wusstest du schon?“ oder „Did you know?“\n".
    "- schreibe NICHT „wird in KuLaDig so beschrieben:“\n".
    "- Ausgabe nur als Klartext, kein Markdown\n\n".
    "Ort: {$name}\n".
    "Text: {$plain}\n";
}

/** ===== Gemini ===== */
function kld_dyk_get_gemini_key(): string {
  if (defined('KULADIG_GEMINI_API_KEY') && is_string(KULADIG_GEMINI_API_KEY) && KULADIG_GEMINI_API_KEY !== '') {
    return KULADIG_GEMINI_API_KEY;
  }
  $env = getenv('KULADIG_GEMINI_API_KEY');
  return is_string($env) ? trim($env) : '';
}

/** Gibt den Modellnamen mit Fallback zurueck. */
function kld_dyk_get_gemini_model(): string {
  if (defined('KULADIG_GEMINI_MODEL') && is_string(KULADIG_GEMINI_MODEL) && KULADIG_GEMINI_MODEL !== '') {
    return KULADIG_GEMINI_MODEL;
  }
  return 'gemini-2.5-flash-lite';
}

/** Fragt die Gemini-API ab und normalisiert die Antwort. */
function kld_dyk_gemini_generate(string $prompt): string {
  $key = kld_dyk_get_gemini_key();
  if ($key === '') return '';

    $model = kld_dyk_get_gemini_model();

  $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent';

  $payload = [
    'contents' => [[ 'role' => 'user', 'parts' => [['text' => $prompt]] ]],
    'generationConfig' => ['temperature' => 0.4, 'maxOutputTokens' => 96],
  ];

  $resp = wp_remote_post($endpoint, [
    'timeout' => 20,
    'headers' => ['Content-Type' => 'application/json', 'x-goog-api-key' => $key],
    'body'    => wp_json_encode($payload),
  ]);

  if (is_wp_error($resp)) return '';
  $code = (int) wp_remote_retrieve_response_code($resp);
  if ($code < 200 || $code >= 300) return '';

  $json = json_decode(wp_remote_retrieve_body($resp), true);
  $text = (string)($json['candidates'][0]['content']['parts'][0]['text'] ?? '');
  $text = preg_replace('/\s+/', ' ', trim($text));
  if (mb_strlen($text) > 260) $text = mb_substr($text, 0, 260) . '…';
  return $text;
}

/** ===== Text Cleanups ===== */
function kld_dyk_normalize_fact(string $fact): string {
  $fact = preg_replace('/\s+/', ' ', trim($fact));
  $fact = preg_replace('/^\s*(wusstest du schon\??|did you know\??)\s*[:\-–—]?\s*/iu', '', $fact);
  $fact = preg_replace('/\b(wird\s+in\s+kuladig\s+so\s+beschrieben)\s*:\s*/iu', '', $fact);
  $fact = ltrim($fact, " \t\n\r\0\x0B-–—:;");

  $max = 240;
  if (mb_strlen($fact) > $max) {
    $cut = mb_substr($fact, 0, $max);
    $cut = preg_replace('/\s+\S*$/u', '', $cut);
    $fact = rtrim($cut) . '…';
  }
  return trim($fact);
}

/** Fallback-Fakt, falls die KI keine Ausgabe liefert. */
function kld_dyk_fallback_fact(string $desc_raw): string {
  $plain = wp_strip_all_tags($desc_raw);
  $plain = preg_replace('/\s+/', ' ', trim($plain));
  $plain = preg_replace('/^\s*(wusstest du schon\??|did you know\??)\s*[:\-–—]?\s*/iu', '', $plain);
  $plain = preg_replace('/\b(wird\s+in\s+kuladig\s+so\s+beschrieben)\s*:\s*/iu', '', $plain);

  $max = 240;
  if (mb_strlen($plain) > $max) {
    $cut = mb_substr($plain, 0, $max);
    $cut = preg_replace('/\s+\S*$/u', '', $cut);
    return rtrim($cut) . '…';
  }
  return $plain;
}

/** HTTP-Helper fuer JSON-Antworten mit einfacher Fehlerbehandlung. */
function kld_dyk_http_get_json(string $url) {
  $resp = wp_remote_get($url, ['timeout' => 15]);
  if (is_wp_error($resp)) return null;
  $code = (int) wp_remote_retrieve_response_code($resp);
  if ($code < 200 || $code >= 300) return null;
  $json = json_decode(wp_remote_retrieve_body($resp), true);
  return is_array($json) ? $json : null;
}

/** ===== Shortcode ===== */
add_shortcode('kuladig_daily_didyouknow', function () {
  kld_dyk_ensure_today();

  $state = get_option(KULADIG_DYK_OPTION, []);
  if (empty($state['id']) || empty($state['token'])) return '<p>Kein Tages-Ort verfügbar.</p>';

  $img  = 'https://www.kuladig.de/api/public/Dokument?token=' . rawurlencode((string)$state['token']);
  $link = esc_url('/ort/?id=' . rawurlencode((string)$state['id']));

  ob_start(); ?>
  <div class="kuladig-dyk kuladig-dyk--dark">
    <div class="kuladig-dyk-media" style="background-image:url('<?php echo esc_url($img); ?>')"></div>

    <div class="kuladig-dyk-body">
      <div class="kuladig-dyk-badge">WUSSTEST DU SCHON?</div>
      <div class="kuladig-dyk-title"><?php echo esc_html((string)$state['name']); ?></div>
      <div class="kuladig-dyk-text"><?php echo esc_html((string)$state['fact']); ?></div>
      <a class="kuladig-dyk-btn" href="<?php echo $link; ?>">Erkunden →</a>
    </div>

    <div class="kuladig-dyk-mark">?</div>
  </div>
  <?php return ob_get_clean();
});

/**
 * Front Kategorien
 */
/**
 * Shortcode: [kuladig_kategorien]
 * Beispiel:
 * [kuladig_kategorien title="Kulturelle Landschaften entdecken" subtitle="Entdecke historische Orte, Naturdenkmäler und Kulturerbe über diese Kategorien."
 *  button_text="Alle Kategorien anzeigen" button_url="/kategorien/" limit="6" type="Projekt" cards_base_url="/karte/" cards_param="projekt"]
 *
 * Quelle API: /api/public/Listen (Projekt-Liste), /api/public/Objekt (Gesamtanzahl + ThumbnailToken) :contentReference[oaicite:1]{index=1}
 */

if (!defined('KLD_CAT_LISTS_TTL')) define('KLD_CAT_LISTS_TTL', 12 * HOUR_IN_SECONDS);
if (!defined('KLD_CAT_PROJECT_TTL')) define('KLD_CAT_PROJECT_TTL', 6 * HOUR_IN_SECONDS);

add_action('wp_enqueue_scripts', function () {
  // Optional: falls du Icons (Dashicons) nutzen willst
  wp_enqueue_style('dashicons');
});

add_shortcode('kuladig_kategorien', function ($atts) {

  $atts = shortcode_atts([
    'title'          => 'Kulturelle Landschaften entdecken',
    'subtitle'       => 'Entdecke historische Orte, Naturdenkmäler und Kulturerbe über diese Kategorien.',
    'button_text'    => 'Alle Kategorien anzeigen',
    'button_url'     => '/kategorien/',
    'limit'          => '6',
    'type'           => 'Projekt',   // Projekt | Thema | Fachsicht (nur Projekt ist hier komplett umgesetzt)
    'cards_base_url' => '/karte/',
    'cards_param'    => 'projekt',   // Query-Param für Kartenlinks
  ], $atts, 'kuladig_kategorien');

  $limit = max(1, min(12, (int)$atts['limit']));
  $type  = trim((string)$atts['type']);

  $lists = kld_cat_get_lists();
  $items = kld_cat_extract_list($lists, $type);

  if (!$items) {
    return '<p>Keine Kategorien gefunden.</p>';
  }

  // normalize + slice
  $norm = [];
  foreach ($items as $row) {
    $n = kld_cat_normalize_list_item($row);
    if (!empty($n['id']) && !empty($n['name'])) $norm[] = $n;
  }
  $norm = array_slice($norm, 0, $limit);

  if (!$norm) {
    return '<p>Keine Kategorien gefunden.</p>';
  }

  $title       = esc_html($atts['title']);
  $subtitle    = esc_html($atts['subtitle']);
  $button_text = esc_html($atts['button_text']);
  $button_url  = esc_url($atts['button_url']);

  ob_start(); ?>
    <section class="kld-cat">
      <div class="kld-cat-head">
        <div class="kld-cat-head-left">
          <h2 class="kld-cat-title"><?php echo $title; ?></h2>
          <p class="kld-cat-subtitle"><?php echo $subtitle; ?></p>
        </div>

        <?php if ($button_url): ?>
          <a class="kld-cat-head-btn" href="<?php echo $button_url; ?>">
            <?php echo $button_text; ?> <span aria-hidden="true">→</span>
          </a>
        <?php endif; ?>
      </div>

      <div class="kld-cat-grid">
        <?php foreach ($norm as $cat):
          $id   = (int)$cat['id'];
          $name = (string)$cat['name'];

          // Für Projekt: Bild + Count aus /api/public/Objekt?Projekt=ID :contentReference[oaicite:2]{index=2}
          $img = '';
          $count = null;

          if (mb_strtolower($type) === 'projekt') {
            $stats = kld_cat_get_project_stats($id);
            $count = $stats['count'];
            if (!empty($stats['token'])) {
              $img = 'https://www.kuladig.de/api/public/Dokument?token=' . rawurlencode($stats['token']);
            }
          }

          $card_link = esc_url(add_query_arg(
            [$atts['cards_param'] => $id],
            $atts['cards_base_url']
          ));

          $count_label = ($count !== null && $count >= 0)
            ? (number_format_i18n($count) . '+ Orte')
            : 'Orte';

          ?>
          <a class="kld-cat-card" href="<?php echo $card_link; ?>">
            <div class="kld-cat-card-media" style="<?php echo $img ? 'background-image:url(' . esc_url($img) . ');' : ''; ?>">
              <?php if (!$img): ?>
                <div class="kld-cat-card-media-fallback"></div>
              <?php endif; ?>
            </div>

            <div class="kld-cat-card-body">
              <div class="kld-cat-card-name"><?php echo esc_html($name); ?></div>
              <span class="kld-cat-card-chip"><?php echo esc_html($count_label); ?></span>
              <span class="kld-cat-card-arrow" aria-hidden="true">↗</span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php
  return ob_get_clean();
});

/* ===== API Helpers ===== */

function kld_cat_get_lists(): array {
  $cache_key = 'kld_cat_lists_v1';
  $cached = get_transient($cache_key);
  if (is_array($cached)) return $cached;

  $url = 'https://www.kuladig.de/api/public/Listen';
  $json = kld_cat_http_get_json($url);
  if (!is_array($json)) $json = [];

  set_transient($cache_key, $json, KLD_CAT_LISTS_TTL);
  return $json;
}

/**
 * Versucht "Projekt" / "Thema" / "Fachsicht" aus der /Listen-Antwort zu ziehen.
 * Die Doku sagt, /Listen liefert u.a. Projekt, Thema, Fachsicht :contentReference[oaicite:3]{index=3}
 */
function kld_cat_extract_list(array $lists, string $wanted): array {
  if (!$lists) return [];

  // 1) Direkt-Key
  if (isset($lists[$wanted]) && is_array($lists[$wanted])) return $lists[$wanted];

  // 2) Case-insensitive Key
  foreach ($lists as $k => $v) {
    if (is_string($k) && is_array($v) && mb_strtolower($k) === mb_strtolower($wanted)) return $v;
  }

  // 3) Manche APIs liefern "Listen" => [ {Name:..., Eintraege:[...]} ... ]
  if (isset($lists['Listen']) && is_array($lists['Listen'])) {
    foreach ($lists['Listen'] as $block) {
      $bn = (string)($block['Name'] ?? $block['name'] ?? '');
      if ($bn !== '' && mb_strtolower($bn) === mb_strtolower($wanted)) {
        $entries = $block['Eintraege'] ?? $block['entries'] ?? $block['Werte'] ?? null;
        if (is_array($entries)) return $entries;
      }
    }
  }

  return [];
}

/** Normalisiert Listen-Eintraege auf ID/Name. */
function kld_cat_normalize_list_item($row): array {
  if (!is_array($row)) return ['id' => 0, 'name' => ''];

  $id = $row['Id'] ?? $row['id'] ?? $row['ProjektId'] ?? $row['projektId'] ?? 0;
  $name = $row['Name'] ?? $row['name'] ?? $row['Bezeichnung'] ?? $row['bezeichnung'] ?? '';

  return [
    'id' => (int)$id,
    'name' => (string)$name,
  ];
}

/**
 * Holt Count + ein ThumbnailToken für ein Projekt:
 * /api/public/Objekt liefert Ergebnis[] mit ThumbnailToken und Gesamtanzahl :contentReference[oaicite:4]{index=4}
 */
function kld_cat_get_project_stats(int $projectId): array {
  $cache_key = 'kld_cat_proj_' . $projectId;
  $cached = get_transient($cache_key);
  if (is_array($cached)) return $cached;

  $base = 'https://www.kuladig.de/api/public/Objekt';
  $url = add_query_arg([
    'ObjektTyp' => 'KuladigObjekt',
    'Projekt'   => $projectId,
    'Seite'     => 0,
  ], $base);

  $json = kld_cat_http_get_json($url);

  $count = null;
  $token = '';

  if (is_array($json)) {
    $count = $json['Gesamtanzahl'] ?? $json['GesamtAnzahl'] ?? null;

    $results = $json['Ergebnis'] ?? [];
    if (is_array($results)) {
      foreach ($results as $r) {
        if (!empty($r['ThumbnailToken'])) { $token = (string)$r['ThumbnailToken']; break; }
      }
    }
  }

  $out = [
    'count' => is_numeric($count) ? (int)$count : null,
    'token' => trim($token),
  ];

  set_transient($cache_key, $out, KLD_CAT_PROJECT_TTL);
  return $out;
}

/** API-GET fuer Kategorien, liefert JSON oder null. */
function kld_cat_http_get_json(string $url) {
  $resp = wp_remote_get($url, ['timeout' => 15]);
  if (is_wp_error($resp)) return null;

  $code = (int)wp_remote_retrieve_response_code($resp);
  if ($code < 200 || $code >= 300) return null;

  $json = json_decode(wp_remote_retrieve_body($resp), true);
  return is_array($json) ? $json : null;
}

/**
 * Unsere Empfehlungen
 */
/**
 * Shortcode: [kuladig_empfehlungen apiaccesset="jci75d5363" limit="6" title="Unsere Empfehlungen"]
 * Benutzung: Elementor -> "Shortcode" Widget -> den Shortcode einfügen
 */

add_shortcode('kuladig_empfehlungen', function ($atts) {
  $a = shortcode_atts([
    'apiaccesset' => 'jci75d5363',
    'limit'       => '6',
      
  ], $atts, 'kuladig_empfehlungen');

  $api   = preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$a['apiaccesset']);
  $limit = max(1, min(24, (int)$a['limit']));
  $title = esc_html((string)$a['title']);

  // JSON Content Importer Template
  $jci = <<<HTML
[jsoncontentimporter apiaccesset="{$api}" basenode=""]
<div class="kld-reco">
  <h2 class="kld-reco-title">{$title}</h2>

  <div class="kld-reco-grid">
    {subloop-array:Ergebnis:{$limit}}

      <a class="kld-reco-card" href="/ort/?id={Ergebnis.Id}">
        <div class="kld-reco-media">
          <img
            loading="lazy"
            src="https://www.kuladig.de/api/public/Dokument?token={Ergebnis.ThumbnailToken}"
            alt="{Ergebnis.Name}"
            onerror="this.style.display='none'; this.parentNode.classList.add('kld-reco-noimg');"
          >

          {subloop-array:Ergebnis.Projekte:1}
            <div class="kld-reco-badge">{Projekte.Name}</div>
          {/subloop-array:Ergebnis.Projekte}
        </div>

        <div class="kld-reco-body">
          <h3 class="kld-reco-h3">{Ergebnis.Name}</h3>
          <p class="kld-reco-desc">{Ergebnis.Beschreibung}</p>
          <span class="kld-reco-link">Mehr erfahren →</span>
        </div>
      </a>

    {/subloop-array:Ergebnis}
  </div>
</div>
[/jsoncontentimporter]
HTML;

  return do_shortcode($jci);
});

/**
 * Full-Page Map
 */
add_shortcode( 'code_snippets_export_13', function () {
	ob_start();
	?>

	<!-- Leaflet & MarkerCluster CSS -->
	<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
	<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
	<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
	
	<style>
	  /* ==== Layout wie Screenshot ==== */
	  .kld-map-wrap{
	    position:relative;
	    width:100%;
	    height: 80vh;
	    min-height: 620px;
	    border-radius: 18px;
	    overflow:hidden;
	    background:#e2e8f0;
	  }
	  #kuladig-map{
	    width:100%;
	    height:100%;
	  }
	
	  /* Search Overlay */
	  .kld-search{
	    position:absolute;
	    top: 18px;
	    left: 18px;
	    z-index: 999;
	    width: min(520px, calc(100vw - 36px));
	  }
	  .kld-searchbar{
	    display:flex;
	    align-items:center;
	    gap: 10px;
	    background:#fff;
	    border: 1px solid rgba(15,23,42,.08);
	    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
	    border-radius: 14px;
	    padding: 10px 10px;
	  }
	  .kld-searchbar .kld-ico{
	    width: 34px;
	    height: 34px;
	    border-radius: 10px;
	    display:flex;
	    align-items:center;
	    justify-content:center;
	    color:#0f172a;
	    background: rgba(15,23,42,.04);
	    flex: 0 0 auto;
	  }
	  .kld-searchbar input{
	    border:0;
	    outline:0;
	    flex:1;
	    font-size:14px;
	    color:#0f172a;
	    background:transparent;
	  }
	  .kld-searchbar button{
	    border:0;
	    cursor:pointer;
	    border-radius: 12px;
	    padding: 10px 14px;
	    font-weight: 800;
	  }
	  .kld-btn-filter{
	    background: rgba(15,23,42,.04);
	    color:#0f172a;
	    padding: 10px 12px;
	  }
	  .kld-btn-search{
	    background:#0f172a;
	    color:#fff;
	    padding: 10px 16px;
	  }
	
	  /* Dropdown */
	  .kld-dropdown{
	    margin-top:10px;
	    background:#fff;
	    border: 1px solid rgba(15,23,42,.08);
	    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
	    border-radius: 14px;
	    overflow:hidden;
	    display:none;
	  }
	  .kld-dropdown.is-open{ display:block; }
	  .kld-dropdown .kld-row{
	    padding: 12px 14px;
	    display:flex;
	    align-items:center;
	    justify-content:space-between;
	    gap: 10px;
	    font-size: 13px;
	    color:#0f172a;
	  }
	  .kld-dropdown .kld-row + .kld-row{
	    border-top: 1px solid rgba(15,23,42,.06);
	  }
	
	  /* Suggestions */
	  .kld-suggest{
	    margin-top:10px;
	    background:#fff;
	    border: 1px solid rgba(15,23,42,.08);
	    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
	    border-radius: 14px;
	    overflow:hidden;
	    display:none;
	  }
	  .kld-suggest.is-open{ display:block; }
	  .kld-suggest a{
	    display:block;
	    padding: 10px 12px;
	    text-decoration:none;
	    color:#0f172a;
	    font-size:13px;
	  }
	  .kld-suggest a:hover{
	    background: rgba(15,23,42,.04);
	  }
	
	  /* Leaflet controls: Zoom unten rechts wie Screenshot */
	  .leaflet-control-zoom{
	    border:0 !important;
	    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14) !important;
	    border-radius: 14px !important;
	    overflow:hidden;
	  }
	  .leaflet-control-zoom a{
	    border:0 !important;
	    width: 42px !important;
	    height: 42px !important;
	    line-height: 42px !important;
	  }
	
	  /* kleiner Loader Text */
	  .kld-loading{
	    position:absolute;
	    bottom: 16px;
	    left: 16px;
	    z-index: 999;
	    background: rgba(255,255,255,.92);
	    border: 1px solid rgba(15,23,42,.08);
	    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
	    border-radius: 12px;
	    padding: 8px 10px;
	    font-size: 12px;
	    color:#0f172a;
	    display:none;
	  }
	  .kld-loading.is-on{ display:block; }
	
	  /* ===== Route UI (hell, passend zum bestehenden Stil) ===== */
	  .kld-route-tab{
	    position:absolute;
	    top: 18px;
	    right: 18px;
	    z-index: 999;
	    border: 1px solid rgba(15,23,42,.08);
	    background:#fff;
	    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
	    border-radius: 14px;
	    padding: 10px 12px;
	    font-weight: 900;
	    color:#0f172a;
	    cursor:pointer;
	    display:flex;
	    align-items:center;
	    gap:10px;
	  }
	
	  .kld-route-count{
	    min-width: 24px;
	    height: 24px;
	    border-radius: 999px;
	    display:inline-flex;
	    align-items:center;
	    justify-content:center;
	    background:#eef2ff;
	    border: 1px solid rgba(79,70,229,.18);
	    color:#4f46e5;
	    font-size:12px;
	    font-weight: 900;
	  }
	
	  .kld-route-panel{
	    position:absolute;
	    top: 18px;
	    right: 18px;
	    z-index: 1000;
	    width: min(380px, calc(100vw - 36px));
	    max-height: calc(80vh - 36px);
	    background:#fff;
	    border: 1px solid rgba(15,23,42,.08);
	    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.14);
	    border-radius: 18px;
	    overflow:hidden;
	    display:none;
	  }
	
	  .kld-route-panel.is-open{ display:block; }
	  .kld-route-tab.is-hidden{ display:none; }
	
	  .kld-route-head{
	    padding: 14px 14px 10px;
	    display:flex;
	    align-items:flex-start;
	    justify-content:space-between;
	    gap:12px;
	    border-bottom: 1px solid rgba(15,23,42,.06);
	  }
	
	  .kld-route-kicker{ font-size:12px; font-weight:900; color:#64748b; }
	  .kld-route-title{ font-size:18px; font-weight:900; color:#0f172a; line-height:1.1; }
	
	  .kld-route-close{
	    width:36px;height:36px;
	    border-radius: 12px;
	    border: 1px solid rgba(15,23,42,.08);
	    background: rgba(15,23,42,.04);
	    color:#0f172a;
	    cursor:pointer;
	    font-size: 20px;
	    font-weight: 900;
	    line-height: 1;
	  }
	
	  .kld-route-actions{
	    padding: 12px 14px;
	    display:flex;
	    gap:10px;
	    flex-wrap:wrap;
	  }
	
	  .kld-route-btn{
	    border: 1px solid rgba(15,23,42,.10);
	    background: rgba(15,23,42,.04);
	    color:#0f172a;
	    padding: 10px 12px;
	    border-radius: 12px;
	    font-weight: 900;
	    cursor:pointer;
	    font-size: 13px;
	  }
	
	  .kld-route-btn-primary{
	    background:#0f172a;
	    border-color:#0f172a;
	    color:#fff;
	  }
	
	  .kld-route-hint{
	    padding: 0 14px 10px;
	    font-size: 12px;
	    color:#64748b;
	  }
	
	  .kld-route-list{
	    list-style:none;
	    margin:0;
	    padding: 8px 10px 12px;
	    overflow:auto;
	    max-height: calc(80vh - 210px);
	  }
	
	  .kld-route-item{
	    border: 1px solid rgba(15,23,42,.08);
	    border-radius: 14px;
	    padding: 10px 10px;
	    display:flex;
	    align-items:center;
	    justify-content:space-between;
	    gap:10px;
	    background:#fff;
	  }
	
	  .kld-route-item + .kld-route-item{ margin-top:10px; }
	
	  .kld-route-left{
	    display:flex;
	    align-items:center;
	    gap:10px;
	    min-width: 0;
	  }
	
	  .kld-route-handle{
	    width: 32px;
	    height: 32px;
	    border-radius: 12px;
	    border: 1px solid rgba(15,23,42,.08);
	    background: rgba(15,23,42,.04);
	    display:flex;
	    align-items:center;
	    justify-content:center;
	    cursor:grab;
	    flex: 0 0 auto;
	  }
	
	  .kld-route-num{
	    width: 28px;
	    height: 28px;
	    border-radius: 999px;
	    background:#eef2ff;
	    border: 1px solid rgba(79,70,229,.20);
	    color:#4f46e5;
	    font-weight: 900;
	    font-size: 12px;
	    display:flex;
	    align-items:center;
	    justify-content:center;
	    flex: 0 0 auto;
	  }
	
	  .kld-route-text{
	    min-width:0;
	  }
	
	  .kld-route-name{
	    font-size: 13px;
	    font-weight: 900;
	    color:#0f172a;
	    white-space:nowrap;
	    overflow:hidden;
	    text-overflow:ellipsis;
	    max-width: 220px;
	  }
	
	  .kld-route-sub{
	    font-size: 12px;
	    color:#94a3b8;
	    white-space:nowrap;
	    overflow:hidden;
	    text-overflow:ellipsis;
	    max-width: 220px;
	  }
	
	  .kld-route-right{
	    display:flex;
	    align-items:center;
	    gap:8px;
	  }
	
	  .kld-route-mini{
	    width: 34px; height: 34px;
	    border-radius: 12px;
	    border: 1px solid rgba(15,23,42,.08);
	    background: rgba(15,23,42,.04);
	    cursor:pointer;
	    font-weight: 900;
	    color:#0f172a;
	  }
	
	  .kld-route-mini.danger{
	    background: rgba(220,38,38,.08);
	    border-color: rgba(220,38,38,.18);
	    color:#dc2626;
	  }
	
	  .kld-route-empty{
	    padding: 16px 14px 18px;
	    color:#64748b;
	    font-size: 13px;
	    display:none;
	  }
	
	  .kld-route-empty.is-on{ display:block; }
	
	  /* Popup Button */
	  .kld-pop-actions{
	    margin-top: 8px;
	    display:flex;
	    gap:8px;
	    flex-wrap:wrap;
	  }
	  .kld-pop-add{
	    border:0;
	    cursor:pointer;
	    border-radius: 10px;
	    padding: 8px 10px;
	    font-weight: 900;
	    background:#0f172a;
	    color:#fff;
	  }
	  .kld-pop-link{
	    display:inline-block;
	    border: 1px solid rgba(15,23,42,.10);
	    background: rgba(15,23,42,.04);
	    color:#0f172a;
	    border-radius: 10px;
	    padding: 7px 10px;
	    text-decoration:none;
	    font-weight: 900;
	  }
	</style>
	
	<div class="kld-map-wrap">
	  <div id="kuladig-map"></div>
	
	  <div class="kld-search">
	    <div class="kld-searchbar">
	      <div class="kld-ico" aria-hidden="true">
	        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
	          <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>
	          <path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
	        </svg>
	      </div>
	
	      <input id="kld-q" type="text" placeholder="Ort oder Stichwort eingeben" autocomplete="off">
	
	      <button class="kld-btn-filter" id="kld-filter-btn" type="button" title="Filter">
	        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
	          <path d="M4 7h10M18 7h2M10 7a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
	          <path d="M4 17h2M10 17h10M18 17a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
	        </svg>
	      </button>
	
	      <button class="kld-btn-search" id="kld-search-btn" type="button">Suchen</button>
	    </div>
	
	    <div class="kld-dropdown" id="kld-dropdown">
	      <div class="kld-row">
	        <div>Nur Orte mit Bild</div>
	        <input id="kld-only-img" type="checkbox" checked>
	      </div>
	      <div class="kld-row">
	        <div>Projekt-ID (optional)</div>
	        <input id="kld-project" type="number" placeholder="z.B. 2085" style="width:120px">
	      </div>
	    </div>
	
	    <div class="kld-suggest" id="kld-suggest"></div>
	  </div>
	
	  <div class="kld-loading" id="kld-loading">Lade Orte…</div>
	
	  <button class="kld-route-tab" id="kld-route-tab" type="button" aria-controls="kld-route-panel" aria-expanded="false">
	    Route <span class="kld-route-count" id="kld-route-count">0</span>
	  </button>
	
	  <aside class="kld-route-panel" id="kld-route-panel" aria-hidden="true">
	    <div class="kld-route-head">
	      <div>
	        <div class="kld-route-kicker">Route</div>
	        <div class="kld-route-title">Ausgewählte Orte</div>
	      </div>
	      <button class="kld-route-close" id="kld-route-close" type="button" title="Schließen">×</button>
	    </div>
	
	    <div class="kld-route-actions">
	      <button class="kld-route-btn kld-route-btn-primary" id="kld-route-open-gmaps" type="button">
	        In Google Maps öffnen
	      </button>
	      <button class="kld-route-btn" id="kld-route-clear" type="button">
	        Leeren
	      </button>
	    </div>
	
	    <div class="kld-route-hint">
	      Zieh die Orte per Drag & Drop in die richtige Reihenfolge.
	    </div>
	
	    <ul class="kld-route-list" id="kld-route-list"></ul>
	
	    <div class="kld-route-empty" id="kld-route-empty">
	      Noch keine Orte ausgewählt. Öffne einen Marker und klicke „Zur Route“.
	    </div>
	  </aside>
	</div>
	
	<!-- Leaflet & MarkerCluster JS -->
	<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
	<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
	
	<script>
	(function(){
	  const elMap = document.getElementById('kuladig-map');
	  if (!elMap || typeof L === 'undefined') return;
	
	  const elQ = document.getElementById('kld-q');
	  const elBtnSearch = document.getElementById('kld-search-btn');
	  const elFilterBtn = document.getElementById('kld-filter-btn');
	  const elDropdown = document.getElementById('kld-dropdown');
	  const elSuggest = document.getElementById('kld-suggest');
	  const elOnlyImg = document.getElementById('kld-only-img');
	  const elProject = document.getElementById('kld-project');
	  const elLoading = document.getElementById('kld-loading');
	
	  // Map
	  const map = L.map('kuladig-map', { zoomControl:false }).setView([51.2, 10.5], 6);
	  L.control.zoom({ position: 'bottomright' }).addTo(map);
	
	  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
	    maxZoom: 19,
	    attribution: '&copy; OpenStreetMap-Mitwirkende'
	  }).addTo(map);
	
	  const cluster = L.markerClusterGroup();
	  map.addLayer(cluster);
	
	  // Custom Marker Icons
	  function mkIcon(color){
	    const svg = encodeURIComponent(`
	      <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24">
	        <path fill="${color}" d="M12 2c-3.9 0-7 3.1-7 7 0 5.3 7 13 7 13s7-7.7 7-13c0-3.9-3.1-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6a2.5 2.5 0 0 1 0 5.5z"/>
	      </svg>
	    `);
	    return L.icon({
	      iconUrl: "data:image/svg+xml," + svg,
	      iconSize: [28,28],
	      iconAnchor: [14,28],
	      popupAnchor: [0,-26]
	    });
	  }
	  const ICON_DARK = mkIcon("#0f172a");
	  const ICON_GREEN = mkIcon("#16a34a");
	
	  // State
	  let all = [];
	  let markerById = new Map();
	  let activeId = "";
	
	  // ===== ROUTE BUILDER =====
	  const elRouteTab   = document.getElementById('kld-route-tab');
	  const elRoutePanel = document.getElementById('kld-route-panel');
	  const elRouteClose = document.getElementById('kld-route-close');
	  const elRouteList  = document.getElementById('kld-route-list');
	  const elRouteEmpty = document.getElementById('kld-route-empty');
	  const elRouteCount = document.getElementById('kld-route-count');
	  const elRouteClear = document.getElementById('kld-route-clear');
	  const elRouteGmaps = document.getElementById('kld-route-open-gmaps');
	
	  const ROUTE_KEY = 'kld_route_v1';
	  let route = [];
	
	  const routeLine = L.polyline([], { weight: 5, opacity: 0.9 }).addTo(map);
	  const routeNums = L.layerGroup().addTo(map);
	
	  function normalizeRouteArr(arr){
	    if(!Array.isArray(arr)) return [];
	    return arr
	      .map(x => {
	        const lat = (typeof x?.lat === "string") ? parseFloat(x.lat) : x?.lat;
	        const lon = (typeof x?.lon === "string") ? parseFloat(x.lon) : x?.lon;
	        return {
	          id: String(x?.id || ""),
	          name: String(x?.name || x?.id || "Ort"),
	          lat, lon
	        };
	      })
	      .filter(x => x.id && Number.isFinite(x.lat) && Number.isFinite(x.lon));
	  }
	
	  function loadRoute(){
	  try{
	    const raw = localStorage.getItem(ROUTE_KEY);
	    if(!raw){ route = []; return; }
	    const arr = JSON.parse(raw);
	    if(!Array.isArray(arr)){ route = []; return; }
	
	    route = arr.map(x => {
	      const la = (typeof x?.lat === "string") ? parseFloat(x.lat) : x?.lat;
	      const lo = (typeof x?.lon === "string") ? parseFloat(x.lon) : x?.lon;
	      return {
	        id: String(x?.id || ""),
	        name: String(x?.name || x?.id || "Ort"),
	        lat: la,
	        lon: lo
	      };
	    }).filter(x => x.id && Number.isFinite(x.lat) && Number.isFinite(x.lon));
	
	  }catch(e){
	    route = [];
	  }
	}
	
	
	  function saveRoute(){
	    try{ localStorage.setItem(ROUTE_KEY, JSON.stringify(route)); }catch(e){}
	  }
	
	  // Live update (andere Tabs / Ort-Seite)
	  window.addEventListener('storage', (e) => {
	    if(e.key !== ROUTE_KEY) return;
	    loadRoute();
	    renderRouteList(false);
	  });
	
	  // Optional: gleicher Tab kann CustomEvent feuern
	  window.addEventListener('kld:route-updated', (e) => {
	    if(!e?.detail?.route) return;
	    route = normalizeRouteArr(e.detail.route);
	    renderRouteList(false);
	  });
	
	  function openRoutePanel(){
	    elRoutePanel.classList.add('is-open');
	    elRoutePanel.setAttribute('aria-hidden','false');
	    elRouteTab.classList.add('is-hidden');
	    elRouteTab.setAttribute('aria-expanded','true');
	  }
	  function closeRoutePanel(){
	    elRoutePanel.classList.remove('is-open');
	    elRoutePanel.setAttribute('aria-hidden','true');
	    elRouteTab.classList.remove('is-hidden');
	    elRouteTab.setAttribute('aria-expanded','false');
	  }
	
	  elRouteTab?.addEventListener('click', openRoutePanel);
	  elRouteClose?.addEventListener('click', closeRoutePanel);
	
	  function numIcon(n){
	    return L.divIcon({
	      className: '',
	      html: `<div style="
	        width:28px;height:28px;border-radius:999px;
	        background:#eef2ff;border:1px solid rgba(79,70,229,.20);
	        color:#4f46e5;font-weight:900;font-size:12px;
	        display:flex;align-items:center;justify-content:center;
	      ">${n}</div>`,
	      iconSize:[28,28],
	      iconAnchor:[14,14],
	    });
	  }
	
	  function updateRouteMap(){
	    const latlngs = route.map(r => [r.lat, r.lon]);
	    routeLine.setLatLngs(latlngs);
	
	    routeNums.clearLayers();
	    route.forEach((r, idx) => {
	      routeNums.addLayer(L.marker([r.lat, r.lon], { icon: numIcon(idx+1), interactive:false }));
	    });
	  }
	
	  function renderRouteList(shouldSave = true){
	    elRouteCount.textContent = String(route.length);
	    elRouteEmpty.classList.toggle('is-on', route.length === 0);
	
	    elRouteList.innerHTML = route.map((r, idx) => `
	      <li class="kld-route-item" draggable="true" data-idx="${idx}">
	        <div class="kld-route-left">
	          <div class="kld-route-handle" title="Ziehen">≡</div>
	          <div class="kld-route-num">${idx+1}</div>
	          <div class="kld-route-text">
	            <div class="kld-route-name">${escapeHtml(r.name)}</div>
	            <div class="kld-route-sub">${escapeHtml(r.id)}</div>
	          </div>
	        </div>
	        <div class="kld-route-right">
	          <button class="kld-route-mini" type="button" data-focus="${idx}" title="Auf Karte zeigen">⌖</button>
	          <button class="kld-route-mini danger" type="button" data-remove="${idx}" title="Entfernen">✕</button>
	        </div>
	      </li>
	    `).join('');
	
	    updateRouteMap();
	    if(shouldSave) saveRoute();
	  }
	
	  function addToRouteById(id){
	    const obj = all.find(o => o.id === id);
	    if(!obj) return;
	
	    if(route.some(x => x.id === id)) {
	      openRoutePanel();
	      return;
	    }
	
	    route.push({ id: obj.id, name: obj.name, lat: obj.lat, lon: obj.lon });
	    renderRouteList(true);
	    openRoutePanel();
	  }
	
	  // Popup Button Click (Leaflet Popup DOM)
	  map.on('popupopen', (e) => {
	    const root = e.popup?.getElement?.();
	    if(!root) return;
	
	    // Falls du später im Popup einen Button einfügst:
	    const btn = root.querySelector('[data-add-route]');
	    if(!btn) return;
	
	    btn.addEventListener('click', () => {
	      const id = btn.getAttribute('data-add-route');
	      if(!id) return;
	      addToRouteById(id);
	    }, { once:true });
	  });
	
	  // List buttons (focus/remove)
	  elRouteList.addEventListener('click', (e) => {
	    const rm = e.target.closest('[data-remove]');
	    if(rm){
	      const idx = parseInt(rm.getAttribute('data-remove'), 10);
	      if(!Number.isNaN(idx)){
	        route.splice(idx,1);
	        renderRouteList(true);
	      }
	      return;
	    }
	
	    const fc = e.target.closest('[data-focus]');
	    if(fc){
	      const idx = parseInt(fc.getAttribute('data-focus'), 10);
	      const r = route[idx];
	      if(r){
	        map.setView([r.lat, r.lon], Math.max(map.getZoom(), 15));
	        setActive(r.id);
	      }
	      return;
	    }
	  });
	
	  // Drag & Drop reorder
	  let dragFrom = null;
	
	  elRouteList.addEventListener('dragstart', (e) => {
	    const li = e.target.closest('.kld-route-item');
	    if(!li) return;
	    dragFrom = parseInt(li.getAttribute('data-idx'), 10);
	    li.style.opacity = '0.6';
	    e.dataTransfer.effectAllowed = 'move';
	  });
	
	  elRouteList.addEventListener('dragend', (e) => {
	    const li = e.target.closest('.kld-route-item');
	    if(li) li.style.opacity = '';
	    dragFrom = null;
	  });
	
	  elRouteList.addEventListener('dragover', (e) => {
	    e.preventDefault();
	    e.dataTransfer.dropEffect = 'move';
	  });
	
	  elRouteList.addEventListener('drop', (e) => {
	    e.preventDefault();
	    const li = e.target.closest('.kld-route-item');
	    if(!li) return;
	
	    const dragTo = parseInt(li.getAttribute('data-idx'), 10);
	    if(dragFrom === null || Number.isNaN(dragTo) || dragTo === dragFrom) return;
	
	    const [moved] = route.splice(dragFrom, 1);
	    route.splice(dragTo, 0, moved);
	    renderRouteList(true);
	  });
	
	  // Clear
	  elRouteClear.addEventListener('click', () => {
	    route = [];
	    renderRouteList(true);
	  });
	
	  // Open in Google Maps
	  elRouteGmaps.addEventListener('click', () => {
	    if(route.length < 2) return;
	
	    const maxStops = 25;
	    const trimmed = route.slice(0, maxStops);
	
	    const origin = `${trimmed[0].lat},${trimmed[0].lon}`;
	    const dest   = `${trimmed[trimmed.length-1].lat},${trimmed[trimmed.length-1].lon}`;
	    const wpsArr = trimmed.slice(1, -1).map(r => `${r.lat},${r.lon}`);
	    const waypoints = wpsArr.length ? `&waypoints=${encodeURIComponent(wpsArr.join('|'))}` : '';
	
	    const url =
	      `https://www.google.com/maps/dir/?api=1` +
	      `&origin=${encodeURIComponent(origin)}` +
	      `&destination=${encodeURIComponent(dest)}` +
	      waypoints +
	      `&travelmode=walking`;
	
	    window.open(url, '_blank', 'noopener');
	  });
	
	  // Init route from storage
	  loadRoute();
	  renderRouteList(false);
		
		function refreshRoute(){
	  loadRoute();
	  renderRouteList(false);
	}
	
	// wenn du aus /ort/ zurückkommst (BFCache)
	window.addEventListener('pageshow', refreshRoute);
	
	// wenn Tab wieder Fokus bekommt
	window.addEventListener('focus', refreshRoute);
	
	// wenn Seite wieder sichtbar wird
	document.addEventListener('visibilitychange', () => {
	  if(!document.hidden) refreshRoute();
	});
	
	  // ===== Cache =====
	  const CACHE_TTL = 24 * 60 * 60 * 1000;
	  function cacheKey(projectId, onlyImg){
	    return `kld_map_cache_v2_p${projectId||0}_img${onlyImg?1:0}`;
	  }
	  function loadCache(key){
	    try{
	      const raw = localStorage.getItem(key);
	      if(!raw) return null;
	      const parsed = JSON.parse(raw);
	      if(!parsed || !parsed.ts || !Array.isArray(parsed.items)) return null;
	      if(Date.now() - parsed.ts > CACHE_TTL) return null;
	      return parsed.items;
	    }catch(e){ return null; }
	  }
	  function saveCache(key, items){
	    try{
	      localStorage.setItem(key, JSON.stringify({ ts: Date.now(), items }));
	    }catch(e){}
	  }
	
	  // ===== Render markers =====
	  function clearMarkers(){
	    cluster.clearLayers();
	    markerById.clear();
	    activeId = "";
	  }
	
	  function addOne(obj){
	    const m = L.marker([obj.lat, obj.lon], { icon: ICON_DARK });
	
	    /* WICHTIG:
	       Wenn du „Zur Route“ auch im Popup willst, dann so:
	       <button class="kld-pop-add" type="button" data-add-route="ID">Zur Route</button>
	    */
	    const popupHtml =
	      `<strong>${escapeHtml(obj.name)}</strong><br>` +
	      `<div class="kld-pop-actions">` +
	        `<a class="kld-pop-link" href="/ort/?id=${encodeURIComponent(obj.id)}">Mehr anzeigen</a>` +
	        `<button class="kld-pop-add" type="button" data-add-route="${escapeAttr(obj.id)}">Zur Route</button>` +
	      `</div>`;
	
	    m.bindPopup(popupHtml);
	    cluster.addLayer(m);
	    markerById.set(obj.id, m);
	  }
	
	  function renderList(items){
	    clearMarkers();
	    items.forEach(addOne);
	    if(items.length){
	      const b = L.latLngBounds(items.map(o => [o.lat, o.lon]));
	      map.fitBounds(b, { padding:[30,30] });
	    }
	  }
	
	  function setActive(id){
	    if(activeId && markerById.has(activeId)){
	      markerById.get(activeId).setIcon(ICON_DARK);
	    }
	    activeId = id;
	    if(id && markerById.has(id)){
	      const m = markerById.get(id);
	      m.setIcon(ICON_GREEN);
	      m.openPopup();
	      map.panTo(m.getLatLng());
	    }
	  }
	
	  // ===== Search =====
	  function normalize(s){ return (s||"").toString().toLowerCase().trim(); }
	
	  function buildSuggest(items){
	    if(!items.length){
	      elSuggest.classList.remove('is-open');
	      elSuggest.innerHTML = "";
	      return;
	    }
	    elSuggest.innerHTML = items.slice(0,8).map(o =>
	      `<a href="#" data-id="${escapeAttr(o.id)}">${escapeHtml(o.name)}</a>`
	    ).join("");
	    elSuggest.classList.add('is-open');
	  }
	
	  function doSearch(){
	    const q = normalize(elQ.value);
	    if(q.length < 2){
	      buildSuggest([]);
	      renderList(all);
	      return;
	    }
	    const hits = all.filter(o => o._n.includes(q)).slice(0, 2000);
	    buildSuggest(hits);
	    renderList(hits);
	  }
	
	  elBtnSearch.addEventListener('click', doSearch);
	  elQ.addEventListener('keydown', (e)=>{ if(e.key === 'Enter') doSearch(); });
	
	  let t = null;
	  elQ.addEventListener('input', ()=>{
	    clearTimeout(t);
	    t = setTimeout(doSearch, 180);
	  });
	
	  elSuggest.addEventListener('click', (e)=>{
	    const a = e.target.closest('a[data-id]');
	    if(!a) return;
	    e.preventDefault();
	    const id = a.getAttribute('data-id');
	    setActive(id);
	    elSuggest.classList.remove('is-open');
	  });
	
	  // Filter dropdown
	  elFilterBtn.addEventListener('click', ()=>{
	    elDropdown.classList.toggle('is-open');
	  });
	
	  // ===== Data loading =====
	  function showLoading(on){
	    elLoading.classList.toggle('is-on', !!on);
	  }
	
	  async function httpJson(url){
	    const res = await fetch(url, { cache: 'no-store' });
	    if(!res.ok) throw new Error("HTTP " + res.status);
	    return await res.json();
	  }
	
	  function buildBaseUrl(projectId){
	    let url = 'https://www.kuladig.de/api/public/Objekt?ObjektTyp=KuladigObjekt';
	    if(projectId) url += '&Projekt=' + encodeURIComponent(projectId);
	    return url;
	  }
	
	  function mapObj(r){
	    if(!r || !r.Id || !r.Punktkoordinate || !r.Punktkoordinate.coordinates) return null;
	    const lon = r.Punktkoordinate.coordinates[0];
	    const lat = r.Punktkoordinate.coordinates[1];
	    if(typeof lat !== 'number' || typeof lon !== 'number') return null;
	
	    const thumb = (r.ThumbnailToken || "").toString().trim();
	    return {
	      id: r.Id.toString(),
	      name: (r.Name || "").toString().trim(),
	      desc: (r.Beschreibung || "").toString().trim(),
	      lat, lon,
	      thumb,
	      _n: normalize(r.Name || "")
	    };
	  }
	
	  async function loadAll(){
	    const projectId = parseInt(elProject.value || "0", 10) || 0;
	    const onlyImg = !!elOnlyImg.checked;
	
	    const key = cacheKey(projectId, onlyImg);
	    const cached = loadCache(key);
	
	    if(cached && cached.length){
	      all = cached.map(o => ({...o, _n: normalize(o.name)}));
	      renderList(all);
	    }
	
	    showLoading(true);
	    try{
	      const base = buildBaseUrl(projectId);
	      const first = await httpJson(base + '&Seite=0');
	
	      const pages = Math.max(1, parseInt(first.AnzahlSeiten || first.Seitenanzahl || 1, 10));
	      const items = [];
	
	      function pushResults(json){
	        if(!json || !Array.isArray(json.Ergebnis)) return;
	        for(const r of json.Ergebnis){
	          const o = mapObj(r);
	          if(!o) continue;
	          if(!o.name || !o.desc) continue;
	          if(onlyImg && !o.thumb) continue;
	          items.push(o);
	        }
	      }
	
	      pushResults(first);
	
	      const limit = 4;
	      let i = 1;
	
	      async function worker(){
	        while(i < pages){
	          const page = i++;
	          try{
	            const json = await httpJson(base + '&Seite=' + page);
	            pushResults(json);
	          }catch(e){}
	        }
	      }
	
	      const workers = Array.from({length: Math.min(limit, pages)}, worker);
	      await Promise.all(workers);
	
	      const seen = new Set();
	      const dedup = [];
	      for(const o of items){
	        if(seen.has(o.id)) continue;
	        seen.add(o.id);
	        dedup.push(o);
	      }
	
	      all = dedup.map(o => ({...o, _n: normalize(o.name)}));
	      renderList(all);
	
	      saveCache(key, all.map(({_n, ...rest}) => rest));
	
	    }catch(err){
	      console.error('KuLaDig load error:', err);
	    }finally{
	      showLoading(false);
	    }
	  }
	
	  elOnlyImg.addEventListener('change', loadAll);
	  elProject.addEventListener('change', loadAll);
	
	  loadAll();
	
	  // ===== utils =====
	  function escapeHtml(s){
	    return (s||"").toString()
	      .replaceAll("&","&amp;")
	      .replaceAll("<","&lt;")
	      .replaceAll(">","&gt;")
	      .replaceAll('"',"&quot;")
	      .replaceAll("'","&#039;");
	  }
	  function escapeAttr(s){
	    return escapeHtml(s).replaceAll('"',"&quot;");
	  }
	})();
	</script>

	<?php
	return ob_get_clean();
} );

/**
 * kuladig_map
 */
add_shortcode('kuladig_map', function () {

  $uid = 'kldm_' . substr(md5(uniqid('', true)), 0, 10);
  $defaultProject = defined('KULADIG_PROJECT_ID') ? (int) KULADIG_PROJECT_ID : 0;

  $ajax_url = admin_url('admin-ajax.php');
  $nonce    = wp_create_nonce('kld_map_nonce');

  ob_start(); ?>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">

  <style>
    /* Full-bleed */
    #<?php echo esc_attr($uid); ?>_bleed{ width:100vw; margin-left:calc(50% - 50vw); }

    /* Vollhöhe */
    #<?php echo esc_attr($uid); ?>_wrap{
      position:relative; width:100%;
      height:100vh; min-height:100vh;
      overflow:hidden; background:#e2e8f0;
    }
    body.admin-bar #<?php echo esc_attr($uid); ?>_wrap{ height:calc(100vh - 32px); min-height:calc(100vh - 32px); }
    @media (max-width:782px){
      body.admin-bar #<?php echo esc_attr($uid); ?>_wrap{ height:calc(100vh - 46px); min-height:calc(100vh - 46px); }
    }

    #<?php echo esc_attr($uid); ?>_map{ width:100%; height:100%; }

    /* Search Overlay */
    #<?php echo esc_attr($uid); ?>_search{
      position:absolute; top:18px; left:18px; z-index:999;
      width:min(520px, calc(100vw - 36px));
    }
    #<?php echo esc_attr($uid); ?>_bar{
      display:flex; align-items:center; gap:10px;
      background:#fff; border:1px solid rgba(15,23,42,.08);
      box-shadow:0 18px 45px rgba(15,23,42,.14);
      border-radius:14px; padding:10px;
    }
    #<?php echo esc_attr($uid); ?>_ico{
      width:34px; height:34px; border-radius:10px;
      display:flex; align-items:center; justify-content:center;
      background:rgba(15,23,42,.04); color:#0f172a; flex:0 0 auto;
    }
    #<?php echo esc_attr($uid); ?>_q{
      border:0; outline:0; flex:1; font-size:14px; color:#0f172a; background:transparent;
    }
    #<?php echo esc_attr($uid); ?>_filter_btn,
    #<?php echo esc_attr($uid); ?>_search_btn{
      border:0; cursor:pointer; border-radius:12px; padding:10px 14px; font-weight:800;
    }
    #<?php echo esc_attr($uid); ?>_filter_btn{ background:rgba(15,23,42,.04); color:#0f172a; padding:10px 12px; }
    #<?php echo esc_attr($uid); ?>_search_btn{ background:#0f172a; color:#fff; padding:10px 16px; }

    /* Dropdown */
    #<?php echo esc_attr($uid); ?>_dropdown{
      margin-top:10px; background:#fff;
      border:1px solid rgba(15,23,42,.08);
      box-shadow:0 18px 45px rgba(15,23,42,.14);
      border-radius:14px; overflow:hidden; display:none;
    }
    #<?php echo esc_attr($uid); ?>_dropdown.is-open{ display:block; }
    #<?php echo esc_attr($uid); ?>_dropdown .row{
      padding:12px 14px; display:flex; align-items:center; justify-content:space-between;
      gap:10px; font-size:13px; color:#0f172a;
    }
    #<?php echo esc_attr($uid); ?>_dropdown .row + .row{ border-top:1px solid rgba(15,23,42,.06); }

    /* Suggestions */
    #<?php echo esc_attr($uid); ?>_suggest{
      margin-top:10px; background:#fff;
      border:1px solid rgba(15,23,42,.08);
      box-shadow:0 18px 45px rgba(15,23,42,.14);
      border-radius:14px; overflow:hidden; display:none;
    }
    #<?php echo esc_attr($uid); ?>_suggest.is-open{ display:block; }
    #<?php echo esc_attr($uid); ?>_suggest a{
      display:block; padding:10px 12px; text-decoration:none; color:#0f172a; font-size:13px;
    }
    #<?php echo esc_attr($uid); ?>_suggest a:hover{ background:rgba(15,23,42,.04); }

    /* Zoom unten rechts */
    .leaflet-control-zoom{
      border:0 !important;
      box-shadow:0 18px 45px rgba(15,23,42,.14) !important;
      border-radius:14px !important;
      overflow:hidden;
    }
    .leaflet-control-zoom a{
      border:0 !important; width:42px !important; height:42px !important; line-height:42px !important;
    }

    /* Loader */
    #<?php echo esc_attr($uid); ?>_loading{
      position:absolute; bottom:16px; left:16px; z-index:999;
      background:rgba(255,255,255,.92);
      border:1px solid rgba(15,23,42,.08);
      box-shadow:0 18px 45px rgba(15,23,42,.14);
      border-radius:12px; padding:8px 10px; font-size:12px; color:#0f172a;
      display:none;
    }
    #<?php echo esc_attr($uid); ?>_loading.is-on{ display:block; }

    /* ===== Route UI (hell) ===== */
    #<?php echo esc_attr($uid); ?>_route_tab{
      position:absolute; top:18px; right:18px; z-index:999;
      border:1px solid rgba(15,23,42,.08);
      background:#fff;
      box-shadow:0 18px 45px rgba(15,23,42,.14);
      border-radius:14px;
      padding:10px 12px;
      font-weight:900;
      color:#0f172a;
      cursor:pointer;
      display:flex;
      align-items:center;
      gap:10px;
    }
    #<?php echo esc_attr($uid); ?>_route_tab.is-hidden{ display:none; }

    #<?php echo esc_attr($uid); ?>_route_count{
      min-width:24px; height:24px; border-radius:999px;
      display:inline-flex; align-items:center; justify-content:center;
      background:#eef2ff; border:1px solid rgba(79,70,229,.18);
      color:#4f46e5; font-size:12px; font-weight:900;
    }

    #<?php echo esc_attr($uid); ?>_route_panel{
      position:absolute; top:18px; right:18px; z-index:1000;
      width:min(380px, calc(100vw - 36px));
      max-height:calc(100vh - 36px);
      background:#fff;
      border:1px solid rgba(15,23,42,.08);
      box-shadow:0 18px 45px rgba(15,23,42,.14);
      border-radius:18px;
      overflow:hidden;
      display:none;
    }
    #<?php echo esc_attr($uid); ?>_route_panel.is-open{ display:block; }

    #<?php echo esc_attr($uid); ?>_route_head{
      padding:14px 14px 10px;
      display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
      border-bottom:1px solid rgba(15,23,42,.06);
    }
    #<?php echo esc_attr($uid); ?>_route_kicker{ font-size:12px; font-weight:900; color:#64748b; }
    #<?php echo esc_attr($uid); ?>_route_title{ font-size:18px; font-weight:900; color:#0f172a; line-height:1.1; }
    #<?php echo esc_attr($uid); ?>_route_close{
      width:36px; height:36px;
      border-radius:12px;
      border:1px solid rgba(15,23,42,.08);
      background:rgba(15,23,42,.04);
      color:#0f172a;
      cursor:pointer;
      font-size:20px;
      font-weight:900;
      line-height:1;
    }

    #<?php echo esc_attr($uid); ?>_route_actions{
      padding:12px 14px;
      display:flex;
      gap:10px;
      flex-wrap:wrap;
    }
    #<?php echo esc_attr($uid); ?>_route_actions button{
      border:1px solid rgba(15,23,42,.10);
      background:rgba(15,23,42,.04);
      color:#0f172a;
      padding:10px 12px;
      border-radius:12px;
      font-weight:900;
      cursor:pointer;
      font-size:13px;
    }
    #<?php echo esc_attr($uid); ?>_route_open_gmaps{
      background:#0f172a !important;
      border-color:#0f172a !important;
      color:#fff !important;
    }

    #<?php echo esc_attr($uid); ?>_route_hint{
      padding:0 14px 10px;
      font-size:12px;
      color:#64748b;
    }

    #<?php echo esc_attr($uid); ?>_route_list{
      list-style:none;
      margin:0;
      padding:8px 10px 12px;
      overflow:auto;
      max-height:calc(100vh - 260px);
    }

    #<?php echo esc_attr($uid); ?>_route_list .item{
      border:1px solid rgba(15,23,42,.08);
      border-radius:14px;
      padding:10px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      background:#fff;
    }
    #<?php echo esc_attr($uid); ?>_route_list .item + .item{ margin-top:10px; }

    #<?php echo esc_attr($uid); ?>_route_list .left{
      display:flex; align-items:center; gap:10px; min-width:0;
    }
    #<?php echo esc_attr($uid); ?>_route_list .handle{
      width:32px; height:32px;
      border-radius:12px;
      border:1px solid rgba(15,23,42,.08);
      background:rgba(15,23,42,.04);
      display:flex;
      align-items:center;
      justify-content:center;
      cursor:grab;
      flex:0 0 auto;
      font-weight:900;
    }
    #<?php echo esc_attr($uid); ?>_route_list .num{
      width:28px; height:28px;
      border-radius:999px;
      background:#eef2ff;
      border:1px solid rgba(79,70,229,.20);
      color:#4f46e5;
      font-weight:900;
      font-size:12px;
      display:flex;
      align-items:center;
      justify-content:center;
      flex:0 0 auto;
    }
    #<?php echo esc_attr($uid); ?>_route_list .text{ min-width:0; }
    #<?php echo esc_attr($uid); ?>_route_list .name{
      font-size:13px; font-weight:900; color:#0f172a;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;
    }
    #<?php echo esc_attr($uid); ?>_route_list .sub{
      font-size:12px; color:#94a3b8;
      white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:220px;
    }

    #<?php echo esc_attr($uid); ?>_route_list .right{
      display:flex; align-items:center; gap:8px;
    }
    #<?php echo esc_attr($uid); ?>_route_list .mini{
      width:34px; height:34px;
      border-radius:12px;
      border:1px solid rgba(15,23,42,.08);
      background:rgba(15,23,42,.04);
      cursor:pointer;
      font-weight:900;
      color:#0f172a;
    }
    #<?php echo esc_attr($uid); ?>_route_list .mini.danger{
      background:rgba(220,38,38,.08);
      border-color:rgba(220,38,38,.18);
      color:#dc2626;
    }

    #<?php echo esc_attr($uid); ?>_route_empty{
      padding:16px 14px 18px;
      color:#64748b;
      font-size:13px;
      display:none;
    }
    #<?php echo esc_attr($uid); ?>_route_empty.is-on{ display:block; }
											  
	#<?php echo esc_attr($uid); ?>_wrap .leaflet-popup-content strong{
  display:block;
  font-size:16px;           /* <-- Titel größer */
  margin-bottom:6px;
	}										

    /* Popup Buttons */
    #<?php echo esc_attr($uid); ?>_wrap .kld-pop-actions{ margin-top:10px; display:flex; gap:8px; flex-wrap:wrap; }
    #<?php echo esc_attr($uid); ?>_wrap .kld-pop-add{
      border:0; cursor:pointer; border-radius:10px;
		font-size:13px;
      padding:8px 10px; font-weight:900;
      background:#0f172a; color:#fff;
    }
    #<?php echo esc_attr($uid); ?>_wrap .kld-pop-link{
      display:inline-block; border:1px solid rgba(15,23,42,.10);
      background:rgba(15,23,42,.04);
      color:#0f172a; border-radius:10px;
      padding:7px 10px; text-decoration:none; font-weight:900;
    }
	#<?php echo esc_attr($uid); ?>_wrap .kld-pop-link{
  padding:10px 12px;
  font-size:13px;
  border-radius:12px;
}
  </style>

  <div id="<?php echo esc_attr($uid); ?>_bleed">
    <div id="<?php echo esc_attr($uid); ?>_wrap">
      <div id="<?php echo esc_attr($uid); ?>_map"></div>

      <div id="<?php echo esc_attr($uid); ?>_search">
        <div id="<?php echo esc_attr($uid); ?>_bar">
          <div id="<?php echo esc_attr($uid); ?>_ico" aria-hidden="true">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" stroke="currentColor" stroke-width="2"/>
              <path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>

          <input id="<?php echo esc_attr($uid); ?>_q" type="text" placeholder="Ort oder Stichwort eingeben (auch Beschreibung)" autocomplete="off">

          <button id="<?php echo esc_attr($uid); ?>_filter_btn" type="button" title="Filter">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
              <path d="M4 7h10M18 7h2M10 7a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              <path d="M4 17h2M10 17h10M18 17a2 2 0 1 0-4 0 2 2 0 0 0 4 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </button>

          <button id="<?php echo esc_attr($uid); ?>_search_btn" type="button">Suchen</button>
        </div>

        <div id="<?php echo esc_attr($uid); ?>_dropdown">
          <div class="row">
            <div>Nur Orte mit Bild</div>
            <input id="<?php echo esc_attr($uid); ?>_only_img" type="checkbox" checked>
          </div>
          <div class="row">
            <div>Projekt-ID (optional)</div>
            <input id="<?php echo esc_attr($uid); ?>_project" type="number" placeholder="z.B. 2085" style="width:120px"
                   value="<?php echo esc_attr($defaultProject ?: ''); ?>">
          </div>
        </div>

        <div id="<?php echo esc_attr($uid); ?>_suggest"></div>
      </div>

      <div id="<?php echo esc_attr($uid); ?>_loading">Lade Orte…</div>

      <!-- Route Tab + Panel -->
      <button id="<?php echo esc_attr($uid); ?>_route_tab" type="button" aria-controls="<?php echo esc_attr($uid); ?>_route_panel" aria-expanded="false">
        Route <span id="<?php echo esc_attr($uid); ?>_route_count">0</span>
      </button>

      <aside id="<?php echo esc_attr($uid); ?>_route_panel" aria-hidden="true">
        <div id="<?php echo esc_attr($uid); ?>_route_head">
          <div>
            <div id="<?php echo esc_attr($uid); ?>_route_kicker">Route</div>
            <div id="<?php echo esc_attr($uid); ?>_route_title">Ausgewählte Orte</div>
          </div>
          <button id="<?php echo esc_attr($uid); ?>_route_close" type="button" title="Schließen">×</button>
        </div>

        <div id="<?php echo esc_attr($uid); ?>_route_actions">
          <button id="<?php echo esc_attr($uid); ?>_route_open_gmaps" type="button">In Google Maps öffnen</button>
          <button id="<?php echo esc_attr($uid); ?>_route_clear" type="button">Leeren</button>
        </div>

        <div id="<?php echo esc_attr($uid); ?>_route_hint">Zieh die Orte per Drag & Drop in die richtige Reihenfolge.</div>

        <ul id="<?php echo esc_attr($uid); ?>_route_list"></ul>

        <div id="<?php echo esc_attr($uid); ?>_route_empty">
          Noch keine Orte ausgewählt. Öffne einen Marker und klicke „Zur Route“.
        </div>
      </aside>
    </div>
  </div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

  <script>
  (function(){
    const uid = <?php echo wp_json_encode($uid); ?>;

    const AJAX_URL = <?php echo wp_json_encode($ajax_url); ?>;
    const NONCE    = <?php echo wp_json_encode($nonce); ?>;

    const elMap     = document.getElementById(uid + "_map");
    const elQ       = document.getElementById(uid + "_q");
    const elBtn     = document.getElementById(uid + "_search_btn");
    const elFBtn    = document.getElementById(uid + "_filter_btn");
    const elDrop    = document.getElementById(uid + "_dropdown");
    const elSuggest = document.getElementById(uid + "_suggest");
    const elOnlyImg = document.getElementById(uid + "_only_img");
    const elProject = document.getElementById(uid + "_project");
    const elLoading = document.getElementById(uid + "_loading");

    // Route UI
    const elRouteTab   = document.getElementById(uid + "_route_tab");
    const elRoutePanel = document.getElementById(uid + "_route_panel");
    const elRouteClose = document.getElementById(uid + "_route_close");
    const elRouteList  = document.getElementById(uid + "_route_list");
    const elRouteEmpty = document.getElementById(uid + "_route_empty");
    const elRouteCount = document.getElementById(uid + "_route_count");
    const elRouteClear = document.getElementById(uid + "_route_clear");
    const elRouteGmaps = document.getElementById(uid + "_route_open_gmaps");

    if (!elMap || typeof L === "undefined") return;

    const map = L.map(elMap, { zoomControl:false }).setView([51.2, 10.5], 6);
    L.control.zoom({ position:"bottomright" }).addTo(map);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: "&copy; OpenStreetMap-Mitwirkende"
    }).addTo(map);

    const cluster = L.markerClusterGroup({
      chunkedLoading: true,
      chunkInterval: 200,
      chunkDelay: 30
    });
    map.addLayer(cluster);

    setTimeout(()=> map.invalidateSize(), 150);
    window.addEventListener("resize", ()=> map.invalidateSize());

    // Icons8 Marker
    const ICON_URL_DARK  = "https://img.icons8.com/?size=100&id=zX7YSoxJxWv6&format=png&color=000529";
    const ICON_URL_GREEN = "https://img.icons8.com/?size=100&id=zX7YSoxJxWv6&format=png&color=16a34a";
    function makeIcon(url){
      return L.icon({
        iconUrl: url,
        iconRetinaUrl: url,
        iconSize: [34,34],
        iconAnchor: [17,34],
        popupAnchor: [0,-30]
      });
    }
    const ICON_DARK  = makeIcon(ICON_URL_DARK);
    const ICON_GREEN = makeIcon(ICON_URL_GREEN);

    let markerById = new Map();
    let objById    = new Map(); // <-- wichtig für Route Add
    let activeId = "";

    let abortMain = null;
    let abortSuggest = null;

    // Cache (Browser)
    const TTL = 6 * 60 * 60 * 1000; // 6h
    function cacheKey(mode, projectId, onlyImg, extra){
      return `kld_map_v5:${mode}:p${projectId||0}:img${onlyImg?1:0}:${extra}`;
    }
    function cacheGet(key){
      try{
        const raw = localStorage.getItem(key);
        if(!raw) return null;
        const o = JSON.parse(raw);
        if(!o || !o.ts || !Array.isArray(o.items)) return null;
        if(Date.now() - o.ts > TTL) return null;
        return o.items;
      }catch(e){ return null; }
    }
    function cacheSet(key, items){
      try{ localStorage.setItem(key, JSON.stringify({ ts: Date.now(), items })); }catch(e){}
    }

    function showLoading(on){ elLoading.classList.toggle("is-on", !!on); }

    function clearMarkers(){
      cluster.clearLayers();
      markerById.clear();
      objById.clear();
      activeId = "";
    }

    function addMarkers(items){
      const ms = [];
      for(const o of items){
        objById.set(o.id, o);

        const m = L.marker([o.lat, o.lon], { icon: ICON_DARK });

        const html =
          "<strong>" + esc(o.name) + "</strong><br>" +
          (o.desc ? "<div style='margin-top:6px;color:#64748b;font-size:12px;line-height:1.35'>" + esc(o.desc) + "</div>" : "") +
          "<div class='kld-pop-actions'>" +
            "<a class='kld-pop-link' href=\"/ort/?id=" + encodeURIComponent(o.id) + "\">Mehr anzeigen</a>" +
            "<button class='kld-pop-add' type='button' data-add-route=\"" + escAttr(o.id) + "\">Zur Route</button>" +
          "</div>";

        m.bindPopup(html);
        ms.push(m);
        markerById.set(o.id, m);
      }
      cluster.addLayers(ms);
    }

    function setActive(id){
      if(activeId && markerById.has(activeId)) markerById.get(activeId).setIcon(ICON_DARK);
      activeId = id || "";
      if(activeId && markerById.has(activeId)){
        const m = markerById.get(activeId);
        m.setIcon(ICON_GREEN);
        m.openPopup();
        map.panTo(m.getLatLng());
      }
    }

    // ===== ROUTE BUILDER (aus Code 1) =====
    const ROUTE_KEY = "kld_route_v1"; // stabil (nicht uid-abhängig)
    let route = []; // [{id,name,lat,lon}]

    function numIcon(n){
      return L.divIcon({
        className: '',
        html: "<div style='width:28px;height:28px;border-radius:999px;background:#eef2ff;border:1px solid rgba(79,70,229,.20);color:#4f46e5;font-weight:900;font-size:12px;display:flex;align-items:center;justify-content:center'>" + n + "</div>",
        iconSize:[28,28],
        iconAnchor:[14,14]
      });
    }

    function loadRoute(){
      try{
        const raw = localStorage.getItem(ROUTE_KEY);
        if(!raw) return;
        const arr = JSON.parse(raw);
        if(Array.isArray(arr)) route = arr.filter(x => x && x.id && typeof x.lat === "number" && typeof x.lon === "number");
      }catch(e){}
    }
    function saveRoute(){
      try{ localStorage.setItem(ROUTE_KEY, JSON.stringify(route)); }catch(e){}
    }

    function openRoutePanel(){
      elRoutePanel.classList.add("is-open");
      elRoutePanel.setAttribute("aria-hidden","false");
      elRouteTab.classList.add("is-hidden");
      elRouteTab.setAttribute("aria-expanded","true");
    }
    function closeRoutePanel(){
      elRoutePanel.classList.remove("is-open");
      elRoutePanel.setAttribute("aria-hidden","true");
      elRouteTab.classList.remove("is-hidden");
      elRouteTab.setAttribute("aria-expanded","false");
    }

    elRouteTab.addEventListener("click", openRoutePanel);
    elRouteClose.addEventListener("click", closeRoutePanel);

    const routeLine = L.polyline([], { color:"#16a34a", weight:5, opacity:0.9 }).addTo(map);
    const routeNums = L.layerGroup().addTo(map);

    const OSRM_PROFILE  = "foot"; // "driving" | "cycling" | "foot"
    const OSRM_ENDPOINT = "https://router.project-osrm.org/route/v1/";

    let routeAbort = null;
    let routeDebounce = null;
    const routeCache = new Map();

    function routeCacheKey(){
      return route.map(r => `${r.lat.toFixed(6)},${r.lon.toFixed(6)}`).join(">");
    }

    async function fetchRoadRouteLatLngs(){
      if(route.length < 2) return [];

      const key = routeCacheKey();
      if(routeCache.has(key)) return routeCache.get(key);

      const coordStr = route.map(r => `${r.lon},${r.lat}`).join(";");
      const url =
        OSRM_ENDPOINT + OSRM_PROFILE + "/" + coordStr +
        "?overview=full&geometries=geojson&steps=false";

      if(routeAbort) routeAbort.abort();
      routeAbort = new AbortController();

      const res = await fetch(url, { signal: routeAbort.signal });
      if(!res.ok) throw new Error("OSRM HTTP " + res.status);

      const json = await res.json();
      const geom = json && json.routes && json.routes[0] && json.routes[0].geometry;
      if(!geom || !Array.isArray(geom.coordinates)) throw new Error("OSRM no geometry");

      const latlngs = geom.coordinates.map(c => [c[1], c[0]]);
      routeCache.set(key, latlngs);
      return latlngs;
    }

    function updateRouteMap(){
      routeNums.clearLayers();
      route.forEach((r, idx) => {
        routeNums.addLayer(L.marker([r.lat, r.lon], { icon: numIcon(idx+1), interactive:false }));
      });

      if(route.length < 2){
        routeLine.setLatLngs([]);
        return;
      }

      clearTimeout(routeDebounce);
      routeDebounce = setTimeout(async () => {
        try{
          const latlngs = await fetchRoadRouteLatLngs();
          routeLine.setLatLngs(latlngs);
        }catch(e){
          if(String(e && e.name) !== "AbortError"){
            console.warn("Routing fallback (straight line):", e);
          }
          routeLine.setLatLngs(route.map(r => [r.lat, r.lon]));
        }
      }, 280);
    }

    function renderRouteList(){
      elRouteCount.textContent = String(route.length);
      elRouteEmpty.classList.toggle("is-on", route.length === 0);

      elRouteList.innerHTML = route.map((r, idx) => (
        "<li class='item' draggable='true' data-idx='" + idx + "'>" +
          "<div class='left'>" +
            "<div class='handle' title='Ziehen'>≡</div>" +
            "<div class='num'>" + (idx+1) + "</div>" +
            "<div class='text'>" +
              "<div class='name'>" + esc(r.name) + "</div>" +
              "<div class='sub'>" + esc(r.id) + "</div>" +
            "</div>" +
          "</div>" +
          "<div class='right'>" +
            "<button class='mini' type='button' data-focus='" + idx + "' title='Auf Karte zeigen'>⌖</button>" +
            "<button class='mini danger' type='button' data-remove='" + idx + "' title='Entfernen'>✕</button>" +
          "</div>" +
        "</li>"
      )).join("");

      updateRouteMap();
      saveRoute();
    }

    function addToRouteById(id){
      id = String(id || "");
      if(!id) return;

      if(route.some(x => x.id === id)){
        openRoutePanel();
        return;
      }

      const obj = objById.get(id);
      if(obj){
        route.push({ id: obj.id, name: obj.name, lat: obj.lat, lon: obj.lon });
      }else{
        return;
      }

      renderRouteList();
      openRoutePanel();
    }

    map.on("popupopen", (e) => {
      const root = e.popup && e.popup.getElement ? e.popup.getElement() : null;
      if(!root) return;
      const btn = root.querySelector("[data-add-route]");
      if(!btn) return;

      btn.addEventListener("click", () => {
        const id = btn.getAttribute("data-add-route");
        if(!id) return;
        addToRouteById(id);
      }, { once:true });
    });

    elRouteList.addEventListener("click", (e) => {
      const rm = e.target.closest("[data-remove]");
      if(rm){
        const idx = parseInt(rm.getAttribute("data-remove"), 10);
        if(!Number.isNaN(idx)){
          route.splice(idx,1);
          renderRouteList();
        }
        return;
      }

      const fc = e.target.closest("[data-focus]");
      if(fc){
        const idx = parseInt(fc.getAttribute("data-focus"), 10);
        const r = route[idx];
        if(r){
          map.setView([r.lat, r.lon], Math.max(map.getZoom(), 15));
          if(markerById.has(r.id)) setActive(r.id);
        }
        return;
      }
    });

    let dragFrom = null;

    elRouteList.addEventListener("dragstart", (e) => {
      const li = e.target.closest(".item");
      if(!li) return;
      dragFrom = parseInt(li.getAttribute("data-idx"), 10);
      li.style.opacity = "0.6";
      e.dataTransfer.effectAllowed = "move";
    });

    elRouteList.addEventListener("dragend", (e) => {
      const li = e.target.closest(".item");
      if(li) li.style.opacity = "";
      dragFrom = null;
    });

    elRouteList.addEventListener("dragover", (e) => {
      e.preventDefault();
      e.dataTransfer.dropEffect = "move";
    });

    elRouteList.addEventListener("drop", (e) => {
      e.preventDefault();
      const li = e.target.closest(".item");
      if(!li) return;

      const dragTo = parseInt(li.getAttribute("data-idx"), 10);
      if(dragFrom === null || Number.isNaN(dragTo) || dragTo === dragFrom) return;

      const moved = route.splice(dragFrom, 1)[0];
      route.splice(dragTo, 0, moved);
      renderRouteList();
    });

    elRouteClear.addEventListener("click", () => {
      route = [];
      renderRouteList();
    });

    elRouteGmaps.addEventListener("click", () => {
      if(route.length < 2) return;

      const maxStops = 25;
      const trimmed = route.slice(0, maxStops);

      const origin = trimmed[0].lat + "," + trimmed[0].lon;
      const dest   = trimmed[trimmed.length-1].lat + "," + trimmed[trimmed.length-1].lon;

      const wpsArr = trimmed.slice(1, -1).map(r => r.lat + "," + r.lon);
      const waypoints = wpsArr.length ? "&waypoints=" + encodeURIComponent(wpsArr.join("|")) : "";

      const url =
        "https://www.google.com/maps/dir/?api=1" +
        "&origin=" + encodeURIComponent(origin) +
        "&destination=" + encodeURIComponent(dest) +
        waypoints +
        "&travelmode=walking";

      window.open(url, "_blank", "noopener");
    });

    // ===== AJAX Proxy =====
    function normalize(s){ return (s||"").toString().toLowerCase().trim().replace(/\s+/g,' '); }
    function projectId(){ return parseInt(elProject.value || "0", 10) || 0; }

    async function ajaxFetch(mode, payload, abortSlot){
      if(abortSlot && abortSlot.current) abortSlot.current.abort();
      const ctrl = new AbortController();
      if(abortSlot) abortSlot.current = ctrl;

      const fd = new FormData();
      fd.append("action", "kld_map_proxy");
      fd.append("nonce", NONCE);
      fd.append("mode", mode);
      Object.keys(payload || {}).forEach(k => fd.append(k, payload[k]));

      const res = await fetch(AJAX_URL, {
        method: "POST",
        body: fd,
        signal: ctrl.signal,
        credentials: "same-origin"
      });

      const json = await res.json().catch(()=>null);
      if(!json || !json.success) throw new Error((json && json.data && json.data.message) ? json.data.message : ("AJAX failed ("+res.status+")"));
      return json.data.items || [];
    }

    function viewportGeom(){
      const b = map.getBounds();
      const sw = b.getSouthWest();
      const ne = b.getNorthEast();
      const geom = {
        type: "Polygon",
        coordinates: [[
          [sw.lng, sw.lat],
          [ne.lng, sw.lat],
          [ne.lng, ne.lat],
          [sw.lng, ne.lat],
          [sw.lng, sw.lat]
        ]]
      };
      return JSON.stringify(geom);
    }

    async function loadViewport(){
      const q = normalize(elQ.value);
      if(map.getZoom() < 7 && !q){
        clearMarkers();
        return;
      }
      if(q.length >= 2) return;

      const p = projectId();
      const onlyImg = !!elOnlyImg.checked;
      const geom = viewportGeom();

      const key = cacheKey("viewport", p, onlyImg, "z"+map.getZoom()+":g"+geom.length);
      const cached = cacheGet(key);
      if(cached){
        clearMarkers();
        addMarkers(cached);
        return;
      }

      showLoading(true);
      try{
        const items = await ajaxFetch("viewport", {
          project: String(p),
          only_img: onlyImg ? "1" : "0",
          geom: geom,
          page: "0"
        }, { current: abortMain });

        clearMarkers();
        addMarkers(items);
        cacheSet(key, items);
      }catch(e){
        if(String(e && e.name) !== "AbortError") console.error("Viewport:", e);
      }finally{
        showLoading(false);
      }
    }

    async function loadSearch(q){
      q = normalize(q);
      if(q.length < 2) return loadViewport();

      const p = projectId();
      const onlyImg = !!elOnlyImg.checked;

      const key = cacheKey("search", p, onlyImg, q);
      const cached = cacheGet(key);
      if(cached){
        clearMarkers();
        addMarkers(cached);
        if(cached.length){
          const b = L.latLngBounds(cached.map(o => [o.lat, o.lon]));
          map.fitBounds(b, { padding:[30,30] });
        }
        return;
      }

      showLoading(true);
      try{
        const items = await ajaxFetch("search", {
          q: q,
          project: String(p),
          only_img: onlyImg ? "1" : "0",
          page: "0"
        }, { current: abortMain });

        clearMarkers();
        addMarkers(items);
        cacheSet(key, items);

        if(items.length){
          const b = L.latLngBounds(items.map(o => [o.lat, o.lon]));
          map.fitBounds(b, { padding:[30,30] });
        }
      }catch(e){
        if(String(e && e.name) !== "AbortError") console.error("Search:", e);
      }finally{
        showLoading(false);
      }
    }

    async function loadSuggest(q){
      q = normalize(q);
      if(q.length < 2){
        elSuggest.classList.remove("is-open");
        elSuggest.innerHTML = "";
        return;
      }

      try{
        const p = projectId();
        const onlyImg = !!elOnlyImg.checked;

        const items = await ajaxFetch("suggest", {
          q: q,
          project: String(p),
          only_img: onlyImg ? "1" : "0",
          page: "0"
        }, { current: abortSuggest });

        const top = items.slice(0, 8);
        if(!top.length){
          elSuggest.classList.remove("is-open");
          elSuggest.innerHTML = "";
          return;
        }

        elSuggest.innerHTML = top.map(o =>
          "<a href=\"#\" data-id=\"" + escAttr(o.id) + "\" data-name=\"" + escAttr(o.name) + "\">" + esc(o.name) + "</a>"
        ).join("");
        elSuggest.classList.add("is-open");
      }catch(e){
        if(String(e && e.name) !== "AbortError") console.error("Suggest:", e);
      }
    }

    // Events
    elFBtn.addEventListener("click", ()=> elDrop.classList.toggle("is-open"));

    elBtn.addEventListener("click", ()=>{
      const q = normalize(elQ.value);
      elSuggest.classList.remove("is-open");
      if(q.length >= 2) loadSearch(q);
      else loadViewport();
    });

    elQ.addEventListener("keydown", (e)=>{
      if(e.key === "Enter"){
        e.preventDefault();
        const q = normalize(elQ.value);
        elSuggest.classList.remove("is-open");
        if(q.length >= 2) loadSearch(q);
        else loadViewport();
      }
    });

    let tt = null;
    elQ.addEventListener("input", ()=>{
      clearTimeout(tt);
      tt = setTimeout(()=> loadSuggest(elQ.value), 220);
    });

    elSuggest.addEventListener("click", (e)=>{
      const a = e.target.closest("a[data-name]");
      if(!a) return;
      e.preventDefault();
      const name = a.getAttribute("data-name") || "";
      elQ.value = name;
      elSuggest.classList.remove("is-open");
      loadSearch(name);
    });

    map.on("click", ()=> elSuggest.classList.remove("is-open"));

    elOnlyImg.addEventListener("change", ()=>{
      elSuggest.classList.remove("is-open");
      const q = normalize(elQ.value);
      if(q.length >= 2) loadSearch(q);
      else loadViewport();
    });

    elProject.addEventListener("change", ()=>{
      elSuggest.classList.remove("is-open");
      const q = normalize(elQ.value);
      if(q.length >= 2) loadSearch(q);
      else loadViewport();
    });

    let mv = null;
    map.on("moveend", ()=>{
      const q = normalize(elQ.value);
      if(q.length >= 2) return;
      clearTimeout(mv);
      mv = setTimeout(loadViewport, 260);
    });

    // Init Route, dann Daten laden
    loadRoute();
    renderRouteList();

    // init
    loadViewport();

    function esc(s){
      return (s||"").toString()
        .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
    }
    function escAttr(s){ return esc(s).replace(/"/g,"&quot;"); }
  })();
  </script>
  <?php
  return ob_get_clean();
});

/**
 * 3D Artefacts Cards
 */
/**
 * KuLaDig 3D Artefacts – GRID (5 Cards) mit Sketchfab Hover Preview
 * Shortcode: [kld_artefact_grid]
 * Linkziel: /3d-model/?artefact=slug
 */

define('KLD_GRID_DETAIL_PATH', '/3d-model/');

/** Holt Artefakt-Daten fuer das Grid (API + Cache). */
function kld_grid_artefacts_data(): array {
  return [
    'ladislaus' => [
      'title' => 'König Ladislaus I.',
      'sketchfab_uid' => '7b0e6c0d6d07454aa1645148e03f4b70',
      'poster'=> '',
    ],
    'maria' => [
      'title' => 'Hl. Maria',
      'sketchfab_uid' => '7b0175a4da514e028285dbe01e5f363d',
      'poster'=> '',
    ],
    'monk-fryston' => [
      'title' => 'Monk Fryston Hall Hotel, North Yorkshire',
      'sketchfab_uid' => 'ba7c0e7ec5c54e30aa53b62821c7cc9d',
      'poster'=> '',
    ],
    'venus' => [
      'title' => 'Venus (Tomis, Romania)',
      'sketchfab_uid' => '1b1f1705d7c74ed8ac9aa4b653c6672e',
      'poster'=> '',
    ],
    'kriegerdenkmal' => [
      'title' => 'Kriegerdenkmal',
      'sketchfab_uid' => 'fc782802decf45a6b34470e796cd66b2',
      'poster'=> '',
    ],
  ];
}


/** Normalisiert URL und gibt leere Strings als '' zurueck. */
function kld_grid_safe_url(string $u): string {
  $u = trim((string)$u);
  return $u ? esc_url_raw($u) : '';
}

/** SVG-Placeholder fuer Karten ohne Bild. */
function kld_grid_svg_thumb_data_uri(string $title): string {
  $t = wp_strip_all_tags($title);
  $t = mb_substr($t, 0, 38);

  $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="700" viewBox="0 0 1200 700">
    <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#eef2ff"/><stop offset="1" stop-color="#e2e8f0"/>
    </linearGradient></defs>
    <rect width="1200" height="700" fill="url(#g)"/>
    <rect x="80" y="90" width="1040" height="520" rx="28" fill="#ffffff"/>
    <text x="140" y="250" font-family="system-ui,-apple-system,Segoe UI,Roboto,Arial" font-size="40" font-weight="900" fill="#0f172a">'.$t.'</text>
    <text x="140" y="310" font-family="system-ui,-apple-system,Segoe UI,Roboto,Arial" font-size="18" font-weight="700" fill="#64748b">3D Model Preview</text>
  </svg>';

  return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/** Ermittelt das beste Vorschaubild fuer eine Karte. */
function kld_grid_get_card_thumb(array $a): string {
  $poster = kld_grid_safe_url((string)($a['poster'] ?? ''));
  if ($poster) return $poster;
  return kld_grid_svg_thumb_data_uri((string)($a['title'] ?? '3D Objekt'));
}

/** Laedt Sketchfab-Preview nur, wenn das Grid gerendert wird. */
function kld_grid_enqueue_sketchfab_preview(): void {
  static $done = false;
  if ($done) return;
  $done = true;

  add_action('wp_footer', function () {
  ?>
  <style>
    .kld-card-media{position:relative;overflow:hidden}
    .kld-card-media iframe{position:absolute;inset:0;width:100%;height:100%;border:0;display:block;pointer-events:none}
    .kld-card-media img{width:100%;height:100%;object-fit:cover;display:block}
    /* Poster erst ausblenden wenn Viewer wirklich ready */
    .kld-card-media.kld-sf-ready img{opacity:0;pointer-events:none}
  </style>

  <script src="https://static.sketchfab.com/api/sketchfab-viewer-1.12.1.js"></script>
  <script>
    (function(){
      function mountSketchfab(host){
        if (!host) return;
        if (host.dataset.sfMounted === "1") return;

        var uid = host.getAttribute("data-sf-uid");
        if (!uid) return;

        host.dataset.sfMounted = "1";

        var iframe = document.createElement("iframe");
        iframe.setAttribute("title", "Sketchfab Preview");
        iframe.setAttribute("allow", "autoplay; fullscreen; xr-spatial-tracking");
        iframe.setAttribute("allowfullscreen", "");
        iframe.loading = "lazy";
        host.appendChild(iframe);

        if (typeof Sketchfab === "undefined") {
          host.dataset.sfMounted = "0";
          iframe.remove();
          return;
        }

        var client = new Sketchfab(iframe);
        client.init(uid, {
          autostart: 1,
          autospin: 0.6,
          transparent: 1,

          ui_controls: 0,
          ui_infos: 0,
          ui_hint: 0,
          ui_watermark: 0,
          ui_fullscreen: 0,
          ui_help: 0,
          ui_settings: 0,
          ui_vr: 0,
          annotations_visible: 0,

          success: function(api){
            host._sfApi = api;
            host.classList.add("kld-sf-ready"); // JETZT Poster ausblenden
          },
          error: function(){
            host.dataset.sfMounted = "0";
            try { iframe.remove(); } catch(e){}
            // Poster bleibt sichtbar
          }
        });
      }

      // Hover load (Desktop)
      document.addEventListener("pointerenter", function(e){
        var host = e.target.closest(".kld-card-media[data-sf-uid]");
        if (!host) return;
        mountSketchfab(host);
      }, true);

      // Optional: Prewarm erste Reihe
      var io = new IntersectionObserver(function(entries){
        entries.forEach(function(en){
          if (!en.isIntersecting) return;
          var host = en.target;
          if (host.dataset.prewarmDone === "1") return;
          host.dataset.prewarmDone = "1";
          if (host.getBoundingClientRect().top < window.innerHeight * 0.9) {
            mountSketchfab(host);
          }
        });
      }, { rootMargin: "150px 0px", threshold: 0.15 });

      document.querySelectorAll(".kld-card-media[data-sf-uid]").forEach(function(el){
        io.observe(el);
      });
    })();
  </script>
  <?php
}, 99);
}

add_shortcode('kld_artefact_grid', function () {
  kld_grid_enqueue_sketchfab_preview();

  $data = kld_grid_artefacts_data();
  $detail_page = home_url(trailingslashit(ltrim(KLD_GRID_DETAIL_PATH, '/')));

  $out = '<div class="kld-a-wrap"><div class="kld-grid">';

  foreach ($data as $slug => $a) {
  $slug  = sanitize_key($slug);
  $url   = esc_url(add_query_arg('artefact', $slug, $detail_page));

  $uid   = preg_replace('/[^a-zA-Z0-9]/', '', (string)($a['sketchfab_uid'] ?? ''));
  $thumb = kld_grid_get_card_thumb($a);
  $title = (string)($a['title'] ?? 'Artefakt');

  // optional, falls du es eintragen willst
  $desc  = (string)($a['desc'] ?? '');
  $loc   = (string)($a['location'] ?? '');
  $views = (string)($a['views'] ?? '');

  $out .= '<a class="kld-cardX" href="'.$url.'">';

    $out .= '<div class="kld-cardX-media kld-card-media" data-sf-uid="'.esc_attr($uid).'">';
      $out .= '<span class="kld-badgeX">3D Model</span>';
      $out .= '<img class="kld-thumbX" loading="lazy" decoding="async" src="'.esc_url($thumb).'" alt="'.esc_attr($title).'">';
    $out .= '</div>';

    $out .= '<div class="kld-cardX-body">';
      $out .= '<div class="kld-catX">'.esc_html(mb_strtoupper($cat)).'</div>';
      $out .= '<div class="kld-titleX">'.esc_html($title).'</div>';

      if ($desc !== '') {
        $out .= '<div class="kld-descX">'.esc_html($desc).'</div>';
      }

      $out .= '<div class="kld-footX">';
        $out .= '<span class="kld-ctaX">View Details <span class="kld-arrowX">→</span></span>';

        $metaParts = [];
        if ($loc !== '')   $metaParts[] = '📍 '.esc_html($loc);
        if ($views !== '') $metaParts[] = '👁 '.esc_html($views);

        if (!empty($metaParts)) {
          $out .= '<span class="kld-metaX">'.implode(' &nbsp;·&nbsp; ', $metaParts).'</span>';
        } else {
          $out .= '<span class="kld-metaX"></span>';
        }
      $out .= '</div>';

    $out .= '</div>';

  $out .= '</a>';
}


  $out .= '</div></div>';
  return $out;
});

/**
 * 3D Single Artefact
 */
/**
 * KuLaDig 3D Artefacts – NUR DETAIL (Sketchfab Viewer)
 * Shortcode: [kld_artefact]
 * Nutzung: Detail-Seite enthält nur [kld_artefact]
 * Aufruf: /3d-model/?artefact=ladislaus
 */

/** (Optional) Upload: GLB + USDZ erlauben */
add_filter('upload_mimes', function ($mimes) {
  $mimes['glb']  = 'model/gltf-binary';
  $mimes['gltf'] = 'model/gltf+json';
  $mimes['usdz'] = 'model/vnd.usdz+zip';
  return $mimes;
});

/** Sketchfab Script nur laden wenn Detail gerendert wird */
function kld_detail_enqueue_sketchfab(): void {
  static $done = false;
  if ($done) return;
  $done = true;

  add_action('wp_footer', function () { ?>
    <style>
      .kld-a-viewer{position:relative; overflow:hidden}
      .kld-a-viewer iframe{position:absolute; inset:0; width:100%; height:100%; border:0; display:block}
      .kld-a-viewerPoster{width:100%; height:100%; object-fit:cover; display:block}
      .kld-a-viewer[data-sf-mounted="1"] .kld-a-viewerPoster{opacity:0; pointer-events:none}
    </style>

    <script src="https://static.sketchfab.com/api/sketchfab-viewer-1.12.1.js"></script>
    <script>
      (function(){
        function initOne(){
          var host = document.querySelector(".kld-a-viewer[data-sf-uid]");
          if (!host) return;
          if (host.dataset.sfMounted === "1") return;

          var uid = host.getAttribute("data-sf-uid");
          if (!uid) return;

          host.dataset.sfMounted = "1";
          host.setAttribute("data-sf-mounted","1");

          var iframe = document.createElement("iframe");
          iframe.setAttribute("title", "Sketchfab Viewer");
          iframe.setAttribute("allow", "autoplay; fullscreen; xr-spatial-tracking");
          iframe.setAttribute("allowfullscreen", "");
          iframe.setAttribute("webkitallowfullscreen", "");
          iframe.setAttribute("mozallowfullscreen", "");
          iframe.loading = "lazy";
          host.appendChild(iframe);

          if (typeof Sketchfab === "undefined") {
            host.dataset.sfMounted = "0";
            host.setAttribute("data-sf-mounted","0");
            iframe.remove();
            return;
          }

          var client = new Sketchfab(iframe);
          client.init(uid, {
            autostart: 1,
            autospin: 0.25,
            transparent: 1,

            // UI auf Detail-Seite
            ui_controls: 1,
            ui_infos: 0,
            ui_hint: 1,
            ui_watermark: 0,
            ui_fullscreen: 1,
            ui_help: 0,
            ui_settings: 1,
            ui_vr: 1,
            annotations_visible: 0,

            success: function(api){ host._sfApi = api; },
            error: function(){
              host.dataset.sfMounted = "0";
              host.setAttribute("data-sf-mounted","0");
              iframe.remove();
            }
          });
        }

        if (document.readyState === "loading") {
          document.addEventListener("DOMContentLoaded", initOne);
        } else {
          initOne();
        }
      })();
    </script>
  <?php }, 99);
}

/** ===== Daten NUR für DETAIL ===== */
function kld_detail_artefacts_data(): array {
  return [
    'ladislaus' => [
      'title' => 'König Ladislaus I.',
      'sketchfab_uid' => '7b0e6c0d6d07454aa1645148e03f4b70',
      'chips' => ['Skulptur', 'König'],
      'about' => 'Bronzene Darstellung eines mittelalterlichen Herrschers. Das Modell zeigt typische Insignien und Gewanddetails, wie sie in Denkmal- und Museumsrekonstruktionen vorkommen.',
      'details' => [
        'Datum' => '11. Jh., Modellscan modern',
        'Material' => 'Bronze',
        'Ort' => 'Győr (HU)',
        'Maße' => 'ca. 2.2 m',
        'Beitrag' => 'Lokale Sammlung',
      ],
      'glb'    => 'http://10.0.107.116/wp-content/uploads/2025/12/konig_ladislaus_i.glb',
      'usdz'   => '',
      'poster' => '',
    ],

    'maria' => [
      'title' => 'Hl. Maria',
      'sketchfab_uid' => '7b0175a4da514e028285dbe01e5f363d',
      'chips' => ['Skulptur', 'Religiös'],
      'about' => 'Religiöse Figurendarstellung, typisch für sakrale Kunst im mitteleuropäischen Raum. Fokus liegt auf Gewandstruktur und Ornamentik.',
      'details' => [
        'Datum' => '19. Jh. ',
        'Material' => 'Stein ',
        'Ort' => 'Mailberg (AT)',
        'Maße' => 'ca. 1.6 m',
        'Beitrag' => 'Kirchliche Umgebung',
      ],
      'glb'    => 'http://10.0.107.116/wp-content/uploads/2025/12/hl._maria.glb',
      'usdz'   => '',
      'poster' => '',
    ],

    'monk-fryston' => [
      'title' => 'Monk Fryston Hall Hotel, North Yorkshire',
      'sketchfab_uid' => 'ba7c0e7ec5c54e30aa53b62821c7cc9d',
      'chips' => ['Gebäude', 'Scan', 'UK'],
      'about' => '3D-Scan eines Gebäudekomplexes. Gut geeignet, um Architekturdetails, Dachflächen und Geländeübergänge zu zeigen.',
      'details' => [
        'Datum' => '20. Jh. Nutzung, Bau älter',
        'Material' => 'Stein / Ziegel ',
        'Ort' => 'North Yorkshire (UK)',
        'Maße' => 'Gebäudekomplex',
        'Beitrag' => 'Photogrammetrie-Scan',
      ],
      'glb'    => 'http://10.0.107.116/wp-content/uploads/2025/12/monk_fryston_hall_hotel_north_yorkshire.glb',
      'usdz'   => '',
      'poster' => '',
    ],

    'venus' => [
      'title' => 'Venus (Tomis, Romania)',
      'sketchfab_uid' => '1b1f1705d7c74ed8ac9aa4b653c6672e',
      'chips' => ['Antike', 'Marmor'],
      'about' => 'Antike Büste im Stil römischer Skulptur. Das Modell eignet sich für Materialstudien und Oberflächendetails.',
      'details' => [
        'Datum' => '2. Jh. n. Chr.',
        'Material' => 'Marmor',
        'Ort' => 'Tomis (Constanța, RO)',
        'Maße' => 'Büste',
        'Beitrag' => 'Musealer Kontext',
      ],
      'glb'    => 'http://10.0.107.116/wp-content/uploads/2025/12/venus_from_site_of_tomis_romania.glb',
      'usdz'   => '',
      'poster' => '',
    ],

    'kriegerdenkmal' => [
      'title' => 'Kriegerdenkmal',
      'sketchfab_uid' => 'fc782802decf45a6b34470e796cd66b2',
      'chips' => ['Denkmal', 'Scan'],
      'about' => 'Gedenkanlage als 3D-Modell. Ideal, um räumliche Komposition, Inschriftenflächen und Gelände zu dokumentieren.',
      'details' => [
        'Datum' => '20. Jh.',
        'Material' => 'Stein / Beton ',
        'Ort' => '—',
        'Maße' => 'Anlage',
        'Beitrag' => 'Photogrammetrie-Scan ',
      ],
      'glb'    => 'http://10.0.107.116/wp-content/uploads/2025/12/kriegerdenkmal.glb',
      'usdz'   => '',
      'poster' => '',
    ],
  ];
}

/** Poster Fallback */
function kld_detail_svg_poster(string $title): string {
  $t = wp_strip_all_tags($title);
  $t = mb_substr($t, 0, 38);

  $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="700" viewBox="0 0 1200 700">
    <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#eef2ff"/><stop offset="1" stop-color="#e2e8f0"/>
    </linearGradient></defs>
    <rect width="1200" height="700" fill="url(#g)"/>
    <rect x="80" y="90" width="1040" height="520" rx="28" fill="#ffffff"/>
    <text x="140" y="250" font-family="system-ui,-apple-system,Segoe UI,Roboto,Arial" font-size="40" font-weight="900" fill="#0f172a">'.$t.'</text>
    <text x="140" y="310" font-family="system-ui,-apple-system,Segoe UI,Roboto,Arial" font-size="18" font-weight="700" fill="#64748b">Loading 3D Viewer…</text>
  </svg>';

  return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

/** ===== Shortcode: DETAIL ===== */
add_shortcode('kld_artefact', function ($atts) {
  kld_detail_enqueue_sketchfab();

  $atts = shortcode_atts(['slug' => ''], $atts);
  $slug = sanitize_key((string)$atts['slug']);
  if (!$slug && isset($_GET['artefact'])) $slug = sanitize_key((string)$_GET['artefact']);

  $data = kld_detail_artefacts_data();
  if (!$data) return '<div class="kld-a-wrap"><div class="kld-a-missing">Keine Artefakte definiert.</div></div>';
  if (!isset($data[$slug])) $slug = array_key_first($data);

  $a = $data[$slug];

  $title   = (string)($a['title'] ?? 'Artefakt');
  $chips   = (array)($a['chips'] ?? []);
  $about   = (string)($a['about'] ?? '');
  $details = (array)($a['details'] ?? []);

  $uidRaw = (string)($a['sketchfab_uid'] ?? '');
  $uid    = preg_replace('/[^a-zA-Z0-9]/', '', $uidRaw);

  $poster = (string)($a['poster'] ?? '');
  if (!$poster) $poster = kld_detail_svg_poster($title);

  $out  = '<div class="kld-a-wrap">';
  $out .= '<p class="kld-a-breadcrumb"><a href="'.esc_url(home_url('/')).'">Home</a> &nbsp;›&nbsp; 3D-Objekte &nbsp;›&nbsp; '.esc_html($title).'</p>';
  $out .= '<div class="kld-a-layout">';

  $out .= '<div class="kld-a-viewerCard">';
  $out .= '<div class="kld-a-viewerTop"><span class="kld-a-badge">3D Model</span></div>';

  if ($uid) {
    $out .= '<div class="kld-a-viewer" data-sf-uid="'.esc_attr($uid).'" data-sf-mounted="0">';
    $out .= '<img class="kld-a-viewerPoster" loading="lazy" decoding="async" src="'.esc_url($poster).'" alt="'.esc_attr($title).'">';
    $out .= '</div>';
  } else {
    $out .= '<div class="kld-a-missing">Sketchfab UID fehlt.</div>';
  }

  $out .= '</div>'; // viewerCard

  $out .= '<aside class="kld-a-info">';
  $out .= '<h1 class="kld-a-title">'.esc_html($title).'</h1>';

  if (!empty($chips)) {
    $out .= '<div class="kld-a-chips">';
    foreach ($chips as $c) {
      $c = trim((string)$c);
      if ($c !== '') $out .= '<span class="kld-a-chip">'.esc_html($c).'</span>';
    }
    $out .= '</div>';
  }

  $out .= '<div class="kld-a-h2">Über das Objekt</div>';
  $out .= '<p class="kld-a-p">'.esc_html($about ?: '—').'</p>';

  if (!empty($details)) {
    $out .= '<div class="kld-a-h2">Details</div><div class="kld-a-grid">';
    foreach ($details as $k => $v) {
      $out .= '<div class="kld-a-kv"><p class="kld-a-k">'.esc_html($k).'</p><p class="kld-a-v">'.esc_html((string)$v ?: '—').'</p></div>';
    }
    $out .= '</div>';
  }

  $out .= '</aside>';
  $out .= '</div></div>';

  return $out;
});

/**
 * Results Page
 */
/**
 * 2) Results Page (Layout wie Screenshot)
 *    Shortcode: [kuladig_search_results limit="20"]
 * URL: /suche/?q=Bonn&p=1
 */

/** Holt Suchergebnisse seitenweise und cached pro Query. */
function kuladig_sr2_fetch_page_cached(string $q, int $page0, int $limit): array {
  $q = trim($q);
  if (mb_strlen($q) < 2) return ['items'=>[], 'total_pages'=>1, 'total_hits'=>0];

  $norm = mb_strtolower($q);
  $norm = preg_replace('/\s+/', ' ', $norm);

  $limit = max(5, min(30, $limit));
  $page0 = max(0, $page0);

  $cache_key = 'kuladig_sr2_' . md5($norm) . '_p' . $page0 . '_l' . $limit;
  $cached = get_transient($cache_key);
  if ($cached !== false && is_array($cached)) return $cached;

  $url = 'https://www.kuladig.de/api/public/Objekt?ObjektTyp=KuladigObjekt'
       . '&Seite=' . $page0
       . '&suchText=' . rawurlencode($norm);

  $resp = wp_remote_get($url, ['timeout' => 15]);

  $items = [];
  $total_pages = 1;
  $total_hits  = 0;

  if (!is_wp_error($resp)) {
    $json = json_decode(wp_remote_retrieve_body($resp), true);

    if (is_array($json)) {
      $total_pages = isset($json['AnzahlSeiten']) ? max(1, (int)$json['AnzahlSeiten']) : 1;
      $total_hits  = isset($json['AnzahlTreffer']) ? (int)$json['AnzahlTreffer'] : (isset($json['AnzahlErgebnisse']) ? (int)$json['AnzahlErgebnisse'] : 0);

      if (!empty($json['Ergebnis']) && is_array($json['Ergebnis'])) {
        foreach ($json['Ergebnis'] as $obj) {
          if (count($items) >= $limit) break;

          $desc = isset($obj['Beschreibung']) ? (string)$obj['Beschreibung'] : '';
          $desc = preg_replace('/\[[^\]]*\]/', '', $desc);
          $desc = trim(preg_replace('/\s+/', ' ', $desc));
          if (mb_strlen($desc) > 220) $desc = mb_substr($desc, 0, 217) . '...';

          // Optional: Location, wenn vorhanden
          $loc = '';
          foreach (['Ort', 'Ortsteil', 'Adresse', 'Kurzadresse', 'Standort'] as $k) {
            if (!empty($obj[$k])) { $loc = (string)$obj[$k]; break; }
          }

          $items[] = [
            'id'    => $obj['Id'] ?? '',
            'name'  => $obj['Name'] ?? '',
            'desc'  => $desc,
            'token' => $obj['ThumbnailToken'] ?? '',
            'loc'   => $loc,
          ];
        }
      }
    }
  }

  $payload = ['items'=>$items, 'total_pages'=>$total_pages, 'total_hits'=>$total_hits];
  set_transient($cache_key, $payload, 12 * HOUR_IN_SECONDS);
  return $payload;
}

add_shortcode('kuladig_search_results', function ($atts) {
  $atts = shortcode_atts([
    'limit' => '20',
    'detail_page' => '/ort/',
  ], $atts);

  $limit = max(5, min(30, (int)$atts['limit']));
  $detail_page = trim((string)$atts['detail_page']);
  if ($detail_page === '') $detail_page = '/ort/';

  $q = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
  $q = trim($q);

  $p = isset($_GET['p']) ? (int)$_GET['p'] : 1;
  if ($p < 1) $p = 1;
  $page0 = $p - 1;

  if (mb_strlen($q) < 2) {
    return '<div class="kldsr-wrap"><div class="kldsr-head"><h1 class="kldsr-h1">Bitte gib einen Suchbegriff ein.</h1></div></div>';
  }

  $data = kuladig_sr2_fetch_page_cached($q, $page0, $limit);
  $items = $data['items'] ?? [];
  $total_pages = (int)($data['total_pages'] ?? 1);
  $total_hits  = (int)($data['total_hits'] ?? 0);

  $guess_category = function(string $name, string $desc): array {
    $t = mb_strtolower($name . ' ' . $desc);

    $is_arch = preg_match('/\b(schloss|kirche|kathedrale|universit|bahnhof|brücke|straße|haus|gebäude|tor|burg)\b/u', $t);
    $is_nat  = preg_match('/\b(wald|park|naturschutz|fluss|see|berg|tal|quelle|felsen|natur)\b/u', $t);
    $is_hist = preg_match('/\b(denkmal|relief|museum|geschichte|histor|altstadt|markt|gedenk)\b/u', $t);

    if ($is_arch) return ['slug'=>'architektur', 'label'=>'ARCHITEKTUR'];
    if ($is_nat)  return ['slug'=>'natur', 'label'=>'NATURDENKMAL'];
    if ($is_hist) return ['slug'=>'historisch', 'label'=>'HISTORISCHER ORT'];
    return ['slug'=>'sonstiges', 'label'=>'SONSTIGES'];
  };

  $base_url = get_permalink() ?: home_url('/suche/');
  $mk_url = function($page) use ($base_url, $q) {
    return esc_url(add_query_arg(['q'=>$q,'p'=>$page], $base_url));
  };

  ob_start();
  ?>
  <div class="kldsr-wrap">
    <div class="kldsr-head">
      <div class="kldsr-head-left">
        <div class="kldsr-kicker">Suchergebnisse für</div>
        <h1 class="kldsr-h1">„<?php echo esc_html($q); ?>“</h1>
      </div>

      <div class="kldsr-head-right">
        <span class="kldsr-pill"><?php echo (int)($total_hits > 0 ? $total_hits : count($items)); ?> Treffer</span>
        <button class="kldsr-filter-btn" type="button" data-kldsr-toggle="filters">Filter</button>
        <div class="kldsr-sort">
          <label class="kldsr-sort-label" for="kldsrSort">Sortierung</label>
          <select id="kldsrSort" class="kldsr-sort-select">
            <option value="relevance">Relevanz</option>
            <option value="az">A–Z</option>
          </select>
        </div>
      </div>
    </div>

    <div class="kldsr-layout">
      <aside class="kldsr-filters" data-kldsr-panel="filters">
        <div class="kldsr-box">
          <div class="kldsr-box-title">Kategorien</div>
          <div class="kldsr-radio">
            <label><input type="radio" name="kldsrCat" value="all" checked> Alle <span class="kldsr-count" data-kldsr-count="all">0</span></label>
            <label><input type="radio" name="kldsrCat" value="architektur"> Architektur <span class="kldsr-count" data-kldsr-count="architektur">0</span></label>
            <label><input type="radio" name="kldsrCat" value="historisch"> Historische Orte <span class="kldsr-count" data-kldsr-count="historisch">0</span></label>
            <label><input type="radio" name="kldsrCat" value="natur"> Naturdenkmäler <span class="kldsr-count" data-kldsr-count="natur">0</span></label>
          </div>
        </div>

     
      </aside>

      <main class="kldsr-results">
        <div class="kldsr-list" id="kldsrList">
          <?php if (empty($items)): ?>
            <div class="kldsr-empty">Keine Treffer gefunden.</div>
          <?php else: ?>
            <?php foreach ($items as $it): ?>
              <?php
                $id = (string)($it['id'] ?? '');
                $name = (string)($it['name'] ?? '');
                $desc = (string)($it['desc'] ?? '');
                $loc  = (string)($it['loc'] ?? '');
                $token = (string)($it['token'] ?? '');
                $cat = $guess_category($name, $desc);

                $img = $token ? 'https://www.kuladig.de/api/public/Dokument?token=' . rawurlencode($token) : '';
                $detail_url = esc_url(add_query_arg('id', $id, home_url($detail_page)));
              ?>
              <a class="kldsr-item" href="<?php echo $detail_url; ?>"
                 data-cat="<?php echo esc_attr($cat['slug']); ?>"
                 data-name="<?php echo esc_attr(mb_strtolower($name)); ?>">
                <div class="kldsr-thumb">
                  <?php if ($img): ?>
                    <img loading="lazy" decoding="async" src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($name); ?>">
                  <?php else: ?>
                    <div class="kldsr-thumb-empty"></div>
                  <?php endif; ?>
                </div>

                <div class="kldsr-body">
                  <div class="kldsr-badge kldsr-badge--<?php echo esc_attr($cat['slug']); ?>"><?php echo esc_html($cat['label']); ?></div>
                  <div class="kldsr-meta"><span class="kldsr-loc"><?php echo $loc !== '' ? esc_html($loc) : '—'; ?></span></div>
                  <h3 class="kldsr-title"><?php echo esc_html($name); ?></h3>
                  <p class="kldsr-desc"><?php echo esc_html($desc); ?></p>
                  <?php if ($id !== ''): ?><div class="kldsr-id">ID: <?php echo esc_html($id); ?></div><?php endif; ?>
                </div>

                <div class="kldsr-cta">Details ansehen →</div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
          <nav class="kldsr-pagination" aria-label="Pagination">
            <?php
              $current = $p;
              $start = max(1, $current - 3);
              $end   = min($total_pages, $current + 3);
              $prev = max(1, $current - 1);
              $next = min($total_pages, $current + 1);
            ?>
            <a class="kldsr-page kldsr-page--nav" href="<?php echo $mk_url($prev); ?>" aria-label="Vorherige Seite">‹</a>
            <?php for ($i = $start; $i <= $end; $i++): ?>
              <a class="kldsr-page <?php echo $i === $current ? 'is-active' : ''; ?>" href="<?php echo $mk_url($i); ?>"><?php echo (int)$i; ?></a>
            <?php endfor; ?>
            <a class="kldsr-page kldsr-page--nav" href="<?php echo $mk_url($next); ?>" aria-label="Nächste Seite">›</a>
          </nav>
        <?php endif; ?>
      </main>
    </div>
  </div>

  <script>
  (function(){
    var wrap = document.querySelector('.kldsr-wrap');
    if(!wrap) return;

    var list = document.getElementById('kldsrList');
    if(!list) return;

    var sortSel = document.getElementById('kldsrSort');
    var catRadios = wrap.querySelectorAll('input[name="kldsrCat"]');
    var toggleBtn = wrap.querySelector('[data-kldsr-toggle="filters"]');
    var panel = wrap.querySelector('[data-kldsr-panel="filters"]');

    function updateCounts(){
      var items = Array.from(list.querySelectorAll('.kldsr-item'));
      var c = { all:0, architektur:0, historisch:0, natur:0 };
      items.forEach(function(a){
        var cat = a.getAttribute('data-cat') || 'all';
        c.all++;
        if(c[cat] !== undefined) c[cat]++;
      });
      Object.keys(c).forEach(function(k){
        var el = wrap.querySelector('[data-kldsr-count="'+k+'"]');
        if(el) el.textContent = c[k];
      });
    }

    function applyFilter(){
      var val = 'all';
      catRadios.forEach(function(r){ if(r.checked) val = r.value; });
      var items = Array.from(list.querySelectorAll('.kldsr-item'));
      items.forEach(function(a){
        var cat = a.getAttribute('data-cat') || '';
        a.style.display = (val === 'all' || cat === val) ? '' : 'none';
      });
    }

    function applySort(){
      if(!sortSel) return;
      var mode = sortSel.value || 'relevance';
      if(mode !== 'az') return;

      var items = Array.from(list.querySelectorAll('.kldsr-item'));
      items.sort(function(a,b){
        var an = (a.getAttribute('data-name')||'').toLowerCase();
        var bn = (b.getAttribute('data-name')||'').toLowerCase();
        return an.localeCompare(bn, 'de');
      });
      items.forEach(function(it){ list.appendChild(it); });
    }

    catRadios.forEach(function(r){
      r.addEventListener('change', function(){ applyFilter(); });
    });

    if(sortSel){
      sortSel.addEventListener('change', function(){
        applySort();
        applyFilter();
      });
    }

    if(toggleBtn && panel){
      toggleBtn.addEventListener('click', function(){
        panel.classList.toggle('is-open');
      });
    }

    updateCounts();
    applySort();
    applyFilter();
  })();
  </script>
  <?php
  return ob_get_clean();
});

/**
 * Trending Places Cards
 */
/**
 * Shortcode: [kld_trending_places]
 * Lädt 3 Orte mit Bild aus KuLaDig API und zeigt nur große Cards (ohne Header/CTA/Dots BG)
 */
add_shortcode('kld_trending_places', function ($atts) {
  $uid = 'kldtp_' . substr(md5(uniqid('', true)), 0, 10);

  $atts = shortcode_atts([
    'project' => defined('KULADIG_PROJECT_ID') ? (int)KULADIG_PROJECT_ID : 0,
  ], $atts);

  $project = (int)$atts['project'];

  ob_start(); ?>

  <style>
    /* ===== Trending Places (nur Cards) ===== */
    #<?php echo esc_attr($uid); ?>{
      max-width: 1280px;
      width: min(1280px, 96vw);
      margin: 18px auto 26px;
      padding: 0;
    }

    #<?php echo esc_attr($uid); ?> .kldtp-grid{
      display:grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 18px;
      align-items: stretch;
    }

    /* größer */
    #<?php echo esc_attr($uid); ?> .kldtp-card{
      position:relative;
      border-radius: 26px;
      overflow:hidden;
      min-height: 320px;
      background:#0b1220;
      border: 1px solid rgba(15,23,42,.08);
      box-shadow: 0 22px 65px rgba(15, 23, 42, 0.12);
      transform: translateZ(0);
    }

    #<?php echo esc_attr($uid); ?> .kldtp-card a{
      display:block;
      height:100%;
      text-decoration:none;
      color:inherit;
    }

    #<?php echo esc_attr($uid); ?> .kldtp-img{
      position:absolute; inset:0;
      width:100%; height:100%;
      object-fit:cover;
      transform: scale(1.02);
      transition: transform .35s ease;
    }

    #<?php echo esc_attr($uid); ?> .kldtp-grad{
      position:absolute; inset:0;
      background: linear-gradient(to top,
        rgba(0,0,0,.78) 0%,
        rgba(0,0,0,.35) 42%,
        rgba(0,0,0,.10) 68%,
        rgba(0,0,0,0) 100%);
      pointer-events:none;
    }

    #<?php echo esc_attr($uid); ?> .kldtp-pill{
      position:absolute;
      top: 14px; left: 14px;
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding: 8px 11px;
      border-radius: 999px;
      background: rgba(15,23,42,.55);
      border: 1px solid rgba(255,255,255,.16);
      color: rgba(255,255,255,.92);
      font-weight: 900;
      font-size: 12px;
      backdrop-filter: blur(8px);
      z-index:2;
    }
    #<?php echo esc_attr($uid); ?> .kldtp-dot{
      width:10px; height:10px;
      border-radius:999px;
      background: #22c55e;
      box-shadow: 0 0 0 4px rgba(34,197,94,.16);
      flex:0 0 auto;
    }

    #<?php echo esc_attr($uid); ?> .kldtp-body{
      position:absolute;
      left: 16px; right: 16px;
      bottom: 16px;
      z-index:2;
      color:#fff;
    }

    /* größerer Titel */
    #<?php echo esc_attr($uid); ?> .kldtp-h{
      margin:0 0 8px;
      font-size: 24px;
      font-weight: 950;
      line-height: 1.05;
      text-shadow: 0 10px 24px rgba(0,0,0,.55);
    }

    #<?php echo esc_attr($uid); ?> .kldtp-p{
      margin:0;
      font-size: 13px;
      font-weight: 800;
      color: rgba(255,255,255,.82);
      max-width: 92%;
      display:-webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow:hidden;
      text-shadow: 0 10px 24px rgba(0,0,0,.50);
    }

    #<?php echo esc_attr($uid); ?> .kldtp-link{
      margin-top: 12px;
      display:inline-flex;
      align-items:center;
      gap:10px;
      color:#fff;
      font-weight: 900;
      font-size: 13px;
      opacity:.92;
    }

    @media (hover:hover){
      #<?php echo esc_attr($uid); ?> .kldtp-card{
        transition: transform .18s ease, box-shadow .18s ease;
      }
      #<?php echo esc_attr($uid); ?> .kldtp-card:hover{
        transform: translateY(-3px);
        box-shadow: 0 28px 80px rgba(15,23,42,.16);
      }
      #<?php echo esc_attr($uid); ?> .kldtp-card:hover .kldtp-img{
        transform: scale(1.07);
      }
    }

    @media (max-width: 980px){
      #<?php echo esc_attr($uid); ?> .kldtp-grid{ grid-template-columns: 1fr; }
      #<?php echo esc_attr($uid); ?> .kldtp-card{ min-height: 320px; }
    }

    /* Skeleton */
    #<?php echo esc_attr($uid); ?> .kldtp-skel{
      border-radius: 26px;
      min-height: 320px;
      border: 1px solid rgba(15,23,42,.08);
      background: linear-gradient(90deg, rgba(15,23,42,.06), rgba(15,23,42,.03), rgba(15,23,42,.06));
      background-size: 200% 100%;
      animation: kldtp_shimmer 1.15s linear infinite;
    }
    @keyframes kldtp_shimmer{
      0%{ background-position: 0% 0; }
      100%{ background-position: 200% 0; }
    }

    #<?php echo esc_attr($uid); ?> .kldtp-note{
      margin-top: 10px;
      font-size: 12px;
      color:#64748b;
      font-weight: 700;
      display:none;
    }
    #<?php echo esc_attr($uid); ?> .kldtp-note.is-on{ display:block; }
  </style>

  <section id="<?php echo esc_attr($uid); ?>" data-project="<?php echo esc_attr($project); ?>">
    <div class="kldtp-grid" id="<?php echo esc_attr($uid); ?>_grid" aria-live="polite">
      <div class="kldtp-skel"></div>
      <div class="kldtp-skel"></div>
      <div class="kldtp-skel"></div>
    </div>
    <div class="kldtp-note" id="<?php echo esc_attr($uid); ?>_note">
      Keine passenden Orte gefunden (oder API nicht erreichbar).
    </div>
  </section>

  <script>
  (function(){
    const root = document.getElementById(<?php echo wp_json_encode($uid); ?>);
    if(!root) return;

    const grid = document.getElementById(<?php echo wp_json_encode($uid . '_grid'); ?>);
    const note = document.getElementById(<?php echo wp_json_encode($uid . '_note'); ?>);
    const projectId = parseInt(root.getAttribute('data-project') || '0', 10) || 0;

    const PILL = ["Trending #1","Most Viewed","Editor's Pick"];

    const TTL = 12 * 60 * 60 * 1000; // 12h
    const KEY = "kld_trending_v2_p" + projectId;

    function cacheGet(){
      try{
        const raw = localStorage.getItem(KEY);
        if(!raw) return null;
        const o = JSON.parse(raw);
        if(!o || !o.ts || !Array.isArray(o.items)) return null;
        if(Date.now() - o.ts > TTL) return null;
        return o.items;
      }catch(e){ return null; }
    }
    function cacheSet(items){
      try{ localStorage.setItem(KEY, JSON.stringify({ ts: Date.now(), items })); }catch(e){}
    }

    function esc(s){
      return (s||"").toString()
        .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
        .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
    }

    function thumbUrl(token){
      token = (token||"").toString().trim();
      if(!token) return "";
      return "https://www.kuladig.de/api/Media/Vespa?token=" + encodeURIComponent(token);
    }

    function pick3(arr){
      const a = arr.slice();
      for(let i=a.length-1;i>0;i--){
        const j = Math.floor(Math.random()*(i+1));
        [a[i],a[j]]=[a[j],a[i]];
      }
      return a.slice(0,3);
    }

    function render(items){
      if(!Array.isArray(items) || items.length < 1){
        grid.innerHTML = "";
        note.classList.add("is-on");
        return;
      }
      note.classList.remove("is-on");

      const three = items.slice(0,3);
      grid.innerHTML = three.map((o, i) => {
        const img  = esc(o.img || "");
        const name = esc(o.name || "Ort");
        const desc = esc(o.desc || "");
        const href = "/ort/?id=" + encodeURIComponent(o.id || "");
        return `
          <div class="kldtp-card">
            <a href="${href}">
              ${img ? `<img class="kldtp-img" src="${img}" alt="${name}" loading="lazy" decoding="async">` : ``}
              <div class="kldtp-grad"></div>
              <div class="kldtp-pill"><span class="kldtp-dot"></span> ${esc(PILL[i] || "Trending")}</div>
              <div class="kldtp-body">
                <div class="kldtp-h">${name}</div>
                <div class="kldtp-p">${desc || "Mehr entdecken."}</div>
                <div class="kldtp-link">View Details <span aria-hidden="true">→</span></div>
              </div>
            </a>
          </div>
        `;
      }).join("");
    }

    async function load(){
      const cached = cacheGet();
      if(cached && cached.length){
        render(cached);
        return;
      }

      let url = "https://www.kuladig.de/api/public/Objekt?ObjektTyp=KuladigObjekt&Seite=0";
      if(projectId) url += "&Projekt=" + encodeURIComponent(projectId);

      try{
        const res = await fetch(url, { cache: "no-store" });
        if(!res.ok) throw new Error("HTTP " + res.status);
        const json = await res.json();

        const arr = Array.isArray(json && json.Ergebnis) ? json.Ergebnis : [];
        const pool = [];

        for(const r of arr){
          if(!r || !r.Id || !r.Name) continue;
          const token = (r.ThumbnailToken || "").toString().trim();
          if(!token) continue;

          pool.push({
            id: String(r.Id),
            name: String(r.Name || "").trim(),
            desc: String(r.Beschreibung || "").trim(),
            img: thumbUrl(token)
          });

          if(pool.length >= 18) break;
        }

        const items = pick3(pool);
        cacheSet(items);
        render(items);

      }catch(e){
        render([]);
      }
    }

    load();
  })();
  </script>

  <?php
  return ob_get_clean();
});

/**
 * AJAX-Endpoint
 */
/**
 * KuLaDig Map AJAX Proxy (super schnell durch Server-Cache + optional Prefix-Cache)
 * Action: kld_map_proxy
 *
 * Erwartet POST:
 * - action = kld_map_proxy
 * - nonce  = (wp_create_nonce('kld_map_nonce'))
 * - mode   = viewport | search | suggest
 * - q      = Suchtext (für search/suggest)
 * - project = int
 * - only_img = 0|1
 * - page   = int (optional)
 * - geom   = JSON (nur viewport) -> bereits JSON-String
 */

add_action('wp_ajax_kld_map_proxy', 'kld_map_proxy');
add_action('wp_ajax_nopriv_kld_map_proxy', 'kld_map_proxy');

/** Serverseitiger Proxy fuer Map-Requests inkl. Cache und Filter. */
function kld_map_proxy() {
  // Security / Nonce
  $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
  if (!$nonce || !wp_verify_nonce($nonce, 'kld_map_nonce')) {
    wp_send_json_error(['message' => 'Bad nonce'], 400);
  }

  $mode    = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'search';
  $q       = isset($_POST['q']) ? trim(sanitize_text_field(wp_unslash($_POST['q']))) : '';
  $project = isset($_POST['project']) ? max(0, (int) $_POST['project']) : 0;
  $onlyImg = !empty($_POST['only_img']) && (string) $_POST['only_img'] !== '0';
  $page    = isset($_POST['page']) ? max(0, (int) $_POST['page']) : 0;
  $geom    = isset($_POST['geom']) ? wp_unslash($_POST['geom']) : '';

  // Helpers
  $normalize = function($s){
    $s = mb_strtolower((string)$s);
    $s = preg_replace('/\s+/u', ' ', trim($s));
    return $s;
  };
  $qNorm = $normalize($q);

  // Für Speed: Prefix-Cache (holt breiter, filtert lokal)
  $prefixLen = 3;
  $prefix = ($qNorm && mb_strlen($qNorm) >= 2) ? mb_substr($qNorm, 0, $prefixLen) : '';

  // Cache-Key (Transient)
  $cacheKey = 'kld_map_' . md5($mode.'|p'.$project.'|img'.($onlyImg?1:0).'|page'.$page.'|q='.$qNorm.'|pre='.$prefix.'|g='.substr(md5($geom),0,10));
  $cached = get_transient($cacheKey);
  if ($cached !== false) {
    wp_send_json_success(['cached' => true, 'items' => $cached]);
  }

  // Build KuLaDig URL
  $base = 'https://www.kuladig.de/api/public/Objekt?ObjektTyp=KuladigObjekt';

  if ($mode === 'viewport') {
    if (!$geom) wp_send_json_error(['message' => 'Missing geom'], 400);
    $url = $base . '&Geometrie=' . rawurlencode($geom) . '&Seite=' . $page;
    if ($project) $url .= '&Projekt=' . rawurlencode((string)$project);
  } else {
    // search / suggest
    if (mb_strlen($qNorm) < 2) {
      set_transient($cacheKey, [], 30 * MINUTE_IN_SECONDS);
      wp_send_json_success(['cached' => false, 'items' => []]);
    }

    // Trick für Geschwindigkeit + "in Beschreibung suchen":
    // Wir fragen KuLaDig nur nach dem PREFIX ab (breiter), und filtern dann serverseitig nach dem vollen Suchtext in Name+Beschreibung.
    $apiText = $prefix ?: $qNorm;

    $url = $base . '&suchText=' . rawurlencode($apiText) . '&Seite=' . $page;
    if ($project) $url .= '&Projekt=' . rawurlencode((string)$project);
  }

  $resp = wp_remote_get($url, [
    'timeout' => 10,
    'headers' => ['Accept' => 'application/json'],
  ]);

  if (is_wp_error($resp)) {
    wp_send_json_error(['message' => 'KuLaDig API Fehler'], 500);
  }

  $json = json_decode(wp_remote_retrieve_body($resp), true);
  $arr  = is_array($json) && !empty($json['Ergebnis']) && is_array($json['Ergebnis']) ? $json['Ergebnis'] : [];

  // Map + Komprimieren
  $items = [];
  foreach ($arr as $r) {
    if (empty($r['Id']) || empty($r['Punktkoordinate']['coordinates'])) continue;

    $lon = $r['Punktkoordinate']['coordinates'][0] ?? null;
    $lat = $r['Punktkoordinate']['coordinates'][1] ?? null;
    if (!is_numeric($lat) || !is_numeric($lon)) continue;

    $name = isset($r['Name']) ? trim((string)$r['Name']) : '';
    if ($name === '') continue;

    $desc = isset($r['Beschreibung']) ? (string)$r['Beschreibung'] : '';
    // BBCode grob entfernen + normalisieren (schnell)
    $desc = preg_replace('/\[[^\]]*\]/', '', $desc);
    $desc = trim(preg_replace('/\s+/u', ' ', $desc));
    if (mb_strlen($desc) > 220) $desc = mb_substr($desc, 0, 217) . '...';

    $thumb = isset($r['ThumbnailToken']) ? trim((string)$r['ThumbnailToken']) : '';
    if ($onlyImg && $thumb === '') continue;

    $o = [
      'id'    => (string)$r['Id'],
      'name'  => $name,
      'desc'  => $desc,
      'lat'   => (float)$lat,
      'lon'   => (float)$lon,
      'thumb' => $thumb,
    ];

    // Serverseitig nach VOLLEM Suchtext filtern (Name + Beschreibung)
    if ($mode !== 'viewport') {
      $hay = $normalize($o['name'] . ' ' . $o['desc']);
      // alle Wörter müssen vorkommen
      $words = array_values(array_filter(explode(' ', $qNorm)));
      $ok = true;
      foreach ($words as $w) {
        if ($w !== '' && mb_strpos($hay, $w) === false) { $ok = false; break; }
      }
      if (!$ok) continue;
    }

    $items[] = $o;

    // Hartes Limit für Performance (kannst du erhöhen)
    if (count($items) >= 1200) break;
  }

  // Cache TTL
  $ttl = $items ? 7 * DAY_IN_SECONDS : 30 * MINUTE_IN_SECONDS;
  set_transient($cacheKey, $items, $ttl);

  wp_send_json_success(['cached' => false, 'items' => $items]);
}
