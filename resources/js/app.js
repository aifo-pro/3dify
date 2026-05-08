import './bootstrap';

import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

Alpine.plugin(persist);
window.Alpine = Alpine;

Alpine.start();

const viewer = document.getElementById('viewer');

const IMAGE_EXTS = ['gif', 'png', 'jpg', 'jpeg', 'webp', 'avif', 'svg'];
function getExtension(url) {
    if (!url) return '';
    try {
        const path = new URL(url, window.location.origin).pathname;
        const m = path.match(/\.([a-z0-9]+)$/i);
        return m ? m[1].toLowerCase() : '';
    } catch (_) {
        return '';
    }
}

if (viewer) {
    const modelUrl = viewer.dataset.modelUrl;
    const ext = getExtension(modelUrl);

    if (modelUrl && IMAGE_EXTS.includes(ext)) {
        viewer.classList.add('flex', 'items-center', 'justify-center', 'bg-zinc-950');
        const img = document.createElement('img');
        img.src = modelUrl;
        img.alt = '';
        img.loading = 'lazy';
        img.className = 'h-full w-full object-contain';
        viewer.appendChild(img);
    } else {
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

        window.addEventListener('resize', () => {
            camera.aspect = viewer.clientWidth / viewer.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(viewer.clientWidth, viewer.clientHeight);
        });

        render();
    });
    }
}
