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
      fontSize: '12px'
    },
    type: 'pie'
  },
  title: {
    style: {'fontSize':'13px', 'fontWeight':'bold'}
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
      center: [100, 80],
      showInLegend: true
    }
  },
  series: [{
    name: 'Uploads',
    animation: false,
  }]
};



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


var submit_object_change = function(el, object_type, object_id, action){
  //action is add or remove
  var update_list = JSON.stringify([{ 'object_id' : object_id, 'action' : action }]);
  var url = base_url + 'index.php/reporting/update_object_preferences/' + object_type;
  $.post(url, update_list, function(data){
    //need to update the DOM to add/remove the appropriate object
    if(action == 'remove'){
      if(el.is('input[type="checkbox"]')){
        my_el = $('#remove_icon_' + object_id);
        remove_empty_uls();
      }else{
        my_el = el;
        el = $('#' + object_type + '_id_' + object_id);
      }
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
  $('.object_selection_checkbox').click(function(el){
    var el = $(el.target);
    var id = el.attr('id');
    var object_id = parseInt(id.substr(id.lastIndexOf("_")+1),10);
    var action = el.is(":checked") ? 'add' : 'remove';
    submit_object_change(el, object_type, object_id, action);
  });
}

var options = {
  callback: function (value) {
    $('#search_done_button').enable();
    get_search_results($(this),value);
  },
  wait: 500,
  highlight: true,
  captureLength: 3
}

var clear_results = function(){
  $('#search_results_display').slideUp('fast');
  $('#search_results_display').html('');
};


$(function(){
  $('#object_search_box').keyup(function(){
    var el = $(this);
    var cfi = el.siblings('.clear_field_icon');
    if(el.val().length !== 0){
      cfi.fadeIn('fast');
    }else{
      cfi.fadeOut('fast');
    }
  });
  $('#clear_field_icon').click(function(){
    var input_field = $(this).siblings('.object_search_box');
    input_field.val("");
    $(this).fadeOut('fast');
    clear_results();
  });
  $('#object_search_box').typeWatch(options);
  $('#search_done_button').click(function(){
    var input_field = $('#object_search_box');
    input_field.val("");
    input_field.siblings('.clear_field_icon').fadeOut('fast');
    clear_results();
    $(this).disable();
  });
});
