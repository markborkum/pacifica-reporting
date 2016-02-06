var get_transaction_info = function(el,transaction_list){
  var el = $(el);
  var url = base_url + 'index.php/reporting/get_transaction_info';
  var posting = $.post(url, transaction_list, function(data){
    var details_container = el.parents('.object_body').find('.transaction_')
  });
  posting.done(function(){

  });
};
