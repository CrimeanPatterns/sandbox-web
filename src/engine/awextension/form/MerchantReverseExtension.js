/**
 * @param {AbstractFormExtension} extension
 */
function addFormExtension(extension) {
    var data;

    function getCategoryData(id) {
        var options = [{value: '', label: ''}];

        data.forEach(function (row) {
            if (row.cardId === id) {
                row.multipliers.forEach(function (multiplier) {
                    options.push({value: multiplier.id, label: multiplier.groupName});
                });
            }
        });

        return options;
    }

    /**
     * will be called on field change
     * @param {FormInterface} form
     * @param {string} fieldName in lower case
     */
    extension.onFieldChange = function (form, fieldName) {
        if (fieldName === 'credit_card') {
            var creditCard = form.getValue(fieldName);

            form.setOptions('category', getCategoryData(creditCard));
        }

        form.submit();
    };

    /**
     * will be called when form loaded and ready
     * @param {FormInterface} form
     */
    extension.onFormReady = function (form) {
        var options = [{value: '', label: ''}];

        data = form.getValue('choice_data');
        data.forEach(function (row) {
            options.push({value: row.cardId, label: row.name});
        });

        form.setOptions('credit_card', options);
    };
}