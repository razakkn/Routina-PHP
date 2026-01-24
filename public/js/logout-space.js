(function () {
    const container = document.getElementById('departure-scene');
    const message = document.getElementById('space-message');
    const skip = document.querySelector('[data-logout-skip]');
    let shown = false;
    let reducedMotion = false;

    if (!container) {
        revealMessage();
        return;
    }

    const prefersReducedMotion = typeof window.matchMedia === 'function'
        ? window.matchMedia('(prefers-reduced-motion: reduce)')
        : null;

    const honorReducedMotion = () => {
        if (shown) {
            return;
        }
        reducedMotion = true;
        shown = true;
        document.body.classList.add('space-logout-reduced');
        revealMessage();
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
    } catch (error) {
        enableFallback();
        return;
    }

    if (!renderer?.domElement) {
        enableFallback();
        return;
    }

    const scene = new THREE.Scene();
    scene.fog = new THREE.Fog(0x010312, 40, 380);

    const camera = new THREE.PerspectiveCamera(55, window.innerWidth / window.innerHeight, 0.1, 600);
    camera.position.set(0, 0, 26);

    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.setSize(window.innerWidth, window.innerHeight);
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    container.appendChild(renderer.domElement);

    const ambient = new THREE.AmbientLight(0x1e3d76, 0.9);
    scene.add(ambient);

    const forwardLight = new THREE.DirectionalLight(0x73a3ff, 1.25);
    forwardLight.position.set(-24, 18, 34);
    scene.add(forwardLight);

    const rearLight = new THREE.DirectionalLight(0x162657, 0.85);
    rearLight.position.set(16, -24, -20);
    scene.add(rearLight);

    const shipMaterial = new THREE.MeshStandardMaterial({
        color: 0x2f72ff,
        metalness: 0.55,
        roughness: 0.22,
        emissive: 0x071937,
        emissiveIntensity: 0.65,
        transparent: true,
        opacity: 0.95
    });

    const ship = new THREE.Mesh(new THREE.BoxGeometry(6.8, 6.8, 6.8, 5, 5, 5), shipMaterial);
    ship.position.set(0, -2, -24);
    ship.rotation.set(0.48, 0.3, 0.18);
    scene.add(ship);

    const shipRing = new THREE.Mesh(
        new THREE.TorusGeometry(10, 0.15, 8, 80),
        new THREE.MeshBasicMaterial({ color: 0x4f7bff, transparent: true, opacity: 0.35 })
    );
    shipRing.position.set(0, -2, -24);
    shipRing.rotation.x = Math.PI / 2.1;
    scene.add(shipRing);

    const trailMaterial = new THREE.MeshBasicMaterial({ color: 0x86b7ff, transparent: true, opacity: 0.28 });
    const trailGeometry = new THREE.CylinderGeometry(0.2, 2.6, 16, 32, 1, true);
    const shipTrail = new THREE.Mesh(trailGeometry, trailMaterial);
    shipTrail.position.set(0, -2, -32);
    shipTrail.rotation.x = Math.PI / 2;
    scene.add(shipTrail);

    const starCount = 1600;
    const starPositions = new Float32Array(starCount * 3);
    const starSpeeds = new Float32Array(starCount);

    for (let i = 0; i < starCount; i++) {
        const i3 = i * 3;
        starPositions[i3] = (Math.random() - 0.5) * 260;
        starPositions[i3 + 1] = (Math.random() - 0.5) * 260;
        starPositions[i3 + 2] = (Math.random() * 220) - 110;
        starSpeeds[i] = 0.35 + Math.random() * 0.55;
    }

    const starsGeometry = new THREE.BufferGeometry();
    starsGeometry.setAttribute('position', new THREE.BufferAttribute(starPositions, 3));

    const starsMaterial = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 0.75,
        sizeAttenuation: true,
        transparent: true,
        opacity: 0.82,
        depthWrite: false
    });

    const stars = new THREE.Points(starsGeometry, starsMaterial);
    scene.add(stars);

    let pointerX = 0;
    let pointerY = 0;
    let startTime = null;
    let lastFrame = null;

    const easing = (t) => 1 - Math.pow(1 - t, 4);

    function animate(time) {
        requestAnimationFrame(animate);

        if (reducedMotion) {
            return;
        }

        if (startTime === null) {
            startTime = time;
        }

        if (lastFrame === null) {
            lastFrame = time;
        }

        const frameDelta = time - lastFrame;
        lastFrame = time;
        const deltaSeconds = frameDelta / 1000;

        const progress = Math.min((time - startTime) / 5200, 1);
        const eased = easing(progress);

        const positions = starsGeometry.attributes.position.array;
        for (let i = 0; i < starCount; i++) {
            const base = i * 3 + 2;
            positions[base] += starSpeeds[i] * deltaSeconds * 26;
            if (positions[base] > 160) {
                positions[base] = -180;
                positions[base - 1] = (Math.random() - 0.5) * 260;
                positions[base - 2] = (Math.random() - 0.5) * 260;
            }
        }
        starsGeometry.attributes.position.needsUpdate = true;

        ship.position.z = THREE.MathUtils.lerp(-24, -260, eased);
        ship.position.y = THREE.MathUtils.lerp(-2, 4, eased);
        ship.rotation.x += 0.35 * deltaSeconds;
        ship.rotation.y += 0.48 * deltaSeconds;
        ship.rotation.z += 0.22 * deltaSeconds;

        shipRing.position.copy(ship.position);
        shipRing.scale.setScalar(THREE.MathUtils.lerp(1, 1.45, eased));
        shipRing.material.opacity = 0.35 * (1 - eased);

        shipTrail.position.z = ship.position.z - 16;
        shipTrail.scale.z = 1 + eased * 1.6;
        shipTrail.material.opacity = THREE.MathUtils.lerp(0.28, 0.08, eased);

        camera.position.x = THREE.MathUtils.lerp(camera.position.x, pointerX * 6, 0.02);
        camera.position.y = THREE.MathUtils.lerp(camera.position.y, pointerY * 4, 0.02);
        camera.lookAt(0, 0, -50);

        if (!shown && progress >= 1) {
            shown = true;
            revealMessage();
        }

        renderer.render(scene, camera);
    }

    requestAnimationFrame(animate);

    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });

    window.addEventListener('pointermove', (event) => {
        pointerX = (event.clientX / window.innerWidth - 0.5) * 2;
        pointerY = -(event.clientY / window.innerHeight - 0.5) * 2;
    });

    skip?.addEventListener('click', (event) => {
        event.preventDefault();
        shown = true;
        revealMessage();
    });

    function revealMessage() {
        if (document.body.classList.contains('space-logout-ready')) {
            return;
        }
        document.body.classList.add('space-logout-ready');
        message?.setAttribute('aria-hidden', 'false');
        const focusTarget = message?.querySelector('[data-logout-focus]');
        if (focusTarget instanceof HTMLElement) {
            setTimeout(() => focusTarget.focus(), 250);
        }
    }

    function enableFallback() {
        shown = true;
        document.body.classList.add('space-logout-no-webgl', 'space-logout-ready');
        message?.setAttribute('aria-hidden', 'false');
    }

    if (!renderer.capabilities.isWebGL2 && !renderer.capabilities.isWebGL1) {
        enableFallback();
    }
})();
