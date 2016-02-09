var get_transaction_info = function(el,transaction_list){
  var el = $(el);
  var url = base_url + 'index.php/reporting/get_transaction_list_details';
  var posting = $.post(url, JSON.stringify(transaction_list), function(data){
    var details_container = el.parents('.object_body').find('.transaction_details_container');
    details_container.html(data);
    details_container.show();
  });
  posting.done(function(){

  });
};
