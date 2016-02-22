/**
 * Copyright (c) 2008-13 Owebia
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 *
 * @website    http://www.owebia.com/
 * @project    Magento Owebia Shipping 2 module
 * @author     Antoine Lemoine
 * @license    http://www.opensource.org/licenses/MIT  The MIT License (MIT)
**/


/**
 * @constructor
 */
OS2Editor = function (options) {
	this.options = options;

	this.jwindow = jQuery(window);
	this.jdialog = null;
	this.jpages = [];
	this.jcurrentpage = null;
	this.jhiddencontainer = null;
	this.jtextarea = null;
	this.jcontextualmenu = null;
	this.mouse_event_owner = null;
	this.dialog_v_padding = 10;
	this.dialog_h_padding = 15;
	this.opened = false;
	this.has_dialog = false;
	this.shipping_code = null;
	this.history = [];
	this.source = '';
}

OS2Editor.prototype = {
	/**
	 * @private
	 */
	_getPropertyInput: function (object) {
		if (!(object instanceof jQuery)) {
			object = jQuery(object);
			if (object[0].nodeName!='textarea') object = object.parents('#os2-field-dialog').find('textarea');
		}
		return object;
	},

	/**
	 * @public
	 */
	getReadableSelection: function (object, callback) {
		var jinput = this._getPropertyInput(object);
		this._ajax({
			data: {
				what: 'readable-selection',
				property: jinput.attr('name'),
				input: jinput.val()
			},
			success: function (msg) {
				if (typeof callback=='function') {
					callback(msg);
				} else {
					jQuery('#os2-output').html(msg);
				}
			}
		});
	},

	/**
	 * @public
	 */
	insertAtCaret: function (object, text_to_insert) {
		var jinput = this._getPropertyInput(object);
		var range = jinput.caret();
		var start = range.start;
		jinput.val(range.replace(text_to_insert));
		jinput.caret({start: start, end: start+text_to_insert.length});
	},

	/**
	 * @private
	 */
	_getAjaxData: function (data) {
		data.form_key = this.options.form_key;
		return data;
	},

	/**
	 * @private
	 */
	_ajax: function (args) {
		var options = {
			type: 'POST',
			url: this.options.ajax_url,
			data: this._getAjaxData(args.data),
			success: args.success,
			dataType: args.dataType ? args.dataType : 'html'
		}
		if (typeof args.failure=='function') options.failure = args.failure;
		jQuery.ajax(options);
	},
	
	/**
	 * @private
	 */
	_download: function (data) {
		data.form_key = this.options.form_key;
		var jform = jQuery('<form action="'+this.options.ajax_url+'" method="post"></form>');
		for (var name in data) {
			var jinput = jQuery('<input type="hidden"/>');
			jinput.attr('name', name).attr('value', data[name]);
			jform.append(jinput);
		}
		jform.appendTo('body').submit().remove();
	},

	/**
	 * @private
	 */
	_getConfig: function () {
		var self = this;
		var config_objects = [];
		this.jdialog.find('#os2-editor-elems-container > li').each(function(){
			var jrowcontainer = jQuery(this);
			if (jrowcontainer.hasClass('ignored-lines')) {
				config_objects.push(jrowcontainer.find('.field').val()+"\n");
			} else {
				config_objects.push(self._getConfigRow(jrowcontainer));
			}
		});
		var config = "{\n"+config_objects.join(",\n")+"}\n";
		return config;
	},

	/**
	 * @private
	 */
	_getConfigRow: function (jrowcontainer) {
		var self = this;
		var config_properties = [];
		var id = null;
		jrowcontainer.find('.field').each(function(){
			var jinput = jQuery(this);
			var property_name = jinput.attr('name');
			var value = jinput.val();
			
			switch (property_name) {
				case '*id':
					id = value;
					break;
				case 'enabled':
					if (value!='1') config_properties.push("\""+property_name+"\": false");
					break;
				case 'type':
					if (value!='method') config_properties.push("\""+property_name+"\": \""+value.replace(/\"/g,"\\\"")+"\"");
					break;
				default:
					if (value!='') {
						config_properties.push("\""+property_name+'\": "'+value.replace(/\"/g,"\\\"")+'"');
					}
					break;
			}
		});
		var property_indent = "\t";
		return "\""+id+"\": {\n"+property_indent+config_properties.join(",\n"+property_indent)+"\n}";
	},

	/**
	 * @public
	 */
	addRow: function () {
		var self = this;
		this._ajax({
			dataType: "json",
			data: {
				what: 'add-row',
				source: this.source
			},
			success: function (response) {
				var jsource = self.jdialog.find('#os2-source');
				jsource.val(response.source);
				self._sourceChanged(false);
			}
		});
	},

	/**
	 * @public
	 */
	saveToFile: function () {
		this._download({
			what: 'save-to-file',
			source: this.source
		});
	},

	/**
	 * @public
	 */
	save: function () {
		var self = this;
		this._ajax({
			data: {
				what: 'save-config',
				source: this.source,
				shipping_code: this.shipping_code
			},
			success: function (msg) {
				self.jtextarea.val(msg);
				self.close();
			}
		});
		/*
		this.jtextarea.val(this._getConfig());
		this.close();
		*/
	},

	/**
	 * @public
	 */
	close: function () {
		this.opened = false;
		jQuery.colorbox.close();
	},

	/**
	 * @public
	 */
	refreshHelp: function () {
		var current_help_section = this.history.pop();
		this.help(current_help_section, true);
	},

	/**
	 * @public
	 */
	previousHelp: function () {
		var current_help_section = this.history.pop();
		var prev_help_section = this.history.pop();
		if (prev_help_section) this.help(prev_help_section);
	},

	/**
	 * @private
	 */
	_initEditor: function (jdialog) {
		var self = this;
		jdialog.find('#os2-source').attr('wrap', 'off').css('white-space', 'pre'); // Patch IE
		jdialog.on('click', '#os2-editor-elems-container > li > h5', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var jelem = jQuery(this);
			var jitem = jelem.parent();
			var jcontainer = jitem.children('.row-ui');
			if (jcontainer.hasClass('opened')) {
				jcontainer.slideUp();
				jcontainer.removeClass('opened');
				return;
			}
			jcontainer.html('<div class="loading rule-param-wait" style="margin:20px;">'+self.options.loading_label+'</div>');
			jcontainer.slideDown();
			var id = jitem.attr('data-id');
			var ajax_data = {
				what: 'row-ui',
				source: self.source,
				id: id
			};
			ajax_data = self._getAjaxData(ajax_data);
			jQuery.ajax({
				type: 'POST',
				url: self.options.ajax_url,
				data: ajax_data
			}).done(function (msg) {
				jcontainer.html(msg);
				jcontainer.animate({'height': 'auto'}, 1000);
				jcontainer.addClass('opened');
			});
		});
		jdialog.on('click', '#os2-editor .properties-list > li', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var jelem = jQuery(this);
			var property = jelem.attr('data-property');
			var jrow = jelem.data('jrow');
			var jproperty_container = jelem.data('jproperty_container');
			if (!jrow) {
				jrow = jelem.parents('#os2-editor-elems-container > li');
				jproperty_container = jrow.find('div[data-property="'+property+'"]');
			}
			jelem.addClass('selected').siblings().removeClass('selected');
			jproperty_container.addClass('selected').siblings().removeClass('selected');
			jrow.data('layout').resizeAll();
		});
		jdialog.on('focusin', '#os2-editor .properties-container .field', function (e) {
			var jinput = jQuery(this);
			jQuery('#os2-field-dialog').remove();
			jinput.data('previous_value', jinput.val());
		});
		jdialog.on('change', '#os2-editor .properties-container select.field', function (e) {
			e.preventDefault();
			e.stopPropagation();
			var jinput = jQuery(this);
			var value = jinput.data('previous_value');
			if (value!=jinput.val()) self._updateProperty(jinput);
		});
		jdialog.on('click', '.os2-field-help', function (e) {
			var jbtn = jQuery(this);
			var property_name = jbtn.attr('data-id');
			self.help(property_name);
		});
		jdialog.on('click', '.os2-remove-row-btn', function (e) {
			e.stopPropagation();
			var jbtn = jQuery(this);
			var jrow = jbtn.parents('li').eq(0);
			var id = jrow.attr('data-id');
			var ajax_data = {
				what: 'remove-row',
				source: self.source,
				id: id
			};
			ajax_data = self._getAjaxData(ajax_data);
			jQuery.ajax({
				dataType: "json",
				type: 'POST',
				url: self.options.ajax_url,
				data: ajax_data
			}).done(function (response) {
				var jsource = self.jdialog.find('#os2-source');
				jsource.val(response.source);
				self._sourceChanged(false);
			});
		});
		jdialog.on('focusin', '#os2-editor input.field', function (e) {
			var jfielddialog = jQuery('#os2-field-dialog');
			if (!jfielddialog.length) {
				jfielddialog = jQuery('<div id=os2-field-dialog></div>');
				jdialog.append(jfielddialog);
			} else {
				jfielddialog.empty();
			}
			var jinput = jQuery(this);
			var jproperty_container = jinput.parents('#os2-editor .os2-p-container');
			var range = jinput.caret();
			var top = jinput.offset().top-jdialog.offset().top-1;
			var left = jinput.offset().left-jdialog.offset().left+5;
			jfielddialog.css({position: 'absolute', top: top, left: left});
			var jtools = jQuery('<div id=os2-field-tools></div>');
			var japply = jQuery('<button class=save><span>'+self.options.apply_btn_label+'</span></button>');
			japply.click(function(){
				var new_value = jtextarea.val();
				jinput.val(new_value);
				jfielddialog.remove();
				var value = jinput.data('previous_value');
				if (value!=new_value) self._updateProperty(jinput);
			});
			var jcancel = jQuery('<button class=cancel><span>'+self.options.cancel_btn_label+'</span></button>');
			jcancel.click(function(){
				jfielddialog.remove();
			});
			var property_name = jinput.attr('name');
			var property_value = jinput.val();
			var jtextarea = jQuery('<textarea></textarea>')
				.val(property_value)
				.attr('name', property_name)
			;
			jfielddialog.append('<h5>'+jproperty_container.find('th').html()+'</h5>', jtools, jtextarea, '<br/>', japply, ' ', jcancel);
			jtextarea.caret(range);
			var ajax_data = {
				what: 'property-tools',
				property: property_name,
				input: property_value
			};
			ajax_data = self._getAjaxData(ajax_data);
			jQuery.ajax({
				type: 'POST',
				url: self.options.ajax_url,
				data: ajax_data
			}).done(function (msg) {
				jtools.append(msg);
			});
		});
		jdialog.on('focusout', '#os2-editor .property-container .field', function (e) {
			e.preventDefault();
			e.stopPropagation();
			self._updateProperty(this);
		});
	},

	_button: function (type, callback) {
		var jbutton = jQuery("<button class=\"os2-btn os2-btn-"+type+"\"></button>");
		jbutton.click(callback);
		return jbutton;
	},

	/**
	 * @private
	 */
	_updateProperty: function (jinput) {
		var self = this;
		if (!(jinput instanceof jQuery)) jinput = jQuery(jinput);

		var jrowcontainer = jinput.parents('#os2-editor-elems-container > li');
		var property_name = jinput.attr('name');
		switch (property_name) {
			case 'label':
				var title = jinput.val().trim();
				if (title=='') title = this.options.default_row_label;
				jrowcontainer.find('h5').html(title);
				break;
		}
		var jsource = this.jdialog.find('#os2-source');
		var row_id = jrowcontainer.attr('data-id');
		this._ajax({
			dataType: "json",
			data: {
				what: 'update-property',
				source: jsource.val(),
				row: row_id,
				property: property_name,
				value: jinput.val()
			},
			success: function (response) {
				jsource.val(response.source);
				self._sourceChanged(false, [row_id]);
			},
			failure: function (response) {
			alert('failure:'+JSON.stringify(response));
			}
		});
	},

	/**
	 * @private
	 */
	_sourceChanged: function (force, row_ids) {
		var self = this;
		var jsource = this.jdialog.find('#os2-source');
		var new_source = jsource.val();
		if (force || new_source!=self.source) {
			self.source = new_source;
			this._ajax({
				dataType: "json",
				data: {
					what: 'correction',
					source: new_source,
					row_ids: row_ids
				},
				success: function (response) {
					self.jdialog.find('#os2-correction').html(response.correction);
					self.jdialog.find('#os2-debug').html(response.debug);
					var jeditor = self.jdialog.find('#os2-editor');
					jeditor.html(response.editor);
				}
			});
		}
	},

	/**
	 * @public
	 */
	init: function (object, shipping_code) {
		if (!this.opened) {
			this.shipping_code = shipping_code;
			this.has_dialog = false;
			this.opened = true;
			this.jpages = [];
			var jdialog = jQuery('<div id=os2-dialog><div id=os2-page-container class=ui-layout-center></div></div>');
			this._openDialog(jdialog);
			var jloadingpage = jQuery('<div class=os2-page><div class="loading rule-param-wait" style="margin:20px;">'+this.options.loading_label+'</div></div>');
			this._openPage('loading', jloadingpage);

			var jcell = jQuery(object).parents('td.value');
			this.jtextarea = jcell.find('textarea');
			this.jhiddencontainer = jQuery('<div style="display:none;"></div>');
			this.jtextarea.after(this.jhiddencontainer);
			this.source = this.jtextarea.val();
		}
		return this;
	},

	/**
	 * @private
	 */
	_openDialog: function (jdialog) {
		this.jdialog = jdialog;
		jQuery.colorbox({
			width: '95%',
			height: '95%',
			inline: true,
			href: jdialog,
			fixed: true
		});
		this._initEditor(this.jdialog);
	},

	/**
	 * @private
	 */
	_openPage: function (id, jpage) {
		var jpagecontainer = this.jdialog.find('#os2-page-container');
		if (typeof jpage!='undefined') {
			this.jpages[id] = jpage;
			jpagecontainer.append(jpage);
		}
		jpage = this.jpages[id];
		jpagecontainer.children().hide();
		jpage.show();
		//alert('open '+id+' : '+this.jdialog.html());
		this.jcurrentpage = jpage;
	},

	/**
	 * @private
	 */
	_initLayout: function (jdialog, jpage) {
		var static_layout_options = {
			applyDefaultStyles: false,
			resizable: false,
			closable: false,
			spacing_open: 0,
			spacing_closed: 0
		};
		jdialog.layout(static_layout_options);
		jpage.layout({
			applyDefaultStyles: false,
			west__size: '30%',
			east__size: '30%',
			spacing_closed: 15
		});
		//self.jdialog.html(msg);
		jdialog.find('.inner-layout').each(function() {
			var layout = jQuery(this);
			if (!layout.children('.ui-layout-center').length) return;
			layout.layout(static_layout_options);
		});
	},

	/**
	 * @public
	 */
	page: function (page, data, callback, refresh) {
		var self = this;
		var id = JSON.stringify({page: page, data: data});
		if (typeof this.jpages[id]!='undefined' && refresh!==true) {
			this._openPage(id);
			if (typeof callback!='undefined') callback();
			return;
		}
		if (typeof data=='undefined') data = {};
		switch (page) {
			case 'source':
				callback = function () {
					var jsource = self.jdialog.find('#os2-source');
					jsource.val(self.source);
					jsource.blur(function (e) {
						self._sourceChanged();
					}).keyup(function (e) {
						clearTimeout(jQuery(this).data('timer'));
						jQuery(this).data('timer', setTimeout(function() { self._sourceChanged(); }, 500));
					});
					self._sourceChanged(true);
				};
		}
		data.what = 'page';
		data.with_dialog = this.has_dialog ? 0 : 1;
		data.page = page;

		this._openPage('loading');
		var ajax_data = this._getAjaxData(data);
		jQuery.ajax({
			type: 'POST',
			url: this.options.ajax_url,
			data: ajax_data
		}).done(function (msg) {
			var jpage = null;
			if (data.with_dialog) {
				self._openDialog(jQuery(msg));
				jpage = self.jdialog.find('#os2-page-container > .os2-page');
			} else {
				jpage = jQuery(msg);
			}

			var jdialog = self.jdialog;
			self.jpages[id] = jpage;
			self._openPage(id, jpage);
			self.has_dialog = true;
			self._initLayout(jdialog, jpage);
			if (typeof callback!='undefined') callback();
		});
	},

	_highlight: function (elem) {
		jQuery(elem).html('<span class=\"stabilo\">'+jQuery(elem).html()+'</span>');
	},

	/**
	 * @public
	 */
	help: function (help_section, refresh) {
		var self = this;
		this.page('help', {input: help_section}, function() {
			if (!self.history.length || help_section!=self.history[self.history.length-1]) self.history.push(help_section);

			self.jdialog.find('.field').not(':eq(0)').hide();
			self.jdialog.find('.field').each(function(){
				var name = jQuery(this).find('a[name]').attr('name');
				jQuery(this).attr('data-anchor', name);
			});
			self.jdialog.find('div.new, li.new, a.new, p.new').each(function(){
				self._highlight(this);
			});
			self.jdialog.find('ul.new li, li.new li').each(function(){
				self._highlight(this);
			});
			self.jdialog.find('span.new').each(function(){
				jQuery(this).addClass('stabilo');
			});
		}, refresh);
	}
}

