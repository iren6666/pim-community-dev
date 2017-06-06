/**
 * @author    Yohan Blain <yohan.blain@akeneo.com>
 * @copyright 2017 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */
'use strict';

define([
    'underscore',
    'pim/controller/base',
    'pim/form-builder',
    'pim/fetcher-registry',
    'pim/user-context',
    'pim/page-title',
    'pim/error',
    'pim/i18n',
    'pim/attribute-edit-form/type-specific-form-registry'
],
function (
    _,
    BaseController,
    FormBuilder,
    FetcherRegistry,
    UserContext,
    PageTitle,
    Error,
    i18n,
    FormRegistry
) {
    return BaseController.extend({
        /**
         * {@inheritdoc}
         */
        renderRoute: function (route) {
            return FetcherRegistry.getFetcher('attribute').fetch(route.params.code, {cached: false})
                .then(function (attribute) {
                    if (!this.active) {
                        return;
                    }

                    var label = _.escape(
                        i18n.getLabel(
                            attribute.labels,
                            UserContext.get('catalogLocale'),
                            attribute.code
                        )
                    );

                    PageTitle.set({'attribute.label': label});

                    return FormBuilder.buildForm('pim-attribute-edit-form')
                        .then(function (form) {
                            form.setAdditionalView(
                                'type-specific',
                                FormRegistry.initialize().getFormName(attribute.type, 'edit')
                            );

                            return form.configure().then(function () {
                                return form;
                            });
                        })
                        .then(function (form) {
                            this.on('pim:controller:can-leave', function (event) {
                                form.trigger('pim_enrich:form:can-leave', event);
                            });
                            form.setData(attribute);
                            form.trigger('pim_enrich:form:entity:post_fetch', attribute);
                            form.setElement(this.$el).render();
                        }.bind(this));
                }.bind(this))
            .fail(function (response) {
                var errorView = new Error(response.responseJSON.message, response.status);
                errorView.setElement(this.$el).render();
            });
        }
    });
});
