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

            const prices = this._getNewPrices();

            $(this.catalogProductPriceOptions.priceBoxSelection).find('span:first').removeClass('special-price');

            const finalPriceDisplayLabelNode = $(this.catalogProductPriceOptions.finalPriceDisplayLabelSelection);

            // Labels are plain text, information texts are HTML with the tags the module allows.
            const finalPriceDisplayLabel = prices.finalPrice.displayLabel;
            if (finalPriceDisplayLabel) {
                finalPriceDisplayLabelNode.text(finalPriceDisplayLabel);
                finalPriceDisplayLabelNode.show();
            } else {
                finalPriceDisplayLabelNode.text('');
                finalPriceDisplayLabelNode.hide();

                const isShow = prices.oldPrice.amount !== prices.finalPrice.amount;
                $(this.catalogProductPriceOptions.finalPriceSelection).toggleClass('special-price', isShow);
            }

            let finalPriceInformationNode = $(this.catalogProductPriceOptions.finalPriceInformationSelection);
            if (finalPriceInformationNode.length === 0) {
                finalPriceInformationNode = $('<span>', {class: 'price-information'});
                $(this.catalogProductPriceOptions.finalPricePriceSelection).after(finalPriceInformationNode);
            }

            const finalPriceInformation = prices.finalPrice.information;
            if (finalPriceInformation) {
                finalPriceInformationNode.html(finalPriceInformation);
                finalPriceInformationNode.show();
            } else {
                finalPriceInformationNode.html('');
                finalPriceInformationNode.hide();
            }

            const oldPriceDisplayLabelNode = $(this.catalogProductPriceOptions.oldPriceDisplayLabelSelection);

            const oldPriceDisplayLabel = prices.oldPrice.displayLabel;
            if (oldPriceDisplayLabel) {
                oldPriceDisplayLabelNode.text(oldPriceDisplayLabel);
                oldPriceDisplayLabelNode.show();
            } else {
                oldPriceDisplayLabelNode.text('');
                oldPriceDisplayLabelNode.hide();
            }

            let oldPriceInformationNode = $(this.catalogProductPriceOptions.oldPriceInformationSelection);

            const oldPriceInformation = prices.oldPrice.information;
            if (oldPriceInformation) {
                if (oldPriceInformationNode.length === 0) {
                    oldPriceInformationNode = $('<span>', {class: 'price-information'});
                    $(this.catalogProductPriceOptions.oldPricePriceSelection).after(oldPriceInformationNode);
                }

                oldPriceInformationNode.html(oldPriceInformation);
                oldPriceInformationNode.show();
            } else {
                if (oldPriceInformationNode.length > 0) {
                    oldPriceInformationNode.remove();
                }
            }

            let informationNode = $(this.catalogProductPriceOptions.informationSelection);

            const priceInformation = prices.priceInformation;
            if (priceInformation) {
                if (informationNode.length === 0) {
                    informationNode = $('<span>', {class: 'price-information'});
                    $(this.catalogProductPriceOptions.finalPriceSelection).before(informationNode);
                }

                const templateData = {};

                templateData.displayLabel = priceInformation.displayLabel;

                const informationAmountValue = priceInformation.price;
                templateData.amountValue = informationAmountValue;
                // formatted like Magento's price box formats the other prices, without sign
                templateData.amount = informationAmountValue ? priceUtils.formatPriceLocale(
                    informationAmountValue,
                    this.options.jsonConfig.currencyFormat,
                    false
                ) : null;

                templateData.information = priceInformation.information;

                informationNode.html(template($('#catalog-product-price-information-template').html(), templateData));
            } else {
                if (informationNode.length > 0) {
                    informationNode.remove();
                }
            }
        }
    };

    return function (targetWidget) {
        $.widget('mage.SwatchRenderer', targetWidget, swatchRendererMixin);

        return $.mage.SwatchRenderer;
    };
});
