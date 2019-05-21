$(function(){
    //setup the date picker
    generate_year_select_options(
        $("#start_year_selector"), earliest_available, latest_available,
        parseInt(Cookies.get("selected_start_year"), 10));
    generate_month_select_options(
        $("#start_month_selector"), earliest_available, latest_available,
        parseInt(Cookies.get("selected_start_year"), 10), parseInt(Cookies.get("selected_start_month"), 10));

    generate_year_select_options(
        $("#end_year_selector"), earliest_available, latest_available,
        parseInt(Cookies.get("selected_end_year"), 10));
    generate_month_select_options(
        $("#end_month_selector"), earliest_available, latest_available,
        parseInt(Cookies.get("selected_end_year"), 10), parseInt(Cookies.get("selected_end_month"), 10));



    $("#start_year_selector").select2({minimumResultsForSearch: -1});
    $("#start_month_selector").select2({minimumResultsForSearch: -1});
    $("#end_year_selector").select2({minimumResultsForSearch: -1});
    $("#end_month_selector").select2({minimumResultsForSearch: -1});

    $(".year_selector").on("change", function(event){
        var el = $(event.target);
        var selector_type = el.parents("div").hasClass("start") ? "start" : "end";
        var month_selector = el.parents("div").find(".month_selector");
        Cookies.set("selected_" + selector_type + "_year", el.val());
        generate_month_select_options(
            month_selector, earliest_available, latest_available,
            parseInt(Cookies.get("selected_" + selector_type + "_year"), 10),
            parseInt(Cookies.get("selected_" + selector_type + "_month"), 10)
        );
    });
    $(".month_selector").on("change", function(event){
        var el = $(event.target);
        var selector_type = el.parents("div").hasClass("start") ? "start" : "end";
        Cookies.set("selected_" + selector_type + "_month", el.val());
    });

    $("#generate_report_button").click(function(){
        load_activity_report(
            $("#search_results_display"),
            $("#start_month_selector").val(),
            $("#start_year_selector").val(),
            $("#end_month_selector").val(),
            $("#end_year_selector").val()
        );
    });

    $(".tp_selector").on("change", function(event) { check_date_range(event); });
    check_date_range();


});

var check_date_range = function(event) {
    var set_all = typeof event === "undefined";
    var event_el = typeof event === "undefined" ? $("#start_month_selector") : $(event.target);
    var selector_type = event_el.parents(".start") ? "Starting" : "Ending";
    var position_type = event_el.parents(".start") ? "earlier" : "later";
    var other_selector_type = event_el.parents(".start") ? "Ending" : "Starting";
    var error_indicator = set_all ? $(".year_month_container >.tp_selector") : event_el.parents(".year_month_container").find(".tp_selector");
    //need to check on date range validity on every change event
    var start_container = $(".time_period_options > .start");
    var end_container = $(".time_period_options > .end");
    var start_month = start_container.find(".month_selector");
    var start_year = start_container.find(".year_selector");
    var start_period = moment(start_year.val() + "-" + start_month.val().padStart(2, "0")).utc().startOf("month");

    var end_month = end_container.find(".month_selector");
    var end_year = end_container.find(".year_selector");
    var end_period = moment(end_year.val() + "-" + end_month.val().padStart(2, "0")).utc().endOf("month");

    var report_button = $("#generate_report_button");

    // now let's check if the date range holds up
    var isValidRange = (end_period - start_period) > 0;
    if (!isValidRange) {
        // we've got an inappropriate range, so let's highlight the fact
        // and disable the report generation search_button
        report_button.disable();
        $(".time_period_options .error_message").text(
            selector_type + " month must be " + position_type + " than " + other_selector_type + " month"
        );
        $(".time_period_options .error_message").show();
        error_indicator.addClass("has-error");

    }else{
        report_button.enable();
        $(".time_period_options .error_message").text();
        $(".time_period_options .error_message").hide();
        $(".year_month_container >.tp_selector").removeClass("has-error");
    }

};

var load_activity_report = function(destination_object, start_month, start_year, end_month, end_year){
    $("#compliance_loading_screen").show();
    $(".time_period_options").disable();
    $("#report_loading_status").spin();
    start_month = start_month.padStart(2, "0");
    end_month = end_month.padStart(2, "0");

    var start_date = moment(start_year + "-" + start_month).utc().startOf("month");
    var end_date = moment(end_year + "-" + end_month).utc().endOf("month");
    var report_url = "/compliance/get_activity_report/";
    report_url += start_date.format("YYYY-MM-DD") + "/";
    report_url += end_date.format("YYYY-MM-DD");
    report_url += "/json";

    $.get(report_url, function(response) {
        $(".search_results_display").show();
        $(".no_booking_results_header").show();
        $("#no_booking_results_display").jsGrid({
            height: "auto",
            width: "100%",
            sorting: true,
            paging: false,
            data: response,
            fields: [
                { name: "project_id", title: "Project ID", type: "text" },
                { name: "project_type", title: "Project Type", type: "text" },
                { name: "project_pi", title: "Principal Investigator", type: "text" },
                { name: "actual_start_date", title: "Actual Start Date", type: "complianceDateField" },
                { name: "actual_end_date", title: "Actual End Date", type: "complianceDateField" },
                { name: "closed_date", title: "Closing Date", type: "complianceDateField" },
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
