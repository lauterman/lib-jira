<?php


/**
 * Service Desk Mail Parser class
 *
 * @package lib-jira
 * @file ServiceDeskMailParser.class.php
 * @created 05.120.2013
 * @author JKCampbell <jimcampbell@psu.edu>
 */

class ServiceDeskMailParser extends MimeMailParser {
    
    public function getParts() {
    	return $this->parts;
    }
    
    public function getPartsStub($key) {
        return $this->parts[$key];
    }
    
    public function getPartsHeader($key) {
        return $this->getPartHeaderRaw($this->parts[$key]);
    }
    
    public function getPartsContent($key) {
        return $this->getPartBody($this->parts[$key]);
    }
}
?>