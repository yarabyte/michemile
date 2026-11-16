/* Admin UX helpers — rich text, media picker, slug auto (CSP-safe, no CDN) */
(function () {
  'use strict';

  function csrfToken() {
    var el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
  }

  function adminApi(path) {
    var base = document.body.getAttribute('data-admin-base') || '';
    return base.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
  }

  /* ---------- Slugify ---------- */
  function slugify(text) {
    var map = {
      à: 'a', á: 'a', â: 'a', ä: 'a', ã: 'a', å: 'a', æ: 'ae',
      ç: 'c', è: 'e', é: 'e', ê: 'e', ë: 'e', ì: 'i', í: 'i', î: 'i', ï: 'i',
      ñ: 'n', ò: 'o', ó: 'o', ô: 'o', ö: 'o', õ: 'o', ø: 'o', œ: 'oe',
      ù: 'u', ú: 'u', û: 'u', ü: 'u', ý: 'y', ÿ: 'y', ß: 'ss',
    };
    return String(text || '')
      .toLowerCase()
      .split('')
      .map(function (ch) { return map[ch] || ch; })
      .join('')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'fiche';
  }

  function bindAutoSlug(form) {
    var nameInput = form.querySelector('[data-slug-source]');
    var slugInput = form.querySelector('[data-slug-target]');
    if (!nameInput || !slugInput) return;
    var locked = slugInput.getAttribute('data-slug-locked') === '1' || !!slugInput.value;
    nameInput.addEventListener('input', function () {
      if (locked && slugInput.value) return;
      slugInput.value = slugify(nameInput.value);
    });
    slugInput.addEventListener('input', function () {
      locked = true;
      slugInput.setAttribute('data-slug-locked', '1');
    });
  }

  /* ---------- Rich text (TinyMCE) ---------- */
  function initRichtexts() {
    if (!window.tinymce) {
      console.warn('TinyMCE non chargé');
      return;
    }
    var nodes = document.querySelectorAll('textarea[data-richtext]');
    if (!nodes.length) return;

    tinymce.remove('textarea[data-richtext]');
    tinymce.init({
      selector: 'textarea[data-richtext]',
      license_key: 'gpl',
      base_url: '/assets/vendor/tinymce',
      suffix: '.min',
      language: 'fr_FR',
      language_url: '/assets/vendor/tinymce/langs/fr_FR.js',
      height: 340,
      menubar: 'edit insert format',
      plugins: 'lists link image table autoresize code',
      toolbar:
        'undo redo | blocks | bold italic underline strikethrough | ' +
        'alignleft aligncenter alignright | bullist numlist outdent indent | ' +
        'link image table | removeformat',
      block_formats: 'Paragraphe=p; Titre=h2; Sous-titre=h3',
      branding: false,
      promotion: false,
      convert_urls: false,
      relative_urls: false,
      remove_script_host: false,
      paste_data_images: false,
      content_style:
        'body{font-family:Figtree,Helvetica,Arial,sans-serif;font-size:15px;line-height:1.55;color:#1c1917}' +
        'h2{font-size:1.25rem;color:#1f4d3a;margin:1rem 0 .5rem}' +
        'h3{font-size:1.1rem;color:#1f4d3a;margin:.85rem 0 .4rem}' +
        'img{max-width:100%;height:auto;border-radius:6px}',
      file_picker_types: 'image',
      file_picker_callback: function (callback) {
        openMediaPicker({
          accept: 'image',
          onSelect: function (item) {
            callback(item.public_path, { alt: item.alt_fr || '', title: item.original_name || '' });
          },
        });
      },
      setup: function (editor) {
        editor.on('change keyup setcontent', function () {
          editor.save();
        });
      },
    });

    document.querySelectorAll('form').forEach(function (form) {
      if (form.dataset.tinymceBound) return;
      form.dataset.tinymceBound = '1';
      form.addEventListener('submit', function () {
        if (window.tinymce) tinymce.triggerSave();
      });
    });
  }

  /* ---------- Media picker ---------- */
  var pickerState = {
    open: false,
    accept: 'image',
    items: [],
    loading: false,
    error: '',
    onSelect: null,
  };

  function openMediaPicker(opts) {
    opts = opts || {};
    pickerState.accept = opts.accept || 'image';
    pickerState.onSelect = opts.onSelect || null;
    pickerState.open = true;
    pickerState.error = '';
    window.dispatchEvent(new CustomEvent('admin-media-open', { detail: { accept: pickerState.accept } }));
  }

  window.AdminMedia = {
    open: openMediaPicker,
    pickInto: function (hiddenId, previewId, pathHiddenId, accept) {
      openMediaPicker({
        accept: accept || 'image',
        onSelect: function (item) {
          var idEl = document.getElementById(hiddenId);
          var pathEl = pathHiddenId ? document.getElementById(pathHiddenId) : null;
          var preview = previewId ? document.getElementById(previewId) : null;
          if (idEl) idEl.value = item.id;
          if (pathEl) pathEl.value = item.public_path;
          if (preview) {
            preview.innerHTML = '';
            if (String(item.mime || '').indexOf('image/') === 0) {
              var img = document.createElement('img');
              img.src = item.public_path;
              img.alt = item.alt_fr || '';
              img.className = 'media-preview-img';
              preview.appendChild(img);
            } else {
              var span = document.createElement('span');
              span.className = 'text-xs text-muted px-2 text-center';
              span.textContent = item.original_name || 'Fichier';
              preview.appendChild(span);
            }
          }
        },
      });
    },
  };

  document.addEventListener('alpine:init', function () {
    Alpine.data('adminMediaModal', function () {
      return {
        open: false,
        accept: 'image',
        items: [],
        loading: false,
        error: '',
        uploading: false,
        init: function () {
          var self = this;
          window.addEventListener('admin-media-open', function (e) {
            self.accept = (e.detail && e.detail.accept) || 'image';
            self.open = true;
            self.load();
          });
        },
        close: function () {
          this.open = false;
          pickerState.onSelect = null;
        },
        load: function () {
          var self = this;
          self.loading = true;
          self.error = '';
          fetch(adminApi('api/medias') + '?accept=' + encodeURIComponent(self.accept), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
          })
            .then(function (r) { return r.json(); })
            .then(function (data) {
              self.items = data.items || [];
              self.loading = false;
            })
            .catch(function () {
              self.error = 'Impossible de charger les médias.';
              self.loading = false;
            });
        },
        select: function (item) {
          if (typeof pickerState.onSelect === 'function') {
            pickerState.onSelect(item);
          }
          this.close();
        },
        upload: function (event) {
          var self = this;
          var file = event.target.files && event.target.files[0];
          if (!file) return;
          var fd = new FormData();
          fd.append('file', file);
          fd.append('_csrf', csrfToken());
          self.uploading = true;
          self.error = '';
          fetch(adminApi('api/medias/upload'), {
            method: 'POST',
            body: fd,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          })
            .then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
            .then(function (res) {
              self.uploading = false;
              event.target.value = '';
              if (!res.ok) {
                self.error = (res.data && res.data.error) || 'Échec de l’envoi.';
                return;
              }
              self.select(res.data.item);
            })
            .catch(function () {
              self.uploading = false;
              self.error = 'Échec de l’envoi.';
            });
        },
      };
    });

    Alpine.data('galleryField', function (initial, max) {
      var limit = typeof max === 'number' && max > 0 ? max : 0;
      return {
        items: Array.isArray(initial) ? initial.slice(0, limit || undefined) : [],
        max: limit,
        addFromPicker: function () {
          var self = this;
          if (self.max && self.items.length >= self.max) {
            window.alert('Maximum ' + self.max + ' images.');
            return;
          }
          openMediaPicker({
            accept: 'image',
            onSelect: function (item) {
              if (self.max && self.items.length >= self.max) return;
              if (self.items.indexOf(item.public_path) === -1) {
                self.items.push(item.public_path);
              }
            },
          });
        },
        remove: function (i) {
          this.items.splice(i, 1);
        },
        move: function (i, dir) {
          var j = i + dir;
          if (j < 0 || j >= this.items.length) return;
          var tmp = this.items[i];
          this.items[i] = this.items[j];
          this.items[j] = tmp;
        },
      };
    });

    Alpine.data('lineRepeater', function (initial) {
      return {
        rows: (Array.isArray(initial) && initial.length) ? initial.slice() : [''],
        add: function () { this.rows.push(''); },
        remove: function (i) {
          this.rows.splice(i, 1);
          if (!this.rows.length) this.rows.push('');
        },
      };
    });

    Alpine.data('pairRepeater', function (initial) {
      return {
        rows: (Array.isArray(initial) && initial.length)
          ? initial.map(function (r) {
              return { left: r[0] || r.left || '', right: r[1] || r.right || '' };
            })
          : [{ left: '', right: '' }],
        add: function () { this.rows.push({ left: '', right: '' }); },
        remove: function (i) {
          this.rows.splice(i, 1);
          if (!this.rows.length) this.rows.push({ left: '', right: '' });
        },
      };
    });

    Alpine.data('equipRepeater', function (initial, paths) {
      return {
        rows: (Array.isArray(initial) && initial.length)
          ? initial.map(function (r) {
              if (Array.isArray(r)) return { icon: r[0] || 'check', label: r[1] || '' };
              return { icon: r.icon || 'check', label: r.label || '' };
            })
          : [{ icon: 'check', label: '' }],
        paths: paths && typeof paths === 'object' ? paths : {},
        iconSvg: function (key) {
          var d = this.paths[key] || this.paths.check || '';
          return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6">' +
            '<path d="' + d + '" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
        },
        add: function () { this.rows.push({ icon: 'check', label: '' }); },
        remove: function (i) {
          this.rows.splice(i, 1);
          if (!this.rows.length) this.rows.push({ icon: 'check', label: '' });
        },
      };
    });

    Alpine.data('iconRows', function (initial, paths, blank) {
      return {
        rows: (Array.isArray(initial) && initial.length) ? initial.slice() : [blank || { icon: 'check' }],
        paths: paths && typeof paths === 'object' ? paths : {},
        blank: blank || { icon: 'check' },
        iconSvg: function (key) {
          var d = this.paths[key] || this.paths.check || '';
          return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.6">' +
            '<path d="' + d + '" stroke-linecap="round" stroke-linejoin="round"></path></svg>';
        },
        add: function () {
          this.rows.push(Object.assign({}, this.blank));
        },
        remove: function (i) {
          this.rows.splice(i, 1);
          if (!this.rows.length) this.add();
        },
      };
    });

    Alpine.data('blocksRepeater', function (initial) {
      return {
        blocks: (Array.isArray(initial) && initial.length)
          ? initial.map(function (b) {
              return {
                title: b.title || '',
                note: b.note || '',
                items: Array.isArray(b.items) ? b.items.join('\n') : '',
              };
            })
          : [{ title: '', note: '', items: '' }],
        add: function () { this.blocks.push({ title: '', note: '', items: '' }); },
        remove: function (i) {
          this.blocks.splice(i, 1);
          if (!this.blocks.length) this.blocks.push({ title: '', note: '', items: '' });
        },
      };
    });

    Alpine.data('langTabs', function (initial) {
      return { tab: initial || 'fr' };
    });
  });

  /* ---------- Reorder list (drag + arrows) ---------- */
  function initReorderLists() {
    document.querySelectorAll('[data-reorder-list]').forEach(initReorderList);
  }

  function initReorderList(tbody) {
    var url = tbody.getAttribute('data-reorder-url');
    if (!url) return;
    var statusEl = document.querySelector('[data-reorder-status]');
    var dragRow = null;
    var saving = false;

    function rows() {
      return Array.prototype.slice.call(tbody.querySelectorAll('tr[data-id]'));
    }

    function refreshRanks() {
      rows().forEach(function (row, i) {
        var rank = row.querySelector('[data-reorder-rank]');
        if (rank) rank.textContent = String(i + 1);
      });
    }

    function setStatus(msg, isError) {
      if (!statusEl) return;
      statusEl.textContent = msg || '';
      statusEl.classList.toggle('text-danger', !!isError);
      statusEl.classList.toggle('text-ok', !isError && !!msg);
    }

    function saveOrder() {
      if (saving) return;
      saving = true;
      setStatus('Enregistrement de l’ordre…', false);
      var ids = rows().map(function (row) {
        return parseInt(row.getAttribute('data-id'), 10);
      }).filter(function (id) { return id > 0; });

      fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({ ids: ids, _csrf: csrfToken() }),
        credentials: 'same-origin',
      })
        .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
        .then(function (result) {
          if (!result.ok || !result.data || !result.data.ok) {
            throw new Error((result.data && result.data.error) || 'Échec de l’enregistrement');
          }
          setStatus('Ordre enregistré.', false);
          window.setTimeout(function () { setStatus('', false); }, 2000);
        })
        .catch(function (err) {
          setStatus(err.message || 'Erreur réseau', true);
        })
        .finally(function () {
          saving = false;
        });
    }

    function moveRow(row, dir) {
      if (dir < 0 && row.previousElementSibling) {
        tbody.insertBefore(row, row.previousElementSibling);
      } else if (dir > 0 && row.nextElementSibling) {
        tbody.insertBefore(row.nextElementSibling, row);
      } else {
        return;
      }
      refreshRanks();
      saveOrder();
    }

    tbody.addEventListener('click', function (e) {
      var up = e.target.closest('[data-reorder-up]');
      var down = e.target.closest('[data-reorder-down]');
      if (!up && !down) return;
      e.preventDefault();
      var row = e.target.closest('tr[data-id]');
      if (!row) return;
      moveRow(row, up ? -1 : 1);
    });

    tbody.addEventListener('dragstart', function (e) {
      var row = e.target.closest('tr[data-id]');
      if (!row || e.target.closest('a, button:not(.reorder-handle)')) {
        e.preventDefault();
        return;
      }
      dragRow = row;
      row.classList.add('is-dragging');
      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', row.getAttribute('data-id') || '');
      }
    });

    tbody.addEventListener('dragend', function () {
      if (dragRow) dragRow.classList.remove('is-dragging');
      rows().forEach(function (row) { row.classList.remove('is-drag-over'); });
      dragRow = null;
    });

    tbody.addEventListener('dragover', function (e) {
      e.preventDefault();
      var row = e.target.closest('tr[data-id]');
      if (!row || !dragRow || row === dragRow) return;
      rows().forEach(function (r) { r.classList.remove('is-drag-over'); });
      row.classList.add('is-drag-over');
      var rect = row.getBoundingClientRect();
      var before = (e.clientY - rect.top) < rect.height / 2;
      if (before) {
        tbody.insertBefore(dragRow, row);
      } else {
        tbody.insertBefore(dragRow, row.nextElementSibling);
      }
    });

    tbody.addEventListener('drop', function (e) {
      e.preventDefault();
      rows().forEach(function (row) { row.classList.remove('is-drag-over'); });
      if (dragRow) dragRow.classList.remove('is-dragging');
      dragRow = null;
      refreshRanks();
      saveOrder();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[data-auto-slug]').forEach(bindAutoSlug);
    initRichtexts(document);
    initReorderLists();
  });
})();
