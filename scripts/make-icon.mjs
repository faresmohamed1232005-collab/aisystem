// يولّد أيقونة التطبيق (الروبوت) من resources/icons/robot-icon.svg إلى:
//   public/icon.png  (512×512)
//   public/icon.ico  (متعدّد المقاسات 16..256)
// NativePHP ينسخ هذين الملفين تلقائياً في البناء (InstallsAppIcon). GD لا يرسم SVG/تدرّجات،
// لذا نولّد مرة واحدة عبر sharp ونلتزم الناتج (خارج مسار البناء الحرج).
//
// التشغيل:  npm run icon:build
import sharp from 'sharp';
import pngToIco from 'png-to-ico';
import { readFileSync, writeFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const svg = readFileSync(resolve(root, 'resources/icons/robot-icon.svg'));

// ارسم الـ SVG مرة واحدة بدقّة عالية ثم صغّر النسخة النقطية لبقية المقاسات (اتساق ووضوح).
const base = await sharp(svg, { density: 512 }).resize(512, 512).png().toBuffer();
writeFileSync(resolve(root, 'public/icon.png'), base);

const sizes = [16, 32, 48, 64, 128, 256];
const pngs = await Promise.all(sizes.map((s) => sharp(base).resize(s, s).png().toBuffer()));
writeFileSync(resolve(root, 'public/icon.ico'), await pngToIco(pngs));

console.log(`Generated public/icon.png (512×512) and public/icon.ico [${sizes.join(', ')}]`);
