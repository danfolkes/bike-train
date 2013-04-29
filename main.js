	var slidertimeselector = "#slider-time";
	var map;
	var marker;
	var directionDisplay;
	var directionsService = new google.maps.DirectionsService();

	var richmond = new google.maps.LatLng(37.557370, -77.488117);
	var richmond2 = new google.maps.LatLng(37.540725 , -77.436);
	var richmond3 = new google.maps.LatLng(37.540 , -77.4360);
	var line;
	var poly;
	var polys = [];
	var markers1 = [];
	var markers2 = [];
	var strokeColorCounter = 0; 
	var strokeColors = [];
	strokeColors.push("RGB(106,74,60)");
	strokeColors.push("RGB(0,160,176)");
	strokeColors.push("RGB(204,51,63)");
	strokeColors.push("RGB(237,201,81)");
	strokeColors.push("RGB(235,104,65)");
	
	//strokeColors = [];
	strokeColors.push("RGB(78,205,196)");
	strokeColors.push("RGB(199,244,100)");
	strokeColors.push("RGB(255,107,107)");
	strokeColors.push("RGB(85,98,112)");
	strokeColors.push("RGB(196,77,88)");
	
	var service = new google.maps.DirectionsService();
	var lsExtradata = $.jStorage.get('extradata');
	var badRecurseStopper = 0;
	if (extradata.length > 0 && typeof lsExtradata != 'undefined' && lsExtradata!==null && extradata.length == lsExtradata.length) {
		//alert('loading from lsExtradata');
		extradata = lsExtradata;
	}
	
	var Bike1 = {
	  path: 'M 95,312.5 L 160,107.5 H 217.5, M 272.5,150 H 360, M 140,172.5 H 325 L 405,312.5 H 272.5 L 132.5,210, M 325,172.5 L 272.5,312.5 V 360 H 305'+',M 90 240 a 70 70 0 1 0 0.0001 0'+',M 390 240 a 70 70 0 1 0 0.0001 0',
	  fillOpacity: 0,
	  scale: .06,
	  strokeColor: "blue",
	  strokeWeight: 2,
	  rotation:90,
	  anchor:new google.maps.Point(0, 400)
	};
	var Bike2 = $().extend({}, Bike1); //shallow clone
	Bike2.strokeColor = "red";
	Bike2.rotation =-90;
	Bike2.anchor = new google.maps.Point(0, 400);
	
	var Arrow1 = {
	  path: google.maps.SymbolPath.FORWARD_OPEN_ARROW,
	  strokeColor: "blue",
	  scale: 2.5
	};
	var Arrow2 = {
	  path: google.maps.SymbolPath.BACKWARD_OPEN_ARROW,
	  strokeColor: "red",
	  scale: 2.5
	};
	
	
	var infowindow1 = new google.maps.InfoWindow({
		content: 'sss'
	});
	  
	  
	  function recurseRouter(i) {
		badRecurseStopper++;
		if (badRecurseStopper > 100)
		{
			alert(badRecurseStopper);
			return;
		}
		if (i >= requests.length) {
			$(".loadingmap").hide();
			//save requests, extradata
			$.jStorage.set('extradata', extradata);
			//alert("saving routes to localStorage");
			return;
		}
		else {
			//alert(i);
		}
		
		service.route(requests[i], function(result, status) {
			
			extradata[i].routeresult = result;
			extradata[i].routestatus = status;
			//alert(extradata[arrayiterator].id);
			//alert(requests[arrayiterator].origin);
			
			if (extradata[i].routestatus == google.maps.DirectionsStatus.OK) {
				serviceroute(extradata[i].routeresult, extradata[i].routestatus);
				i++;
				recurseRouter(i);
			} else {
				//failed, try again.
				setTimeout(function() {
					  recurseRouter(i);
				}, 250);
				
			}
		});
	  }
	  
	  
      function initialize() {
        var mapOptions = {
          zoom: 13,
          center: richmond,
          mapTypeId: google.maps.MapTypeId.TERRAIN 
        };
        map = new google.maps.Map(document.getElementById('map_canvas'), mapOptions);
		var kmlLayerOptions = {map:map, preserveViewport:true, suppressInfoWindows: false}; //map:display kml layer on created map object called "map"; 
		
		var allcomplete = false;
		var allcompletecount = 0;
		
		
		var extradataloaded = false;
		$.each(extradata,  function() {
			if (this.routestatus == google.maps.DirectionsStatus.OK) {
				extradataloaded = true;
			} else {
				extradataloaded = false
				return false;
			}
		});
		if (extradataloaded) {
			for (var extradataloaded_i = 0; extradataloaded_i < requests.length; extradataloaded_i++) {
				serviceroute(extradata[extradataloaded_i].routeresult, extradata[extradataloaded_i].routestatus);
			}
			$(".loadingmap").hide();
		} else {
			recurseRouter(0);
		}
      }
      google.maps.event.addDomListener(window, 'load', initialize);
      function getStrokeColor() {
			strokeColorCounter++;
			if (strokeColorCounter > strokeColors.length-1)
				strokeColorCounter = 0;
			
			return strokeColors[strokeColorCounter];
	  }
	  function serviceroute(result, status) {
			overviewpath = result.routes[0].overview_path;
			var lls = [];
			for (var t=0; t < overviewpath.length; t++) {
				lls.push(getLatLangFromOverViewPath(overviewpath, t));
				//if ((overviewpath[t].mb == null)||(overviewpath[t].nb == null))
				//	lls.push(new google.maps.LatLng(overviewpath[t].kb, overviewpath[t].lb));
				//else
				//	lls.push(new google.maps.LatLng(overviewpath[t].mb, overviewpath[t].nb));
			}
			polys.push(new google.maps.Polyline({
				path: lls,
				icons: [{
				  icon: Bike1,
				  offset: '100%'
				},{
				  icon: Bike2,
				  offset: '100%'
				},{
				  icon: Arrow1,
				  offset: '10%',
				  /*repeat: '26%'*/
				},{
				  icon: Arrow2,
				  offset: '90%',
				  /*repeat: '36%'*/
				}],
				map: map,
				title:''+arrayiterator,
				strokeWeight:7,
				strokeOpacity:1,
				strokeColor:getStrokeColor()
			}));
			tmppoly = polys[polys.length-1];
			
			google.maps.event.addListener(tmppoly, 'click', function(event) {
				MapItemClick(this.title);
			});
			google.maps.event.addListener(tmppoly, 'mouseover', function() {
				MapItemHover(this.title);
				
			});
			google.maps.event.addListener(tmppoly, 'mouseout', function() {
				MapItemHoverOut(this.title);
				
			});
			/*markers1.push(new google.maps.Marker({
				  position: lls[0],
				  map: map,
				  title:''+arrayiterator,
				  icon: 'http://www.google.com/intl/en_us/mapfiles/ms/micons/blue-dot.png'
			}));

			markers2.push(new google.maps.Marker({
				  position: lls[overviewpath.length - 1],
				  map: map,
				  title:''+arrayiterator,
				  icon: {
					path: google.maps.SymbolPath.CIRCLE,
					scale: 6,
					strokeColor: "red",
					fillOpacity:0.25,
					strokeOpacity:0.25
				  }
			}));
			
			marker1 = markers1[markers1.length-1];
			marker2 = markers2[markers2.length-1];
			google.maps.event.addListener(marker1, 'click', function() {
				MapItemClick(this.title);
			});
			google.maps.event.addListener(marker2, 'click', function() {
				MapItemClick(this.title);
			});*/
			arrayiterator++;
		
		
	  }
	  
	  function MapItemClick(i) {
		$(".routeselector").effect("highlight", {}, 1000);
		$('#routeselector_id'+extradata[i].id+'').prop('checked', true).focus();
	  }
	  function MapItemHover(i) {
		polys[i].setOptions({
			strokeOpacity : .5
		});
		/*markers1[i].icon.setOptions({
			strokeOpacity : .25,
			scale:16
		});
		/*markers2[i].icon.setOptions({
			strokeOpacity : .25,
			scale:16
		});*/
	  }
	  function MapItemHoverOut(i) {
		polys[i].setOptions({
			strokeOpacity:1
		});
		/*markers1[i].setOptions({
			strokeOpacity : 0.75,
			scale:6
		});
		markers2[i].setOptions({
			strokeOpacity : 0.75,
			scale:6
		});*/
	  }
	  $(function() {
		var count = 0;
		//$( "#train_direction_radios_div" ).buttonset();
		
		$( "#buttonSlideDown" ).click(function( event ) {
			event.preventDefault();
			$('#chkAutoTime').attr('checked', false);
			//$( slidertimeselector ).slider( "option", "value", $( slidertimeselector ).slider( "option", "value") -60 );
			$( slidertimeselector ).val($( slidertimeselector ).val() -60);
		});
		$( "#buttonSlideUp" ).click(function( event ) {
			event.preventDefault();
			$('#chkAutoTime').attr('checked', false);
			//$( slidertimeselector ).slider( "option", "value", $( slidertimeselector ).slider( "option", "value") +60 );
			$( slidertimeselector ).val($( slidertimeselector ).val() +60);
		});
		
		/*$("#train_time").timepicker({
		  minTime: "12:00am",  // Using string. Can take string or Date object.
		  maxTime: "12:00am",  // Using Date object.
		  show24Hours: false,
		  separator:':',
		  step: 15,
		  scrollDefaultNow: true,
		  timeFormat: 'H:i',
		  forceRoundTime: true
		  
		});*/
		function ChangeSlide(ui) {
			var now = new Date();
			//var d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(ui.value / 60 / 60), (ui.value / 60), (ui.value % 60), 0);

			
			var d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), parseInt(ui.value / 60 / 60), ((ui.value/60)%60), (ui.value%60), 0);
			$( "#time" ).val(d.toTimeString());
			
			offset = 0;
			count = ui.value % 900;
			if (count == 0)
				offset = 0;
			else
				offset = offset + ((100 / 900) * count)
			for (var i = 0; i < polys.length; i++) {
				var icons = polys[i].get('icons');
				icons[0].offset = offset + '%';
				icons[1].offset = 100 - offset + '%';
				polys[i].set('icons', icons);
			}
		}
		
		$( "#slider-time" ).slider({
		  value:0,
		  min: 0,
		  max: 86400,
		  step: 1,
		  change: function( event, ui ) {ChangeSlide(ui);
		  },
		  slide: function( event, ui ) {
			ChangeSlide(ui);
			$('#chkAutoTime').attr('checked', false);
		  }
		});
		//$( "#time" ).val( "" + $( "#slider-time" ).slider( "value" ) );
		$( "#time" ).val( "" + $( "#slider-time" ).val() );
		var accordionicons = {
		  header: "ui-icon-circle-arrow-e",
		  activeHeader: "ui-icon-circle-arrow-s"
		};
		/*$( ".accordion" ).accordion({
			icons: accordionicons,
			heightStyle: "content",
			collapsible: true,
			active: false
		});*/
		
		//$( ".tt" ).tooltip();
		
		
		var handlechkAutoTime;
		function AddhandlechkAutoTime() {
			handlechkAutoTime = setInterval(function() {
				if ($('#chkAutoTime').is(':checked')) {
					
					var step = $( slidertimeselector ).slider( "option", "step" );
					var min = $( slidertimeselector ).slider( "option", "min" );
					var max = $( slidertimeselector ).slider( "option", "max" );
					var now = new Date();
					var value = (now.getHours()*60*60) + (now.getMinutes()*60) + (now.getSeconds()) ;
					
					if (value >= max)
						value = min;
						
					//$( slidertimeselector ).slider( "option", "value", value );
					$( slidertimeselector ).val( value );
					//alert(value);
					
				}
			}, 500);
		}

		$('#chkAutoTime').change(function() {
			if ($(this).is(':checked')) {
				AddhandlechkAutoTime();
			}
			else
			{
				clearInterval(handlechkAutoTime);
			}
		});
		var now = new Date();
		//$( "#slider-time" ).slider("option", "value", (now.getHours()*60*60) + (now.getMinutes()*60) + (now.getSeconds()) );
		$( "#slider-time" ).val((now.getHours()*60*60) + (now.getMinutes()*60) + (now.getSeconds()) );
		
		$('#chkAutoTime').attr('checked', true);
		AddhandlechkAutoTime();
		//onResize();
	});
	function onResize() {
		if ($(window).width() > 800)
			$("html").attr("class","america");
		else if ($(window).width() < 500)
			$("html").attr("class","skinny");
		else
			$("html").attr("class","fat");
	}
	/*$(window).resize(function() {
		onResize();
	});*/
	function getLatLangFromOverViewPath(overviewpath, t) {
		//new google.maps.LatLng(overviewpath[t].mb, overviewpath[t].nb
		if ((overviewpath[t].mb == null)||(overviewpath[t].nb == null))
			return new google.maps.LatLng(overviewpath[t].kb, overviewpath[t].lb);
		else
			return new google.maps.LatLng(overviewpath[t].mb, overviewpath[t].nb);
	}
	function updateform(section) {
		var rid = $('.JoinTheTrain input[type="radio"][name="routeselector"]:checked').val();
		var direction = $('.JoinTheTrain input[type="radio"][name="train_direction_radios"]:checked').val();
		var time = $('.JoinTheTrain input[type="radio"][name="train_time"]:checked').val();
		
		if (section == "routeselector") {
			direction = null;
			time = null;
		} else if (section == "direction") {
			time = null;
		}
			
		
		if (rid > 0) {
			//View on map:
			$.each(extradata,  function( key, value ) {
				rid = $('.JoinTheTrain input[type="radio"][name="routeselector"]:checked').val();
				if (this.id == rid) {
					overviewpath = extradata[key].routeresult.routes[0].overview_path;
					var bounds = new google.maps.LatLngBounds();
					for (var t=0; t < overviewpath.length; t++) {
						bounds.extend(getLatLangFromOverViewPath(overviewpath, t));
						//bounds.extend(new google.maps.LatLng(overviewpath[t].mb, overviewpath[t].nb));
					}
					map.fitBounds(bounds);
				} 
			});
			// route selected
			if ((direction == 0)||(direction == 1)) {
				//starting postion selected
				
				if (time > 0) {
					//alert(time);
					//time selected
					//load day counts
					//SELECT *, COUNT(0) FROM UserRoute WHERE time=830 and ',' || days like '%,m%'
					
					$.each( ['su','m','t','w','th','f','sa'], function(i, l){
						
						$.ajax({
							url: "serv.php",
							dataType:"json",
							type: "POST",
							cache: false,
							data: { gettable: "UserRoute", 
								where: "routeid = " + rid + " AND startposition = " + direction + " and time=\'" + time + "\' and (',' || days) like '%," + l + "%'",
								addcount: true
								},
							success : function(data, statusText){
								$.each(data,  function() {
									$(".train_days_count span.train_days_" + l + "").html("(" + this.Count + ")");
								});
							},
							error: function (request, status, error) {
								//$(".train_days_count span.train_days_" + l + "").html("(0)");
							}
						});
					});
					
					//show days
				} else {
					//alert(time);
					//load time counts
					
					$.ajax({
						url: "serv.php",
						dataType:"json",
						type: "POST",
						cache: false,
						data: { gettable: "UserRoute", 
							where: "routeid = " + rid + " AND startposition = " + direction,
							addcount: true, DaysCount: true
							},
							success : function(data, statusText){
								$.each(data,  function() {
									$(".train_time_div label[for='train_time_" + this['time'] + "'] .ui-btn-inner .ui-btn-text ").append("(" + this.DaysCount + ")");
								});
							},
							error: function (request, status, error) {
								$(".train_time_div label[for='train_time_15']").append("(0)");
							}
					});
				}
			} else {
				$.ajax({
					  url: "serv.php",
					  dataType:"json",
					  type: "POST",
					  cache: false,
					  data: { gettable: "Waypoint", where: "routeid = " + rid + " AND position <= 1" },
					  success : function(data, statusText) {
						$("#train_direction_radios_div [for='train_direction_radios_blue'] .ui-btn-inner  .ui-btn-text").html(data[0].name);
						updatetrain_direction_radios_div_count(0);
						updatetrain_direction_radios_div_count(1);
						function updatetrain_direction_radios_div_count(startposition) {
							$.ajax({
								url: "serv.php",
								dataType:"json",
								type: "POST",
								cache: false,
								data: { gettable: "UserRoute", 
									where: "routeid = " + rid + " AND startposition = "+ startposition,
									addcount: true, DaysCount: true
									},
									error: function (request, status, error) {
										if (startposition==0) $("#train_direction_radios_div [for='train_direction_radios_blue'] .ui-btn-inner .ui-btn-text").append("(0)");
										else if (startposition==1) $("#train_direction_radios_div [for='train_direction_radios_red'] .ui-btn-inner .ui-btn-text").append("(0)");
									},
									success : function(data, statusText){
										//$("#results").append(html);
										//alert(data[0].name);
										if (startposition==0) $("#train_direction_radios_div [for='train_direction_radios_blue'] .ui-btn-inner .ui-btn-text").append("(" + (parseInt(data[0].DaysCount) || 0) + ")");
										else if (startposition==1) $("#train_direction_radios_div [for='train_direction_radios_red'] .ui-btn-inner .ui-btn-text").append("(" + (parseInt(data[0].DaysCount) || 0) + ")");
									}
							});
						}
						$("#train_direction_radios_div [for='train_direction_radios_red'] .ui-btn-text").html(data[1].name);
					}
				});
			}
		} else {
			//alert("#1 please select a route");
		}
	}	
