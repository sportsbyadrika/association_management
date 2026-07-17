<?php
/**
 * Site favicon / touch icons. Replace the files in public/assets/img/ to
 * change the icon (keep the same filenames):
 *   - favicon.svg  (scalable, used by modern browsers)
 *   - favicon.png  (512x512 fallback + Apple touch icon)
 */
?>
<link rel="icon" type="image/svg+xml" href="<?= e(asset('/assets/img/favicon.svg')) ?>">
<link rel="icon" type="image/png" sizes="512x512" href="<?= e(asset('/assets/img/favicon.png')) ?>">
<link rel="apple-touch-icon" href="<?= e(asset('/assets/img/favicon.png')) ?>">
