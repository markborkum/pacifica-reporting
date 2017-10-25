$(function(){
    $(".search_term_type_selector").select2({
        placeholder:"Search Type...",
        minimumResultsForSearch: Infinity
    });

    $(".search_term").select2({
        height:"2em",
        placeholder:"Select Your Search Criteria...",
        minimumInputLength: 3,
        tokenSeparators: [",", " "],
        ajax: {
            dataType: "json",
            delay: 250,
            cache: true,
            url: generateSearchURL,
            data: function() { return ""; },
            processResults: function(data, params) {
                params.page = params.page || 1;
                current_object_type = getCurrentObjectType();
                if(current_object_type == "user"){
                    $.each(data, function(index, obj){
                        obj.id = obj.person_id;
                    });
                }
                return {
                    results: data //,
                    // pagination: {
                    //     more: (params.page * 300) < data.total_count
                    // }
                };
            }
        },
        escapeMarkup: function(markup) {
            return markup;
        },
        templateResult: formatSearchResult,
        templateSelection: formatSearchSelection
    });

    //check if object type has been selected
    if(!getCurrentObjectType()){
        $(".search_term").disable();
    }

    $(".search_term_type_selector").on("change", function(){
        if($(this).val()){
            $("#search_term").enable();
        }else{
            $("#search_term").disable();
        }
        $("#search_term").val(null).trigger("change");
    });

});
var term_search_url = "/ajax/metadata_item_search/";
var current_object_type = "";

var generateSearchURL = function(params){
    var new_url = term_search_url;
    var object_type = $("#search_term_type_selector").val();
    new_url += object_type + "/" + params.term;
    return new_url;
};

var formatSearchResult = function(item){
    current_object_type = getCurrentObjectType();
    var markup = false;
    if (item.loading) return item.text;
    switch(current_object_type)
    {
    case "proposal":
        markup = formatProposal(item);
        break;
    case "instrument":
        markup = formatInstrument(item);
        break;
    case "user":
        markup = formatUser(item);
        break;
    default:
        markup = false;
    }
    return markup;
};

var formatSearchSelection = function(item){
    current_object_type = getCurrentObjectType();
    if(!item.id) return false;
    var markup = "Please Select a Proposal/Project...";
    switch(current_object_type)
    {
    case "proposal":
        markup = "<span title=\"" + item.title + "\">Proposal: " + item.id + "</span>";
        break;
    case "instrument":
        markup = "<span title=\"" + item.display_name + "\">Instrument: " + item.id + "</span>";
        break;
    case "user":
        markup = "<span title=\"" + item.display_name + "\">User: " + item.simple_display_name + "</span>";
        break;
    default:

        break;
    }
    return markup;
};

var formatProposal = function(item) {
    var markup = false;
    var start_date = moment(item.start_date);
    var end_date = moment(item.end_date);
    var start_date_string = start_date.isValid() ? start_date.format("MM/DD/YYYY") : "&mdash;&mdash;";
    var end_date_string = end_date.isValid() ? end_date.format("MM/DD/YYYY") : "&mdash;&mdash;";

    if (item.loading) return item.text;
    // if (item.id.length > 0) {
    markup = "<div id=\"prop_info_" + item.id + "\" class=\"prop_info\">";
    markup += "   <div class=\"";
    markup += item.currently_active == true ? "active" : "inactive";
    markup += "_proposal\"><strong>Proposal " + item.id + "</strong>";
    markup += "   </div>";
    markup += "   <div style=\"float:right;\">";
    markup += "     <span class=\"active_dates\">";
    if (item.currently_active == true && item.state == "active") {
        markup += "Active Through " + end_date_string;
    }else if(item.currently_active == false) {
        if(item.state == "preactive") {
            markup += "Inactive Until " + start_date_string;
        }else{
            if(!start_date.isValid() || !end_date.isValid()) {
                markup += "Invalid Start/End Dates";
            }else{
                markup += "Inactive Since " + end_date_string;
            }
        }
    }
    markup += "     </span>";
    markup += "   </div>";
    markup += "</div>";
    markup += "<div class=\"prop_description\">" + item.title + "</div>";

    return markup;
};

var formatInstrument = function(item) {
    if (item.loading) return item.text;
    var markup = false;
    var active = item.active == true ? "active" : "inactive";
    if (item.id) {
        if (item.id > 0) {
            markup = "<div id=\"inst_info_" + item.id + "\" class=\"inst_info\">";
            markup += "  <div class=\"" + active + "_instrument\">";
            markup += "     <strong>Instrument " + item.id + "</strong>";
            markup += "  </div>";
            markup += "  <div style=\"float:right;\">";
            markup += "     <span class=\"active_dates\">";
            markup += item.category;
            markup += "     </span>";
            markup += "</div>";
            markup += "<div class=\"inst_description\">" + item.name + "</div>";
        }
    }

    return markup;
};

var formatUser = function(item) {
    var markup = false;

    if (item.loading) return item.text;
    markup = "<div id=\"user_info_" + item.person_id + "\" class=\"user_info\">";
    markup += "   <div class=\"";
    // markup += item.currently_active == true ? "active" : "inactive";
    markup += "active_user\"><strong>" + item.simple_display_name + "</strong>";
    markup += "   </div>";
    if (item.emsl_employee == true) {
        markup += "   <div style=\"float:right;\">";
        markup += "<span class=\"emsl_staff_logo\">&nbsp;</span>";
        markup += "   </div>";
    }
    markup += "</div>";
    markup += "<div class=\"user_description\">" + item.email_address + "</div>";
    // }
    return markup;
};

var getCurrentObjectType = function(){
    return $("#search_term_type_selector").val() || false;
};
