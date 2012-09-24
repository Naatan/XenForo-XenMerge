<?php

class XenMerge_ControllerAdmin_Template extends XFCP_XenMerge_ControllerAdmin_Template
{
	
	public static $_editTemplateId = false;
	
	public function actionEdit()
	{
		self::$_editTemplateId = $this->_input->filterSingle('template_id', XenForo_Input::UINT);
		
		return parent::actionEdit();
	}

	public function actionEditMerge()
	{
		define('TS_DISABLE', true);
		
		$input = $this->_input->filter(array(
			'title' 			=> XenForo_Input::STRING,
			'master_style_id'	=> XenForo_Input::UINT,
			'style_id'			=> XenForo_Input::UINT,
			'template_id' 		=> XenForo_Input::UINT
		));
		
		$templateModel = $this->_getTemplateModel();
		
		if ($input['template_id'])
		{
			$template = $this->_getTemplateOrError($input['template_id']);
		}
		else if ($input['title'] AND isset($input['style_id']))
		{
			$template = $templateModel->getTemplateInStyleByTitle($input['title'], $input['style_id']);
			
			if ( ! $template)
			{
				$template = array('title' => $input['title'], 'style_id' => $input['style_id']);
			}
		}
		else
		{
			return $this->responseError(new XenForo_Phrase('requested_template_not_found'));
		}
		
		if ($template['style_id'] == 0)
		{
			return $this->responseError(new XenForo_Phrase('cannot_compare_master_template'));
		}
		
		$masterTemplate = $this->_getTemplateModel()->getTemplateInStyleByTitle($template['title'], $input['master_style_id'] ? $input['master_style_id'] : 0);
		
		if ( ! $masterTemplate)
		{
			$masterTemplate = array('title' => $input['title'], 'style_id' => $input['master_style_id'] ? $input['master_style_id'] : 0);
		}
		else if ( ! isset($template['template_id']))
		{
			$template['template'] = $masterTemplate['template'];
		}
		
		$styleModel = $this->_getStyleModel();
		$styles 	= $styleModel->getAllStylesAsFlattenedTree(1);
		
		return $this->responseView('XenForo_ViewAdmin_Base', 'xenmerge_template_edit', array(
			'template'			=> $template,
			'masterTemplate' 	=> $masterTemplate,
			'style'				=> $styles[$template['style_id']],
			'styles' 			=> $styles,
			'masterStyle' 		=> $styleModel->getStyleById(0, true),
			'redirect'			=> $this->_input->filterSingle('redirect', XenForo_Input::STRING)
		));
	}
	
	public function actionSaveMerge()
	{
		$this->_assertPostOnly();

		$templateModel = $this->_getTemplateModel();

		$data = $this->_input->filter(array(
			'title'		=> XenForo_Input::STRING,
			'template' 	=> array(XenForo_Input::STRING, 'noTrim' => true),
			'style_id' 	=> XenForo_Input::UINT,
		));

		// only allow templates to be edited in non-master styles, unless in debug mode
		if (!$templateModel->canModifyTemplateInStyle($data['style_id']))
		{
			return $this->responseError(new XenForo_Phrase('this_template_can_not_be_modified'));
		}

		$propertyModel = $this->_getStylePropertyModel();

		$properties = $propertyModel->keyPropertiesByName(
			$propertyModel->getEffectiveStylePropertiesInStyle($data['style_id'])
		);
		$propertyChanges = $propertyModel->translateEditorPropertiesToArray(
			$data['template'], $data['template'], $properties
		);

		$writer = XenForo_DataWriter::create('XenForo_DataWriter_Template');
		if ($templateId = $this->_input->filterSingle('template_id', XenForo_Input::UINT))
		{
			$writer->setExistingData($templateId);
		}
		else
		{
			$masterStyleId 	= $this->_input->filterSingle('master_style_id', XenForo_Input::UINT);
			$masterTemplate = $this->_getTemplateModel()->getTemplateInStyleByTitle($data['title'], $masterStyleId);
			
			if ($masterTemplate)
			{
				$data['addon_id'] = $masterTemplate['addon_id'];
			}
		}

		$writer->bulkSet($data);

		if ($writer->isChanged('template') || $writer->get('style_id') > 0)
		{
			$writer->updateVersionId();
		}
		
		$writer->save();

		$propertyModel->saveStylePropertiesInStyleFromTemplate($data['style_id'], $propertyChanges, $properties);

		if ($this->_input->filterSingle('reload', XenForo_Input::STRING))
		{
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('templates/edit', $writer->getMergedData(), array('style_id' => $writer->get('style_id')))
			);
		}
		else
		{
			$redirect = $this->_input->filterSingle('redirect', XenForo_Input::STRING);
			
			if ($redirect)
			{
				$redirect	= XenForo_Link::buildAdminLink($redirect);
			}
			else
			{
				$style 		= $this->_getStyleModel()->getStyleByid($writer->get('style_id'), true);
				$redirect	= XenForo_Link::buildAdminLink('styles/templates', $style) . $this->getLastHash($writer->get('title'));
			}
			
			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				$redirect
			);
		}
	}

}