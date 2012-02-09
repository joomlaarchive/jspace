<?php
defined( '_JEXEC' ) or die;

jimport("sword.app.entry");

class SwordAppResponse extends SwordAppEntry {

    // Construct a new deposit response by passing in the http status code
    function __construct($sac_newstatus, $sac_thexml) {
        // Call the super constructor
	    parent::__construct($sac_newstatus, $sac_thexml);
        
    }

}

?>
