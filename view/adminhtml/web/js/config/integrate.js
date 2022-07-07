define([
    'jquery',
    'mage/translate',
    'mage/url',
    'domReady!'
], function ($, $t, urlBuilder) {
    'use strict';

    var klevuAdmin = klevuAdmin || {};

    const continueButton = $('#klevu_integration_authentication_keys_integration_continue');
    const revertButton = $('#klevu_integration_authentication_keys_integration_revert');
    const jsApiKeyField = $('#klevu_integration_authentication_keys_js_api_key');
    const restApiKeyField = $('#klevu_integration_authentication_keys_rest_api_key');
    const companyNameRow = $('#row_klevu_integration_authentication_keys_company_name');
    const companyEmailRow = $('#row_klevu_integration_authentication_keys_company_email');
    const confirmButtonsRow = $('#row_klevu_integration_authentication_keys_integration_confirm');
    const confirmIntegrationButton = $('#klevu_integration_authentication_keys_integration_confirm');
    const cancelIntegrationButton = $('#klevu_integration_authentication_keys_integration_cancel');
    const successMessage = $('#klevu-integration-success-msg');
    const warningMessage = $('#klevu-integration-warning-msg');
    const errorMessage = $('#klevu-integration-error-msg');
    const saveButton = $('#save');

    klevuAdmin.isJsAPIKeyValid = function (key) {
        return !key || key.startsWith('klevu-');
    };

    klevuAdmin.isRestAPIKeyValid = function (key) {
        return !key || key.length >= 10;
    };

    klevuAdmin.hideConfirmation = function () {
        companyNameRow.hide();
        companyEmailRow.hide();
        confirmButtonsRow.hide();
    }

    klevuAdmin.disableSave = function () {
        if (klevuAdmin.js_api_key && klevuAdmin.rest_api_key) {
            warningMessage.text($t('Config Save has been disabled until API changes are confirmed, cancelled or reverted.')).show();
        }
        saveButton.attr("disabled", "disabled");
    }

    klevuAdmin.enableSave = function () {
        warningMessage.hide().text('');
        saveButton.removeAttr("disabled");
    }

    klevuAdmin.revertChanges = function () {
        klevuAdmin.enableSave()

        jsApiKeyField.val(klevuAdmin.js_api_key);
        jsApiKeyField.removeAttr('readonly');

        restApiKeyField.val(klevuAdmin.rest_api_key);
        restApiKeyField.removeAttr('readonly');

        errorMessage.hide().text('');
        warningMessage.text($t('Update cancelled. Changes to API keys have been reverted.')).show();
        successMessage.hide().text('');

        continueButton.hide();
        revertButton.hide();
        klevuAdmin.hideConfirmation();
    }

    /**
     * @param js_api_key
     * @param rest_api_key
     *
     * @returns {*|boolean}
     */
    klevuAdmin.checkExistingApiKeys = function (js_api_key, rest_api_key) {
        const serviceUrl = '/rest/default/V1/klevu/integration/getStoresUsingApiKeys';
        const payload = {
            js_api_key: js_api_key, rest_api_key: rest_api_key, form_key: FORM_KEY
        };
        let headers = {};
        if (klevuAdmin.bearerToken) {
            headers = {
                'Authorization': 'Bearer ' + klevuAdmin.bearerToken,
            };
        }

        return $.ajax({
            url: urlBuilder.build(serviceUrl),
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: "application/json; charset=UTF-8",
            headers: headers,
            beforeSend: function (xhr) {
                //Empty to remove magento's default handler
            }
        }).done(function (data, textStatus) {
            $('#klevu_integration_authentication_keys').trigger("processStop");
            if (textStatus === 'success' && !data.error) {
                let storeList = '';
                for (const store in data) {
                    if (!data.hasOwnProperty(store)) {
                        continue;
                    }
                    if (!storeList.length) {
                        storeList += '<ul style="margin-left: 1.5em; margin-top: 0.5em">';
                    }
                    storeList += '<li>' + data[store].name + ' (' + data[store].id + ': ' + data[store].code + ')</li>';
                }
                if (storeList.length) {
                    storeList += '</ul>';
                    warningMessage.html($t('This API Key has already been used in the following store(s):') + storeList).show();
                } else {
                    warningMessage.hide().html('');
                }
            } else if (data.error) {
                warningMessage.html(data.message).show();
            } else {
                warningMessage.hide().html('');
            }
        }).fail(function (xhr) {
            $('#klevu_integration_authentication_keys').trigger("processStop");
            warningMessage.hide().html('');
        });
    }

    /**
     * Initializer
     * @param {String} continueUrl
     * @param {String} confirmationUrl
     * @param {String} jsApiKey
     * @param {String} restApiKey
     * @param {String} bearerToken
     */
    function init(continueUrl, confirmationUrl, jsApiKey, restApiKey, bearerToken) {
        klevuAdmin.js_api_key = jsApiKey;
        klevuAdmin.rest_api_key = restApiKey;
        klevuAdmin.bearerToken = bearerToken;

        continueButton.hide();

        jsApiKeyField.on('keyup', function () {
            klevuAdmin.disableSave()
            klevuAdmin.hideConfirmation();

            if (!klevuAdmin.isJsAPIKeyValid($(this).val())) {
                continueButton.hide();
                revertButton.hide();
                return;
            }
            if (
                !$(this).val() ||
                ($(this).val() === klevuAdmin.js_api_key && restApiKeyField.val() === klevuAdmin.rest_api_key)
            ) {
                continueButton.hide();
                revertButton.hide();
                klevuAdmin.enableSave()
            } else {
                continueButton.show();
                revertButton.show();
            }
        });

        restApiKeyField.on('keyup', function () {
            klevuAdmin.disableSave()
            klevuAdmin.hideConfirmation();

            if (!klevuAdmin.isRestAPIKeyValid($(this).val())) {
                continueButton.hide();
                revertButton.hide();
                return;
            }
            if (
                !$(this).val() ||
                ($(this).val() === klevuAdmin.rest_api_key && jsApiKeyField.val() === klevuAdmin.js_api_key)
            ) {
                continueButton.hide();
                revertButton.hide();
                klevuAdmin.enableSave()
            } else {
                continueButton.show();
                revertButton.show();
            }
        });

        continueButton.click(function () {
            const js_api_key = jsApiKeyField.val();
            const rest_api_key = restApiKeyField.val();

            successMessage.hide().text('');
            warningMessage.hide().text('');
            errorMessage.hide().text('');

            if (js_api_key && rest_api_key) {
                $('#klevu_integration_authentication_keys').trigger("processStart");
                jsApiKeyField.attr("readonly", "readonly");
                restApiKeyField.attr("readonly", "readonly");
                $.post(continueUrl, {
                    js_api_key: js_api_key, rest_api_key: rest_api_key
                }, function (data) {
                    $('#klevu_integration_authentication_keys').trigger("processStop");
                    successMessage.text(data.message).show();
                    continueButton.hide();
                    revertButton.hide();
                    companyNameRow.show().find('.value').text(data.company);
                    companyEmailRow.show().find('.value').text(data.email);
                    confirmButtonsRow.show();
                    klevuAdmin.checkExistingApiKeys(js_api_key, rest_api_key);
                }).fail(function (xhr) {
                    $('#klevu_integration_authentication_keys').trigger("processStop");
                    const response = JSON.parse(xhr.responseText);
                    errorMessage.text(response.message).show();
                    jsApiKeyField.removeAttr('readonly');
                    restApiKeyField.removeAttr('readonly');
                });
            } else {
                errorMessage.text($t('Both JS and Rest API Keys must be set for integration')).show();
            }
        });

        confirmIntegrationButton.click(function () {
            const js_api_key = jsApiKeyField.val();
            const rest_api_key = restApiKeyField.val();

            successMessage.hide().text('');
            warningMessage.hide().text('');
            errorMessage.hide().text('');

            if (js_api_key && rest_api_key) {
                $('#klevu_integration_authentication_keys').trigger("processStart");
                $.post(confirmationUrl, {
                    js_api_key: js_api_key, rest_api_key: rest_api_key
                }, function (data) {
                    klevuAdmin.enableSave()
                    successMessage.text(data.message + '  Refreshing Page.').show();
                    continueButton.hide();
                    revertButton.hide();
                    klevuAdmin.hideConfirmation();
                    window.location.reload();
                }).fail(function (xhr) {
                    $('#klevu_integration_authentication_keys').trigger("processStop");
                    const response = JSON.parse(xhr.responseText);
                    errorMessage.text(response.message).show();
                });
            } else {
                errorMessage.text($t('Both JS and Rest API Keys must be set for integration')).show();
            }
        });

        cancelIntegrationButton.click(function () {
            klevuAdmin.revertChanges();
        });

        revertButton.click(function () {
            klevuAdmin.revertChanges();
        });
    }

    /**
     * Export/return dataFields
     * @param {Object} dataFields
     */
    return function (dataFields) {
        init(
            dataFields.continueUrl,
            dataFields.confirmationUrl,
            dataFields.jsApiKey,
            dataFields.restApiKey,
            dataFields.bearerToken
        );
    };
});
