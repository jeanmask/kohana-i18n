<?php defined('SYSPATH') or die('No direct script access.');

abstract class i18n_Language {
	protected $_acronym;
	protected $_name;
	
	public static function factory($driver) {
		$driver_name = 'i18n_Language_Driver_' . str_replace('-','_', ucfirst($driver));
		return new $driver_name;
	}
	
	public function __construct() {
		if(empty($this->_acronym)) {
			throw new Kohana_Exception('acronym not set');
		}
	}
	
	public function __toString() {
		return $this->_acronym;
	}
	
	public function acronym() {
		return $this->_acronym;
	}
	
	public function name() {
		return $this->_name;
	}
}