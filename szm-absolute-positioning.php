<?php
/**
 * Plugin Name: SZM Absolute Positioning
 * Description: Framer-achtige absolute positionering in de site editor. Per blok
 * (Group, Cover, Column, Heading, Paragraph, Image, Media & Text, Button) een
 * inspector-paneel "Absoluut positioneren": X/Y in procent, breedte, z-index en
 * horizontaal anker. Het blok wordt position:absolute binnen zijn directe ouder
 * (die automatisch relative wordt), responsive via percentages. Geen nieuwe
 * blokken, alles gegraft op bestaande core-blokken, editor-preview én frontend.
 * Version: 0.1.0
 * Author: Studio Zonder Meer
 * Text Domain: szm-absolute-positioning
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SZM_AP_VERSION', '0.1.0' );
define( 'SZM_AP_DIR', plugin_dir_path( __FILE__ ) );
define( 'SZM_AP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Welke blokken het paneel "Absoluut positioneren" krijgen.
 * Uitbreidbaar via het php-filter szm_ap_supported_blocks.
 */
function szm_ap_get_supported_blocks() {
	return apply_filters(
		'szm_ap_supported_blocks',
		array(
			'core/group',
			'core/cover',
			'core/column',
			'core/columns',
			'core/heading',
			'core/paragraph',
			'core/image',
			'core/media-text',
			'core/button',
		)
	);
}

/**
 * Editor-script: voegt attributes (szmPos*) toe en toont het inspector-paneel.
 */
function szm_ap_enqueue_editor_assets() {
	$deps = array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-hooks',
		'wp-i18n',
		'wp-compose',
		'wp-data',
	);

	wp_enqueue_script(
		'szm-ap-editor',
		SZM_AP_URL . 'assets/editor.js',
		$deps,
		SZM_AP_VERSION,
		true
	);

	wp_localize_script(
		'szm-ap-editor',
		'szmAbsolutePositioning',
		array(
			'blocks' => szm_ap_get_supported_blocks(),
		)
	);

	wp_enqueue_style(
		'szm-ap-editor-style',
		SZM_AP_URL . 'assets/editor.css',
		array(),
		SZM_AP_VERSION
	);
}
add_action( 'enqueue_block_editor_assets', 'szm_ap_enqueue_editor_assets' );

/**
 * Front-end: de positionerende CSS. Positionering zelf is pure inline style
 * gezet door render_block hieronder; geen JS nodig op de frontend.
 */
function szm_ap_enqueue_frontend_assets() {
	wp_enqueue_style(
		'szm-ap-style',
		SZM_AP_URL . 'assets/style.css',
		array(),
		SZM_AP_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'szm_ap_enqueue_frontend_assets' );

/* ---- Hulpjes (alleen 100% zekere PHP-primitieven, bewust geen exotische functies) ---- */

/** Tolerant lezen van een boolean-achtige attribute. */
function szm_ap_attr_true( $v ) {
	return ( $v === true || $v === 1 || $v === '1' || $v === 'true' );
}

/** '50' -> '50%', geklemd op 0..100. */
function szm_ap_pct( $v ) {
	$n = (float) $v;
	if ( $n < 0 ) { $n = 0; }
	if ( $n > 100 ) { $n = 100; }
	return (string) $n . '%';
}

/** Positie (index) van het eerste '>' in $html, of -1 als er geen is. */
function szm_ap_first_gt( $html ) {
	$len = strlen( $html );
	for ( $i = 0; $i < $len; $i++ ) {
		if ( $html[ $i ] === '>' ) {
			return $i;
		}
	}
	return -1;
}

/** Zit $name in de lijst $list? */
function szm_ap_in_list( $list, $name ) {
	foreach ( $list as $item ) {
		if ( $item === $name ) {
			return true;
		}
	}
	return false;
}

/**
 * Rendert de positionering op de frontend. De waarden zijn eigen attributes
 * (szmPos, szmPosX, ...) die editor.js schrijft; dit filter zet ze om naar een
 * inline style én een class op het eerste element van het blok. Zo blijft de
 * opgeslagen block-data schoon en kan de editor de attributen gewoon bewerken.
 */
function szm_ap_render_block( $content, $block ) {
	if ( ! is_array( $block ) || ! is_array( $block['attrs'] ?? null ) ) {
		return $content;
	}
	$attrs = $block['attrs'];

	if ( ! szm_ap_attr_true( $attrs['szmPos'] ?? false ) ) {
		return $content;
	}

	$name = $block['blockName'] ?? '';
	if ( ! szm_ap_in_list( szm_ap_get_supported_blocks(), $name ) ) {
		return $content;
	}

	$style = szm_ap_build_style_string( $attrs );
	if ( $style === '' ) {
		return $content;
	}

	// Openings-tag: alles tot en met het eerste '>'.
	$gt = szm_ap_first_gt( $content );
	if ( $gt < 0 ) {
		return $content;
	}

	$open = '';
	for ( $i = 0; $i <= $gt; $i++ ) {
		$open .= $content[ $i ];
	}

	// Class veilig samenvoegen: als er al class="..." staat, plakken we erachter.
	if ( szm_ap_has_class_attr( $open ) ) {
		$open = szm_ap_add_class( $open, 'szm-pos-absolute' );
	} else {
		$open .= ' class="szm-pos-absolute"';
	}

	// Style altijd als los attribuut vóór het sluitende '>' (verwijder die even).
	$open = substr( $open, 0, strlen( $open ) - 1 ) . ' style="' . esc_attr( $style ) . '">';

	// Rest van het blok erachter plakken.
	$rest = '';
	for ( $i = $gt + 1; $i < strlen( $content ); $i++ ) {
		$rest .= $content[ $i ];
	}

	return $open . $rest;
}

/** Heeft de openings-tag al een class-attribuut (class=" of class=')? */
function szm_ap_has_class_attr( $open ) {
	$len = strlen( $open );
	for ( $i = 0; $i < $len - 8; $i++ ) {
		if ( $open[ $i ] === 'c' && substr5( $open, $i ) === 'class=' ) {
			return true;
		}
	}
	return false;
}

/** Small helper: vergelijkt 'class=' vanaf offset zonder substr(). */
function substr5( $s, $off ) {
	$want = 'class=';
	$len  = strlen( $s );
	for ( $j = 0; $j < 6; $j++ ) {
		if ( $off + $j >= $len || $s[ $off + $j ] !== $want[ $j ] ) {
			return '';
		}
	}
	return 'class=';
}

/** Voegt een class toe aan het eerste class="..." attribuut in de openings-tag. */
function szm_ap_add_class( $open, $cls ) {
	// Vind het eerste aanhalingsteken dat bij 'class="' hoort en plak erna.
	$needle = 'class=';
	$nlen   = strlen( $needle );
	$len    = strlen( $open );
	for ( $i = 0; $i < $len - $nlen; $i++ ) {
		if ( $open[ $i ] === 'c' && szm_ap_eq_at( $open, $i, $needle ) ) {
			// quote na 'class=' (ondersteun " en ')
			$q = $open[ $i + $nlen ];
			if ( $q === '"' || $q === "'" ) {
				$before = szm_ap_slice( $open, 0, $i + $nlen + 1 );
				$after  = szm_ap_slice( $open, $i + $nlen + 1, $len );
				return $before . $cls . ' ' . $after;
			}
		}
	}
	return $open . ' class="' . $cls . '"';
}

/** Vergelijkt of $s[off..off+nlen] gelijk is aan $needle (zonder substr()). */
function szm_ap_eq_at( $s, $off, $needle ) {
	$nlen = strlen( $needle );
	$len  = strlen( $s );
	for ( $j = 0; $j < $nlen; $j++ ) {
		if ( $off + $j >= $len || $s[ $off + $j ] !== $needle[ $j ] ) {
			return false;
		}
	}
	return true;
}

/** slice($s, start, end) zonder substr(): bouwt de substring met een loop. */
function szm_ap_slice( $s, $start, $end ) {
	$len = strlen( $s );
	if ( $start < 0 ) { $start = 0; }
	if ( $end > $len ) { $end = $len; }
	$out = '';
	for ( $i = $start; $i < $end; $i++ ) {
		$out .= $s[ $i ];
	}
	return $out;
}

/**
 * Bouwt de inline style-string uit de szmPos*-attributen. Procenten zodat het
 * responsive blijft; anker bepaalt of de positie vanaf links, midden of rechts
 * gerekend wordt.
 */
function szm_ap_build_style_string( $attrs ) {
	$parts = array( 'position:absolute' );

	$anchor = $attrs['szmPosXAnchor'] ?? 'left';
	$x      = $attrs['szmPosX'] ?? null;
	$y      = $attrs['szmPosY'] ?? null;
	$w      = $attrs['szmPosWidth'] ?? null;
	$z      = $attrs['szmPosZ'] ?? null;

	if ( $x !== null && $x !== '' ) {
		if ( $anchor === 'center' ) {
			$parts[] = 'left:50%;transform:translateX(-50%)';
		} elseif ( $anchor === 'right' ) {
			$parts[] = 'right:' . szm_ap_pct( $x );
		} else {
			$parts[] = 'left:' . szm_ap_pct( $x );
		}
	}

	if ( $y !== null && $y !== '' ) {
		$parts[] = 'top:' . szm_ap_pct( $y );
	}

	if ( $w !== null && $w !== '' ) {
		$parts[] = 'width:' . szm_ap_pct( $w );
	}

	if ( $z !== null && $z !== '' && (int) $z > 0 ) {
		$parts[] = 'z-index:' . (int) $z;
	}

	return implode( ';', $parts );
}

add_filter( 'render_block', 'szm_ap_render_block', 10, 2 );

/**
 * Self-updates through WordPress's native Plugins/Updates screen (zelfde als de
 * andere SZM-plugins). Remote: github.com/Yelbow/szm-absolute-positioning.
 * Voor een privé-repo: setAuthentication met een read-only token.
 */
require_once __DIR__ . '/inc/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5p4\PucFactory;
add_action( 'init', function () {
	$update_checker = PucFactory::buildUpdateChecker(
		'https://github.com/Yelbow/szm-absolute-positioning',
		__FILE__,
		'szm-absolute-positioning'
	);
	$update_checker->setBranch( 'main' );
	// $update_checker->setAuthentication( '«redacted:ghp_…»' );
} );