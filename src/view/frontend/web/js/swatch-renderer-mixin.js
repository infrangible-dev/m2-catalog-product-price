/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'jquery',
    'priceUtils',
    'mage/template'
], function ($, priceUtils, template) {
    'use strict';

    const swatchRendererMixin = {
        catalogProductPriceOptions: {
            priceBoxSelection: '.product-info-main [data-role=priceBox]',
            finalPriceSelection: '.product-info-main .normal-price',
            finalPriceDisplayLabelSelection: '.product-info-main .normal-price .price-label',
            finalPricePriceSelection: '.product-info-main .normal-price .price-wrapper',
            finalPriceInformationSelection: '.product-info-main .normal-price .price-information',
            oldPriceDisplayLabelSelection: '.product-info-main .old-price .price-label',
            oldPricePriceSelection: '.product-info-main .old-price .price-wrapper',
            oldPriceInformationSelection: '.product-info-main .old-price .price-information',
            informationSelection: '.product-info-main [data-role=priceBox] > .price-information',
            informationDisplayLabelSelection: '.product-info-main .price-information .price-label',
            informationPriceSelection: '.product-info-main .price-information .price-wrapper',
            informationInformationSelection: '.product-info-main .price-information .price-information'
        },

        _UpdatePrice: function () {
            this._super();

            const prices = this._getNewPrices(),
                options = this.catalogProductPriceOptions;

            $(options.priceBoxSelection).find('span:first').removeClass('special-price');

            // labels are plain text, information texts are HTML with the tags the module allows
            if (!this._updatePriceLabel(options.finalPriceDisplayLabelSelection, prices.finalPrice.displayLabel)) {
                $(options.finalPriceSelection).toggleClass(
                    'special-price',
                    prices.oldPrice.amount !== prices.finalPrice.amount
                );
            }

            this._updateFinalPriceInformation(prices.finalPrice.information);
            this._updatePriceLabel(options.oldPriceDisplayLabelSelection, prices.oldPrice.displayLabel);
            this._updateOldPriceInformation(prices.oldPrice.information);
            this._updatePriceInformation(prices.priceInformation);
        },

        /**
         * Shows the label in the element of the selection, or hides the element without label.
         *
         * @param {string} selection
         * @param {?string} label
         * @returns {boolean} whether the label is shown
         */
        _updatePriceLabel: function (selection, label) {
            const labelNode = $(selection);

            if (label) {
                labelNode.text(label);
                labelNode.show();

                return true;
            }

            labelNode.text('');
            labelNode.hide();

            return false;
        },

        /**
         * Shows the information text of the final price after it, or hides it without text.
         *
         * @param {?string} information
         */
        _updateFinalPriceInformation: function (information) {
            let informationNode = $(this.catalogProductPriceOptions.finalPriceInformationSelection);

            if (informationNode.length === 0) {
                informationNode = $('<span>', {class: 'price-information'});
                $(this.catalogProductPriceOptions.finalPricePriceSelection).after(informationNode);
            }

            if (information) {
                informationNode.html(information);
                informationNode.show();
            } else {
                informationNode.html('');
                informationNode.hide();
            }
        },

        /**
         * Shows the information text of the old price after it, or removes it without text.
         *
         * @param {?string} information
         */
        _updateOldPriceInformation: function (information) {
            let informationNode = $(this.catalogProductPriceOptions.oldPriceInformationSelection);

            if (!information) {
                informationNode.remove();

                return;
            }

            if (informationNode.length === 0) {
                informationNode = $('<span>', {class: 'price-information'});
                $(this.catalogProductPriceOptions.oldPricePriceSelection).after(informationNode);
            }

            informationNode.html(information);
            informationNode.show();
        },

        /**
         * Shows the information price of the variant before the final price, or removes it without one.
         *
         * @param {?Object} priceInformation
         */
        _updatePriceInformation: function (priceInformation) {
            let informationNode = $(this.catalogProductPriceOptions.informationSelection);

            if (!priceInformation) {
                informationNode.remove();

                return;
            }

            if (informationNode.length === 0) {
                informationNode = $('<span>', {class: 'price-information'});
                $(this.catalogProductPriceOptions.finalPriceSelection).before(informationNode);
            }

            informationNode.html(template($('#catalog-product-price-information-template').html(), {
                displayLabel: priceInformation.displayLabel,
                amountValue: priceInformation.price,
                // formatted like Magento's price box formats the other prices, without sign
                amount: priceInformation.price ? priceUtils.formatPriceLocale(
                    priceInformation.price,
                    this.options.jsonConfig.currencyFormat,
                    false
                ) : null,
                information: priceInformation.information
            }));
        }
    };

    return function (targetWidget) {
        $.widget('mage.SwatchRenderer', targetWidget, swatchRendererMixin);

        return $.mage.SwatchRenderer;
    };
});
