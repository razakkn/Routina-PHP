(function () {
  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function clearDatalist(datalist) {
    while (datalist.firstChild) datalist.removeChild(datalist.firstChild);
  }

  function fillDatalist(datalist, items) {
    clearDatalist(datalist);
    (items || []).forEach((value) => {
      const opt = document.createElement('option');
      opt.value = value;
      datalist.appendChild(opt);
    });
  }

  function debounce(fn, ms) {
    let t;
    return function (...args) {
      clearTimeout(t);
      t = setTimeout(() => fn.apply(this, args), ms);
    };
  }

  async function fetchJson(url) {
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) return [];
    try {
      const data = await res.json();
      return Array.isArray(data) ? data : [];
    } catch {
      return [];
    }
  }

  async function loadMakes(makeInput, makeList) {
    const q = (makeInput.value || '').trim();
    const url = '/api/vehicle/makes?q=' + encodeURIComponent(q);
    const makes = await fetchJson(url);
    fillDatalist(makeList, makes);
  }

  async function loadModels(yearSelect, makeInput, modelInput, modelList) {
    const year = (yearSelect.value || '').trim();
    const make = (makeInput.value || '').trim();
    if (!year || !make || make.length < 2) {
      fillDatalist(modelList, []);
      return;
    }

    const url =
      '/api/vehicle/models?year=' +
      encodeURIComponent(year) +
      '&make=' +
      encodeURIComponent(make);

    const models = await fetchJson(url);
    fillDatalist(modelList, models);

    // If user already typed something, leave it; otherwise prefill first model
    if (!modelInput.value && models.length === 1) {
      modelInput.value = models[0];
    }
  }

  function initVehiclePicker(root) {
    const yearSelect = qs('[data-vehicle-year]', root);
    const makeInput = qs('[data-vehicle-make]', root);
    const modelInput = qs('[data-vehicle-model]', root);
    const makeList = qs('#vehicleMakeList', root);
    const modelList = qs('#vehicleModelList', root);

    if (!yearSelect || !makeInput || !modelInput || !makeList || !modelList) return;

    const debouncedMakes = debounce(() => loadMakes(makeInput, makeList), 200);
    const debouncedModels = debounce(
      () => loadModels(yearSelect, makeInput, modelInput, modelList),
      250
    );

    yearSelect.addEventListener('change', () => {
      // Changing year changes the model set
      fillDatalist(modelList, []);
      debouncedModels();
    });

    makeInput.addEventListener('input', () => {
      debouncedMakes();
      debouncedModels();
    });

    makeInput.addEventListener('change', () => {
      debouncedModels();
    });

    modelInput.addEventListener('focus', () => {
      // Load models when user focuses model field
      debouncedModels();
    });

    // Initial fill
    debouncedMakes();
    debouncedModels();
  }

  document.addEventListener('DOMContentLoaded', function () {
    // Supports multiple forms on same page
    document
      .querySelectorAll('[data-vehicle-picker]')
      .forEach((el) => initVehiclePicker(el));
  });
})();
