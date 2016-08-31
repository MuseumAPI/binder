(function () {

  'use strict';

  angular.module('drmc.services').service('StatisticsService', function ($http, SETTINGS) {

    /**
     * API endpoints
     *
     * - /api/activity/downloads
     * - /api/activity/ingestion
     * - /api/summary/ingestion
     * - /api/summary/artworkbydate
     * - /api/summary/mediafilesizebycollectionyear
     * - /api/summary/departmentartworkcount
     * - /api/summary/storagebycodec
     * - /api/summary/storagebymediacategory
     *
     */

    this.getDownloadActivity = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/activity/downloads'
      });
    };

    this.getIngestionActivity = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/activity/ingestion'
      });
    };

    this.getIngestionSummary = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/ingestion'
      });
    };

    this.getArtworkByMonthSummary = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/artworkbydate'
      });
    };

    this.getArtworkSizesByYearSummary = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/mediafilesizebycollectionyear'
      });
    };

    // Artwork counts and running totals by ingestion month and collection year
    this.getArtworkCountsAndTotalsByDate = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/artworkbydate'
      });
    };

    // Artwork count per department
    this.getRunningTotalByDepartment = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/departmentartworkcount'
      });

    };

    // Storage used per codec
    this.getRunningTotalByCodec = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/storagebycodec'
      });
    };

    // Storage used per media category
    this.getRunningTotalByFormat = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/storagebymediacategory'
      });
    };

    this.getStorageSizeByDateSummary = function () {
      return $http({
        method: 'GET',
        url: SETTINGS.frontendPath + 'api/summary/storagesizebydate'
      });
    };

  });

})();
