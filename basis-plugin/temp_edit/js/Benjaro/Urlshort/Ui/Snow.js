/**
 * @module Benjaro/Urlshort/Ui/Snow
 */
define(["require", "exports", "tslib", "Benjaro/Urlshort/3rdParty/three.min", "WoltLabSuite/Core/Environment"], function (require, exports, tslib_1, THREE, Environment) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.Snow = void 0;
    THREE = tslib_1.__importStar(THREE);
    Environment = tslib_1.__importStar(Environment);
    /**
     * Main class for creating a snow effect using Three.js.
     * Handles initialization, image loading, and particle generation.
     */
    class ScSnow {
        options;
        engines = [];
        containers;
        isActive;
        /**
         * Initializes the snow effect.
         *
         * @param options - Configuration options for the snow effect.
         * @param containers - Optional CSS selector, single HTMLElement, NodeList, or array of HTMLElements where the effect will be rendered.
         */
        constructor(options, containers) {
            this.options = {
                num: 50,
                speed: 0.3,
                minScale: 5,
                maxScale: 50,
                enableMobile: false,
                fadeScroll: true,
                opacity: 1,
                enableInteraction: true,
                mode: "simple",
                direction: "down",
                ...options,
            };
            if (!this.options.enableMobile && Environment.platform() !== "desktop") {
                return;
            }
            this.isActive = true;
            this.containers = this.normalizeContainers(containers);
            if (this.containers.length === 0) {
                console.warn("Snow: No valid container elements found. Snow effect will not be applied.");
                return;
            }
            this.init();
        }
        /**
         * Normalizes the container input to an array of HTMLElements.
         *
         * @param containers - The container parameter which can be a selector string, single HTMLElement, NodeList, or array of HTMLElements.
         * @returns An array of HTMLElements.
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
         * Initializes the snow effect by setting up the engines, loading resources, and registering event listeners.
         */
        init() {
            const imageSrc = `${window.WCF_PATH}images/snow-texture-atlas.png`;
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
         * Creates a SnowEngine instance for each container and generates particles.
         *
         * @param image - Loaded image to use as texture atlas.
         */
        createEngines(image) {
            if (this.options.num > 0) {
                this.containers.forEach((container) => {
                    const engine = new SnowEngine(this.options.opacity, container, this.options.fadeScroll, this.options.enableInteraction, this.options.mode, this.options.direction);
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
    exports.Snow = ScSnow;
    /**
     * Class responsible for rendering and managing the snow particles using
     * instanced meshes for efficient performance.
     */
    class SnowEngine {
        baseOpacity;
        container;
        fadeScroll;
        enableInteraction;
        mode;
        direction;
        isMobile;
        atlasRows = 10;
        atlasColumns = 20;
        totalAtlasImages = 200;
        particlesData = [];
        rayCaster = new THREE.Raycaster();
        camera;
        scene;
        renderer;
        wrapper;
        instancedMesh;
        textureAtlas;
        fadeArray;
        fadeAttribute;
        lastFrameTime = performance.now();
        /**
         * Constructs the SnowEngine.
         *
         * @param baseOpacity - Base opacity value for the snow particles.
         * @param container - The DOM element where the snow effect will be rendered.
         * @param fadeScroll - Whether to enable fading based on scroll position.
         * @param enableInteraction - Whether to enable particle interactions.
         * @param mode - Mode for particle images: 'simple' or 'all'.
         * @param direction - Direction of particle movement ('up' or 'down').
         */
        constructor(baseOpacity, container, fadeScroll, enableInteraction, mode, direction) {
            this.baseOpacity = baseOpacity;
            this.container = container;
            this.fadeScroll = fadeScroll;
            this.enableInteraction = enableInteraction;
            this.mode = mode;
            this.direction = direction;
            this.isMobile = Environment.platform() !== "desktop";
        }
        /**
         * Initializes the Three.js scene and attaches the renderer to the DOM.
         */
        create() {
            const isBody = this.container === document.body;
            const width = isBody ? window.innerWidth : this.container.clientWidth;
            const height = isBody ? window.innerHeight : this.container.clientHeight;
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
            this.wrapper.classList.add("snow");
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
                    window.addEventListener("touchend", this.onTouchEnd, { passive: false });
                }
                else {
                    window.addEventListener("click", this.onClick);
                }
            }
        }
        /**
         * Handles visibility change events.
         */
        handleVisibilityChange() {
            if (!document.hidden) {
                this.lastFrameTime = performance.now();
            }
        }
        /**
         * Updates the size of the renderer and camera based on the container size.
         */
        updateSize() {
            const isBody = this.container === document.body;
            const width = isBody ? window.innerWidth : this.container.clientWidth;
            const height = isBody ? window.innerHeight : this.container.clientHeight;
            this.renderer.setSize(width, height, false);
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
            const isBody = this.container === document.body;
            const width = isBody ? window.innerWidth : this.container.clientWidth;
            const height = isBody ? window.innerHeight : this.container.clientHeight;
            const textureLoader = new THREE.TextureLoader();
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
            const uvIndexArray = new Float32Array(num);
            this.fadeArray = new Float32Array(num);
            const matrix = new THREE.Matrix4();
            for (let i = 0; i < num; i++) {
                const scale = Math.random() * (maxScale - minScale) + minScale;
                const position = new THREE.Vector3(Math.random() * width - width / 2, Math.random() * height - height / 2, 0);
                const velocity = new THREE.Vector3((Math.random() - 0.5) * speed * 3, this.direction === "up" ? speed * 3 : -speed * 3, 0);
                const uvIndex = this.mode === "simple" ? 0 : Math.floor(Math.random() * (this.totalAtlasImages - 1)) + 1;
                const particleData = {
                    position,
                    velocity,
                    scale,
                    uvIndex,
                    fade: 1,
                    isFadingOut: false,
                };
                matrix.identity();
                matrix.makeScale(scale, scale, 1);
                matrix.setPosition(position);
                this.instancedMesh.setMatrixAt(i, matrix);
                uvIndexArray[i] = uvIndex;
                this.fadeArray[i] = 1;
                this.particlesData.push(particleData);
            }
            this.instancedMesh.geometry.setAttribute("uvIndex", new THREE.InstancedBufferAttribute(uvIndexArray, 1, false));
            this.fadeAttribute = new THREE.InstancedBufferAttribute(this.fadeArray, 1, false);
            this.instancedMesh.geometry.setAttribute("fade", this.fadeAttribute);
            this.scene.add(this.instancedMesh);
        }
        /**
         * Starts the animation loop.
         *
         * @param isActive - A function that returns whether the animation should continue.
         */
        startAnimation(isActive) {
            const animate = () => {
                if (isActive()) {
                    this.frame();
                }
                requestAnimationFrame(animate);
            };
            requestAnimationFrame(animate);
        }
        /**
         * Updates the position of the particles and renders the scene for each frame.
         */
        frame() {
            const now = performance.now();
            let deltaTime = (now - this.lastFrameTime) / 1000;
            this.lastFrameTime = now;
            deltaTime = Math.min(deltaTime, 0.033);
            const isBody = this.container === document.body;
            const width = isBody ? window.innerWidth : this.container.clientWidth;
            const height = isBody ? window.innerHeight : this.container.clientHeight;
            const halfWidth = width / 2;
            const halfHeight = height / 2;
            const fadeMargin = 0.05;
            const matrix = new THREE.Matrix4();
            let fadeUpdated = false;
            const fadeOutSpeed = 1; // Adjust this value to control fade-out speed (1 means 1 second to fade out)
            for (let i = 0; i < this.particlesData.length; i++) {
                const data = this.particlesData[i];
                data.position.x += data.velocity.x * deltaTime * 60;
                data.position.y += data.velocity.y * deltaTime * 60;
                // Handle fading out particles
                if (data.isFadingOut) {
                    data.fade -= fadeOutSpeed * deltaTime;
                    if (data.fade <= 0) {
                        // Reset the particle
                        this.resetParticle(data, width, height);
                    }
                    fadeUpdated = true;
                }
                // Reset position if particle is off-screen and not fading out
                if (!data.isFadingOut) {
                    this.resetPosition(data.position, width, height);
                }
                matrix.identity();
                matrix.makeScale(data.scale, data.scale, 1);
                matrix.setPosition(data.position);
                this.instancedMesh.setMatrixAt(i, matrix);
                // Edge fade
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
                const edgeFade = Math.max(0, Math.min(fadeX, fadeY, 1));
                this.fadeArray[i] = Math.min(data.fade, edgeFade);
                fadeUpdated = true;
            }
            this.instancedMesh.instanceMatrix.needsUpdate = true;
            if (fadeUpdated) {
                this.fadeAttribute.needsUpdate = true;
            }
            if (this.fadeScroll) {
                this.updateOpacity();
            }
            this.renderer.render(this.scene, this.camera);
        }
        /**
         * Resets the position of a particle if it goes out of bounds.
         *
         * @param position - The position of the particle.
         * @param width - Width of the container or window.
         * @param height - Height of the container or window.
         */
        resetPosition(position, width, height) {
            const halfWidth = width / 2;
            const halfHeight = height / 2;
            if (position.x > halfWidth) {
                position.x = -halfWidth;
            }
            else if (position.x < -halfWidth) {
                position.x = halfWidth;
            }
            if (position.y > halfHeight) {
                position.y = -halfHeight;
            }
            else if (position.y < -halfHeight) {
                position.y = halfHeight;
            }
        }
        /**
         * Resets a particle to a random position with default properties.
         *
         * @param data - The particle data.
         * @param width - Width of the container or window.
         * @param height - Height of the container or window.
         */
        resetParticle(data, width, height) {
            data.position.x = Math.random() * width - width / 2;
            data.position.y = Math.random() * height - height / 2;
            data.fade = 1;
            data.isFadingOut = false;
        }
        /**
         * Updates the opacity of the snow effect based on the scroll position.
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
         * Vertex shader handling texture atlas UV mapping and fade effects.
         *
         * @returns The GLSL code for the vertex shader.
         */
        vertexShader() {
            return `
      attribute float uvIndex;
      attribute float fade;
      uniform float atlasRows;
      uniform float atlasColumns;
      varying vec2 vUv;
      varying float vFade;

      void main() {
        vec2 baseUv = uv;

        float index = uvIndex;
        float column = mod(index, atlasColumns);
        float row = atlasRows - 1.0 - floor(index / atlasColumns);

        vec2 uvOffset = vec2(column / atlasColumns, row / atlasRows);
        vec2 uvScale = vec2(1.0 / atlasColumns, 1.0 / atlasRows);

        vUv = baseUv * uvScale + uvOffset;
        vFade = fade;

        gl_Position = projectionMatrix * modelViewMatrix * instanceMatrix * vec4(position, 1.0);
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
            if (!document.hidden) {
                this.lastFrameTime = performance.now();
            }
        };
        /**
         * Handles click events to fade out particles.
         */
        onClick = (event) => {
            const rect = this.renderer.domElement.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            const y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
            const pointer = new THREE.Vector2(x, y);
            this.rayCaster.setFromCamera(pointer, this.camera);
            const intersects = this.rayCaster.intersectObject(this.instancedMesh);
            if (intersects.length > 0) {
                intersects.forEach((intersect) => {
                    const instanceId = intersect.instanceId;
                    if (instanceId !== undefined) {
                        const data = this.particlesData[instanceId];
                        data.isFadingOut = true; // Start fading out the particle
                    }
                });
            }
        };
        /**
         * Handles touch end events to fade out particles.
         */
        onTouchEnd = (event) => {
            if (event.changedTouches.length === 1) {
                const touch = event.changedTouches[0];
                const rect = this.renderer.domElement.getBoundingClientRect();
                const x = ((touch.clientX - rect.left) / rect.width) * 2 - 1;
                const y = -((touch.clientY - rect.top) / rect.height) * 2 + 1;
                const pointer = new THREE.Vector2(x, y);
                this.rayCaster.setFromCamera(pointer, this.camera);
                const intersects = this.rayCaster.intersectObject(this.instancedMesh);
                if (intersects.length > 0) {
                    intersects.forEach((intersect) => {
                        const instanceId = intersect.instanceId;
                        if (instanceId !== undefined) {
                            const data = this.particlesData[instanceId];
                            data.isFadingOut = true; // Start fading out the particle
                        }
                    });
                }
            }
        };
    }
});
