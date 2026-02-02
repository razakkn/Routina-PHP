(function () {
	const body = document.body;
	if (!body) {
		return;
	}

	const pathname = window.location.pathname || "/";

	const detectModule = (path) => {
		if (path === "/" || path === "/dashboard") return "dashboard";
		if (path.startsWith("/journal")) return "journal";
		if (path.startsWith("/vacation")) return "vacation";
		if (path.startsWith("/finance")) return "finance";
		if (path.startsWith("/vehicle")) return "vehicle";
		if (path.startsWith("/home")) return "home";
		if (path.startsWith("/health")) return "health";
		if (path.startsWith("/calendar")) return "calendar";
		if (path.startsWith("/family")) return "family";
		if (path.startsWith("/profile") || path.startsWith("/account/profile")) return "profile";
		return "dashboard";
	};

	const appShell = document.querySelector(".app-shell");
	const hasSidebar = appShell?.dataset?.hasSidebar === "true";
	const moduleName = (!hasSidebar && pathname === "/") ? "landing" : detectModule(pathname);
	body.dataset.module = moduleName;

	const STORAGE_KEYS = {
		sidebar: "routina.sidebar",
		eggHistory: "routina.eggs.history"
	};

	const sidebarToggles = document.querySelectorAll("[data-sidebar-toggle]");
	const sidebarDismiss = document.querySelector("[data-sidebar-dismiss]");
	// appShell/hasSidebar are declared above for module detection.
	const moduleTabs = document.querySelector(".module-tabs");
	const compactSidebarMedia = window.matchMedia("(max-width: 1180px)");

	const onMediaChange = (mediaQuery, handler) => {
		if (typeof mediaQuery.addEventListener === "function") {
			mediaQuery.addEventListener("change", handler);
		} else if (typeof mediaQuery.addListener === "function") {
			mediaQuery.addListener(handler);
		}
	};

	const safeGet = (key) => {
		try {
			return window.localStorage.getItem(key);
		} catch (error) {
			return null;
		}
	};

	const safeSet = (key, value) => {
		try {
			window.localStorage.setItem(key, value);
		} catch (error) {
			// storage might be disabled; ignore
		}
	};

	const safeSessionGet = (key) => {
		try {
			return window.sessionStorage.getItem(key);
		} catch (error) {
			return null;
		}
	};

	const safeSessionSet = (key, value) => {
		try {
			window.sessionStorage.setItem(key, value);
		} catch (error) {
			// ignore
		}
	};

	const applyTheme = () => {
		body.dataset.theme = "light";
		document.documentElement.style.colorScheme = "light";
	};
	applyTheme();

	// Highlight active module tab
	if (moduleTabs) {
		const tabs = Array.from(moduleTabs.querySelectorAll(".module-tab"));
		tabs.forEach((tab) => {
			const tabModule = tab.getAttribute("data-module") || "";
			if (tabModule === moduleName) {
				tab.classList.add("is-active");
			} else {
				tab.classList.remove("is-active");
			}
		});
	}

	// Warp transition when switching modules (non-SPA):
	// Applies to any link carrying data-module (tabs, sidebar, dashboard portals).
	const attachWarpToLink = (linkEl) => {
		if (!linkEl || linkEl.__routinaWarpBound) return;
		linkEl.__routinaWarpBound = true;

		linkEl.addEventListener("click", (event) => {
			// let browser handle new-tab behavior
			if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
				return;
			}

			const href = linkEl.getAttribute("href") || "";
			if (!href || href.startsWith("#")) return;

			const toModule = linkEl.getAttribute("data-module") || "dashboard";
			const fromModule = body.dataset.module || "dashboard";
			if (toModule === fromModule) {
				return;
			}

			try {
				window.sessionStorage.setItem("routina.nav.warp", "1");
				window.sessionStorage.setItem("routina.nav.from", fromModule);
				window.sessionStorage.setItem("routina.nav.to", toModule);
				window.sessionStorage.setItem("routina.nav.ts", String(Date.now()));
			} catch (error) {
				// ignore
			}

			const overlay = document.createElement("div");
			overlay.className = "nav-warp-overlay";
			overlay.setAttribute("data-to", toModule);
			overlay.setAttribute("data-from", fromModule);
			document.body.appendChild(overlay);
			requestAnimationFrame(() => overlay.classList.add("is-on"));

			event.preventDefault();
			window.setTimeout(() => {
				window.location.href = href;
			}, 140);
		});
	};

	const moduleLinks = Array.from(document.querySelectorAll('a[data-module][href]'));
	moduleLinks.forEach(attachWarpToLink);


	// Easter egg: small, rotating, non-repeating (within a session)
	const injectEasterEgg = () => {
		// Only for authenticated app pages (after login)
		if (!hasSidebar) {
			return;
		}

		const baseEggs = [
			{ id: "sparkles", kind: "emoji", value: "✨", label: "Sparkles" },
			{ id: "bubble", kind: "bubble", value: "psst… you’re doing great.", label: "Bubble" },
			{ id: "ghost", kind: "emoji", value: "👻", label: "Friendly ghost" }
		];

		const eggsByModule = {
			dashboard: [
				{ id: "rocket", kind: "emoji", value: "🚀", label: "Warp ready" },
				{ id: "satellite", kind: "emoji", value: "🛰️", label: "Signal lock" },
				{ id: "alien", kind: "emoji", value: "👽", label: "Hello, human" },
				{ id: "star", kind: "emoji", value: "⭐", label: "Tiny star" }
			],
			vacation: [
				{ id: "compass", kind: "emoji", value: "🧭", label: "True north" },
				{ id: "island", kind: "emoji", value: "🏝️", label: "Island mode" },
				{ id: "wave", kind: "emoji", value: "🌊", label: "Good tide" },
				{ id: "leaf", kind: "emoji", value: "🍃", label: "Fresh air" }
			],
			vehicle: [
				{ id: "wrench", kind: "emoji", value: "🔧", label: "Tune-up" },
				{ id: "tire", kind: "emoji", value: "🛞", label: "Grip check" },
				{ id: "fuel", kind: "emoji", value: "⛽", label: "Fuel up" },
				{ id: "cone", kind: "emoji", value: "🚧", label: "Pit lane" }
			],
			family: [
				{ id: "tree", kind: "emoji", value: "🌳", label: "Roots" },
				{ id: "bird", kind: "emoji", value: "🐦", label: "Little bird" },
				{ id: "leaf2", kind: "emoji", value: "🍂", label: "Falling leaf" },
				{ id: "globe", kind: "emoji", value: "🌍", label: "Our world" }
			],
			finance: [
				{ id: "coin", kind: "emoji", value: "🪙", label: "Coin flip" },
				{ id: "chart", kind: "emoji", value: "📈", label: "Up and right" },
				{ id: "receipt", kind: "emoji", value: "🧾", label: "Receipt goblin" },
				{ id: "bank", kind: "emoji", value: "🏦", label: "Vault" }
			],
			home: [
				{ id: "tea", kind: "emoji", value: "🫖", label: "Cozy" },
				{ id: "couch", kind: "emoji", value: "🛋️", label: "Soft landing" },
				{ id: "house", kind: "emoji", value: "🏠", label: "Home base" },
				{ id: "lamp", kind: "emoji", value: "💡", label: "Good idea" }
			],
			journal: [
				{ id: "ink", kind: "emoji", value: "🖋️", label: "Ink" },
				{ id: "book", kind: "emoji", value: "📚", label: "Pages" },
				{ id: "note", kind: "emoji", value: "🗒️", label: "Note" },
				{ id: "moon", kind: "emoji", value: "🌙", label: "Night thoughts" }
			],
			calendar: [
				{ id: "calendar", kind: "emoji", value: "🗓️", label: "Timebox" },
				{ id: "alarm", kind: "emoji", value: "⏰", label: "Tick" },
				{ id: "pin", kind: "emoji", value: "📍", label: "Here" },
				{ id: "clock", kind: "emoji", value: "🕰️", label: "Old clock" }
			],
			health: [
				{ id: "heart", kind: "emoji", value: "🫀", label: "Heartbeat" },
				{ id: "water", kind: "emoji", value: "💧", label: "Hydrate" },
				{ id: "stretch", kind: "emoji", value: "🧘", label: "Stretch" },
				{ id: "bandage", kind: "emoji", value: "🩹", label: "Patch" }
			]
		};

		const eggs = baseEggs.concat(eggsByModule[moduleName] || []);

		let history = [];
		try {
			const raw = safeSessionGet(STORAGE_KEYS.eggHistory);
			if (raw) {
				const parsed = JSON.parse(raw);
				if (Array.isArray(parsed)) history = parsed;
			}
		} catch (error) {
			history = [];
		}

		const recent = history.slice(-5);
		const candidates = eggs.filter((e) => !recent.includes(e.id));
		const list = candidates.length ? candidates : eggs;

		// Seed: changes every page load but still deterministic-ish within a second
		const seed = (Date.now() ^ (pathname.length * 2654435761)) >>> 0;
		const idx = seed % list.length;
		const chosen = list[idx];

		// Persist history
		history.push(chosen.id);
		if (history.length > 20) history = history.slice(-20);
		safeSessionSet(STORAGE_KEYS.eggHistory, JSON.stringify(history));

		// Render
		const existing = document.querySelector("[data-easter-egg]");
		if (existing) existing.remove();

		const egg = document.createElement("div");
		egg.setAttribute("data-easter-egg", "true");
		egg.setAttribute("aria-hidden", "true");
		egg.style.position = "fixed";
		egg.style.right = "18px";
		egg.style.bottom = "18px";
		egg.style.zIndex = "999";
		egg.style.pointerEvents = "none";
		egg.style.filter = "drop-shadow(0 18px 30px rgba(0,0,0,0.18))";
		egg.style.opacity = "0";
		egg.style.transform = "translateY(10px) scale(0.98)";
		egg.style.transition = "opacity 420ms ease, transform 520ms cubic-bezier(0.23, 0.92, 0.36, 1)";

		const bubble = document.createElement("div");
		bubble.style.display = "inline-flex";
		bubble.style.alignItems = "center";
		bubble.style.gap = "10px";
		bubble.style.padding = "10px 14px";
		bubble.style.borderRadius = "999px";
		bubble.style.border = "1px solid rgba(124,109,255,0.22)";
		bubble.style.background = "rgba(255,255,255,0.62)";
		bubble.style.backdropFilter = "blur(14px) saturate(140%)";
		bubble.style.color = "inherit";
		bubble.style.fontWeight = "650";
		bubble.style.fontSize = "0.95rem";

		if (chosen.kind === "bubble") {
			bubble.textContent = chosen.value;
		} else {
			const icon = document.createElement("span");
			icon.textContent = chosen.value;
			icon.style.fontSize = "1.1rem";
			const text = document.createElement("span");
			text.textContent = chosen.label;
			bubble.appendChild(icon);
			bubble.appendChild(text);
		}

		egg.appendChild(bubble);
		document.body.appendChild(egg);

		requestAnimationFrame(() => {
			egg.style.opacity = "1";
			egg.style.transform = "translateY(0) scale(1)";
		});

		// Auto fade out after a bit (keep it “little”)
		window.setTimeout(() => {
			egg.style.opacity = "0";
			egg.style.transform = "translateY(8px) scale(0.98)";
			window.setTimeout(() => egg.remove(), 650);
		}, 5500);
	};

	// Skip on login/register pages that don't use the app shell.
	if (hasSidebar && !pathname.startsWith("/login") && !pathname.startsWith("/register")) {
		injectEasterEgg();
	}

	// Theme toggle removed: keep light theme only.

	if (!hasSidebar) {
		return;
	}

	const updateSidebarState = (state, options = { persist: true }) => {
		const nextState = state === "expanded" ? "expanded" : "collapsed";
		body.dataset.sidebarState = nextState;
		sidebarToggles.forEach((toggle) => {
			toggle.setAttribute("aria-expanded", String(nextState === "expanded"));
		});
		if (options.persist) {
			safeSet(STORAGE_KEYS.sidebar, nextState);
		}
	};

	const storedSidebar = safeGet(STORAGE_KEYS.sidebar);
	const defaultSidebar = compactSidebarMedia.matches ? "collapsed" : "expanded";
	const initialSidebar = storedSidebar === "collapsed" || storedSidebar === "expanded" ? storedSidebar : defaultSidebar;
	updateSidebarState(initialSidebar, { persist: false });

	const toggleSidebar = () => {
		const nextState = body.dataset.sidebarState === "expanded" ? "collapsed" : "expanded";
		updateSidebarState(nextState, { persist: true });
	};

	sidebarToggles.forEach((toggle) => {
		toggle.addEventListener("click", (event) => {
			event.preventDefault();
			toggleSidebar();
		});
	});

	if (sidebarDismiss) {
		sidebarDismiss.addEventListener("click", (event) => {
			event.preventDefault();
			updateSidebarState("collapsed", { persist: false });
		});
	}

	onMediaChange(compactSidebarMedia, (event) => {
		if (event.matches) {
			updateSidebarState("collapsed", { persist: false });
		} else {
			const persisted = safeGet(STORAGE_KEYS.sidebar) || "expanded";
			updateSidebarState(persisted, { persist: false });
		}
	});

	document.addEventListener("keydown", (event) => {
		if (event.key === "Escape" && compactSidebarMedia.matches && body.dataset.sidebarState === "expanded") {
			updateSidebarState("collapsed", { persist: false });
		}
	});
})();
