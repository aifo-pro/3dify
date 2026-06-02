import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';
import { blogPostBlocksEditor } from './blog-post-blocks-editor';

Alpine.plugin(persist);
window.Alpine = Alpine;

Alpine.data('blogPostBlocksEditor', blogPostBlocksEditor);

Alpine.data('customOrderDelivery', (initial) => ({
    carrier: initial.carrier || 'nova_poshta',
    cityName: initial.cityName || '',
    cityRef: initial.cityRef || '',
    cityQuery: initial.cityName || '',
    warehouseName: initial.warehouseName || '',
    warehouseRef: initial.warehouseRef || '',
    warehouseQuery: initial.warehouseName || '',
    citiesUrl: initial.citiesUrl,
    warehousesUrl: initial.warehousesUrl,
    cities: [],
    warehouses: [],
    loading: false,
    resetDelivery() {
        this.cityName = '';
        this.cityRef = '';
        this.cityQuery = '';
        this.warehouseName = '';
        this.warehouseRef = '';
        this.warehouseQuery = '';
        this.cities = [];
        this.warehouses = [];
    },
    async searchCities() {
        if (this.cityQuery.length < 2) {
            this.cities = [];
            return;
        }

        this.loading = true;
        const url = new URL(this.citiesUrl, window.location.origin);
        url.searchParams.set('carrier', this.carrier);
        url.searchParams.set('q', this.cityQuery);

        try {
            const response = await fetch(url);
            const payload = await response.json();
            this.cities = payload.items || [];
        } finally {
            this.loading = false;
        }
    },
    selectCity(city) {
        this.cityName = city.name;
        this.cityRef = city.ref;
        this.cityQuery = city.region ? `${city.name}, ${city.region}` : city.name;
        this.warehouseName = '';
        this.warehouseRef = '';
        this.warehouseQuery = '';
        this.cities = [];
        this.searchWarehouses();
    },
    async searchWarehouses() {
        if (!this.cityRef) {
            this.warehouses = [];
            return;
        }

        this.loading = true;
        const url = new URL(this.warehousesUrl, window.location.origin);
        url.searchParams.set('carrier', this.carrier);
        url.searchParams.set('city_ref', this.cityRef);
        if (this.warehouseQuery.length > 1) {
            url.searchParams.set('q', this.warehouseQuery);
        }

        try {
            const response = await fetch(url);
            const payload = await response.json();
            this.warehouses = payload.items || [];
        } finally {
            this.loading = false;
        }
    },
    selectWarehouse(warehouse) {
        this.warehouseName = warehouse.name;
        this.warehouseRef = warehouse.ref;
        this.warehouseQuery = warehouse.name;
        this.warehouses = [];
    },
}));

Alpine.data('customOrderChat', (initial) => ({
    messages: initial.messages || [],
    fetchUrl: initial.fetchUrl,
    sendUrl: initial.sendUrl,
    csrf: initial.csrf,
    placeholder: initial.placeholder || '',
    sending: false,
    polling: null,
    body: '',
    error: '',
    get lastId() {
        return this.messages.length ? Math.max(...this.messages.map((message) => Number(message.id || 0))) : 0;
    },
    start() {
        this.scrollToBottom();
        this.polling = window.setInterval(() => this.fetchNewMessages(), 3500);
        window.addEventListener('beforeunload', () => {
            if (this.polling) window.clearInterval(this.polling);
        });
    },
    async fetchNewMessages() {
        const url = new URL(this.fetchUrl, window.location.origin);
        url.searchParams.set('after', this.lastId);

        try {
            const response = await fetch(url, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) return;

            const payload = await response.json();
            this.addMessages(payload.messages || []);
        } catch {
            // Keep polling quiet; the user can still send via the normal form fallback.
        }
    },
    addMessages(items) {
        const known = new Set(this.messages.map((message) => Number(message.id)));
        const fresh = items.filter((message) => !known.has(Number(message.id)));

        if (!fresh.length) return;

        this.messages.push(...fresh);
        this.scrollToBottom();
    },
    async send(event) {
        this.error = '';
        const form = event.target;
        const formData = new FormData(form);
        const hasFiles = form.querySelector('input[type="file"]')?.files?.length > 0;

        if (!String(formData.get('body') || '').trim() && !hasFiles) {
            this.error = this.placeholder;
            return;
        }

        this.sending = true;

        try {
            const response = await fetch(this.sendUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                },
            });

            if (!response.ok) {
                const payload = await response.json().catch(() => ({}));
                this.error = payload.message || this.placeholder;
                return;
            }

            const payload = await response.json();
            this.addMessages([payload.message]);
            form.reset();
            this.body = '';
        } finally {
            this.sending = false;
        }
    },
    scrollToBottom() {
        this.$nextTick(() => {
            if (this.$refs.messages) {
                this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
            }
        });
    },
}));

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
