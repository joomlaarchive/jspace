<?php
defined( '_JEXEC' ) or die;

    // A class used to stream large multipart files
    class SwordStream {
        var $data;

        function stream_function($handle, $fd, $length) {
            return fread($this->data, $length);
        }
    }

?>