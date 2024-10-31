<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
class SAN_Woosend_Logger {

	private $_handles;
	private $log_directory;

	public function __construct() {
		$upload_dir          = wp_upload_dir();
		$this->log_directory = $upload_dir['basedir'] . '/woosend-logs/';

		wp_mkdir_p( $this->log_directory );
	}

	private function open( $handle ) {
		if ( isset( $this->_handles[ $handle ] ) ) {
			return true;
		}

		if ( $this->_handles[ $handle ] = @fopen( $this->log_directory . $handle . '.log', 'a' ) ) {
			return true;
		}

		return false;
	}

	public function add( $handle, $message ) {
		if ( $this->open( $handle ) ) {
			@fwrite( $this->_handles[ $handle ], "$message\n" );
		}
	}

	public function clear( $handle ) {
		if ( $this->open( $handle ) ) {
			@fopen( $this->log_directory . $handle . '.log', 'w+' );
		}
	}

    public function get_log_file($handle)
    {
        $log_file = $this->log_directory . "{$handle}.log"; //The log file.
        if(file_exists($log_file)){
            return file_get_contents($log_file);
        }
    }
}

?>
