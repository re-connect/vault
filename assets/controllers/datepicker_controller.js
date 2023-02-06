import { Controller } from '@hotwired/stimulus';
import tempusDominus from "@eonasdan/tempus-dominus";
import moment from 'moment';

const momentPlugin = (option, tdClasses, tdFactory) => {
    tdClasses.Dates.prototype.setFromInput = function(value, index) {
        let converted = moment(value, option);
        if (converted.isValid()) {
            let date = tdFactory.DateTime.convert(converted.toDate(), this.optionsStore.options.localization.locale);
            this.setValue(date, index);
        }
        else {
            console.warn('Momentjs failed to parse the input date.');
        }
    }

    tdClasses.Dates.prototype.formatInput = function(date) {
        return moment(date).format(option);
    }
}

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input']

    connect() {
        tempusDominus.extend(momentPlugin, 'DD/MM/yyyy HH:mm');
        new tempusDominus.TempusDominus(this.inputTarget, {
            display: {
                components: {
                    useTwentyfourHour: true
                },
                sideBySide: true,
                icons: {
                    type: 'icons',
                    time: 'fa fa-solid fa-clock',
                    date: 'fa fa-solid fa-calendar',
                    up: 'fa fa-solid fa-arrow-up',
                    down: 'fa fa-solid fa-arrow-down',
                    previous: 'fa fa-solid fa-chevron-left',
                    next: 'fa fa-solid fa-chevron-right',
                    today: 'fa fa-solid fa-calendar-check',
                    clear: 'fa fa-solid fa-trash',
                    close: 'fa fa-solid fa-xmark'
                },
            },
        });
    }
}
