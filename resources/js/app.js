import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

Alpine.plugin(persist);
window.Alpine = Alpine;

Alpine.data('blogBlocksEditor', () => {
    const cfg = window.__blogBlocksEditorPayload ?? {};

    return {
    doc: structuredClone(cfg.initial && typeof cfg.initial === 'object' ? cfg.initial : { version: 1, blocks: [] }),
    csrf: cfg.csrf || '',
    uploadUrl: cfg.uploadUrl || '',

    uid() {
        return `blk_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 9)}`;
    },

    blockLabel(type) {
        const labels = cfg.labels || {};
        return labels[type] || type;
    },

    submitForm(event) {
        this.syncRichFromTiny();
        const form = event.target;
        const hidden = form.querySelector('input[name="content_blocks"]');
        if (hidden) {
            hidden.value = JSON.stringify(this.doc);
        }
        if (window.tinymce) {
            window.tinymce.triggerSave();
        }
    },

    syncRichFromTiny() {
        if (!window.tinymce) {
            return;
        }
        this.doc.blocks.forEach((b) => {
            if (b.type !== 'richtext') {
                return;
            }
            ['uk', 'en'].forEach((loc) => {
                const id = `mce_${b.id}_${loc}`;
                const ed = window.tinymce.get(id);
                if (ed && b[loc]) {
                    b[loc].html = ed.getContent();
                }
            });
        });
    },

    addBlock(type) {
        const id = this.uid();
        let block;
        switch (type) {
            case 'heading':
                block = { id, type, uk: { text: '', level: 2 }, en: { text: '', level: 2 } };
                break;
            case 'richtext':
                block = { id, type, uk: { html: '' }, en: { html: '' } };
                break;
            case 'image':
                block = { id, type, path: '', uk: { alt: '', caption: '' }, en: { alt: '', caption: '' } };
                break;
            case 'quote':
                block = { id, type, uk: { text: '' }, en: { text: '' } };
                break;
            case 'divider':
                block = { id, type, uk: {}, en: {} };
                break;
            default:
                return;
        }
        this.doc.blocks.push(block);
        this.$nextTick(() => this.refreshTinyMce());
    },

    removeBlock(index) {
        this.syncRichFromTiny();
        const b = this.doc.blocks[index];
        if (b?.type === 'richtext' && window.tinymce) {
            ['uk', 'en'].forEach((loc) => {
                const ed = window.tinymce.get(`mce_${b.id}_${loc}`);
                if (ed) {
                    ed.remove();
                }
            });
        }
        this.doc.blocks.splice(index, 1);
        this.$nextTick(() => this.refreshTinyMce());
    },

    moveBlock(index, dir) {
        this.syncRichFromTiny();
        const j = index + dir;
        if (j < 0 || j >= this.doc.blocks.length) {
            return;
        }
        const arr = this.doc.blocks;
        [arr[index], arr[j]] = [arr[j], arr[index]];
        this.$nextTick(() => this.refreshTinyMce());
    },

    async uploadForBlock(index, event) {
        const file = event.target.files?.[0];
        if (!file || !this.uploadUrl) {
            return;
        }
        const formData = new FormData();
        formData.append('image', file);
        const res = await fetch(this.uploadUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
            body: formData,
            credentials: 'same-origin',
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.path) {
            window.alert(typeof json.message === 'string' ? json.message : 'Upload failed');
            return;
        }
        this.doc.blocks[index].path = json.path;
        event.target.value = '';
    },

    imagePreviewUrl(block) {
        if (!block.path) {
            return '';
        }
        const p = String(block.path).replace(/^\/+/, '');
        return `/storage/${p}`;
    },

    refreshTinyMce() {
        if (!window.tinymce || !cfg.tinyDefaults) {
            return;
        }
        this.syncRichFromTiny();
        document.querySelectorAll('textarea.blog-block-mce').forEach((ta) => {
            const ed = window.tinymce.get(ta.id);
            if (ed) {
                ed.remove();
            }
        });
        const { uploadUrl, csrf } = this;
        document.querySelectorAll('textarea.blog-block-mce').forEach((ta) => {
            const id = ta.id;
            if (!id) {
                return;
            }
            window.tinymce.init({
                ...cfg.tinyDefaults,
                selector: `#${id}`,
                height: 280,
                images_upload_handler: (blobInfo) =>
                    new Promise((resolve, reject) => {
                        const form = new FormData();
                        form.append('image', blobInfo.blob(), blobInfo.filename());
                        fetch(uploadUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf || '', Accept: 'application/json' },
                            body: form,
                            credentials: 'same-origin',
                        })
                            .then((r) => r.json())
                            .then((j) => (j.url ? resolve(j.url) : reject(new Error('Upload failed'))))
                            .catch(() => reject(new Error('Upload failed')));
                    }),
            });
        });
    },

    init() {
        this.$nextTick(() => this.refreshTinyMce());
    },
};
});

Alpine.data('productPricing', () => ({
    licenseType: 'personal',
    personalPrice: 0,
    commercialPrice: 0,
    currency: 'UAH',
    accountBalance: 0,
    useBalance: false,
    balanceAmount: 0,
    locale: 'uk-UA',
    freeLabel: 'Безкоштовно',

    get currentPrice() {
        return this.licenseType === 'commercial' ? this.commercialPrice : this.personalPrice;
    },
    get maxBalanceAmount() {
        return Math.max(0, Math.min(this.accountBalance, this.currentPrice));
    },
    get payableAmount() {
        const amount = this.useBalance
            ? Math.min(Math.max(Number(this.balanceAmount || 0), 0), this.maxBalanceAmount)
            : 0;
        return Math.max(0, this.currentPrice - amount);
    },
    money(value) {
        return new Intl.NumberFormat(this.locale, {
            style: 'currency',
            currency: this.currency,
            minimumFractionDigits: 2,
        }).format(value);
    },
    get displayPrice() {
        if (this.currentPrice <= 0) return this.freeLabel;
        return this.money(this.currentPrice);
    },
}));

Alpine.start();

function initModelViewers() {
    const IMAGE_EXTS = ['gif', 'png', 'jpg', 'jpeg', 'webp', 'avif', 'svg'];

    document.querySelectorAll('[data-model-viewer]').forEach((viewer) => {
        if (viewer.dataset.viewerInitialized === '1') return;
        if (viewer.clientWidth < 20 || viewer.clientHeight < 20) return;

        const modelUrl = viewer.dataset.modelUrl;
        const ext = (() => {
            if (!modelUrl) return '';
            try {
                const m = new URL(modelUrl, location.origin).pathname.match(/\.([a-z0-9]+)$/i);
                return m ? m[1].toLowerCase() : '';
            } catch { return ''; }
        })();

        viewer.dataset.viewerInitialized = '1';

        if (modelUrl && IMAGE_EXTS.includes(ext)) {
            viewer.classList.add('flex', 'items-center', 'justify-center', 'bg-zinc-950');
            const img = document.createElement('img');
            img.src = modelUrl;
            img.alt = '';
            img.loading = 'lazy';
            img.className = 'h-full w-full object-contain';
            viewer.appendChild(img);
            return;
        }

        import('https://unpkg.com/three@0.165.0/build/three.module.js').then(async (THREE) => {
            const { OrbitControls } = await import('https://unpkg.com/three@0.165.0/examples/jsm/controls/OrbitControls.js');
            const { GLTFLoader } = await import('https://unpkg.com/three@0.165.0/examples/jsm/loaders/GLTFLoader.js');
            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0x09090b);

            const camera = new THREE.PerspectiveCamera(45, viewer.clientWidth / viewer.clientHeight, 0.1, 1000);
            camera.position.set(2.4, 1.8, 3.2);

            const renderer = new THREE.WebGLRenderer({ antialias: true });
            renderer.setSize(viewer.clientWidth, viewer.clientHeight);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            viewer.appendChild(renderer.domElement);

            scene.add(new THREE.HemisphereLight(0xffffff, 0x222233, 2));
            const key = new THREE.DirectionalLight(0xffffff, 2.5);
            key.position.set(4, 5, 3);
            scene.add(key);

            const controls = new OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;

            if (modelUrl) {
                new GLTFLoader().load(modelUrl, (gltf) => {
                    scene.add(gltf.scene);
                }, undefined, () => addFallbackMesh());
            } else {
                addFallbackMesh();
            }

            function addFallbackMesh() {
                const geometry = new THREE.TorusKnotGeometry(0.75, 0.22, 120, 18);
                const material = new THREE.MeshStandardMaterial({ color: 0x34d399, metalness: 0.35, roughness: 0.45 });
                scene.add(new THREE.Mesh(geometry, material));
            }

            function render() {
                controls.update();
                renderer.render(scene, camera);
                requestAnimationFrame(render);
            }

            function resize() {
                if (viewer.clientWidth < 20 || viewer.clientHeight < 20) return;
                camera.aspect = viewer.clientWidth / viewer.clientHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(viewer.clientWidth, viewer.clientHeight);
            }

            window.addEventListener('resize', resize);
            window.addEventListener('init-model-viewers', resize);
            render();
        });
    });
}

window.addEventListener('init-model-viewers', () => requestAnimationFrame(initModelViewers));
requestIdleCallback(initModelViewers);
