define(['jquery', 'core/str', 'core/ajax', 'highcharts'], function($, str, ajax, Highcharts) {
    let dataset, chart, completionPercentage;

    function getData() {
        console.log("dataset dataset dataset:", dataset.data);
        return [
            {
                name: "Completed",
                y: dataset.data.Completed,
                color: '#0f6cbf'  // Color for Completed
            },
            {
                name: "Pending",
                y: dataset.data.Pending,
                color: '#eeaf00'  // Color for Pending
            }
        ];
    }

    function getSubtitle() {
        return `<span style="font-size: 80px">${completionPercentage}%</span><br><span style="font-size: 22px">${dataset.status}</span>`;
    }

    function update() {
        chart.update({
            subtitle: {
                text: getSubtitle()
            }
        });

        chart.series[0].update({
            name: dataset.status,
            data: getData()
        });
    }

    return {
        init: function(data) {
            dataset = JSON.parse(data);
            completionPercentage = dataset.completion_percentage;

            chart = Highcharts.chart('container', {
                title: {
                    text: '',
                    align: 'center'
                },
                subtitle: {
                    useHTML: true,
                    text: getSubtitle(),
                    floating: true,
                    verticalAlign: 'middle',
                    y: 30
                },
                legend: {
                    enabled: true,
                    itemHoverStyle: {
                        color: '#FF0000'
                    },
                    align: 'center',
                    verticalAlign: 'bottom',
                    layout: 'horizontal',
                    symbolRadius: 55,
                    symbolRadius: 10,  // Keep symbols circular
                    symbolHeight: 18,  // Increase circle height
                    symbolWidth: 25,   // Increase circle width
                    itemStyle: {
                        fontSize: '16px',
                        fontWeight: 'normal'
                    }
                },
                tooltip: {
                    valueDecimals: 2,
                },
                plotOptions: {
                    pie: {
                        borderWidth: 0,
                        colorByPoint: true,
                        size: '100%',
                        innerSize: '70%',
                        dataLabels: {
                            enabled: false,
                            crop: false,
                            distance: '-20%',
                            style: {
                                fontWeight: 'bold',
                                fontSize: '16px'
                            },
                            connectorWidth: 0
                        },
                        center: ['50%', '50%'],
                        states: {
                            hover: {
                                brightness: 0.1  // Highlight effect on hover
                            }
                        }
                    }
                },
                colors: ['#0f6cbf', '#eeaf00'],  // Colors for the donut chart
                series: [{
                    type: 'pie',
                    name: dataset.status,
                    data: getData(),
                    showInLegend: true  // Display legend items for each data point
                }],
                credits: {
                    enabled: false
                }
            });

        }
    };
});
