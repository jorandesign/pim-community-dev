 'use strict';

define(
    [
        'jquery',
        'underscore',
        'backbone',
        'pim/form',
        'routing',
        'text!pim/template/product/meta/groups',
        'text!pim/template/product/meta/group-modal',
        'pim/user-context',
        'pim/group-manager',
        'pim/variant-group-manager',
        'pim/attribute-manager',
        'oro/navigation',
        'backbone/bootstrap-modal'
    ],
    function (
        $,
        _,
        Backbone,
        BaseForm,
        Routing,
        formTemplate,
        modalTemplate,
        UserContext,
        GroupManager,
        VariantGroupManager,
        AttributeManager,
        Navigation
    ) {
        var FormView = BaseForm.extend({
            tagName: 'span',
            template: _.template(formTemplate),
            modalTemplate: _.template(modalTemplate),
            events: {
                'click a[data-group]': 'displayModal'
            },
            families: [],
            configure: function () {
                this.listenTo(this.getRoot().model, 'change:groups', this.render);

                return BaseForm.prototype.configure.apply(this, arguments);
            },
            render: function () {
                if (!this.configured) {
                    return this;
                }

                this.getProductGroups(this.getData()).done(_.bind(function (groups) {
                    this.$el.html(
                        this.template({
                            groups: groups,
                            locale: UserContext.get('catalogLocale')
                        })
                    );

                    this.delegateEvents();
                }, this));

                return this;
            },
            getProductGroups: function (product) {
                var deferred = $.Deferred();

                var promises = [];
                var groups = [];

                _.each(product.groups, function (groupCode) {
                    promises.push(
                        GroupManager.getGroup(groupCode).then(function (group) {
                            groups.push(group);
                        })
                    );
                });

                if (product.variant_group) {
                    promises.push(
                        VariantGroupManager.getVariantGroup(product.variant_group).then(function (group) {
                            groups.push(group);
                        })
                    );
                }

                $.when.apply($, promises).done(function () {
                    deferred.resolve(groups);
                });

                return deferred.promise();
            },
            getProductList: function (groupCode) {
                return $.getJSON(
                    Routing.generate('pim_enrich_group_rest_list_products', { identifier: groupCode })
                ).then(function (data) {
                    return data;
                });
            },
            displayModal: function (event) {
                this.getProductGroups(this.getData()).done(_.bind(function (groups) {
                    var group = _.findWhere(groups, { code: event.currentTarget.dataset.group });

                    $.when(
                        this.getProductList(group.code),
                        AttributeManager.getIdentifierAttribute()
                    ).done(_.bind(function (productList, identifier) {
                        var groupModal = new Backbone.BootstrapModal({
                            allowCancel: true,
                            okText: _.__('pim_enrich.entity.product.meta.groups.modal.view_group'),
                            cancelText: _.__('Close'),
                            title: _.__(
                                'pim_enrich.entity.product.meta.groups.modal.title',
                                { group: group.label[UserContext.get('catalogLocale')] || '[' + group.code + ']' }
                            ),
                            content: this.modalTemplate({
                                products:     productList.products,
                                productCount: productList.productCount,
                                identifier:   identifier,
                                locale:       UserContext.get('catalogLocale')
                            })
                        });

                        groupModal.on('ok', function visitGroup() {
                            groupModal.close();
                            Navigation.getInstance().setLocation(
                                Routing.generate(
                                    group.type === 'VARIANT' ?
                                        'pim_enrich_variant_group_edit' :
                                        'pim_enrich_group_edit',
                                    { id: group.meta.id }
                                )
                            );
                        });
                        groupModal.open();

                        groupModal.$el.on('click', 'a[data-product-id]', function visitProduct(event) {
                            groupModal.close();
                            Navigation.getInstance().setLocation(
                                Routing.generate(
                                    'pim_enrich_product_edit',
                                    { id: event.currentTarget.dataset.productId }
                                )
                            );
                        });
                    }, this));
                }, this));
            }
        });

        return FormView;
    }
);
