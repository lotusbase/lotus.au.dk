$(document).ready(function() {

	$w	= $(window);

	// Global vars
	var globalVar = {
		cookies: Cookies.noConflict()
	};
	var globalFun = {
		getCookie: function() {
			var c = globalVar.cookies.getJSON('lotusbase');
			if(!c || typeof c === undefined) {
				c = {};
			}
			return c;
		},
		getJWT: function() {
			var c = globalVar.cookies.get('auth_token');

			if(!c || typeof c === undefined) {
				c = {};
			} else {
				c = globalFun.parseJWT(c);
			}

			return c;
		},
		parseJWT: function(token) {
			var base64Url = token.split('.')[1],
				base64 = base64Url.replace('-', '+').replace('_', '/');
			return JSON.parse(window.atob(base64));
		}
	};

	// Set custom headers for all API calls
	$.ajaxPrefilter(function(options) {
		if (!options.beforeSend) {
			options.beforeSend = function (xhr) { 
				if(globalVar.cookies.get('auth_token')) {
					xhr.setRequestHeader('Authorization', 'Bearer '+globalVar.cookies.get('auth_token'));
				}
			};
		}
	});

	// Fix table widths
	$("#rows tbody td").each(function(i) {
		$("#rows tbody td").eq(i).attr("data-width", $(this).width());
	});

	// Dynamically position login and registration window
	function centerize(elements) {
		var arr = elements.split(", ");
		$.each(arr, function(i) {
			$(arr[i]).css({
				"margin-top": -0.5*$(arr[i]).outerHeight()
			});
		});
	}
	centerize(".floating form");

	// Form validation
	$("#register-form").validate({
		rules: {
			fname: "required",
			lname: "required",
			email: {
				required: true,
				email: true
			},
			login: "required",
			password: "required",
			cpassword: {
				required: true,
				equalTo: "#password"
			},
			captcha_code: "required"
		},
		errorElement: "label"
	});
	
	// Hide first
	$(".toggle.hide-first > form").hide();

	// Toggle
	$(".toggle h3 a").toggle(function(){
		if($(this).hasClass("open")) {
			$(this).removeClass("open");
			$(this).parent().siblings().hide();			
		} else {
			$(this).addClass("open");
			$(this).parent().siblings().show();
		}
	}, function(){
		if(!$(this).hasClass("open")) {
			$(this).addClass("open");
			$(this).parent().siblings().show();	
		} else {
			$(this).removeClass("open");
			$(this).parent().siblings().hide();
		}
	});

	// Sticky order table header
	$("#default-heading").clone().appendTo("#order-table").attr("id", "sticky-heading").width($("#default-heading").width());
//	$(".order-card")
//		.filter(":first")
//		.clone()
//		.appendTo("#order-table")
//		.attr("id", "sticky-card")
//		.css({
//			"top":48
//		})
//		.empty();
//	$(".order-card").each(function(i) {
//		// Absolutely position headers
//		$(this).not("#sticky-card").css({
//			"position": "absolute",
//			"z-index": 2*i+100
//		});
//
//		// Create spacer element
//		var $spacer = $(this).after('<div class="card-spacer" />').next();
//	});
	$w.scroll(function(){
		if ($w.scrollTop() > $("#default-heading").offset().top) {
			$("#sticky-heading").css({
				"opacity": 1,
				"display": "block"
			});
		} else {
			$("#sticky-heading").css({
				"opacity": 0,
				"display": "none"
			});
		}

//		$(".order-card").each(function(i) {
//			if($w.scrollTop() > $(this).offset().top - $("#default-heading").outerHeight()) {
//				$("#sticky-card").html($(this).html()).css({
//					"opacity": 1,
//					"display": "block",
//					"z-index": 2*i+101
//				});
//			}
//		});
//
//		if($w.scrollTop() < $("#default-heading").offset().top) {
//			$("#sticky-card").css({
//				"opacity": 0,
//				"display": "none"
//			});
//		}
	});

	// Highlight columns
	$(".order-details table").delegate("td", "mouseover mouseleave", function(e){
		if(e.type == "mouseover") {
			$(this).parents("table").children("colgroup").eq($(this).index()).addClass("focused");
			$("#sticky-heading > div").eq($(this).index()).addClass("focused");
			$(".table-heading > div").eq($(this).index()).addClass("focused");
			$(this).parents("tr").addClass("focused");
		} else {
			$(this).parents("table").children("colgroup").removeClass("focused");
			$("#sticky-heading > div").eq($(this).index()).removeClass("focused");
			$(".table-heading > div").eq($(this).index()).removeClass("focused");
			$(this).parents("tr").removeClass("focused");
		}
	});

	// Hide lengthy text
	$("#rows tbody td > span.limit").each(function() {
		var content = $(this).html();
		var limit = 25;
		if(content.length > limit) {
			var c = content.substr(0, limit) + '&hellip;';
			$(this).attr("data-full-text", content);
			$(this).hide();
			$(this).after('<span class="no-limit">'+c+'</span>')
		}
	});

	// Input/textarea value injection
	$(".today-date").click(function() {
		var d = new Date(),
			currentdate = d.getFullYear()+"-"+((""+(d.getMonth()+1)).length<2 ? "0" : "")+(d.getMonth()+1)+"-"+((""+d.getDate()).length<2 ? "0" : "")+d.getDate();
		$(this).siblings("input:text").val(currentdate);
		$(this).parents("tr").trigger("change");
	});

	$(".com-ins").click(function() {
		var c = $(this).siblings("textarea").val();
		$(this).siblings("textarea").val(c + $(this).data("text-insert") + ' ');
		$(this).parents("tr").trigger("change");
	});

	// Orders live edit
	$(".order-details table tr").click(function() {
		$(this).find(".allow-edit span").hide();
		$(this).find(".db-edit").show();
	}).change(function() {
		var OrderID = $(this).attr("id").substr(6),
			OrderSalt = $(this).data("order-salt"),
			SeedQuantity = $(this).find("#input-seeds-"+OrderID).val(),
			AdminID = $("#admin-id").val(),
			AdminComments = $(this).find("#input-com-"+OrderID).val(),
			InternalComments = $(this).find("#input-int-"+OrderID).val(),
			ProcessDate = $(this).find("#input-date-"+OrderID).val();

		if(SeedQuantity<0 || SeedQuantity=='') {
			SeedQuantity = '';
		}

		var DataString = "type=1&id="+OrderID+"&salt="+OrderSalt+"&seed="+SeedQuantity+"&admin="+AdminID+"&com="+encodeURIComponent(AdminComments)+"&intcom="+encodeURIComponent(InternalComments)+"&date="+ProcessDate;

		$.ajax({
			type: "GET",
			url: "admin-api",
			data: DataString,
			dataType: 'json',
			cache: false,
			success: function(data) {
				if(data.success==false) {
					modal('API has responded, but an error occurred'+data.message+'</p><p>Please contact the system administrator.','warntext');
				} else if(data.success==true) {
					$("#admin-"+OrderID).html(data.admin);
					$("#com-"+OrderID).html(AdminComments);
					$("#int-"+OrderID).html(InternalComments);
					$("#seeds-"+OrderID).html(SeedQuantity);
					$("#date-"+OrderID).html(ProcessDate);
					$("#order-"+OrderID+" td").css({
						"background-color": "#A2D39C"
					});
					setTimeout(function() {
						$("#order-"+OrderID+" td").css({
							"background-color": "transparent"
						});
					}, 1000);
				} else {
					modal('No data returned by API','No data has been returned from the backend. Please contact the system administrator.','warntext');
				}
			},
			failure: function() {
				modal('AJAX call to API has failed','There is an error making an AJAX call to the API, and no specific information has been returned.','warntext');
			}
		});
	});

	// Sending out emails to recepients by AJAX call
	$("#shipping-table button.action-send").click(function() {
		var OrderSalt = $(this).parents(".order-row").attr("id").substr(6),
			DataString = "key="+OrderSalt+"&type=4";
			context = $(this);

		$.ajax({
			type: "GET",
			url: "admin-api",
			data: DataString,
			dataType: "json",
			cache: false,
			success: function(data) {
				if(data.success) {
					context.parents(".order-card").css({"background-color": "#A2D39C"});
					context.html('<span class="pictogram icon-check"></span>Notification Sent').css({
						"color":"#297030"
					});

					setTimeout(function() {
						context.parents(".order-card").css({"background-color":"#ccc"}).parent(".order-row").fadeOut(500, function() {
							$(this).remove();
						});
					}, 1000);
				} else {
					modal('API has responded, but an error occurred'+data.message+'</p><p>Please contact the system administrator.','warntext');
				}
			}
		});
	});

	// Downloads live edit
	$(".reset-count").click(function() {
		$(this).siblings("input:text").val(0);
		$(this).parents(".edit").trigger("change");
	}).mouseup(function(){
		return false;
	});
	$(".downloads #rows tbody .edit").click(function() {
		$(this).find(".allow-edit span").hide();
		$(this).find(".db-edit").show();
	}).change(function() {
		var DownloadID = $(this).attr('id').substr(4);
		var FileDesc = $(this).find("#input-desc-"+DownloadID).val();
		var FileCount = $(this).find("#input-count-"+DownloadID).val();

		if(FileCount<0 || FileCount=='') {
			FileCount = 0;
		}

		var DataString = "type=2&id="+DownloadID+"&desc="+FileDesc+"&count="+FileCount;
		$.ajax({
			type: "GET",
			url: "admin-api",
			data: DataString,
			dataType: 'json',
			cache: false,
			success: function(data) {
				if(data.success==false) {
					modal('API has responded, but an error occurred'+data.message+'</p><p>Please contact the system administrator.','warntext');
				} else if(data.success==true) {
					$("#desc-"+DownloadID).html(FileDesc);
					$("#count-"+DownloadID).html(FileCount);
					$("#row-"+DownloadID+" td").css({
						"background-color": "#A2D39C"
					});
					setTimeout(function() {
						$("#row-"+DownloadID+" td").css({
							"background-color": "transparent"
						});
					}, 1000);
				} else {
					modal('No data returned by API','No data has been returned from the backend. Please contact the system administrator.','warntext');
				}
			},
			failure: function() {
				modal('AJAX call to API has failed','There is an error making an AJAX call to the API, and no specific information has been returned.','warntext');
			}
		});
	});

	// Live edit mouse events
	$(".order-details table tr, .downloads #rows tbody .edit").mouseup(function() {
		return false;
	});
	$(document).keyup(function(e) {
		if(e.keyCode == 27) {
			$(".order-details table tr .allow-edit span").show();
			$(".order-details table tr .db-edit").hide();
		}
	});
	$(document).mouseup(function() {
		$(".order-details table tr .allow-edit span").show();
		$(".order-details table tr .db-edit").hide();
	});

	// File delete
	$(".file-delete").click(function() {
		var FileID = $(this).attr('id').substr(7);
		var FileName = $("#name-"+FileID).html();
		modal('Confirm file deletion', '<p>Are you sure you want to delete the following file: <strong><code>'+FileName+'</code></strong>? The deletion step is irreversible. Database entry of the file will be removed.</p><p class="dialog"><a class="dialog-yes delete-yes" id="confirm-delete-'+FileID+'">Yes, delete file</a><a class="dialog-no delete-no">No, take me back</a></p>');
	});
	$(document).on("click", ".dialog-yes.delete-yes", function() {
		modalClose();
		var FileID = $(this).attr('id').substr(15);
		var DataString = "type=3&id="+FileID;
		$.ajax({
			type: "GET",
			url: "admin-api",
			data: DataString,
			dataType: 'json',
			cache: false,
			success: function(data) {
				console.log(data);
				if(data.success) {
					$("#row-"+FileID)
						.css({"background-color": "#eab4b4"})
						.fadeOut(500, function() {
							$(this).remove();
						});
					
				} else {
					modal('API has responded, but an error occurred'+data.message+'</p><p>Please contact the system administrator.','warntext');
				}
			},
			failure: function() {
				modal('AJAX call to API has failed','There is an error making an AJAX call to the API, and no specific information has been returned.','warntext');
			}
		});
	});
	$(document).on("click", ".dialog-no.delete-no",function() {
		modalClose();
	});

	// Check all / Uncheck all toggle
//	$(".ca").change(function(){
//		if($(this).attr("checked")) {
//			$("#rows tbody input:checkbox").attr("checked", "checked").closest("tr").addClass("checked");
//			$(".ca").attr("checked", "checked");
//			updatechecked();
//		} else {
//			$("#rows tbody input:checkbox").removeAttr("checked").closest("tr").removeClass("checked");
//			$(".ca").removeAttr("checked");
//			updatechecked();
//		}
//	});

	// Pluralize
	function pluralize(number, singular, plural) {
		if(!plural) { var plural = singular+"s" }
		return ((number === 1) ? singular : plural);
	}

	// Check download set
	function itemScope(ele) {
		if(ele.children("option:selected").val() == 'all') {
			$("#order-rows button[class*='action'] > span").hide();
		} else {
			$("#order-rows button[class*='action'] > span").show();
		}

		updateCount();
	}

	// Update chekced cout
	function updateCount() {
		$("#order-rows button[class*='action'] span.count").text($(".orders .order-card input:checkbox:checked").length);
	}

	// Download set
	itemScope($("#item-scope"));
	$("#item-scope").change(function() {
		if($(this).find("option[value='all']").is(":selected")) {
			$(".orders .order-card input:checkbox").prop("checked",false);
		}
		itemScope($(this));
	});

	// Update checked count
	$(".orders .order-card input:checkbox").change(function() {
		if($(".orders .order-card input:checkbox:checked").length > 0) {
			$("#item-scope option[value='some']").prop("selected",true).parent("select").trigger("change");
		} else {
			$("#item-scope option[value='all']").prop("selected",true).parent("select").trigger("change");
		}

		updateCount();
	});

	// Define download action
	$(".download-action").click(function() {
		$("#action-type").val($(this).attr('data-download-action'));
		$("#order-rows").submit();
	});

	// Define admin action
	$(".admin-action").click(function(e) {
		e.preventDefault();

		var $adbtn = $(this),
			scope = $("#item-scope").val(),
			action = $(this).data('admin-action'),
			confirm =  false,
			os;

		if(action == '4') {
			confirm = true;
		} else if (action == '5') {
			if(window.confirm('Are you sure you want to delete the selected database entries?')) {
				confirm = true;
			}
		}

		if(confirm) {
			if(scope == 'all') {
				os = $("#order-salts").val();
			} else if(scope == 'some') {
				os = [];
				$(".card-name input:checkbox:checked").each(function() {
					os.push($(this).val());
				});
				os = JSON.stringify(os);
			}

			// Make ajax call
			$.ajax({
				type: "POST",
				url: "admin-api",
				data: "type=6&scope="+scope+"&action="+parseInt($(this).data('admin-action'))+"&salts="+os,
				dataType: 'json',
				cache: false,
				global: false,
				async: false,
				success: function(data) {
					if(data.success) {
						console.log(data.success);
						if(action == '4') {
							$.each(data.os, function() {
								$("#order-"+data.os)
									.find(".order-card")
										.removeClass("order-unverified")
										.addClass("order-verified")
										.css({ 'background-color': '#A2D39C'})
										.find(".verification-status")
											.removeClass("icon-cancel")
											.addClass("icon-check");
							});
						} else if(action == '5') {
							$.each(data.os, function() {
								$("#order-"+data.os)
									.find(".order-card")
										.css({ 'background-color': '#F7977A' })
										.end()
									.slideUp(500, function() {
										$(this).remove();
									});
							});
						}
					} else {
						modal('AJAX call returned an error','AJAX call is successful but returned an error with the message: '+data.message,'warntext');
					}
				},
				failure: function() {
					modal('AJAX call to API has failed','There is an error making an AJAX call to the API, and no specific information has been returned.','warntext');
				}
			});
		} else {
			modal('Admin action aborted','Admin action is not carried out, no changes to the database has been made.');
		}
	});


	// Table column highlight
	$("#rows, #sticky").delegate("td", "mouseover mouseleave", function(e){
		if(e.type == "mouseover") {
			$("#rows colgroup, #sticky colgroup").eq($(this).index()).addClass("hover");
			$("#rows thead td").eq($(this).index()).addClass("hover");
			$("#sticky thead td").eq($(this).index()).addClass("hover");
		} else {
			$("#rows colgroup, #sticky colgroup").eq($(this).index()).removeClass("hover");
			$("#rows thead td").eq($(this).index()).removeClass("hover");
			$("#sticky thead td").eq($(this).index()).removeClass("hover");
		}
	});

	// Modal box
		function modal(title, content, modalclass) {
			// Open modal box
			$("#modal").show().addClass("open");
			$("body").addClass("blur");

			// Write content into modal box
			$("#modal h2").html(title);
			$("#modal div div").append(content);
			$("#modal > div").addClass(modalclass);

			$w.resize();

			// Ignore default event
			return false;		
		}

		function modalClose() {
			$("#modal").removeClass("open").hide();
			$("#modal > div h2, #modal > div div").empty();
			$("#modal > div").removeClass();
			$("body").removeClass("blur");
			return false;

		}

		// Empty model box content upon closing
		$("a#close").click(function(e){
			e.preventDefault();
			modalClose();
		});
		$(document).keyup(function(e) { 
			if (e.keyCode == 27) {
				modalClose();
			} 
		});

		// Model box: Fetch content
		$("[data-modal]").on("click", function(){
			modal($(this).attr('title'), "<p>"+$(this).attr('data-modal-content')+"</p>", "");
		});

	// Finally, fire off resize events for all dynamically-sized elements
	$w.resize(function() {

		// Set height of world map
		var $map = $("#world-map"),
			$map_h = $map.data("aspect-ratio")*$map.width();
		if($w.height() > 500) {
			$map.height(Math.min($map_h,$w.height()-$("#order-stats").height()-48));
		} else {
			$map.height(Math.max($map_h,$w.height()));
		}

		// Set width for header, card and spacer - do first because card height is dependent on width
		$("#sticky-heading, .order-card").width($(".table").width());

		// Uniform height for order cards
		$(".order-card").each(function(i) {
			$(this).children("span").css({"height":"auto"});
			var card_heights = $(this).children("span").map(function() {
				return $(this).height();
			});
			var h = Math.max.apply(null, card_heights);
			$(this).children("span").height(h);
			$(this).siblings(".card-spacer").height(h);
		});

		// Reposition modal box
		var $mw = $("#modal > div"),
			mt = 0.5*($w.height() - $mw.outerHeight());
		$mw.css({
			"margin-top": mt
		});

	}).resize();

});