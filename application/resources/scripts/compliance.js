$(function(){
    //setup the date picker
    generate_year_select_options(
        $("#tp_year_selector"), earliest_available, latest_available,
        parseInt(Cookies.get("selected_year"), 10));
    generate_month_select_options(
        $("#tp_month_selector"), earliest_available, latest_available,
        parseInt(Cookies.get("selected_year"), 10), parseInt(Cookies.get("selected_month"), 10));

    $("#tp_year_selector").select2({minimumResultsForSearch: -1});
    $("#tp_month_selector").select2({minimumResultsForSearch: -1});

    $("#tp_year_selector").on("change", function(event){
        var el = $(event.target);
        Cookies.set("selected_year", el.val());
        generate_month_select_options(
            $("#tp_month_selector"), earliest_available, latest_available,
            parseInt(Cookies.get("selected_year"), 10), parseInt(Cookies.get("selected_month"), 10));
    });
    $("#tp_month_selector").on("change", function(event){
        var el = $(event.target);
        Cookies.set("selected_month", el.val());
    });

    $("#generate_report_button").click(function(){
        load_compliance_report(
            $("#search_results_display"),
            $("#tp_month_selector").val(),
            $("#tp_year_selector").val()
        );
    });

    var ComplianceDateField = function(config) {
        jsGrid.Field.call(this, config);
    };

    ComplianceDateField.prototype = new jsGrid.Field({
        sorter: function(date1, date2) {
            return new Date(date1) - new Date(date2);
        },
        itemTemplate: function(value, item) {
            if (typeof value != "undefined" && value !== null) {
                return moment(value).format("MMMM DD, YYYY");
            }else{
                return "&mdash; &mdash; &mdash; &mdash;";
            }
        }
    });

    jsGrid.fields.complianceDateField = ComplianceDateField;

});

var load_compliance_report = function(destination_object, month, year){
    $("#compliance_loading_screen").show();
    $(".time_period_options").disable();
    $("#report_loading_status").spin();

    var start_date = moment().year(year).month(month - 1).date(1).hour(0).minute(0).seconds(0);
    var end_date = moment(start_date);
    end_date.add(1, "months").subtract(1, "days");
    var report_url = "/compliance/get_report/proposal/";
    report_url += start_date.format("YYYY-MM-DD") + "/";
    report_url += end_date.format("YYYY-MM-DD");
    report_url += "/json";

    $.get(report_url, function(response) {
        $(".search_results_display").show();
        $(".booking_results_header").show();
        $("#booking_results_display").jsGrid({
            height: "auto",
            width: "100%",
            sorting: true,
            paging: false,
            data: response.booking_results,
            fields: [
                {
                    name: "proposal_id", title: "Proposal ID", width: "8%",
                    cellRenderer: function(value, item) {
                        return $("<td>", {
                            "class": "proposal_id_container " + item.proposal_color_class,
                            "text": value
                        });
                    },
                    headercss: "compliance_table_header"
                },
                {
                    name: "instrument_id", title: "Instrument ID", width: "9%",
                    cellRenderer: function(value, item) {
                        return $("<td>", {
                            "class": "instrument_id_container " + item.instrument_color_class,
                            "text": value
                        });
                    },
                    headercss: "compliance_table_header"
                },
                {
                    name: "project_type", title: "Project Type", width: "15%"
                },
                {
                    name: "proposal_pi", title: "Principal Investigator",
                    headercss: "compliance_table_header", width: "15%"
                },
                {
                    name: "instrument_group", title: "Instrument", type: "text", headercss: "compliance_table_header",
                    width: "40%",
                    cellRenderer: function(value, item) {
                        return $("<td>", {
                            "class": "instrument_group_container",
                        })
                            .append($("<span>", {
                                "class": "instrument_group",
                                "text": item.instrument_group
                            }))
                            .append($("<p>", {
                                "class": "instrument_name",
                                "text": item.instrument_name
                            }));
                    }
                },
                {
                    name: "booking_count", title: "Number of Bookings", type: "number",
                    headercss: "compliance_table_header", width: "10%", align: "center"
                },
                {
                    name: "file_count", title: "Data File Count", type: "number",
                    headercss: "compliance_table_header", width: "10%", align: "center"
                }
            ]
        });
        $(".no_booking_results_header").show();
        $("#no_booking_results_display").jsGrid({
            height: "auto",
            width: "100%",
            sorting: true,
            paging: false,
            data: response.no_booking_results,
            fields: [
                { name: "proposal_id", title: "Proposal ID", type: "text" },
                { name: "project_type", title: "Project Type", type: "text" },
                { name: "proposal_pi", title: "Principal Investigator", type: "text" },
                { name: "actual_start_date", title: "Actual Start Date", type: "complianceDateField" },
                { name: "actual_end_date", title: "Actual End Date", type: "complianceDateField" },
                // { name: "closed_date", title: "Closing Date", type: "complianceDateField" },
                // { name: "last_change_date", title: "Last Updated", type: "complianceDateField" }
            ]
        });

    })
        .complete(function(){
            $("#compliance_loading_screen").fadeOut();
            $(".time_period_options").enable();
        });
};

var generate_year_select_options = function(parent_obj, min_date, max_date, selected_year){
    var today = moment();
    var min_date_obj = moment(min_date);
    // var max_date_obj = moment(max_date);
    var min_year = min_date_obj.year();
    // var max_year = max_date_obj.year();
    var current_year = today.year();
    if(!selected_year){
        selected_year = current_year;
    }
    // var year_list = {};
    parent_obj.empty();
    while(current_year >= min_year){
        var options = {value: current_year};
        if(current_year == selected_year){
            options["selected"] = "selected";
        }
        $("<option/>", options).text(current_year).appendTo(parent_obj);
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
    // var is_selected = "";
    // var this_month = earliest_month;
    var earliest_month_obj = moment().year(selected_year).month(earliest_month - 1).date(1).hour(1).minute(0).second(0);
    var latest_month_obj = moment().year(selected_year).month(latest_month - 1).date(1).hour(23).minute(59).second(59);
    var this_month_obj = earliest_month_obj;
    parent_obj.empty();
    while(this_month_obj < latest_month_obj){
        var options = {
            value: this_month_obj.format("M")
        };
        if(parseInt(this_month_obj.format("M"), 10) == selected_month){
            options["selected"] = "selected";
        }
        $("<option/>", options).text(this_month_obj.format("MMMM")).appendTo(parent_obj);
        this_month_obj.add(1, "months");
    }
    return parent_obj;
};
