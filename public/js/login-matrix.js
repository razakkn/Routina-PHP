// login-matrix.js
document.addEventListener('DOMContentLoaded', function() {
    const introText = document.getElementById('intro-text');
    const matrixContainer = document.getElementById('matrix-container');
    const spaceBg = document.getElementById('space-bg');

    // Launch matrix on click
    function launchMatrix() {
        introText.style.opacity = '0';
        setTimeout(() => {
            introText.style.display = 'none';
            matrixContainer.classList.remove('hidden');
            matrixContainer.classList.add('launched');
        }, 1000);
    }

    introText.addEventListener('click', launchMatrix);
    spaceBg.addEventListener('click', launchMatrix);

    // Screen switching
    const initialScreen = document.getElementById('initial-screen');
    const loginScreen = document.getElementById('login-screen');
    const signupScreen = document.getElementById('signup-screen');

    const btnLogin = document.getElementById('btn-login');
    const btnSignup = document.getElementById('btn-signup');
    const backToInitialLogin = document.getElementById('back-to-initial-login');
    const backToInitialSignup = document.getElementById('back-to-initial-signup');

    function showScreen(screen) {
        initialScreen.style.display = 'none';
        loginScreen.style.display = 'none';
        signupScreen.style.display = 'none';
        screen.style.display = 'flex';
    }

    btnLogin.addEventListener('click', function() {
        showScreen(loginScreen);
    });

    btnSignup.addEventListener('click', function() {
        showScreen(signupScreen);
    });

    backToInitialLogin.addEventListener('click', function(e) {
        e.preventDefault();
        showScreen(initialScreen);
    });

    backToInitialSignup.addEventListener('click', function(e) {
        e.preventDefault();
        showScreen(initialScreen);
    });

    // Password strength checker
    const passwordInput = document.getElementById('signup-password');
    const strengthFill = document.getElementById('strength-fill');
    const strengthText = document.getElementById('strength-text');
    const requirements = {
        length: document.getElementById('req-length'),
        upper: document.getElementById('req-upper'),
        lower: document.getElementById('req-lower'),
        number: document.getElementById('req-number'),
        special: document.getElementById('req-special')
    };
    const signupBtn = document.getElementById('signup-btn');

    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let score = 0;
        const checks = {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /\d/.test(password),
            special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
        };

        for (const [key, valid] of Object.entries(checks)) {
            if (valid) {
                score++;
                requirements[key].classList.add('valid');
            } else {
                requirements[key].classList.remove('valid');
            }
        }

        strengthFill.className = 'strength-fill';
        if (score <= 2) {
            strengthFill.classList.add('weak');
            strengthText.textContent = 'Weak password';
        } else if (score <= 3) {
            strengthFill.classList.add('fair');
            strengthText.textContent = 'Fair password';
        } else if (score <= 4) {
            strengthFill.classList.add('good');
            strengthText.textContent = 'Good password';
        } else {
            strengthFill.classList.add('strong');
            strengthText.textContent = 'Strong password';
        }

        signupBtn.disabled = score < 5;
    });

    // Routina ID checker
    const routinaIdInput = document.getElementById('signup-routina-id');
    const routinaIdStatus = document.getElementById('routina-id-status');

    routinaIdInput.addEventListener('input', function() {
        const id = this.value;
        if (id.length < 3) {
            routinaIdStatus.textContent = 'At least 3 characters';
            routinaIdStatus.style.color = '#ff6666';
            return;
        }
        if (!/^[a-z][a-z0-9_]*$/.test(id)) {
            routinaIdStatus.textContent = 'Only lowercase letters, numbers, and underscores; must start with letter';
            routinaIdStatus.style.color = '#ff6666';
            return;
        }
        routinaIdStatus.textContent = 'Available';
        routinaIdStatus.style.color = '#00ff88';
    });
});