define(
    ['jquery', 'underscore', 'backbone', 'oro/translator'],
    function($, _, Backbone, __) {
        'use strict';

        /**
         * Export action
         *
         * @export  pim/datagrid/export-action
         * @class   pim.datagrid.ExportAction
         * @extends Backbone.View
         */
        var ExportAction = Backbone.View.extend({

            label: __('pim.grid.mass_action.quick_export.title'),

            icon: 'download',

            /** @property {oro.datagrid.Grid} */
            datagrid: null,

            originalButtonSelector: 'div.grid-toolbar .mass-actions-panel .action.btn',

            originalButtonIcon: 'download',

            originalButton: null,

            template: _.template(
                '<li>' +
                    '<a href="javascript:void(0);" class="no-hash" title="<%= label %>">' +
                        '<%= label %>' +
                    '</a>' +
                '</li>'
            ),

            initialize: function (options) {
                if (!options.datagrid) {
                    throw new TypeError("'datagrid' is required");
                }
                this.datagrid = options.datagrid;

                if (_.has(options, 'label')) {
                    this.label = __(options.label);
                }
                if (_.has(options, 'icon')) {
                    this.icon = options.icon;
                }
                if (_.has(options, 'originalButtonIcon')) {
                    this.originalButtonIcon = options.originalButtonIcon;
                }

                if (!options.$gridContainer) {
                    throw new Error('Grid selector is not specified');
                }

                this.$gridContainer = options.$gridContainer;
                this.gridName = options.gridName;

                Backbone.View.prototype.initialize.apply(this, arguments);

                this.render();
            },

            render: function () {
                this.$gridContainer
                    .find('div.export-actions-panel')
                    .find('ul.dropdown-menu')
                    .append(
                        this.template({
                            icon: this.icon,
                            label: this.label
                        })
                    )
                    .on('click', 'li a:contains("'+ this.label +'")', _.bind(this.execute, this));

                this.originalButton = this.$gridContainer
                    .find(this.originalButtonSelector)
                    .find('.icon-' + this.originalButtonIcon +':contains("'+ this.label +'")')
                    .parent();

                this.originalButton.hide();
            },

            execute: function() {

                $.ajax({
                    url: this._getLink(),
                    data: this._getActionParameters(),
                    context: this,
                    dataType: 'json',
                    error: this._onAjaxError,
                    success: this._onAjaxSuccess
                });
            },

            _getLink: function() {
                var metadata = this.$gridContainer.data('metadata');

                var route = Routing.generate(metadata.massActions.quick_export_csv.route);

                return route;
            },

            _getActionParameters: function() {
                var selectionState = this.datagrid.getSelectionState();
                var collection     = this.datagrid.collection;

                var idValues = _.map(selectionState.selectedModels, function(model) {
                    return model.get(this.identifierFieldName);
                }, this);
                var params = {
                    inset: selectionState.inset ? 1 : 0,
                    values: idValues.join(',')
                };

                params = this.getExtraParameters(params, collection.state);
                params = collection.processFiltersParams(params, null, 'filters');

                var locale = decodeURIComponent(this.datagrid.collection.url).split('dataLocale]=').pop();
                if (locale) {
                    params.dataLocale = locale;
                }

                return params;
            },

            _onAjaxError: function() {
                return alert("broken");
                //return new Modal({
                //    title: this.messages.confirm_title,
                //    content: this.messages.confirm_content,
                //    okText: this.messages.confirm_ok
                //});
            },

            _onAjaxSuccess: function() {
                return null;
            }
        });

        /** init method which create export buttons */
        ExportAction.init = function ($gridContainer, gridName) {
            var metadata = $gridContainer.data('metadata');
            var actions  = metadata.massActions;
            ExportAction.createPanel($gridContainer);

            for (var key in actions) {
                var action = actions[key];
                if (action.type == 'export') {
                    new ExportAction(
                        _.extend({ $gridContainer: $gridContainer, gridName: gridName }, action)
                    );
                }
            }
        };

        /** Create the dropdown panel which contains export buttons */
        ExportAction.createPanel = function ($gridContainer) {
            $gridContainer
                .find('div.grid-toolbar>.pull-left')
                .append(
                    '<div class="export-actions-panel btn-group buffer-left">' +
                        '<button href="javascript:void(0);" class="action btn dropdown-toggle" title="Export" data-toggle="dropdown">' +
                            '<i class="icon-download-alt"></i>' +
                            __('pim.grid.mass_action.quick_export.title') +
                            '<i class="caret"></i>' +
                        '</button>' +
                        '<ul class="dropdown-menu"></ul>' +
                    '</div>'
                );
        };

        return ExportAction;
    }
);
