app.controller("BeneficiairesListCtrl", function ($scope, $http, $filter) {
	$http.get(Routing.generate("api_beneficiary_list_for_user")).success(function(data) {
		$scope.beneficiaires = data;
		$scope.currentPage = 1;
		$scope.pageSize = 10;
		$scope.numberOfPages=function(){
			return Math.ceil($scope.beneficiaires.length/$scope.pageSize);                
		};
	});

	$scope.hasBeneficiaires = function(){
		if($scope.beneficiaires) {
			return $scope.beneficiaires.length > 0;
		}
	};
});
