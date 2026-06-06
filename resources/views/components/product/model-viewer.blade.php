@props([
    'viewer' => ['available' => false, 'format' => null, 'src' => null, 'reason' => 'none'],
    'title' => '',
    'fill' => false,
    'poster' => null, // image shown in-place if WebGL/loading fails (out-of-the-box fallback)
])

@php
    $vid = 'mv-'.\Illuminate\Support\Str::random(8);
    $available = (bool) ($viewer['available'] ?? false);
    $format = strtolower((string) ($viewer['format'] ?? ''));
    $src = $viewer['src'] ?? null;
    $reason = $viewer['reason'] ?? 'none';

    // `fill` mode is used when the viewer lives inside the gallery frame, so it
    // fills the parent instead of imposing its own card aspect ratio / border.
    $rootClass = $fill
        ? 'group relative h-full w-full overflow-hidden'
        : 'group relative overflow-hidden rounded-3xl border border-white/10 shadow-2xl shadow-black/30';
    $rootStyle = $fill
        ? 'height:100%;width:100%;background:#05070a;'
        : 'aspect-ratio: 4/3; max-height: 620px; background:#05070a;';
@endphp

@if($available && $src)
    <div
        id="{{ $vid }}"
        data-model-viewer-root
        data-src="{{ $src }}"
        data-format="{{ $format }}"
        class="{{ $rootClass }}"
        style="{{ $rootStyle }}"
    >
        {{-- WebGL canvas host --}}
        <div data-canvas class="absolute inset-0"></div>

        {{-- Static fallback image shown in-place when WebGL is unavailable or the
             model fails to load — keeps the slide useful with zero configuration. --}}
        @if($poster)
            <img data-poster src="{{ $poster }}" alt="{{ $title }}" hidden loading="lazy"
                 class="absolute inset-0 z-20 h-full w-full object-contain" style="background:#05070a;">
        @endif

        {{-- Format badge --}}
        <div class="pointer-events-none absolute left-4 top-4 z-10 flex items-center gap-1.5 rounded-full border border-emerald-300/30 bg-emerald-400/[0.12] px-3 py-1 text-[11px] font-black uppercase tracking-wider text-emerald-200 backdrop-blur">
            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            <span>{{ strtoupper($format) }} · 3D</span>
        </div>

        {{-- Toolbar --}}
        <div class="absolute right-4 top-4 z-10 flex items-center gap-2">
            <button type="button" data-reset title="{{ __('Скинути камеру') }}" aria-label="{{ __('Скинути камеру') }}"
                class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-zinc-900/70 text-zinc-300 backdrop-blur transition hover:border-emerald-300/40 hover:text-emerald-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
            </button>
            <button type="button" data-fullscreen title="{{ __('На весь екран') }}" aria-label="{{ __('На весь екран') }}"
                class="grid h-9 w-9 place-items-center rounded-full border border-white/10 bg-zinc-900/70 text-zinc-300 backdrop-blur transition hover:border-emerald-300/40 hover:text-emerald-300">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            </button>
        </div>

        {{-- Drag hint --}}
        <div class="pointer-events-none absolute bottom-4 left-1/2 z-10 -translate-x-1/2 rounded-full border border-white/10 bg-zinc-900/60 px-3 py-1 text-[11px] text-zinc-400 opacity-0 backdrop-blur transition group-hover:opacity-100">
            {{ __('Обертайте мишкою · колесо — зум · права кнопка — перемістити') }}
        </div>

        {{-- Loading indicator --}}
        <div data-loading class="absolute inset-0 z-20 grid place-items-center bg-[#05070a]">
            <div class="flex flex-col items-center gap-3">
                <svg class="h-8 w-8 animate-spin text-emerald-400" viewBox="0 0 24 24" fill="none"><circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-90" d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                <p class="text-xs font-medium text-zinc-400">{{ __('Завантаження 3D-моделі') }} <span data-progress></span></p>
            </div>
        </div>

        {{-- Error state --}}
        <div data-error hidden class="absolute inset-0 z-20 grid place-items-center bg-[#05070a] px-6 text-center">
            <div class="max-w-xs">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl border border-amber-300/25 bg-amber-400/[0.08] text-amber-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <p class="mt-3 text-sm font-bold text-white">{{ __('Не вдалося завантажити 3D-перегляд') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-zinc-400">{{ __('Спробуйте оновити сторінку. Ви все одно можете завантажити файл моделі.') }}</p>
            </div>
        </div>

        {{-- WebGL unsupported fallback --}}
        <div data-nowebgl hidden class="absolute inset-0 z-20 grid place-items-center bg-[#05070a] px-6 text-center">
            <div class="max-w-xs">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl border border-white/10 bg-white/[0.04] text-zinc-300">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                </div>
                <p class="mt-3 text-sm font-bold text-white">{{ __('3D-перегляд недоступний у вашому браузері') }}</p>
                <p class="mt-1 text-xs leading-relaxed text-zinc-400">{{ __('Ваш браузер не підтримує WebGL. Спробуйте інший браузер або оновіть поточний.') }}</p>
                <p data-nowebgl-reason class="mt-2 break-words text-[10px] leading-relaxed text-zinc-600"></p>
            </div>
        </div>
    </div>

    @verbatim
    <script type="module">
    (() => {
        // r160: WebGLRenderer automatically falls back to WebGL1 when WebGL2 is
        // unavailable (r163+ is WebGL2-only) — widest browser coverage.
        const VER = '0.160.0';
        const BASE = 'https://unpkg.com/three@' + VER;

        // Try hard to obtain a WebGL renderer across browsers/GPUs. A non-AA
        // retry lets software renderers (SwiftShader / llvmpipe) succeed where
        // AA contexts fail. The last error is logged for diagnostics.
        function createRenderer(THREE) {
            const attempts = [
                { antialias: true, failIfMajorPerformanceCaveat: false },
                { antialias: false, failIfMajorPerformanceCaveat: false },
                { antialias: false, failIfMajorPerformanceCaveat: false, powerPreference: 'low-power' },
                { antialias: false, failIfMajorPerformanceCaveat: false, forceWebGL1: true },
            ];
            let lastErr = null;
            for (const opts of attempts) {
                try {
                    const r = new THREE.WebGLRenderer(opts);
                    if (r) return r;
                } catch (e) { lastErr = e; }
            }
            if (lastErr) console.error('[3Dify viewer] WebGL renderer creation failed:', lastErr);
            createRenderer.lastError = lastErr;
            return null;
        }

        function defaultMaterial(THREE) {
            return new THREE.MeshStandardMaterial({ color: 0xc7d2da, metalness: 0.08, roughness: 0.62 });
        }

        function applyDefaultMaterial(THREE, root) {
            const mat = defaultMaterial(THREE);
            root.traverse((c) => {
                if (!c.isMesh) return;
                const empty = !c.material || (Array.isArray(c.material) && c.material.length === 0);
                if (empty) c.material = mat;
                if (c.geometry && !c.geometry.attributes.normal) c.geometry.computeVertexNormals();
            });
        }

        async function loadObject(THREE, format, url, onProgress) {
            if (format === 'glb' || format === 'gltf') {
                const { GLTFLoader } = await import(BASE + '/examples/jsm/loaders/GLTFLoader.js');
                const gltf = await new Promise((res, rej) => new GLTFLoader().load(url, res, onProgress, rej));
                return gltf.scene;
            }
            if (format === 'stl') {
                const { STLLoader } = await import(BASE + '/examples/jsm/loaders/STLLoader.js');
                const geo = await new Promise((res, rej) => new STLLoader().load(url, res, onProgress, rej));
                geo.computeVertexNormals();
                return new THREE.Mesh(geo, defaultMaterial(THREE));
            }
            if (format === 'obj') {
                const { OBJLoader } = await import(BASE + '/examples/jsm/loaders/OBJLoader.js');
                const obj = await new Promise((res, rej) => new OBJLoader().load(url, res, onProgress, rej));
                applyDefaultMaterial(THREE, obj);
                return obj;
            }
            if (format === '3mf') {
                const { ThreeMFLoader } = await import(BASE + '/examples/jsm/loaders/3MFLoader.js');
                const obj = await new Promise((res, rej) => new ThreeMFLoader().load(url, res, onProgress, rej));
                applyDefaultMaterial(THREE, obj);
                return obj;
            }
            throw new Error('Unsupported format: ' + format);
        }

        async function init(root) {
            root.setAttribute('data-mv-init', '1');
            const host = root.querySelector('[data-canvas]');
            const loadingEl = root.querySelector('[data-loading]');
            const errorEl = root.querySelector('[data-error]');
            const noWebgl = root.querySelector('[data-nowebgl]');
            const format = (root.getAttribute('data-format') || '').toLowerCase();
            const src = root.getAttribute('data-src');

            const posterEl = root.querySelector('[data-poster]');
            const hasPoster = () => posterEl && posterEl.getAttribute('src');

            const showError = () => {
                if (loadingEl) loadingEl.hidden = true;
                // Prefer the product image over a technical error message.
                if (hasPoster()) { posterEl.hidden = false; }
                else if (errorEl) { errorEl.hidden = false; }
            };
            const showNoWebgl = (err) => {
                if (loadingEl) loadingEl.hidden = true;
                // Out-of-the-box fallback: show the product image right here.
                if (hasPoster()) {
                    posterEl.hidden = false;
                } else if (noWebgl) {
                    noWebgl.hidden = false;
                    const reason = noWebgl.querySelector('[data-nowebgl-reason]');
                    if (reason && err) reason.textContent = String(err && err.message ? err.message : err);
                }
                // Also tell the gallery to drop the 3D slide entirely so the user
                // lands on the real photo gallery (belt-and-suspenders).
                root.dispatchEvent(new CustomEvent('viewer-webgl-unavailable', { bubbles: true }));
            };

            let THREE, OrbitControls;
            try {
                THREE = await import(BASE + '/build/three.module.js');
                ({ OrbitControls } = await import(BASE + '/examples/jsm/controls/OrbitControls.js'));
            } catch (e) { showError(); return; }

            // The actual renderer creation is the definitive WebGL test.
            const renderer = createRenderer(THREE);
            if (!renderer) { showNoWebgl(createRenderer.lastError); return; }

            let disposed = false, raf = 0, ro = null;

            const aspect = () => {
                const w = host.clientWidth || 1, h = host.clientHeight || 1;
                return w / h;
            };

            const scene = new THREE.Scene();
            scene.background = new THREE.Color(0x05070a);

            const camera = new THREE.PerspectiveCamera(45, aspect(), 0.01, 5000);

            renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
            renderer.setSize(host.clientWidth || 1, host.clientHeight || 1, false);
            const canvas = renderer.domElement;
            canvas.style.width = '100%';
            canvas.style.height = '100%';
            canvas.style.display = 'block';
            canvas.style.touchAction = 'none';
            host.appendChild(canvas);

            scene.add(new THREE.HemisphereLight(0xffffff, 0x202028, 2.0));
            const key = new THREE.DirectionalLight(0xffffff, 2.2); key.position.set(5, 8, 6); scene.add(key);
            const fill = new THREE.DirectionalLight(0xffffff, 0.8); fill.position.set(-6, 2, -4); scene.add(fill);

            const controls = new OrbitControls(camera, canvas);
            controls.enableDamping = true;
            controls.dampingFactor = 0.08;
            controls.minDistance = 0.02;

            const initialCam = new THREE.Vector3();
            const initialTarget = new THREE.Vector3();

            function frameObject(object) {
                let box = new THREE.Box3().setFromObject(object);
                const size = box.getSize(new THREE.Vector3());
                const center = box.getCenter(new THREE.Vector3());
                object.position.x -= center.x;
                object.position.y -= center.y;
                object.position.z -= center.z;
                const maxDim = Math.max(size.x, size.y, size.z) || 1;
                object.scale.setScalar(2 / maxDim);

                box = new THREE.Box3().setFromObject(object);
                const sphere = box.getBoundingSphere(new THREE.Sphere());
                const r = sphere.radius || 1;
                const fov = camera.fov * Math.PI / 180;
                const dist = (r / Math.sin(fov / 2)) * 1.25;
                camera.position.set(dist * 0.7, dist * 0.55, dist * 0.95);
                camera.near = Math.max(dist / 200, 0.01);
                camera.far = dist * 200;
                camera.updateProjectionMatrix();
                controls.target.copy(sphere.center);
                controls.update();

                const grid = new THREE.GridHelper(Math.ceil(r * 6) || 6, 24, 0x1f2937, 0x14161c);
                grid.position.y = box.min.y;
                if (grid.material) {
                    const gm = Array.isArray(grid.material) ? grid.material : [grid.material];
                    gm.forEach((m) => { m.transparent = true; m.opacity = 0.5; });
                }
                scene.add(grid);

                initialCam.copy(camera.position);
                initialTarget.copy(controls.target);
            }

            function animate() {
                if (disposed) return;
                raf = requestAnimationFrame(animate);
                // Skip GPU work while the slide is hidden (e.g. another gallery
                // image is shown) — the element collapses to zero size.
                if (host.clientWidth < 8 || host.clientHeight < 8) return;
                controls.update();
                renderer.render(scene, camera);
            }

            function resize() {
                if (disposed) return;
                const w = host.clientWidth, h = host.clientHeight;
                if (w < 8 || h < 8) return;
                camera.aspect = w / h;
                camera.updateProjectionMatrix();
                renderer.setSize(w, h, false);
            }

            function cleanup() {
                if (disposed) return;
                disposed = true;
                cancelAnimationFrame(raf);
                try { controls.dispose(); } catch (e) {}
                scene.traverse((o) => {
                    if (o.geometry) o.geometry.dispose();
                    if (o.material) {
                        const mats = Array.isArray(o.material) ? o.material : [o.material];
                        mats.forEach((m) => {
                            for (const k in m) { const v = m[k]; if (v && v.isTexture) v.dispose(); }
                            if (m.dispose) m.dispose();
                        });
                    }
                });
                try { renderer.dispose(); } catch (e) {}
                if (canvas.parentNode) canvas.parentNode.removeChild(canvas);
                if (ro) ro.disconnect();
                window.removeEventListener('pagehide', cleanup);
                document.removeEventListener('fullscreenchange', onFsChange);
            }

            function onFsChange() { setTimeout(resize, 60); }

            ro = new ResizeObserver(resize);
            ro.observe(host);
            window.addEventListener('pagehide', cleanup, { once: true });
            document.addEventListener('fullscreenchange', onFsChange);

            const resetBtn = root.querySelector('[data-reset]');
            const fsBtn = root.querySelector('[data-fullscreen]');
            if (resetBtn) resetBtn.addEventListener('click', () => {
                camera.position.copy(initialCam);
                controls.target.copy(initialTarget);
                camera.updateProjectionMatrix();
                controls.update();
            });
            if (fsBtn) fsBtn.addEventListener('click', () => {
                if (document.fullscreenElement) {
                    if (document.exitFullscreen) document.exitFullscreen();
                } else if (root.requestFullscreen) {
                    root.requestFullscreen();
                }
            });

            const onProgress = (xhr) => {
                if (loadingEl && xhr && xhr.lengthComputable) {
                    const pct = Math.round((xhr.loaded / xhr.total) * 100);
                    const t = loadingEl.querySelector('[data-progress]');
                    if (t) t.textContent = pct + '%';
                }
            };

            try {
                const object = await loadObject(THREE, format, src, onProgress);
                if (disposed) return;
                scene.add(object);
                frameObject(object);
                if (loadingEl) loadingEl.hidden = true;
                animate();
            } catch (e) {
                showError();
            }
        }

        function boot() {
            document.querySelectorAll('[data-model-viewer-root]:not([data-mv-init])').forEach((root) => {
                const io = new IntersectionObserver((entries, obs) => {
                    entries.forEach((en) => {
                        if (en.isIntersecting) { obs.disconnect(); init(root); }
                    });
                }, { rootMargin: '300px' });
                io.observe(root);
            });
        }

        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot);
        else boot();
    })();
    </script>
    @endverbatim
@else
    {{-- Preview unavailable — informative block (only rendered for zip / gated cases) --}}
    <div class="relative grid place-items-center overflow-hidden rounded-3xl border border-white/10 px-6 text-center"
         style="aspect-ratio: 4/3; max-height: 620px; background:#05070a;">
        <div class="max-w-sm">
            <div class="mx-auto grid h-14 w-14 place-items-center rounded-2xl border border-white/10 bg-white/[0.04] text-zinc-400">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            </div>
            <p class="mt-4 text-base font-bold text-white">{{ __('3D-перегляд недоступний') }}</p>
            <p class="mt-1.5 text-sm leading-relaxed text-zinc-400">
                @if($reason === 'zip')
                    {{ __('Модель завантажена як ZIP-архів — його не можна показати у 3D напряму. Завантажте архів, щоб отримати файли STL/OBJ/GLB всередині.') }}
                @elseif($reason === 'unauthorized')
                    {{ __('Інтерактивний 3D-перегляд цієї моделі стане доступним після придбання.') }}
                @else
                    {{ __('Для цієї моделі немає файлу, який можна показати в 3D-перегляді.') }}
                @endif
            </p>
        </div>
    </div>
@endif
