var hc_pie_options = {
    credits: false,
    chart: {
        animation: false,
        plotBackgroundColor: null,
        plotBorderWidth: null,
        plotShadow: false,
        spacing: [10, 0, 0, 0],
        type: 'pie'
    },
    title: {
        style: {
            fontSize: '13px',
            fontWeight: 'bold',
            fontFamily: '"Open Sans", Helvetica, Arial, sans-serif'
        },
        x: -30
    },
    legend: {
        layout: 'vertical',
        align: 'right',
        margin: '2'
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
                floating: true
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
            fontFamily: '"Open Sans","Helvetica Neue", Helvetica, Arial, sans-serif',
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
        type: 'logarithmic',
        min: 1,
        title: {
            text: 'Total File Count',
            style: {
                color: Highcharts.getOptions().colors[1]
            }
        },
        labels: {
            style: {
                color: Highcharts.getOptions().colors[1]
            }
        }
    }, { //volume axis
        // type: 'logarithmic',
        min: 0,
        title: {
            text: 'Total File Size',
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
        opposite: true
    }]
};

// var datepicker = $.fn.datepicker.noConflict();
// $.fn.bootstrapDP = datepicker;

var get_transaction_info = function(el, transaction_list) {
    var el = $(el);
    var parent_container = el.parents('.object_body');
    var details_container = parent_container.find('.transaction_details_container');
    var notifier_container = parent_container.find('.transaction_details_notifier');
    var disclosure_arrow = notifier_container.find('.disclosure_arrows');
    var load_indicator = parent_container.find('.transaction_details_loader');
    if (details_container.html().length == 0) { //empty details, needs loading
        var url = base_url + 'group/get_transaction_list_details';
        load_indicator.spin({
            scale: 0.5,
            left: '20%',
            width: 4,
            lines: 11
        })
        load_indicator.fadeIn();
        var posting = $.post(url, JSON.stringify(transaction_list), function(data) {
            details_container.html(data);
            disclosure_arrow.removeClass('dc_up').addClass('dc_down');
            load_indicator.fadeOut().spin(false);
            details_container.show();
        });
        posting.done(function() {

        });
    } else if (disclosure_arrow.hasClass('dc_up') && details_container.html().length > 0) {
        //filled, but hidden so just show
        disclosure_arrow.removeClass('dc_up').addClass('dc_down');
        details_container.show();
    } else {
        disclosure_arrow.removeClass('dc_down').addClass('dc_up');
        details_container.hide();
    }
};

var submit_group_change_worker = function(el, object_type, object_id, action) {
    var group_id = parseInt(el.parents('.reporting_object_container').find('.group_search_form .group_id').val(), 10);
    var current_search_string = el.parents('.group_edit_section').find('.object_search_box').val();
    var update_list = JSON.stringify(
        [{
            'object_id': object_id,
            'group_id': group_id,
            'action': action,
            'current_search_string': current_search_string
        }]);
    var url = base_url + 'ajax/update_object_preferences/' + object_type + '/' + group_id;
    var poster = $.post(url, update_list, function(data) {
        if (data) {
            el.parents('.search_results_display').html(data);
        }

    });
};

var submit_group_option_change = function(el, option_type, new_value) {
    var group_id = parseInt(el.parents('.reporting_object_container').find('.group_search_form .group_id').val(), 10);
    var update_list = JSON.stringify({
        'group_id': group_id,
        'option_type': option_type,
        'option_value': new_value
    });
    var url = base_url + 'ajax/change_group_option/' + group_id;
    var poster = $.post(url, update_list, function(data) {
        load_group_results(object_type, group_id);
    }, 'json');
};

var humanFileSize = function(size) {
    if (size == 0) {
        return "0";
    }
    var i = Math.floor(Math.log(size) / Math.log(1024));
    return (size / Math.pow(1000, i)).toFixed(2) * 1 + ' ' + ['B', 'kB', 'MB', 'GB', 'TB'][i];
};


var timeline_load_new_data_check = function(timeline_obj, new_start, new_end) {
    // debugger;
    var chart = timeline_obj;
    var x_extremes = chart.xAxis[0].getExtremes();
    if (new_end > x_extremes.dataMax || new_start < x_extremes.dataMin) {
        //we're outside of our loaded data, need to request more
        return true;
    } else {
        //still inside our current bounds, just zoom
        return false;
    }
};

var load_new_timeline_data = function(timeline_obj, object_type, object_id, start_date, end_date) {
    var url = base_url + "item/get_timeline_data/" + object_type + "/" + object_id + "/";
    url += start_date + "/" + end_date;
    var fv_data = timeline_obj.series[0];
    var tx_data = timeline_obj.series[1];

    var getter = $.get(url, function(data) {
        fv_data.setData(data.file_volumes, false);
        tx_data.setData(data.transaction_counts, false);
        timeline_obj.redraw();
    });
};

var load_new_group_timeline_data = function(timeline_obj, object_type, group_id, start_date, end_date) {
    // debugger;
    var url = base_url + "group/get_group_timeline_data/" + object_type + "/" + group_id + "/";
    url += start_date + "/" + end_date;
    var fv_data = timeline_obj.series[0];
    var tx_data = timeline_obj.series[1];
    $('#loading_blocker_' + group_id).spin().show();
    var getter = $.get(url, function(data) {
        $('#loading_blocker_' + group_id).spin(false).hide();
        fv_data.setData(data.file_volumes, false);
        tx_data.setData(data.transaction_counts, false);
        timeline_obj.redraw();
    });
};

var submit_group_change = function(el, object_type, object_id, action) {
    submit_group_change_worker(el, object_type, object_id, action)
}


var remove_empty_uls = function() {
    $('#search_results_display').find('ul').each(function(index, item) {
        item = $(item);
        if (item.find('li').length === 0) {
            item.remove();
        }
    });
};

var load_results = function(object_type, object_id) {
    $('#loading_status_' + object_id).spin();
    var url = base_url + 'item/get_reporting_info/' + object_type + '/' + object_id + '/' + time_range;
    var getter = $.get(url);
    getter.done(function(data, status) {
        $('#loading_status_' + object_id).spin(false);
        $('#object_body_container_' + object_id).replaceWith(data);
    });
};


var load_group_results = function(object_type, group_id, item_list) {
    if($('#loading_status_' + group_id).length !== 0){
        $('#loading_status_' + group_id).spin().show();
    }else{
        $('#loading_blocker_' + group_id).spin().show();
    }
    var obj_footer = $('#object_footer_' + group_id);
    var time_basis = $('#time_basis_selector_' + group_id).val();
    obj_footer.disable();
    var url = base_url + 'group/get_reporting_info_list/' + object_type + '/' + group_id + '/' + time_basis + '/' + time_range;
    var getter = $.get(url);
    getter.done(function(data, status) {
        $('#loading_status_' + group_id).spin(false).hide();
        $('#loading_blocker_' + group_id).spin(false).hide();
        $('#object_body_' + group_id + ' .info_message').hide();
        if ($('#object_body_container_' + group_id).length == 0) {
            $('#object_body_' + group_id).append(data);
        } else {
            $('#object_body_container_' + group_id).replaceWith(data);
        }
        obj_footer.enable();
    });
};


var get_search_results = function(el, filter_text) {
    if (filter_text.length > 0) {
        var url = base_url + 'item/get_object_lookup/' + object_type + '/' + filter_text;
        $.get(url, function(data) {
            $('#search_results_display').html(data);
        });
    } else {
        clear_results();
    }
};


var setup_search_checkboxes = function() {
    $('.object_selection_checkbox').click(function(event) {
        var el = $(event.target);
        var id = el.attr('id');
        var object_id = parseInt(id.substr(id.lastIndexOf("_") + 1), 10);
        var action = el.is(":checked") ? 'add' : 'remove';
        submit_group_change(el, object_type, object_id, action);
    });
}

var options = {
    callback: function(value) {
        get_group_objects($(this), value);
    },
    wait: 500,
    highlight: true,
    captureLength: 3
}

var clear_results = function() {
    // $('#search_results_display').slideUp('fast');
    // $('#search_results_display').html('');
};

var setup_confirmation_dialog_boxes = function(e) {};

var make_new_group_entry = function(group_name, input_el) {
    var update_list = {
        group_name: group_name
    };
    var url = base_url + 'ajax/make_new_group/' + object_type
    var poster = $.post(url, JSON.stringify(update_list), function(data) {
        var get_group_url = base_url + 'ajax/get_group_container/' + object_type + '/' + data.group_id;
        var getter = $.get(get_group_url, function(group_data) {
            var my_container = input_el.parents('div.reporting_object_container');
            my_container.before(group_data);
            $('#group_edit_section_new').fadeOut();
            $('#create_new_group_button').fadeIn()
        });
    }, "json");
};

var submit_group_name_change = function(group_name, group_id, input_el) {
    var url = base_url + 'ajax/change_group_name/' + group_id + '/';
    var update_list = {
        group_name: group_name
    }
    var posting = $.post(url, JSON.stringify(update_list), function(data) {
        input_el.attr('placeholder', data.group_name).val("");
        input_el.siblings('.group_edit_confirm_buttons').hide();
        $('span.displayed_group_name').html(data.group_name);
    });
};

var update_group_time_range = function(group_id, start_time, end_time) {
    var url = base_url + 'ajax/change_group_option/' + group_id;
    var start_time_obj = new moment(start_time);
    var end_time_obj = new moment(end_time);

    var update_list = {
        'option_type': 'start_time',
        'option_value': start_time_obj.format('YYYY-MM-DD HH:mm:ss')
    };
    var poster1 = $.post(url, JSON.stringify(update_list));

    update_list = {
        'option_type': 'end_time',
        'option_value': end_time_obj.format('YYYY-MM-DD HH:mm:ss')
    };
    var poster2 = $.post(url, JSON.stringify(update_list));
}

var get_group_objects = function(el, filter_text) {
    filter_text = filter_text != null ? filter_text : "";
    var edit_el = el.parents('.reporting_object_container').find('.group_edit_section');
    var instructions_container = edit_el.find('.search_instructions_container');
    var group_id = edit_el.find('.group_search_form input.group_id').val();
    var object_type = edit_el.find('.group_search_form input.object_type').val();
    var dl_url = base_url + 'ajax/get_object_group_lookup/' + object_type + '/' + group_id;
    var results_container = edit_el.find('.search_results_display');
    instructions_container.slideUp();
    if (el.hasClass('edit_grouping_button')) {
        // if(!edit_el.hasClass('closed')){
        if (edit_el.is(':visible')) {
            edit_el.hide();
            load_group_results(object_type, group_id);
        } else {
            $.get(dl_url, function(data) {
                results_container.html(data);
                edit_el.show();
            });
        }
    }

    if (el.hasClass('object_search_box')) {
        dl_url += '/' + filter_text;
        $.get(dl_url, function(data) {
            results_container.html(data);
        });
    }
};

var get_transaction_list_for_date_range = function(el, start_date, end_date, full_txn_list){
    var date_key = '';
    var output_txn_list = [];
    var current_moment = moment(start_date);
    var end_moment = moment(end_date);
    if(start_date && end_date){
        while(current_moment <= end_moment){
            date_key = current_moment.format('YYYY-MM-DD');
            if(full_txn_list[date_key]){
                $.merge(output_txn_list,full_txn_list[date_key]);
            }
            current_moment.add(1,'d');
        }
    }else{
        $.each(full_txn_list, function(index, value){
            $.merge(output_txn_list,value);
        });
    }
    output_txn_list = GetUnique(output_txn_list);
    return output_txn_list;
}

function GetUnique(inputArray)
{
	var outputArray = [];
	for (var i = 0; i < inputArray.length; i++)
	{
		if ((jQuery.inArray(inputArray[i], outputArray)) == -1)
		{
			outputArray.push(inputArray[i]);
		}
	}
	return outputArray;
}



$(function() {
    // Make monochrome colors and set them as default for all pies
    Highcharts.getOptions().plotOptions.pie.colors = (function() {
        var colors = [],
            // base = Highcharts.getOptions().colors[2],
            base = '#4b7eba',
            i;
        for (i = 0; i < 10; i += 1) {
            // Start out with a darkened base color (negative brighten), and end
            // up with a much brighter color
            colors.push(Highcharts.Color(base).brighten((i - 2) / 7).get());
        }
        return colors;
    }());

    Highcharts.getOptions().plotOptions.spline.colors = (function() {
        var colors = [],
            // base = Highcharts.getOptions().colors[2],
            base = '#4b7eba',
            i;
        for (i = 0; i < 10; i += 1) {
            // Start out with a darkened base color (negative brighten), and end
            // up with a much brighter color
            colors.push(Highcharts.Color(base).brighten((i - 2) / 7).get());
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


    $('.select2-search').hide();


});
