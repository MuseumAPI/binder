(function () {

  'use strict';

  angular.module('drmc.services').service('ModalLinkSupportingTechnologyService', function ($modal, SETTINGS) {

    this.open = function (id) {
      var configuration = {
        templateUrl: SETTINGS.viewsPath + '/modals/link-supporting-technology.html',
        backdrop: 'static',
        controller: 'LinkSupportingTechnologyCtrl',
        windowClass: 'modal-large',
        resolve: {
          id: function () {
            return id;
          }
        }
      };
      return $modal.open(configuration);
    };

    // Close the dialog
    this.cancel = function () {
      $modal.dismiss('cancel');
    };

  });

})();
