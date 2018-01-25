(function () {

  'use strict';

  angular.module('drmc.controllers').controller('TmsBrowserCtrl', function ($scope, $http, SETTINGS) {

    $scope.submitted = false;

    var endpoints = {
      'ObjectID': {
        url: SETTINGS.drmc.tms_url + '/GetTombstoneDataRest/ObjectID/',
        group: 'GetTombstoneDataRestIdResult',
        regex: /^[0-9]+$/
      },
      'AccessionNo': {
        url: SETTINGS.drmc.tms_url + '/GetTombstoneDataRest/Object/',
        group: 'GetTombstoneDataRestResult'
      }
    };

    $scope.submit = function () {
      if (!$scope.query || $scope.query.length < 1) {
        return;
      }
      $scope.endpoint = endpoints.AccessionNo;
      if (endpoints.ObjectID.regex.test($scope.query)) {
        $scope.endpoint = endpoints.ObjectID;
      }
      $http.get($scope.endpoint.url + $scope.query)
        .then(function (response) {
          if (response.data[$scope.endpoint.group].ObjectID === 0) {
            $scope.found = false;
            return;
          }
          $scope.found = true;
          $scope.results = response.data[$scope.endpoint.group];
          $scope.thumbnail = response.data[$scope.endpoint.group].Thumbnail;
        }, function () {
          $scope.found = false;
        }).finally(function () {
          $scope.submitted = true;
        });
    };

    $scope.zoom = function () {
      $scope.thumbnail = $scope.results.FullImage;
      $scope.zoomed = true;
    };

  });

})();
