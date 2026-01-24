(function () {
    const container = document.getElementById('space-scene');
    const overlay = document.getElementById('space-overlay');
    const cta = document.querySelector('[data-space-begin]');
    const skip = document.querySelector('[data-space-skip]');
    const form = document.getElementById('space-form');

    if (!container) {
        return;
    }

    let engaged = false;
    let formReady = false;
    let startTime = null;
    let pointerX = 0;
    let pointerY = 0;
    let lastFrame = 0;
    let reducedMotion = false;

    const prefersReducedMotion = typeof window.matchMedia === 'function'
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null;

    const honorReducedMotion = () => {
        reducedMotion = true;
        document.body.classList.add('space-reduced-motion');
        skipAnimation();
    };

    if (prefersReducedMotion?.matches) {
        honorReducedMotion();
        return;
    }

    const reducedMotionListener = (event) => {
        if (event.matches) {
            honorReducedMotion();
        }
    };

    if (prefersReducedMotion) {
        if (typeof prefersReducedMotion.addEventListener === 'function') {
            prefersReducedMotion.addEventListener('change', reducedMotionListener);
        } else if (typeof prefersReducedMotion.addListener === 'function') {
            prefersReducedMotion.addListener(reducedMotionListener);
        }
    }

    const THREE = window.THREE;
    if (!THREE) {
        enableFallback();
        return;
    }

    let renderer;
    try {
        renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    } catch (err) {
        enableFallback();
        return;
    }

    if (!renderer?.domElement) {
        enableFallback();
        return;
    }

    const scene = new THREE.Scene();
    scene.fog = new THREE.Fog(0x020415, 55, 420);

    const camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.1, 600);
    camera.position.set(0, 0, 28);

    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    container.appendChild(renderer.domElement);

    const ambient = new THREE.AmbientLight(0x2f4e86, 0.9);
    scene.add(ambient);

    const keyLight = new THREE.DirectionalLight(0x7faeff, 1.35);
    keyLight.position.set(30, 60, 30);
    scene.add(keyLight);

    const rimLight = new THREE.DirectionalLight(0x1e2d88, 0.65);
    rimLight.position.set(-24, -32, -24);
    scene.add(rimLight);

    const cubeMaterial = new THREE.MeshStandardMaterial({
        color: 0x1f5dff,
        metalness: 0.45,
        roughness: 0.24,
        emissive: 0x061c3e,
        emissiveIntensity: 0.7,
        transparent: true,
        opacity: 0.96
    });

    const pod = new THREE.Mesh(new THREE.BoxGeometry(7, 7, 7, 5, 5, 5), cubeMaterial);
    pod.position.set(0, 0, -160);
    pod.rotation.set(0.62, 0.45, 0.15);
    pod.scale.setScalar(0.3);
    scene.add(pod);

    const frameMaterial = new THREE.MeshBasicMaterial({
        color: 0x244a9c,
        wireframe: true,
        transparent: true,
        opacity: 0.28
    });
    const frame = new THREE.Mesh(new THREE.BoxGeometry(7.6, 7.6, 7.6, 4, 4, 4), frameMaterial);
    frame.position.copy(pod.position);
    frame.rotation.copy(pod.rotation);
    frame.scale.copy(pod.scale).multiplyScalar(1.05);
    scene.add(frame);

    const starCount = 1800;
    const starPositions = new Float32Array(starCount * 3);
    const starSpeeds = new Float32Array(starCount);
    for (let i = 0; i < starCount; i++) {
        const i3 = i * 3;
        starPositions[i3] = (Math.random() - 0.5) * 240;
        starPositions[i3 + 1] = (Math.random() - 0.5) * 240;
        starPositions[i3 + 2] = -Math.random() * 420;
        starSpeeds[i] = 0.45 + Math.random() * 0.65;
    }
    const starsGeometry = new THREE.BufferGeometry();
    starsGeometry.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));
    const starsMaterial = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 0.75,
        sizeAttenuation: true,
        transparent: true,
        opacity: 0.88,
        depthWrite: false
    });
    const stars = new THREE.Points(starsGeometry, starsMaterial);
    scene.add(stars);

    const overlayPulse = new THREE.Points(
        new THREE.SphereGeometry(40, 32, 32),
        new THREE.PointsMaterial({ color: 0x4f7bff, size: 1.1, transparent: true, opacity: 0.08 })
    );
    overlayPulse.position.set(0, 0, -90);
    scene.add(overlayPulse);

    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    function animate(time) {
        requestAnimationFrame(animate);

        if (reducedMotion) {
            return;
        }

        const delta = time - (lastFrame || time);
        lastFrame = time;

        const positions = starsGeometry.attributes.position.array;
        for (let i = 0; i < starCount; i++) {
            const idx = i * 3 + 2;
            positions[idx] += starSpeeds[i] * (delta * 0.018);
            if (positions[idx] > 20) {
                positions[idx] = -420;
                positions[idx - 1] = (Math.random() - 0.5) * 240;
                positions[idx - 2] = (Math.random() - 0.5) * 240;
            }
        }
        starsGeometry.attributes.position.needsUpdate = true;

        pod.rotation.x += 0.00065 * delta;
        pod.rotation.y += 0.0008 * delta;
        pod.rotation.z += 0.00025 * delta;
        frame.rotation.copy(pod.rotation);

        camera.position.x = THREE.MathUtils.lerp(camera.position.x, pointerX * 8, 0.018);
        camera.position.y = THREE.MathUtils.lerp(camera.position.y, pointerY * 5, 0.018);
        camera.lookAt(0, 0, -30);

        if (engaged) {
            if (startTime === null) {
                startTime = time;
            }
            const progress = Math.min((time - startTime) / 4600, 1);
            const eased = easeOutCubic(progress);
            const zPos = THREE.MathUtils.lerp(-160, -18, eased);
            const scale = THREE.MathUtils.lerp(0.3, 1, eased);

            pod.position.z = zPos;
            frame.position.z = zPos;
            pod.scale.setScalar(scale);
            frame.scale.setScalar(scale * 1.03);

            if (progress >= 1 && !formReady) {
                formReady = true;
                document.body.classList.add('space-form-ready');
                form?.setAttribute('aria-hidden', 'false');
                const autoFocus = form?.querySelector('[data-space-autofocus]');
                if (autoFocus instanceof HTMLElement) {
                    setTimeout(() => autoFocus.focus(), 250);
                }
            }
        }

        renderer.render(scene, camera);
    }

    requestAnimationFrame(animate);

    function beginExperience() {
        if (engaged) {
            return;
        }
        engaged = true;
        document.body.classList.add('space-engaged');
        overlay?.setAttribute('aria-hidden', 'true');
        if (!formReady) {
            form?.setAttribute('aria-hidden', 'true');
        }
    }

    function skipAnimation() {
        engaged = true;
        formReady = true;
        document.body.classList.add('space-engaged', 'space-form-ready');
        overlay?.setAttribute('aria-hidden', 'true');
        form?.setAttribute('aria-hidden', 'false');
    }

    overlay?.addEventListener('click', beginExperience);
    overlay?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            beginExperience();
        }
    });

    cta?.addEventListener('click', (event) => {
        event.stopPropagation();
        beginExperience();
    });

    skip?.addEventListener('click', (event) => {
        event.preventDefault();
        skipAnimation();
    });

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });

    window.addEventListener('pointermove', (event) => {
        pointerX = (event.clientX / window.innerWidth - 0.5) * 2;
        pointerY = -(event.clientY / window.innerHeight - 0.5) * 2;
    });

    function enableFallback() {
        document.body.classList.add('space-no-webgl', 'space-form-ready');
        overlay?.setAttribute('aria-hidden', 'true');
        form?.setAttribute('aria-hidden', 'false');
    }

    if (!renderer.capabilities.isWebGL2 && !renderer.capabilities.isWebGL1) {
        enableFallback();
    }
})();
