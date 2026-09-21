#!/usr/bin/env bash
#
# Рендерит слайды 2000×2000 в PNG из slide_builder.html.
#
# Usage:
#   ./build_slides.sh            # все языки
#   ./build_slides.sh ru         # только русский
#   ./build_slides.sh en fr      # выборочно
#
# Результат: out/ru/slide-01.png … slide-14.png и out/ru/upload/.
#
# Требуется Google Chrome (headless). Скриншоты кладите в ./shots/ —
# см. имена файлов в шапке slide_builder.html.

set -euo pipefail

cd "$(dirname "$0")"

CHROME="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
[ -x "$CHROME" ] || CHROME="$(command -v google-chrome || command -v chromium || true)"
if [ -z "$CHROME" ] || [ ! -x "$CHROME" ]; then
  echo "Не найден Chrome. Укажите путь в переменной CHROME внутри скрипта." >&2
  exit 1
fi

LANGS=("$@")
[ ${#LANGS[@]} -eq 0 ] && LANGS=(ru)

TOTAL=14

# --- Пред-пасс: подгоняем исходники под размер окна кадра в 4000-рендере ---
# Окно скрина на слайде = вся ширина контента (1808 css-px) × 2 (dsf) = 3616 device-px.
# Если файл скриншота уже этого, Chrome РАСТЯГИВАЕТ его своим мягким апскейлером —
# мелкий текст интерфейса мылится (заголовки не страдают, они текст). Увеличиваем
# копии до 3800 px качественным Lanczos'ом, тогда Chrome их УМЕНЬШАЕТ — резко.
# Оригиналы в shots/ не трогаем; работаем в _shots_hi/, чистим на выходе.
SHOTS_HI="_shots_hi"
TARGET_W=3800
USE_HI=0
trap 'rm -rf "$SHOTS_HI" "._slide_tmp.html"' EXIT
if command -v magick >/dev/null 2>&1 && [ -d shots ]; then
  echo "=== пред-пасс: исходники → ${TARGET_W}px (без пересъёмки) ==="
  rm -rf "$SHOTS_HI"; mkdir -p "$SHOTS_HI"
  while IFS= read -r -d '' f; do
    base="$(basename "$f")"
    w="$(magick identify -format '%w' "$f" 2>/dev/null || echo 0)"
    if [ "${w:-0}" -gt 0 ] && [ "${w:-0}" -lt "$TARGET_W" ]; then
      magick "$f" -filter Lanczos -resize "${TARGET_W}x" -unsharp 0x0.7+0.4+0.01 "$SHOTS_HI/$base"
    else
      cp "$f" "$SHOTS_HI/$base"
    fi
  done < <(find shots -maxdepth 1 -type f \( -iname '*.png' -o -iname '*.jpg' \) -print0)
  USE_HI=1
else
  echo "  ! magick не найден — рендер напрямую из shots/ (скрины могут мылить)" >&2
fi

for LANG_CODE in "${LANGS[@]}"; do
  OUT="out/$LANG_CODE"
  mkdir -p "$OUT"
  echo "=== $LANG_CODE ==="

  for i in $(seq 1 $TOTAL); do
    # Временная страница: тот же билдер, но показывает один слайд без масштаба и панели.
    TMP="$(mktemp -t slide).html"
    python3 - "$i" "$LANG_CODE" "$TMP" <<'PY'
import sys, io
idx, lang, out = sys.argv[1], sys.argv[2], sys.argv[3]
html = io.open('slide_builder.html', encoding='utf-8').read()
inject = """
<style>
  .toolbar{display:none!important}
  .stage{padding:0!important}
  body{background:#fff!important}
  .slide{transform:none!important; margin:0!important}
</style>
<script>
window.addEventListener('load', function(){
  var b = document.querySelector('[data-lang="%s"]');
  if (b) { b.click(); }
  setTimeout(function(){
    document.querySelectorAll('.slide').forEach(function(el){
      if (el.getAttribute('data-n') !== '%s') { el.remove(); }
      else { el.style.transform = 'none'; el.style.margin = '0'; }
    });
  }, 60);
});
</script>
""" % (lang, idx)
io.open(out, 'w', encoding='utf-8').write(html.replace('</body>', inject + '</body>'))
PY

    # file:// нужен абсолютный путь; шрифты и картинки тянутся относительно исходной папки,
    # поэтому копируем временный файл рядом с ассетами.
    LOCAL="._slide_tmp.html"
    cp "$TMP" "$LOCAL"
    rm -f "$TMP"

    # Рендерим из увеличенных копий (_shots_hi/), если пред-пасс отработал.
    [ "$USE_HI" = 1 ] && sed -i '' 's#shots/#'"$SHOTS_HI"'/#g' "$LOCAL"

    NN="$(printf '%02d' "$i")"
    RAW="$OUT/.raw-$NN.png"          # исходный 4000×4000 рендер
    PNG="$OUT/slide-$NN.png"         # master 2000×2000 (для .com / архива)
    UP="$OUT/upload/slide-$NN.png"   # 1000×1000 под загрузку на маркетплейс
    mkdir -p "$OUT/upload"

    # Рендерим в 4000×4000: суперсэмплинг убирает «мыло» на тексте и тонких
    # линиях. Исходные скриншоты всё равно должны быть ретинными — см. README,
    # иначе внутри окна останется апскейл.
    "$CHROME" --headless=new --disable-gpu --no-sandbox --hide-scrollbars \
      --force-device-scale-factor=2 --window-size=2000,2000 --virtual-time-budget=6000 \
      --screenshot="$RAW" "file://$(pwd)/$LOCAL" 2>/dev/null

    # Даунскейл делаем Lanczos'ом (magick), а не sips: фильтр резче.
    # ВАЖНО: ресайз в обычном sRGB, без -colorspace RGB (linear-light). Для UI
    # чёрным по белому линейный ресайз осветляет краевые пиксели и рисует вокруг
    # букв серебристую кайму («тиснение») — выглядит кошмарно. И без unsharp:
    # 4000→1000 = 4× суперсэмплинг, и так резко, а шарп на чёрном тексте даёт
    # ореолы. upload-версия — сразу 1000×1000, т.к. маркетплейс отдаёт максимум
    # 1000 (pbig) и уменьшает сам мягким ресемплером; отдавая готовый 1000, мы
    # убираем его даунскейл — остаётся один чистый проход JPEG.
    if command -v magick >/dev/null 2>&1; then
      magick "$RAW" -filter Lanczos -resize 2000x2000 "$PNG"
      # upload: мягкий unsharp (0.35) — прибавляет читабельности мелкому UI-тексту
      # админки, который в 1000px ужимается сильнее всего. В sRGB (не linear) и с
      # малой амплитудой ореолов на чёрном заголовке не даёт — проверено.
      magick "$RAW" -filter Lanczos -resize 1000x1000 -unsharp 0x0.6+0.35+0.01 "$UP"
      rm -f "$RAW"
    elif command -v sips >/dev/null 2>&1; then
      echo "  ! magick не найден — фолбэк на sips (мягче); поставьте: brew install imagemagick" >&2
      sips -z 2000 2000 "$RAW" --out "$PNG" >/dev/null 2>&1
      sips -z 1000 1000 "$RAW" --out "$UP"  >/dev/null 2>&1
      rm -f "$RAW"
    else
      mv "$RAW" "$PNG"
      echo "  ! ни magick, ни sips — файл остался 4000×4000, уменьшите вручную" >&2
    fi

    rm -f "$LOCAL"
    printf '  slide-%02d.png  (master 2000 + upload 1000)\n' "$i"
  done
done

echo
echo "Готово."
echo "  master  → out/<lang>/slide-XX.png        (2000×2000, архив / prestashop.com)"
echo "  ЗАГРУЖАТЬ → out/<lang>/upload/slide-XX.png (1000×1000, под маркетплейс addons)"
