<?php

/**
 * Extension to provide a search interface when applied to ContentController.
 * 
 * @package lucene-silverstripe-module
 * @author Darren Inwood <darren.inwood@chrometoaster.com>
 */
class ZendSearchLuceneContentController extends Extension { 

    /**
     * Enables the search form to talk to the controller.
     * @access public
     * @static
     */
	public static $allowed_actions = array(
		'ZendSearchLuceneForm',
		'ZendSearchLuceneResults',
		'results'
	);

	/**
	 * Returns the Lucene-powered search Form object.
     * 
     * @access public
	 * @return  Form    A Form object representing the search form.
	 */
	public function ZendSearchLuceneForm() {
		return SS_Object::create('ZendSearchLuceneForm', $this->owner);
	}

	/**
	 * Process and render search results. Uses the Lucene_results.ss template to
	 * render the form.
	 * 
     * @access public
	 * @param   array           $data       The raw request data submitted by user
	 * @param   Form            $form       The form instance that was submitted
	 * @param   SS_HTTPRequest  $request    Request generated for this action
	 * @return  String                      The rendered form, for inclusion into the page template.
	 */
	public function ZendSearchLuceneResults($data, $form, $request) {
		$querystring = $form->Fields()->dataFieldByName('Search')->dataValue();
		$query = Zend_Search_Lucene_Search_QueryParser::parse($querystring);
		$hits = ZendSearchLuceneWrapper::find($query);
        	$data = $this->getDataArrayFromHits($hits, $request);
		return $this->owner->customise($data)->renderWith(array('Lucene_results', 'Page'));
	}

    /**
     * Wrapper around ZendSearchLuceneResults, for when we are using $SearchForm
     * in templates.
     */
    public function results($data, $form, $request) {
        return $this->ZendSearchLuceneResults($data, $form, $request);
    }

    /**
     * Makes $SearchForm included in many stock templates return a Lucene form
     * analogous to the one that the FulltextSearchable extension outputs. Uses
     * the SearchForm.ss template that comes with Sapphire (or an overridden
     * custom version if one is available, as per the regular SearchForm).
     *
     * @return String       The rendered form, for inclusion into the page template.
     */
    public function SearchForm() {
        $form = $this->ZendSearchLuceneForm();
        // Use the same CSS as the stock search form...
        $form->setHTMLId('SearchForm_SearchForm');
		$actions = $form->Actions();
		$action = SS_Object::create( 'FormAction', 'results', _t('SearchForm.GO', 'Go'));
		$action->setForm($form);
		$actions->replaceField('action_ZendSearchLuceneResults', $action);
        return $form->renderWith(array(
            'SearchForm', 'Page'
        ));
    }

    /**
     * Returns a data array suitable for customising a Page with, containing
     * search result and pagination information.
     * 
     * Returns a data array suitable for customising a Page with, containing
     * search result and pagination information.  The format of the return is:
     *
     * <code>
     * array(
     *     'Results' => DataObjectSet containing the objects found by the search
     *                  on the currently displayed page
     *     'Query' => The original query contained in a TextField object
     *     'Title' => The page title contained in a TextField object
     *     'TotalResults' => The total number of results found, as a TextField
     *     'TotalPages' => The total number of pages, as a TextField
     *     'ThisPage' => Page number of the current page, as a TextField
     *     'StartResult' => Number of the first result displayed on the current
     *                      page.
     *     'EndResult' => Number of the last result displayed on the current
     *                      page.
     *     'PrevUrl' => URL to get to the previous page of results.  False if
     *                  there are no results. A TextField object.
     *     'NextUrl => URL to get to the next page of results.  False if there 
     *                 are no results. A TextField object.
     *     'SearchPages' => A DataObjectSet containing the search pages to show
     *                      in pagination.
     * )
     * </code>
     *
     * Each result in Results is a bona fide DataObject stored in the database.
     * This may be any of the types searched, so you should ensure your search 
     * results template can display all types that can be returned.
     *
     * SearchPages contains a set of Objects that have three parameters:
     * <ul>
     *   <li>Link - the URL this page should link to.</li>
     *   <li>Current - a boolean indicating whether this page is the currently 
     *   displayed page.</li>
     *   <li>IsEllipsis - a boolean indicating whether this page is actually an
     *   ellipsis indicating more pages that aren't shown.</li>
     * </ul>
     *
     * Uses the ZendSearchLuceneSearchable::$pageLength, 
     * ZendSearchLuceneSearchable::$alwaysShowPages and 
     * ZendSearchLuceneSearchable::$maxShowPages static vars to indicate the 
     * pagination structure.
     * 
     * @access private
     * @param   Array           $hits       An array of Zend_Search_Lucene_Search_QueryHit objects
     * @param   SS_HTTPRequest  $request    The request that generated the search
     * @return  Array                       A custom array containing pagination data.
     */
    public function getDataArrayFromHits($hits, $request) {
		$data = array(
			'Results' => null,
			'Query' => null,
			'Title' => DBField::create_field('Text', _t('ZendSearchLucene.SearchResultsTitle', 'Search Results')),
			'TotalResults' => null,
			'TotalPages' => null,
			'ThisPage' => null,
			'StartResult' => null,
			'EndResult' => null,
			'PrevUrl' => DBField::create_field('Text', 'false'),
			'NextUrl' => DBField::create_field('Text', 'false'),
			'SearchPages' => new ArrayList()
		);
		
        $pageLength = ZendSearchLuceneSearchable::$pageLength;              // number of results per page
        $alwaysShowPages = ZendSearchLuceneSearchable::$alwaysShowPages;    // always show this many pages in pagination
        $maxShowPages = ZendSearchLuceneSearchable::$maxShowPages;          // maximum number of pages shown in pagination

		$start = $request->requestVar('start') ? (int)$request->requestVar('start') : 0;
		$currentPage = floor( $start / $pageLength ) + 1;
		$totalPages = ceil( count($hits) / $pageLength );
		if ( $totalPages == 0 ) $totalPages = 1;
		if ( $currentPage > $totalPages ) $currentPage = $totalPages;

        // Assemble final results after page number mangling
        $results = new ArrayList();
		foreach($hits as $k => $hit) {
		    if ( $k < ($currentPage-1)*$pageLength || $k >= ($currentPage*$pageLength) ) continue;
			$obj = DataObject::get_by_id($hit->ClassName, $hit->ObjectID);
			if ( ! $obj ) {
			    // The index is out of sync with reality - that item doesn't actually exist.
                continue;
			}
			$obj->score = $hit->score;
			$obj->Number = $k + 1; // which number result it is...
			$obj->Link = $hit->Link;
			$results->push($obj);
		}

	    $data['Results'] = $results;
	    $data['Query']   = DBField::create_field('Text', $request->requestVar('Search'));
	    $data['TotalResults'] = DBField::create_field('Text', count($hits));
        $data['TotalPages'] = DBField::create_field('Text', $totalPages);
        $data['ThisPage'] = DBField::create_field('Text', $currentPage);
        $data['StartResult'] = $start + 1;
        $data['EndResult'] = $start + count($results);

        // Helper to get the pagination URLs
        function build_search_url($params) {
	        $url = parse_url($_SERVER['REQUEST_URI']);
	        if ( ! array_key_exists('query', $url) ) $url['query'] = '';
            parse_str($url['query'], $url['query']);
            if ( ! is_array($url['query']) ) $url['query'] = array();
            // Remove 'start parameter if it exists
            if ( array_key_exists('start', $url['query']) ) unset( $url['query']['start'] );
            // Add extra parameters from argument
            $url['query'] = array_merge($url['query'], $params);
            $url['query'] = http_build_query($url['query']);
            $url = $url['path'] . ($url['query'] ? '?'.$url['query'] : '');
            return $url;
        }

        // Pagination links
        if ( $currentPage > 1 ) {
            $data['PrevUrl'] = DBField::create_field('Text', 
                build_search_url(array('start' => ($currentPage - 2) * $pageLength))
            );
        }
        if ( $currentPage < $totalPages ) {
            $data['NextUrl'] = DBField::create_field('Text', 
                build_search_url(array('start' => $currentPage * $pageLength))
            );
        }
        if ( $totalPages > 1 ) {
            // Always show a certain number of pages at the start
            for ( $i = 1; $i <= min($totalPages, $alwaysShowPages ); $i++ ) {
                $obj = new DataObject();
                $obj->IsEllipsis = false;
                $obj->PageNumber = $i;
                $obj->Link = build_search_url(array(
                    'start' => ($i - 1) * $pageLength
                ));
                $obj->Current = false;
                if ( $i == $currentPage ) $obj->Current = true;
                $data['SearchPages']->push($obj);
            }
            if ( $totalPages > $alwaysShowPages ) {
                // Start showing pages from 
                $extraPagesStart = max($currentPage-1, $alwaysShowPages+1);
                if ( $totalPages <= $maxShowPages ) {
                    $extraPagesStart = $alwaysShowPages + 1;
                }
                $extraPagesEnd = min($extraPagesStart + ($maxShowPages - $alwaysShowPages) - 1, $totalPages);
                if ( $extraPagesStart > ($alwaysShowPages+1) ) {
                    // Ellipsis to denote that there are more pages in the middle
                    $obj = new DataObject();
                    $obj->IsEllipsis = true;
                    $obj->Link = false;
                    $obj->Current = false;
                    $data['SearchPages']->push($obj);                    
                }
                for ( $i = $extraPagesStart; $i <= $extraPagesEnd; $i++ ) {
                    $obj = new DataObject();
                    $obj->IsEllipsis = false;
                    $obj->PageNumber = $i;
                    $obj->Link = build_search_url(array(
                        'start' => ($i - 1) * $pageLength
                    ));
                    $obj->Current = false;
                    if ( $i == $currentPage ) $obj->Current = true;
                    $data['SearchPages']->push($obj);                    
                }
                if ( $extraPagesEnd < $totalPages ) {
                    // Ellipsis to denote that there are more pages after
                    $obj = new DataObject();
                    $obj->IsEllipsis = true;
                    $obj->Link = false;
                    $obj->Current = false;
                    $data['SearchPages']->push($obj);                    
                }                
            }
        }

        return $data;
    }

}
