<div
  x-data="adminMediaModal"
  x-show="open"
  x-cloak
  class="media-modal-root"
  style="display:none"
>
  <div class="media-modal-backdrop" @click="close()"></div>
  <div class="media-modal" role="dialog" aria-modal="true" aria-label="Choisir un média">
    <div class="flex items-center justify-between gap-3 border-b border-line px-4 py-3">
      <h2 class="m-0 text-base font-semibold text-forest">Médias</h2>
      <button type="button" class="ui-btn-secondary" @click="close()">Fermer</button>
    </div>
    <div class="border-b border-line px-4 py-3">
      <label class="ui-label">
        <span>Envoyer un fichier</span>
        <span class="ui-label-hint">JPEG, PNG, WebP ou PDF — max 8 Mo</span>
        <input class="ui-input" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" @change="upload($event)">
      </label>
      <p class="mt-2 mb-0 text-sm text-muted" x-show="uploading">Envoi en cours…</p>
      <p class="mt-2 mb-0 text-sm text-danger" x-show="error" x-text="error"></p>
    </div>
    <div class="media-modal-grid p-4">
      <p class="text-sm text-muted" x-show="loading">Chargement…</p>
      <p class="text-sm text-muted" x-show="!loading && !items.length">Aucun fichier pour l’instant.</p>
      <template x-for="item in items" :key="item.id">
        <button type="button" class="media-modal-item" @click="select(item)">
          <template x-if="(item.mime || '').startsWith('image/')">
            <img :src="item.public_path" :alt="item.alt_fr || ''" loading="lazy">
          </template>
          <template x-if="!(item.mime || '').startsWith('image/')">
            <div class="media-modal-pdf">PDF</div>
          </template>
          <span class="media-modal-name" x-text="item.original_name || item.public_path"></span>
        </button>
      </template>
    </div>
  </div>
</div>
