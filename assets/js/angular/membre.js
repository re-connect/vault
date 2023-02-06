app.controller('MembresListCtrl', function ($scope, $http, $filter) {
	$http.get(Routing.generate('api_get_membres_from_user_handles_centre')).success(function(data) {
		$scope.membres = data;
		$scope.currentPage = 1;
		$scope.pageSize = 10;
		$scope.numberOfPages=function(){
			return Math.ceil($scope.membres.length/$scope.pageSize);                
		};
	});

       $scope.hasRight = function(membre, right) {
              let bResult = false;
              $.each(membre.membres_centres, function (key, testedMembreCentre) {
                     if (testedMembreCentre.centre.nom === $scope.centreName) {
                            bResult = testedMembreCentre.droits[right];
                     }
              });
              return bResult;
       };

	$scope.changeRight = function(membre, droit) {
		$http.post(Routing.generate("api_changer_droits_membre_centre", {"id" : membre.id}), {"centreName" : $scope.centreName, "droit" : droit}).success(function(data) {
			$http.get(Routing.generate('api_get_membres_from_user_handles_centre')).success(function(data2) {
				$scope.membres = data2;
			});
		});
	};
});
