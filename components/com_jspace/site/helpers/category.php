<?php
class JSpaceCategories extends JCategories
{
    public function __construct($options = array())
    {
        $options['table'] = '#__jspace_records';
        $options['extension'] = 'com_jspace';
        $options['statefield'] = 'published';
        
        parent::__construct($options);
    }
}
