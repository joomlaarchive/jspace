<?php
defined('JPATH_BASE') or die;

/**
 * A file uploader for a record.
 * 
 * Provides the ability to upload one or more assets as part of a record.
 */
class JSpaceFormFieldAsset extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var		string
	 */
	protected $type = 'JSpace.Asset';

	protected function getInput()
	{
		$html = JLayoutHelper::render("jspace.form.fields.asset", $this);
		return $html;
	}
	
	public function getAssets()
	{
		$record = JSpaceRecord::getInstance($this->form->getData()->get('id'));

		return $record->getAssets(array('derivative'=>$this->fieldname));
	}
	
	public function getDownloadLinks($asset)
	{
        $dispatcher = JEventDispatcher::getInstance();
        
        JPluginHelper::importPlugin("content");
        
        return $dispatcher->trigger('onJSpaceAssetPrepareDownload', array($asset));
    }
}