/**
 * uLam NSFWJS fallback moderation service (pure-JS build).
 *
 * Local-only HTTP service that classifies an image file on disk.
 * Laravel calls it when the Claude Haiku monthly cap is reached (or Claude errors).
 * Uses @tensorflow/tfjs CPU backend (no native binaries — works on any Node).
 *
 * Setup (one time):   cd moderation-service && npm install
 * Run:                npm start          (listens on 127.0.0.1:3310)
 *
 * POST /classify { "path": "C:/absolute/path/to/image.jpg" }
 *   -> { "predictions": [{ "className": "Porn", "probability": 0.01 }, ...] }
 */
const express = require('express');
const fs = require('fs');
const tf = require('@tensorflow/tfjs');
const jpeg = require('jpeg-js');
const { PNG } = require('pngjs');
const nsfwjs = require('nsfwjs');

const PORT = process.env.PORT || 3310;

let modelPromise = nsfwjs.load(); // downloads + caches the model on first run

function decodeToTensor(buffer) {
  let data, width, height, channels;
  if (buffer[0] === 0x89 && buffer[1] === 0x50) {
    const png = PNG.sync.read(buffer);
    ({ data, width, height } = png);
    channels = 4;
  } else {
    const img = jpeg.decode(buffer, { useTArray: true, maxMemoryUsageInMB: 512 });
    ({ data, width, height } = img);
    channels = 4;
  }
  const numPixels = width * height;
  const rgb = new Int32Array(numPixels * 3);
  for (let i = 0; i < numPixels; i++) {
    rgb[i * 3]     = data[i * channels];
    rgb[i * 3 + 1] = data[i * channels + 1];
    rgb[i * 3 + 2] = data[i * channels + 2];
  }
  return tf.tensor3d(rgb, [height, width, 3], 'int32');
}

const app = express();
app.use(express.json());

app.get('/health', (_req, res) => res.json({ ok: true }));

app.post('/classify', async (req, res) => {
  try {
    const { path } = req.body ?? {};
    if (!path || !fs.existsSync(path)) {
      return res.status(400).json({ error: 'path missing or file not found' });
    }

    const model = await modelPromise;
    const image = decodeToTensor(fs.readFileSync(path));
    const predictions = await model.classify(image);
    image.dispose();

    res.json({ predictions });
  } catch (e) {
    console.error('classify failed:', e.message);
    res.status(500).json({ error: e.message });
  }
});

app.listen(PORT, '127.0.0.1', () => {
  console.log(`uLam moderation fallback listening on http://127.0.0.1:${PORT}`);
});
