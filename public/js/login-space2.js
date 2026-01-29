(function(){
    // Wait/poll for Three.js to be available (some hosts defer or block CDN).
    const MAX_WAIT = 3000; // ms
    const POLL = 75; // ms
    let waited = 0;

    function tryInit(){
        if (window.THREE) return initScene();
        waited += POLL;
        if (waited < MAX_WAIT) {
            setTimeout(tryInit, POLL);
        } else {
            console.warn('login-space2: THREE not found after waiting ' + MAX_WAIT + 'ms');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInit);
    } else {
        tryInit();
    }

    function initScene(){
        const overlay = document.getElementById('intro-text') || document.getElementById('space-bg');
        const matrixContainer = document.getElementById('matrix-container');

        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        renderer.setSize(window.innerWidth, window.innerHeight);
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.domElement.style.position = 'fixed';
        renderer.domElement.style.top = '0';
        renderer.domElement.style.left = '0';
        renderer.domElement.style.width = '100%';
        renderer.domElement.style.height = '100%';
        // place behind UI but above plain background
        renderer.domElement.style.zIndex = '0';
        renderer.domElement.style.pointerEvents = 'none';
        renderer.domElement.id = 'routina-space-canvas';
        document.body.appendChild(renderer.domElement);

        try { document.body.style.background = 'transparent'; } catch(e){}
        if (matrixContainer) {
            // ensure terminal sits above the canvas
            try { matrixContainer.style.zIndex = '12'; matrixContainer.style.position = matrixContainer.style.position || 'relative'; } catch(e){}
        }

        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(60, window.innerWidth/window.innerHeight, 0.1, 1000);
        camera.position.z = 40;

        // stars
        const starsGeo = new THREE.BufferGeometry();
        const starCount = 800;
        const positions = new Float32Array(starCount * 3);
        for (let i=0;i<starCount;i++){
            positions[i*3] = (Math.random()-0.5) * 800;
            positions[i*3+1] = (Math.random()-0.5) * 600;
            positions[i*3+2] = (Math.random()-0.0) * -800;
        }
        starsGeo.setAttribute('position', new THREE.BufferAttribute(positions,3));
        const starsMat = new THREE.PointsMaterial({ color: 0xffffff, size: 0.8, transparent: true, opacity: 0.9 });
        const stars = new THREE.Points(starsGeo, starsMat);
        scene.add(stars);

        // floating nebula sphere
        const nebGeo = new THREE.SphereGeometry(30, 32, 32);
        const nebMat = new THREE.ShaderMaterial({
            transparent: true,
            uniforms: {uTime:{value:0}},
            vertexShader: 'varying vec2 vUv; void main(){ vUv = uv; gl_Position = projectionMatrix * modelViewMatrix * vec4(position,1.0); }',
            fragmentShader: 'uniform float uTime; varying vec2 vUv; void main(){ float a = 0.5 + 0.5*sin(uTime+vUv.x*10.0); gl_FragColor = vec4(0.2,0.05,0.35, 0.18*a); }'
        });
        const neb = new THREE.Mesh(nebGeo, nebMat);
        neb.position.set(60, -10, -120);
        scene.add(neb);

        // cube pod (matrix box)
        const cubeMat = new THREE.MeshStandardMaterial({ color:0x00ff66, emissive:0x003300, roughness:0.4, metalness:0.3 });
        const cube = new THREE.Mesh(new THREE.BoxGeometry(8,8,8), cubeMat);
        cube.position.set(0,0,-200);
        scene.add(cube);

        const light = new THREE.PointLight(0x66ffcc, 1.2);
        light.position.set(0,30,50);
        scene.add(light);

        let start = null;
        let launched = false;

        function animate(t){
            requestAnimationFrame(animate);
            const time = (t||0) * 0.001;
            neb.material.uniforms.uTime.value = time;
            // stars slight move
            const pos = stars.geometry.attributes.position.array;
            for (let i=0;i<starCount;i++){
                pos[i*3+2] += 0.2;
                if (pos[i*3+2] > 50) pos[i*3+2] = -800;
            }
            stars.geometry.attributes.position.needsUpdate = true;

            if (launched) {
                const p = Math.min((time - start)/3.8, 1);
                cube.position.z = -200 + 170 * p;
                cube.scale.setScalar(0.3 + 0.7 * p);
                if (p>=1) {
                    launched = false; // stop animating launch
                    if (matrixContainer) matrixContainer.classList.add('launched');
                }
            }

            cube.rotation.x += 0.002;
            cube.rotation.y += 0.003;

            renderer.render(scene, camera);
        }
        animate();

        function begin(){
            if (start) return;
            start = performance.now()*0.001;
            launched = true;
            start = performance.now()*0.001;
        }

        overlay?.addEventListener('click', function(){ begin(); });
        window.addEventListener('resize', function(){
            camera.aspect = window.innerWidth/window.innerHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(window.innerWidth, window.innerHeight);
        });
    }
})();