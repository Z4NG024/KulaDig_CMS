<?php
// Shortcode: [kuladig_search]
function kuladig_search_shortcode($atts) {
  // Eindeutige ID fuer mehrere Instanzen auf einer Seite.
  $uid = 'kld_' . wp_generate_password(6, false, false);
  $ajax_url = admin_url('admin-ajax.php');

  ob_start();
  ?>
  <div class="kuladig-search" id="<?php echo esc_attr($uid); ?>" data-ajax-url="<?php echo esc_url($ajax_url); ?>">
    <form class="kuladig-search-form">
      <input type="text" class="kuladig-search-input" placeholder="Ort eingeben" />
      <button type="submit">Suchen</button>
    </form>

    <div class="kuladig-search-message"></div>
    <div class="kuladig-search-results kuladig-search-results-grid"></div>
  </div>
  <?php
  return ob_get_clean();
}
add_shortcode('kuladig_search', 'kuladig_search_shortcode');


// AJAX: gecachte Suche
function kuladig_ajax_search_handler() {
  $q = isset($_POST['q']) ? sanitize_text_field(wp_unslash($_POST['q'])) : '';
  $q = trim($q);

  if ($q === '' || mb_strlen($q) < 2) {
    wp_send_json_error(['message' => 'Bitte mindestens 2 Zeichen eingeben.']);
  }

  // Cache-Key pro Suchbegriff.
  $norm = mb_strtolower($q);
  $norm = preg_replace('/\s+/', ' ', $norm);
  $cache_key = 'kuladig_query_' . md5($norm);

  $cached = get_transient($cache_key);
  if ($cached !== false) {
    wp_send_json_success(['items' => $cached]);
  }

  // KuLaDig API (nur wenn nicht gecached).
  $url = 'https://www.kuladig.de/api/public/Objekt?ObjektTyp=KuladigObjekt&Seite=0&suchText=' . rawurlencode($q);

  $resp = wp_remote_get($url, ['timeout' => 15]);
  if (is_wp_error($resp)) {
    wp_send_json_error(['message' => 'Fehler beim Laden der Daten.']);
  }

  $json = json_decode(wp_remote_retrieve_body($resp), true);
  if (!is_array($json) || empty($json['Ergebnis'])) {
    // "Keine Treffer" kurz cachen.
    set_transient($cache_key, [], 30 * MINUTE_IN_SECONDS);
    wp_send_json_success(['items' => []]);
  }

  // Kompakt speichern, damit der Cache klein bleibt.
  $items = [];
  foreach ($json['Ergebnis'] as $obj) {
    $items[] = [
      'Id' => $obj['Id'] ?? '',
      'Name' => $obj['Name'] ?? '',
      'Beschreibung' => $obj['Beschreibung'] ?? '',
      'ThumbnailToken' => $obj['ThumbnailToken'] ?? '',
    ];
  }

  // 24h Cache
  set_transient($cache_key, $items, 24 * HOUR_IN_SECONDS);

  wp_send_json_success(['items' => $items]);
}
add_action('wp_ajax_kuladig_search', 'kuladig_ajax_search_handler');
add_action('wp_ajax_nopriv_kuladig_search', 'kuladig_ajax_search_handler');
