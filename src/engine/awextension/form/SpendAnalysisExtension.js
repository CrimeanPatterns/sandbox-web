/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    /**
     * will be called on field change
     * @param {FormInterface} form
     * @param {string} fieldName in lower case
     */
    extension.onFieldChange = function (form, fieldName) {
        if (fieldName === 'owner') {
            var field = form.getField('owner');

            if (field && field.attr) {
                var selectedValue = form.getValue('owner');
                if (field.attr.fields && field.attr.fields[selectedValue]) {
                    form.showField(field.attr.fields[selectedValue], true);

                    for (var owner in field.attr.fields) {
                        if (field.attr.fields.hasOwnProperty(owner) && owner !== selectedValue) {
                            form.showField(field.attr.fields[owner], false);
                        }
                    }
                }
            }
        }

        if (fieldName === 'select_filter') {
            var selectFilterValue = form.getValue('select_filter');
            var selectFilter = form.getField('select_filter');

            if (selectFilter && selectFilter.attr) {
                selectFilter.attr.fields.forEach(function (fieldName) {
                    var switchField = form.getField(fieldName);

                    if (switchField && switchField.attr && switchField && switchField.attr.icons) {
                        if (selectFilterValue === 'all') {
                            return form.setValue(fieldName, true);
                        }
                        if (switchField.attr.icons.indexOf(selectFilterValue) === -1) {
                            form.setValue(fieldName, false);
                        } else {
                            form.setValue(fieldName, true);
                        }
                    }
                });
            }
        }
    };

    /**
     * will be called when form loaded and ready
     * @param {FormInterface} form
     */
    extension.onFormReady = function (form) {
        extension.onFieldChange(form, 'owner');
    };

}