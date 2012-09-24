<?php

class XenMerge_DataWriter_Template extends XFCP_XenMerge_DataWriter_Template
{

	public function updateVersionId($versionIdField = 'version_id', $versionStringField = 'version_string', $addOnIdField = 'addon_id')
	{
		$versionId = parent::updateVersionId($versionIdField, $versionStringField, $addOnIdField);
		
		if ($this->get('version_id') == 0)
		{
			$versionId = XenForo_Application::$versionId;
			$this->set('version_id', $versionId);
			$this->set('version_string', XenForo_Application::$version);
		}
		
		return $versionId;
	}

}