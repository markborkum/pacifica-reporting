$(function(){
    //setup the date picker
    generate_year_select_options(
        $('#tp_year_selector'), earliest_available, latest_available,
        parseInt(Cookies.get('selected_year'), 10));
    generate_month_select_options(
        $('#tp_month_selector'), earliest_available, latest_available,
        parseInt(Cookies.get('selected_year'), 10), parseInt(Cookies.get('selected_month'), 10));

    $('#tp_year_selector').select2({minimumResultsForSearch: -1});
    $('#tp_month_selector').select2({minimumResultsForSearch: -1});

    $('#tp_year_selector').on('change', function(event){
        var el = $(event.target);
        Cookies.set('selected_year', el.val());
        generate_month_select_options(
            $('#tp_month_selector'), earliest_available, latest_available,
            parseInt(Cookies.get('selected_year'), 10), parseInt(Cookies.get('selected_month'), 10));
    });
    $('#tp_month_selector').on('change', function(event){
        var el = $(event.target);
        Cookies.set('selected_month', el.val());
    })

    $('#generate_report_button').click(function(event){
        load_compliance_report(
            $('#search_results_display'),
            $('#tp_month_selector').val(),
            $('#tp_year_selector').val()
        )
    });
});

var load_compliance_report = function(destination_object, month, year){
    $('#compliance_loading_screen').show();
    $('.time_period_options').disable();
    $('#report_loading_status').spin();
    destination_object.empty();

    var start_date = moment().year(year).month(month - 1).date(1).hour(0).minute(0).seconds(0);
    var end_date = moment(start_date);
    end_date.add(1, 'months').subtract(1, 'days');
    var report_url = "/compliance/get_report/proposal/";
    report_url += start_date.format('YYYY-MM-DD') + "/";
    report_url += end_date.format('YYYY-MM-DD');

    var jqxhr = $.get(report_url, function(data){
        destination_object.append(data);
        $('#report_loading_status').spin(false);
        $( document ).tooltip({
            content: function () {
                return $(this).prop('title');
            }
        });
        if($('#export_csv_button').length == 0){
            $('#search_term_container').append('<input type="button" value="Export as CSV" class="search_button export_csv_button" id="export_csv_button"/>');
            $('#export_csv_button').click(function(){
                var csv_url = report_url + "/csv";
                location.href= csv_url;
            });
        }
        $('#compliance_loading_screen').fadeOut();
        $('.time_period_options').enable();

    }, 'html')
    .fail(function(v1, v2, v3){
        alert("failed");
    });
};

var export_report_as_csv = function(element, csv_url){};


var generate_year_select_options = function(parent_obj, min_date, max_date, selected_year){
    var today = moment();
    var min_date_obj = moment(min_date);
    var max_date_obj = moment(max_date);
    var min_year = min_date_obj.year();
    var max_year = max_date_obj.year();
    var current_year = today.year();
    if(!selected_year){
        selected_year = current_year;
    }
    var year_list = {};
    parent_obj.empty();
    while(current_year >= min_year){
        var options = {value: current_year};
        if(current_year == selected_year){
            options['selected'] = 'selected';
        }
        $('<option/>', options).text(current_year).appendTo(parent_obj);
        current_year--;
    }
    return parent_obj;
};

var generate_month_select_options = function(parent_obj, min_date, max_date, selected_year, selected_month){
    var today = moment();
    if(!selected_year){
        selected_year = today.year();
    }
    if(!selected_month){
        selected_month = parseInt(today.format("M"),10);
    }
    var min_date_obj = moment(min_date);
    var max_date_obj = moment(max_date);
    var earliest_month = 1;
    var latest_month = 12;
    if(selected_year == min_date_obj.year()){
        earliest_month = parseInt(min_date_obj.format("M"), 10);
        selected_month = earliest_month;
    }
    if(selected_year == max_date_obj.year()){
        latest_month = parseInt(max_date_obj.format("M"), 10);
        selected_month = latest_month < selected_month ? latest_month : selected_month;
    }
    var is_selected = "";
    var this_month = earliest_month;
    var earliest_month_obj = moment().year(selected_year).month(earliest_month - 1).date(1).hour(1).minute(0).second(0);
    var latest_month_obj = moment().year(selected_year).month(latest_month - 1).date(1).hour(23).minute(59).second(59);
    var this_month_obj = earliest_month_obj;
    parent_obj.empty();
    while(this_month_obj < latest_month_obj){
        var options = {
            value: this_month_obj.format("M")
        }
        if(parseInt(this_month_obj.format("M"), 10) == selected_month){
            options['selected'] = 'selected';
        }
        $('<option/>', options).text(this_month_obj.format("MMMM")).appendTo(parent_obj);
        this_month_obj.add(1, 'months');
    }
    return parent_obj;
};
