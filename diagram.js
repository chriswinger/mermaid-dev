/* global Drupal, mermaid, svgPanZoom, once */
/**
 * @file
 * Render Mermaid diagrams and attach svg-pan-zoom
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.diagramDisplay = {
    attach(context) {
      const elements = once('diagram-display', '.mermaid', context);
      if (!elements.length || typeof mermaid === 'undefined') {
        return;
      }

      // Keep default (strict) security; no links required.
      mermaid.initialize({
        startOnLoad: false
      });

      elements.forEach(async (el, idx) => {
        el.style.opacity = '0';
        const code = el.textContent.trim();
        const id = `mmd-${Date.now()}-${idx}-${Math.random().toString(36).slice(2)}`;

        try {
          const { svg } = await mermaid.render(id, code);
          el.innerHTML = svg;

          const svgEl = el.querySelector('svg');
          if (svgEl && typeof svgPanZoom !== 'undefined') {
            const panZoom = svgPanZoom(svgEl, {
              zoomEnabled: true,
              controlIconsEnabled: true,
              fit: true,
              center: true,
              minZoom: 0.4,
              maxZoom: 12
            });

            // Keep responsive on resize.
            window.addEventListener('resize', () => {
              panZoom.resize();
              panZoom.fit();
              panZoom.center();
            });
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
