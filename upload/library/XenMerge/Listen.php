<?php

class XenMerge_Listen
{

	public static function template_create($templateName, array &$params, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'template_outdated')
		{
			$templateName = 'xenmerge_template_outdated';
		}
		
	}

	public static function load_class_controller($class, array &$extend)
	{
		/* Extend XenForo_ControllerAdmin_Template */
		if ($class == 'XenForo_ControllerAdmin_Template' AND ! in_array('XenMerge_ControllerAdmin_Template', $extend))
		{
			$extend[] = 'XenMerge_ControllerAdmin_Template';
		}
		/* Extend End */
	}

	public static function load_class_datawriter($class, array &$extend)
	{
		/* Extend XenForo_DataWriter_Template */
		if ($class == 'XenForo_DataWriter_Template' AND ! in_array('XenMerge_DataWriter_Template', $extend))
		{
			$extend[] = 'XenMerge_DataWriter_Template';
		}
		/* Extend End */
	}

	public static function template_post_render($templateName, &$content, array &$containerData, XenForo_Template_Abstract $template)
	{
		if ($templateName == 'template_edit' AND class_exists('XenMerge_ControllerAdmin_Template', false))
		{
			$templateId = XenMerge_ControllerAdmin_Template::$_editTemplateId;
			
			if ($templateId)
			{
				$tpl 		= XenForo_Model::create('XenForo_Model_Template')->getTemplateById($templateId);
				$style 		= $template->getParam('style');
				
				$link 		= XenForo_Link::buildAdminLink('templates/edit-merge', '', array('title' => $tpl['title'], 'style_id' => $style['style_id']));
				$phrase 	= new XenForo_Phrase('manage_changes');
				$button 	= '<a href="'.$link.'" class="button">'.$phrase->render().'</a>';
				$content 	= preg_replace('/(\"ctrlUnit submitUnit.*?\<dd\>.*?)(\<\/dd)/s', '$1'.$button.'$2', $content, 1);
			}
		}
	}


}