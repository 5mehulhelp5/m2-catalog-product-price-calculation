/**
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

define([
    'ko'
], function (ko) {
    'use strict';

    const activeCalculationsObservable = ko.observable([]);

    /**
     * The calculations that are active for the customer, sorted by priority (the highest first). The calculation
     * "default" stands for the prices of Magento.
     */
    return {
        getActiveCalculations: function () {
            return activeCalculationsObservable;
        },

        hasActiveCalculation: function (activeCalculationCode) {
            return activeCalculationsObservable().some(function (activeCalculation) {
                return activeCalculation.code === activeCalculationCode;
            });
        },

        addActiveCalculation: function (activeCalculationCode, activeCalculationPriority) {
            if (this.hasActiveCalculation(activeCalculationCode)) {
                return;
            }

            const activeCalculations = activeCalculationsObservable().slice();

            activeCalculations.push({code: activeCalculationCode, priority: activeCalculationPriority});

            activeCalculations.sort(function (a, b) {
                return b.priority - a.priority;
            });

            activeCalculationsObservable(activeCalculations);
        },

        resetActiveCalculations: function () {
            activeCalculationsObservable([{code: 'default', priority: 0}]);
        },

        removeActiveCalculation: function (activeCalculationCode) {
            activeCalculationsObservable(activeCalculationsObservable().filter(function (activeCalculation) {
                return activeCalculation.code !== activeCalculationCode;
            }));
        }
    };
});
