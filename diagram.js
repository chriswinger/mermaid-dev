/* global Drupal, mermaid, svgPanZoom, once, drupalSettings */
/**
 * @file
 * Render Mermaid diagrams and (optionally) attach svg-pan-zoom.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.diagramDisplay = {
    attach(context) {
      const elements = once('diagram-display', '.mermaid', context);
      if (!elements.length || typeof mermaid === 'undefined') {
        return;
      }

      // Helper to read the pan/zoom flag:
      // Priority: data attribute on the element -> drupalSettings.
      const globalSettings = (typeof drupalSettings !== 'undefined' && drupalSettings) || {};
      const mermaidSettings = globalSettings.mermaid || globalSettings.diagram || {};
      const defaultPanZoom =
        (typeof mermaidSettings.enable_pan_zoom !== 'undefined'
          ? mermaidSettings.enable_pan_zoom
          : mermaidSettings.enablePanZoom) || false;

      const wantsPanZoom = (el) => {
        const a = (el.getAttribute('data-pan-zoom') || '').toLowerCase();
        if (a === 'true' || a === '1') return true;
        if (a === 'false' || a === '0') return false;
        return !!defaultPanZoom;
      };

      // Mermaid config (strict security; no links).
      mermaid.initialize({ startOnLoad: false });

      elements.forEach(async (el, idx) => {
        el.style.opacity = '0';
        const code = el.textContent.trim();
        const id = `mmd-${Date.now()}-${idx}-${Math.random().toString(36).slice(2)}`;

        try {
          const { svg } = await mermaid.render(id, code);
          el.innerHTML = svg;

          // Conditionally attach svg-pan-zoom.
          if (wantsPanZoom(el)) {
            const svgEl = el.querySelector('svg');
            if (svgEl) {
              if (typeof svgPanZoom !== 'undefined') {
                const panZoom = svgPanZoom(svgEl, {
                  zoomEnabled: true,
                  controlIconsEnabled: true,
                  fit: true,
                  center: true,
                  minZoom: 1,
                  maxZoom: 20,
                  zoomScaleSensitivity: 0.8,
                });
                // Keep responsive.
                window.addEventListener('resize', () => {
                  panZoom.resize();
                  panZoom.fit();
                  panZoom.center();
                });
              } else {
                // eslint-disable-next-line no-console
                console.warn(
                  'Pan/zoom requested but svgPanZoom library is not loaded. ' +
                  'Ensure your formatter attached the pan-zoom library when enable_pan_zoom = true.'
                );
              }
            }
          }

          el.style.opacity = '1';
        } catch (e) {
          // eslint-disable-next-line no-console
          console.error('Mermaid render failed:', e);
          el.innerHTML = `<pre class="mermaid-error">Mermaid render error:\n${String(e)}</pre>`;
          el.style.opacity = '1';
        }
      });
    }
  };
})(Drupal, once);
