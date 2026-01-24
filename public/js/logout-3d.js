// logout-3d.js
document.addEventListener('DOMContentLoaded', () => {
    
    // ----- THREE.JS SETUP -----
    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(0x000000, 0.0008);

    const camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 2000);
    camera.position.z = 1000;

    const renderer = new THREE.WebGLRenderer({ 
        canvas: document.getElementById('canvas-3d'), 
        alpha: true 
    });
    renderer.setPixelRatio(window.devicePixelRatio);
    renderer.setSize(window.innerWidth, window.innerHeight);

    // ----- STARS -----
    const starGeometry = new THREE.BufferGeometry();
    const starCount = 6000; // Same density as login
    const posArray = new Float32Array(starCount * 3);

    for(let i = 0; i < starCount * 3; i++) {
        posArray[i] = (Math.random() - 0.5) * 3000;
    }

    starGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));

    // Slight blue tint for "cold space" feel on logout
    const starMaterial = new THREE.PointsMaterial({
        color: 0xaaccff, 
        size: 2,
        transparent: true,
        opacity: 0.6
    });

    const starMesh = new THREE.Points(starGeometry, starMaterial);
    scene.add(starMesh);

    // ----- INTERACTION (LOOK AROUND) -----
    let mouseX = 0;
    let mouseY = 0;
    let targetX = 0;
    let targetY = 0;
    
    const windowHalfX = window.innerWidth / 2;
    const windowHalfY = window.innerHeight / 2;

    document.addEventListener('mousemove', (event) => {
        mouseX = (event.clientX - windowHalfX);
        mouseY = (event.clientY - windowHalfY);
    });

    // ----- ANIMATION LOOP -----
    const animate = () => {
        requestAnimationFrame(animate);

        // Drift backwards (stars move away)
        // Or stars move towards camera slowly? 
        // Let's make them move slowly towards camera like drifting through debris
        const positions = starMesh.geometry.attributes.position.array;
        
        for (let i = 2; i < starCount * 3; i += 3) {
            positions[i] += 0.2; // Very slow drift

            if (positions[i] > 1000) {
                positions[i] = -2000;
            }
        }
        starMesh.geometry.attributes.position.needsUpdate = true;

        // Mouse Look Parallax
        targetX = mouseX * 0.001;
        targetY = mouseY * 0.001;

        starMesh.rotation.y += 0.05 * (targetX - starMesh.rotation.y);
        starMesh.rotation.x += 0.05 * (targetY - starMesh.rotation.x);
        
        // Constant slow rotation
        starMesh.rotation.z += 0.0001;

        renderer.render(scene, camera);
    };

    animate();

    // ----- RESIZE HANDLER -----
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });
});
