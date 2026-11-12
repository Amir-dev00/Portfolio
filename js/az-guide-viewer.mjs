/* On-demand Three.js astronaut viewer for the homepage guide.
   Loaded only after the visitor opens the guide — never at first paint. */
import * as THREE from "./vendor/three/three.module.min.js";
import { GLTFLoader } from "./vendor/three/GLTFLoader.js";

export async function createGuideViewer(canvas, modelUrl) {
  const reduced =
    typeof matchMedia === "function" &&
    matchMedia("(prefers-reduced-motion: reduce)").matches;

  let renderer;
  try {
    renderer = new THREE.WebGLRenderer({
      canvas,
      alpha: true,
      antialias: true,
      powerPreference: "low-power",
    });
  } catch (err) {
    return null;
  }

  if (!renderer || !renderer.getContext()) {
    try {
      renderer.dispose();
    } catch (e) {}
    return null;
  }

  renderer.setClearColor(0x000000, 0);
  renderer.outputColorSpace = THREE.SRGBColorSpace;

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(32, 1, 0.1, 40);
  camera.position.set(0, 1.15, 3.2);
  camera.lookAt(0, 0.95, 0);

  const hemi = new THREE.HemisphereLight(0xb8e7ff, 0x0b1220, 1.15);
  scene.add(hemi);
  const key = new THREE.DirectionalLight(0xffffff, 1.35);
  key.position.set(2.2, 3.4, 2.6);
  scene.add(key);
  const fill = new THREE.DirectionalLight(0x6ee7ff, 0.45);
  fill.position.set(-2.4, 1.2, -1.4);
  scene.add(fill);

  const loader = new GLTFLoader();
  const gltf = await new Promise(function (resolve, reject) {
    loader.load(modelUrl, resolve, undefined, reject);
  });

  const root = gltf.scene || gltf.scenes[0];
  root.traverse(function (obj) {
    if (obj.isMesh) {
      obj.castShadow = false;
      obj.receiveShadow = false;
      if (obj.material) {
        obj.material.side = THREE.FrontSide;
      }
    }
  });

  // Frame the character nicely in the small canvas.
  const box = new THREE.Box3().setFromObject(root);
  const size = new THREE.Vector3();
  const center = new THREE.Vector3();
  box.getSize(size);
  box.getCenter(center);
  root.position.sub(center);
  root.position.y += size.y * 0.08;
  const scale = 1.55 / Math.max(size.x, size.y, size.z);
  root.scale.setScalar(scale);
  scene.add(root);

  const mixer = new THREE.AnimationMixer(root);
  const clips = {};
  (gltf.animations || []).forEach(function (clip) {
    if (clip && clip.name) clips[clip.name] = clip;
  });

  let currentAction = null;
  let idleAction = null;
  let raf = 0;
  let running = false;
  let last = 0;
  const clock = new THREE.Clock();

  function resize(cssSize) {
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const w = Math.max(1, Math.round(cssSize));
    renderer.setPixelRatio(dpr);
    renderer.setSize(w, w, false);
    camera.aspect = 1;
    camera.updateProjectionMatrix();
  }

  function renderFrame() {
    const dt = clock.getDelta();
    if (mixer) mixer.update(dt);
    renderer.render(scene, camera);
  }

  function loop(now) {
    if (!running) return;
    raf = requestAnimationFrame(loop);
    // Throttle a little on low-end: skip if frame gap is tiny after a hitch.
    if (now - last < 12) return;
    last = now;
    renderFrame();
  }

  function start() {
    if (running || reduced) return;
    running = true;
    clock.start();
    last = performance.now();
    raf = requestAnimationFrame(loop);
  }

  function stop() {
    running = false;
    if (raf) {
      cancelAnimationFrame(raf);
      raf = 0;
    }
  }

  function playIdle() {
    const clip = clips.idle;
    if (!clip) return;
    if (!idleAction) idleAction = mixer.clipAction(clip);
    idleAction.reset();
    idleAction.setLoop(THREE.LoopRepeat, Infinity);
    idleAction.fadeIn(0.25).play();
    currentAction = idleAction;
  }

  function playOnce(name) {
    const clip = clips[name];
    if (!clip) {
      return Promise.resolve();
    }
    return new Promise(function (resolve) {
      const action = mixer.clipAction(clip);
      action.reset();
      action.setLoop(THREE.LoopOnce, 1);
      action.clampWhenFinished = true;
      if (currentAction && currentAction !== action) {
        currentAction.fadeOut(0.2);
      }
      action.fadeIn(0.15).play();
      currentAction = action;

      const onDone = function (e) {
        if (e.action !== action) return;
        mixer.removeEventListener("finished", onDone);
        if (idleAction) {
          action.fadeOut(0.2);
          idleAction.reset().fadeIn(0.25).play();
          currentAction = idleAction;
        }
        resolve();
      };
      mixer.addEventListener("finished", onDone);

      // Safety if the clip reports no finished event.
      const ms = Math.max(600, (clip.duration || 1) * 1000 + 120);
      setTimeout(function () {
        mixer.removeEventListener("finished", onDone);
        resolve();
      }, ms + 80);
    });
  }

  function dispose() {
    stop();
    try {
      mixer.stopAllAction();
    } catch (e1) {}
    scene.traverse(function (obj) {
      if (obj.geometry) obj.geometry.dispose();
      if (obj.material) {
        const mats = Array.isArray(obj.material) ? obj.material : [obj.material];
        mats.forEach(function (m) {
          if (m.map) m.map.dispose();
          m.dispose();
        });
      }
    });
    renderer.dispose();
  }

  if (!reduced && clips.idle) playIdle();

  return {
    resize: resize,
    start: start,
    stop: stop,
    playOnce: playOnce,
    playIdle: playIdle,
    dispose: dispose,
    hasClip: function (name) {
      return !!clips[name];
    },
    reduced: reduced,
  };
}
