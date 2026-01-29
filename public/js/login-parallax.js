// login-parallax.js
// Subtle pointer parallax for the login box and star layers. Respects prefers-reduced-motion.
(function(){
    if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const box = document.querySelector('.login-box');
    const floatWrap = document.querySelector('.float-wrap');
    const stars = document.getElementById('stars');
    const stars2 = document.getElementById('stars2');
    if (!box || !floatWrap || (!stars && !stars2)) return;

    let mouseX = 0, mouseY = 0, cx = 0, cy = 0;
    const damp = 0.06;

    // Activation: clicking the box stops the float animation and focuses the first input
    let activated = false;
    box.addEventListener('click', function(){
        if (activated) return;
        activated = true;
        floatWrap.classList.add('activated');
        box.classList.add('activated');
        const firstInput = box.querySelector('input');
        if (firstInput) firstInput.focus();
    });

    window.addEventListener('pointermove', (e)=>{
        const w = window.innerWidth;
        const h = window.innerHeight;
        mouseX = (e.clientX - w/2) / (w/2); // -1..1
        mouseY = (e.clientY - h/2) / (h/2);
    });

    function update(){
        cx += (mouseX - cx) * damp;
        cy += (mouseY - cy) * damp;

        // apply small rotation/translation to card (applied to box)
        const rotX = cy * 4; // deg
        const rotY = cx * -6; // deg
        const tx = cx * 8; // px
        const ty = cy * 8; // px
        box.style.transform = `translate3d(${tx}px, ${ty}px, 0) rotateX(${rotX}deg) rotateY(${rotY}deg)`;

        // parallax stars (move opposite, smaller magnitude)
        if (stars) stars.style.transform = `translate3d(${ -cx * 30 }px, ${ -cy * 18 }px, 0) scale(${1 + Math.abs(cx)*0.02})`;
        if (stars2) stars2.style.transform = `translate3d(${ -cx * 18 }px, ${ -cy * 10 }px, 0) scale(${1 + Math.abs(cy)*0.01})`;

        requestAnimationFrame(update);
    }
    update();
})();