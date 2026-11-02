# Yummy Treats! design canvas, source backup

Backup of the Claude design canvas "Yummy Treats! Website Concepts" (<https://claude.ai/artifact/2J5m2ktAgmrnZk811EMFce>), copied 30 October 2026.

- `project/canvas.json`: board positions, pages (Billboard, Directions) and sticky notes.
- `project/d2-...d8-*.dc.html` and `Main.dc.html`: the eight homepage directions (Shelf, Pantry, Lab, Billboard, Noir, Studio, Kit, Ledger). Keep all of them; Studio, Kit and Noir are candidates for future demo sites.
- `project/b*.dc.html`: the Billboard page set (style guide, sitemap, shop flow, service pages, phone boards).

Each file is a self-contained HTML "design component" for the canvas runtime (it needs the canvas's `support.js` to run), not a WordPress template. The build in Phase 4 reads these as the spec; the WooCommerce theme lives in `../theme/` once it exists.
