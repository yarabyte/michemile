<?php
$filterStatus = $filterStatus ?? null;
$counts = $counts ?? ['all' => 0, 'new' => 0, 'confirmed' => 0, 'cancelled' => 0, 'completed' => 0];
$items = $items ?? [];
$selected = $selected ?? null;
$selectedId = (int) ($selectedId ?? 0);
$filterKey = $filterStatus ?: '';

$fmtDate = static function (?string $dt): string {
    if (!$dt) {
        return '';
    }
    $ts = strtotime($dt);
    if (!$ts) {
        return (string) $dt;
    }
    $today = strtotime('today');
    $yesterday = strtotime('yesterday');
    if ($ts >= $today) {
        return 'Aujourd’hui · ' . date('H:i', $ts);
    }
    if ($ts >= $yesterday) {
        return 'Hier · ' . date('H:i', $ts);
    }
    return date('d/m/Y · H:i', $ts);
};

$fmtStay = static function (?string $in, ?string $out): string {
    if (!$in || !$out) {
        return '—';
    }
    $a = strtotime($in);
    $b = strtotime($out);
    if (!$a || !$b) {
        return $in . ' → ' . $out;
    }
    return date('d/m/Y', $a) . ' → ' . date('d/m/Y', $b);
};

$nights = static function (?string $in, ?string $out): int {
    $a = strtotime((string) $in);
    $b = strtotime((string) $out);
    if (!$a || !$b || $b <= $a) {
        return 0;
    }
    return (int) round(($b - $a) / 86400);
};

$statuses = [
    '' => ['label' => 'Toutes', 'count' => (int) $counts['all']],
    'new' => ['label' => 'Nouvelles', 'count' => (int) $counts['new']],
    'confirmed' => ['label' => 'Confirmées', 'count' => (int) $counts['confirmed']],
    'cancelled' => ['label' => 'Annulées', 'count' => (int) $counts['cancelled']],
    'completed' => ['label' => 'Terminées', 'count' => (int) $counts['completed']],
];

$payload = [];
foreach ($items as $item) {
    $in = (string) ($item['check_in'] ?? '');
    $out = (string) ($item['check_out'] ?? '');
    $n = $nights($in, $out);
    $payload[] = [
        'id' => (int) $item['id'],
        'reference' => (string) $item['reference'],
        'guest_name' => (string) $item['guest_name'],
        'guest_email' => (string) $item['guest_email'],
        'guest_phone' => (string) ($item['guest_phone'] ?? ''),
        'room_name' => (string) ($item['room_name'] ?? ''),
        'check_in' => $in,
        'check_out' => $out,
        'stay_label' => $fmtStay($in, $out),
        'nights' => $n,
        'nights_label' => $n > 0 ? ($n . ' nuit' . ($n > 1 ? 's' : '')) : '',
        'guests' => (int) ($item['guests'] ?? 1),
        'message' => (string) ($item['message'] ?? ''),
        'admin_notes' => (string) ($item['admin_notes'] ?? ''),
        'status' => (string) $item['status'],
        'status_label' => booking_status_label((string) $item['status']),
        'locale' => (string) ($item['locale'] ?? 'fr'),
        'created_at' => (string) ($item['created_at'] ?? ''),
        'created_label' => $fmtDate((string) ($item['created_at'] ?? '')),
        'notified_at' => (string) ($item['notified_at'] ?? ''),
        'notified_label' => !empty($item['notified_at'])
            ? ('E-mails envoyés · ' . $fmtDate((string) $item['notified_at']))
            : 'E-mails non envoyés',
        'mail_ok' => !empty($item['notified_at']),
    ];
}
$initialId = $selectedId > 0 ? $selectedId : (int) ($payload[0]['id'] ?? 0);
?>
<?php component('page-header', [
    'title' => $title ?? 'Réservations',
    'subtitle' => ((int) $counts['new'] > 0)
        ? ((int) $counts['new'] . ((int) $counts['new'] > 1 ? ' nouvelles' : ' nouvelle') . ' · suivi des séjours')
        : 'Demandes entrantes et suivi',
]); ?>

<nav class="filter-tabs" aria-label="Filtrer par statut">
  <?php foreach ($statuses as $key => $meta):
    $href = admin_url('reservations') . ($key !== '' ? '?statut=' . urlencode($key) : '');
    $active = $filterKey === $key;
  ?>
    <a class="filter-tab <?= $active ? 'filter-tab-active' : '' ?>" href="<?= e($href) ?>">
      <?= e($meta['label']) ?>
      <span class="filter-tab-count"><?= (int) $meta['count'] ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<?php if (empty($payload)): ?>
  <?php component('empty-state', [
      'message' => $filterKey === 'new'
          ? 'Aucune nouvelle réservation.'
          : 'Aucune réservation dans ce filtre.',
  ]); ?>
<?php else: ?>
  <div
    class="msg-inbox"
    x-data='{
      items: <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>,
      selectedId: <?= (int) $initialId ?>,
      q: "",
      get filtered() {
        const q = this.q.trim().toLowerCase();
        if (!q) return this.items;
        return this.items.filter((b) =>
          (b.reference + " " + b.guest_name + " " + b.guest_email + " " + b.guest_phone + " " + b.room_name + " " + b.message).toLowerCase().includes(q)
        );
      },
      get selected() {
        return this.items.find((b) => b.id === this.selectedId) || this.filtered[0] || null;
      },
      select(id) {
        this.selectedId = id;
        const url = new URL(window.location.href);
        url.searchParams.set("id", String(id));
        history.replaceState({}, "", url);
      },
      mailto(b) {
        if (!b) return "#";
        const sub = encodeURIComponent("Réservation " + b.reference);
        return "mailto:" + encodeURIComponent(b.guest_email) + "?subject=" + sub;
      },
      badgeClass(st) {
        if (st === "new") return "ui-badge ui-badge-warn";
        if (st === "cancelled") return "ui-badge ui-badge-danger";
        if (st === "confirmed" || st === "completed") return "ui-badge ui-badge-ok";
        return "ui-badge ui-badge-muted";
      }
    }'
  >
    <aside class="msg-list-pane ui-card">
      <div class="msg-search">
        <label class="sr-only" for="bk-search">Rechercher</label>
        <input
          id="bk-search"
          class="ui-input"
          type="search"
          placeholder="Réf., nom, e-mail, chambre…"
          x-model="q"
        >
      </div>
      <div class="msg-list" role="listbox" aria-label="Liste des réservations">
        <template x-for="b in filtered" :key="b.id">
          <button
            type="button"
            class="msg-row"
            role="option"
            :aria-selected="selectedId === b.id"
            :class="{ 'is-active': selectedId === b.id, 'is-unread': b.status === 'new' }"
            @click="select(b.id)"
          >
            <div class="msg-row-top">
              <span class="msg-row-name" x-text="b.guest_name"></span>
              <span class="msg-row-date" x-text="b.created_label"></span>
            </div>
            <div class="msg-row-subject">
              <span x-text="b.reference"></span>
              <span class="text-muted"> · </span>
              <span x-text="b.stay_label"></span>
            </div>
            <div class="msg-row-preview">
              <span x-text="b.room_name || 'Hébergement indifférent'"></span>
              <span x-show="b.nights_label"> · <span x-text="b.nights_label"></span></span>
            </div>
          </button>
        </template>
        <p class="msg-empty" x-show="filtered.length === 0" style="display:none">Aucun résultat pour cette recherche.</p>
      </div>
    </aside>

    <section class="msg-detail-pane ui-card" x-show="selected" x-cloak>
      <template x-if="selected">
        <div class="msg-detail">
          <header class="msg-detail-head">
            <div class="msg-detail-title-row">
              <h2 class="msg-detail-subject" x-text="selected.reference"></h2>
              <span :class="badgeClass(selected.status)" x-text="selected.status_label"></span>
            </div>
            <div class="msg-detail-meta">
              <div>
                <div class="msg-detail-from" x-text="selected.guest_name"></div>
                <div class="msg-detail-contacts">
                  <a :href="'mailto:' + selected.guest_email" x-text="selected.guest_email"></a>
                  <template x-if="selected.guest_phone">
                    <span> · <a :href="'tel:' + selected.guest_phone" x-text="selected.guest_phone"></a></span>
                  </template>
                </div>
              </div>
              <div class="msg-detail-when" x-text="selected.created_label"></div>
            </div>
          </header>

          <div class="bk-facts">
            <div class="bk-fact">
              <span class="bk-fact-label">Séjour</span>
              <span class="bk-fact-value" x-text="selected.stay_label"></span>
              <span class="bk-fact-sub" x-text="selected.nights_label" x-show="selected.nights_label"></span>
            </div>
            <div class="bk-fact">
              <span class="bk-fact-label">Hébergement</span>
              <span class="bk-fact-value" x-text="selected.room_name || 'Indifférent'"></span>
            </div>
            <div class="bk-fact">
              <span class="bk-fact-label">Voyageurs</span>
              <span class="bk-fact-value" x-text="selected.guests + (selected.guests > 1 ? ' personnes' : ' personne')"></span>
            </div>
            <div class="bk-fact">
              <span class="bk-fact-label">Langue</span>
              <span class="bk-fact-value" x-text="(selected.locale || 'fr').toUpperCase()"></span>
            </div>
          </div>

          <div class="msg-detail-body" x-show="selected.message">
            <div class="bk-msg-label">Message du client</div>
            <div x-text="selected.message"></div>
          </div>
          <div class="msg-detail-body text-muted" x-show="!selected.message" style="display:none">
            Aucun message laissé par le client.
          </div>

          <form
            class="bk-edit"
            method="post"
            action="<?= e(admin_url('reservations/sauver')) ?>"
            @submit="/* keep alpine selected */"
          >
            <?= csrf_field() ?>
            <input type="hidden" name="id" :value="selected.id">
            <input type="hidden" name="filter_status" value="<?= e($filterKey) ?>">
            <div class="bk-edit-grid">
              <label class="ui-label">
                <span>Statut</span>
                <select class="ui-select" name="status" x-model="selected.status">
                  <option value="new">Nouvelle</option>
                  <option value="confirmed">Confirmée</option>
                  <option value="cancelled">Annulée</option>
                  <option value="completed">Terminée</option>
                </select>
              </label>
              <label class="ui-label bk-notes">
                <span>Notes internes</span>
                <textarea class="ui-textarea" name="admin_notes" rows="3" x-model="selected.admin_notes" placeholder="Suivi, conditions, rappel…"></textarea>
              </label>
            </div>
            <div class="msg-detail-actions">
              <button class="ui-btn" type="submit">Enregistrer</button>
              <a class="ui-btn-secondary" :href="mailto(selected)">Écrire au client</a>

              <button
                class="ui-btn-secondary"
                type="submit"
                name="status"
                value="confirmed"
                x-show="selected.status === 'new'"
              >Confirmer</button>
              <button
                class="ui-btn-ghost"
                type="submit"
                name="status"
                value="cancelled"
                x-show="selected.status !== 'cancelled'"
              >Annuler</button>
            </div>
          </form>

          <div class="bk-mail-bar">
            <span class="bk-mail-status" :class="selected.mail_ok ? 'is-ok' : 'is-warn'" x-text="selected.notified_label"></span>
            <form method="post" action="<?= e(admin_url('reservations/renvoyer-email')) ?>" class="msg-quick-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" :value="selected.id">
              <input type="hidden" name="filter_status" value="<?= e($filterKey) ?>">
              <button class="ui-btn-secondary" type="submit">Renvoyer les e-mails</button>
            </form>
          </div>
        </div>
      </template>
    </section>
  </div>
<?php endif; ?>
