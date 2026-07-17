// Habitract — lightweight self-hosted image cropper (no external deps).
//
// Attach automatically to any <input type="file" data-crop="passport|square">.
// On file selection it opens a pan/zoom crop modal with a fixed aspect frame,
// then writes the cropped image back into the same file input so the existing
// multipart upload sends the cropped result.
(function () {
    'use strict';

    // Aspect presets: [aspectRatio (w/h), outputW, outputH, frameW, label, mime]
    var PRESETS = {
        passport: { ratio: 35 / 45, outW: 413, outH: 531, frameW: 294, mime: 'image/jpeg',
                    title: 'Crop photo (passport size)', sub: 'Drag to reposition · slide to zoom · 35:45 ratio' },
        square:   { ratio: 1, outW: 512, outH: 512, frameW: 320, mime: 'image/png',
                    title: 'Crop logo (square)', sub: 'Drag to reposition · slide to zoom · 1:1 ratio' }
    };

    // Feature check: we must be able to write a File back into an <input>.
    function canSetFiles() {
        try {
            var dt = new DataTransfer();
            return typeof dt.items.add === 'function';
        } catch (e) { return false; }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!canSetFiles() || typeof HTMLCanvasElement.prototype.toBlob !== 'function') {
            return; // Gracefully degrade to a plain upload.
        }
        document.querySelectorAll('input[type=file][data-crop]').forEach(function (input) {
            var preset = PRESETS[input.getAttribute('data-crop')];
            if (!preset) return;
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file || !/^image\//.test(file.type)) return;
                openCropper(input, file, preset);
            });
        });
    });

    function openCropper(input, file, preset) {
        var reader = new FileReader();
        reader.onload = function () {
            var img = new Image();
            img.onload = function () { buildModal(input, img, file, preset); };
            img.onerror = function () { /* leave original file as-is */ };
            img.src = reader.result;
        };
        reader.readAsDataURL(file);
    }

    function buildModal(input, img, file, preset) {
        var frameW = preset.frameW;
        var frameH = Math.round(frameW / preset.ratio);

        var overlay = el('div', 'cropper-overlay');
        var modal = el('div', 'cropper-modal');
        overlay.appendChild(modal);

        var title = el('div', 'cropper-title'); title.textContent = preset.title;
        var sub = el('div', 'cropper-subtitle'); sub.textContent = preset.sub;
        modal.appendChild(title); modal.appendChild(sub);

        var stage = el('div', 'cropper-stage');
        stage.style.width = frameW + 'px';
        stage.style.height = frameH + 'px';
        var imgEl = document.createElement('img');
        imgEl.src = img.src;
        stage.appendChild(imgEl);
        var grid = el('div', 'cropper-grid');
        stage.appendChild(grid);
        modal.appendChild(stage);

        // Zoom control
        var zoomWrap = el('div', 'cropper-zoom');
        var zoomOut = document.createElement('span'); zoomOut.textContent = '−'; zoomOut.style.color = '#6b7280';
        var range = document.createElement('input');
        range.type = 'range'; range.min = '1'; range.max = '4'; range.step = '0.01'; range.value = '1';
        var zoomIn = document.createElement('span'); zoomIn.textContent = '+'; zoomIn.style.color = '#6b7280';
        zoomWrap.appendChild(zoomOut); zoomWrap.appendChild(range); zoomWrap.appendChild(zoomIn);
        modal.appendChild(zoomWrap);

        // Actions
        var actions = el('div', 'cropper-actions');
        var cancel = btn('Cancel', 'btn-secondary');
        var apply = btn('Apply crop', 'btn-primary');
        actions.appendChild(cancel); actions.appendChild(apply);
        modal.appendChild(actions);

        document.body.appendChild(overlay);

        // ---- Crop state ----
        var natW = img.naturalWidth, natH = img.naturalHeight;
        var minScale = Math.max(frameW / natW, frameH / natH);
        var scale = minScale;      // display px per source px
        var tx = (frameW - natW * scale) / 2;
        var ty = (frameH - natH * scale) / 2;

        function clamp() {
            var w = natW * scale, h = natH * scale;
            tx = w <= frameW ? (frameW - w) / 2 : Math.min(0, Math.max(frameW - w, tx));
            ty = h <= frameH ? (frameH - h) / 2 : Math.min(0, Math.max(frameH - h, ty));
        }
        function render() {
            clamp();
            imgEl.style.transform = 'translate(' + tx + 'px,' + ty + 'px) scale(' + scale + ')';
        }
        render();

        // Zoom keeps the frame centre stable.
        range.addEventListener('input', function () {
            var newScale = minScale * parseFloat(range.value);
            var cx = frameW / 2, cy = frameH / 2;
            var sx = (cx - tx) / scale, sy = (cy - ty) / scale;
            scale = newScale;
            tx = cx - sx * scale;
            ty = cy - sy * scale;
            render();
        });

        // Drag (mouse + touch via pointer events).
        var dragging = false, startX = 0, startY = 0, baseTx = 0, baseTy = 0;
        function down(e) {
            dragging = true; stage.classList.add('is-grabbing');
            var p = point(e); startX = p.x; startY = p.y; baseTx = tx; baseTy = ty;
            e.preventDefault();
        }
        function move(e) {
            if (!dragging) return;
            var p = point(e);
            tx = baseTx + (p.x - startX);
            ty = baseTy + (p.y - startY);
            render();
        }
        function up() { dragging = false; stage.classList.remove('is-grabbing'); }
        stage.addEventListener('pointerdown', down);
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', up);

        function point(e) {
            if (e.touches && e.touches[0]) return { x: e.touches[0].clientX, y: e.touches[0].clientY };
            return { x: e.clientX, y: e.clientY };
        }

        function close() {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', up);
            if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
        }

        cancel.addEventListener('click', function () {
            input.value = ''; // discard the uncropped selection
            close();
        });
        overlay.addEventListener('click', function (e) { if (e.target === overlay) { input.value = ''; close(); } });
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') { input.value = ''; close(); document.removeEventListener('keydown', esc); }
        });

        apply.addEventListener('click', function () {
            var srcX = -tx / scale, srcY = -ty / scale;
            var srcW = frameW / scale, srcH = frameH / scale;
            var canvas = document.createElement('canvas');
            canvas.width = preset.outW; canvas.height = preset.outH;
            var ctx = canvas.getContext('2d');
            if (preset.mime === 'image/jpeg') {
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            }
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(img, srcX, srcY, srcW, srcH, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(function (blob) {
                if (!blob) { close(); return; }
                var ext = preset.mime === 'image/png' ? '.png' : '.jpg';
                var base = (file.name || 'photo').replace(/\.[^.]+$/, '');
                var cropped = new File([blob], base + '-cropped' + ext, { type: preset.mime });
                var dt = new DataTransfer();
                dt.items.add(cropped);
                input.files = dt.files;
                showPreview(input, canvas.toDataURL(preset.mime), frameW, frameH);
                close();
            }, preset.mime, 0.92);
        });
    }

    function showPreview(input, dataUrl, frameW, frameH) {
        var id = 'crop-preview-' + (input.id || input.name);
        var wrap = document.getElementById(id);
        if (!wrap) {
            wrap = el('div', 'cropper-preview-wrap');
            wrap.id = id;
            var thumb = document.createElement('img');
            var note = el('span', 'cropper-preview-note');
            note.textContent = 'Cropped image ready — will be saved on submit.';
            wrap.appendChild(thumb); wrap.appendChild(note);
            input.parentNode.insertBefore(wrap, input.nextSibling);
        }
        var previewImg = wrap.querySelector('img');
        var h = 72, w = Math.round(h * (frameW / frameH));
        previewImg.style.width = w + 'px';
        previewImg.style.height = h + 'px';
        previewImg.src = dataUrl;
    }

    function el(tag, cls) { var e = document.createElement(tag); if (cls) e.className = cls; return e; }
    function btn(text, cls) { var b = document.createElement('button'); b.type = 'button'; b.className = cls; b.textContent = text; return b; }
})();
