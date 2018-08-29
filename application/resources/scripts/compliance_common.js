var ComplianceDateField = function(config) {
    jsGrid.Field.call(this, config);
};

ComplianceDateField.prototype = new jsGrid.Field({
    sorter: function(date1, date2) {
        return new Date(date1) - new Date(date2);
    },
    itemTemplate: function(value) {
        if (typeof value != "undefined" && value !== null) {
            return moment(value).format("MMMM DD, YYYY");
        }else{
            return "&mdash; &mdash; &mdash; &mdash;";
        }
    }
});

jsGrid.fields.complianceDateField = ComplianceDateField;
