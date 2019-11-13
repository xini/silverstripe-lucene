<?php

/**
 * The search form.
 * 
 * @package lucene-silverstripe-module
 * @author Darren Inwood <darren.inwood@chrometoaster.com>
 */
class ZendSearchLuceneForm extends Form {

    public function __construct($controller) {
		$searchText = isset($_REQUEST['Search']) ? $_REQUEST['Search'] : '';
		$fields = SS_Object::create('FieldList', 
			SS_Object::create('TextField',
			    'Search', 
			    '',
			    $searchText
			)
		);
		$actions = SS_Object::create( 'FieldList',
			SS_Object::create('FormAction', 'ZendSearchLuceneResults', _t('SearchForm.GO', 'Go'))
		);
		parent::__construct($controller, 'ZendSearchLuceneForm', $fields, $actions);
        $this->disableSecurityToken();
        $this->setFormMethod('get');    
    }

	public function forTemplate() {
		return $this->renderWith(array(
			'ZendSearchLuceneForm',
			'Form'
		));
	}

}

