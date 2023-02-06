'use strict';

require("../base-personal-data");
import $ from 'jquery';

app.controller("NotesListCtrl", function ($scope, $http, $filter, $window) {
    $scope.refresh = function () {
        $http.get($scope.getRoute("api_note_list", beneficiaireId)).success(function (data) {
            $scope.notes = data;
            $scope.currentPage = 1;
            $scope.pageSize = 10;
            $scope.numberOfPages = function () {
                return Math.ceil($scope.notes.length / $scope.pageSize);
            };
        });
    };

    $scope.toggleAccess = function (note) {
        $http.patch($scope.getRoute("api_note_toggle_access", note.id)).success(function (data) {
            const noteEdit = $scope.notes.find(function (x) {
                return (x.id === note.id);
            });
            noteEdit.b_prive = data.b_prive;
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

    $scope.delete = function (note) {
        $http.delete($scope.getRoute("api_note_delete", note.id)).success(function (data) {
            $scope.refresh();
        });
    };

    $scope.activateLine = function (note, e) {
        $(".tableLine").removeClass("tableLine-active");
        $(angular.element(e.currentTarget)[0]).children("td").each(function () {
            $(this).addClass("tableLine");
            $(this).addClass("tableLine-active");
        });
        // angular.element(e.currentTarget).addClass("tableLine-active");
        $scope.$broadcast("lineActivated", {note: note});
    };

    $scope.getEditForm = (entity) => {
        $http.get($scope.getRoute("re_app_note_edit", entity.id)).success(function (form) {
            $("#editModalForm").html(form);
        });
    };

    $scope.reportAbuse = function (entity) {
        $http.patch($scope.getRoute("api_note_report_abuse", entity.id)).success(function () {
            $scope.notes.splice($scope.notes.indexOf(entity), 1);
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
        $scope.entity = args.note;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");

    $scope.submitForm = () => {
        const formSerialize = $("#editModalForm").serialize();

        $http({
            method: 'POST',
            url: $scope.getRoute("re_app_note_edit", $scope.entity.id),
            data: formSerialize,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
            .success(() => {
                $("#editModal").modal("hide");

                $("#alertSuccessMessage").append('Note modifiée.');
                $("#alertSuccess").fadeTo(2000, 500);
                setTimeout(() => {
                    $("#alertSuccess").slideUp(500, () => {
                        $("#alertSuccessMessage").html('');
                    });
                }, 5000);

                $scope.refresh();
            });
    };
});

app.controller("EntityAddCtrl", function ($scope, $http) {
    $scope.getNewForm = () => {
        $http.get($scope.getRoute("re_app_note_new", beneficiaireId)).success(form => {
            $("#newModalForm").html($(form));
        });
    };
});

app.controller("newModalCtrl", function ($scope, $http) {
    $scope.submitForm = () => {
        const formSerialize = $("#newModalForm").serialize();
        $http({
            method: 'POST',
            url: $scope.getRoute("re_app_note_new", beneficiaireId),
            data: formSerialize,
            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
        })
            .success(() => {
                $("#newModal").modal("hide");

                $("#alertSuccessMessage").append('Note créée.');
                $("#alertSuccess").fadeTo(2000, 500);
                setTimeout(() => {
                    $("#alertSuccess").slideUp(500, () => {
                        $("#alertSuccessMessage").html('');
                    });
                }, 5000);

                $scope.refresh();
            });
    };
});

app.controller("EntityInformationModalCtrl", function ($scope) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.note;
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");
});

app.controller("EntityApercuModalCtrl", function ($scope, $sce) {
    $scope.$on("lineActivated", (event, args) => {
        $scope.entity = args.note;
        $scope.noteBody = $sce.trustAsHtml($scope.entity.contenu);
    });

    $scope.isDefined = () => (typeof $scope.entity !== "undefined");
});
