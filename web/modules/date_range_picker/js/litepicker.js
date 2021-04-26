(function ($, Drupal, drupalSettings) {
    'use strict';

    var formatDate = function(date){
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, "0")
         + '-' + String(date.getDate()).padStart(2, "0");
    };

    var init = function(element, startInput, endInput){
        var startDate = null;
        var endDate = null;

        if (typeof drupalSettings.date_range_picker !== 'undefined') {
            if (typeof drupalSettings.date_range_picker[element.id].start !== 'undefined') {
                startDate = new Date(drupalSettings.date_range_picker[element.id].start + ' 12:00');
            }

            if (typeof drupalSettings.date_range_picker[element.id].end !== 'undefined') {
                endDate = new Date(drupalSettings.date_range_picker[element.id].end + ' 12:00');
            }
        }

        var picker = new Litepicker({
            element: element,
            //elementEnd: null,
            firstDay: 1,
            format: "DD.MM.YYYY",
        lang: "de-DE",
        numberOfMonths: 2,
            numberOfColumns: 2,
            startDate: startDate,
            endDate: endDate,
            zIndex: 9999,
            minDate: null,
            maxDate: null,
            minDays: null,
            maxDays: null,
            selectForward: !1,
            selectBackward: !1,
            splitView: !1,
            //inlineMode: !1,
            singleMode: !1,
            //autoApply: !0,
            //allowRepick: true,
            showWeekNumbers: !1,
            showTooltip: !0,
            hotelMode: !1,
            disableWeekends: !0,
            //scrollToDate: !0,
            //mobileFriendly: !0,
            lockDaysFormat: "YYYY-MM-DD",
            lockDays: [],
            bookedDaysFormat: "YYYY-MM-DD",
            bookedDays: [],
            dropdowns: {
                minYear: 1990,
                maxYear: null,
                months: !1,
                years: !1
            },
            buttonText: {
                apply: "Apply",
                cancel: "Cancel",
                previousMonth: '<svg width="11" height="16" xmlns="http://www.w3.org/2000/svg"><path d="M7.919 0l2.748 2.667L5.333 8l5.334 5.333L7.919 16 0 8z" fill-rule="nonzero"/></svg>',
                nextMonth: '<svg width="11" height="16" xmlns="http://www.w3.org/2000/svg"><path d="M2.748 16L0 13.333 5.333 8 0 2.667 2.748 0l7.919 8z" fill-rule="nonzero"/></svg>'
            }, tooltipText: { one: "day", other: "days" }, onShow: function () {
                console.log("onShow callback")
            },
            onHide: function () {
                console.log("onHide callback")
            },
            onSelect: function (e, t) {
                startInput.val(formatDate(e));
                endInput.val(formatDate(t));
                console.log("onSelect callback e:", formatDate(e));
                console.log("onSelect callback t:", formatDate(t));
            },
            onError: function (e) {
                console.log("onError callback", e)
            },
            onChangeMonth: function (e, t) {
                console.log("onChangeMonth callback", e, t)
            },
            onChangeYear: function (e) {
                console.log("onChangeYear callback", e)
            }
        });

    };

    Drupal.behaviors.datetime_range_picker = {
        attach: function (context) {
            console.log('Moo.');

            var $dateRangeWidgets = $(context).find('.field--type-daterange');
            $dateRangeWidgets.each(function() {
                var $dateRangeWidget = $(this);

                var startInput= $(this).find('.start input').first().first();
                var endInput=$(this).find('.end input').first().first();

                console.log('startInput');
                console.log(startInput);
                console.log('endInput');
                console.log(endInput);

                $dateRangeWidget.find('.litepicker-input').once('daterange-widget').each(function() {
                    console.log('hallo');
                    console.log(this.id);
                    console.log($(this));
                    init(this, startInput, endInput);
                });
            });

        }
    }
}(jQuery, Drupal, drupalSettings));
