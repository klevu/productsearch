define(
    [
        'Magento_Ui/js/grid/columns/actions'
    ],
    function (Actions) {
        'use strict';

        return Actions.extend({

            /**
             * Applies specified action.
             *
             * @param {String} actionIndex - Actions' identifier.
             * @param {Number} rowIndex - Index of a row.
             * @returns {ActionsColumn} Chainable.
             */
            applyAction: function (actionIndex, rowIndex) {
                var action = this.getAction(rowIndex, actionIndex),
                    callback = this._getCallback(action);

                action.confirm ?
                    this._confirm(action, callback) :
                    callback();

                return this;
            },
        });
    }
);
