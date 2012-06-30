<?php defined('SYSPATH') or die('No direct script access.');

abstract class i18n_ORM extends ORM {
	
	/**
	  * @var $_i18n_default_language
	  */		
	protected $_i18n_default_language = 'en-us';
	
	/**
	  * @var $_i18n_avaible_languages
	  */			
	protected $_i18n_avaible_languages = array('en-us');
	
	/**
	 * @var $_i18n_default_inside
	 */
	protected $_i18n_default_inside = false;

	/**
	  * @var $_i18n_rel_name
	  */	
	protected $_i18n_rel_name = 'i18n';
	
	/**
	  * @var $_i18n_rel_column_language
	  */
	protected $_i18n_rel_column_language = 'lang';

	/**
	  * @var $_i18n_rel_column_fk
	  */	
	protected $_i18n_rel_column_fk;
	
	/**
	  * @var $_i18n_colums
	  */	
	protected $_i18n_columns = array();
	
	/**
	  * @var $_i18n_language
	  */	
	protected $_i18n_language;
	
	/**
	  * @var $_i18n_auto_get
	  */	
	protected $_i18n_auto_get = false;

	/**
	  * @var $_i18n_translations
	  */	
	protected $_i18n_translations = array();
	
	public function __construct($id=null) {
		parent::__construct($id);
		
		$this->_i18n_rel_column_fk =& $this->_has_many[$this->_i18n_rel_name]['foreign_key'];
		$this->_i18n_language = i18n_Language::factory($this->_i18n_default_language);
		$this->_i18n_avaible_languages = Arr::map('i18n_Language::factory', $this->_i18n_avaible_languages);
	}
	
	public function __get($column) {
		if(in_array($column, $this->_i18n_columns)) {
			$i18n_value = $this->i18n_get_column($column, $this->_i18n_language);
			if($i18n_value !== false) {
				return $i18n_value;
			}
			
			if(!$this->_i18n_auto_get) {
				return null;
			}
		}
		
		return parent::__get($column);
	}
	
	public function i18n_get_column($column, i18n_Language $lang) {
		$acronym = $lang->acronym();
		
		if(!in_array($column, $this->_i18n_columns)) {
			throw new Kohana_Exception('this column is not translable');
		}
		
		if($this->_i18n_default_inside && $acronym == $this->_i18n_default_language) {
			return parent::__get($column);
		}
		
		if($this->_i18n_load_language($lang)) {
			return $this->_i18n_translations[$acronym]->{$column};
		}
		
		return false;
	}
	
	public function i18n_set_column($column, $value, i18n_Language $lang) {
		$acronym = $lang->acronym();
		
		if(!$this->_i18n_default_inside || $acronym != $this->_i18n_default_language) {
			
			if($this->_i18n_load_language($lang)) {
				$translation = $this->_i18n_translations[$acronym];
			}
			else {
				$translation = $this->_i18n_translations[$acronym] = $this->{$this->_i18n_rel_name};
				$translation->{$this->_i18n_rel_column_language} = $acronym;
			}
			
			$translation->{$column} = $value;
		}
		else {
			$this->{$column} = $value;
		}
	}
	
	public function i18n_auto_get($bool) {
		$this->_i18n_auto_get = (bool)$bool;
		return $this;
	}
	
	public function i18n_set_language($lang) {
		$this->_i18n_language = $lang instanceof i18n_Language ? $lang : i18n_Language::factory($lang);
		return $this;
	}
	
	public function i18n_get_language() {
		return $this->_i18n_language;
	}
	
	public function i18n_columns() {
		return $this->_i18n_columns;
	}
	
	public function i18n_avaible_languages($exclude_inside=false) {
		return $exclude_inside && $this->_i18n_default_inside 
			? array_diff($this->_i18n_avaible_languages, array($this->_i18n_default_language))
			: $this->_i18n_avaible_languages;
	}

	protected function _i18n_load_language(i18n_Language $lang) {
		$acronym = $lang->acronym();
		
		if(!array_key_exists($acronym, $this->_i18n_translations)) {
			if(!$this->loaded()) {
				return false;
			}
			
			$translation = $this->{$this->_i18n_rel_name}->where($this->_i18n_rel_column_language, '=', $acronym)->find();
			
			if($translation->loaded()) {
				$this->_i18n_translations[$acronym] = $translation;
				return true;
			}
			
			return false;
		}
		
		return true;
	}
	
	protected function _i18n_save() {
		if($this->loaded()) {
			foreach($this->_i18n_translations as &$translation) {
				if(!is_numeric($translation->{$this->_i18n_rel_column_fk})) {
					$translation->{$this->_i18n_rel_column_fk} = $this->pk();
				}
				$translation->save();
			}
		}		
	}
	
	public function create(Validation $validation = null) {
		$create = parent::create($validation);
		$this->_i18n_save();
		return $create;
	}
	
	public function update(Validation $validation = null) {
		$update = parent::update($validation);
		$this->_i18n_save();
		return $update;
	}
}