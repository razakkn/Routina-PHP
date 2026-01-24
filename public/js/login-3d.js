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

    // 3. Password Strength Validation
    const passwordInput = document.getElementById('signup-password');
    const signupBtn = document.getElementById('signup-btn');
    const strengthFill = document.getElementById('strength-fill');
    const strengthText = document.getElementById('strength-text');
    
    const requirements = {
        length: document.getElementById('req-length'),
        upper: document.getElementById('req-upper'),
        lower: document.getElementById('req-lower'),
        number: document.getElementById('req-number'),
        special: document.getElementById('req-special')
    };

    if (passwordInput && signupBtn) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let score = 0;
            let checks = {
                length: password.length >= 8,
                upper: /[A-Z]/.test(password),
                lower: /[a-z]/.test(password),
                number: /[0-9]/.test(password),
                special: /[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\/`~]/.test(password)
            };

            // Update requirement indicators
            Object.keys(checks).forEach(key => {
                if (requirements[key]) {
                    if (checks[key]) {
                        requirements[key].classList.add('valid');
                        score++;
                    } else {
                        requirements[key].classList.remove('valid');
                    }
                }
            });

            // Update strength bar
            if (strengthFill) {
                strengthFill.className = 'strength-fill';
                if (score === 0) {
                    strengthFill.className = 'strength-fill';
                } else if (score <= 2) {
                    strengthFill.classList.add('weak');
                } else if (score <= 3) {
                    strengthFill.classList.add('fair');
                } else if (score <= 4) {
                    strengthFill.classList.add('good');
                } else {
                    strengthFill.classList.add('strong');
                }
            }

            // Update text
            if (strengthText) {
                const labels = ['', 'Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
                strengthText.textContent = labels[score] || 'Enter a strong password';
            }

            // Enable/disable submit button
            const allValid = Object.values(checks).every(v => v);
            
            // Also check if Routina ID is valid and available
            const routinaIdInput = document.getElementById('signup-routina-id');
            const routinaIdValid = routinaIdInput && routinaIdInput.dataset.available === 'true';
            
            signupBtn.disabled = !(allValid && routinaIdValid);
        });
    }

    // 4. Routina ID availability check
    const routinaIdInput = document.getElementById('signup-routina-id');
    const routinaIdStatus = document.getElementById('routina-id-status');

    if (routinaIdInput && routinaIdStatus) {
        let checkTimeout = null;

        routinaIdInput.addEventListener('input', function() {
            let value = this.value.toLowerCase().replace(/[^a-z0-9_]/g, '');
            this.value = value;
            this.dataset.available = 'false';

            if (signupBtn) signupBtn.disabled = true;

            if (value.length < 3) {
                routinaIdStatus.innerHTML = '<span style="color: #888;">At least 3 characters required</span>';
                return;
            }

            if (!/^[a-z]/.test(value)) {
                routinaIdStatus.innerHTML = '<span style="color: #ff6b6b;">Must start with a letter</span>';
                return;
            }

            routinaIdStatus.innerHTML = '<span style="color: #888;">Checking...</span>';

            if (checkTimeout) clearTimeout(checkTimeout);
            checkTimeout = setTimeout(() => {
                fetch('/api/check-routina-id?id=' + encodeURIComponent(value))
                    .then(r => r.json())
                    .then(data => {
                        if (data.available) {
                            routinaIdStatus.innerHTML = '<span style="color: #4ecdc4;">✓ @' + value + ' is available!</span>';
                            routinaIdInput.dataset.available = 'true';
                            // Re-trigger password validation to update button state
                            if (passwordInput) passwordInput.dispatchEvent(new Event('input'));
                        } else {
                            routinaIdStatus.innerHTML = '<span style="color: #ff6b6b;">✕ @' + value + ' is taken</span>';
                            routinaIdInput.dataset.available = 'false';
                        }
                    })
                    .catch(() => {
                        routinaIdStatus.innerHTML = '<span style="color: #888;">Could not check availability</span>';
                    });
            }, 300);
        });
    }

});
