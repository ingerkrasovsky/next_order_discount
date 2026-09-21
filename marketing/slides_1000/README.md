# Слайды 1000×1000 для Next Order Discount

Компактный вариант набора из `../slides`, подготовленный для прямой загрузки на Addons Marketplace.

## Сборка

```bash
./build_slides.sh
```

Скрипт рендерит каждый слайд в 4000×4000 и уменьшает до 1000×1000 через Lanczos. Результат: `out/ru/slide-01.png` … `slide-14.png`.

Тексты берутся из `../slides/translations.js`. Кадры лежат в `shots/`. Шрифты загружаются локально из `set_demo/views/fonts`.
