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
    'pim/i18n'
],
function (
    _,
    BaseController,
    FormBuilder,
    FetcherRegistry,
    UserContext,
    PageTitle,
    Error,
    i18n
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

                    var formName = 'pim_catalog_identifier' === attribute.type ?
                        'pim-attribute-identifier-edit-form' :
                        'pim-attribute-edit-form';

                    return FormBuilder.buildForm(formName)
                        .then(function (form) {
                            form.setType(attribute.type);

                            return form.configure().then(function () {
                                return form;
                            });
                        })
                        .then(function (form) {
                            this.on('pim:controller:can-leave', function (event) {
                                form.trigger('pim_enrich:form:can-leave', event);
                            });

                            // TODO: check if it is still needed with the new mass edith
                            form.on('pim_enrich:form:entity:post_fetch', function (data) {
                                FetcherRegistry.getFetcher('attribute').clear(data.code);
                            });

                            form.setData(attribute);
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
