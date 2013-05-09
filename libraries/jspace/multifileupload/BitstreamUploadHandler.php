<?php
/*
 * jQuery File Upload Plugin PHP Class 6.1.2
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */
jimport("jspace.multifileupload.UploadHandler");

class BitstreamUploadHandler extends UploadHandler
{
    protected function generate_response($content, $print_response = true) {
    	if( isset($this->options['bundle']) ) {
	    	$bundle = $this->options['bundle'];
	    	$files = $content[ $this->options['param_name'] ];
	    	$tmp = array();
	    	foreach( $files as $file ) {
	    		$bitstream = $bundle->addBitstream($this->options['upload_dir'] . $file->name);
	    		$file->id = $bitstream->id;
	    		$file->delete_url .= '&bitstream_id=' . $bitstream->id;
	    		$tmp[] = $file;
	    		
	    		if( isset($file->thumbnail_url) ) {
	    			$bitstream->setAssociatedFile('thumbnail', 'thumbnail' . DS . $file->name);
	    		}
	    	}
	    	$content[ $this->options['param_name'] ] = $tmp;
    	}
    	
        if ($print_response) {
            $json = json_encode($content);
            $redirect = isset($_REQUEST['redirect']) ?
                stripslashes($_REQUEST['redirect']) : null;
            if ($redirect) {
                $this->header('Location: '.sprintf($redirect, rawurlencode($json)));
                return;
            }
            $this->head();
            if (isset($_SERVER['HTTP_CONTENT_RANGE'])) {
                $files = isset($content[$this->options['param_name']]) ?
                    $content[$this->options['param_name']] : null;
                if ($files && is_array($files) && is_object($files[0]) && $files[0]->size) {
                    $this->header('Range: 0-'.($this->fix_integer_overflow(intval($files[0]->size)) - 1));
                }
            }
            $this->body($json);
        }
        return $content;
    }
}
