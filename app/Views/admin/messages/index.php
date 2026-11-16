<?php
$filterStatus = $filterStatus ?? null;
$counts = $counts ?? ['all' => 0, 'new' => 0, 'read' => 0, 'archived' => 0];
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

$statuses = [
    '' => ['label' => 'Tous', 'count' => (int) $counts['all']],
    'new' => ['label' => 'Nouveaux', 'count' => (int) $counts['new']],
    'read' => ['label' => 'Lus', 'count' => (int) $counts['read']],
    'archived' => ['label' => 'Archivés', 'count' => (int) $counts['archived']],
];

$payload = [];
foreach ($items as $item) {
    $payload[] = [
        'id' => (int) $item['id'],
        'name' => (string) $item['name'],
        'email' => (string) $item['email'],
        'phone' => (string) ($item['phone'] ?? ''),
        'subject' => (string) ($item['subject'] ?? ''),
        'body' => (string) $item['body'],
        'status' => (string) $item['status'],
        'locale' => (string) ($item['locale'] ?? 'fr'),
        'created_at' => (string) $item['created_at'],
        'created_label' => $fmtDate((string) $item['created_at']),
        'status_label' => message_status_label((string) $item['status']),
        'preview' => mb_strimwidth(preg_replace('/\s+/', ' ', (string) $item['body']) ?? '', 0, 110, '…'),
    ];
}
$initialId = $selectedId > 0 ? $selectedId : (int) ($payload[0]['id'] ?? 0);
?>
<?php component('page-header', [
    'title' => $title ?? 'Messages',
    'subtitle' => ((int) $counts['new'] > 0)
        ? ((int) $counts['new'] . ((int) $counts['new'] > 1 ? ' nouveaux' : ' nouveau') . ' · boîte de contact')
        : 'Demandes de contact',
]); ?>

<nav class="filter-tabs" aria-label="Filtrer par statut">
  <?php foreach ($statuses as $key => $meta):
    $href = admin_url('messages') . ($key !== '' ? '?statut=' . urlencode($key) : '');
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
          ? 'Aucun nouveau message. Tout est à jour.'
          : 'Aucun message dans ce filtre.',
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
        return this.items.filter((m) =>
          (m.name + " " + m.email + " " + m.subject + " " + m.body).toLowerCase().includes(q)
        );
      },
      get selected() {
        return this.items.find((m) => m.id === this.selectedId) || this.filtered[0] || null;
      },
      select(id) {
        this.selectedId = id;
        const url = new URL(window.location.href);
        url.searchParams.set("id", String(id));
        history.replaceState({}, "", url);
      },
      mailto(m) {
        if (!m) return "#";
        const sub = encodeURIComponent("Re: " + (m.subject || "Votre message"));
        return "mailto:" + encodeURIComponent(m.email) + "?subject=" + sub;
      },
      badgeClass(st) {
        if (st === "new") return "ui-badge ui-badge-warn";
        if (st === "archived") return "ui-badge ui-badge-muted";
        return "ui-badge ui-badge-ok";
      }
    }'
  >
    <aside class="msg-list-pane ui-card">
      <div class="msg-search">
        <label class="sr-only" for="msg-search">Rechercher</label>
        <input
          id="msg-search"
          class="ui-input"
          type="search"
          placeholder="Rechercher nom, e-mail, sujet…"
          x-model="q"
        >
      </div>
      <div class="msg-list" role="listbox" aria-label="Liste des messages">
        <template x-for="m in filtered" :key="m.id">
          <button
            type="button"
            class="msg-row"
            role="option"
            :aria-selected="selectedId === m.id"
            :class="{ 'is-active': selectedId === m.id, 'is-unread': m.status === 'new' }"
            @click="select(m.id)"
          >
            <div class="msg-row-top">
              <span class="msg-row-name" x-text="m.name"></span>
              <span class="msg-row-date" x-text="m.created_label"></span>
            </div>
            <div class="msg-row-subject" x-text="m.subject || '(Sans objet)'"></div>
            <div class="msg-row-preview" x-text="m.preview"></div>
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
              <h2 class="msg-detail-subject" x-text="selected.subject || '(Sans objet)'"></h2>
              <span :class="badgeClass(selected.status)" x-text="selected.status_label"></span>
            </div>
            <div class="msg-detail-meta">
              <div>
                <div class="msg-detail-from" x-text="selected.name"></div>
                <div class="msg-detail-contacts">
                  <a :href="'mailto:' + selected.email" x-text="selected.email"></a>
                  <template x-if="selected.phone">
                    <span> · <a :href="'tel:' + selected.phone" x-text="selected.phone"></a></span>
                  </template>
                </div>
              </div>
              <div class="msg-detail-when" x-text="selected.created_label"></div>
            </div>
          </header>

          <div class="msg-detail-body" x-text="selected.body"></div>

          <div class="msg-detail-actions">
            <a class="ui-btn" :href="mailto(selected)">Répondre par e-mail</a>

            <form method="post" action="<?= e(admin_url('messages/sauver')) ?>" class="msg-quick-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" :value="selected.id">
              <input type="hidden" name="filter_status" value="<?= e($filterKey) ?>">
              <input type="hidden" name="status" value="read">
              <button class="ui-btn-secondary" type="submit" x-show="selected.status === 'new'">Marquer lu</button>
            </form>

            <form method="post" action="<?= e(admin_url('messages/sauver')) ?>" class="msg-quick-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" :value="selected.id">
              <input type="hidden" name="filter_status" value="<?= e($filterKey) ?>">
              <input type="hidden" name="status" value="archived">
              <button class="ui-btn-secondary" type="submit" x-show="selected.status !== 'archived'">Archiver</button>
            </form>

            <form method="post" action="<?= e(admin_url('messages/sauver')) ?>" class="msg-quick-form">
              <?= csrf_field() ?>
              <input type="hidden" name="id" :value="selected.id">
              <input type="hidden" name="filter_status" value="<?= e($filterKey) ?>">
              <input type="hidden" name="status" value="new">
              <button class="ui-btn-ghost" type="submit" x-show="selected.status !== 'new'">Remettre en nouveau</button>
            </form>
          </div>
        </div>
      </template>
    </section>
  </div>
<?php endif; ?>
