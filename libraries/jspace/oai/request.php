<?php
/**
 *
 * @package     JSpace
 * @subpackage  Oai
 * @copyright   Copyright (C) 2013-2014 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace library for Joomla!.

   The JSpace library for Joomla! is free software: you can redistribute it
   and/or modify it under the terms of the GNU General Public License as
   published by the Free Software Foundation, either version 3 of the License,
   or (at your option) any later version.

   The JSpace library for Joomla! is distributed in the hope that it will be
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace library for Joomla!.  If not, see
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have
 * contributed any source code changes.
 * @author  Michał Kocztorz <michalkocztorz@wijiti.com>
 * @author  Hayden Young <hayden@knowledgearc.com>
 */

defined('JPATH_PLATFORM') or die;

JLoader::import('jspace.archive.record');
JLoader::import('jspace.oai.exception');

/**
 * @package     JSpace
 * @subpackage  OAI
 */
class JSpaceOAIRequest extends JObject
{
    const LIMIT = 100;

    const GRANULARITY = 'Y-m-d\TH:i:s\Z';

    /**
     * Provides a list of OAI-aware verbs and their configurations.
     */
    protected static $verbs = array(
        // http://www.openarchives.org/OAI/openarchivesprotocol.html#GetRecord
        'GetRecord'=>array(
            'mandatory'=>array('metadataPrefix', 'identifier')),
        // http://www.openarchives.org/OAI/openarchivesprotocol.html#Identify
        'Identify'=>array(),
        // http://www.openarchives.org/OAI/openarchivesprotocol.html#ListIdentifiers
        'ListIdentifiers'=>array(
            'mandatory'=>array('metadataPrefix'),
            'optional'=>array('from', 'until', 'set'),
            'canResume'=>true),
        //http://www.openarchives.org/OAI/openarchivesprotocol.html#ListMetadataFormats
        'ListMetadataFormats'=>array(
            'optional'=>array('identifier'),
        ),
        // http://www.openarchives.org/OAI/openarchivesprotocol.html#ListRecords
        'ListRecords'=>array(
            'mandatory'=>array('metadataPrefix'),
            'optional'=>array('from', 'until', 'set'),
            'canResume'=>true),
        // http://www.openarchives.org/OAI/openarchivesprotocol.html#ListSets
        'ListSets'=>array(
            'optional'=>array('identifier'),
            'canResume'=>true)
    );

    /**
     * Initiates an OAI request.
     *
     * @param  array  An array of query string properties.
     */
    public function __construct($properties)
    {
        JFactory::getLanguage()->load('lib_jspace');
        JLog::addLogger(array());

        $allowedProperties = array();

        // sanitize
        if ($verb = JArrayHelper::getValue($properties, 'verb')) {

            $verbSettings = JArrayHelper::getValue(self::$verbs, $verb);
            $allowedProperties = array_merge(
                JArrayHelper::getValue($verbSettings, 'mandatory', array()),
                JArrayHelper::getValue($verbSettings, 'optional', array()));

            $allowedProperties[] = 'verb';
        }

        foreach (array_keys($properties) as $property) {
            if (array_search($property, $allowedProperties) === false) {
                unset($properties[$property]);
            }
        }

        parent::__construct($properties);
    }

    /**
     * Gets a list of OAI-aware verbs.
     *
     * @return array
     */
    public static function getVerbs()
    {
        return self::$verbs;
    }

    /**
     * Gets a response for the OAI request.
     *
     * @return  DomDocument  A response for the OAI request.
     *
     * @throws  Exception    Thrown if an unhandled error occurs whilst building the response.
     */
    public function getResponse()
    {
        $config = JSpaceFactory::getConfig();

        $responseDate = JDate::getInstance('now', 'UTC')->format(self::GRANULARITY);

        $response = new DomDocument('1.0', 'UTF-8');
        $oaiPmh = $response->createElement("OAI-PMH");
        $oaiPmh->setAttribute('xmlns', "http://www.openarchives.org/OAI/2.0/");
        $oaiPmh->setAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
        $oaiPmh->setAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/ http://www.openarchives.org/OAI/2.0/OAI-PMH.xsd');

        $oaiPmh->appendChild($response->createElement('responseDate', $responseDate));

        $response->appendChild($oaiPmh);

        $verb = $this->get('verb');

        $request = $response->createElement('request', htmlentities($this->getRequestUri()));
        $oaiPmh->appendChild($request);

        foreach ($this->getProperties() as $key=>$value) {
            $request->setAttribute($key, $value);
        }

        try {
            if (array_search($verb, array_keys(self::$verbs), true) !== false) {
                // if there is a resumption token, bind its values for use in the OAI endpoints.
                if ($this->get('resumptionToken')) {
                    $resumptionToken = json_decode(base64_decode($this->get('resumptionToken')));

                    if (!$resumptionToken instanceof stdClass) {
                        throw new JSpaceOAIException(JText::_('LIB_JSPACE_OAI_EXCEPTION_BADRESUMPTIONTOKEN_LABEL'), 'badResumptionToken');
                    }

                    $created = JFactory::getDate(
                        $resumptionToken->created.
                        $config->get('oai_resumptiontokenexpires', '+1 week'));

                    if (JFactory::getDate('now') > $created) {
                        throw new JSpaceOAIException(JText::_('LIB_JSPACE_OAI_EXCEPTION_RESUMPTIONTOKENEXPIRED_LABEL'), 'badResumptionToken');
                    }

                    $this->setProperties($resumptionToken);
                }

                if (!$this->hasValidArguments()) {
                    throw new JSpaceOAIException(
                        JText::_('LIB_JSPACE_OAI_EXCEPTION_BADARGUMENT_LABEL'),
                        'badArgument');
                }

                if (!$this->canDisseminateFormat()) {
                    throw new JSpaceOAIException(JText::_('LIB_JSPACE_OAI_EXCEPTION_CANNOTDISSEMINATEFORMAT_LABEL'), 'cannotDisseminateFormat');
                }

                $verb = lcfirst($verb);
                $node = $response->importNode($this->$verb(), true);
                $response->documentElement->appendChild($node);
            } else {
                throw new JSpaceOAIException(
                    JText::_('LIB_JSPACE_OAI_EXCEPTION_BADVERB_LABEL'),
                    'badVerb');
            }
        } catch (Exception $e) {
            if ($e instanceof JSpaceOAIException) {
                $error = $response->createElement("error", $e->getMessage());
                $error->setAttribute('code', $e->getCode());

                $oaiPmh->appendChild($error);
            } else {
                JLog::add($e->getMessage()."\n".$e->getTraceAsString(), JLog::ERROR, 'jspace');
                throw new Exception('Internal Server Error', 500);
            }

        }

        return $response;
    }

    /**
     * Provides identity information about the JSpace archive, including
     * information about OAI access.
     *
     * For more information see <a href="http://www.openarchives.org/OAI/openarchivesprotocol.html#Identify">Identify</a>.
     *
     * @return  DomElement  Information about the JSpace archive.
     */
    protected function identify()
    {
        $config = JSpaceFactory::getConfig();

        $earliestDatestamp = new JDate( $config->get('oai_earliest_datestamp', ''));

        $admins = $config->get('oai_administrators', "");
        $admins = explode(";", $admins);
        $admins = array_map('trim', $admins);

        $xml = new DomDocument();
        $xml->appendChild($xml->createElement(ucfirst(__FUNCTION__)));
        $xml->documentElement->appendChild($xml->createElement('repositoryName', $config->get('oai_repository_name', '')));
        $xml->documentElement->appendChild($xml->createElement('baseURL', JUri::current()));
        $xml->documentElement->appendChild($xml->createElement('protocolVersion', '2.0'));

        foreach( $admins as $email )
        {
            $xml->documentElement->appendChild($xml->createElement('adminEmail', $email));
        }

        $xml->documentElement->appendChild($xml->createElement('earliestDatestamp', $earliestDatestamp->format(self::GRANULARITY)));
        $xml->documentElement->appendChild($xml->createElement('deletedRecord', 'transient'));
        $xml->documentElement->appendChild($xml->createElement('granularity', "YYYY-MM-DDThh:mm:ssZ"));
        $description = $xml->documentElement->appendChild($xml->createElement('description'));

        $oaiIdentifier = $xml->createElement('oai-identifier');
        $description->appendChild($oaiIdentifier);

        $oaiIdentifier->setAttribute('xmlns', 'http://www.openarchives.org/OAI/2.0/oai-identifier');
        $oaiIdentifier->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $oaiIdentifier->setAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/oai-identifier
      http://www.openarchives.org/OAI/2.0/oai-identifier.xsd');
        $oaiIdentifier->appendChild($xml->createElement('scheme', 'oai'));
        $oaiIdentifier->appendChild($xml->createElement('repositoryIdentifier', JUri::getInstance()->getHost()));
        $oaiIdentifier->appendChild($xml->createElement('delimiter', ":"));
        $oaiIdentifier->appendChild($xml->createElement('sampleIdentifier', "oai:".JUri::getInstance()->getHost().":1234"));

        return $xml->documentElement;
    }

    /**
     * Lists the available metadata formats.
     *
     * The list of metadata formats available is based on the JOAI plugins
     * installed and configured for providing OAI information.
     *
     * For more information see <a href="http://www.openarchives.org/OAI/openarchivesprotocol.html#ListMetadataFormats">ListMetadataFormats</a>.
     *
     * @return     DomElement          A list of available metadata formats.
     *
     * @exception  JSpaceOAIException  Thrown if no metadata formats exist or
     * if the optional identifier does not exist or is invalid.
     */
    protected function listMetadataFormats()
    {
        $xml = new DomDocument();
        $xml->appendChild($xml->createElement(ucfirst(__FUNCTION__)));

        $formats = $this->getSupportedMetadataFormats();

        if (count($formats) === 0) {
            throw new JSpaceOAIException(JText::_('LIB_JSPACE_OAI_EXCEPTION_NOMETADATAFORMATS_LABEL'), 'noMetadataFormats');
        }

        // if there is an identifier, validate it.
        if (isset($this->identifier)) {
            if (!($identifier = $this->lookupIdentifier())) {
                throw new JSpaceOAIException(JText::_('LIB_JSPACE_OAI_EXCEPTION_IDDOESNOTEXIST_LABEL'), 'idDoesNotExist');
            }
        }

        foreach($formats as $format) {
            $metadataFormat = $xml->documentElement->appendChild($xml->createElement('metadataFormat'));

            foreach ($format as $key=>$value) {
                $metadataFormat->appendChild($xml->createELement($key, $value));
            }
        }

        return $xml->documentElement;
    }

    /**
     * Lists the available sets.
     *
     * Sets, in JSpace, are synonymous with categories.
     *
     * For more information see <a href="http://www.openarchives.org/OAI/openarchivesprotocol.html#ListSets">ListSets</a>.
     *
     * @return     DomElement          A list of available sets.
     */
    protected function listSets()
    {
        if ($this->getCategoryCount() == 0) {
            // not really an error but OAI specifies that it is.
            throw new JSpaceOAIException(JText::_("LIB_JSPACE_OAI_EXCEPTION_NORECORDSMATCH_LABEL"), 'noRecordsMatch');
        }

        $xml = new DomDocument();
        $xml->appendChild($xml->createElement(ucfirst(__FUNCTION__)));

        $categories = $this->getCategories();

        foreach ($categories as $category) {
            $set = $xml->createElement('set');
            $xml->documentElement->appendChild($set);
            $set->appendChild($xml->createElement('setSpec', str_replace('/', ':', $category->get('path'))));
            $set->appendChild($xml->createElement('setName', $category->get('title')));

            $description = $xml->createElement('description');
            $set->appendChild($description);

            $dc = $xml->createElement('oai_dc:dc');
            $description->appendChild($dc);
            $dc->setAttribute('xmlns:oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
            $dc->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
            $dc->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
            $dc->setAttribute('xsi:schemaLocation', 'http://www.openarchives.org/OAI/2.0/oai_dc/
          http://www.openarchives.org/OAI/2.0/oai_dc.xsd');

            $dc->appendChild($xml->createElement('dc:title', $category->get('title')));

            if ($category->get('description')) {
                $dc->appendChild($xml->createElement('dc:description', $category->get('description')));
            }

            $dc->appendChild($xml->createElement('dc:created', $category->get('created_time')));

            if ($category->get('modified_time') != JFactory::getDbo()->getNullDate()) {
                $dc->appendChild($xml->createElement('dc:modified', $category->get('modified_time')));
            }

            if (($language = $category->get('language')) == '*') {
                $language = JFactory::getLanguage()->getDefault();
            }

            $dc->appendChild($xml->createElement('dc:language', $language));
        }

        $resumptionToken = $xml->importNode(
            $this->createResumptionToken(
                array('completeListSize'=>$this->getCategoryCount())), true);

        $xml->documentElement->appendChild($resumptionToken);

        return $xml->documentElement;
    }

    /**
     * Lists an archive's records, limiting what is returned to header
     * information only.
     *
     * For more information see <a href="http://www.openarchives.org/OAI/openarchivesprotocol.html#ListIdentifiers">ListIdentifiers</a>.
     *
     * @return     DomElement          A list of available sets.
     */
    protected function listIdentifiers()
    {
        if ($this->getRecordCount() == 0) {
            // not really an error but OAI specifies that it is.
            throw new JSpaceOAIException(JText::_("LIB_JSPACE_OAI_EXCEPTION_NORECORDSMATCH_LABEL"), 'noRecordsMatch');
        }

        $xml = new DomDocument();
        $xml->appendChild($xml->createElement(ucfirst(__FUNCTION__)));

        $records = $this->getRecords();

        foreach ($records as $record) {
            $header = $xml->importNode($this->createHeader($record), true);
            $xml->documentElement->appendChild($header);
        }

        $resumptionToken = $xml->importNode(
            $this->createResumptionToken(
                array('completeListSize'=>$this->getRecordCount())), true);

        $xml->documentElement->appendChild($resumptionToken);

        return $xml->documentElement;
    }

    /**
     * Lists an archive's records, including header and metadata.
     *
     * For more information see <a href="http://www.openarchives.org/OAI/openarchivesprotocol.html#ListRecords">ListRecords</a>.
     *
     * @return     DomElement          A list of available sets.
     */
    protected function listRecords()
    {
        if ($this->getRecordCount() == 0) {
            // not really an error but OAI specifies that it is.
            throw new JSpaceOAIException(JText::_("LIB_JSPACE_OAI_EXCEPTION_NORECORDSMATCH_LABEL"), 'noRecordsMatch');
        }

        $xml = new DomDocument();
        $xml->appendChild($xml->createElement(ucfirst(__FUNCTION__)));

        $records = $this->getRecords();

        foreach ($records as $record) {
            // no relation for records with a root parent.
            if ($record->get('parentAlias') != 'root') {
                $parentUrl = JUri::getInstance()->toString(array('scheme', 'host', 'port')).
                    JRoute::_('index.php?option=com_jspace&view=record&id='.$record->get('parentId'));

                $record->set('relation', htmlentities($parentUrl));
            }

            $xrecord = $xml->importNode($this->createRecord($record), true);
            $xml->documentElement->appendChild($xrecord);
        }

        $resumptionToken = $xml->importNode(
            $this->createResumptionToken(
                array('completeListSize'=>$this->getRecordCount())), true);

        $xml->documentElement->appendChild($resumptionToken);

        return $xml->documentElement;
    }

    /**
     * Get a single record.
     *
     * @link http://www.openarchives.org/OAI/openarchivesprotocol.html#GetRecord
     *
     * @return     DomElement          A single record.
     */
    private function getRecord()
    {
        $xml = new DomDocument();
        $xml->appendChild($xml->createElement(ucfirst(__FUNCTION__)));

        if ($record = $this->lookupRecord()) {
            // no relation for records with a root parent.
            if ($record->getParent()) {
                $parentUrl = JUri::getInstance()->toString(array('scheme', 'host', 'port')).
                    JRoute::_(JSpaceHelperRoute::getRecordRoute($record->getParent()->get('id')));

                $record->set('relation', htmlentities($parentUrl));
            }

            $xrecord = $xml->importNode($this->createRecord($record), true);
            $xml->documentElement->appendChild($xrecord);
        } else {
            throw new JSpaceOAIException(JText::_('LIB_JSPACE_OAI_EXCEPTION_IDDOESNOTEXIST_LABEL'), 'idDoesNotExist');
        }

        return $xml->documentElement;
    }

    /**
     * Gets a count of categories based on the query returned by
     * {@link getCategoryQuery()}.
     *
     * @return  int  A count of categories.
     */
    private function getCategoryCount()
    {
        $db = JFactory::getDbo();

        $query = $this->getCategoryQuery();
        $query->select('COUNT('.$db->q('id').')');

        return (int)$db->setQuery($query)->loadResult();
    }

    /**
     * Gets a list of categories based on the query returned by
     * {@link getCategoryQuery()}.
     *
     * @return  JObject[]  A list of categories.
     */
    private function getCategories()
    {
        $db = JFactory::getDbo();

        $select = array(
            $db->qn('c.id'),
            $db->qn('c.title'),
            $db->qn('c.path'),
            $db->qn('c.description'),
            $db->qn('c.created_time'),
            $db->qn('c.modified_time'),
            $db->qn('c.language'));

        $query = $this->getCategoryQuery();
        $query->select($select);

        return $db
            ->setQuery($query, (int)$this->get('cursor', 0), self::LIMIT)
            ->loadObjectList('id', 'JObject');
    }

    /**
     * Gets a database query for retrieving available categories.
     *
     * @return  JDatabaseQuery  A database query for retrieving available categories.
     */
    private function getCategoryQuery()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);

        $query
            ->select(array())
            ->from($db->qn('#__categories', 'c'))
            ->where($db->qn('c.extension').'='.$db->q('com_jspace'))
            ->where($db->qn('c.access').'='.JSpaceFactory::getConfig()->get('oai_accesslevel', 1))
            ->where($db->qn('c.published').' IN (1, 2)')
            ->order($db->qn('c.lft').' ASC');

        // Also ignore parent categories.
        $ignoreCategories = JSpaceFactory::getConfig()->get('oai_ignorecategories', array());

        if ($ignoreCategories) {
            $subQuery = "(select COUNT(p.id) from bk309_categories as p where p.lft < c.lft and p.rgt > c.rgt and p.id IN (".implode(',', $ignoreCategories).") and extension = 'com_jspace') = 0";
            $query->where($subQuery);
            $query->where($db->qn('c.id').' NOT IN ('.implode(',', $ignoreCategories).')');
        }

        return $query;
    }

    /**
     * Gets a count of records based on the query returned by
     * {@link getRecordQuery()}.
     *
     * @return  int  A count of records.
     */
    private function getRecordCount()
    {
        $db = JFactory::getDbo();

        $query = $this->getRecordQuery();
        $query->select('COUNT('.$db->q('id').')');

        return (int)$db->setQuery($query)->loadResult();
    }

    /**
     * Gets a list of records based on the query returned by
     * {@link getRecordQuery()}.
     *
     * @return  JObject[]  A list of records.
     */
    private function getRecords()
    {
        $db = JFactory::getDbo();

        $select = array(
            $db->qn('r.id'),
            $db->qn('r.title'),
            $db->qn('r.created'),
            $db->qn('r.modified'),
            $db->qn('r.language'),
            $db->qn('r.metadata'),
            $db->qn('r.parent_id', 'parentId'),
            $db->qn('p.alias', 'parentAlias'),
            $db->qn('c.title', 'category'));

        $query = $this->getRecordQuery();
        $query->select($select);

        return $db
            ->setQuery($query, (int)$this->get('cursor', 0), self::LIMIT)
            ->loadObjectList('id', 'JObject');
    }

    /**
     * Gets a database query for retrieving available records.
     *
     * @return  JDatabaseQuery  A database query for retrieving available records.
     */
    private function getRecordQuery()
    {
        $db = JFactory::getDbo();

        $query = $db->getQuery(true);

        $where = array();

        $query
            ->select(array())
            ->from($db->qn('#__jspace_records', 'r'))
            ->join(
                'inner',
                $db->qn('#__categories', 'c').
                ' ON ('.$db->qn('r.catid').'='.$db->qn('c.id').')')
            ->join(
                'inner',
                $db->qn('#__jspace_records', 'p').
                ' ON ('.$db->qn('r.parent_id').'='.$db->qn('p.id').')')
            ->order($db->qn('r.parent_id').' ASC');

        $where[] = $db->qn('r.published').' IN (1, 2)';
        $where[] = $db->qn('r.access').'='.JSpaceFactory::getConfig()->get('oai_accesslevel', 1);
        $where[] = $db->qn('c.access').'='.JSpaceFactory::getConfig()->get('oai_accesslevel', 1);

        $nullDate = $db->q($db->getNullDate());

        $datestamp = JSpaceFactory::getConfig()->get('oai_earliest_datestamp', '1970-01-01');

        $startDate = $db->q(JFactory::getDate($this->get('from', $datestamp))->toSql());

        $where[] = '(('.$db->qn('r.created').'>='.$startDate.' AND '.
            $db->qn('r.modified').'='.$nullDate.') OR '.
            $db->qn('r.modified').'>='.$startDate.')';

        if ($endDate = $this->get('until')) {
            $endDate = $db->q(JFactory::getDate($endDate)->toSql());

            $where[] = '(('.$db->qn('r.created').'<='.$endDate.' AND '.
                $db->qn('r.modified').'='.$nullDate.') OR '.
                $db->qn('r.modified').'<='.$endDate.')';
        }

        if ($set = $this->get('set')) {
            $where[] = $db->qn('c.path').'='.$db->q($set);
        }

        // Also ignore parent categories.
        $ignoreCategories = JSpaceFactory::getConfig()->get('oai_ignorecategories', array());

        if ($ignoreCategories) {
            $subQuery = "(select COUNT(p.id) from bk309_categories as p where p.lft < c.lft and p.rgt > c.rgt and p.id IN (".implode(',', $ignoreCategories).") and extension = 'com_jspace') = 0";
            $query->where($subQuery);
            $query->where($db->qn('c.id').' NOT IN ('.implode(',', $ignoreCategories).')');
        }

        $query->where($where);

        return $query;
    }

    /**
     * Validates the identifier passed as part of the request and returns the
     * record id if it is found, or null if the record does not exist or the
     * identifier has an invalid format.
     *
     * @return  int  The record id if the identifier is found, null otherwise.
     */
    private function lookupIdentifier()
    {
        if ($record = $this->lookupRecord()) {
            return $record->id;
        }

        return null;
    }

    /**
     * Validates the identifier passed as part of the request and returns the
     * record if it is found, or null if the record does not exist or the
     * identifier has an invalid format.
     *
     * @return  JSpaceRecord  The record if the identifier is found, null otherwise.
     */
    private function lookupRecord()
    {
        $pattern = "/oai:".JUri::getInstance()->getHost().":[0-9]+/";

        if (preg_match($pattern, $this->get('identifier')) === 0)
        {
            return null;
        }

        $parts = explode(':', $this->get('identifier'));

        $record = JSpaceRecord::getInstance(JArrayHelper::getValue($parts, count($parts)-1));

        if ($record->get('id')) {
            return $record;
        } else {
            return null;
        }
    }

    /**
     * Gets the request url.
     *
     * @return  JUri  The request url.
     */
    private function getRequestUri()
    {
        $uri = JUri::getInstance();

        foreach (array_keys($this->getProperties()) as $query) {
            $uri->delVar($query);
        }

        return $uri;
    }

    /**
     * Validates the queries passed as part of the OAI request and based on
     * the settings provided as part of {@link static::$verbs}.
     *
     * @return  bool  True if the request is valid, false otherwise.
     */
    private function hasValidArguments()
    {
        $values = $this->getProperties();
        unset($values['verb']);
        $keys = array_keys($values);

        $valid = true;

        $verb = self::$verbs[$this->get('verb')];

        $mandatory = JArrayHelper::getValue($verb, 'mandatory', array());
        $optional = JArrayHelper::getValue($verb, 'optional', array());
        $canResume = JArrayHelper::getValue($verb, 'canResume', false);

        if ($this->get('resumptionToken')) {
            // if resume allowed, make sure only 2 arguments exit.
            if ($canResume) {
                if (count(array_diff(array('resumptionToken'), $keys)) > 0) {
                    $valid = false;
                }
            // if resume not allowed
            } else {
                $valid = false;
            }
        // if not resuming, check allowed arguments.
        } else {
            $mkeys = array_diff($mandatory, $keys);
            $okeys = array_diff(array_diff($keys, $mandatory), $optional);

            if (count($mkeys) != 0 || count($okeys) != 0) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Creates the record XML based on the provided record information.
     *
     * @param   JObject  $record  The record to convert to XML.
     *
     * @return  DomNode  An XML representation of the record.
     */
    private function createRecord($record)
    {
        $xml = new DomDocument();

        if ($record->get('language') == "*") {
            $record->set('language', JFactory::getLanguage()->getDefault());
        }

        $xrecord = $xml->createElement('record');
        $xml->appendChild($xrecord);

        $header = $xml->importNode($this->createHeader($record), true);
        $xrecord->appendChild($header);

        $metadata = $this->createMetadata($record);
        $xmetadata = $xml->importNode($this->createMetadata($record), true);
        $xrecord->appendChild($xmetadata);

        return $xrecord;
    }

    /**
     * Creates the header XML based on the provided record information.
     *
     * @param   JObject  $record  The record to convert to XML.
     *
     * @return  DomNode  An XML representation of the header.
     */
    private function createHeader($record)
    {
        $xml = new DomDocument();
        $header = $xml->createElement('header');
        $xml->appendChild($header);

        $header->appendChild(
            $xml->createElement(
                'identifier',
                "oai:".JUri::getInstance()->getHost().":".$record->get('id')));

        $datestamp = JFactory::getDate($record->get('modified', $record->get('created')));

        $header->appendChild(
            $xml->createElement('datestamp', $datestamp->format(self::GRANULARITY)));

        $header->appendChild(
            $xml->createElement('setSpec', $record->get('category')));

        return $header;
    }

    /**
     * Creates the metadata XML based on the provided record information.
     *
     * Metadata is crosswalked via the JOAI plugins.
     *
     * @param   JObject            $record  The record to convert to XML.
     *
     * @return  DomNode            An XML representation of the metadata.
     *
     * @throw  JSpaceOAIException  Thrown if no JOAI plugin is configured to
     * handle the crosswalk. This error should never be thrown.
     */
    private function createMetadata($record)
    {
        $context = 'joai.'.$this->get('metadataPrefix');

        JPluginHelper::importPlugin('joai');
        $dispatcher = JEventDispatcher::getInstance();
        $result = $dispatcher->trigger('onJSpaceCrosswalkMetadata', array($context, $record));

        if ($metadata = JArrayHelper::getValue($result, 0)) {
            $xml = new DomDocument();
            $xmetadata = $xml->createElement('metadata');
            $xml->appendChild($xmetadata);
            $xmetadata->appendChild($xml->importNode($metadata, true));

            return $xml->documentElement;
        } else {
            // should never get to this error.
            throw new JSpaceOAIException(JText::_("LIB_JSPACE_OAI_EXCEPTION_CANNOTDISSEMINATEFORMAT_LABEL"), 'cannotDisseminateFormat');
        }
    }

    /**
     * Creates the resumption token XML based on the provided record
     * information.
     *
     * @param   array    $properties  The properties to use for creating the
     * resumption token.
     *
     * @return  DomNode  An XML representation of the resumption token.
     */
    private function createResumptionToken($properties)
    {

        $completeListSize = JArrayHelper::getValue($properties, 'completeListSize', 0, 'int');

        if (!$completeListSize) {
            throw new Exception('No completeListSize specified.');
        }

        $xml = new DomDocument();

        $newCursor = (int)$this->get('cursor', 0)+self::LIMIT;

        $token = null;

        if ($newCursor < $completeListSize) {
            $properties['cursor'] = $newCursor;
            $token = $this->getResumptionToken($properties);
        }

        $resumptionToken = $xml->createElement('resumptionToken', $token);
        $xml->appendChild($resumptionToken);

        $resumptionToken->setAttribute('cursor', (int)$this->get('cursor', 0));
        $resumptionToken->setAttribute('completeListSize', $completeListSize);

        $expires = JSpaceFactory::getConfig()
            ->get('oai_resumptiontokenexpires', '+1 week');

        if ($expires) {
            $expires = JFactory::getDate($this->get('created').$expires);

            $resumptionToken->setAttribute('expirationDate', $expires->format(self::GRANULARITY));
        }

        return $xml->documentElement;
    }

    /**
     * Creates a resumption token for accessing multiple pages of OAI results.
     *
     * @param   array   $properties  An array of additional properties to
     * initiate the resumption token with.
     *
     * @return  string  A base64 encoded token.
     */
    private function getResumptionToken($properties)
    {
        $token = new JObject($properties);

        if (!$token->cursor = (int)$token->get('cursor')) {
            throw new Exception('No cursor specified.');
        }

        if (!($token->created = $this->get('created'))) {
            $token->created = JFactory::getDate('now')->toISO8601();
        }

        if ($this->get('from')) {
            $token->from = $this->get('from');
        }

        if ($this->get('until')) {
            $token->until = $this->get('until');
        }

        if ($this->get('metadataPrefix')) {
            $token->metadataPrefix = $this->get('metadataPrefix');
        }

        if ($this->get('set')) {
            $token->metadataPrefix = $this->get('set');
        }

        return base64_encode(json_encode($token->getProperties()));
    }

    /**
     * Determines whether the OAI verb supports the metadataPrefix query and,
     * if it does, determines whether JOAI is configured to handle the
     * metadata format.
     *
     * @return  bool  True if the verb doesn't require a metadata prefix or if
     * the metadata prefix exists for the verb that does, false otherwise.
     */
    private function canDisseminateFormat()
    {
        // if no prefix is defined then the verb doesn't require a metadataPrefix.
        if (!($prefix = $this->get('metadataPrefix'))) {
            return true;
        }

        $formats = $this->getSupportedMetadataFormats();

        $found = false;

        while (($format = current($formats)) && !$found)
        {
            if (JArrayHelper::getValue($format, 'metadataPrefix') == $prefix)
            {
                $found = true;
            }

            next($formats);
        }

        return $found;
    }

    /**
     * Gets a list of supported metadata formats based on the JOAI plugins
     * configured.
     *
     * @return  array  A list of supported metadata formatas based on the JOAI
     * plugins configured.
     */
    private function getSupportedMetadataFormats()
    {
        JPluginHelper::importPlugin('joai');
        $dispatcher = JEventDispatcher::getInstance();
        return $dispatcher->trigger('onJSpaceProviderMetadataFormat');
    }
}