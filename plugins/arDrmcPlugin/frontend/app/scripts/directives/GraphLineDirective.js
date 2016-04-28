(function () {

  'use strict';

  angular.module('drmc.directives').directive('arGraphLine', function () {

    return {
      restrict: 'E',
      replace: true,
      scope: {
        'yFilter': '=yFilter'
      },
      template: '<div><rs-y-axis></rs-y-axis><rs-chart></rs-chart><rs-x-axis></rs-x-axis></div>',
      link: function (scope, element, attrs) {

        attrs.$observe('data', function (graphSpecification) {

          var series = [],
              max = Number.MIN_VALUE,
              dataFound = false;

          if (graphSpecification) {
            var xLabels = [];

            // process data for each line of the graph
            JSON.parse(graphSpecification).forEach(function (lineData) {
              // set graph x/y values and increase max y if needed
              for (var i = 0; i < lineData.data.length; i++) {
                // note that data has been found
                dataFound = true;

                // store index as X value because Rickshaw accepts only sequential value
                lineData.data[i].x = i;

                // store real X value as a label to be diplayed using a custom formatter
                if (typeof lineData.xLabelFormat !== 'undefined') {
                  if (lineData.xLabelFormat === 'yearAndMonth') {
                    xLabels[i] = lineData.data[i].year + '-';
                    if (lineData.data[i].month < 10) {
                      xLabels[i] = xLabels[i] + '0';
                    }
                    xLabels[i] = xLabels[i] + lineData.data[i].month;
                  } else {
                    console.log('Invalid xLabelFormat.');
                  }
                } else {
                  xLabels[i] = parseInt(lineData.data[i][lineData.xProperty]);
                }

                // store y value data
                lineData.data[i].y = lineData.data[i][lineData.yProperty];

                // determine whether a new Y data ceiling should be set
                max = Math.max(max, lineData.data[i].y);
              }
              series.push(lineData);
            });

            // add padding to max
            max = max + (max / 10);

            // set optional element ID
            if (typeof attrs.id !== 'undefined') {
              element.attr('id', attrs.id);
            }

            if (dataFound) {
              var graph = new Rickshaw.Graph({
                element: element.find('rs-chart')[0],
                width: attrs.width,
                height: attrs.height,
                series: series,
                max: max
              });

              // use custom X axis formatter so we can use arbitrary X values
              var xFormat = function (i) {
                return (typeof xLabels[i] !== 'undefined') ? xLabels[i] : '';
              };

              var xAxis = new Rickshaw.Graph.Axis.X({
                graph: graph,
                element: element.find('rs-x-axis')[0],
                orientation: 'bottom',
                pixelsPerTick: attrs.xperTick,
                tickFormat: xFormat
              });
              xAxis.render();

              // allow optional use of Y axis formatter passed in as attribute
              var yFormat = (typeof scope.yFilter !== 'undefined') ? scope.yFilter : Rickshaw.Fixtures.Number.formatKMBT;

              var yAxis = new Rickshaw.Graph.Axis.Y({
                graph: graph,
                element: element.find('rs-y-axis')[0],
                pixelsPerTick: attrs.yperTick,
                orientation: 'left',
                tickFormat: yFormat
              });
              yAxis.render();

              graph.setRenderer(attrs.type);
              graph.render();
            }
          }
        });
      }
    };

  });

})();
