# DECISIONS

Failed approaches / gotchas / architecture choices, dated.

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