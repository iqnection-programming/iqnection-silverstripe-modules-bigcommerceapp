(function($){
	"use strict";
	$(document).ready(function(){
		if (typeof $.fn.select2 === 'function') {
			var select2config = {};
			$("select[data-ajax-call]").each(function(){
				select2config = {
					minimumInputLength: 3,
					placeholder: 'Search Products/Categories'
				};
				if (($.inArray($(this).data('resource-type'), ['categories','category']) > -1) && (window._categories !== undefined)) {
					select2config.data = window._categories;
					select2config.matcher = window._select2matcher;
				} else if (($.inArray($(this).data('resource-type'), ['products','product']) > -1) && (window._products !== undefined)) {
					select2config.data = window._products;
					select2config.matcher = window._select2matcher;
				} else {
					select2config.ajax = {
						url: window._search_url,
						dataType: 'json',
						delay:1000,
						debug:true,
						data: function(params){
							var resource = $(this).data('resource-type');
							if ($(resource).length) {
								resource = $(resource).val();
							}
							var q = {
								ajax: true,
								search: {
									value: params.term,
									order: [{
										column: "Title",
										dir: "ASC"
									}]
								},
								resource: resource
							};
							return q;
						}
					};
					select2config.processResults = function(data) {
						data = data.data;
						var results = [];
						var id, text, e = $(this.$element);
						for(var i=0;i<data.length;i++) {
							id = data[i].ID;
							if ( (e.data('id-field')) && (data[i][e.data('id-field')] !== undefined) )
							{
								id = data[i][e.data('id-field')];
							}
							text = data[i].DropdownText;
							if ( (e.data('text-field')) && (data[i][e.data('text-field')] !== undefined) )
							{
								text = data[i][e.data('text-field')];
							}
							results.push({
								"id": id,
								"text": text
							});
						}
						return {
							"results": results, 
							"pagination": {
								"more": false
							}
						};
					};
				}
				$(this).select2(select2config);
			});
			$('select.select2').select2();
		}
		
		
		if (typeof Sortable === 'function') {
			$(".sortable").each(function(){
				Sortable.create(this,{
					draggable: ".sort-item",
					handle: ".drag-handle",
					onUpdate: function(evt) {
						window.loading(true);
						var postData = {
							_ID: $(evt.srcElement).data('record-id'),
							ComponentName: $(evt.srcElement).data('component'),
							RelatedID: $(evt.srcElement).data('component-id'),
							SubcomponentName: $(evt.srcElement).data('subcomponent'),
							item_ids: []
						};
						$(evt.srcElement).find('.sort-item').each(function(){
							postData.item_ids.push($(this).data('id'));
						});
						var sortUrl = window._sort_url
						console.log(sortUrl);
						setTimeout(function(){
							$.ajax(sortUrl,{
								data: postData,
								complete: function(){
									window.loading(false);
								}
							});
						}, 0);
					}
				});
			});
		}

		var searchFilterTimer;
		var searchFilter;
		$("input[data-filter-target]").each(function(){
			if ($($(this).data('filter-target')).find('[data-filter-value]').length) {
				this.searchFilter = {
					input: this,
					targetItems: $($(this).data('filter-target')).find('[data-filter-value]'),
					searchTerm: '',
					previousTerm: '',
					searchTimer: 0,
					init: function() {
						var me = this;
						$(me.input).keyup(function(){
							me.previousTerm = $(me.input).val().toLowerCase();
							clearTimeout(me.searchTimer);
							me.searchTimer = setTimeout(function(){
								me.searchTerm = $(me.input).val().toLowerCase();
								// see if the user stopped typing
								if (me.searchTerm === me.previousTerm) {
									me.filter(me.searchTerm);
								}
							}, 1000);
						}).on('clear', function() {
							me.showAll();
						});
					},
					showAll: function(){
						this.targetItems.show();
						return this;
					},
					filter: function(term) {
						if (term === '') {
								this.showAll();
						} else {
							this.targetItems.hide().filter('[data-filter-value*="'+term.toString().toLowerCase()+'"]').each(function(){
								$(this).parents('li').show();
								$(this).show();
								$(this).find('li').show();
								$(this).parents('.collapse').each(function(){
									$(this).addClass('show').show();
									$('[data-target="'+$(this).attr('id')+'"]').removeClass('collapsed');
								});
							});
						}
						return this;
					}
				}
				this.searchFilter.init();
			}
				
		});
		$(".filter-form button").click(function(){
			$(this).parents('.filter-form').find('input').val('').trigger('clear');
		});
		
//		$('a.btn[href],button[type="submit"],input[type="submit"]').each(function(){
//			if (!$(this).attr('target')) {
//				$(this).click(function(){
//					window.loading(true);
//				});
//			}
//		});
		window.addEventListener('beforeunload', function(event) {
			window.loading(true);
		});
		
		window._setupAjaxLinks();
		window._setupInputMasks();
		window._setupSelectionGroups();
		
		if (window._ping_url !== undefined){
			setInterval(function(){
				$.get(window._ping_url);
			},120000); // ping every 2 minutes to keep the session active
		}
		
		$("input.toggle-hidden").change(function(){
			window.updateShowHidden(!$(this).prop('checked'),$(this).data('hidden-class'), $(this).data('hidden-target'));
		});
	});
	window.updateShowHidden = function(show, cssClass, target) {
		$(target).toggleClass(cssClass, show);
	}
	
	window._select2matcher = function(params, data) {
		// If there are no search terms, return all of the data
		if ($.trim(params.term) === '') {
		  return null;
		}
	
		// Do not display the item if there is no 'text' property
		if (typeof data.text === 'undefined') {
		  return null;
		}
	
		// `params.term` should be the term that is used for searching
		// `data.text` is the text that is displayed for the data object
		if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
			return {
				id: data.id,
				text: data.text	
			};
		}
	
		// Return `null` if the term should not be displayed
		return null;
	};
	window.loading = function(on) {
		if (on) {
			$(".dashboard-wrapper").addClass('loading');
		} else {
			$(".dashboard-wrapper").removeClass('loading');
		}
	};
	window._alert = function(message, status) {
		if (status === undefined) {
			status = 'success';
		}
		var a=$($("#alert-template").html()).addClass('alert-'+status);
		a.find('.message').append(message);
		$("#alert-container").append(a);
	};
	window._spinner = function(){
		return $($("#spinner-template").html());
	};
	window._elementLoading = function(element) {
		$(element).addClass('has-loading').append($("#loading-template").html());
	};
	window._setupAjaxLinks = function(){
		if (window._disable_ajax) { return; }
		$("a.ajax").not('[data-ajax]').each(function(){
			$(this).attr('data-ajax',1).click(function(e){
				window.loading(true);
				e.preventDefault();
				var lnk=$(this);
				var useModal = false;
				if ( (lnk.data('modal') === '1') || (lnk.data('modal') === 1) ) {
					$("#genericModal").find('.modal-title').text(lnk.data('modal-title'));
					$("#genericModal").find('.modal-body').empty().append(window._spinner());
					$("#genericModal").modal('show');
					useModal = true;
				}
				if (typeof window[lnk.attr('data-ajax-before-callback')] === 'function') {
					window[lnk.attr('data-ajax-before-callback')](lnk);
				}
				$.ajax(lnk.attr('href'),{
					type: 'GET',
					success: function(response){
						if (response.message) {
							if (useModal) {
								$("#genericModal").find('.modal-body').empty().text(response.message);
							} else {
								window._alert(response.message, response.success ? 'success' : 'danger');
							}
						}
						if (typeof window[lnk.attr('data-ajax-after-callback')] === 'function') {
							window[lnk.attr('data-ajax-after-callback')](lnk,response);
						}
					},
					error: function(){
						window._alert('There was an error, please try again','danger');
					},
					complete: function(){
						window.loading(false);
//						 window._setupAjaxLinks();
					}
				});
			});
		});
	};
	
	window.removingItem = function(a) {
		window._elementLoading($(a).parents('[data-block-element]'));
	};
	window.itemRemoved = function(a, response) {
		if (response.success) {
			$(a).parents('[data-block-element]').fadeOut(function(){
				$(this).remove();
			});
		}
	}
	
	window._scrollTo = function(element){
		var to=$(element).offset().top.toFixed(0) - 200;
		$([document.documentElement, document.body]).animate({scrollTop:to},200);
	};
	
	window._setupInputMasks = function(){
		if (typeof inputmask === 'function') {
			$(".inputmask[data-inputmask]").inputmask();
			$(".date-inputmask").inputmask("dd/mm/yyyy");
			$(".phone-inputmask").inputmask("(999) 999-9999");
			$(".cc-inputmask").inputmask("9999 9999 9999 9999");
			$(".currency-inputmask").inputmask("$9999");
			$(".percentage-inputmask").inputmask("99%");
			$(".decimal-inputmask").inputmask({
				alias: "decimal",
				radixPoint: "."
			});

			$(".email-inputmask").inputmask({
				mask: "*{1,20}[.*{1,20}][.*{1,20}][.*{1,20}]@*{1,20}[*{2,6}][*{1,2}].*{1,}[.*{2,6}][.*{1,2}]",
				greedy: !1,
				onBeforePaste: function(n, a) {
					return (n = n.toLowerCase()).replace("mailto:", "")
				},
				definitions: {
					"*": {
						validator: "[0-9A-Za-z!#$%&'*+/=?^_`{|}~/-]",
						cardinality: 1,
						casing: "lower"
					}
				}
			});
		}
	};
	window._setupSelectionGroups = function(){
		$('ul.selectiongroup input.selector').each(function(){
			this.compositeFieldContainer = $(this).parents('li').first();
			this._updateActive = function(){
				if ($(this).prop('checked')) {
					this.compositeFieldContainer.siblings().removeClass('active selected');
					this.compositeFieldContainer.addClass('active selected');
				} else {
					this.compositeFieldContainer.removeClass('active selected');
				}
			};
			$(this).click(function(){
				this._updateActive();
			});
			this._updateActive();
		});
	};
}(jQuery));