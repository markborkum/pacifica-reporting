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
