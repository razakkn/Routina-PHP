(() => {
  const greetingTextEl = document.getElementById('dash-greeting-text');
  const timeEl = document.getElementById('dash-datetime');
  const tzEl = document.getElementById('dash-timezone');
  const cityEl = document.getElementById('dash-city');

  if (!greetingTextEl || !timeEl || !tzEl || !cityEl) {
    return;
  }

  const getGreeting = (hours) => {
    if (hours < 5) return 'Good night';
    if (hours < 12) return 'Good morning';
    if (hours < 17) return 'Good afternoon';
    if (hours < 21) return 'Good evening';
    return 'Good night';
  };

  const formatTime = (date) => {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  };

  const formatCityFromTz = (timeZone) => {
    if (!timeZone) return 'Local time';
    const parts = timeZone.split('/');
    const name = parts.length > 1 ? parts[parts.length - 1] : timeZone;
    return name.replace(/_/g, ' ');
  };

  const setCity = (value) => {
    if (value) {
      cityEl.textContent = value;
    }
  };

  const update = () => {
    const now = new Date();
    const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
    greetingTextEl.textContent = getGreeting(now.getHours());
    timeEl.textContent = formatTime(now);
    tzEl.textContent = tz ? tz.replace(/_/g, ' ') : 'Local time';
    if (!cityEl.textContent || cityEl.textContent === 'City') {
      cityEl.textContent = formatCityFromTz(tz);
    }
  };

  const fetchCityFromGeo = async (lat, lon) => {
    const url = `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${encodeURIComponent(lat)}&longitude=${encodeURIComponent(lon)}&localityLanguage=en`;
    const res = await fetch(url, { method: 'GET', credentials: 'omit' });
    if (!res.ok) throw new Error('Geo lookup failed');
    const data = await res.json();
    return data.city || data.locality || data.principalSubdivision || data.countryName || '';
  };

  const fetchCityFromIP = async () => {
    const res = await fetch('https://ipapi.co/json/', { method: 'GET', credentials: 'omit' });
    if (!res.ok) throw new Error('IP lookup failed');
    const data = await res.json();
    return data.city || data.region || data.country_name || '';
  };

  const initLocation = () => {
    if (!navigator.geolocation) {
      fetchCityFromIP().then(setCity).catch(() => {});
      return;
    }

    navigator.geolocation.getCurrentPosition(
      (pos) => {
        const lat = pos.coords.latitude;
        const lon = pos.coords.longitude;
        fetchCityFromGeo(lat, lon)
          .then(setCity)
          .catch(() => {
            fetchCityFromIP().then(setCity).catch(() => {});
          });
      },
      () => {
        fetchCityFromIP().then(setCity).catch(() => {});
      },
      { timeout: 5000, maximumAge: 600000, enableHighAccuracy: false }
    );
  };

  update();
  initLocation();
  setInterval(update, 1000);
})();
