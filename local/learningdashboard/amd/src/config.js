require.config({
    paths: {
        'highcharts': M.cfg.wwwroot + '/local/learningdashboard/js/highcharts' // Path to your local Highcharts file
    },
    shim: {
        'highcharts': {
            exports: 'Highcharts'
        }
    }
});
