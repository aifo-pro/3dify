import Sortable from 'sortablejs';
import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';

// ─── Defaults ────────────────────────────────────────────────────────────────

function defaultsFor(type) {
    const d = {
        heading:       { level: 2, title_uk: '', title_en: '', anchor: '' },
        paragraph:     { text_uk: '', text_en: '' },
        image:         { path: '', alt_uk: '', alt_en: '', caption_uk: '', caption_en: '' },
        image_text:    { path: '', title_uk: '', title_en: '', text_uk: '', text_en: '', image_position: 'left' },
        quote:         { text_uk: '', text_en: '' },
        list:          { title_uk: '', title_en: '', style: 'bullets', items_uk: [''], items_en: [''] },
        table:         { title_uk: '', title_en: '', headers: [''], rows: [['']] },
        tips:          { title_uk: '', title_en: '', icon: '', items_uk: [''], items_en: [''] },
        warning:       { title_uk: '', title_en: '', text_uk: '', text_en: '', tone: 'amber' },
        steps:         { title_uk: '', title_en: '', steps: [{ title_uk: '', title_en: '', text_uk: '', text_en: '' }] },
        product_cards: { title_uk: '', title_en: '', body_uk: '', body_en: '', href: '' },
        related_models:{ title_uk: '', title_en: '', body_uk: '', body_en: '', href: '' },
        faq:           { items: [{ question_uk: '', answer_uk: '', question_en: '', answer_en: '' }] },
        cta:           { title_uk: '', title_en: '', text_uk: '', text_en: '', button_text_uk: '', button_text_en: '', button_url: '' },
        divider:       {},
        gallery:       { title_uk: '', title_en: '', style: 'grid', images: [] },
        code:          { language: 'plaintext', code: '', caption: '' },
        filament_card: { name_uk: '', name_en: '', brand: '', material: 'PLA', temp_nozzle: '', temp_bed: '', color: '', price: '', href: '' },
        printer_card:  { name_uk: '', name_en: '', brand: '', build_volume: '', tech: 'FDM', price: '', href: '' },
        material_card: { name_uk: '', name_en: '', brand: '', type: '', pros_uk: [''], pros_en: [''], href: '' },
        subscribe_box: { title_uk: '', title_en: '', text_uk: '', text_en: '' },
        spacer:        { size: 'md' },
    };
    return JSON.parse(JSON.stringify(d[type] || {}));
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function ensurePairedLocaleLists(data, type) {
    if (!['list', 'tips', 'material_card'].includes(type)) return;
    const ukKey = 'items_uk', enKey = 'items_en';
    let a = Array.isArray(data[ukKey]) ? [...data[ukKey]] : [];
    let b = Array.isArray(data[enKey]) ? [...data[enKey]] : [];
    const n = Math.max(a.length, b.length, 1);
    while (a.length < n) a.push('');
    while (b.length < n) b.push('');
    data[ukKey] = a;
    data[enKey] = b;
}

function genKey() {
    return `k_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`;
}

function normalizeRow(raw) {
    const type = raw.type || 'paragraph';
    const data = { ...defaultsFor(type), ...(raw.data && typeof raw.data === 'object' ? raw.data : {}) };
    ensurePairedLocaleLists(data, type);
    return {
        _key: raw.id != null ? `db_${raw.id}` : genKey(),
        serverId: raw.id ?? null,
        type,
        is_active: raw.is_active !== false,
        data,
    };
}

// ─── TipTap rich-text instances ──────────────────────────────────────────────

const tiptapInstances = new Map();

function makeTipTapConfig(el, onUpdate) {
    return new Editor({
        element: el,
        extensions: [
            StarterKit,
            Link.configure({ openOnClick: false }),
            Image,
        ],
        content: el.dataset.content || '',
        editorProps: {
            attributes: {
                class: 'tiptap-content min-h-[120px] rounded-xl border border-white/10 bg-zinc-950/80 px-4 py-3 text-sm text-zinc-100 focus:outline-none focus:ring-1 focus:ring-emerald-400/40',
            },
        },
        onUpdate({ editor }) {
            onUpdate(editor.getHTML());
        },
    });
}

function destroyTiptap(key, suffix) {
    const id = `${key}_${suffix}`;
    const inst = tiptapInstances.get(id);
    if (inst) { inst.destroy(); tiptapInstances.delete(id); }
}

function initTiptapForBlock(block) {
    ['uk', 'en'].forEach((lang) => {
        const el = document.getElementById(`tiptap_${block._key}_${lang}`);
        if (!el || tiptapInstances.has(`${block._key}_${lang}`)) return;
        el.dataset.content = block.data[`text_${lang}`] || '';
        const inst = makeTipTapConfig(el, (html) => {
            block.data[`text_${lang}`] = html;
        });
        tiptapInstances.set(`${block._key}_${lang}`, inst);
    });
}

// ─── Alpine component ─────────────────────────────────────────────────────────

export function blogPostBlocksEditor() {
    const cfg = typeof window !== 'undefined' ? window.__blogPostBlocksCfg || {} : {};

    return {
        blocks: [],
        showPicker: false,
        uploadUrl: cfg.uploadUrl || '',
        csrf:      cfg.csrf || '',
        _sortable: null,

        init() {
            const rows = Array.isArray(cfg.initialBlocks) ? cfg.initialBlocks : [];
            this.blocks = rows.map(normalizeRow);
            this.$nextTick(() => {
                this._initSortable();
                this._initAllTiptap();
            });
        },

        // ── Sortable ───────────────────────────────────────────────────────
        _initSortable() {
            const list = this.$el.querySelector('[data-sortable-blocks]');
            if (!list || this._sortable) return;
            this._sortable = Sortable.create(list, {
                handle: '[data-drag-handle]',
                animation: 200,
                ghostClass: 'opacity-30',
                chosenClass: 'ring-2 ring-emerald-400/50',
                onEnd: (evt) => {
                    const { oldIndex, newIndex } = evt;
                    if (oldIndex === newIndex) return;
                    const moved = this.blocks.splice(oldIndex, 1)[0];
                    this.blocks.splice(newIndex, 0, moved);
                },
            });
        },

        // ── TipTap ─────────────────────────────────────────────────────────
        _initAllTiptap() {
            this.blocks.forEach((b) => {
                if (b.type === 'paragraph') {
                    this.$nextTick(() => initTiptapForBlock(b));
                }
            });
        },

        _initTiptapForNewBlock(block) {
            if (block.type !== 'paragraph') return;
            this.$nextTick(() => initTiptapForBlock(block));
        },

        // ── Blocks CRUD ────────────────────────────────────────────────────
        addBlock(type) {
            const data = defaultsFor(type);
            ensurePairedLocaleLists(data, type);
            const block = { _key: genKey(), serverId: null, type, is_active: true, data };
            this.blocks.push(block);
            this.showPicker = false;
            this._initTiptapForNewBlock(block);
        },

        removeBlock(i) {
            const b = this.blocks[i];
            if (b.type === 'paragraph') {
                destroyTiptap(b._key, 'uk');
                destroyTiptap(b._key, 'en');
            }
            this.blocks.splice(i, 1);
        },

        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.blocks.length) return;
            [this.blocks[i], this.blocks[j]] = [this.blocks[j], this.blocks[i]];
        },

        duplicateBlock(i) {
            const src = this.blocks[i];
            const copy = { ...src, _key: genKey(), serverId: null, data: JSON.parse(JSON.stringify(src.data)) };
            this.blocks.splice(i + 1, 0, copy);
            this._initTiptapForNewBlock(copy);
        },

        toggleActive(i) {
            this.blocks[i].is_active = !this.blocks[i].is_active;
        },

        // ── List / Tips ────────────────────────────────────────────────────
        listAddPairedRow(block) {
            if (!Array.isArray(block.data.items_uk)) block.data.items_uk = [];
            if (!Array.isArray(block.data.items_en)) block.data.items_en = [];
            block.data.items_uk.push('');
            block.data.items_en.push('');
        },

        listRemovePairedRow(block, idx) {
            if (!Array.isArray(block.data.items_uk)) block.data.items_uk = [];
            if (!Array.isArray(block.data.items_en)) block.data.items_en = [];
            block.data.items_uk.splice(idx, 1);
            block.data.items_en.splice(idx, 1);
            if (!block.data.items_uk.length) { block.data.items_uk.push(''); block.data.items_en.push(''); }
        },

        // ── FAQ ───────────────────────────────────────────────────────────
        faqAdd(block) {
            if (!Array.isArray(block.data.items)) block.data.items = [];
            block.data.items.push({ question_uk: '', answer_uk: '', question_en: '', answer_en: '' });
        },
        faqRemove(block, idx) {
            block.data.items.splice(idx, 1);
            if (!block.data.items.length) this.faqAdd(block);
        },

        // ── Steps ─────────────────────────────────────────────────────────
        stepAdd(block) {
            if (!Array.isArray(block.data.steps)) block.data.steps = [];
            block.data.steps.push({ title_uk: '', title_en: '', text_uk: '', text_en: '' });
        },
        stepRemove(block, idx) {
            block.data.steps.splice(idx, 1);
            if (!block.data.steps.length) this.stepAdd(block);
        },

        // ── Table ─────────────────────────────────────────────────────────
        tableAddRow(block) {
            const cols = Math.max(1, (block.data.headers || []).length);
            if (!Array.isArray(block.data.rows)) block.data.rows = [];
            block.data.rows.push(Array.from({ length: cols }, () => ''));
        },
        tableAddCol(block) {
            if (!Array.isArray(block.data.headers)) block.data.headers = [''];
            block.data.headers.push('');
            if (!Array.isArray(block.data.rows)) block.data.rows = [];
            block.data.rows.forEach((row) => { if (Array.isArray(row)) row.push(''); });
        },

        // ── Gallery ───────────────────────────────────────────────────────
        galleryRemoveImage(block, idx) {
            block.data.images.splice(idx, 1);
        },

        async galleryUpload(block, event) {
            const files = [...(event.target.files || [])];
            if (!files.length || !this.uploadUrl) return;
            for (const file of files) {
                const fd = new FormData();
                fd.append('image', file);
                const res = await fetch(this.uploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: fd,
                    credentials: 'same-origin',
                });
                const json = await res.json().catch(() => ({}));
                if (res.ok && json.path) {
                    if (!Array.isArray(block.data.images)) block.data.images = [];
                    block.data.images.push({ path: json.path, alt_uk: '', alt_en: '' });
                }
            }
            event.target.value = '';
        },

        // ── Image upload ──────────────────────────────────────────────────
        async uploadForBlock(block, event) {
            const file = event.target.files?.[0];
            if (!file || !this.uploadUrl) return;
            const fd = new FormData();
            fd.append('image', file);
            const res = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                body: fd,
                credentials: 'same-origin',
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.path) { window.alert(json.message || 'Upload failed'); return; }
            block.data.path = json.path;
            event.target.value = '';
        },

        // ── Submit ────────────────────────────────────────────────────────
        prepareBlocksSubmit() {
            // flush TipTap HTML into data before serialisation
            tiptapInstances.forEach((inst, key) => {
                const [blockKey, lang] = key.split('_uk').length > 1
                    ? [key.replace(/_uk$/, ''), 'uk']
                    : [key.replace(/_en$/, ''), 'en'];
                const block = this.blocks.find((b) => b._key === blockKey);
                if (block && block.type === 'paragraph') {
                    block.data[`text_${lang}`] = inst.getHTML();
                }
            });

            const hidden = document.getElementById('blocks_json');
            if (!hidden) return;
            hidden.value = JSON.stringify(
                this.blocks.map((b) => ({ type: b.type, is_active: b.is_active, data: b.data }))
            );
        },

        typeLabel(type) {
            const labels = cfg.typeLabels || {};
            return labels[type] || type;
        },
    };
}
