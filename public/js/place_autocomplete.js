(function () {
  function debounce(fn, delay) {
    let t = null;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), delay);
    };
  }

  function createDropdown(host) {
    const ul = document.createElement('ul');
    ul.className = 'place-dd';
    ul.hidden = true;
    host.appendChild(ul);
    return ul;
  }

  function attach(input) {
    const host = input.closest('.place-host') || input.parentElement;
    if (!host) return;

    host.classList.add('place-host');
    const dd = createDropdown(host);

    const close = () => { dd.hidden = true; dd.innerHTML = ''; };
    const open = () => { if (dd.children.length > 0) dd.hidden = false; };

    const render = (items) => {
      dd.innerHTML = '';
      if (!items || items.length === 0) { close(); return; }

      items.forEach(item => {
        const li = document.createElement('li');
        li.className = 'place-dd-item';
        li.tabIndex = 0;

        const main = document.createElement('div');
        main.className = 'place-dd-main';
        main.textContent = item.display;

        li.appendChild(main);

        li.addEventListener('click', () => {
          input.value = item.value;
          close();
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        li.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') { e.preventDefault(); li.click(); }
          if (e.key === 'Escape') { e.preventDefault(); close(); input.blur(); }
        });

        dd.appendChild(li);
      });

      open();
    };

    const fetchPlaces = debounce(async () => {
      const q = (input.value || '').trim();
      if (q.length < 2) { close(); return; }

      try {
        const res = await fetch(`/api/places?q=${encodeURIComponent(q)}`, {
          headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) { close(); return; }

        const data = await res.json();
        render(data);
      } catch {
        close();
      }
    }, 220);

    input.addEventListener('input', fetchPlaces);
    input.addEventListener('blur', () => setTimeout(close, 140));
    input.addEventListener('focus', () => setTimeout(open, 50));
  }

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-place-autocomplete="true"]').forEach(attach);
  });
})(); 
