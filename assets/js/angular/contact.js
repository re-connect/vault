'use strict';

import $ from 'jquery';
require("../base-personal-data");

app.controller("ContactsListCtrl", function ($scope, $http, $filter) {
    $scope.refresh = function () {
        $http.get($scope.getRoute("api_contact_list", beneficiaireId)).success(function (data) {
            $scope.contacts = data;
            $scope.currentPage = 1;
            $scope.pageSize = 10;
            $scope.numberOfPages = function () {
                return Math.ceil($scope.contacts.length / $scope.pageSize);
            };
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

    $scope.toggleAccess = function (contact) {
        $http.patch($scope.getRoute("api_contact_toggle_access", contact.id)).success(function (data) {
            const contactEdit = $scope.contacts.find(function (x) {
                return (x.id === contact.id);
            });
            contactEdit.b_prive = data.b_prive;
        });
    };

    $scope.delete = function (contact) {
        $http.delete($scope.getRoute("api_contact_delete", contact.id)).success(function (data) {
            // $scope.contacts = data;
            $scope.refresh();
        });

    };

    $scope.activateLine = function (contact, e) {
        $('.tableLine').removeClass('tableLine-active');
        $(angular.element(e.currentTarget)[0]).children("td").each(function () {
            $(this).addClass("tableLine");
            $(this).addClass("tableLine-active");
        });
        $scope.$broadcast('lineActivated', {contact: contact});
    };

    $scope.getEditForm = (entity) => {
        $http.get($scope.getRoute("re_app_contact_edit", entity.id)).success(function (form) {
            $("#editModalForm").html(form);
        });
    };

    $scope.reportAbuse = function (entity) {
        $http.patch($scope.getRoute("api_contact_report_abuse", entity.id)).success(function () {
            $scope.contacts.splice($scope.contacts.indexOf(entity), 1);
        });
    };

    $scope.getMessageDelete = entity => {
        const deleteMessage = $('.delete-button').data('message');

        return deleteMessage + " : \"" + entity.nom + " "+ entity.prenom + "\"?";
    }

    $scope.refresh();
});

app.controller("editModalCtrl", function ($scope, $http) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.contact;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");

    $scope.submitForm = () => {
        const formSerialize = $("#editModalForm").serialize();

        $http({
            method: 'POST',
            url: $scope.getRoute("re_app_contact_edit", $scope.entity.id),
            data: formSerialize,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
            .success(() => {
                $("#editModal").modal("hide");

                $("#alertSuccessMessage").append('Contact modifié.');
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
        $http.get($scope.getRoute("re_app_contact_add", beneficiaireId)).success(form => {
            $("#newModalForm").html($(form));
        });
    };
});

app.controller("newModalCtrl", function ($scope, $http) {
    $scope.submitForm = () => {
        const formSerialize = $("#newModalForm").serialize();

        $http({
            method: 'POST',
            url: $scope.getRoute("re_app_contact_add", beneficiaireId),
            data: formSerialize,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
            .success(() => {
                $("#newModal").modal("hide");

                $("#alertSuccessMessage").append('Contact créé.');
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
        $scope.entity = args.contact;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");
});

app.controller("EntityApercuModalCtrl", function ($scope) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.contact;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");
});
