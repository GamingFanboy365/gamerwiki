<?php
/**
 * Router for PHP's built-in web server, used by the ./gamerwiki launcher:
 *
 *   php -S 127.0.0.1:8080 -t /path/to/gamerwiki maintenance/gamerwiki-router.php
 *
 * PHP's built-in server will run *any* .php file it is asked for and serve
 * any file under the document root. This router puts the same fences
 * around the wiki that the shipped .htaccess files give Apache users:
 *
 * - Only requests addressed to this computer (Host: 127.0.0.1, localhost
 *   or [::1]) are answered. This stops DNS-rebinding attacks, where a
 *   malicious website points its own hostname at 127.0.0.1 to read the
 *   wiki from your browser.
 * - Only MediaWiki's public entry points (index.php, api.php, load.php,
 *   ...) are executed. Every other PHP file is refused, so library code,
 *   maintenance scripts and anything that lands in images/ never run.
 * - Private directories (includes/, maintenance/, data/, cache/, ...) and
 *   files (LocalSettings.php, databases, dotfiles) are never served.
 * - Uploaded files are served with headers that stop browsers from
 *   running scripts embedded in them (e.g. JavaScript inside an SVG).
 *
 * @file
 * @ingroup Maintenance
 */

if ( PHP_SAPI !== 'cli-server' ) {
	exit( 1 );
}

$IP = dirname( __DIR__ );

/** Entry points that may be executed, relative to the wiki root. */
$entryPoints = array(
	'/index.php',
	'/api.php',
	'/load.php',
	'/thumb.php',
	'/img_auth.php',
	'/opensearch_desc.php',
	'/mw-config/index.php',
);

/** Top-level paths that are never served or executed. */
$privatePrefixes = array(
	'/includes/', '/maintenance/', '/languages/', '/serialized/', '/cache/',
	'/tests/', '/docs/', '/data/', '/bin/',
	'/images/deleted/', '/images/temp/', '/images/lockdir/',
	'/LocalSettings.php', '/StartProfiler.php',
);

/** File types that must never be executed or served as source. */
$scriptPattern = '/\.(php\d?|phtml|phar|inc|sql|sqlite\d?|db|sh|ini|log)$/i';

function gamerwikiDeny( $code, $text ) {
	http_response_code( $code );
	header( 'Content-Type: text/plain; charset=utf-8' );
	header( 'X-Content-Type-Options: nosniff' );
	echo "$code $text\n";
	return true;
}

// 1. Loopback hosts only (DNS rebinding protection).
$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( $_SERVER['HTTP_HOST'] ) : '';
$hostname = preg_replace( '/:\d+$/', '', $host );
if ( !in_array( $hostname, array( '127.0.0.1', 'localhost', '[::1]' ), true ) ) {
	return gamerwikiDeny( 403, 'Forbidden: this wiki only answers requests addressed to 127.0.0.1 or localhost.' );
}

$path = rawurldecode( parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) ?: '/' );
if ( strpos( $path, "\0" ) !== false || preg_match( '!(^|/)\.\.?(/|$)!', $path ) ) {
	return gamerwikiDeny( 400, 'Bad Request' );
}

if ( $path === '/' ) {
	header( 'Location: /index.php' );
	http_response_code( 302 );
	return true;
}

// 2. Public entry points run as normal (index.php/Page_title included).
$script = isset( $_SERVER['SCRIPT_NAME'] ) ? $_SERVER['SCRIPT_NAME'] : '';
if ( in_array( $script, $entryPoints, true ) ) {
	$isDirIndex = $script === '/mw-config/index.php' && $path === '/mw-config/';
	if ( $isDirIndex || $path === $script || strpos( $path, $script . '/' ) === 0 ) {
		return false;
	}
}

// 3. Nothing private, hidden or executable.
foreach ( $privatePrefixes as $prefix ) {
	if ( stripos( $path, $prefix ) === 0 ) {
		return gamerwikiDeny( 403, 'Forbidden' );
	}
}
if ( preg_match( '!/\.!', $path ) || preg_match( $scriptPattern, $path ) ) {
	return gamerwikiDeny( 403, 'Forbidden' );
}

$file = realpath( $IP . $path );
if ( $file === false || strpos( $file, $IP . DIRECTORY_SEPARATOR ) !== 0 || !is_file( $file ) ) {
	return gamerwikiDeny( 404, 'Not Found' );
}

// 4. Uploaded files: never let the browser treat them as an active page.
if ( stripos( $path, '/images/' ) === 0 ) {
	$types = array(
		'png' => 'image/png', 'gif' => 'image/gif', 'jpg' => 'image/jpeg',
		'jpeg' => 'image/jpeg', 'svg' => 'image/svg+xml', 'pdf' => 'application/pdf',
		'ogv' => 'video/ogg', 'oga' => 'audio/ogg', 'ogg' => 'audio/ogg',
	);
	$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	header( 'X-Content-Type-Options: nosniff' );
	// Scripts in an SVG opened directly cannot run; images may still be shown.
	header( "Content-Security-Policy: default-src 'none'; img-src 'self' data:; " .
		"style-src 'unsafe-inline'; media-src 'self'; sandbox" );
	if ( isset( $types[$ext] ) ) {
		header( 'Content-Type: ' . $types[$ext] );
	} else {
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment' );
	}
	header( 'Content-Length: ' . filesize( $file ) );
	header( 'Cache-Control: max-age=86400' );
	readfile( $file );
	return true;
}

// 5. Everything else (skins, resources, extension assets) is a static file.
return false;
