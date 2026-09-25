# DECISIONS

Failed approaches / gotchas / architecture choices, dated.

## 2026-09-25

- **Root cause of "doesn't work at all" report**: `szm-absolute-positioning.php:172`
  had a leftover no-op placeholder `if ( strpos ?? false ) {}`. A bare identifier
  without `()` is a constant reference in PHP; PHP 8 throws a fatal `Error` for an
  undefined constant (not a warning like PHP 7). This crashed `szm_ap_render_block`
  with a page-wide 500 on every page containing a block with `szmPos:true`, in both
  wp-admin (block preview/post rendering) and the live frontend. Fixed by deleting
  the dead line. Confirmed via a direct WP-CLI-created test post + curl: 500 before,
  200 with correct inline style after.
- **Second bug, same session**: `editor.js` never registered the `szmPos*`
  attributes on the block type via a `blocks.registerBlockType` filter — it only
  added `editor.BlockEdit` (inspector UI) and `editor.BlockListBlock` (preview)
  filters. WordPress only serializes attributes that are registered on the block
  type into the saved block comment, so even where the fatal error didn't hide it,
  the position data would vanish on save/reload and never reach `render_block`.
  Fixed by adding a `blocks.registerBlockType` filter that merges the `szmPos*`
  attribute definitions in for supported blocks.
- Both fixes deployed to fse-test's docker bind mount (`docker exec fse-test-wp`,
  since the plugin dir is owned by uid 33/www-data inside that container, not the
  wpcli container's uid 82). Frontend render verified live; the editor-side
  save/reload round trip still needs a real browser check (no browser tool
  available this session) — see PLAN.md.

## 2026-09-14

- **Positionering opslaan als eigen attributen + `render_block`, niet in de core
  `style`-attribuut.** WP valideert keys in `attributes.style` bij opslaan en kan
  onbekende (zoals `position`/`z-index`) weggooien. Eigen `szmPos*`-attributen
  overleven de save en een `render_block`-filter zet ze om naar een inline style
  op de frontend. Voorbeeld: szm-hover-animations gebruikt hetzelfde principe
  (attributes + classes), maar daar is het class-gebaseerd; hier zijn het
  numerieke procent-waardes, dus inline style is passender.
- **Parent-relative via `:has()`.** In plaats van een handmatige "frame"-marker op
  het ouderblok: elk `.wp-block:has(> .szm-pos-absolute)` wordt `position:relative`.
  Minder bewegende delen, geen extra toggle voor de gebruiker. Nadeel: afhankelijk
  van `:has()`-ondersteuning (ondersteund sinds Chrome 105/Firefox 121, 2023).
- **Percentages i.p.v. pixels** voor X/Y/breedte: responsive zonder media queries
  per klant. Voor echte Framer-nabootsing wil je later per-breakpoint waarden, maar
  dat is een vervolg.
- **Center-anchor = `left:50%; transform:translateX(-50%)`.** Zowel in PHP als in
  de editor dezelfde regel, anders lopen preview en frontend uit elkaar.