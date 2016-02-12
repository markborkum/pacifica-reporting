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
    alert(data);
  });
}


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
});
