(function () {

  'use strict';

  angular.module('drmc.controllers').controller('DashboardCtrl', function ($scope, $q, $timeout, $filter, StatisticsService, FixityService, AIPService, SETTINGS) {

    var timer;

    var pull = function () {

      var queries = [
        // StatisticsService.getArtworkByMonthSummary(),
        StatisticsService.getDownloadActivity(),
        StatisticsService.getIngestionActivity(),
        StatisticsService.getIngestionSummary(),
        StatisticsService.getRunningTotalByDepartment(),
        StatisticsService.getRunningTotalByCodec(),
        StatisticsService.getRunningTotalByFormat(),
        StatisticsService.getArtworkSizesByYearSummary(),
        StatisticsService.getArtworkCountsAndTotalsByDate()
      ];

      $q.all(queries).then(function (responses) {
        $scope.downloadActivity = responses[0].data.results;
        $scope.ingestionActivity = responses[1].data.results;
        $scope.ingestionSummary = {
          accessKey: 'total',
          formatKey: 'type',
          data: responses[2].data.results
        };
        $scope.countByDepartment = {
          accessKey: 'count',
          formatKey: 'department',
          data: responses[3].data.results
        };
        $scope.storageCodecs = {
          accessKey: 'total',
          formatKey: 'codec',
          data: responses[4].data.results
        };
        $scope.storageFormats = {
          accessKey: 'total',
          formatKey: 'media_type',
          data: responses[5].data.results
        };
        $scope.artworkSizes = [{
          name: 'Average',
          color: 'steelblue',
          xProperty: 'year',
          yProperty: 'average',
          data: responses[6].data.results
        }];
        $scope.yearlyCountsByCollectionDate = [{
          name: 'Year',
          color: 'hotpink',
          xProperty: 'year',
          yProperty: 'count',
          data: responses[7].data.results.collection
        }];
        $scope.monthlyCountsByCreation = [{
          name: 'Month',
          color: 'hotpink',
          xProperty: 'month',
          xLabelFormat: 'yearAndMonth',
          yProperty: 'count',
          data: responses[7].data.results.creation
        }];
        $scope.yearlyTotalsByCollectionDate = [{
          name: 'Year',
          color: 'hotpink',
          xProperty: 'year',
          yProperty: 'total',
          data: responses[7].data.results.collection
        }];
        $scope.monthlyTotalsByCreation = [{
          name: 'Month',
          color: 'hotpink',
          xProperty: 'month',
          xLabelFormat: 'yearAndMonth',
          yProperty: 'total',
          data: responses[7].data.results.creation
        }];
        // Levels of description to determine part_of link in ingests tab
        $scope.artworkId = parseInt(SETTINGS.drmc.lod_artwork_record_id);
        $scope.techId = parseInt(SETTINGS.drmc.lod_supporting_technology_record_id);
      }, function (responses) {
        console.log('Something went wrong', responses);
      });

    };

    pull();

    // Expose rounded version of unit filter so it can be supplied, as an attribute, to directives
    $scope.unitFilterRounded = function (value) {
      return $filter('UnitFilter')(value, 2);
    };

    // If the user changes the overview state, it's not changed again on update
    var isOverviewToggled = false;
    $scope.showOverview = false;
    $scope.toggleOverview = function () {
      $scope.showOverview = !$scope.showOverview;
      isOverviewToggled = true;
    };

    var getFixityWidgetData = function () {
      FixityService.getStatusFixity({ limit: 5 }).then(function (response) {
        $scope.fixityStats = response.data;
        if ($scope.fixityStats.hasOwnProperty('lastFails') && $scope.fixityStats.unrecoveredFailsCount > 0) {
          $scope.fixityHasFails = true;
          if (!isOverviewToggled) {
            $scope.showOverview = true;
          }
        } else {
          $scope.fixityHasFails = false;
          if (!isOverviewToggled) {
            $scope.showOverview = false;
          }
        }
        // Convert boolean to human-friendly string
        angular.forEach($scope.fixityStats.lastChecks, function (e) {
          if (e.outcome === false) {
            e.statusAlert = 'Failed';
          } else if (e.outcome === true) {
            e.statusAlert = 'Success';
          } else {
            return;
          }
        });
        // Update on success and error
        timer = $timeout(getFixityWidgetData, 1000);
      }, function () {
        timer = $timeout(getFixityWidgetData, 1000);
      });
    };

    getFixityWidgetData();

    // Allow request for AIP recovery
    $scope.requestRecover = function (reportId) {
      AIPService.recoverAip(reportId)
        .success(function () {
          pull();
        })
        .error(function () {
          console.log('Error requesting AIP recovery');
        });
    };

    $scope.$on('$destroy', function () {
      $timeout.cancel(timer);
    });

  });

})();
