<?php

// Code heavily borrowed from the Redirection plugin (http://urbangiraffe.com/plugins/redirection/) for WordPress by John Godley

class Red_FileIO
{
	var $items = array();

	function export ( $type ) {
		global $ydb;

		// Variables
		$table_url = YOURLS_DB_TABLE_URL;
		$table_log = YOURLS_DB_TABLE_LOG;
		
		// Main Query
		if (isset($_SESSION["shorturl"])) {
			$shorturl = $_SESSION["shorturl"];
			$items = $ydb->get_results("SELECT * FROM `$table_log` where `shorturl`='$shorturl'");
		} else {
			return false;
		}
		session_destroy();
		if ( !empty( $items ) )
		{
			require_once $type . '.php';

			if ($type == 'rss')
				$exporter = new Red_Rss_File();
			else if ($type == 'xml')
				$exporter = new Red_Xml_File();
			else if ($type == 'csv')
				$exporter = new Red_Csv_File();
			else if ($type == 'apache')
				$exporter = new Red_Apache_File();


			$file = $shorturl . '.csv';
			$contents = "click_id,click_time,shorturl,referrer,user_agent,ip_address,country_code\r\n";
			foreach ($items as $item) {
				$contents .= $this->escape($item->click_id) . "," . $this->escape($item->click_time) . "," . $this->escape($item->shorturl) . "," .
							$this->escape($item->referrer) . "," . $this->escape($item->user_agent) . "," . $this->escape($items->ip_address) . "," .
							$this->escape($item->country_code) . "\r\n";
			}
			if (file_put_contents("csv/" . $file, $contents) === false) {
				return false;
			}
			if (file_exists("csv/" . $file)) {
			    header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
			    header('Content-Disposition: attachment; filename='.basename("csv/" . $file));
			    header('Content-Transfer-Encoding: binary');
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . filesize("csv/" . $file));
			    ob_clean();
			    flush();
			    readfile("csv/" . $file);
			    exit;
			}
			return true;
		}

		return false;
	}

	function import ( $file ) {
		if ( is_uploaded_file( $file['tmp_name'] ) ) {
			$parts = pathinfo( $file['name'] );

			if ( $parts['extension'] == 'xml') {
				include dirname( __FILE__ ).'/xml.php';
				$importer = new Red_Xml_File();
				$data = @file_get_contents( $file['tmp_name'] );
			}
			elseif ( $parts['extension'] == 'csv' ) {
				include dirname( __FILE__ ).'/csv.php';
				$importer = new Red_Csv_File();
				$data = '';
			}
			else {
				include dirname( __FILE__ ).'/apache.php';
				$importer = new Red_Apache_File();
				$data = @file_get_contents( $file['tmp_name'] );
			}

			return $importer->load( $data, $file['tmp_name'] );
		}

		return 0;
	}
	function escape ($value)
	{
		// Escape any special values
		$double = false;
		if (strpos ($value, ',') !== false || $value == '')
			$double = true;

		if (strpos ($value, '"') !== false)
		{
			$double = true;
			$value  = str_replace ('"', '""', $value);
		}

		if ($double)
			$value = '"'.$value.'"';
		return $value;
	}

	function load ( $data, $filename ) { }
}

?>
