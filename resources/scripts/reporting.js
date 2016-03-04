var hc_pie_options = {
  credits: false,
  chart: {
    animation: false,
    plotBackgroundColor: null,
    plotBorderWidth: null,
    plotShadow: false,
    spacing: [10,0,0,0],
    style: {
      fontFamily: 'Helvetica, Arial, sans-serif',
      fontSize: '10px'
    },
    type: 'pie'
  },
  title: {
    style: {'fontSize':'13px', 'fontWeight':'bold'},
    x:-30
  },
  legend: {
    layout: 'vertical',
    align:'right',
    margin:'2'
  },
  tooltip: {
    headerFormat: '<span style="font-size: 10px">{series.name}</span><br/>'
  },
  plotOptions: {
    pie: {
      allowPointSelect: true,
      cursor: 'pointer',
      dataLabels: {
        enabled: false,
        floating:true
      },
      showInLegend: true
    }
  },
  series: [{
    name: 'Uploads',
    animation: false,
  }]
};

var hc_timeline_options = {
  credits: false,
  chart: {
    animation: {
      duration: 250
    },
    height: 250,
    style: {
      fontFamily: '"Helvetica Neue", Helvetica, Arial, sans-serif',
      fontSize: '12px'
    },
    zoomType: 'x',
    type: 'column'
  },
  title: {
    text: ''
  },
  legend: {
    enabled: false
  },
  tooltip: {
    pointFormat: '<strong>{point.name} {point.y}{valueSuffix}</strong>',
    headerFormat: '<span style="font-size: 10px">{series.name}</span><br/>'
  },
  plotOptions: {
    series: {
      marker: {
        enabled: false
      },
      animation: {
        duration: 250
      }
    }
  },
  xAxis: {
    type: 'datetime',
    labels: {
      formatter: function() {
        return moment(this.value).format("ddd M/D/YYYY");
      },
      rotation: -45
    },
    // tickInterval: 24 * 3600 * 1000,
    tickPixelInterval: 30
  },
  yAxis: [{ //transaction count axis
    title: {
      text: 'Number of Uploads',
      style: {
        color: Highcharts.getOptions().colors[1]
      }
    },
    labels: {
      style: {
        color: Highcharts.getOptions().colors[1]
      }
    }
  },{ //volume axis
    title: {
      text: 'File Volume (MB)',
      style: {
        color: Highcharts.getOptions().colors[0]
      }
    },
    labels: {
      formatter: function() {
        return humanFileSize(this.value);
      },
      style: {
        color: Highcharts.getOptions().colors[0]
      }
    },
    opposite:true
  }]
};

// var datepicker = $.fn.datepicker.noConflict();
// $.fn.bootstrapDP = datepicker;

var get_transaction_info = function(el,transaction_list){
  var el = $(el);
  var parent_container = el.parents('.object_body');
  var details_container = parent_container.find('.transaction_details_container');
  var notifier_container = parent_container.find('.transaction_details_notifier');
  var disclosure_arrow = notifier_container.find('.disclosure_arrows');
  var load_indicator = parent_container.find('.transaction_details_loader');
  if(details_container.html().length == 0){ //empty details, needs loading
    var url = base_url + 'index.php/reporting/get_transaction_list_details';
    load_indicator.spin({scale:0.5, left:'20%', width:4, lines:11})
    load_indicator.fadeIn();
    var posting = $.post(url, JSON.stringify(transaction_list), function(data){
      details_container.html(data);
      disclosure_arrow.removeClass('dc_up').addClass('dc_down');
      load_indicator.fadeOut();
      details_container.show();
    });
    posting.done(function(){

    });
  }else if(disclosure_arrow.hasClass('dc_up') && details_container.html().length > 0){
    //filled, but hidden so just show
    disclosure_arrow.removeClass('dc_up').addClass('dc_down');
    details_container.show();
  }else{
    disclosure_arrow.removeClass('dc_down').addClass('dc_up');
    details_container.hide();
  }
};

var submit_group_change_worker = function(el, object_type, object_id, action){
  var group_id = parseInt(el.parents('.reporting_object_container').find('.group_search_form .group_id').val(),10);
  var current_search_string = el.parents('.group_edit_section').find('.object_search_box').val();
  var update_list = JSON.stringify(
    [
      { 'object_id' : object_id,
        'group_id' : group_id,
        'action' : action,
        'current_search_string' : current_search_string
      }
    ]);
  var url = base_url + 'index.php/reporting/update_object_preferences/' + object_type + '/' + group_id;
  var poster = $.post(url,update_list, function(data){
    if(data){
      el.parents('.search_results_display').html(data);
    }

  });
}


var submit_object_change_worker = function(el, object_type, object_id, action){
  var group_id = parseInt(el.parents('.reporting_object_container').find('.group_search_form .group_id').val(),10);
  debugger;
  var current_search_string = el.parents('.group_edit_section').find('.object_search_box').val();
  var update_list = JSON.stringify(
    [
      { 'object_id' : object_id,
        'group_id' : group_id,
        'action' : action,
        'current_search_string' : current_search_string
      }
    ]);
  var url = base_url + 'index.php/reporting/update_object_preferences/' + object_type + '/' + group_id;

  $.post(url, update_list, function(data){
    //need to update the DOM to add/remove the appropriate object
    if(action == 'remove'){
      my_el.parents('.reporting_object_container').remove();
      var my_list_item = el.parents('li').detach();
      remove_empty_uls();
    }else if(action == 'add'){
      if($('.reporting_object_container').length == 0){
        $('#search_results_display').after('<div class="reporting_object_container"></div>');
      }
      $('.info_container').fadeOut();
      var first_container = $('.reporting_object_container')[0];
      var container_url = base_url + 'index.php/reporting/get_object_container/';
      container_url += object_type + '/' + object_id + '/' + time_range;
      var posting = $.get(container_url, function(data){
        //add in the new container
        $(first_container).before(data);
        if(el.is('input[type="checkbox"]')){
          var list_item = el.parents('li').detach();
          if($('#' + object_type + "_my_" + object_type + "s_search_results").length == 0){
            var new_element = $('<ul id="' + object_type + '_my_' + object_type + 's_search_results" class="search_results_list"></ul>');
            $('#search_results_display > ul').before(new_element);
            new_element = $('#' + object_type + '_my_' + object_type + 's_search_results');
            new_element.append('<div class="search_results_header">my ' + object_type + 's</div>');
          }
          // debugger;
          $('#' + object_type + "_my_" + object_type + "s_search_results").append(list_item);
          remove_empty_uls();
        }

      })
      .fail(function(){
        location.reload();
      });
    }
  });
}

var humanFileSize = function(size) {
    if (size == 0) { return "0"; }
    var i = Math.floor( Math.log(size) / Math.log(1024) );
    return ( size / Math.pow(1000, i) ).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
};


var timeline_load_new_data_check = function(timeline_obj, new_start, new_end){
  // debugger;
  var chart = timeline_obj;
  var x_extremes = chart.xAxis[0].getExtremes();
  if(new_end > x_extremes.dataMax || new_start < x_extremes.dataMin){
    //we're outside of our loaded data, need to request more
    return true;
  }else{
    //still inside our current bounds, just zoom
    return false;
  }
};

var load_new_timeline_data = function(timeline_obj, object_type, object_id, start_date, end_date){
  var url = base_url + "index.php/reporting/get_timeline_data/" + object_type + "/" + object_id + "/";
  url += start_date + "/" + end_date;
  var fv_data = timeline_obj.series[0];
  var tx_data = timeline_obj.series[1];

  var getter = $.get(url, function(data){
    fv_data.setData(data.file_volumes,false);
    tx_data.setData(data.transaction_counts,false);
    timeline_obj.redraw();
  });
};

var load_new_group_timeline_data = function(timeline_obj, object_type, group_id, start_date, end_date){
  var url = base_url + "index.php/reporting/get_group_timeline_data/" + object_type + "/" + group_id + "/";
  url += start_date + "/" + end_date;
  var fv_data = timeline_obj.series[0];
  var tx_data = timeline_obj.series[1];
  $('#loading_blocker_' + group_id).spin().show();
  var getter = $.get(url, function(data){
    $('#loading_blocker_' + group_id).spin(false).hide();
    fv_data.setData(data.file_volumes,false);
    tx_data.setData(data.transaction_counts,false);
    timeline_obj.redraw();
  });
};

var submit_group_change = function(el, object_type, object_id, action){
  submit_group_change_worker(el, object_type, object_id, action)
}

var submit_object_change = function(el, object_type, object_id, action){
  //action is add or remove
  if(el.is('input[type="checkbox"]')){
    my_el = $('#remove_icon_' + object_id);
    remove_empty_uls();
  }else{
    my_el = el;
    el = $('#' + object_type + '_id_' + object_id);
  }
  if(action == 'remove'){
    var d_box = $(my_el).siblings('.remove_confirmation_dialog');
    var message = $(my_el).siblings('.remove_dialog_message').text();
    var object_id = $(my_el).siblings('.container_id').text();
    d_box.html(message);
    d_box.dialog({
      resizable:false,
      modal:true,
      title: 'Remove ' + object_type + ' ID ' + object_id,
      width:250,
      buttons: {
        "Yes" : function(){
          submit_object_change_worker(el, object_type,object_id,action);
          $(this).dialog('close');
        },
        "No" : function(){
          $(this).dialog('close');
        }
      }
    });
  }else{
    submit_object_change_worker(el, object_type,object_id,action);
  }






}

var remove_empty_uls = function(){
  $('#search_results_display').find('ul').each(function(index,item){
    item = $(item);
    if(item.find('li').length === 0){
      item.remove();
    }
  });
};

var load_results = function(object_type, object_id){
  $('#loading_status_' + object_id).spin();
  var url = base_url + 'index.php/reporting/get_reporting_info/' + object_type + '/' + object_id + '/' + time_range;
  var getter = $.get(url);
  getter.done(function(data,status){
    $('#loading_status_' + object_id).spin(false);

    $('#object_body_container_' + object_id).replaceWith(data);
  });
};


var load_group_results = function(object_type, group_id, item_list){
  $('#loading_status_' + group_id).spin();
  var url = base_url + 'index.php/reporting/get_reporting_info_list/' + object_type + '/' + group_id + '/' + time_range;
  var getter = $.get(url);
  getter.done(function(data,status){
    $('#loading_status_' + group_id).spin(false);
    $('#object_body_container_' + group_id).replaceWith(data);
  });
};


var get_search_results = function(el, filter_text){
  if(filter_text.length > 0){
    var url = base_url + 'index.php/reporting/get_object_lookup/' + object_type + '/' + filter_text;
    $.get(url, function(data){
      $('#search_results_display').html(data);
      $('#search_results_display').slideDown();
    });
  }else{
    clear_results();
  }
};


var setup_search_checkboxes = function(){
  $('.object_selection_checkbox').click(function(event){
    var el = $(event.target);
    var id = el.attr('id');
    var object_id = parseInt(id.substr(id.lastIndexOf("_")+1),10);
    var action = el.is(":checked") ? 'add' : 'remove';
    submit_group_change(el, object_type, object_id, action);
  });
}

var options = {
  callback: function (value) {
    // $('#search_done_button').enable();
    get_group_objects($(this),value);
  },
  wait: 500,
  highlight: true,
  captureLength: 3
}

var clear_results = function(){
  // $('#search_results_display').slideUp('fast');
  // $('#search_results_display').html('');
};

var setup_confirmation_dialog_boxes = function(e){
};

var submit_group_name_change = function(group_name, group_id, input_el){
  var url = base_url + 'index.php/reporting/change_group_name/' + group_id + '/';
  var update_list = {group_name : group_name}
  var posting = $.post(url, JSON.stringify(update_list), function(data){
    var new_input_field = $('<input/>',{
      type:'text',
      class:'group_name_editor',
      id:'group_name_editor_' + data.group_id,
      name:'group_name_editor_' + data.group_id,
      placeholder: data.group_name
    });
    input_el.siblings('.group_edit_confirm_buttons').hide();
    input_el.replaceWith(new_input_field);
    $('span.displayed_group_name').html(data.group_name);
  });
};

var get_group_objects = function(el,filter_text){
  filter_text = filter_text != null ? filter_text : "";
  var edit_el = el.parents('.reporting_object_container').find('.group_edit_section');
  var group_id = edit_el.find('.group_search_form input.group_id').val();
  var object_type = edit_el.find('.group_search_form input.object_type').val();
  var dl_url = base_url + 'index.php/reporting/get_object_group_lookup/' + object_type + '/' + group_id;
  var results_container = edit_el.find('.search_results_display');
  if(el.hasClass('edit_grouping_button')){
    if(!edit_el.hasClass('closed')){
      edit_el.slideUp(function(){
        edit_el.addClass('closed');
      });
    }else{
      $.get(dl_url, function(data){
        results_container.html(data);
        edit_el.slideDown(function(){
          edit_el.removeClass('closed');
        });
      });
    }
  }

  if(el.hasClass('object_search_box')){
    dl_url += '/' + filter_text;
    $.get(dl_url, function(data){
      results_container.html(data);
    });
  }
};


$(function(){
  // Make monochrome colors and set them as default for all pies
  Highcharts.getOptions().plotOptions.pie.colors = (function () {
      var colors = [],
          // base = Highcharts.getOptions().colors[2],
          base = '#81aa00',
          i;
      for (i = 0; i < 10; i += 1) {
          // Start out with a darkened base color (negative brighten), and end
          // up with a much brighter color
          colors.push(Highcharts.Color(base).brighten((i - 3) / 7).get());
      }
      return colors;
  }());

  Highcharts.getOptions().plotOptions.spline.colors = (function () {
      var colors = [],
          // base = Highcharts.getOptions().colors[2],
          base = '#81aa00',
          i;
      for (i = 0; i < 10; i += 1) {
          // Start out with a darkened base color (negative brighten), and end
          // up with a much brighter color
          colors.push(Highcharts.Color(base).brighten((i - 3) / 7).get());
      }
      return colors;
  }());

  Highcharts.setOptions({
    lang: {
      thousandsSep: ""
    },
    global: {
      useUTC: false
    }
  });

  // $('.time_range_container').bootstrapDP({ format: 'mm/dd/yyyy' })
  // $('.input-daterange').bootstrapDP();
  $('.object_search_box').keyup(function(){
    var el = $(this);
    var cfi = el.siblings('.clear_field_icon');
    if(el.val().length !== 0){
      cfi.fadeIn('fast');
    }else{
      cfi.fadeOut('fast');
    }
  });
  $('.clear_field_icon').click(function(){
    var input_field = $(this).siblings('.object_search_box');
    input_field.val("");
    $(this).fadeOut('fast');
    clear_results();
  });
  $('.object_search_box').typeWatch(options);
  // $('.search_done_button').click(function(){
  //   var input_field = $('#object_search_box');
  //   input_field.val("");
  //   input_field.siblings('.clear_field_icon').fadeOut('fast');
  //   clear_results();
  //   $(this).disable();
  // });
  // $('.remove_icon').mouseover(function(event){
  //   $(event.target).siblings('.remove_message').fadeIn('fast');
  //
  // });
  // $('.remove_icon').mouseout(function(event) {
  //   $(event.target).siblings('.remove_message').fadeOut('fast');
  // });
  $('.edit_grouping_button').click(function(event){
    var el = $(event.target);
    get_group_objects(el)
  });
  $('.group_name_editor').keyup(function(event){
    var el = $(event.target);
    var button_container = el.siblings('.group_edit_confirm_buttons');
    if(button_container.is(":hidden") && el.val().length > 0){
      button_container.fadeIn();
    }else if(el.val().length == 0){
      button_container.hide();
    }
  });

  $('.group_edit_confirm_buttons .change_icon_accept_reject').click(function(event){
    var el = $(event.target);
    var my_field = el.parents('.group_name_edit_container').find('input[type="text"]');
    var my_group_id = parseInt(el.parents('.group_search_bar_container').find('input.group_id').val(),10);
    if(el.hasClass('accept')){
      submit_group_name_change(my_field.val(), my_group_id, my_field);
    }
    if(el.hasClass('reject')){
      my_field.val('');
      my_field.siblings('.group_edit_confirm_buttons').fadeOut('fast');
    }
  });


  $('.disclosure_triangle').click(function(event){
    var el = $(event.target);
    var current_state = el.hasClass('opened') ? 'open' : 'closed';
    var closeable = el.parents('.reporting_object_container').find('.object_closeable');
    var header_block = el.parents('.object_header');
    if(current_state == 'open'){
      closeable.slideUp(250);
      el.removeClass('opened').addClass('closed');
      header_block.addClass('closed');
    }else{
      closeable.slideDown(250);
      el.removeClass('closed').addClass('opened');
      header_block.removeClass('closed');
    }
  });

});
