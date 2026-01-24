(function () {
	// Only enable on authenticated app pages where the layout includes the canvas.
	const canvas = document.getElementById("app-3d");
	if (!canvas) return;

	const body = document.body;
	if (!body) return;

	const reduceMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
	if (reduceMotion) return;

	// If Three.js isn't available (blocked CDN), quietly bail.
	if (typeof THREE === "undefined") return;

	const getModule = () => body.dataset.module || "dashboard";
	const getThemeMode = () => (body.dataset.theme === "dark" ? "dark" : "light");

	const THEMES = {
		dashboard: {
			primary: 0x7c6dff,
			secondary: 0x42d8ff,
			accent: 0xff6cf2,
			fog: 0x06081a,
			shapes: "space"
		},
		vacation: {
			primary: 0x06b6d4,
			secondary: 0x22c55e,
			accent: 0x3b82f6,
			fog: 0x04171a,
			shapes: "nature"
		},
		home: {
			primary: 0xffc857,
			secondary: 0x7c6dff,
			accent: 0x42d8ff,
			fog: 0x140f07,
			shapes: "calm"
		},
		vehicle: {
			primary: 0xff9557,
			secondary: 0x7c6dff,
			accent: 0x42d8ff,
			fog: 0x12080a,
			shapes: "garage"
		},
		family: {
			primary: 0x22c55e,
			secondary: 0x3b82f6,
			accent: 0x8b5a2b,
			fog: 0x07120a,
			shapes: "tree"
		},
		finance: {
			primary: 0x32d3a0,
			secondary: 0x7c6dff,
			accent: 0xffb757,
			fog: 0x06120b,
			shapes: "coins"
		},
		health: {
			primary: 0xff6b8f,
			secondary: 0x42d8ff,
			accent: 0x7c6dff,
			fog: 0x12060c,
			shapes: "pulse"
		},
		calendar: {
			primary: 0x806bff,
			secondary: 0x42d8ff,
			accent: 0xff6cf2,
			fog: 0x07061a,
			shapes: "orbit"
		},
		journal: {
			primary: 0x8a7dff,
			secondary: 0x42d8ff,
			accent: 0xff6cf2,
			fog: 0x07061a,
			shapes: "ink"
		},
		profile: {
			primary: 0x7c6dff,
			secondary: 0x42d8ff,
			accent: 0xff6cf2,
			fog: 0x06081a,
			shapes: "space"
		}
	};

	const themeFor = (moduleName) => THEMES[moduleName] || THEMES.dashboard;

	// Renderer
	const renderer = new THREE.WebGLRenderer({
		canvas,
		alpha: true,
		antialias: false,
		powerPreference: "high-performance"
	});
	renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
	renderer.setSize(window.innerWidth, window.innerHeight);

	const scene = new THREE.Scene();
	const camera = new THREE.PerspectiveCamera(60, window.innerWidth / window.innerHeight, 0.1, 2400);
	camera.position.set(0, 0, 760);

	// Small glow sprite so point particles look like stars (not squares)
	const makeGlowSprite = () => {
		const size = 64;
		const c = document.createElement("canvas");
		c.width = size;
		c.height = size;
		const ctx = c.getContext("2d");
		if (!ctx) return null;
		const g = ctx.createRadialGradient(size / 2, size / 2, 0, size / 2, size / 2, size / 2);
		g.addColorStop(0.0, "rgba(255,255,255,1)");
		g.addColorStop(0.20, "rgba(255,255,255,0.85)");
		g.addColorStop(0.48, "rgba(255,255,255,0.18)");
		g.addColorStop(1.0, "rgba(255,255,255,0)");
		ctx.fillStyle = g;
		ctx.fillRect(0, 0, size, size);
		const tex = new THREE.CanvasTexture(c);
		tex.needsUpdate = true;
		return tex;
	};

	const GLOW_SPRITE = makeGlowSprite();

	// Lights
	const ambient = new THREE.AmbientLight(0xffffff, 0.65);
	scene.add(ambient);
	const keyLight = new THREE.DirectionalLight(0xffffff, 0.55);
	keyLight.position.set(120, 220, 300);
	scene.add(keyLight);

	// Groups
	const root = new THREE.Group();
	scene.add(root);

	let starField = null;
	let starState = null;
	let fxField = null;
	let fxState = null;
	let nebulaField = null;
	let nebulaState = null;
	let galaxyField = null;
	let galaxyState = null;
	let heroMeshes = [];
	let warpUntil = 0;
	let warpLevel = 0;

	const disposeMesh = (mesh) => {
		try {
			if (mesh.geometry) mesh.geometry.dispose();
			if (mesh.material) {
				if (Array.isArray(mesh.material)) mesh.material.forEach((m) => m.dispose());
				else mesh.material.dispose();
			}
		} catch (_) {
			// ignore
		}
	};

	const clearScene = () => {
		if (starField) {
			root.remove(starField);
			disposeMesh(starField);
			starField = null;
			starState = null;
		}
		if (nebulaField) {
			root.remove(nebulaField);
			disposeMesh(nebulaField);
			nebulaField = null;
			nebulaState = null;
		}
		if (galaxyField) {
			root.remove(galaxyField);
			disposeMesh(galaxyField);
			galaxyField = null;
			galaxyState = null;
		}
		if (fxField) {
			root.remove(fxField);
			disposeMesh(fxField);
			fxField = null;
			fxState = null;
		}
		heroMeshes.forEach((m) => {
			root.remove(m);
			disposeMesh(m);
		});
		heroMeshes = [];
	};

	const randN = () => {
		// Box–Muller transform (rough gaussian)
		let u = 0;
		let v = 0;
		while (u === 0) u = Math.random();
		while (v === 0) v = Math.random();
		return Math.sqrt(-2.0 * Math.log(u)) * Math.cos(2.0 * Math.PI * v);
	};

	const mix3 = (a, b, t) => a + (b - a) * t;

	const makeNebula = (theme, mode) => {
		const count = mode === "dark" ? 2600 : 1700;
		const geo = new THREE.BufferGeometry();
		const pos = new Float32Array(count * 3);
		const col = new Float32Array(count * 3);
		const base = new THREE.Color(theme.primary);
		const edge = new THREE.Color(theme.accent);
		const cool = new THREE.Color(theme.secondary);

		const clusters = [
			{ x: -520, y: 120, z: -620, sx: 360, sy: 230, sz: 220, w: 0.45 },
			{ x: -140, y: -40, z: -560, sx: 260, sy: 200, sz: 180, w: 0.35 },
			{ x: 220, y: -180, z: -600, sx: 300, sy: 220, sz: 220, w: 0.20 }
		];

		for (let i = 0; i < count; i++) {
			// Weighted cluster selection
			let r = Math.random();
			let chosen = clusters[0];
			if (r > clusters[0].w) {
				chosen = r > clusters[0].w + clusters[1].w ? clusters[2] : clusters[1];
			}

			const px = chosen.x + randN() * chosen.sx;
			const py = chosen.y + randN() * chosen.sy;
			const pz = chosen.z + randN() * chosen.sz;
			const idx = i * 3;
			pos[idx] = px;
			pos[idx + 1] = py;
			pos[idx + 2] = pz;

			// Color mix based on radius from cluster center
			const dx = (px - chosen.x) / (chosen.sx || 1);
			const dy = (py - chosen.y) / (chosen.sy || 1);
			const dz = (pz - chosen.z) / (chosen.sz || 1);
			const d = Math.min(1, Math.sqrt(dx * dx + dy * dy + dz * dz));
			const t1 = Math.min(1, d * 1.15);
			const t2 = Math.min(1, Math.max(0, (d - 0.35) * 1.25));
			const cr = mix3(base.r, edge.r, t1);
			const cg = mix3(base.g, edge.g, t1);
			const cb = mix3(base.b, edge.b, t1);
			col[idx] = mix3(cr, cool.r, t2);
			col[idx + 1] = mix3(cg, cool.g, t2);
			col[idx + 2] = mix3(cb, cool.b, t2);
		}

		geo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
		geo.setAttribute("color", new THREE.BufferAttribute(col, 3));
		const mat = new THREE.PointsMaterial({
			vertexColors: true,
			size: mode === "dark" ? 5.2 : 4.2,
			transparent: true,
			opacity: mode === "dark" ? 0.30 : 0.18,
			depthWrite: false,
			blending: THREE.AdditiveBlending,
			map: GLOW_SPRITE || null,
			alphaTest: 0.01
		});
		const mesh = new THREE.Points(geo, mat);
		mesh.position.z = 0;
		return {
			mesh,
			state: {
				count,
				pos,
				t: 0,
				baseOpacity: mat.opacity
			}
		};
	};

	const updateNebula = (state, warpBoost) => {
		if (!state) return;
		state.t += 0.012 + (warpBoost ? 0.035 : 0);
		const breathe = 0.88 + Math.sin(state.t) * 0.10;
		if (nebulaField && nebulaField.material && typeof nebulaField.material.opacity === "number") {
			nebulaField.material.opacity = Math.min(0.36, state.baseOpacity * breathe + (warpBoost ? 0.06 : 0));
		}
		if (nebulaField) {
			nebulaField.rotation.z += 0.00012 + (warpBoost ? 0.00065 : 0);
			nebulaField.rotation.y += 0.00008 + (warpBoost ? 0.00045 : 0);
		}
	};

	const makeGalaxy = (theme, mode) => {
		const count = mode === "dark" ? 1900 : 1100;
		const arms = 3;
		const R = 520;
		const geo = new THREE.BufferGeometry();
		const pos = new Float32Array(count * 3);
		const col = new Float32Array(count * 3);
		const cCore = new THREE.Color(0xffffff);
		const cA = new THREE.Color(theme.secondary);
		const cB = new THREE.Color(theme.primary);

		for (let i = 0; i < count; i++) {
			const idx = i * 3;
			const radius = Math.pow(Math.random(), 0.55) * R;
			const arm = Math.floor(Math.random() * arms);
			const baseAngle = (arm / arms) * Math.PI * 2;
			const angle = baseAngle + radius * 0.010 + randN() * 0.16;
			const thickness = randN() * (18 + (1 - radius / R) * 46);

			const x = Math.cos(angle) * radius + randN() * 10;
			const y = Math.sin(angle) * radius + randN() * 10;
			const z = thickness;

			pos[idx] = x;
			pos[idx + 1] = y;
			pos[idx + 2] = z;

			const t = Math.min(1, radius / R);
			const warm = 0.25 + (1 - t) * 0.55;
			col[idx] = mix3(cCore.r, mix3(cA.r, cB.r, t), t) * warm;
			col[idx + 1] = mix3(cCore.g, mix3(cA.g, cB.g, t), t) * warm;
			col[idx + 2] = mix3(cCore.b, mix3(cA.b, cB.b, t), t) * warm;
		}

		geo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
		geo.setAttribute("color", new THREE.BufferAttribute(col, 3));
		const mat = new THREE.PointsMaterial({
			vertexColors: true,
			size: mode === "dark" ? 2.5 : 2.1,
			transparent: true,
			opacity: mode === "dark" ? 0.42 : 0.22,
			depthWrite: false,
			blending: THREE.AdditiveBlending,
			map: GLOW_SPRITE || null,
			alphaTest: 0.01
		});
		const mesh = new THREE.Points(geo, mat);
		// Place galaxy up/right like a “distant swirl”
		mesh.position.set(520, 260, -720);
		mesh.rotation.x = Math.PI * 0.08;
		mesh.rotation.y = -Math.PI * 0.10;
		return {
			mesh,
			state: {
				baseOpacity: mat.opacity,
				rot: 0,
				rotSpeed: 0.00075
			}
		};
	};

	const updateGalaxy = (state, warpBoost) => {
		if (!state || !galaxyField) return;
		state.rot += state.rotSpeed + (warpBoost ? 0.0024 : 0);
		galaxyField.rotation.z = state.rot;
		galaxyField.rotation.y = -Math.PI * 0.10 + Math.sin(state.rot * 0.7) * 0.03;
		if (galaxyField.material && typeof galaxyField.material.opacity === "number") {
			galaxyField.material.opacity = Math.min(0.55, state.baseOpacity + (warpBoost ? 0.10 : 0));
		}
	};

	const makeStars = (color, density = 3800) => {
		const starGeometry = new THREE.BufferGeometry();
		const count = density;
		const pos = new Float32Array(count * 3);
		for (let i = 0; i < count * 3; i += 3) {
			pos[i] = (Math.random() - 0.5) * 3200;
			pos[i + 1] = (Math.random() - 0.5) * 2200;
			pos[i + 2] = -2200 + Math.random() * 3200;
		}
		starGeometry.setAttribute("position", new THREE.BufferAttribute(pos, 3));

		const starMaterial = new THREE.PointsMaterial({
			color,
			size: 1.9,
			transparent: true,
			opacity: 0.75,
			depthWrite: false,
			map: GLOW_SPRITE || null,
			alphaTest: 0.01
		});

		const mesh = new THREE.Points(starGeometry, starMaterial);
		return {
			mesh,
			state: {
				count,
				positions: pos,
				speed: 0.55
			}
		};
	};

	const updateStars = (state, speedScale) => {
		if (!state) return;
		const positions = state.positions;
		const count = state.count;
		const speed = state.speed * speedScale;
		for (let i = 2; i < count * 3; i += 3) {
			positions[i] += speed;
			if (positions[i] > 900) {
				positions[i] = -2200;
				positions[i - 1] = (Math.random() - 0.5) * 2200;
				positions[i - 2] = (Math.random() - 0.5) * 3200;
			}
		}
		// geometry attribute is updated via needsUpdate below
	};

	const makeFx = (kind, theme, mode) => {
		const count = kind === "nature" ? 650 : kind === "garage" ? 520 : kind === "tree" ? 560 : 480;
		const geo = new THREE.BufferGeometry();
		const pos = new Float32Array(count * 3);
		const vel = new Float32Array(count * 3);
		for (let i = 0; i < count * 3; i += 3) {
			pos[i] = (Math.random() - 0.5) * 1400;
			pos[i + 1] = (Math.random() - 0.5) * 900;
			pos[i + 2] = -80 - Math.random() * 900;

			// velocities vary by kind
			if (kind === "nature") {
				vel[i] = (Math.random() - 0.5) * 0.20;
				vel[i + 1] = -0.35 - Math.random() * 0.35;
				vel[i + 2] = 0.04 + Math.random() * 0.06;
			} else if (kind === "garage") {
				vel[i] = (Math.random() - 0.5) * 0.35;
				vel[i + 1] = 0.25 + Math.random() * 0.55;
				vel[i + 2] = 0.10 + Math.random() * 0.14;
			} else if (kind === "tree") {
				vel[i] = (Math.random() - 0.5) * 0.18;
				vel[i + 1] = -0.22 - Math.random() * 0.28;
				vel[i + 2] = 0.06 + Math.random() * 0.08;
			} else {
				vel[i] = (Math.random() - 0.5) * 0.14;
				vel[i + 1] = (Math.random() - 0.5) * 0.14;
				vel[i + 2] = 0.06 + Math.random() * 0.08;
			}
		}
		geo.setAttribute("position", new THREE.BufferAttribute(pos, 3));
		const color = kind === "garage" ? theme.primary : kind === "tree" ? theme.secondary : theme.accent;
		const mat = new THREE.PointsMaterial({
			color,
			size: kind === "garage" ? 2.6 : 2.2,
			transparent: true,
			opacity: mode === "dark" ? 0.40 : 0.18,
			depthWrite: false,
			map: GLOW_SPRITE || null,
			alphaTest: 0.01
		});
		const mesh = new THREE.Points(geo, mat);
		return {
			mesh,
			state: { kind, count, pos, vel }
		};
	};

	const updateFx = (state, warpBoost) => {
		if (!state) return;
		const { kind, count, pos, vel } = state;
		const boost = warpBoost ? 1.8 : 1.0;
		for (let i = 0; i < count * 3; i += 3) {
			pos[i] += vel[i] * boost;
			pos[i + 1] += vel[i + 1] * boost;
			pos[i + 2] += vel[i + 2] * boost;

			// recycle depending on direction
			if (kind === "garage") {
				if (pos[i + 1] > 520 || pos[i + 2] > 240) {
					pos[i] = (Math.random() - 0.5) * 1400;
					pos[i + 1] = -520 - Math.random() * 180;
					pos[i + 2] = -900 - Math.random() * 400;
				}
			} else {
				if (pos[i + 1] < -520 || pos[i + 2] > 240) {
					pos[i] = (Math.random() - 0.5) * 1400;
					pos[i + 1] = 520 + Math.random() * 180;
					pos[i + 2] = -900 - Math.random() * 400;
				}
			}
		}
	};

	const makeWire = (color, opacity = 0.35) =>
		new THREE.MeshPhysicalMaterial({
			color,
			metalness: 0.25,
			roughness: 0.22,
			transmission: 0.0,
			transparent: true,
			opacity,
			wireframe: true
		});

	const makeSolid = (color, opacity = 0.30) =>
		new THREE.MeshStandardMaterial({
			color,
			metalness: 0.35,
			roughness: 0.35,
			transparent: true,
			opacity
		});

	const buildThemeScene = () => {
		clearScene();

		const moduleName = getModule();
		const mode = getThemeMode();
		const theme = themeFor(moduleName);

		// Fog helps blend the 3D layer with CSS canvas gradients
		let fogColor = mode === "dark" ? theme.fog : 0xf6f8ff;
		let fogDensity = mode === "dark" ? 0.00075 : 0.00055;
		// Dashboard: reduce "white wash" so nebula/galaxy colors stay visible in light mode.
		if (moduleName === "dashboard") {
			fogColor = mode === "dark" ? theme.fog : 0x0b1024;
			fogDensity = mode === "dark" ? 0.00058 : 0.00026;
		}
		scene.fog = new THREE.FogExp2(fogColor, fogDensity);

		// Stars always, density varies per theme
		const starColor = mode === "dark" ? 0xffffff : 0x0c1024;
		const stars = makeStars(starColor, moduleName === "dashboard" ? 5600 : 3600);
		starField = stars.mesh;
		starState = stars.state;
		root.add(starField);
		// Stash theme for warp tinting
		starState.baseColor = starColor;
		starState.warpTint = mode === "dark" ? theme.primary : starColor;

		// Theme hero objects
		const common = new THREE.Group();
		root.add(common);

		const add = (mesh) => {
			common.add(mesh);
			heroMeshes.push(mesh);
		};

		if (theme.shapes === "space") {
			const geo1 = new THREE.IcosahedronGeometry(140, 1);
			const geo2 = new THREE.TorusKnotGeometry(92, 18, 120, 14);
			const m1 = new THREE.Mesh(geo1, makeWire(theme.primary, 0.32));
			const m2 = new THREE.Mesh(geo2, makeWire(theme.secondary, 0.26));
			m1.position.set(-220, 80, -120);
			m2.position.set(220, -70, -180);
			add(m1);
			add(m2);
		} else if (theme.shapes === "nature") {
			const geo = new THREE.SphereGeometry(150, 28, 18);
			const ring = new THREE.TorusGeometry(220, 10, 16, 80);
			const s = new THREE.Mesh(geo, makeSolid(theme.secondary, 0.20));
			const r = new THREE.Mesh(ring, makeWire(theme.primary, 0.22));
			s.position.set(-180, 60, -160);
			r.position.set(200, -40, -240);
			r.rotation.x = Math.PI / 2.6;
			add(s);
			add(r);
		} else if (theme.shapes === "garage") {
			const gear = new THREE.TorusKnotGeometry(110, 26, 110, 10);
			const box = new THREE.BoxGeometry(180, 120, 120, 2, 2, 2);
			const g = new THREE.Mesh(gear, makeWire(theme.primary, 0.28));
			const b = new THREE.Mesh(box, makeWire(theme.secondary, 0.22));
			g.position.set(-210, 40, -160);
			b.position.set(220, -55, -220);
			add(g);
			add(b);
		} else if (theme.shapes === "tree") {
			// Stylized "tree": trunk + canopy spheres (abstract)
			const trunk = new THREE.CylinderGeometry(36, 52, 220, 12, 1);
			const canopy = new THREE.SphereGeometry(110, 20, 14);
			const t = new THREE.Mesh(trunk, makeSolid(theme.accent, 0.16));
			const c1 = new THREE.Mesh(canopy, makeWire(theme.primary, 0.22));
			const c2 = new THREE.Mesh(new THREE.SphereGeometry(80, 18, 12), makeWire(theme.secondary, 0.18));
			t.position.set(-210, -60, -180);
			c1.position.set(-210, 90, -180);
			c2.position.set(-120, 140, -260);
			add(t);
			add(c1);
			add(c2);
		} else if (theme.shapes === "coins") {
			const coin = new THREE.CylinderGeometry(90, 90, 18, 40, 1);
			const stack = new THREE.CylinderGeometry(70, 70, 80, 30, 1);
			const c = new THREE.Mesh(coin, makeWire(theme.primary, 0.22));
			const s = new THREE.Mesh(stack, makeWire(theme.accent, 0.18));
			c.position.set(-200, 40, -160);
			s.position.set(210, -60, -240);
			c.rotation.x = Math.PI / 2.9;
			s.rotation.x = Math.PI / 2.6;
			add(c);
			add(s);
		} else {
			// Generic abstract
			const geo = new THREE.DodecahedronGeometry(150, 0);
			const mesh = new THREE.Mesh(geo, makeWire(theme.primary, 0.26));
			mesh.position.set(0, 30, -200);
			add(mesh);
		}

		// Extra richness for the Dashboard: nebula + spiral galaxy (procedural)
		if (moduleName === "dashboard") {
			const neb = makeNebula(theme, mode);
			nebulaField = neb.mesh;
			nebulaState = neb.state;
			root.add(nebulaField);

			const gal = makeGalaxy(theme, mode);
			galaxyField = gal.mesh;
			galaxyState = gal.state;
			root.add(galaxyField);
		}

		// Module-specific foreground particles (leaf/sparks/petals/etc.)
		let fxKind = "generic";
		if (moduleName === "vacation") fxKind = "nature";
		else if (moduleName === "vehicle") fxKind = "garage";
		else if (moduleName === "family") fxKind = "tree";
		else if (moduleName === "home") fxKind = "calm";
		else if (moduleName === "finance") fxKind = "coins";
		else if (moduleName === "dashboard") fxKind = "space";
		const fx = makeFx(fxKind, theme, mode);
		fxField = fx.mesh;
		fxState = fx.state;
		root.add(fxField);
	};

	// Check whether we should play a short “warp” on arrival
	try {
		const w = window.sessionStorage.getItem("routina.nav.warp");
		const ts = parseInt(window.sessionStorage.getItem("routina.nav.ts") || "0", 10);
		if (w === "1" && ts && (Date.now() - ts) < 3000) {
			warpUntil = performance.now() + 900;
		}
		window.sessionStorage.removeItem("routina.nav.warp");
	} catch (error) {
		// ignore
	}

	buildThemeScene();

	// Parallax
	let targetX = 0;
	let targetY = 0;
	let currentX = 0;
	let currentY = 0;

	const onPointerMove = (event) => {
		const x = (event.clientX / window.innerWidth) * 2 - 1;
		const y = (event.clientY / window.innerHeight) * 2 - 1;
		targetX = x;
		targetY = y;
	};

	window.addEventListener("pointermove", onPointerMove, { passive: true });

	let running = true;
	const onVisibility = () => {
		running = !document.hidden;
	};
	document.addEventListener("visibilitychange", onVisibility);

	// Animate
	const animate = () => {
		if (running) {
			const now = performance.now();
			const warpActive = now < warpUntil;
			warpLevel += ((warpActive ? 1 : 0) - warpLevel) * 0.08;

			currentX += (targetX - currentX) * 0.035;
			currentY += (targetY - currentY) * 0.035;

			root.rotation.y = currentX * 0.22;
			root.rotation.x = -currentY * 0.14;

			if (starField && starState) {
				starField.rotation.z += 0.00015 + warpLevel * 0.001;
				updateStars(starState, 1 + warpLevel * 90);
				starField.geometry.attributes.position.needsUpdate = true;
				// brightens slightly during warp
				if (starField.material && typeof starField.material.opacity === "number") {
					starField.material.opacity = 0.75 + warpLevel * 0.18;
				}
				if (starField.material && starField.material.color) {
					// Blend toward warpTint when warping
					starField.material.color.setHex(warpLevel > 0.05 ? starState.warpTint : starState.baseColor);
				}
			}

			if (fxField && fxState) {
				updateFx(fxState, warpLevel > 0.05);
				fxField.geometry.attributes.position.needsUpdate = true;
				if (fxField.material && typeof fxField.material.opacity === "number") {
					fxField.material.opacity = (getThemeMode() === "dark" ? 0.40 : 0.18) + warpLevel * 0.10;
				}
			}

			if (nebulaField && nebulaState) {
				updateNebula(nebulaState, warpLevel > 0.05);
			}

			if (galaxyField && galaxyState) {
				updateGalaxy(galaxyState, warpLevel > 0.05);
			}

			heroMeshes.forEach((m, i) => {
				m.rotation.x += 0.0006 + i * 0.00003;
				m.rotation.y += 0.0009 + i * 0.00002;
			});

			renderer.render(scene, camera);
		}

		requestAnimationFrame(animate);
	};

	animate();

	// Resize
	const onResize = () => {
		camera.aspect = window.innerWidth / window.innerHeight;
		camera.updateProjectionMatrix();
		renderer.setSize(window.innerWidth, window.innerHeight);
		renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
	};
	window.addEventListener("resize", onResize);

	// Rebuild if theme/module toggles (our app reloads for navigation, but theme can toggle without reload)
	const observer = new MutationObserver(() => {
		buildThemeScene();
	});
	observer.observe(body, { attributes: true, attributeFilter: ["data-theme", "data-module"] });
})();
