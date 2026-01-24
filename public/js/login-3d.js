// login-3d.js
document.addEventListener('DOMContentLoaded', () => {
    // UI Elements
    const introText = document.getElementById('intro-text');
    const cubeWrapper = document.getElementById('cube-wrapper');
    const switchSignup = document.getElementById('switch-signup');
    const switchLogin = document.getElementById('switch-login');
    
    let isInitiated = false;

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
    const starCount = 6000;
    const posArray = new Float32Array(starCount * 3);

    for(let i = 0; i < starCount * 3; i++) {
        posArray[i] = (Math.random() - 0.5) * 3000; // Spread stars wide
    }

    starGeometry.setAttribute('position', new THREE.BufferAttribute(posArray, 3));

    const starMaterial = new THREE.PointsMaterial({
        color: 0xffffff,
        size: 2,
        transparent: true,
        opacity: 0.8
    });

    const starMesh = new THREE.Points(starGeometry, starMaterial);
    scene.add(starMesh);

    // ----- ANIMATION STATE -----
    let speed = 0.5; // Idle speed
    let targetSpeed = 0.5;
    let warpActive = false;

    // ----- ANIMATION LOOP -----
    const animate = () => {
        requestAnimationFrame(animate);

        // Smooth speed transition
        speed += (targetSpeed - speed) * 0.05;

        // Move stars
        const positions = starMesh.geometry.attributes.position.array;
        
        for (let i = 2; i < starCount * 3; i += 3) {
            positions[i] += speed;

            // Reset star if it passes camera
            if (positions[i] > 1000) {
                positions[i] = -2000;
                
                // Randomize X/Y on reset to prevent "tunnel" artifacts
                positions[i-1] = (Math.random() - 0.5) * 3000;
                positions[i-2] = (Math.random() - 0.5) * 3000;
            }
        }
        
        starMesh.geometry.attributes.position.needsUpdate = true;
        
        // Slight rotation for interest
        starMesh.rotation.z += 0.0002;

        renderer.render(scene, camera);
    };

    animate();

    // ----- RESIZE HANDLER -----
    window.addEventListener('resize', () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setSize(window.innerWidth, window.innerHeight);
    });

    // ----- INTERACTION LOGIC -----
    
    // 1. Initial Click -> Warp & Cube Arrival
    const initiateSequence = () => {
        if(isInitiated) return;
        isInitiated = true;

        // Fade out text
        introText.style.opacity = '0';
        introText.style.pointerEvents = 'none';

        // WARP SPEED
        targetSpeed = 50;

        // After 1 second, Cube arrives and speed normalizes
        setTimeout(() => {
            targetSpeed = 2; // Slower than warp, faster than idle
            cubeWrapper.classList.add('arrived');
        }, 1200);
    };

    introText.addEventListener('click', initiateSequence);
    // Also allow clicking anywhere on background if text is missed, but only initially
    document.addEventListener('click', (e) => {
        if(!isInitiated && e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
            initiateSequence();
        }
    });

    // 2. Cube Rotation (Login <-> Signup)
    if(switchSignup) {
        switchSignup.addEventListener('click', (e) => {
            e.preventDefault();
            cubeWrapper.classList.add('show-signup');
        });
    }

    if(switchLogin) {
        switchLogin.addEventListener('click', (e) => {
            e.preventDefault();
            cubeWrapper.classList.remove('show-signup');
        });
    }

});
