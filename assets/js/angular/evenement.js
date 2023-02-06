'use strict';

import $ from 'jquery';
require("../base-personal-data");

$('#newModal').on('hidden.bs.modal', function (e) {
    $("#newModalForm").empty();
});

$('#editModal').on('hidden.bs.modal', function (e) {
    $("#editModalForm").empty();
});

app.controller("EvenementsListCtrl", function ($scope, $http, $filter, $window) {
    $scope.refresh = function () {
        $http.get($scope.getRoute("api_evenement_list", beneficiaireId)).success(function (data) {
            $scope.evenements = data;
            $scope.currentPage = 1;
            $scope.pageSize = 10;
            $scope.numberOfPages = function () {
                return Math.ceil($scope.evenements.length / $scope.pageSize);
            };
            $scope.today = $filter('date')(new Date(), 'yyyyMMdd');
        });
    };

    $scope.toggleAccess = function (evenement) {
        $http.patch($scope.getRoute("api_evenement_toggle_access", evenement.id)).success(function (data) {
            const evenementEdit = $scope.evenements.find(function (x) {
                return (x.id === evenement.id);
            });
            evenementEdit.b_prive = data.b_prive;
        });
    };

    $scope.getShareIconClass = function (folderDoc) {
        if (folderDoc.b_prive) {
            return "text-secondary";
        }
        return "vault-green";
    };

    $scope.getLockIconClass = function (folderDoc) {
        if (folderDoc.b_prive) {
            return " text-primary";
        }
        return "-open text-secondary";
    };

    $scope.delete = function (evenement) {
        $http.delete($scope.getRoute("api_evenement_delete", evenement.id)).success(function (data) {
            $scope.refresh();
        });
    };

    $scope.activateLine = function (evenement, e) {
        $(".tableLine").removeClass("tableLine-active");
        $(angular.element(e.currentTarget)[0]).children("td").each(function () {
            $(this).addClass("tableLine");
            $(this).addClass("tableLine-active");
        });
        $scope.$broadcast("lineActivated", {evenement: evenement});
    };

    $scope.getEditForm = (entity) => {
        $http.get($scope.getRoute("re_app_evenement_edit", entity.id)).success(function (form) {
            $("#editModalForm").html(form);
        });
    };

    $scope.reportAbuse = function (entity) {
        $http.patch($scope.getRoute("api_evenement_report_abuse", entity.id)).success(function () {
            $scope.evenements.splice($scope.evenements.indexOf(entity), 1);
        });
    };

    $scope.getMessageDelete = entity => {
        const deleteMessage = $('.delete-button').data('message');

        return deleteMessage + " : \"" + entity.nom + "\"?";
    }

    $scope.refresh();
});

app.controller("editModalCtrl", function ($scope, $http) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.evenement;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");

    $scope.submitForm = () => {
        const formSerialize = $("#editModalForm").serialize();

        $http({
            method: 'POST',
            url: $scope.getRoute("re_app_evenement_edit", $scope.entity.id),
            data: formSerialize,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
            .success(() => {
                $("#editModal").modal("hide");

                $("#alertSuccessMessage").append('Événement modifié.');
                $("#alertSuccess").fadeTo(2000, 500);
                setTimeout(() => {
                    $("#alertSuccess").slideUp(500, () => {
                        $("#alertSuccessMessage").html('');
                    });
                }, 5000);

                $scope.refresh();
            })
            .error(form => {
                $("#editModalForm").html($(form));
            });
    };
});

app.controller("EntityAddCtrl", function ($scope, $http) {
    $scope.getNewForm = () => {
        $http.get($scope.getRoute("re_app_evenement_new", beneficiaireId)).success(form => {
            $("#newModalForm").html($(form));
        });
    };
});

app.controller("newModalCtrl", function ($scope, $http) {
    $scope.submitForm = () => {
        const formSerialize = $("#newModalForm").serialize();

        $http({
            method: 'POST',
            url: $scope.getRoute("re_app_evenement_new", beneficiaireId),
            data: formSerialize,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
            .success(() => {
                $("#newModal").modal("hide");

                $("#alertSuccessMessage").append('Événement créé.');
                $("#alertSuccess").fadeTo(2000, 500);
                setTimeout(() => {
                    $("#alertSuccess").slideUp(500, () => {
                        $("#alertSuccessMessage").html('');
                    });
                }, 5000);

                $scope.refresh();
            })
            .error(form => {
                $("#newModalForm").html($(form));
            });
    };
});

app.controller("EntityInformationModalCtrl", function ($scope) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.evenement;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");
});

app.controller("EntityApercuModalCtrl", function ($scope) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.evenement;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");
});