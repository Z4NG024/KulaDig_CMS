/* Frontend-Logik fuer die KuLaDig-Suche (AJAX + Rendering). */
(function () {
  // Quelle fuer den AJAX-Endpunkt: data-Attribut, globale Variable, oder Default.
  function getAjaxUrl(root) {
    if (!root) {
      return '/wp-admin/admin-ajax.php';
    }
    var attr = root.getAttribute('data-ajax-url');
    if (attr) {
      return attr;
    }
    if (typeof window !== 'undefined' && window.kuladigSearchAjaxUrl) {
      return window.kuladigSearchAjaxUrl;
    }
    return '/wp-admin/admin-ajax.php';
  }

  // Entfernt Shortcode-Reste und trimmt den Text.
  function cleanText(value) {
    var text = (value || '').toString().replace(/\[.*?\]/g, '');
    return text.trim();
  }

  // Minimales HTML-Escaping fuer Inhalte aus der API.
  function escapeHtml(value) {
    var text = (value || '').toString();
    return text.replace(/[&<>"']/g, function (ch) {
      switch (ch) {
        case '&':
          return '&amp;';
        case '<':
          return '&lt;';
        case '>':
          return '&gt;';
        case '"':
          return '&quot;';
        case '\'':
          return '&#039;';
        default:
          return ch;
      }
    });
  }

  function setMessage(msgBox, text) {
    if (!msgBox) {
      return;
    }
    msgBox.textContent = text || '';
  }

  // Baut die Ergebnisliste komplett neu auf.
  function renderResults(resultBox, msgBox, items) {
    if (!resultBox) {
      return;
    }
    resultBox.innerHTML = '';
    if (!items || !items.length) {
      setMessage(msgBox, 'Keine Treffer gefunden.');
      return;
    }
    setMessage(msgBox, 'Treffer: ' + items.length);

    for (var i = 0; i < items.length; i++) {
      var obj = items[i] || {};
      var name = obj.Name || '';
      var desc = cleanText(obj.Beschreibung || '');
      var id = obj.Id || '';
      var token = obj.ThumbnailToken || '';

      if (desc.length > 260) {
        desc = desc.substring(0, 257) + '...';
      }

      var imgHtml = '';
      if (token) {
        imgHtml =
          '<div class="kuladig-card-image">' +
            '<img src="https://www.kuladig.de/api/public/Dokument?token=' + encodeURIComponent(token) + '" alt="' + escapeHtml(name) + '">' +
          '</div>';
      }

      var cardHtml =
        '<a class="kuladig-card kuladig-card-search" href="/ort/?id=' + encodeURIComponent(id) + '">' +
          imgHtml +
          '<div class="kuladig-card-body">' +
            '<h3 class="kuladig-title">' + escapeHtml(name) + '</h3>' +
            '<p class="kuladig-desc">' + escapeHtml(desc) + '</p>' +
          '</div>' +
        '</a>';

      resultBox.insertAdjacentHTML('beforeend', cardHtml);
    }
  }

  // POST-Helper mit fetch + XHR-Fallback.
  function post(url, body, onSuccess, onError) {
    if (typeof window !== 'undefined' && typeof window.fetch === 'function') {
      fetch(url, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
        body: body
      })
        .then(function (res) { return res.json(); })
        .then(onSuccess)
        .catch(onError);
      return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) {
        return;
      }
      if (xhr.status >= 200 && xhr.status < 300) {
        try {
          var data = JSON.parse(xhr.responseText);
          onSuccess(data);
        } catch (err) {
          onError(err);
        }
        return;
      }
      onError(new Error('Request failed'));
    };
    xhr.onerror = function () {
      onError(new Error('Request failed'));
    };
    xhr.send(body);
  }

  // Pro Such-Box die Events binden.
  function init(root) {
    if (!root) {
      return;
    }
    var form = root.querySelector('.kuladig-search-form');
    var input = root.querySelector('.kuladig-search-input');
    var msgBox = root.querySelector('.kuladig-search-message');
    var resultBox = root.querySelector('.kuladig-search-results');

    if (!form || !input || !resultBox) {
      return;
    }

    var ajaxUrl = getAjaxUrl(root);

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      var q = (input.value || '').trim();
      resultBox.innerHTML = '';
      setMessage(msgBox, '');

      if (q.length < 2) {
        setMessage(msgBox, 'Bitte mindestens 2 Zeichen eingeben.');
        return;
      }

      setMessage(msgBox, 'Suche laeuft...');

      var body = 'action=kuladig_search&q=' + encodeURIComponent(q);

      post(ajaxUrl, body, function (data) {
        if (!data || !data.success) {
          var msg = (data && data.data && data.data.message) ? data.data.message : 'Fehler bei der Suche.';
          setMessage(msgBox, msg);
          return;
        }
        renderResults(resultBox, msgBox, (data.data && data.data.items) ? data.data.items : []);
      }, function (err) {
        if (typeof console !== 'undefined' && console.error) {
          console.error(err);
        }
        setMessage(msgBox, 'Fehler bei der Suche.');
      });
    });
  }

  // Mehrere Instanzen auf einer Seite unterstuetzen.
  function initAll() {
    var roots = document.querySelectorAll('.kuladig-search');
    for (var i = 0; i < roots.length; i++) {
      init(roots[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();
