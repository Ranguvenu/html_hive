define([], function () {
    window.requirejs.config({
        paths: {
            "moment": M.cfg.wwwroot + '/blocks/training_calendar/js/moment.min',
            "fullcalendar": M.cfg.wwwroot + '/blocks/training_calendar/js/fullcalendar.min',
        },
        shim: {
            'moment': {exports: 'moment'},
            'fullcalendar': {exports: 'fullCalendar'},
        }
    });
});