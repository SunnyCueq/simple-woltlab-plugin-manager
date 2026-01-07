/**
 * @module Shrinkr/Ui/Ghosts
 */
define(["require", "exports", "tslib", "Shrinkr/3rdParty/three.min", "WoltLabSuite/Core/Environment"], function (require, exports, tslib_1, THREE, Environment) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Ghosts = void 0;
    THREE = tslib_1.__importStar(THREE);
    Environment = tslib_1.__importStar(Environment);
    const SHARED_GHOST_SETTINGS = {
        pointerHoverRadius: 90,
        pointerClickRadius: 130,
        pointerRepelRadius: 170,
        appearFadeSpeed: 2.1,
        flickerSpeed: 1.7,
        spawnMarginFactor: 0.22,
        minSpawnMargin: 48,
        maxSpawnMargin: 180,
        focusMarginFactor: 0.08,
        minFocusMargin: 18,
        maxFocusMargin: 72,
        atlasRows: 2,
        atlasColumns: 2,
        minReappearDelay: 220,
        maxAdditionalReappearDelay: 380,
        hauntShiftMin: 8000,
        hauntShiftMaxAdditional: 8000,
        minHauntRadius: 90,
        maxHauntRadius: 240,
        minPhaseInterval: 6500,
        maxAdditionalPhaseInterval: 7000,
        explosionDurationMin: 0.45,
        explosionDurationMaxAdditional: 0.35,
        explosionScaleBoostMin: 0.85,
        explosionScaleBoostMaxAdditional: 0.75,
        explosionSpeedMin: 5.5,
        explosionSpeedMaxAdditional: 5.5,
        explosionFadeExponent: 1.35,
    };
    /**
     * Shared engine implementation that encapsulates particle simulation, timing, and
     * pointer interaction logic used by the WebGL and canvas renderers.
     */
    class BaseGhostEngine {
        baseOpacity;
        container;
        fadeScroll;
        enableInteraction;
        pointerHoverRadius = SHARED_GHOST_SETTINGS.pointerHoverRadius;
        pointerClickRadius = SHARED_GHOST_SETTINGS.pointerClickRadius;
        pointerRepelRadius = SHARED_GHOST_SETTINGS.pointerRepelRadius;
        appearFadeSpeed = SHARED_GHOST_SETTINGS.appearFadeSpeed;
        flickerSpeed = SHARED_GHOST_SETTINGS.flickerSpeed;
        spawnMarginFactor = SHARED_GHOST_SETTINGS.spawnMarginFactor;
        minSpawnMargin = SHARED_GHOST_SETTINGS.minSpawnMargin;
        maxSpawnMargin = SHARED_GHOST_SETTINGS.maxSpawnMargin;
        focusMarginFactor = SHARED_GHOST_SETTINGS.focusMarginFactor;
        minFocusMargin = SHARED_GHOST_SETTINGS.minFocusMargin;
        maxFocusMargin = SHARED_GHOST_SETTINGS.maxFocusMargin;
        atlasRows = SHARED_GHOST_SETTINGS.atlasRows;
        atlasColumns = SHARED_GHOST_SETTINGS.atlasColumns;
        totalAtlasImages = this.atlasRows * this.atlasColumns;
        minReappearDelay = SHARED_GHOST_SETTINGS.minReappearDelay;
        maxAdditionalReappearDelay = SHARED_GHOST_SETTINGS.maxAdditionalReappearDelay;
        hauntShiftMin = SHARED_GHOST_SETTINGS.hauntShiftMin;
        hauntShiftMaxAdditional = SHARED_GHOST_SETTINGS.hauntShiftMaxAdditional;
        minHauntRadius = SHARED_GHOST_SETTINGS.minHauntRadius;
        maxHauntRadius = SHARED_GHOST_SETTINGS.maxHauntRadius;
        minPhaseInterval = SHARED_GHOST_SETTINGS.minPhaseInterval;
        maxAdditionalPhaseInterval = SHARED_GHOST_SETTINGS.maxAdditionalPhaseInterval;
        explosionDurationMin = SHARED_GHOST_SETTINGS.explosionDurationMin;
        explosionDurationMaxAdditional = SHARED_GHOST_SETTINGS.explosionDurationMaxAdditional;
        explosionScaleBoostMin = SHARED_GHOST_SETTINGS.explosionScaleBoostMin;
        explosionScaleBoostMaxAdditional = SHARED_GHOST_SETTINGS.explosionScaleBoostMaxAdditional;
        explosionSpeedMin = SHARED_GHOST_SETTINGS.explosionSpeedMin;
        explosionSpeedMaxAdditional = SHARED_GHOST_SETTINGS.explosionSpeedMaxAdditional;
        explosionFadeExponent = SHARED_GHOST_SETTINGS.explosionFadeExponent;
        tmpVec3 = new THREE.Vector3();
        tmpVec3b = new THREE.Vector3();
        tmpVec2 = new THREE.Vector2();
        tmpVec2b = new THREE.Vector2();
        width = 0;
        height = 0;
        baseSpeedSetting = 1;
        minScaleSetting = 0;
        maxScaleSetting = 0;
        isMobile;
        animationFrameId = null;
        timeOrigin = performance.now();
        pausedDuration = 0;
        pauseStartedAt = null;
        isActiveCheck = null;
        lastFrameTime = performance.now();
        constructor(baseOpacity, container, fadeScroll, enableInteraction) {
            this.baseOpacity = baseOpacity;
            this.container = container;
            this.fadeScroll = fadeScroll;
            this.enableInteraction = enableInteraction;
            this.isMobile = this.detectMobile();
        }
        startAnimation(isActive) {
            this.isActiveCheck = isActive;
            if (this.isEngineReady()) {
                this.handleVisibilityChange();
            }
        }
        handleVisibilityChange() {
            if (!this.isEngineReady()) {
                return;
            }
            if (this.shouldAnimate()) {
                this.resumeTimeline();
                this.startAnimationLoop();
            }
            else {
                this.pauseTimeline();
                this.stopAnimationLoop();
            }
        }
        shouldAnimate() {
            const activeCheck = this.isActiveCheck ? this.isActiveCheck() : true;
            return activeCheck && !document.hidden;
        }
        startAnimationLoop() {
            if (this.animationFrameId !== null) {
                return;
            }
            this.animationFrameId = requestAnimationFrame(this.animationStep);
        }
        stopAnimationLoop() {
            if (this.animationFrameId !== null) {
                cancelAnimationFrame(this.animationFrameId);
                this.animationFrameId = null;
            }
        }
        animationStep = () => {
            this.animationFrameId = null;
            if (!this.shouldAnimate()) {
                this.pauseTimeline();
                return;
            }
            const now = this.now();
            let deltaTime = (now - this.lastFrameTime) / 1000;
            this.lastFrameTime = now;
            deltaTime = Math.max(0, Math.min(deltaTime, 0.033));
            this.onFrame(deltaTime, now);
            this.animationFrameId = requestAnimationFrame(this.animationStep);
        };
        now() {
            const rawNow = performance.now();
            const pausedTime = this.pauseStartedAt !== null ? rawNow - this.pauseStartedAt : 0;
            return rawNow - this.timeOrigin - this.pausedDuration - pausedTime;
        }
        pauseTimeline() {
            if (this.pauseStartedAt === null) {
                this.pauseStartedAt = performance.now();
            }
        }
        resumeTimeline() {
            if (this.pauseStartedAt !== null) {
                this.pausedDuration += performance.now() - this.pauseStartedAt;
                this.pauseStartedAt = null;
            }
            this.lastFrameTime = this.now();
        }
        detectMobile() {
            if (typeof window === "undefined") {
                return Environment.platform() !== "desktop";
            }
            if (Environment.platform() !== "desktop") {
                return true;
            }
            if (typeof window.matchMedia === "function") {
                return window.matchMedia("(max-width: 960px)").matches;
            }
            return window.innerWidth <= 960;
        }
        measureContainer() {
            if (this.container === document.body) {
                const width = Math.max(1, window.innerWidth || document.documentElement.clientWidth || this.container.clientWidth);
                const height = Math.max(1, window.innerHeight || document.documentElement.clientHeight || this.container.clientHeight);
                return { width, height };
            }
            const rect = this.container.getBoundingClientRect();
            const width = Math.max(1, rect.width || this.container.clientWidth || 0);
            const height = Math.max(1, rect.height || this.container.clientHeight || 0);
            return { width, height };
        }
        computeSpawnMargin(width, height) {
            const minDimension = Math.max(1, Math.min(width, height));
            const minMargin = Math.min(this.maxSpawnMargin, Math.max(this.minSpawnMargin, minDimension * 0.12));
            const margin = minDimension * this.spawnMarginFactor;
            return Math.max(this.minSpawnMargin, Math.min(this.maxSpawnMargin, Math.max(minMargin, margin)));
        }
        computeFocusMargin(width, height) {
            const minDimension = Math.max(1, Math.min(width, height));
            const margin = minDimension * this.focusMarginFactor;
            return Math.max(this.minFocusMargin, Math.min(this.maxFocusMargin, margin));
        }
        randomHauntRadius(width, height) {
            const minDimension = Math.max(1, Math.min(width, height));
            const dimensionFactor = this.isMobile ? 0.32 : 0.48;
            const scaledMax = Math.min(this.maxHauntRadius, Math.max(48, minDimension * dimensionFactor));
            const maxRadius = Math.max(48, scaledMax);
            const minRadiusBase = this.isMobile ? this.minHauntRadius * 0.7 : this.minHauntRadius;
            const minRadius = Math.min(maxRadius, Math.max(36, minRadiusBase));
            const range = Math.max(0, maxRadius - minRadius);
            return minRadius + Math.random() * range;
        }
        randomHauntAngularSpeed(baseSpeed) {
            return (Math.random() * 0.006 + 0.0035) * (0.6 + baseSpeed * 0.5);
        }
        randomPhaseInterval(baseSpeed) {
            const speedFactor = Math.min(1.6, Math.max(0.5, 0.7 + baseSpeed * 0.6));
            const intervalBase = this.minPhaseInterval / speedFactor;
            const range = this.maxAdditionalPhaseInterval / speedFactor;
            return intervalBase + Math.random() * range;
        }
        randomExplosionDuration() {
            return this.explosionDurationMin + Math.random() * this.explosionDurationMaxAdditional;
        }
        randomExplosionScaleBoost() {
            return this.explosionScaleBoostMin + Math.random() * this.explosionScaleBoostMaxAdditional;
        }
        randomExplosionSpeed(baseSpeed) {
            const base = this.explosionSpeedMin + Math.random() * this.explosionSpeedMaxAdditional;
            return base * (0.65 + baseSpeed * 0.85);
        }
        randomOrientation() {
            return Math.random() < 0.5 ? 1 : -1;
        }
        randomAtlasIndex() {
            return Math.floor(Math.random() * this.totalAtlasImages);
        }
        randomScale() {
            if (this.maxScaleSetting <= this.minScaleSetting) {
                return this.minScaleSetting;
            }
            return Math.random() * (this.maxScaleSetting - this.minScaleSetting) + this.minScaleSetting;
        }
        randomBaseSpeed() {
            return this.baseSpeedSetting * (0.35 + Math.random() * 0.55);
        }
        randomPosition(width, height, target, preferVisibleArea = false) {
            const halfWidth = width / 2;
            const halfHeight = height / 2;
            if (preferVisibleArea) {
                const margin = this.computeFocusMargin(width, height);
                const clampedMarginX = Math.min(margin, Math.max(0, halfWidth - 1));
                const clampedMarginY = Math.min(margin, Math.max(0, halfHeight - 1));
                const minX = -halfWidth + clampedMarginX;
                const maxX = halfWidth - clampedMarginX;
                const minY = -halfHeight + clampedMarginY;
                const maxY = halfHeight - clampedMarginY;
                const rangeX = Math.max(0, maxX - minX);
                const rangeY = Math.max(0, maxY - minY);
                const x = rangeX === 0 ? minX : Math.random() * rangeX + minX;
                const y = rangeY === 0 ? minY : Math.random() * rangeY + minY;
                if (target) {
                    target.set(x, y, 0);
                    return target;
                }
                return new THREE.Vector3(x, y, 0);
            }
            const margin = this.computeSpawnMargin(width, height);
            const minX = -halfWidth - margin;
            const maxX = halfWidth + margin;
            const minY = -halfHeight - margin;
            const maxY = halfHeight + margin;
            const x = Math.random() * (maxX - minX) + minX;
            const y = Math.random() * (maxY - minY) + minY;
            if (target) {
                target.set(x, y, 0);
                return target;
            }
            return new THREE.Vector3(x, y, 0);
        }
        assignHaunt(data, width, height, now) {
            this.randomPosition(width, height, data.hauntCenter, true);
            data.hauntRadius = this.randomHauntRadius(width, height);
            data.hauntAngle = Math.random() * Math.PI * 2;
            data.hauntAngularSpeed = this.randomHauntAngularSpeed(data.baseSpeed);
            data.nextHauntShift = now + this.hauntShiftMin + Math.random() * this.hauntShiftMaxAdditional;
            data.target.set(data.hauntCenter.x + Math.cos(data.hauntAngle) * data.hauntRadius, data.hauntCenter.y + Math.sin(data.hauntAngle) * data.hauntRadius, 0);
        }
        prepareGhostForRespawn(data, width, height, now) {
            this.randomPosition(width, height, data.position, this.isMobile);
            data.velocity.set(0, 0, 0);
            data.fade = 0;
            const newScale = this.randomScale();
            data.scale = newScale;
            data.baseScale = newScale;
            data.baseSpeed = this.randomBaseSpeed();
            data.uvIndex = this.randomAtlasIndex();
            this.onParticleAtlasIndexChanged(data);
            data.rotation = 0;
            data.swayOffset = Math.random() * Math.PI * 2;
            data.bobSpeed = 0.6 + Math.random() * 0.8;
            data.bobStrength = 8 + Math.random() * 10;
            data.phaseInterval = this.randomPhaseInterval(data.baseSpeed);
            data.nextPhaseAt = data.reappearAt + data.phaseInterval * (0.7 + Math.random() * 0.6);
            data.flickerOffset = Math.random() * Math.PI * 2;
            data.orientation = this.randomOrientation();
            data.explosionElapsed = 0;
            data.explosionDuration = this.explosionDurationMin;
            data.explosionVelocity.set(0, 0, 0);
            data.explosionScaleBoost = 0;
            this.assignHaunt(data, width, height, now);
        }
        updateGhostMotion(data, deltaTime, now, width, height) {
            if (now >= data.nextHauntShift) {
                this.assignHaunt(data, width, height, now);
            }
            data.hauntAngle += data.hauntAngularSpeed * deltaTime * 60;
            data.target.set(data.hauntCenter.x + Math.cos(data.hauntAngle) * data.hauntRadius, data.hauntCenter.y + Math.sin(data.hauntAngle) * data.hauntRadius, 0);
            const direction = this.tmpVec3.copy(data.target).sub(data.position);
            const distance = direction.length();
            if (distance > 0.001) {
                direction.normalize();
            }
            const bobTime = now * 0.001 * data.bobSpeed + data.swayOffset;
            const orbitalSpeed = data.baseSpeed * (0.75 + Math.sin(bobTime * 0.75) * 0.25);
            this.tmpVec3b.copy(direction).multiplyScalar(orbitalSpeed);
            data.velocity.lerp(this.tmpVec3b, 0.06);
            data.position.addScaledVector(data.velocity, deltaTime * 60);
            const lateralDrift = Math.cos(bobTime) * data.bobStrength * 0.035;
            const verticalDrift = Math.sin(bobTime * 1.3) * data.bobStrength * 0.045;
            data.position.x += lateralDrift * deltaTime * 60;
            data.position.y += verticalDrift * deltaTime * 60;
        }
        updateGhostExplosion(data, deltaTime, now, width, height) {
            data.explosionElapsed += deltaTime;
            const duration = Math.max(0.001, data.explosionDuration);
            const progress = Math.min(1, data.explosionElapsed / duration);
            const easedProgress = 1 - Math.pow(1 - progress, 2);
            const scaleBoost = 1 + easedProgress * data.explosionScaleBoost;
            data.scale = data.baseScale * scaleBoost;
            data.fade = Math.pow(Math.max(0, 1 - progress), this.explosionFadeExponent);
            const velocityFalloff = Math.max(0, 1 - progress * 0.85);
            if (velocityFalloff > 0) {
                data.position.addScaledVector(data.explosionVelocity, deltaTime * 60 * velocityFalloff);
            }
            if (progress >= 1) {
                data.fade = 0;
                data.scale = data.baseScale;
                data.state = "hidden";
                data.reappearAt = now + this.minReappearDelay + Math.random() * this.maxAdditionalReappearDelay;
                this.prepareGhostForRespawn(data, width, height, now);
            }
        }
        wrapPosition(data, width, height, now) {
            const halfWidth = width / 2;
            const halfHeight = height / 2;
            const margin = this.computeSpawnMargin(width, height);
            let wrapped = false;
            if (data.position.x < -halfWidth - margin) {
                data.position.x = halfWidth + margin;
                wrapped = true;
            }
            else if (data.position.x > halfWidth + margin) {
                data.position.x = -halfWidth - margin;
                wrapped = true;
            }
            if (data.position.y < -halfHeight - margin) {
                data.position.y = halfHeight + margin;
                wrapped = true;
            }
            else if (data.position.y > halfHeight + margin) {
                data.position.y = -halfHeight - margin;
                wrapped = true;
            }
            if (wrapped) {
                this.assignHaunt(data, width, height, now);
            }
        }
        triggerGhostExplosion(index, now, origin, particles) {
            const data = particles ? particles[index] : undefined;
            if (!data) {
                return;
            }
            if (data.state !== "visible" || data.cooldown > 0) {
                return;
            }
            data.state = "exploding";
            data.cooldown = 0.6;
            data.nextPhaseAt = now + data.phaseInterval;
            data.explosionElapsed = 0;
            data.explosionDuration = this.randomExplosionDuration();
            data.explosionScaleBoost = this.randomExplosionScaleBoost();
            data.velocity.set(0, 0, 0);
            const explosionSpeed = this.randomExplosionSpeed(data.baseSpeed);
            let velocityX;
            let velocityY;
            if (origin) {
                const dx = data.position.x - origin.x;
                const dy = data.position.y - origin.y;
                const distance = Math.hypot(dx, dy);
                if (distance > 0.0001) {
                    const randomOffset = (Math.random() - 0.5) * 0.6;
                    const sinOffset = Math.sin(randomOffset);
                    const cosOffset = Math.cos(randomOffset);
                    const normX = dx / distance;
                    const normY = dy / distance;
                    velocityX = normX * cosOffset - normY * sinOffset;
                    velocityY = normX * sinOffset + normY * cosOffset;
                }
                else {
                    const angle = Math.random() * Math.PI * 2;
                    velocityX = Math.cos(angle);
                    velocityY = Math.sin(angle);
                }
            }
            else {
                const angle = Math.random() * Math.PI * 2;
                velocityX = Math.cos(angle);
                velocityY = Math.sin(angle);
            }
            data.explosionVelocity.set(velocityX * explosionSpeed, velocityY * explosionSpeed, 0);
            data.fade = 1;
            data.scale = data.baseScale;
        }
        findClosestVisibleParticle(particles, position, radius) {
            let closestIndex = null;
            let closestDistanceSq = radius * radius;
            for (let i = 0; i < particles.length; i++) {
                const data = particles[i];
                if (data.state !== "visible" || data.cooldown > 0) {
                    continue;
                }
                this.tmpVec2.set(data.position.x, data.position.y);
                const distanceSq = this.tmpVec2.distanceToSquared(position);
                if (distanceSq < closestDistanceSq) {
                    closestDistanceSq = distanceSq;
                    closestIndex = i;
                }
            }
            return closestIndex;
        }
        onParticleAtlasIndexChanged(_data) {
            // Subclasses can hook into atlas updates.
        }
        isEngineReady() {
            return true;
        }
    }
    /**
     * Entry point that wires the Halloween ghost effect into one or more containers.
     * Chooses a WebGL instanced renderer when available and transparently falls back
     * to the 2D canvas engine while sharing the same particle simulation.
     */
    class Ghosts {
        options;
        engines = [];
        containers;
        isActive;
        /**
         * Initializes the ghosts effect and starts loading the sprite atlas.
         *
         * @param options - Configuration options for the ghosts effect.
         * @param containers - Optional CSS selector, single HTMLElement, NodeList, or array of HTMLElements
         *   where the effect should render. Defaults to the document body when omitted.
         */
        constructor(options, containers) {
            this.options = {
                num: 50,
                speed: 1,
                minScale: 70,
                maxScale: 130,
                enableMobile: true,
                fadeScroll: false,
                opacity: 1,
                enableInteraction: true,
                ...options,
            };
            if (!this.options.enableMobile && Environment.platform() !== "desktop") {
                return;
            }
            this.isActive = true;
            this.containers = this.normalizeContainers(containers);
            if (this.containers.length === 0) {
                console.warn("Ghosts: No valid container elements found. Ghosts effect will not be applied.");
                return;
            }
            this.init();
        }
        /**
         * Normalizes the container input to an array of HTMLElements.
         *
         * @param containers - A selector, element, NodeList, or array describing where to attach the effect.
         * @returns A filtered array of HTMLElements, or the document body when no input was provided.
         */
        normalizeContainers(containers) {
            if (!containers) {
                return [document.body];
            }
            if (typeof containers === "string") {
                const nodeList = document.querySelectorAll(containers);
                return Array.from(nodeList);
            }
            if (containers instanceof HTMLElement) {
                return [containers];
            }
            if (containers instanceof NodeList) {
                return Array.from(containers);
            }
            if (Array.isArray(containers)) {
                return containers.filter((el) => el instanceof HTMLElement);
            }
            return [];
        }
        /**
         * Initializes the ghosts effect by loading the texture atlas and registering resize/visibility listeners.
         */
        init() {
            const imageSrc = `${window.WCF_PATH}images/ghosts-texture-atlas.png`;
            const image = new Image();
            image.src = imageSrc;
            image.onload = () => {
                this.createEngines(image);
            };
            image.onerror = (error) => {
                console.error("Error loading image:", error);
            };
            window.addEventListener("resize", this.onResize);
            document.addEventListener("visibilitychange", this.onVisibilityChange);
            this.onVisibilityChange();
        }
        /**
         * Handles visibility change events to pause or resume the animation based on document visibility.
         */
        onVisibilityChange = () => {
            this.isActive = !document.hidden;
            this.engines.forEach((engine) => engine.handleVisibilityChange());
        };
        /**
         * Creates an engine per container, preferring WebGL when available and falling back to the canvas renderer.
         *
         * @param image - Loaded image to use as texture atlas.
         */
        createEngines(image) {
            const webglSupported = GhostsEngine.isWebGLSupported();
            if (this.options.num > 0) {
                this.containers.forEach((container) => {
                    const engine = webglSupported
                        ? new GhostsEngine(this.options.opacity, container, this.options.fadeScroll, this.options.enableInteraction)
                        : new CanvasGhostsEngine(this.options.opacity, container, this.options.fadeScroll, this.options.enableInteraction);
                    engine.create();
                    engine.generateParticles(image, this.options.num, this.options.speed, this.options.minScale, this.options.maxScale);
                    engine.startAnimation(() => this.isActive);
                    this.engines.push(engine);
                });
            }
            else {
                console.warn("Invalid number of particles specified.");
            }
        }
        /**
         * Handles window resize events and updates the size of all renderers.
         */
        onResize = () => {
            this.engines.forEach((engine) => engine.updateSize());
        };
    }
    exports.Ghosts = Ghosts;
    /**
     * WebGL renderer that draws ghosts via instanced meshes while the
     * shared base class handles particle behaviour and interactions.
     */
    class GhostsEngine extends BaseGhostEngine {
        particlesData = [];
        camera;
        scene;
        renderer;
        wrapper;
        instancedMesh;
        textureAtlas;
        uvIndexArray;
        uvIndexAttribute;
        uvIndexDirty = false;
        fadeArray;
        fadeAttribute;
        rotationArray;
        rotationAttribute;
        orientationArray;
        orientationAttribute;
        mousePosition = new THREE.Vector2(Infinity, Infinity);
        /**
         * Detects whether the current environment supports creating a WebGL context.
         *
         * @returns True when a WebGL rendering context can be created.
         */
        static isWebGLSupported() {
            if (typeof window === "undefined") {
                return true;
            }
            try {
                const canvas = document.createElement("canvas");
                const context = canvas.getContext("webgl2") ||
                    canvas.getContext("webgl") ||
                    canvas.getContext("experimental-webgl");
                return context !== null;
            }
            catch {
                return false;
            }
        }
        /**
         * Creates a new WebGL renderer instance for the ghosts effect.
         *
         * @param baseOpacity - Base opacity value for the ghosts particles.
         * @param container - The DOM element where the ghosts effect will be rendered.
         * @param fadeScroll - Whether to enable fading based on scroll position.
         * @param enableInteraction - Whether to enable particle interactions.
         */
        constructor(baseOpacity, container, fadeScroll, enableInteraction) {
            super(baseOpacity, container, fadeScroll, enableInteraction);
        }
        /**
         * Initializes the Three.js scene and attaches the renderer to the DOM.
         */
        create() {
            const isBody = this.container === document.body;
            const { width, height } = this.measureContainer();
            const left = -width / 2;
            const right = width / 2;
            const top = height / 2;
            const bottom = -height / 2;
            this.camera = new THREE.OrthographicCamera(left, right, top, bottom, -1000, 1000);
            this.camera.position.z = 0;
            this.scene = new THREE.Scene();
            this.renderer = new THREE.WebGLRenderer({
                alpha: true,
                antialias: false,
                powerPreference: "high-performance",
            });
            this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
            this.updateSize();
            this.wrapper = document.createElement("div");
            this.wrapper.classList.add("scHalloweenGhosts");
            this.wrapper.style.cssText = `
      position: ${isBody ? "fixed" : "absolute"};
      left: 0px;
      top: 0px;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 299;
      opacity: ${this.baseOpacity};
      overflow: hidden;
    `;
            this.wrapper.appendChild(this.renderer.domElement);
            if (!isBody && getComputedStyle(this.container).position === "static") {
                this.container.style.position = "relative";
            }
            this.container.appendChild(this.wrapper);
            document.addEventListener("visibilitychange", this.onVisibilityChange);
            if (this.enableInteraction) {
                if (this.isMobile) {
                    window.addEventListener("touchstart", this.onTouchStart, { passive: true });
                }
                else {
                    window.addEventListener("mousemove", this.onMouseMove);
                    window.addEventListener("mouseleave", this.onMouseLeave);
                    window.addEventListener("click", this.onClick);
                }
            }
        }
        /**
         * Updates the size of the renderer and camera based on the container size.
         */
        updateSize() {
            const { width, height } = this.measureContainer();
            this.isMobile = this.detectMobile();
            this.width = width;
            this.height = height;
            this.renderer.setSize(width, height, true);
            this.camera.left = -width / 2;
            this.camera.right = width / 2;
            this.camera.top = height / 2;
            this.camera.bottom = -height / 2;
            this.camera.updateProjectionMatrix();
        }
        /**
         * Generates particles using an instanced mesh and adds them to the scene.
         *
         * @param image - HTMLImageElement to use as texture atlas for the particles.
         * @param num - Number of particles to generate.
         * @param speed - Base speed of the particles.
         * @param minScale - Minimum scale of the particles (in pixels).
         * @param maxScale - Maximum scale of the particles (in pixels).
         */
        generateParticles(image, num, speed, minScale, maxScale) {
            let { width, height } = this.measureContainer();
            if (!width || !height) {
                width = this.width || 1;
                height = this.height || 1;
            }
            const textureLoader = new THREE.TextureLoader();
            this.isMobile = this.detectMobile();
            this.width = width;
            this.height = height;
            this.textureAtlas = textureLoader.load(image.src);
            this.textureAtlas.magFilter = THREE.LinearFilter;
            this.textureAtlas.minFilter = THREE.LinearFilter;
            const geometry = new THREE.PlaneGeometry(1, 1);
            const material = new THREE.ShaderMaterial({
                uniforms: {
                    map: { value: this.textureAtlas },
                    atlasRows: { value: this.atlasRows },
                    atlasColumns: { value: this.atlasColumns },
                },
                vertexShader: this.vertexShader(),
                fragmentShader: this.fragmentShader(),
                transparent: true,
                depthTest: false,
            });
            this.instancedMesh = new THREE.InstancedMesh(geometry, material, num);
            this.instancedMesh.frustumCulled = false;
            this.baseSpeedSetting = speed;
            this.minScaleSetting = minScale;
            this.maxScaleSetting = maxScale;
            this.uvIndexArray = new Float32Array(num);
            this.uvIndexDirty = false;
            this.fadeArray = new Float32Array(num);
            this.rotationArray = new Float32Array(num);
            this.orientationArray = new Float32Array(num);
            const matrix = new THREE.Matrix4();
            const now = this.now();
            this.particlesData.length = 0;
            for (let i = 0; i < num; i++) {
                const scale = this.randomScale();
                const position = this.randomPosition(width, height, undefined, this.isMobile);
                const baseSpeed = this.randomBaseSpeed();
                const velocity = new THREE.Vector3((Math.random() - 0.5) * baseSpeed, (Math.random() - 0.5) * baseSpeed, 0);
                const phaseInterval = this.randomPhaseInterval(baseSpeed);
                const particleData = {
                    index: i,
                    position,
                    velocity,
                    baseScale: scale,
                    scale,
                    uvIndex: this.randomAtlasIndex(),
                    fade: Math.random() * 0.5 + 0.5,
                    rotation: 0,
                    swayOffset: Math.random() * Math.PI * 2,
                    baseSpeed,
                    target: new THREE.Vector3(),
                    bobSpeed: 0.6 + Math.random() * 0.8,
                    bobStrength: 8 + Math.random() * 10,
                    cooldown: Math.random() * 0.5,
                    reappearAt: now,
                    hauntCenter: new THREE.Vector3(),
                    hauntRadius: 0,
                    hauntAngle: 0,
                    hauntAngularSpeed: 0,
                    nextHauntShift: now,
                    phaseInterval,
                    nextPhaseAt: now + Math.random() * phaseInterval,
                    flickerOffset: Math.random() * Math.PI * 2,
                    orientation: this.randomOrientation(),
                    state: "visible",
                    explosionElapsed: 0,
                    explosionDuration: this.explosionDurationMin,
                    explosionVelocity: new THREE.Vector3(),
                    explosionScaleBoost: 0,
                };
                this.onParticleAtlasIndexChanged(particleData);
                this.assignHaunt(particleData, width, height, now);
                matrix.identity();
                matrix.makeScale(scale, scale, 1);
                matrix.setPosition(position);
                this.instancedMesh.setMatrixAt(i, matrix);
                this.fadeArray[i] = 1;
                this.rotationArray[i] = particleData.rotation;
                this.orientationArray[i] = particleData.orientation;
                this.particlesData.push(particleData);
            }
            this.uvIndexAttribute = new THREE.InstancedBufferAttribute(this.uvIndexArray, 1, false);
            this.instancedMesh.geometry.setAttribute("uvIndex", this.uvIndexAttribute);
            this.fadeAttribute = new THREE.InstancedBufferAttribute(this.fadeArray, 1, false);
            this.instancedMesh.geometry.setAttribute("fade", this.fadeAttribute);
            this.rotationAttribute = new THREE.InstancedBufferAttribute(this.rotationArray, 1, false);
            this.instancedMesh.geometry.setAttribute("rotation", this.rotationAttribute);
            this.orientationAttribute = new THREE.InstancedBufferAttribute(this.orientationArray, 1, false);
            this.instancedMesh.geometry.setAttribute("orientation", this.orientationAttribute);
            this.scene.add(this.instancedMesh);
            this.uvIndexDirty = false;
        }
        /**
         * Advances the particle simulation, handles pointer interactions, and renders the current frame.
         *
         * @param deltaTime - Seconds elapsed since the last frame.
         * @param now - High-resolution timestamp for the current frame.
         */
        onFrame(deltaTime, now) {
            const width = this.renderer.domElement.clientWidth;
            const height = this.renderer.domElement.clientHeight;
            this.width = width;
            this.height = height;
            const halfWidth = width / 2;
            const halfHeight = height / 2;
            const fadeMargin = 0.12;
            const matrix = new THREE.Matrix4();
            const pointerActive = this.enableInteraction && !this.isMobile && this.mousePosition.x !== Infinity;
            const hoverRadiusSq = this.pointerHoverRadius * this.pointerHoverRadius;
            const repulsionRadiusSq = this.pointerRepelRadius * this.pointerRepelRadius;
            for (let i = 0; i < this.particlesData.length; i++) {
                const data = this.particlesData[i];
                data.cooldown = Math.max(0, data.cooldown - deltaTime);
                if (data.state === "visible" && data.cooldown <= 0 && now >= data.nextPhaseAt) {
                    this.explodeParticle(i, now);
                }
                if (data.state === "exploding") {
                    this.updateGhostExplosion(data, deltaTime, now, width, height);
                }
                else if (data.state === "hidden") {
                    if (now >= data.reappearAt) {
                        data.state = "visible";
                        data.cooldown = 0.3;
                        data.scale = data.baseScale;
                    }
                }
                else {
                    data.fade = Math.min(1, data.fade + deltaTime * this.appearFadeSpeed);
                    data.scale = data.baseScale;
                }
                if (data.state === "visible") {
                    this.updateGhostMotion(data, deltaTime, now, width, height);
                }
                if (pointerActive && data.state === "visible") {
                    this.tmpVec2.set(data.position.x, data.position.y);
                    const distanceSq = this.tmpVec2.distanceToSquared(this.mousePosition);
                    if (distanceSq < repulsionRadiusSq) {
                        const distance = Math.sqrt(distanceSq) || 0.0001;
                        const intensity = 1 - Math.min(distance / this.pointerRepelRadius, 1);
                        const pushStrength = intensity * (24 + data.baseSpeed * 36) * deltaTime;
                        this.tmpVec2b
                            .set(data.position.x - this.mousePosition.x, data.position.y - this.mousePosition.y)
                            .multiplyScalar(pushStrength / distance);
                        data.position.x += this.tmpVec2b.x;
                        data.position.y += this.tmpVec2b.y;
                    }
                    if (distanceSq < hoverRadiusSq && data.cooldown <= 0) {
                        this.explodeParticle(i, now, this.mousePosition);
                    }
                }
                if (data.state === "visible") {
                    this.wrapPosition(data, width, height, now);
                }
                matrix.identity();
                matrix.makeRotationZ(data.rotation);
                this.tmpVec3.set(data.scale, data.scale, 1);
                matrix.scale(this.tmpVec3);
                matrix.setPosition(data.position);
                this.instancedMesh.setMatrixAt(i, matrix);
                this.rotationArray[i] = data.rotation;
                this.orientationArray[i] = data.orientation;
                let fadeX = 1;
                let fadeY = 1;
                const normalizedX = (data.position.x + halfWidth) / width;
                const normalizedY = (data.position.y + halfHeight) / height;
                if (normalizedX < fadeMargin) {
                    fadeX = normalizedX / fadeMargin;
                }
                else if (normalizedX > 1 - fadeMargin) {
                    fadeX = (1 - normalizedX) / fadeMargin;
                }
                if (normalizedY < fadeMargin) {
                    fadeY = normalizedY / fadeMargin;
                }
                else if (normalizedY > 1 - fadeMargin) {
                    fadeY = (1 - normalizedY) / fadeMargin;
                }
                const borderFade = Math.max(0, Math.min(fadeX, fadeY, 1));
                const flicker = 0.82 + Math.sin(now * 0.0015 * this.flickerSpeed + data.flickerOffset) * 0.18;
                this.fadeArray[i] = data.fade * borderFade * flicker;
            }
            this.instancedMesh.instanceMatrix.needsUpdate = true;
            this.fadeAttribute.needsUpdate = true;
            this.rotationAttribute.needsUpdate = true;
            this.orientationAttribute.needsUpdate = true;
            if (this.uvIndexDirty) {
                this.uvIndexAttribute.needsUpdate = true;
                this.uvIndexDirty = false;
            }
            if (this.fadeScroll) {
                this.updateOpacity();
            }
            this.renderer.render(this.scene, this.camera);
        }
        onParticleAtlasIndexChanged(data) {
            if (this.uvIndexArray) {
                this.uvIndexArray[data.index] = data.uvIndex;
                this.uvIndexDirty = true;
            }
        }
        isEngineReady() {
            return Boolean(this.instancedMesh);
        }
        explodeParticle(index, now, origin) {
            super.triggerGhostExplosion(index, now, origin, this.particlesData);
        }
        explodeClosestGhost(position, radius, now) {
            const closestIndex = this.findClosestVisibleParticle(this.particlesData, position, radius);
            if (closestIndex !== null) {
                this.explodeParticle(closestIndex, now, position);
            }
        }
        getWorldPosition(clientX, clientY) {
            const rect = this.renderer.domElement.getBoundingClientRect();
            const x = ((clientX - rect.left) / rect.width) * 2 - 1;
            const y = -((clientY - rect.top) / rect.height) * 2 + 1;
            const pointer = new THREE.Vector3(x, y, 0.5);
            pointer.unproject(this.camera);
            return new THREE.Vector2(pointer.x, pointer.y);
        }
        /**
         * Updates the opacity of the ghosts effect based on the scroll position.
         */
        updateOpacity() {
            const isBody = this.container === document.body;
            let opacity;
            if (this.baseOpacity <= 0) {
                opacity = 0;
            }
            else {
                let scrollPosition;
                let totalScrollableHeight;
                if (isBody) {
                    scrollPosition = window.scrollY;
                    totalScrollableHeight = document.body.scrollHeight - window.innerHeight;
                }
                else {
                    scrollPosition = this.container.scrollTop;
                    totalScrollableHeight = this.container.scrollHeight - this.container.clientHeight;
                }
                const fadeStartPercentage = 0.2;
                const fadeEndPercentage = 0.6;
                const fadeStart = totalScrollableHeight * fadeStartPercentage;
                const fadeEnd = totalScrollableHeight * fadeEndPercentage;
                if (scrollPosition <= fadeStart) {
                    opacity = this.baseOpacity;
                }
                else if (scrollPosition >= fadeEnd) {
                    opacity = 0.1;
                }
                else {
                    const fadeRange = fadeEnd - fadeStart;
                    const fadeProgress = (scrollPosition - fadeStart) / fadeRange;
                    opacity = this.baseOpacity - (this.baseOpacity - 0.1) * fadeProgress;
                }
            }
            opacity = Math.max(0.1, Math.min(opacity, this.baseOpacity));
            this.wrapper.style.opacity = opacity.toString();
        }
        /**
         * Vertex shader handling texture atlas UV mapping, rotation, and fade effects.
         *
         * @returns The GLSL code for the vertex shader.
         */
        vertexShader() {
            return `
      attribute float uvIndex;
      attribute float fade;
      attribute float rotation;
      attribute float orientation;
      uniform float atlasRows;
      uniform float atlasColumns;
      varying vec2 vUv;
      varying float vFade;

      void main() {
        vec2 baseUv = uv;
        if (orientation < 0.0) {
          baseUv.x = 1.0 - baseUv.x;
        }

        float index = uvIndex;
        float column = mod(index, atlasColumns);
        float row = atlasRows - 1.0 - floor(index / atlasColumns);

        vec2 uvOffset = vec2(column / atlasColumns, row / atlasRows);
        vec2 uvScale = vec2(1.0 / atlasColumns, 1.0 / atlasRows);

        vUv = baseUv * uvScale + uvOffset;
        vFade = fade;

        float cosRot = cos(rotation);
        float sinRot = sin(rotation);
        mat2 rotationMatrix = mat2(cosRot, -sinRot, sinRot, cosRot);
        vec3 rotatedPosition = vec3(rotationMatrix * position.xy, position.z);

        gl_Position = projectionMatrix * modelViewMatrix * instanceMatrix * vec4(rotatedPosition, 1.0);
      }
    `;
        }
        /**
         * Fragment shader for rendering the texture atlas with fade effects.
         *
         * @returns The GLSL code for the fragment shader.
         */
        fragmentShader() {
            return `
      uniform sampler2D map;
      varying vec2 vUv;
      varying float vFade;

      void main() {
        vec4 color = texture2D(map, vUv);
        color.a *= vFade;
        if (color.a < 0.01) discard;
        gl_FragColor = color;
      }
    `;
        }
        /**
         * Handles visibility change events.
         */
        onVisibilityChange = () => {
            this.handleVisibilityChange();
        };
        /**
         * Handles mouse move events.
         */
        onMouseMove = (event) => {
            const rect = this.renderer.domElement.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            const y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
            const mouseVector = new THREE.Vector3(x, y, 0.5);
            mouseVector.unproject(this.camera);
            this.mousePosition.set(mouseVector.x, mouseVector.y);
        };
        /**
         * Handles mouse leave events.
         */
        onMouseLeave = () => {
            this.mousePosition.set(Infinity, Infinity);
        };
        /**
         * Handles touch start events.
         */
        onTouchStart = (event) => {
            if (!this.enableInteraction || event.touches.length === 0) {
                return;
            }
            const touch = event.touches[0];
            if (!touch) {
                return;
            }
            const worldPosition = this.getWorldPosition(touch.clientX, touch.clientY);
            this.explodeClosestGhost(worldPosition, this.pointerClickRadius, this.now());
        };
        /**
         * Handles click events.
         */
        onClick = (event) => {
            if (!this.enableInteraction) {
                return;
            }
            const worldPosition = this.getWorldPosition(event.clientX, event.clientY);
            this.explodeClosestGhost(worldPosition, this.pointerClickRadius, this.now());
        };
    }
    /**
     * Canvas renderer that mirrors the WebGL engine's particle updates while drawing
     * sprites through the 2D context for browsers without WebGL support.
     */
    class CanvasGhostsEngine extends BaseGhostEngine {
        canvas = null;
        context = null;
        wrapper = null;
        pixelRatio = 1;
        image = null;
        particles = [];
        pointerPosition = new THREE.Vector2(Infinity, Infinity);
        isReady = false;
        constructor(baseOpacity, container, fadeScroll, enableInteraction) {
            super(baseOpacity, container, fadeScroll, enableInteraction);
        }
        create() {
            const isBody = this.container === document.body;
            const { width, height } = this.measureContainer();
            this.canvas = document.createElement("canvas");
            const context = this.canvas.getContext("2d");
            if (!context) {
                console.warn("Ghosts: Canvas 2D context not available. Ghosts effect will not be applied.");
                return;
            }
            this.context = context;
            this.pixelRatio = Math.min(window.devicePixelRatio || 1, 1.5);
            this.wrapper = document.createElement("div");
            this.wrapper.classList.add("scHalloweenGhosts");
            this.wrapper.style.cssText = `
      position: ${isBody ? "fixed" : "absolute"};
      left: 0px;
      top: 0px;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 299;
      opacity: ${this.baseOpacity};
      overflow: hidden;
    `;
            if (!isBody && getComputedStyle(this.container).position === "static") {
                this.container.style.position = "relative";
            }
            this.wrapper.appendChild(this.canvas);
            this.container.appendChild(this.wrapper);
            this.width = width;
            this.height = height;
            this.updateSize();
            document.addEventListener("visibilitychange", this.onVisibilityChange);
            if (this.enableInteraction) {
                if (this.isMobile) {
                    window.addEventListener("touchstart", this.onTouchStart, { passive: true });
                }
                else {
                    window.addEventListener("mousemove", this.onMouseMove);
                    window.addEventListener("mouseleave", this.onMouseLeave);
                    window.addEventListener("click", this.onClick);
                }
            }
            this.isReady = true;
        }
        generateParticles(image, num, speed, minScale, maxScale) {
            if (!this.isReady || !this.canvas) {
                return;
            }
            this.image = image;
            this.particles = [];
            let { width, height } = this.measureContainer();
            if (!width || !height) {
                width = this.width || 1;
                height = this.height || 1;
            }
            this.width = width;
            this.height = height;
            this.isMobile = this.detectMobile();
            this.baseSpeedSetting = speed;
            this.minScaleSetting = minScale;
            this.maxScaleSetting = maxScale;
            this.updateSize();
            const now = this.now();
            for (let i = 0; i < num; i++) {
                const particle = {
                    index: i,
                    position: new THREE.Vector3(),
                    velocity: new THREE.Vector3(),
                    baseScale: 0,
                    scale: 0,
                    uvIndex: 0,
                    fade: 0,
                    rotation: 0,
                    swayOffset: 0,
                    baseSpeed: 0,
                    target: new THREE.Vector3(),
                    bobSpeed: 0,
                    bobStrength: 0,
                    cooldown: Math.random() * 0.5,
                    reappearAt: now,
                    hauntCenter: new THREE.Vector3(),
                    hauntRadius: 0,
                    hauntAngle: 0,
                    hauntAngularSpeed: 0,
                    nextHauntShift: now,
                    phaseInterval: 0,
                    nextPhaseAt: now,
                    flickerOffset: 0,
                    orientation: 1,
                    state: "visible",
                    explosionElapsed: 0,
                    explosionDuration: this.explosionDurationMin,
                    explosionVelocity: new THREE.Vector3(),
                    explosionScaleBoost: 0,
                };
                this.prepareGhostForRespawn(particle, width, height, now);
                particle.fade = Math.random() * 0.5 + 0.5;
                particle.cooldown = Math.random() * 0.5;
                particle.nextPhaseAt = now + Math.random() * particle.phaseInterval;
                this.particles.push(particle);
            }
        }
        updateSize() {
            if (!this.canvas) {
                return;
            }
            const { width, height } = this.measureContainer();
            this.width = width;
            this.height = height;
            this.isMobile = this.detectMobile();
            this.pixelRatio = Math.min(window.devicePixelRatio || 1, 1.5);
            this.canvas.width = Math.max(1, Math.floor(width * this.pixelRatio));
            this.canvas.height = Math.max(1, Math.floor(height * this.pixelRatio));
            this.canvas.style.width = `${width}px`;
            this.canvas.style.height = `${height}px`;
        }
        onFrame(deltaTime, now) {
            if (!this.canvas || !this.context) {
                return;
            }
            const width = this.canvas.clientWidth || this.width;
            const height = this.canvas.clientHeight || this.height;
            this.width = width;
            this.height = height;
            const halfWidth = width / 2;
            const halfHeight = height / 2;
            const fadeMargin = 0.12;
            const pointerActive = this.enableInteraction && !this.isMobile && this.pointerPosition.x !== Infinity;
            const hoverRadiusSq = this.pointerHoverRadius * this.pointerHoverRadius;
            const repulsionRadiusSq = this.pointerRepelRadius * this.pointerRepelRadius;
            this.context.clearRect(0, 0, this.canvas.width, this.canvas.height);
            for (let i = 0; i < this.particles.length; i++) {
                const data = this.particles[i];
                data.cooldown = Math.max(0, data.cooldown - deltaTime);
                if (data.state === "visible" && data.cooldown <= 0 && now >= data.nextPhaseAt) {
                    this.explodeParticle(i, now);
                }
                if (data.state === "exploding") {
                    this.updateGhostExplosion(data, deltaTime, now, width, height);
                }
                else if (data.state === "hidden") {
                    if (now >= data.reappearAt) {
                        data.state = "visible";
                        data.cooldown = 0.3;
                        data.scale = data.baseScale;
                    }
                }
                else {
                    data.fade = Math.min(1, data.fade + deltaTime * this.appearFadeSpeed);
                    data.scale = data.baseScale;
                }
                if (data.state === "visible") {
                    this.updateGhostMotion(data, deltaTime, now, width, height);
                }
                if (pointerActive && data.state === "visible") {
                    this.tmpVec2.set(data.position.x, data.position.y);
                    const distanceSq = this.tmpVec2.distanceToSquared(this.pointerPosition);
                    if (distanceSq < repulsionRadiusSq) {
                        const distance = Math.sqrt(distanceSq) || 0.0001;
                        const intensity = 1 - Math.min(distance / this.pointerRepelRadius, 1);
                        const pushStrength = intensity * (24 + data.baseSpeed * 36) * deltaTime;
                        this.tmpVec2b
                            .set(data.position.x - this.pointerPosition.x, data.position.y - this.pointerPosition.y)
                            .multiplyScalar(pushStrength / distance);
                        data.position.x += this.tmpVec2b.x;
                        data.position.y += this.tmpVec2b.y;
                    }
                    if (distanceSq < hoverRadiusSq && data.cooldown <= 0) {
                        this.explodeParticle(i, now, this.pointerPosition);
                    }
                }
                if (data.state === "visible") {
                    this.wrapPosition(data, width, height, now);
                }
                const drawAlpha = this.computeDrawAlpha(data, now, width, height, halfWidth, halfHeight, fadeMargin);
                if (!this.image || drawAlpha <= 0.01) {
                    continue;
                }
                const drawScale = data.scale * this.pixelRatio;
                const drawX = (data.position.x + halfWidth) * this.pixelRatio;
                const drawY = (data.position.y + halfHeight) * this.pixelRatio;
                const atlasWidth = this.image.width / this.atlasColumns;
                const atlasHeight = this.image.height / this.atlasRows;
                const column = data.uvIndex % this.atlasColumns;
                const row = this.atlasRows - 1 - Math.floor(data.uvIndex / this.atlasColumns);
                const sx = column * atlasWidth;
                const sy = row * atlasHeight;
                this.context.save();
                this.context.globalAlpha = drawAlpha;
                this.context.translate(drawX, drawY);
                this.context.rotate(data.rotation);
                this.context.scale(data.orientation, 1);
                this.context.drawImage(this.image, sx, sy, atlasWidth, atlasHeight, -drawScale / 2, -drawScale / 2, drawScale, drawScale);
                this.context.restore();
            }
            if (this.fadeScroll) {
                this.updateOpacity();
            }
        }
        isEngineReady() {
            return this.isReady && !!this.canvas && !!this.context;
        }
        computeDrawAlpha(data, now, width, height, halfWidth, halfHeight, fadeMargin) {
            if (!this.image) {
                return 0;
            }
            const normalizedX = (data.position.x + halfWidth) / width;
            const normalizedY = (data.position.y + halfHeight) / height;
            let fadeX = 1;
            let fadeY = 1;
            if (normalizedX < fadeMargin) {
                fadeX = normalizedX / fadeMargin;
            }
            else if (normalizedX > 1 - fadeMargin) {
                fadeX = (1 - normalizedX) / fadeMargin;
            }
            if (normalizedY < fadeMargin) {
                fadeY = normalizedY / fadeMargin;
            }
            else if (normalizedY > 1 - fadeMargin) {
                fadeY = (1 - normalizedY) / fadeMargin;
            }
            const borderFade = Math.max(0, Math.min(fadeX, fadeY, 1));
            const flicker = 0.82 + Math.sin(now * 0.0015 * this.flickerSpeed + data.flickerOffset) * 0.18;
            return data.fade * borderFade * flicker;
        }
        explodeParticle(index, now, origin) {
            super.triggerGhostExplosion(index, now, origin, this.particles);
        }
        explodeClosestGhost(position, radius, now) {
            const closestIndex = this.findClosestVisibleParticle(this.particles, position, radius);
            if (closestIndex !== null) {
                this.explodeParticle(closestIndex, now, position);
            }
        }
        updateOpacity() {
            if (!this.wrapper) {
                return;
            }
            const isBody = this.container === document.body;
            let opacity;
            if (isBody) {
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                const windowHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                const fadeStart = windowHeight * 0.1;
                const fadeEnd = windowHeight * 0.3;
                if (scrollTop <= fadeStart) {
                    opacity = this.baseOpacity;
                }
                else if (scrollTop >= fadeEnd) {
                    opacity = 0;
                }
                else {
                    const progress = (scrollTop - fadeStart) / (fadeEnd - fadeStart);
                    opacity = this.baseOpacity * (1 - progress);
                }
            }
            else {
                const rect = this.container.getBoundingClientRect();
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
                const elementTop = rect.top;
                const elementBottom = rect.bottom;
                const elementHeight = rect.height || 1;
                if (elementBottom <= 0 || elementTop >= viewportHeight) {
                    opacity = 0;
                }
                else {
                    const visibleHeight = Math.min(viewportHeight, elementBottom) - Math.max(0, elementTop);
                    opacity = this.baseOpacity * Math.max(0, Math.min(1, visibleHeight / elementHeight));
                }
            }
            this.wrapper.style.opacity = opacity.toString();
        }
        clientToWorld(clientX, clientY) {
            if (!this.canvas) {
                return new THREE.Vector2();
            }
            const rect = this.canvas.getBoundingClientRect();
            const x = ((clientX - rect.left) / rect.width) * this.canvas.width - this.canvas.width / 2;
            const y = ((clientY - rect.top) / rect.height) * this.canvas.height - this.canvas.height / 2;
            return new THREE.Vector2(x / this.pixelRatio, y / this.pixelRatio);
        }
        onVisibilityChange = () => {
            this.handleVisibilityChange();
        };
        onMouseMove = (event) => {
            if (!this.canvas) {
                return;
            }
            const rect = this.canvas.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * this.canvas.width - this.canvas.width / 2;
            const y = ((event.clientY - rect.top) / rect.height) * this.canvas.height - this.canvas.height / 2;
            this.pointerPosition.set(x / this.pixelRatio, y / this.pixelRatio);
        };
        onMouseLeave = () => {
            this.pointerPosition.set(Infinity, Infinity);
        };
        onClick = (event) => {
            if (!this.enableInteraction) {
                return;
            }
            const position = this.clientToWorld(event.clientX, event.clientY);
            this.explodeClosestGhost(position, this.pointerClickRadius, this.now());
        };
        onTouchStart = (event) => {
            if (!this.enableInteraction || event.touches.length === 0) {
                return;
            }
            const touch = event.touches[0];
            if (!touch) {
                return;
            }
            const position = this.clientToWorld(touch.clientX, touch.clientY);
            this.explodeClosestGhost(position, this.pointerClickRadius, this.now());
        };
    }
});
