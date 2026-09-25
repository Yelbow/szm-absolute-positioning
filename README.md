# SZM Absolute Positioning

Framer-achtige absolute positionering van blokken in de WordPress site editor.
Geen nieuwe blokken: het Paneel "Absoluut positioneren" wordt gegraft op
bestaande core-blokken (Group, Cover, Column, Heading, Paragraph, Image,
Media & Text, Button).

## Wat het doet

Per blok een toggle + velden: X (%), Y (%), breedte (%), z-index en horizontaal
anker (links/midden/rechts). Aangezet wordt het blok `position:absolute`
binnen zijn directe ouder (die automatisch `relative` wordt via `:has()`).
Alles in procenten zodat het responsive blijft op elk scherm.

## Installatie (dev)

Kopieer de plugin-map naar de testsite en activeer:

```bash
cp -r ~/Dev/szm-absolute-positioning ~/Projects/fse-test/docker-site/wp-content/plugins/
docker exec fse-test-wpcli wp --allow-root --path=/var/www/html plugin activate szm-absolute-positioning
```

Site editor: `https://fse-test.studiozondermeer.nl` / `http://localhost:8310`.

## Structuur

- `szm-absolute-positioning.php` — hoofdplugin: enqueues, `render_block`-filter
  dat de inline style op de frontend zet, self-update via GitHub (PUC).
- `assets/editor.js` — inspector-paneel + live preview via `editor.BlockEdit` en
  `editor.BlockListBlock` filters.
- `assets/style.css` — frontend CSS (parent-relative via `:has()`).
- `assets/editor.css` — editor-preview hints.
- `inc/plugin-update-checker/` — self-update (zelfde als andere SZM-plugins).

## Hoe de positionering werkt

De waarden worden als eigen attributen opgeslagen (`szmPos`, `szmPosX`, ...).
Een `render_block`-filter zet die om naar een inline style op het blokelement.
Zo blijft de opgeslagen block-data schoon en kan de editor de attributen
bewerken zonder dat core de inline style weggooit. Editor-preview benadert de
frontend, maar de frontend is leidend (zie de wordpress-gutenberg skill).

## GitHub

Self-update wijst naar `github.com/Yelbow/szm-absolute-positioning` (zoals de
andere plugins). De remote moet nog aangemaakt worden; lokaal is dit een git-repo.