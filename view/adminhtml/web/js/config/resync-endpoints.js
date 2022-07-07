define(['jquery', 'mage/translate', 'domReady!'], function ($, $t) {
    'use strict';

    var klevuAdmin = klevuAdmin || {};

    const endPointsButton = $('#klevu_integration_endpoints_resync');
    const successMessage = $('#klevu-integration-endpoint-success-msg');
    const errorMessage = $('#klevu-integration-endpoint-error-msg');

    klevuAdmin.isJsAPIKeyValid = function (key) {
        return key.startsWith('klevu-');
    };

    klevuAdmin.isRestAPIKeyValid = function (key) {
        return key.length >= 10;
    };

    /**
     * Initializer
     * @param {String} endpointUrl
     * @param {String} jsApiKey
     * @param {String} restApiKey
     */
    function init(endpointUrl, jsApiKey, restApiKey) {

        if (klevuAdmin.isJsAPIKeyValid(jsApiKey) && klevuAdmin.isRestAPIKeyValid(restApiKey)) {
            endPointsButton.show();
        }

        endPointsButton.click(function () {
            successMessage.hide().text('');
            errorMessage.hide().text('');

            if (!klevuAdmin.isJsAPIKeyValid(jsApiKey)) {
                errorMessage.text($t('Js API is not valid, please check and update.')).show();
                return;
            }
            if (!klevuAdmin.isRestAPIKeyValid(restApiKey)) {
                errorMessage.text($t('Rest API Key is not valid, please check and update.')).show();
                return;
            }

            $('#klevu_integration_endpoints').trigger("processStart");
            $.post(endpointUrl, {
                js_api_key: jsApiKey, rest_api_key: restApiKey
            }, function (data) {
                successMessage.text(data.message + ' ' + $t('Refreshing Page.')).show();
                window.location.reload();
            }).fail(function(xhr) {
                $('#klevu_integration_endpoints').trigger("processStop");
                const response = JSON.parse(xhr.responseText);
                errorMessage.text(response.message).show();
            });
        });
    }

    /**
     * Export/return dataFields
     * @param {Object} dataFields
     */
    return function (dataFields) {
        init(
            dataFields.endpointUrl,
            dataFields.jsApiKey,
            dataFields.restApiKey
        );
    };
});
