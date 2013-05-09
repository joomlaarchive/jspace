<?php 
/**
 * A model that displays information about a single item and its bitstreams.
 * 
 * @author		$LastChangedBy$
 * @package		JSpace
 * @copyright	Copyright (C) 2011 Wijiti Pty Ltd. All rights reserved.
 * @license     This file is part of the JSpace component for Joomla!.

   The JSpace component for Joomla! is free software: you can redistribute it 
   and/or modify it under the terms of the GNU General Public License as 
   published by the Free Software Foundation, either version 3 of the License, 
   or (at your option) any later version.

   The JSpace component for Joomla! is distributed in the hope that it will be 
   useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with the JSpace component for Joomla!.  If not, see 
   <http://www.gnu.org/licenses/>.

 * Contributors
 * Please feel free to add your name and email (optional) here if you have 
 * contributed any source code changes.
 * Name							Email
 * Hayden Young					<haydenyoung@wijiti.com> 
 * 
 */

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.model');
jimport("joomla.filesystem.file");
jimport('joomla.error.log');
jimport('joomla.utilities');

require_once(JPATH_COMPONENT_ADMINISTRATOR.DS."helpers".DS."restrequest.php");

class JSpaceModelItem extends JModel
{
	var $configPath = null;
	
	var $configuration = null;

	var $id = 0;
	
	var $data = null;
	
	public function __construct()
	{
		$this->configPath = JPATH_ROOT.DS."administrator".DS."components".DS."com_jspace".DS."configuration.php";
		
		require_once($this->configPath);
		
		parent::__construct();
	}

	/**
	 * Gets the configuration file path.
	 * 
	 * @return The configuration file path.
	 */
	public function getConfig()
	{
		if (!$this->configuration) {
			$this->configuration = new JSpaceConfig();	
		}
		
		return $this->configuration;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * Gets an item.
	 * 
	 * @return stdClass A item object.
	 */
	public function getData()
	{
		if (!$this->data) {
			$request = new JSpaceRestRequestHelper($this->getConfig()->rest_url.'/items/'. $this->getId() .'.json', 'GET');
			$request->execute();

			if (JArrayHelper::getValue($request->getResponseInfo(), "http_code") == 200) {
				$this->data = json_decode($request->getResponseBody());
			} else {
				$this->data = new stdClass();
				$log = JLog::getInstance();
				$log->addEntry(array("c-ip"=>JArrayHelper::getValue($request->getResponseInfo(), "http_code", 0), "comment"=>$request->getResponseBody()));
			}
		}
		
		return $this->data;
	}

	public function findPreview($original)
	{
		$item = $this->getData();
		
		$thumbnail = null;
		
		$i = 0;
		foreach ($item->bundles as $bundle) {
			if ($bundle->name == "BRANDED_PREVIEW" || $bundle->name == "TEXT") {

				foreach ($item->bitstreams as $bitstream) {
					$found = false;

					reset($bundle->bitstreams);

					while (!$found && $bundleBitstream = current($bundle->bitstreams)) {
						if ($bitstream->id == $bundleBitstream->id && 
							strpos($bitstream->name, $original->name) === 0) {
							$url = $this->getConfig()->rest_url . "/bitstream/" . $bitstream->id . "/receive"; 
							
							$thumbnail = $bitstream;
							$thumbnail->url = $url;
							$found = true;
						}

						next($bundle->bitstreams);
					}
				}
			}
		}
		
		return $thumbnail;	
	}
	
	public function findThumbnail($original)
	{
		$item = $this->getData();
		
		$thumbnail = null;
		
		$i = 0;
		foreach ($item->bundles as $bundle) {
			if ($bundle->name == "THUMBNAIL") {

				foreach ($item->bitstreams as $bitstream) {
					$found = false;

					reset($bundle->bitstreams);

					while (!$found && $bundleBitstream = current($bundle->bitstreams)) {
						if ($bitstream->id == $bundleBitstream->id && 
							strpos($bitstream->name, $original->name) === 0) {
							$url = $this->getConfig()->rest_url . "/bitstream/" . $bitstream->id . "/receive"; 
							
							$thumbnail = $bitstream;
							$thumbnail->url = $url;
							$found = true;
						}

						next($bundle->bitstreams);
					}
				}
			}
		}
		
		return $thumbnail;	
	}
	
	public function getOriginalBitstreams()
	{
		$item = $this->getData();
		
		$bitstreams = array();
		
		$i = 0;
		foreach ($item->bundles as $bundle) {
			if ($bundle->name == "ORIGINAL") {

				foreach ($item->bitstreams as $bitstream) {
					$found = false;

					reset($bundle->bitstreams);

					while (!$found && $bundleBitstream = current($bundle->bitstreams)) {
						if ($bitstream->id == $bundleBitstream->id) {
							$url = $this->getConfig()->rest_url . "/bitstream/" . $bitstream->id . "/receive"; 
							
							$bitstreams[] = $bitstream;
							$bitstreams[count($bitstreams)-1]->url = $url;
							$found = true;
						}

						next($bundle->bitstreams);
					}
				}
			}
		}
		
		return $bitstreams;		
	}
	
	public function getBitstreams()
	{
		$item = $this->getData();
		
		$bitstreams = array();
		
		$i = 0;
		foreach ($item->bundles as $bundle) {
			foreach ($item->bitstreams as $bitstream) {
				$url = $this->getConfig()->rest_url . "/bitstream/" . $bitstream->id . "/receive";
				$obj = strtolower($bundle->name);				
				$found = false;

				reset($bundle->bitstreams);

				while (!$found && $bundleBitstream = current($bundle->bitstreams)) {
					if ($bitstream->id == $bundleBitstream->id) {
						$bitstreams[$i]->$obj = $bitstream;
						$bitstreams[$i]->$obj->url = $url;
						$found = true;
					}

					next($bundle->bitstreams);
				}
			}
		
			$i++;
		}
		
		return $bitstreams;
	}
	
	public function getPreviewLink($original)
	{
		$url = null;
		$class = "jspace-preview modal";
		$rel = "";
		$title = JText::_("COM_JSPACE_BITSTREAM_PREVIEW");
		$w = 0;
		$h = 0;
		
		switch ($original->mimeType) {
			case "video/x-flv":
				if (extension_loaded("ffmpeg") && class_exists("ffmpeg_movie")) {
					$url = "index.php?option=com_jspace&view=bitstream&layout=preview&tmpl=component&id=".$original->id;
					$movie = new ffmpeg_movie($original->url);
					$w = $movie->getFrameWidth();
					$h = $movie->getFrameHeight();
				}
				
				break;
		
			case "image/png":
			case "image/gif":
			case "image/jpeg":
			case "image/jpg":
				if (extension_loaded("gd") && function_exists('gd_info')) {
					if ($preview = $this->findPreview($original)) {
						$url = "index.php?option=com_jspace&view=bitstream&layout=preview&tmpl=component&id=".$preview->id;
					    $ch = curl_init();
					    curl_setopt ($ch, CURLOPT_URL, $preview->url);
					    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
					    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
					    $contents = curl_exec($ch);
					    curl_close($ch);
					    $image = imagecreatefromstring($contents);
					    
						$w = imagesx($image);
						$h = imagesy($image); 
					}
				}

				break;
			
			case "application/pdf":
				if ($preview = $this->findPreview($original)) {
					$url = "index.php?option=com_jspace&view=bitstream&layout=preview&tmpl=component&id=".$preview->id;
					$w = 800;
					$h = 600;
				}
				
				break;
				
			default:
				
				break;
		}
		
		$rel = "{handler:'iframe',size:{x:".$w.",y:".$h."}}";
		if ($url) {
			return JHTML::link($url, "", array("class"=>$class, "rel"=>$rel, "title"=>$title));
		} else {
			return "";
		}
	}
	
	public function getThumbnail($original)
	{
		$thumbnail = "";
		
		switch ($original->mimeType) {
			case "video/x-flv":
				$thumbnail = $this->getFrameThumbnail($original);
				
				break;
		
			case "image/png":
			case "image/gif":
			case "image/jpeg":
			case "image/jpg":
				$image = $this->findThumbnail($original);
				
				if ($image) {
					$thumbnail = $image->url;
				}

				break;
			
			case "application/pdf":
				$thumbnail = JURI::base()."media/com_jspace/images/pdf.png";

				break;
				
			default:
				
				break;
		}
		
		return $thumbnail;
	}
	
	public function getFrameThumbnail($original)
	{
		if (extension_loaded("ffmpeg") && class_exists("ffmpeg_movie")) {
			$wFrame = 103;
			$hFrame = 68;
			
			$movie = new ffmpeg_movie($original->url, false);
	
			$frames = $movie->getFrameCount(); // to get number of frames incase you want to loop through
			
			if ($frames > 100) {
				$frame = $movie->getFrame(100);
			} else {
				$frame = $movie->getFrame(1);
			}
			
			$image = $frame->toGDImage();
			//imagejpeg($image, null);
			$w = imagesx($image);
			$h = imagesy($image);
	
			$resize = imagecreatetruecolor($wFrame, $hFrame);
			$tmp = imagecreatefromjpeg(JPATH_ROOT."/media/com_jspace/images/filmstrip.jpg");
			
			imagecopyresampled($resize, $image, 0, 0, 0, 0, $wFrame, $hFrame, $w, $h);
			imagecopymerge($tmp, $resize, 4, 13, 0, 0, $wFrame, $hFrame, 100);
			
			imagejpeg($tmp, "preview.".$original->id.".jpg");
			
			imagedestroy($image);
			imagedestroy($resize);

			return "preview.".$original->id.".jpg";
		} else {
			return null;
		}
	}
	
	public function formatFileSize($size)
	{
		if ($size > 1024) {
			return intval($size/1024) . JText::_("Kb");
		} else {
			return $size . JText::_("bytes");
		}
	}
}