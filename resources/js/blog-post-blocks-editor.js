function defaultsFor(type) {
    const d = {
        heading: { level: 2, title_uk: '', title_en: '', anchor: '' },
        paragraph: { text_uk: '', text_en: '' },
        image: { path: '', alt_uk: '', alt_en: '', caption_uk: '', caption_en: '' },
        image_text: { path: '', title_uk: '', title_en: '', text_uk: '', text_en: '', image_position: 'left' },
        quote: { text_uk: '', text_en: '' },
        list: { title_uk: '', title_en: '', style: 'bullets', items_uk: [''], items_en: [''] },
        table: { title_uk: '', title_en: '', headers: [''], rows: [['']] },
        tips: { title_uk: '', title_en: '', icon: '', items_uk: [''], items_en: [''] },
        warning: { title_uk: '', title_en: '', text_uk: '', text_en: '', tone: 'amber' },
        steps: { title_uk: '', title_en: '', steps: [{ title_uk: '', title_en: '', text_uk: '', text_en: '' }] },
        product_cards: { title_uk: '', title_en: '', body_uk: '', body_en: '', href: '' },
        related_models: { title_uk: '', title_en: '', body_uk: '', body_en: '', href: '' },
        faq: { items: [{ question_uk: '', answer_uk: '', question_en: '', answer_en: '' }] },
        cta: { title_uk: '', title_en: '', text_uk: '', text_en: '', button_text_uk: '', button_text_en: '', button_url: '' },
        divider: {},
    };
    return JSON.parse(JSON.stringify(d[type] || {}));
}

function ensurePairedLocaleLists(data, type) {
    if (type !== 'list' && type !== 'tips') {
        return;
    }
    let a = Array.isArray(data.items_uk) ? [...data.items_uk] : [];
    let b = Array.isArray(data.items_en) ? [...data.items_en] : [];
    const n = Math.max(a.length, b.length, 1);
    while (a.length < n) {
        a.push('');
    }
    while (b.length < n) {
        b.push('');
    }
    data.items_uk = a;
    data.items_en = b;
}

function normalizeRow(raw) {
    const type = raw.type || 'paragraph';
    const data = { ...defaultsFor(type), ...(raw.data && typeof raw.data === 'object' ? raw.data : {}) };
    ensurePairedLocaleLists(data, type);
    return {
        _key: raw.id != null ? `db_${raw.id}` : `k_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`,
        serverId: raw.id ?? null,
        type,
        is_active: raw.is_active !== false,
        data,
    };
}

export function blogPostBlocksEditor() {
    const cfg = typeof window !== 'undefined' ? window.__blogPostBlocksCfg || {} : {};

    return {
        blocks: [],
        showPicker: false,
        uploadUrl: cfg.uploadUrl || '',
        csrf: cfg.csrf || '',

        init() {
            const rows = Array.isArray(cfg.initialBlocks) ? cfg.initialBlocks : [];
            this.blocks = rows.map((r) => normalizeRow(r));
        },

        addBlock(type) {
            const data = defaultsFor(type);
            ensurePairedLocaleLists(data, type);
            this.blocks.push({
                _key: `k_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`,
                serverId: null,
                type,
                is_active: true,
                data,
            });
            this.showPicker = false;
        },

        removeBlock(i) {
            this.blocks.splice(i, 1);
        },

        move(i, dir) {
            const j = i + dir;
            if (j < 0 || j >= this.blocks.length) {
                return;
            }
            const a = this.blocks[i];
            this.blocks[i] = this.blocks[j];
            this.blocks[j] = a;
        },

        toggleActive(i) {
            this.blocks[i].is_active = !this.blocks[i].is_active;
        },

        listAddPairedRow(block) {
            if (!Array.isArray(block.data.items_uk)) {
                block.data.items_uk = [];
            }
            if (!Array.isArray(block.data.items_en)) {
                block.data.items_en = [];
            }
            block.data.items_uk.push('');
            block.data.items_en.push('');
        },

        listRemovePairedRow(block, idx) {
            if (!Array.isArray(block.data.items_uk)) {
                block.data.items_uk = [];
            }
            if (!Array.isArray(block.data.items_en)) {
                block.data.items_en = [];
            }
            block.data.items_uk.splice(idx, 1);
            block.data.items_en.splice(idx, 1);
            if (block.data.items_uk.length === 0) {
                block.data.items_uk.push('');
                block.data.items_en.push('');
            }
        },

        faqAdd(block) {
            if (!Array.isArray(block.data.items)) {
                block.data.items = [];
            }
            block.data.items.push({ question_uk: '', answer_uk: '', question_en: '', answer_en: '' });
        },

        faqRemove(block, idx) {
            block.data.items.splice(idx, 1);
            if (block.data.items.length === 0) {
                this.faqAdd(block);
            }
        },

        stepAdd(block) {
            if (!Array.isArray(block.data.steps)) {
                block.data.steps = [];
            }
            block.data.steps.push({ title_uk: '', title_en: '', text_uk: '', text_en: '' });
        },

        stepRemove(block, idx) {
            block.data.steps.splice(idx, 1);
            if (!block.data.steps.length) {
                this.stepAdd(block);
            }
        },

        tableAddRow(block) {
            const cols = Math.max(1, (block.data.headers || []).length || 1);
            if (!Array.isArray(block.data.rows)) {
                block.data.rows = [];
            }
            block.data.rows.push(Array.from({ length: cols }, () => ''));
        },

        tableAddCol(block) {
            if (!Array.isArray(block.data.headers)) {
                block.data.headers = [''];
            }
            block.data.headers.push('');
            if (!Array.isArray(block.data.rows)) {
                block.data.rows = [];
            }
            block.data.rows.forEach((row) => {
                if (Array.isArray(row)) {
                    row.push('');
                }
            });
        },

        async uploadForBlock(block, event) {
            const file = event.target.files?.[0];
            if (!file || !this.uploadUrl) {
                return;
            }
            const fd = new FormData();
            fd.append('image', file);
            const res = await fetch(this.uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                body: fd,
                credentials: 'same-origin',
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok || !json.path) {
                window.alert(json.message || 'Upload failed');
                return;
            }
            block.data.path = json.path;
            event.target.value = '';
        },

        prepareBlocksSubmit() {
            const hidden = document.getElementById('blocks_json');
            if (!hidden) {
                return;
            }
            const payload = this.blocks.map((b) => ({
                type: b.type,
                is_active: b.is_active,
                data: b.data,
            }));
            hidden.value = JSON.stringify(payload);
        },

        typeLabel(type) {
            const labels = cfg.typeLabels || {};
            return labels[type] || type;
        },
    };
}
