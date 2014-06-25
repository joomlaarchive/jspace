<?php
defined('_JEXEC') or die;

require_once(JPATH_PLATFORM.'/amazon/aws-autoloader.php');

use Aws\S3\S3Client;
use Aws\Common\Credentials\Credentials;

defined('_JEXEC') or die;

jimport('joomla.filesystem.folder');

jimport('jspace.factory');
jimport('jspace.filesystem.file');
jimport('jspace.html.assets');

/**
 * Manages assets on Amazon Web Services S3.
 */
class PlgJSpaceS3 extends JPlugin
{
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);
		$this->loadLanguage();
		
		JLog::addLogger(array());
		
		$params = JComponentHelper::getParams('com_jspace', true);
		
		$this->params->loadArray(array('component'=>$params->toArray()));
	}
	
	/**
	 * validates the S3 settings.
	 *
	 * @param  JForm  $form
	 * @param  array  $data
	 * @param  array  $group
	 *
	 * @throw  UnexpectedValueException  If either the bucket, access key id or secret access key are blank.
	 */
	public function onJSpaceRecordAfterValidate($form, $data, $group = null)
	{
		if (!$this->params->get('bucket'))
		{
			throw new UnexpectedValueException('AWS bucket required.');
		}
	
		if (!$this->params->get('access_key_id'))
		{
			throw new UnexpectedValueException('AWS access key id required.');
		}

		if (!$this->params->get('secret_access_key'))
		{
			throw new UnexpectedValueException('AWS secret access key required.');
		}
	}
	
	/**
	 * Saves an asset to an Amazon S3 bucket.
	 *
	 * @param  JSpaceAsset               $asset  The asset to save. Contains POST-style array.
	 */
	public function onJSpaceAssetAfterSave($asset)
	{
		$credentials = new Credentials($this->params->get('access_key_id'), $this->params->get('secret_access_key')); 
		
		$storage = JSpaceArchiveAssetHelper::buildStoragePath($asset->record_id);
		
		try
		{
			$s3 = S3Client::factory(array('credentials'=>$credentials));
			
			$uploader = \Aws\S3\Model\MultipartUpload\UploadBuilder::newInstance()
				->setClient($s3)
				->setSource($asset->tmp_name)
				->setBucket($this->params->get('bucket'))
				->setKey($storage.sha1_file($asset->tmp_name))
				->setOption('Metadata', $asset->getMetadata()->toArray())
				->setOption('CacheControl', 'max-age=3600')
				->setConcurrency(3)
				->build();
			
			$uploader->upload();
		} 
		catch(Exception $e)
		{
			JLog::add(__METHOD__.' '.$e->getMessage(), JLog::ERROR, 'jspace');
			throw $e;
		}
	}
	
	/**
	 * Deletes an asset to an Amazon S3 bucket.
	 *
	 * If the asset cannot be found, this method will fail silently.
	 *
	 * @param  JSpaceAsset  $asset  The asset to delete.
	 */
	public function onJSpaceAssetBeforeDelete($asset)
	{
		$credentials = new Credentials($this->params->get('access_key_id'), $this->params->get('secret_access_key'));
		
		$storage = JSpaceArchiveAssetHelper::buildStoragePath($asset->record_id);
		
		$path = $storage.$asset->hash;
		
		$s3 = S3Client::factory(array('credentials'=>$credentials));
		
		if ($s3->doesObjectExist($this->params->get('bucket'), $path))
		{		
			$result = $s3->deleteObject(array('Bucket'=>$this->params->get('bucket'), 'Key'=>$path));	
		}
		else
		{
			JLog::add(__METHOD__.' '.JText::sprintf('PLG_JSPACE_S3_WARNING_OBJECTDOESNOTEXIST', json_encode($asset)), JLog::WARNING, 'jspace');
		}
	}
}