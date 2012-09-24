var XenMerge = {};

XenMerge.DiffMerge = function() { this.__construct(); };
XenMerge.DiffMerge.prototype = {
	
	updating: false,
	
	__construct: function()
	{
		
		$(window).resize($.proxy(this.setPageSize, this));
		this.setPageSize();
		
		$('#compare').mergely({
			autoresize: true,
			cmsettings: {
				readOnly: false,
				mode: $("input[name=title]").val().substr(-3) == 'css' ? 'css' : 'text/html',
				onUpdate: $.proxy(this.onUpdate, this)
			},
			lhs_cmsettings: {
				readOnly: true
			},
			lhs: function(setValue)
			{
				setValue($("textarea[name=masterTemplate]").val());
			},
			rhs: function(setValue)
			{
				setValue($("textarea[name=template]").val());
			},
			height: function(h)
			{
				return $(document).height() - $("#mergely-resizer").offset().top - $("#footer").height() - 130;
			},
			loaded: function()
			{
				//$("#compare-editor-lhs .merge-button").css("");
			}
		});
		
	},
	
	onUpdate: function(CM)
	{
		$(".merge-button").each(function()
		{
			$(this).appendTo($(this).parents(".CodeMirror-scroll")).css({left: 11, width: '14px', 'z-index': 20});
		});
		
		if ($(CM.getWrapperElement()).parents("#compare-editor-rhs").length != 0)
		{
			clearTimeout(this.updating);
			this.updating = setTimeout(function()
			{
				
				$("textarea[name=template]").val(CM.getValue());
				
			}, 100);
		}
	},
	
	setPageSize: function()
	{
		$("#sideNav").hide();
		$("#contentContainer").css("left", 0);
		$("#content").width($(document).width() - 100);
		$("#content").css('margin-left', -20 - (($("#content").width() - $(".pageWidth").width()) / 2));
		$(".xenForm").css("width", "auto");
	}
}

XenMerge.Form = function() { this.__construct(); };
XenMerge.Form.prototype = {
	
	$form: null,
	$saveReloadButton: null,
	$saveExitButton: null,
	
	__construct: function()
	{
		this.$form = $("form[name=XenMerge]");
		this.$saveReloadButton = $("#saveReloadButton");
		this.$saveExitButton = $("#saveExitButton");
		this.updateSaveActions();
	},
	
	updateSaveActions: function()
	{
		this.$form.submit( function() { return false; } );
		this.$saveReloadButton.click($.context(this, 'saveAjax'));
		this.$saveExitButton.click($.context(this, 'saveAjax'));
	},
	
	saveAjax: function(e)
	{
		var postParams, i, includeTitles;
		
		postParams = this.$form.serializeArray();
		
		if (e)
		{
			XenForo.ajaxDataPush(postParams, $(e.currentTarget).attr("name"), 1);
			e.preventDefault();
		}
		
		XenForo.ajaxDataPush(postParams, '_TemplateEditorAjax', 1);

		XenForo.ajax(
			this.$form.attr("action"),
			postParams,
			$.context(this, 'ajaxSaveSuccess')
		);

		return true;
	},
	
	ajaxSaveSuccess: function(ajaxData, textStatus)
	{
		if (XenForo.hasResponseError(ajaxData))
		{
			return false;
		}
		
		if (ajaxData.saveMessage)
		{
			XenForo.alert(ajaxData.saveMessage, '', 1000);
		}

		if (ajaxData._redirectTarget !== undefined)
		{
			window.location = XenForo.canonicalizeUrl(ajaxData._redirectTarget)
		}
	},
	
};