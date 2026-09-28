<script setup lang="ts">
import { onMounted, onUnmounted, useTemplateRef, watch } from 'vue';
import * as THREE from 'three';

export type ParticleShape = 'cube' | 'sphere' | 'octahedron' | 'torus';

interface AntigravityProps {
  count?: number;
  magnetRadius?: number;
  waveSpeed?: number;
  particleSize?: number;
  lerpSpeed?: number;
  color?: string;
  isDark?: boolean;
  autoAnimate?: boolean;
  particleVariance?: number;
  pulseSpeed?: number;
  fieldStrength?: number;
  particleShape?: ParticleShape;
}

interface ParticleData {
  t: number;
  speed: number;
  baseScale: number;
  seed: number;
  ox: number;
  oy: number;
  oz: number;
  cx: number;
  cy: number;
  cz: number;
  // 3D Rotational tumbling physics
  rx: number;
  ry: number;
  rz: number;
  rotSpeedX: number;
  rotSpeedY: number;
  rotSpeedZ: number;
}

const props = withDefaults(defineProps<AntigravityProps>(), {
  count: 120,
  magnetRadius: 9,
  waveSpeed: 0.35,
  particleSize: 0.9,
  lerpSpeed: 0.075,
  color: '#3B82F6',
  isDark: false,
  autoAnimate: true,
  particleVariance: 0.35,
  pulseSpeed: 1.4,
  fieldStrength: 4.8,
  particleShape: 'cube',
});

const containerRef = useTemplateRef<HTMLDivElement>('containerRef');

let renderer: THREE.WebGLRenderer | null = null;
let scene: THREE.Scene | null = null;
let camera: THREE.PerspectiveCamera | null = null;
let mesh: THREE.InstancedMesh | null = null;
let animationFrameId: number = 0;
let particles: ParticleData[] = [];
let dummy: THREE.Object3D;
let clock: THREE.Clock;

let ambientLight: THREE.AmbientLight | null = null;
let dirLight: THREE.DirectionalLight | null = null;
let rimLight: THREE.DirectionalLight | null = null;
let mouseLight: THREE.PointLight | null = null;

let lastMousePos = { x: 0, y: 0 };
let lastMouseMoveTime = 0;
let virtualMouse = { x: 0, y: 0 };
let pointer = { x: 0, y: 0 };

function createGeometry(shape: ParticleShape): THREE.BufferGeometry {
  switch (shape) {
    case 'cube':
      return new THREE.BoxGeometry(0.7, 0.7, 0.7);
    case 'octahedron':
      return new THREE.OctahedronGeometry(0.55);
    case 'torus':
      return new THREE.TorusGeometry(0.42, 0.1, 12, 24);
    case 'sphere':
    default:
      return new THREE.SphereGeometry(0.55, 20, 20);
  }
}

function initParticles(viewportWidth: number, viewportHeight: number) {
  particles = [];
  
  for (let i = 0; i < props.count; i++) {
    const t = Math.random() * 100;
    const speed = 0.006 + Math.random() * 0.009;
    const seed = Math.random() * 10;

    // Distribute across the hero viewport
    let ox = (Math.random() - 0.5) * (viewportWidth * 1.3);
    let oy = (Math.random() - 0.5) * (viewportHeight * 1.25);
    let oz = (Math.random() - 0.5) * 18;

    // Sizing & Central Corridor Protection (Text remains 100% crisp)
    const isCentralZone = Math.abs(ox) < 19 && Math.abs(oy) < 8;
    
    let baseScale: number;
    if (isCentralZone) {
      baseScale = 0.2 + Math.random() * 0.2;
      oz = -8 - Math.random() * 10;
    } else {
      const rand = Math.random();
      if (rand < 0.65) {
        baseScale = 0.35 + Math.random() * 0.3; // Micro floating units
      } else if (rand < 0.90) {
        baseScale = 0.7 + Math.random() * 0.3;  // Medium units
      } else {
        baseScale = 1.05 + Math.random() * 0.3; // Accent units
      }
    }

    particles.push({
      t,
      speed,
      baseScale,
      seed,
      ox,
      oy,
      oz,
      cx: ox,
      cy: oy,
      cz: oz,
      rx: Math.random() * Math.PI * 2,
      ry: Math.random() * Math.PI * 2,
      rz: Math.random() * Math.PI * 2,
      rotSpeedX: (Math.random() - 0.5) * 0.012,
      rotSpeedY: (Math.random() - 0.5) * 0.015,
      rotSpeedZ: (Math.random() - 0.5) * 0.01,
    });
  }
}

function getViewportAtDepth(camera: THREE.PerspectiveCamera, depth: number) {
  const fovInRadians = (camera.fov * Math.PI) / 180;
  const height = 2 * Math.tan(fovInRadians / 2) * depth;
  const width = height * camera.aspect;
  return { width, height };
}

function setupScene() {
  const container = containerRef.value;
  if (!container) return;

  const { clientWidth, clientHeight } = container;

  // WebGL Renderer with alpha transparency & high performance
  renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
  renderer.setSize(clientWidth, clientHeight);
  renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
  container.appendChild(renderer.domElement);

  // Scene & Camera
  scene = new THREE.Scene();
  camera = new THREE.PerspectiveCamera(40, clientWidth / clientHeight, 0.1, 1000);
  camera.position.z = 45;

  const viewport = getViewportAtDepth(camera, camera.position.z);

  // Three-point Lighting for high-fidelity 3D reflections
  ambientLight = new THREE.AmbientLight(0xffffff, props.isDark ? 1.0 : 1.4);
  scene.add(ambientLight);

  dirLight = new THREE.DirectionalLight(0xffffff, props.isDark ? 2.2 : 2.5);
  dirLight.position.set(20, 30, 25);
  scene.add(dirLight);

  rimLight = new THREE.DirectionalLight(props.isDark ? 0x38bdf8 : 0x60a5fa, 1.3);
  rimLight.position.set(-20, -20, 15);
  scene.add(rimLight);

  mouseLight = new THREE.PointLight(0xffffff, 2.6, 36);
  mouseLight.position.set(0, 0, 15);
  scene.add(mouseLight);

  // Initialize Particles
  initParticles(viewport.width, viewport.height);

  // Geometry
  const geometry = createGeometry(props.particleShape);

  // Glossy Translucent Material (Eye-friendly opacity & high specular sheen)
  const material = new THREE.MeshPhongMaterial({
    color: new THREE.Color(props.color),
    specular: new THREE.Color(0xffffff),
    shininess: 95,
    transparent: true,
    opacity: props.isDark ? 0.38 : 0.28,
    depthWrite: false,
  });

  mesh = new THREE.InstancedMesh(geometry, material, props.count);
  scene.add(mesh);

  dummy = new THREE.Object3D();
  clock = new THREE.Clock();

  window.addEventListener('pointermove', onPointerMove, { passive: true });
  window.addEventListener('resize', onResize);

  animate();
}

function onPointerMove(event: PointerEvent) {
  const container = containerRef.value;
  if (!container) return;

  const rect = container.getBoundingClientRect();
  pointer.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
  pointer.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
}

function onResize() {
  const container = containerRef.value;
  if (!container || !renderer || !camera) return;

  const { clientWidth, clientHeight } = container;
  camera.aspect = clientWidth / clientHeight;
  camera.updateProjectionMatrix();
  renderer.setSize(clientWidth, clientHeight);
}

function animate() {
  animationFrameId = requestAnimationFrame(animate);

  if (!mesh || !camera || !renderer || !scene) return;

  const viewport = getViewportAtDepth(camera, camera.position.z);
  const elapsedTime = clock.getElapsedTime();

  // Mouse interaction detection
  const mouseDist = Math.sqrt(
    Math.pow(pointer.x - lastMousePos.x, 2) + Math.pow(pointer.y - lastMousePos.y, 2)
  );

  if (mouseDist > 0.001) {
    lastMouseMoveTime = Date.now();
    lastMousePos = { x: pointer.x, y: pointer.y };
  }

  let destX = (pointer.x * viewport.width) / 2;
  let destY = (pointer.y * viewport.height) / 2;

  // Gentle autonomous wandering motion when idle
  if (props.autoAnimate && Date.now() - lastMouseMoveTime > 1600) {
    destX = Math.sin(elapsedTime * 0.3) * (viewport.width * 0.25);
    destY = Math.cos(elapsedTime * 0.45) * (viewport.height * 0.22);
  }

  // Snappy yet smooth cursor tracking
  const smoothFactor = 0.1;
  virtualMouse.x += (destX - virtualMouse.x) * smoothFactor;
  virtualMouse.y += (destY - virtualMouse.y) * smoothFactor;

  // Update dynamic point light
  if (mouseLight) {
    mouseLight.position.set(virtualMouse.x, virtualMouse.y, 14);
  }

  const time = elapsedTime * props.waveSpeed;

  // Animate each particle
  for (let i = 0; i < particles.length; i++) {
    const p = particles[i];
    p.t += p.speed;

    // Harmonic floating zero-gravity drift
    const floatX = Math.sin(time + p.seed * 2.5) * 1.4;
    const floatY = Math.cos(time * 0.8 + p.seed * 3.8) * 1.5;
    const floatZ = Math.sin(time * 0.6 + p.seed * 1.7) * 1.0;

    let targetX = p.ox + floatX;
    let targetY = p.oy + floatY;
    let targetZ = p.oz + floatZ;

    // Distance to virtual cursor
    const dx = p.cx - virtualMouse.x;
    const dy = p.cy - virtualMouse.y;
    const dist2D = Math.sqrt(dx * dx + dy * dy);

    // Interactive fluid dispersion when near cursor
    if (dist2D < props.magnetRadius && dist2D > 0.01) {
      const repelFactor = (1 - dist2D / props.magnetRadius) * props.fieldStrength;
      const angle = Math.atan2(dy, dx);
      targetX += Math.cos(angle) * repelFactor * 2.2;
      targetY += Math.sin(angle) * repelFactor * 2.2;
      targetZ += Math.sin(time * 2 + p.seed) * repelFactor;
    }

    // Responsive lerp towards target position
    p.cx += (targetX - p.cx) * props.lerpSpeed;
    p.cy += (targetY - p.cy) * props.lerpSpeed;
    p.cz += (targetZ - p.cz) * props.lerpSpeed;

    // 3D Tumbling Rotation
    p.rx += p.rotSpeedX;
    p.ry += p.rotSpeedY;
    p.rz += p.rotSpeedZ;

    dummy.position.set(p.cx, p.cy, p.cz);
    dummy.rotation.set(p.rx, p.ry, p.rz);

    // Gentle breathing pulse
    const pulse = 1 + Math.sin(time * props.pulseSpeed + p.seed * 4) * 0.08 * props.particleVariance;
    const hoverScale = dist2D < props.magnetRadius ? 1 + (1 - dist2D / props.magnetRadius) * 0.15 : 1;
    const finalScale = p.baseScale * props.particleSize * pulse * hoverScale;

    dummy.scale.set(finalScale, finalScale, finalScale);
    dummy.updateMatrix();

    mesh.setMatrixAt(i, dummy.matrix);
  }

  mesh.instanceMatrix.needsUpdate = true;
  renderer.render(scene, camera);
}

function cleanup() {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId);
  }

  window.removeEventListener('pointermove', onPointerMove);
  window.removeEventListener('resize', onResize);

  if (mesh) {
    mesh.geometry.dispose();
    if (Array.isArray(mesh.material)) {
      mesh.material.forEach((m) => m.dispose());
    } else {
      mesh.material.dispose();
    }
  }

  if (renderer) {
    renderer.dispose();
    const container = containerRef.value;
    if (container && renderer.domElement.parentNode === container) {
      container.removeChild(renderer.domElement);
    }
  }

  renderer = null;
  scene = null;
  camera = null;
  mesh = null;
}

onMounted(setupScene);
onUnmounted(cleanup);

watch(
  () => [props.color, props.isDark],
  ([newColor, newIsDark]) => {
    if (mesh && mesh.material) {
      const mat = mesh.material as THREE.MeshPhongMaterial;
      mat.color.set(newColor as string);
      mat.opacity = newIsDark ? 0.38 : 0.28;
    }
    if (ambientLight) {
      ambientLight.intensity = (newIsDark as boolean) ? 1.0 : 1.4;
    }
    if (dirLight) {
      dirLight.intensity = (newIsDark as boolean) ? 2.2 : 2.5;
    }
    if (rimLight) {
      rimLight.color.set((newIsDark as boolean) ? 0x38bdf8 : 0x60a5fa);
    }
  }
);

watch(
  () => [props.count, props.particleSize, props.particleShape],
  () => {
    cleanup();
    setupScene();
  }
);
</script>

<template>
  <div ref="containerRef" class="relative w-full h-full" />
</template>
