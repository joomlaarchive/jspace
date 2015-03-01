<?php
defined('_JEXEC') or die;

class JSpaceControllerRecord extends JControllerForm
{
	protected function allowAdd($data = array())
	{
		$user = JFactory::getUser();
		$categoryId = JArrayHelper::getValue($data, 'catid', $this->input->getInt('filter_category_id'), 'int');
		$allow = null;

		if ($categoryId)
		{
			// If the category has been passed in the data or URL check it.
			$allow = $user->authorise('core.create', 'com_jspace.category.' . $categoryId);
		}

		if ($allow === null)
		{
			// In the absense of better information, revert to the component permissions.
			return parent::allowAdd();
		}
		else
		{
			return $allow;
		}
	}

	/**
	 * Requests the deletion of a single asset.
	 */
	public function deleteAsset()
	{
		JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
		$id = JFactory::getApplication()->input->get('id', 0, 'int');

		$model = $this->getModel('record');

		if ($asset = $model->getAsset($id))
		{
			try
			{
				$model->deleteAsset($id);
				$message = JText::_('COM_JSPACE_RECORD_ASSET_DELETED');
				$type = '';
			}
			catch(Exception $e)
			{
				$message = JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
				$type = 'error';
			}

			$url = JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item.$this->getRedirectToItemAppend($asset->record_id), false);
		}
		else
		{
			JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list);
			$message = JText::_('COM_JSPACE_RECORD_ASSET_DOESNOTEXISTS');
			$type = 'warning';
		}

		$this->setRedirect($url, $message, $type);
	}

	public function useAssetMetadata()
	{
		JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
		$id = JFactory::getApplication()->input->get('id', 0, 'int');

		$model = $this->getModel('record');

		if ($asset = $model->getAsset($id))
		{
			try
			{
				$model->useAssetMetadata($id);
				$message = JText::_('COM_JSPACE_RECORD_ASSET_METADATA_USED');
				$type = '';
			}
			catch (Exception $e)
			{
				$message = JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
				$type = 'error';
			}

			$url = JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item.$this->getRedirectToItemAppend($asset->record_id), false);
		}
		else
		{
			JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list);
			$message = JText::_('COM_JSPACE_RECORD_ASSET_DOESNOTEXISTS');
			$type = 'warning';
		}

		$this->setRedirect($url, $message, $type);
	}

    /**
     * Requests the deletion of a single reference.
     */
    public function deleteReference()
    {
        JSession::checkToken('get') or die(JText::_('JINVALID_TOKEN'));
        $id = JFactory::getApplication()->input->get('id', 0, 'int');

        $model = $this->getModel('record');

        if ($asset = $model->getReference($id))
        {
            try
            {
                $model->deleteReference($id);
                $message = JText::_('COM_JSPACE_RECORD_REFERENCE_DELETED');
                $type = '';
            }
            catch(Exception $e)
            {
                $message = JText::_('JERROR_AN_ERROR_HAS_OCCURRED');
                $type = 'error';
            }

            $url = JRoute::_('index.php?option='.$this->option.'&view='.$this->view_item.$this->getRedirectToItemAppend($asset->record_id), false);
        }
        else
        {
            JRoute::_('index.php?option='.$this->option.'&view='.$this->view_list);
            $message = JText::_('COM_JSPACE_RECORD_REFERENCE_DOESNOTEXISTS');
            $type = 'warning';
        }

        $this->setRedirect($url, $message, $type);
    }
}