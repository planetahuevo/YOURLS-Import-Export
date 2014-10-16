<?php
/*
Plugin Name: YOURLS Import Export
Plugin URI: http://gaut.am/plugins/yourls/yourls-import-export/
Description: Import and Export the URLs
Version: 1.0
Author: Gautam
Author URI: http://gaut.am/
================================================================================
This software is provided "as is" and any express or implied warranties,
including, but not limited to, the implied warranties of merchantibility and
fitness for a particular purpose are disclaimed. In no event shall the copyright
owner or contributors be liable for any direct, indirect, incidental, special,
exemplary, or consequential damages(including, but not limited to, procurement
of substitute goods or services; loss of use, data, or profits; or business
interruption) however caused and on any theory of liability, whether in
contract, strict liability, or tort(including negligence or otherwise) arising
in any way out of the use of this software, even if advised of the possibility
of such damage.

For full license details see license.txt
================================================================================
*/

/**
 * Get the supported export formats
 *
 * @return array Supported export formats
 */
function yourls_imex_get_export_formats() {
	return array(
		'csv' => 'Comma Separated Values (CSV)',
	);
}
session_start();

/**
 * Add the plugin page in the menu
 */
function yourls_imex_add_page() {
	yourls_register_plugin_page( 'import_export', 'Import/Export', 'yourls_imex_do_page' );
}

/**
 * Display admin page
 */
function yourls_imex_do_page() {
	$export_urls = array();
	global $ydb;
	$rows = $ydb->get_results("show tables");
	$table_url = YOURLS_DB_TABLE_LOG;

	echo <<<HTML
		<br />
		<h2>Export</h2>
HTML;
	$shorturls = array();
	$items = $ydb->get_results("select distinct shorturl from `$table_url`");
	foreach($items as $item) {
		$shorturls[] = $item->{"shorturl"};
	}
	echo '<form method="post" action="">';
    echo '<select id="shorturl" name="shorturl"><OPTION>';
    echo "Select a url</OPTION>";
    foreach ($shorturls as $shorturl) {
        echo "<OPTION value=\"$shorturl\">$shorturl</OPTION>";
    }
    echo '</SELECT>';
    echo '<input type="submit" name="submit" value="submit"/>';
    echo '</form>';

	foreach ( yourls_imex_get_export_formats() as $export_option => $export_label ) {
		$export_urls[$export_option] = '<a href="' . yourls_nonce_url( 'imex_export_' . $export_option, yourls_add_query_arg( array( 'export' => $export_option ) ) ) . '" title="Export URLs in ' . $export_label . ' format">' . $export_label . '</a>';
	}
	$_SESSION["shorturl"] = $_POST["shorturl"];
	echo "<br>";
	if (isset($_SESSION["shorturl"])) {
		echo "<strong>Campaign chosen: </strong>" . $_SESSION["shorturl"];
	} else {
		echo "<strong>No campaign chosen.</strong>";
	}
	echo "<br><br>";
	echo '<strong>Export URLs in</strong>: ' . implode( ' | ', $export_urls );
	echo "<br><br>";
}

/**
 * Handle import/export
 */
function yourls_imex_handle_post()
{
	// Import
	if ( !empty( $_FILES['import'] ) && !empty( $_POST['nonce'] ) && yourls_verify_nonce( 'imex_import', $_POST['nonce'] ) ) {
		$count = yourls_imex_import_urls( $_FILES['import'] );

		if ( $count > 0 )
			$message = $count . ' redirection(s) were successfully imported.';
		else
			$message = 'No items were imported.';
	}

	// Export
	if( isset( $_GET['export'] ) && !empty( $_GET['nonce'] ) ) {

		$format = in_array( $_GET['export'], array_keys( yourls_imex_get_export_formats() ) ) ? $_GET['export'] : 'csv';

		yourls_verify_nonce( 'imex_export_' . $format, $_GET['nonce'] );

		if ( yourls_imex_export_urls( $format , $_GET['shorturl']) )
			die();
		else
			$message = 'YOURLS export failed!';
	}

	// Message
	if ( !empty( $message ) )
		yourls_add_notice($message);
}

/**
 * Import the urls
 * @param type $import_file Uploaded file to be imported
 * @return int|bool Count of imported redirections or false on failure
 */
function yourls_imex_import_urls( $import_file ) {
	require_once 'fileio/file_io.php';

	$importer = new Red_FileIO;
	return $importer->import( $import_file );
}

/**
 * Export the urls
 *
 * @param string $format Format of the file to be exported. Check {@link yourls_imex_get_export_formats()}
 * @return boolean True on success, false on failure
 */
function yourls_imex_export_urls( $format = 'csv' ) {
	$format = in_array( $format, array_keys( yourls_imex_get_export_formats() ) ) ? $format : 'csv';

	require_once 'fileio/file_io.php';

	$exporter = new Red_FileIO;
	return $exporter->export( $format );
}

// Register our plugin admin page
yourls_add_action( 'plugins_loaded', 'yourls_imex_add_page' );

// Handle import/export
yourls_add_action( 'load-import_export', 'yourls_imex_handle_post' );
